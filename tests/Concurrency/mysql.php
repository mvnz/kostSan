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
use App\Models\Invoice;
use App\Models\Kamar;
use App\Models\Keuangan;
use App\Models\Pembayaran;
use App\Models\Penghuni;
use App\Models\Reservasi;
use App\Models\Sewa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Process\Process;

function check(bool $condition, string $message): void
{
    if (! $condition) {
        throw new RuntimeException($message);
    }
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
            operate($argv[2], (int) $argv[3], (int) $argv[4]);
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
} catch (Throwable $exception) {
    fwrite(STDERR, get_class($exception).': '.$exception->getMessage()."\n");
    exit(1);
}
