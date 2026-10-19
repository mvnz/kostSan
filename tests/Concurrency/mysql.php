<?php

// Dedicated ephemeral CI database only. Refuse unsafe targets before bootstrapping.
$root = dirname(__DIR__, 2);
$required = [
    'APP_ENV' => 'testing', 'DB_CONNECTION' => 'mysql', 'DB_HOST' => '127.0.0.1',
    'DB_PORT' => '3306', 'DB_DATABASE' => 'kostsan_concurrency_test', 'DB_USERNAME' => 'kostsan_ci',
    'WHATSAPP_ENABLED' => 'false', 'MAIL_MAILER' => 'array', 'SESSION_DRIVER' => 'array',
];
foreach ($required as $key => $value) {
    if (PHP_SAPI !== 'cli' || getenv($key) !== $value) {
        fwrite(STDERR, "Refusing unsafe MySQL concurrency environment.\n");
        exit(1);
    }
}
if (getenv('DB_URL') !== '' || is_file($root.'/bootstrap/cache/config.php')) {
    fwrite(STDERR, "Refusing database URL or cached configuration.\n");
    exit(1);
}

require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$connection = config('database.connections.mysql');
if (! $app->environment('testing') || $app->configurationIsCached() || config('database.default') !== 'mysql'
    || ! empty($connection['url']) || $connection['host'] !== '127.0.0.1' || (string) $connection['port'] !== '3306'
    || $connection['database'] !== 'kostsan_concurrency_test' || $connection['username'] !== 'kostsan_ci'
    || config('whatsapp.enabled') || config('session.driver') !== 'array' || config('mail.default') !== 'array') {
    fwrite(STDERR, "Refusing resolved MySQL concurrency configuration.\n");
    exit(1);
}
Illuminate\Support\Facades\Http::preventStrayRequests();

use App\Http\Controllers\PembayaranController;
use App\Http\Controllers\ReservasiController;
use App\Http\Controllers\SewaController;
use App\Http\Controllers\KeuanganController;
use App\Http\Controllers\KamarController;
use App\Models\Invoice;
use App\Models\Kamar;
use App\Models\Keuangan;
use App\Models\Pembayaran;
use App\Models\Penghuni;
use App\Models\Reservasi;
use App\Models\Sewa;
use App\Models\User;
use App\Models\PaymentReversal;
use App\Models\FinancePaymentLinkAudit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\InputStream;

function check(bool $condition, string $message): void
{
    if (! $condition) {
        throw new RuntimeException($message);
    }
}

function operatorRequest(string $uri, string $method, array $payload, int $operator): Request
{
    $request = Request::create($uri, $method, $payload);
    $request->setUserResolver(fn () => User::findOrFail($operator));

    return $request;
}

function moveLease(Sewa $lease, int $roomId): void
{
    app(SewaController::class)->update(Request::create('/sewas/'.$lease->id, 'PUT', [
        'kamar_id' => $roomId, 'penghuni_id' => $lease->penghuni_id,
        'tanggal_masuk' => $lease->tanggal_masuk->toDateString(),
        'tanggal_keluar' => $lease->tanggal_keluar?->toDateString(),
        'biaya_bulanan' => $lease->biaya_bulanan, 'status' => $lease->status,
    ]), $lease);
}

function checkBalancedReversal(Pembayaran $payment, Keuangan $income, string $amount): void
{
    $reversal = PaymentReversal::where('payment_id', $payment->id)->sole();
    $offset = $reversal->reversalEntry;
    check($income->fresh()->payment_id === $payment->id && $reversal->original_entry_id === $income->id, 'Reversal source identity changed.');
    check((string) $income->fresh()->jumlah === $amount && (string) $offset->jumlah === $amount, 'Reversal decimal amounts changed.');
    check($offset->jenis === 'pengeluaran' && $offset->kategori === 'Pembalikan Pembayaran' && $offset->payment_id === null, 'Offset classification/source incorrect.');
    $balance = Keuangan::whereIn('id', [$income->id, $offset->id])->selectRaw("SUM(CASE WHEN jenis = 'pemasukan' THEN jumlah ELSE -jumlah END) AS balance")->first();
    check((string) $balance->balance === '0.00', 'Reversal is not net zero.');
    $invoice = Invoice::where('payment_id', $payment->id)->sole();
    check($payment->fresh()->status === 'lunas' && $invoice->status === 'lunas' && (string) $invoice->jumlah_tagihan === $amount, 'Payment/invoice history inconsistent.');
}

// Invoke the real controller in independent PHP/DB sessions, not a copy of its transaction.
function operate(string $operation, int $id, int $resident): void
{
    if ($operation === 'approve') {
        app(PembayaranController::class)->approve(Pembayaran::findOrFail($id));
    } elseif ($operation === 'bill') {
        app(PembayaranController::class)->store(Request::create('/pembayarans', 'POST', [
            'sewa_id' => $id, 'periode' => '2026-11-01', 'jumlah' => 550000, 'metode' => 'cash',
        ]));
    } elseif ($operation === 'reserve') {
        app(ReservasiController::class)->store(Request::create('/reservasis', 'POST', [
            'kamar_id' => $id, 'penghuni_id' => $resident, 'tanggal_reservasi' => '2026-10-01',
            'rencana_masuk' => '2026-11-01', 'rencana_keluar' => '2026-12-01', 'status' => 'dikonfirmasi',
        ]));
    } elseif ($operation === 'reverse') {
        app(PembayaranController::class)->reverse(operatorRequest('/pembayarans/'.$id.'/reverse', 'POST', [
            'reversal_date' => now()->toDateString(), 'reason' => 'Synthetic full reversal',
        ], $resident), Pembayaran::findOrFail($id));
    } elseif ($operation === 'detach') {
        app(KeuanganController::class)->detachManualIncome(operatorRequest('/keuangans/reconciliation/'.$id.'/ledger-link', 'DELETE', [
            'reason' => 'Synthetic concurrent unlink attempt',
        ], $resident), Pembayaran::findOrFail($id));
    } elseif ($operation === 'delete-lease') {
        app(SewaController::class)->destroy(Sewa::findOrFail($id));
    } elseif ($operation === 'convert') {
        $reservation = Reservasi::findOrFail($id);
        app(SewaController::class)->store(Request::create('/sewas', 'POST', [
            'reservasi_id' => $id, 'kamar_id' => $reservation->kamar_id, 'penghuni_id' => $reservation->penghuni_id,
            'tanggal_masuk' => $reservation->rencana_masuk->toDateString(),
            'tanggal_keluar' => $reservation->rencana_keluar?->toDateString(),
            'biaya_bulanan' => 550000.25, 'uang_jaminan' => 125000.25, 'status' => 'aktif',
        ]));
    } elseif ($operation === 'cancel-reservation') {
        $reservation = Reservasi::findOrFail($id);
        app(ReservasiController::class)->update(Request::create('/reservasis/'.$id, 'PUT', [
            'kamar_id' => $reservation->kamar_id, 'penghuni_id' => $reservation->penghuni_id,
            'tanggal_reservasi' => $reservation->tanggal_reservasi->toDateString(),
            'rencana_masuk' => $reservation->rencana_masuk->toDateString(),
            'rencana_keluar' => $reservation->rencana_keluar?->toDateString(),
            'uang_muka' => 0, 'status' => 'dibatalkan',
        ]), $reservation);
    } else {
        throw new RuntimeException('Unknown worker operation.');
    }
}

function waitForLocks(array $workers, string $table): void
{
    $deadline = microtime(true) + 15;
    do {
        foreach ($workers as $worker) {
            $worker->checkTimeout();
            check($worker->isRunning(), 'Worker exited before lock contention: '.$worker->getErrorOutput().$worker->getOutput());
        }
        $waiting = array_filter(DB::select('SHOW FULL PROCESSLIST'), fn ($thread) =>
            $thread->db === 'kostsan_concurrency_test'
            && str_contains(strtolower((string) $thread->Info), 'for update')
            && str_contains((string) $thread->Info, '`'.$table.'`'));
        if (count($waiting) === count($workers)) {
            return;
        }
        usleep(20000);
    } while (microtime(true) < $deadline);
    throw new RuntimeException('Did not observe all simultaneous blocked row-lock queries.');
}

function race(string $operation, string $table, int $lockId, int $targetId, array $residents): array
{
    $workers = [];
    DB::beginTransaction();
    try {
        DB::table($table)->where('id', $lockId)->lockForUpdate()->firstOrFail();
        foreach ($residents as $resident) {
            $process = new Process([PHP_BINARY, __FILE__, 'worker', $operation, (string) $targetId, (string) $resident], dirname(__DIR__, 2));
            $process->setTimeout(25);
            $process->start();
            $workers[] = $process;
        }
        waitForLocks($workers, $table);
        echo "CONTENTION {$operation}: two independent queries blocked on {$table}\n";
        DB::commit();
        $results = [];
        foreach ($workers as $worker) {
            check($worker->wait() === 0, 'Worker failed: '.$worker->getErrorOutput().$worker->getOutput());
            $results[] = json_decode(trim($worker->getOutput()), true, flags: JSON_THROW_ON_ERROR);
        }

        return $results;
    } finally {
        if (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
        foreach ($workers as $worker) {
            if ($worker->isRunning()) {
                $worker->stop(1);
            }
        }
    }
}

try {
    if (($argv[1] ?? '') === 'worker') {
        check(ctype_digit($argv[3] ?? '') && ctype_digit($argv[4] ?? ''), 'Invalid worker identifiers.');
        try {
            if ($argv[2] === 'paused-lease-update') {
                $bound = Sewa::findOrFail((int) $argv[3]);
                echo "BOUND\n";
                fflush(STDOUT);
                check(trim((string) fgets(STDIN)) === 'continue', 'Missing synthetic binding handoff.');
                moveLease($bound, (int) $argv[4]);
            } else {
                operate($argv[2], (int) $argv[3], (int) $argv[4]);
            }
            echo json_encode(['result' => 'ok']);
        } catch (ValidationException $exception) {
            echo json_encode(['result' => 'validation', 'fields' => array_keys($exception->errors())]);
        }
        exit(0);
    }
    check(count(DB::select('SHOW TABLES')) === 0, 'Refusing non-empty test schema; use a new ephemeral MySQL service.');
    check(Artisan::call('migrate', ['--force' => true]) === 0, 'Fresh migrations failed.');
    check(Artisan::call('migrate:rollback', ['--step' => 1, '--force' => true]) === 0, 'Latest migration rollback failed.');
    check(Artisan::call('migrate', ['--force' => true]) === 0, 'Migration reapply failed.');
    $coverageMigration = require $root.'/database/migrations/2026_10_09_010000_add_billing_coverage_to_pembayarans.php';
    $coverageMigration->down();
    check(Schema::hasForeignKey('pembayarans', ['sewa_id']) && Schema::hasIndex('pembayarans', ['sewa_id']), 'Coverage rollback lost lease FK/index.');
    check(! Schema::hasColumn('pembayarans', 'coverage_start') && ! Schema::hasColumn('pembayarans', 'coverage_end'), 'Coverage rollback left columns.');
    $coverageMigration->up();
    echo "PASS coverage rollback preserves lease FK and supporting index\n";
    $migrationCount = DB::table('migrations')->count();
    check($migrationCount > 0, 'No migrations were exercised.');
    check(Artisan::call('migrate:rollback', ['--step' => $migrationCount, '--force' => true]) === 0, 'Full migration rollback failed.');
    check(DB::table('migrations')->count() === 0 && count(DB::select('SHOW TABLES')) === 1, 'Full rollback left application tables or migration records.');
    check(Artisan::call('migrate', ['--force' => true]) === 0 && DB::table('migrations')->count() === $migrationCount, 'Full migration reapply failed.');
    echo "PASS full migration cycle: {$migrationCount} applied, rolled back and reapplied before synthetic seeding\n";
    echo 'ENGINE '.json_encode(DB::selectOne('SELECT VERSION() AS version, @@transaction_isolation AS isolation_level'))."\n";

    $room = Kamar::create(['nomor' => 'MYSQL-01', 'tipe' => 'Test', 'harga_bulanan' => 550000, 'status' => 'terisi']);
    $resident = Penghuni::create(['nama' => 'Synthetic MySQL One', 'telepon' => '']);
    $other = Penghuni::create(['nama' => 'Synthetic MySQL Two', 'telepon' => '']);
    $lease = Sewa::create(['kamar_id' => $room->id, 'penghuni_id' => $resident->id, 'tanggal_masuk' => '2026-10-01', 'tanggal_keluar' => '2026-12-01', 'biaya_bulanan' => 550000, 'status' => 'aktif']);
    $payment = Pembayaran::create(['sewa_id' => $lease->id, 'periode' => '2026-10-01', 'jumlah' => 550000, 'metode' => 'cash', 'status' => 'belum_lunas']);

    $results = race('approve', 'kamars', $room->id, $payment->id, [0, 0]);
    check(array_column($results, 'result') === ['ok', 'ok'], 'Approval retry did not succeed.');
    check($payment->fresh()->status === 'lunas' && Invoice::where('payment_id', $payment->id)->count() === 1, 'Approval payment/invoice mismatch.');
    check(Invoice::where('payment_id', $payment->id)->value('status') === 'lunas'
        && (string) Invoice::where('payment_id', $payment->id)->value('jumlah_tagihan') === '550000.00', 'Invoice status/amount mismatch.');
    check(Keuangan::where('payment_id', $payment->id)->count() === 1 && (string) Keuangan::where('payment_id', $payment->id)->value('jumlah') === '550000.00', 'Duplicate or incorrect ledger.');
    echo "PASS concurrent approval: one paid invoice and one income 550000\n";

    $results = race('bill', 'sewas', $lease->id, $lease->id, [0, 0]);
    $states = array_column($results, 'result');
    sort($states);
    check($states === ['ok', 'validation'], 'Overlapping bill did not reject exactly one request.');
    check(in_array(['result' => 'validation', 'fields' => ['periode']], $results, true), 'Unexpected billing validation.');
    check(Pembayaran::whereDate('periode', '2026-11-01')->count() === 1 && Invoice::count() === 2, 'Duplicate bill/invoice persisted.');
    $bill = Pembayaran::whereDate('periode', '2026-11-01')->sole();
    check($bill->coverage_start->toDateString() === '2026-11-01' && $bill->coverage_end->toDateString() === '2026-12-01'
        && (string) $bill->jumlah === '550000.00' && (string) $bill->invoice->jumlah_tagihan === '550000.00', 'Bill coverage or invoice amount mismatch.');
    echo "PASS concurrent billing: one bill, one overlap rejection\n";

    $available = Kamar::create(['nomor' => 'MYSQL-02', 'tipe' => 'Test', 'harga_bulanan' => 550000, 'status' => 'tersedia']);
    $results = race('reserve', 'kamars', $available->id, $available->id, [$resident->id, $other->id]);
    $states = array_column($results, 'result');
    sort($states);
    check($states === ['ok', 'validation'], 'Conflicting reservation did not reject exactly one request.');
    check(in_array(['result' => 'validation', 'fields' => ['kamar_id']], $results, true), 'Unexpected reservation validation.');
    check(Reservasi::where('kamar_id', $available->id)->count() === 1, 'Conflicting reservations persisted.');
    $reservation = Reservasi::where('kamar_id', $available->id)->sole();
    check($reservation->status === 'dikonfirmasi' && in_array($reservation->penghuni_id, [$resident->id, $other->id], true), 'Reservation state/owner mismatch.');
    echo "PASS concurrent reservations: one confirmed, one conflict rejection\n";

    $pending = Pembayaran::whereDate('periode', '2026-11-01')->sole();
    $pending->update(['jumlah' => 650000]);
    // A test-only CHECK induces a real DB write failure without SUPER/trigger privileges.
    DB::statement('ALTER TABLE keuangans ADD CONSTRAINT synthetic_ledger_failure CHECK (jumlah <> 650000)');
    try {
        operate('approve', $pending->id, 0);
        throw new RuntimeException('Expected ledger failure was not raised.');
    } catch (Illuminate\Database\QueryException $exception) {
        check(str_contains($exception->getMessage(), 'synthetic_ledger_failure'), 'Unexpected DB failure.');
    } finally {
        DB::statement('ALTER TABLE keuangans DROP CHECK synthetic_ledger_failure');
    }
    check($pending->fresh()->status === 'belum_lunas' && $pending->fresh()->tanggal_bayar === null, 'Failed approval was not rolled back.');
    check(Invoice::where('payment_id', $pending->id)->value('status') === 'draft' && ! Keuangan::where('payment_id', $pending->id)->exists(), 'Rollback left invoice/ledger inconsistent.');
    check((string) $pending->fresh()->jumlah === '650000.00'
        && (string) Invoice::where('payment_id', $pending->id)->value('jumlah_tagihan') === '650000.00', 'Rollback changed pending amounts.');
    echo "PASS ledger failure rollback: payment/invoice pending, no income\n";

    $oldRoom = Kamar::create(['nomor' => 'MYSQL-STALE-OLD', 'tipe' => 'Test', 'harga_bulanan' => 550000, 'status' => 'tersedia']);
    $newRoom = Kamar::create(['nomor' => 'MYSQL-STALE-NEW', 'tipe' => 'Test', 'harga_bulanan' => 550000, 'status' => 'terisi']);
    $oldLease = Sewa::create(['kamar_id' => $oldRoom->id, 'penghuni_id' => $resident->id, 'tanggal_masuk' => '2026-09-01', 'tanggal_keluar' => '2026-10-01', 'biaya_bulanan' => 550000, 'status' => 'selesai']);
    $newLease = Sewa::create(['kamar_id' => $newRoom->id, 'penghuni_id' => $other->id, 'tanggal_masuk' => '2026-10-01', 'tanggal_keluar' => '2026-11-01', 'biaya_bulanan' => 550000, 'status' => 'aktif']);
    $legacy = Pembayaran::create(['sewa_id' => $oldLease->id, 'periode' => '2026-09-01', 'jumlah' => 550000, 'metode' => 'cash', 'status' => 'belum_lunas']);
    $worker = new Process([PHP_BINARY, __FILE__, 'worker', 'approve', (string) $legacy->id, '0'], $root);
    $worker->setTimeout(25);
    DB::beginTransaction();
    try {
        Kamar::whereKey($oldRoom->id)->lockForUpdate()->firstOrFail();
        $worker->start();
        waitForLocks([$worker], 'kamars');
        echo "CONTENTION edit/approve: old-source approval blocked while edit commits on separate connection\n";
        app(PembayaranController::class)->update(Request::create('/pembayarans/'.$legacy->id, 'PUT', [
            'sewa_id' => $newLease->id, 'periode' => '2026-10-01', 'jumlah' => 650000, 'metode' => 'cash',
        ]), $legacy);
        DB::commit();
        check($worker->wait() === 0, 'Stale approval worker failed: '.$worker->getErrorOutput());
        check(json_decode(trim($worker->getOutput()), true, flags: JSON_THROW_ON_ERROR) === ['result' => 'validation', 'fields' => ['pembayaran']], 'Stale approval did not reject changed source.');
    } finally {
        if (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
        if ($worker->isRunning()) {
            $worker->stop(1);
        }
    }
    check($oldRoom->fresh()->status === 'tersedia' && $legacy->fresh()->status === 'belum_lunas', 'Stale approval changed old room or approved payment.');
    check(Invoice::where('payment_id', $legacy->id)->value('status') === 'draft' && ! Keuangan::where('payment_id', $legacy->id)->exists(), 'Stale approval wrote paid invoice/ledger.');
    operate('approve', $legacy->id, 0);
    operate('approve', $legacy->id, 0);
    check($oldRoom->fresh()->status === 'tersedia' && $newRoom->fresh()->status === 'terisi', 'Fresh retry altered the wrong room.');
    check(Invoice::where('payment_id', $legacy->id)->count() === 1 && Invoice::where('payment_id', $legacy->id)->value('status') === 'lunas', 'Fresh retry invoice inconsistent.');
    check((int) Invoice::where('payment_id', $legacy->id)->value('penghuni_id') === $other->id, 'Invoice belongs to old source.');
    check(Keuangan::where('payment_id', $legacy->id)->count() === 1 && (string) Keuangan::where('payment_id', $legacy->id)->value('jumlah') === '650000.00', 'Fresh retry ledger inconsistent.');
    echo "PASS edit/approve race: stale source rejected, old room unchanged, fresh retry one invoice/income 650000\n";

    // The payment can keep its lease while that lease moves to another room.
    $moveFrom = Kamar::create(['nomor' => 'MYSQL-MOVE-OLD', 'tipe' => 'Test', 'harga_bulanan' => 550000, 'status' => 'terisi']);
    $moveTo = Kamar::create(['nomor' => 'MYSQL-MOVE-NEW', 'tipe' => 'Test', 'harga_bulanan' => 550000, 'status' => 'tersedia']);
    $movingLease = Sewa::create(['kamar_id' => $moveFrom->id, 'penghuni_id' => $resident->id, 'tanggal_masuk' => '2026-10-01', 'tanggal_keluar' => '2026-11-01', 'biaya_bulanan' => 550000, 'status' => 'aktif']);
    $movingPayment = Pembayaran::create(['sewa_id' => $movingLease->id, 'periode' => '2026-10-01', 'jumlah' => 550000, 'metode' => 'cash', 'status' => 'belum_lunas']);
    $worker = new Process([PHP_BINARY, __FILE__, 'worker', 'approve', (string) $movingPayment->id, '0'], $root);
    $worker->setTimeout(25);
    DB::beginTransaction();
    try {
        Kamar::whereKey($moveFrom->id)->lockForUpdate()->firstOrFail();
        $worker->start();
        waitForLocks([$worker], 'kamars');
        echo "CONTENTION lease-move/approve: approval blocked on old room before lease move commits\n";
        app(SewaController::class)->update(Request::create('/sewas/'.$movingLease->id, 'PUT', [
            'kamar_id' => $moveTo->id, 'penghuni_id' => $resident->id,
            'tanggal_masuk' => '2026-10-01', 'tanggal_keluar' => '2026-11-01',
            'biaya_bulanan' => 550000, 'status' => 'aktif',
        ]), $movingLease);
        DB::commit();
        check($worker->wait() === 0, 'Room-move approval worker failed: '.$worker->getErrorOutput());
        check(json_decode(trim($worker->getOutput()), true, flags: JSON_THROW_ON_ERROR) === ['result' => 'validation', 'fields' => ['pembayaran']], 'Stale approval did not reject changed lease room.');
    } finally {
        if (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
        if ($worker->isRunning()) {
            $worker->stop(1);
        }
    }
    check($moveFrom->fresh()->status === 'tersedia' && $moveTo->fresh()->status === 'terisi', 'Stale approval corrupted moved-room status.');
    check($movingPayment->fresh()->status === 'belum_lunas' && $movingLease->fresh()->kamar_id === $moveTo->id, 'Room-move payment/lease inconsistent.');
    check(Invoice::where('payment_id', $movingPayment->id)->value('status') === 'draft' && ! Keuangan::where('payment_id', $movingPayment->id)->exists(), 'Room-move stale approval wrote paid invoice/ledger.');
    operate('approve', $movingPayment->id, 0);
    operate('approve', $movingPayment->id, 0);
    check($moveFrom->fresh()->status === 'tersedia' && $moveTo->fresh()->status === 'terisi', 'Fresh room-move retry altered wrong room.');
    check(Invoice::where('payment_id', $movingPayment->id)->count() === 1 && Invoice::where('payment_id', $movingPayment->id)->value('status') === 'lunas'
        && (string) Invoice::where('payment_id', $movingPayment->id)->value('jumlah_tagihan') === '550000.00', 'Room-move retry invoice inconsistent.');
    $income = Keuangan::where('payment_id', $movingPayment->id)->sole();
    check((string) $income->jumlah === '550000.00' && str_contains($income->deskripsi, 'Kamar MYSQL-MOVE-NEW'), 'Room-move retry income amount/room inconsistent.');
    echo "PASS lease-move/approve race: stale room rejected, room states preserved, fresh retry one invoice/income 550000 for new room\n";

    $operator = User::create(['name' => 'Synthetic finance operator', 'email' => 'finance@concurrency.example.test', 'password' => 'SyntheticOnlyPassword123']);
    $reconciled = Pembayaran::create(['sewa_id' => $lease->id, 'periode' => '2026-12-01', 'tanggal_bayar' => now()->toDateString(), 'jumlah' => 550000.25, 'metode' => 'cash', 'status' => 'lunas']);
    $manual = Keuangan::create(['tanggal' => now()->toDateString(), 'jenis' => 'pemasukan', 'kategori' => 'Sewa Kamar', 'deskripsi' => 'Synthetic reconciled income', 'jumlah' => 550000.25]);
    app(KeuanganController::class)->attachManualIncome(operatorRequest('/keuangans/reconciliation/'.$reconciled->id.'/ledger-link', 'POST', [
        'keuangan_id' => $manual->id, 'reason' => 'Synthetic bank reconciliation',
    ], $operator->id), $reconciled);
    $worker = new Process([PHP_BINARY, __FILE__, 'worker', 'detach', (string) $reconciled->id, (string) $operator->id], $root);
    $worker->setTimeout(25);
    DB::beginTransaction();
    try {
        Pembayaran::whereKey($reconciled->id)->lockForUpdate()->firstOrFail();
        $worker->start();
        waitForLocks([$worker], 'pembayarans');
        echo "CONTENTION reverse/unlink: unlink blocked on payment before reversal commits\n";
        operate('reverse', $reconciled->id, $operator->id);
        DB::commit();
        check($worker->wait() === 0, 'Concurrent unlink worker failed: '.$worker->getErrorOutput());
        check(json_decode(trim($worker->getOutput()), true, flags: JSON_THROW_ON_ERROR) === ['result' => 'validation', 'fields' => ['keuangan']], 'Reversed income was unlinked.');
    } finally {
        if (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
        if ($worker->isRunning()) {
            $worker->stop(1);
        }
    }
    operate('reverse', $reconciled->id, $operator->id);
    $reversal = PaymentReversal::where('payment_id', $reconciled->id)->sole();
    check($manual->fresh()->payment_id === $reconciled->id && $reversal->original_entry_id === $manual->id, 'Reversal lost its source link.');
    check(FinancePaymentLinkAudit::where('payment_id', $reconciled->id)->count() === 1, 'Rejected unlink wrote an audit mutation.');
    check((string) $manual->fresh()->jumlah === '550000.25' && (string) $reversal->reversalEntry->jumlah === '550000.25', 'Reversal changed decimal amounts.');
    $balance = Keuangan::whereIn('id', [$manual->id, $reversal->reversal_entry_id])->selectRaw("SUM(CASE WHEN jenis = 'pemasukan' THEN jumlah ELSE -jumlah END) AS balance")->first();
    check((string) $balance->balance === '0.00', 'Reconciled reversal is not net zero.');
    check($reconciled->fresh()->status === 'lunas' && Invoice::where('payment_id', $reconciled->id)->value('status') === 'lunas', 'Reversal changed payment/invoice history.');
    echo "PASS reverse/unlink race: source link and audit preserved, one reversal, income/offset 550000.25 and net zero\n";

    $parallel = Pembayaran::create(['sewa_id' => $lease->id, 'periode' => '2026-10-01', 'tanggal_bayar' => now()->toDateString(), 'jumlah' => 735000.25, 'metode' => 'cash', 'status' => 'belum_lunas']);
    operate('approve', $parallel->id, 0);
    $parallelIncome = Keuangan::where('payment_id', $parallel->id)->sole();
    $history = [$parallel->fresh()->getAttributes(), $parallel->fresh()->invoice->getAttributes(), $parallelIncome->getAttributes()];
    $entryCount = Keuangan::count();
    $results = race('reverse', 'pembayarans', $parallel->id, $parallel->id, [$operator->id, $operator->id]);
    check(array_column($results, 'result') === ['ok', 'ok'], 'Simultaneous reversal/retry did not both complete.');
    check(Keuangan::count() === $entryCount + 1, 'Concurrent reversal created duplicate/orphan offsets.');
    checkBalancedReversal($parallel, $parallelIncome, '735000.25');
    check($history === [$parallel->fresh()->getAttributes(), $parallel->fresh()->invoice->getAttributes(), $parallelIncome->fresh()->getAttributes()], 'Concurrent reversal mutated original history.');
    echo "PASS concurrent reversal creation: two blocked workers, one offset/reversal 735000.25, original history unchanged and net zero\n";

    $recoverable = Pembayaran::create(['sewa_id' => $lease->id, 'periode' => '2026-10-01', 'tanggal_bayar' => now()->toDateString(), 'jumlah' => 845000.75, 'metode' => 'cash', 'status' => 'lunas']);
    $recoverableIncome = Keuangan::create(['tanggal' => now()->toDateString(), 'jenis' => 'pemasukan', 'kategori' => 'Sewa Kamar', 'deskripsi' => 'Synthetic recoverable manual income', 'jumlah' => 845000.75]);
    $attach = operatorRequest('/keuangans/reconciliation/'.$recoverable->id.'/ledger-link', 'POST', [
        'keuangan_id' => $recoverableIncome->id, 'reason' => 'Synthetic verified source for recovery',
    ], $operator->id);
    app(KeuanganController::class)->attachManualIncome($attach, $recoverable);
    $history = [$recoverable->fresh()->getAttributes(), $recoverable->fresh()->invoice->getAttributes()];
    $entryCount = Keuangan::count();
    $worker = new Process([PHP_BINARY, __FILE__, 'worker', 'reverse', (string) $recoverable->id, (string) $operator->id], $root);
    $worker->setTimeout(25);
    DB::beginTransaction();
    try {
        Pembayaran::whereKey($recoverable->id)->lockForUpdate()->firstOrFail();
        $worker->start();
        waitForLocks([$worker], 'pembayarans');
        echo "CONTENTION unlink/reverse: reversal blocked before unlink commits first\n";
        operate('detach', $recoverable->id, $operator->id);
        DB::commit();
        check($worker->wait() === 0, 'Waiting reversal worker failed: '.$worker->getErrorOutput());
        check(json_decode(trim($worker->getOutput()), true, flags: JSON_THROW_ON_ERROR) === ['result' => 'validation', 'fields' => ['pembayaran']], 'Reversal accepted an unlinked source.');
    } finally {
        if (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
        if ($worker->isRunning()) {
            $worker->stop(1);
        }
    }
    check(! PaymentReversal::where('payment_id', $recoverable->id)->exists() && Keuangan::count() === $entryCount, 'Rejected reversal left an offset/reversal.');
    check($recoverableIncome->fresh()->payment_id === null && (string) $recoverableIncome->fresh()->jumlah === '845000.75', 'Unlink changed income amount or failed.');
    check(FinancePaymentLinkAudit::where('payment_id', $recoverable->id)->orderBy('id')->pluck('action')->all() === ['linked', 'unlinked'], 'Rejected reversal changed link audit history.');
    app(KeuanganController::class)->attachManualIncome($attach, $recoverable->fresh());
    operate('reverse', $recoverable->id, $operator->id);
    operate('reverse', $recoverable->id, $operator->id);
    check(Keuangan::count() === $entryCount + 1, 'Recovery/retry created duplicate/orphan offsets.');
    checkBalancedReversal($recoverable, $recoverableIncome, '845000.75');
    check($history === [$recoverable->fresh()->getAttributes(), $recoverable->fresh()->invoice->getAttributes()], 'Recovery mutated payment/invoice history.');
    check(FinancePaymentLinkAudit::where('payment_id', $recoverable->id)->orderBy('id')->pluck('action')->all() === ['linked', 'unlinked', 'linked'], 'Recovery did not preserve audit sequence.');
    echo "PASS unlink-first/reversal race: missing source rejected without writes, reconciliation/retry one offset 845000.75 and net zero\n";

    $rollbackPayment = Pembayaran::create(['sewa_id' => $lease->id, 'periode' => '2026-10-01', 'tanggal_bayar' => now()->toDateString(), 'jumlah' => 956000.25, 'metode' => 'cash', 'status' => 'belum_lunas']);
    operate('approve', $rollbackPayment->id, 0);
    $rollbackIncome = Keuangan::where('payment_id', $rollbackPayment->id)->sole();
    $history = [$rollbackPayment->fresh()->getAttributes(), $rollbackPayment->fresh()->invoice->getAttributes(), $rollbackIncome->getAttributes()];
    $entryCount = Keuangan::count();
    $reversalCount = PaymentReversal::count();
    $auditCount = FinancePaymentLinkAudit::count();
    $offsetInserted = false;
    Keuangan::created(function (Keuangan $entry) use (&$offsetInserted): void {
        if ($entry->kategori === 'Pembalikan Pembayaran' && (string) $entry->jumlah === '956000.25') {
            $offsetInserted = true;
        }
    });
    $probe = operatorRequest('/pembayarans/'.$rollbackPayment->id.'/reverse', 'POST', [
        'reversal_date' => now()->toDateString(), 'reason' => 'Synthetic rollback probe',
    ], $operator->id);
    // Test-only constraint rejects the second write, not the preceding offset insert.
    DB::statement("ALTER TABLE payment_reversals ADD CONSTRAINT synthetic_reversal_failure CHECK (reason <> 'Synthetic rollback probe')");
    try {
        app(PembayaranController::class)->reverse($probe, $rollbackPayment->fresh());
        throw new RuntimeException('Expected reversal write failure was not raised.');
    } catch (Illuminate\Database\QueryException $exception) {
        check(str_contains($exception->getMessage(), 'synthetic_reversal_failure'), 'Unexpected reversal DB failure.');
        check($offsetInserted, 'Failure occurred before an offset was actually inserted.');
        check(DB::transactionLevel() === 0 && ! DB::connection()->getPdo()->inTransaction(), 'Failed reversal left an open transaction.');
        check(Keuangan::count() === $entryCount && PaymentReversal::count() === $reversalCount, 'Failed reversal left an orphan offset/reversal.');
        check(FinancePaymentLinkAudit::count() === $auditCount, 'Failed reversal mutated link audits.');
        check($history === [$rollbackPayment->fresh()->getAttributes(), $rollbackPayment->fresh()->invoice->getAttributes(), $rollbackIncome->fresh()->getAttributes()], 'Failed reversal mutated original history.');
    } finally {
        DB::statement('ALTER TABLE payment_reversals DROP CHECK synthetic_reversal_failure');
    }
    app(PembayaranController::class)->reverse($probe, $rollbackPayment->fresh());
    app(PembayaranController::class)->reverse($probe, $rollbackPayment->fresh());
    check(Keuangan::count() === $entryCount + 1 && PaymentReversal::count() === $reversalCount + 1, 'Rollback recovery/retry created duplicate/orphan entries.');
    checkBalancedReversal($rollbackPayment, $rollbackIncome, '956000.25');
    check($history === [$rollbackPayment->fresh()->getAttributes(), $rollbackPayment->fresh()->invoice->getAttributes(), $rollbackIncome->fresh()->getAttributes()], 'Recovered reversal mutated original history.');
    check(FinancePaymentLinkAudit::count() === $auditCount, 'Recovery mutated link audits.');
    echo "PASS late reversal write rollback: offset insert observed, no orphan/history/audit changes, retry one offset/reversal 956000.25 and net zero\n";

    $deleteRoom = Kamar::create(['nomor' => 'MYSQL-DELETE', 'tipe' => 'Test', 'harga_bulanan' => 550000, 'status' => 'terisi']);
    $deleteLease = Sewa::create(['kamar_id' => $deleteRoom->id, 'penghuni_id' => $resident->id, 'tanggal_masuk' => '2026-10-01', 'tanggal_keluar' => '2026-11-01', 'biaya_bulanan' => 550000, 'status' => 'aktif']);
    $worker = new Process([PHP_BINARY, __FILE__, 'worker', 'delete-lease', (string) $deleteLease->id, '0'], $root);
    $worker->setTimeout(25);
    DB::beginTransaction();
    try {
        Kamar::whereKey($deleteRoom->id)->lockForUpdate()->firstOrFail();
        $worker->start();
        $deadline = microtime(true) + 15;
        do {
            $worker->checkTimeout();
            check($worker->isRunning(), 'Delete exited before room contention: '.$worker->getErrorOutput().$worker->getOutput());
            // Old code waits at UPDATE; corrected code waits at SELECT FOR UPDATE.
            $waiting = array_filter(DB::select('SHOW FULL PROCESSLIST'), fn ($thread) =>
                $thread->db === 'kostsan_concurrency_test'
                && preg_match('/^(update `kamars`|select .*`kamars`.*for update)/i', (string) $thread->Info));
            if (count($waiting) === 1) {
                break;
            }
            usleep(20000);
        } while (microtime(true) < $deadline);
        check(count($waiting) === 1, 'Delete never reached the room lock barrier.');
        echo "CONTENTION delete/checkout: delete blocked on held room before actual checkout\n";
        app(KamarController::class)->selesaiSewa($deleteRoom->fresh());
        check(Sewa::findOrFail($deleteLease->id)->status === 'selesai', 'Checkout did not finish the lease.');
        DB::commit();
        check($worker->wait() === 0, 'Delete/checkout worker failed: '.$worker->getErrorOutput().$worker->getOutput());
        check(json_decode(trim($worker->getOutput()), true, flags: JSON_THROW_ON_ERROR) === ['result' => 'ok'], 'Delete did not complete after checkout.');
        check(! Sewa::whereKey($deleteLease->id)->exists() && $deleteRoom->fresh()->status === 'tersedia', 'Delete/checkout final state inconsistent.');
        check(! Pembayaran::where('sewa_id', $deleteLease->id)->exists(), 'Unexpected payment in deletion fixture.');
        echo "PASS delete/checkout serialization: completed checkout, deleted history-free lease, room available\n";
    } finally {
        if (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
        if ($worker->isRunning()) {
            $worker->stop(1);
        }
    }
    $editRooms = collect(['A', 'B', 'C'])->map(fn ($name) => Kamar::create([
        'nomor' => 'MYSQL-EDIT-'.$name, 'tipe' => 'Test', 'harga_bulanan' => 550000,
        'status' => $name === 'A' ? 'terisi' : 'tersedia',
    ]));
    $editLease = Sewa::create(['kamar_id' => $editRooms[0]->id, 'penghuni_id' => $resident->id,
        'tanggal_masuk' => '2026-10-01', 'tanggal_keluar' => '2026-11-01', 'biaya_bulanan' => 550000, 'status' => 'aktif']);
    $input = new InputStream();
    $worker = new Process([PHP_BINARY, __FILE__, 'worker', 'paused-lease-update', (string) $editLease->id, (string) $editRooms[2]->id], $root);
    $worker->setInput($input);
    $worker->setTimeout(25);
    try {
        $worker->start();
        check($worker->waitUntil(fn ($type, $output) => str_contains($output, "BOUND\n")), 'Worker did not bind original lease.');
        moveLease($editLease->fresh(), $editRooms[1]->id);
        DB::beginTransaction();
        Kamar::whereKey($editRooms[1]->id)->lockForUpdate()->firstOrFail();
        $input->write("continue\n");
        $input->close();
        $deadline = microtime(true) + 15;
        do {
            $worker->checkTimeout();
            $waiting = array_filter(DB::select('SHOW FULL PROCESSLIST'), fn ($thread) =>
                $thread->db === 'kostsan_concurrency_test'
                && preg_match('/^update `kamars`/i', (string) $thread->Info));
            if (count($waiting) === 1 || ! $worker->isRunning()) {
                break;
            }
            usleep(20000);
        } while (microtime(true) < $deadline);
        check(count($waiting) === 1 || ! $worker->isRunning(), 'Stale edit never reached rejection or room-write barrier.');
        echo count($waiting) === 1 ? "CONTENTION stale edit/checkout: update waits for actual room after locking lease\n" : "STALE edit rejected before touching actual room\n";
        app(KamarController::class)->selesaiSewa($editRooms[1]->fresh());
        DB::commit();
        check($worker->wait() === 0, 'Stale update worker failed: '.$worker->getErrorOutput().$worker->getOutput());
        $lines = explode("\n", trim($worker->getOutput()));
        check(json_decode(end($lines), true, flags: JSON_THROW_ON_ERROR) === ['result' => 'validation', 'fields' => ['sewa']], 'Update did not reject unlocked source.');
        check($editLease->fresh()->kamar_id === $editRooms[1]->id && $editLease->fresh()->status === 'selesai'
            && (string) $editLease->fresh()->biaya_bulanan === '550000.00', 'Stale edit changed source/amount or checkout failed.');
        check($editRooms->every(fn ($room) => $room->fresh()->status === 'tersedia'), 'Stale edit/checkout corrupted room states.');
        echo "PASS stale edit/checkout: unlocked source rejected, checkout finished, no wrong-room mutation\n";
    } finally {
        if (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
        $input->close();
        if ($worker->isRunning()) {
            $worker->stop(1);
        }
    }
    $conversionFixtures = [];
    foreach (['DUPLICATE', 'CANCEL', 'ROLLBACK'] as $case) {
        $conversionRoom = Kamar::create(['nomor' => 'MYSQL-CONVERT-'.$case, 'tipe' => 'Test', 'harga_bulanan' => 550000.25, 'status' => 'tersedia']);
        $conversionReservation = Reservasi::create(['kamar_id' => $conversionRoom->id, 'penghuni_id' => $resident->id,
            'tanggal_reservasi' => '2026-10-19', 'rencana_masuk' => '2026-11-01', 'rencana_keluar' => '2026-12-01',
            'uang_muka' => 125000.25, 'status' => 'dikonfirmasi']);
        $conversionFixtures[$case] = [$conversionRoom, $conversionReservation];
    }
    [$conversionRoom, $conversionReservation] = $conversionFixtures['DUPLICATE'];
    $leaseCount = Sewa::count();
    $results = race('convert', 'kamars', $conversionRoom->id, $conversionReservation->id, [0, 0]);
    $states = array_column($results, 'result');
    sort($states);
    check($states === ['ok', 'validation'] && in_array(['result' => 'validation', 'fields' => ['reservasi_id']], $results, true), 'Double conversion did not reject exactly one request.');
    $convertedLease = $conversionReservation->fresh()->sewa;
    check(Sewa::count() === $leaseCount + 1 && $convertedLease !== null && $conversionReservation->fresh()->status === 'dikonversi', 'Conversion created duplicate/orphan leases.');
    check($convertedLease->kamar_id === $conversionRoom->id && $convertedLease->penghuni_id === $resident->id
        && $convertedLease->tanggal_masuk->toDateString() === '2026-11-01' && $convertedLease->tanggal_keluar->toDateString() === '2026-12-01'
        && (string) $convertedLease->biaya_bulanan === '550000.25' && (string) $convertedLease->uang_jaminan === '125000.25'
        && (string) $conversionReservation->fresh()->uang_muka === '125000.25' && $conversionRoom->fresh()->status === 'terisi', 'Conversion owner/period/money/room inconsistent.');
    app(PembayaranController::class)->store(Request::create('/pembayarans', 'POST', [
        'sewa_id' => $convertedLease->id, 'periode' => '2026-11-01', 'jumlah' => 550000.25, 'metode' => 'cash',
    ]));
    $convertedPayment = Pembayaran::where('sewa_id', $convertedLease->id)->sole();
    operate('approve', $convertedPayment->id, 0);
    operate('approve', $convertedPayment->id, 0);
    check($convertedPayment->fresh()->status === 'lunas' && Invoice::where('payment_id', $convertedPayment->id)->count() === 1
        && (string) $convertedPayment->fresh()->invoice->jumlah_tagihan === '550000.25' && $convertedPayment->fresh()->invoice->status === 'lunas'
        && Keuangan::where('payment_id', $convertedPayment->id)->count() === 1
        && (string) $convertedPayment->fresh()->ledgerEntry->jumlah === '550000.25', 'Converted lease billing/approval retry inconsistent.');
    check($convertedPayment->coverage_start->toDateString() === '2026-11-01' && $convertedPayment->coverage_end->toDateString() === '2026-12-01', 'Converted lease bill coverage incorrect.');
    echo "PASS concurrent conversion: one linked lease, one rejected duplicate, billing/approval retry one invoice/income 550000.25\n";

    [$conversionRoom, $conversionReservation] = $conversionFixtures['CANCEL'];
    $worker = new Process([PHP_BINARY, __FILE__, 'worker', 'cancel-reservation', (string) $conversionReservation->id, '0'], $root);
    $worker->setTimeout(25);
    DB::beginTransaction();
    try {
        Kamar::whereKey($conversionRoom->id)->lockForUpdate()->firstOrFail();
        $worker->start();
        waitForLocks([$worker], 'kamars');
        echo "CONTENTION conversion/cancel: old cancellation blocked before conversion commits\n";
        operate('convert', $conversionReservation->id, 0);
        $convertedLease = $conversionReservation->fresh()->sewa;
        $history = [$conversionReservation->fresh()->getAttributes(), $convertedLease->getAttributes(), $conversionRoom->fresh()->getAttributes()];
        DB::commit();
        check($worker->wait() === 0, 'Concurrent cancellation worker failed: '.$worker->getErrorOutput().$worker->getOutput());
        check(json_decode(trim($worker->getOutput()), true, flags: JSON_THROW_ON_ERROR) === ['result' => 'validation', 'fields' => ['reservasi']], 'Old cancellation changed converted history.');
        check($history === [$conversionReservation->fresh()->getAttributes(), $convertedLease->fresh()->getAttributes(), $conversionRoom->fresh()->getAttributes()], 'Cancellation mutated converted reservation/lease/room.');
        echo "PASS conversion/cancel: stale cancellation rejected, raw conversion history unchanged\n";
    } finally {
        if (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
        if ($worker->isRunning()) {
            $worker->stop(1);
        }
    }

    [$conversionRoom, $conversionReservation] = $conversionFixtures['ROLLBACK'];
    $history = [$conversionReservation->fresh()->getAttributes(), $conversionRoom->fresh()->getAttributes()];
    $leaseCount = Sewa::count();
    $financialCounts = [Pembayaran::count(), Invoice::count(), Keuangan::count()];
    $leaseInserted = false;
    Sewa::created(function (Sewa $lease) use ($conversionRoom, &$leaseInserted): void {
        if ($lease->kamar_id === $conversionRoom->id) {
            $leaseInserted = true;
        }
    });
    DB::statement('ALTER TABLE reservasis ADD CONSTRAINT synthetic_conversion_failure CHECK (id <> '.$conversionReservation->id." OR status <> 'dikonversi')");
    try {
        operate('convert', $conversionReservation->id, 0);
        throw new RuntimeException('Expected reservation-close failure was not raised.');
    } catch (Illuminate\Database\QueryException $exception) {
        check(str_contains($exception->getMessage(), 'synthetic_conversion_failure') && $leaseInserted, 'Unexpected failure or lease insert not observed.');
        check(DB::transactionLevel() === 0 && ! DB::connection()->getPdo()->inTransaction(), 'Conversion failure left transaction open.');
        check(Sewa::count() === $leaseCount && ! Sewa::where('kamar_id', $conversionRoom->id)->exists(), 'Conversion failure left orphan lease.');
        check($financialCounts === [Pembayaran::count(), Invoice::count(), Keuangan::count()], 'Conversion failure changed financial records.');
        check($history === [$conversionReservation->fresh()->getAttributes(), $conversionRoom->fresh()->getAttributes()], 'Failed conversion mutated reservation/room.');
    } finally {
        DB::statement('ALTER TABLE reservasis DROP CHECK synthetic_conversion_failure');
    }
    operate('convert', $conversionReservation->id, 0);
    try {
        operate('convert', $conversionReservation->id, 0);
        throw new RuntimeException('Recovered conversion accepted duplicate retry.');
    } catch (ValidationException $exception) {
        check(array_keys($exception->errors()) === ['reservasi_id'], 'Unexpected conversion retry validation.');
    }
    check(Sewa::count() === $leaseCount + 1 && $conversionReservation->fresh()->sewa_id === Sewa::where('kamar_id', $conversionRoom->id)->sole()->id
        && $conversionReservation->fresh()->status === 'dikonversi' && $conversionRoom->fresh()->status === 'terisi', 'Recovered conversion link/room inconsistent.');
    check($financialCounts === [Pembayaran::count(), Invoice::count(), Keuangan::count()], 'Conversion retry created unexpected financial records.');
    echo "PASS conversion rollback: lease insert observed, no orphan/history/finance mutation before cleanup, recovery one linked lease\n";
} catch (Throwable $exception) {
    fwrite(STDERR, get_class($exception).': '.$exception->getMessage()."\n");
    exit(1);
}
