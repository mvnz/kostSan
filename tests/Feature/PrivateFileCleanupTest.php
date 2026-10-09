<?php

namespace Tests\Feature;

use App\Models\FileCleanupJob;
use App\Models\Kamar;
use App\Models\Pembayaran;
use App\Models\Penghuni;
use App\Models\Sewa;
use App\Models\User;
use App\Services\PrivateFileCleanup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class PrivateFileCleanupTest extends TestCase
{
    use RefreshDatabase;

    public function test_failed_delete_is_deduplicated_and_records_retry_metadata(): void
    {
        $disk = Mockery::mock();
        $disk->shouldReceive('delete')->twice()->with('bukti-keuangan/orphan.pdf')->andReturn(false);
        Storage::shouldReceive('disk')->twice()->with('local')->andReturn($disk);

        $cleanup = app(PrivateFileCleanup::class);
        $this->assertFalse($cleanup->deleteOrQueue('bukti-keuangan/orphan.pdf', 'finance replacement'));
        $this->assertFalse($cleanup->deleteOrQueue('bukti-keuangan/orphan.pdf', 'finance retry'));

        $job = FileCleanupJob::sole();
        $this->assertSame('bukti-keuangan/orphan.pdf', $job->path);
        $this->assertSame('finance retry', $job->context);
        $this->assertSame(2, $job->attempts);
        $this->assertNotNull($job->last_attempt_at);
        $this->assertNotNull($job->last_error);
    }

    public function test_cleanup_command_removes_file_and_queue_record(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('bukti-keuangan/retry.pdf', 'synthetic');
        FileCleanupJob::create(['path' => 'bukti-keuangan/retry.pdf', 'context' => 'synthetic', 'attempts' => 1, 'last_error' => 'previous failure', 'last_attempt_at' => now()]);

        $this->artisan('private-files:cleanup', ['--limit' => 10])->expectsOutputToContain('berhasil: 1')->assertSuccessful();

        Storage::disk('local')->assertMissing('bukti-keuangan/retry.pdf');
        $this->assertDatabaseCount('file_cleanup_jobs', 0);
    }

    public function test_successful_delete_does_not_create_queue_record(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('bukti-sewa/old.pdf', 'synthetic');

        $this->assertTrue(app(PrivateFileCleanup::class)->deleteOrQueue('bukti-sewa/old.pdf', 'lease replacement'));

        Storage::disk('local')->assertMissing('bukti-sewa/old.pdf');
        $this->assertDatabaseCount('file_cleanup_jobs', 0);
    }

    public function test_dry_run_reports_without_mutating_file_or_queue(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('bukti-sewa/pending.pdf', 'synthetic');
        FileCleanupJob::create(['path' => 'bukti-sewa/pending.pdf', 'context' => 'lease replacement', 'attempts' => 2, 'last_error' => 'offline', 'last_attempt_at' => now()]);

        $this->artisan('private-files:cleanup', ['--dry-run' => true])->expectsOutputToContain('Antrean tertunda: 1')->assertSuccessful();

        Storage::disk('local')->assertExists('bukti-sewa/pending.pdf');
        $this->assertDatabaseCount('file_cleanup_jobs', 1);
    }

    public function test_payment_delete_commits_and_queues_failed_proof_cleanup(): void
    {
        $this->actingAs(User::factory()->create());
        $room = Kamar::create(['nomor' => 'CLEANUP', 'tipe' => 'A', 'harga_bulanan' => 550000, 'status' => 'terisi']);
        $resident = Penghuni::create(['nama' => 'Cleanup Synthetic', 'telepon' => '']);
        $lease = Sewa::create(['kamar_id' => $room->id, 'penghuni_id' => $resident->id, 'tanggal_masuk' => '2026-10-01', 'biaya_bulanan' => 550000, 'status' => 'aktif']);
        $payment = Pembayaran::create(['sewa_id' => $lease->id, 'periode' => '2026-10-01', 'metode' => 'cash', 'jumlah' => 550000, 'status' => 'belum_lunas', 'bukti_pembayaran_path' => 'bukti-pembayaran-sewa/fail.pdf']);
        $disk = Mockery::mock();
        $disk->shouldReceive('delete')->once()->with('bukti-pembayaran-sewa/fail.pdf')->andReturn(false);
        Storage::shouldReceive('disk')->once()->with('local')->andReturn($disk);

        $this->delete('/pembayarans/'.$payment->id)->assertRedirect('/pembayarans');

        $this->assertDatabaseMissing('pembayarans', ['id' => $payment->id]);
        $this->assertDatabaseHas('file_cleanup_jobs', ['path' => 'bukti-pembayaran-sewa/fail.pdf', 'context' => 'payment delete', 'attempts' => 1]);
    }

    public function test_lease_delete_commits_and_queues_failed_proof_cleanup(): void
    {
        $this->actingAs(User::factory()->create());
        $room = Kamar::create(['nomor' => 'LEASE-CLEANUP', 'tipe' => 'A', 'harga_bulanan' => 550000, 'status' => 'terisi']);
        $resident = Penghuni::create(['nama' => 'Lease Cleanup Synthetic', 'telepon' => '']);
        $lease = Sewa::create([
            'kamar_id' => $room->id,
            'penghuni_id' => $resident->id,
            'tanggal_masuk' => '2026-10-01',
            'biaya_bulanan' => 550000,
            'status' => 'aktif',
            'bukti_pembayaran' => 'bukti-sewa/fail.pdf',
        ]);
        $disk = Mockery::mock();
        $disk->shouldReceive('delete')->once()->with('bukti-sewa/fail.pdf')->andReturn(false);
        Storage::shouldReceive('disk')->once()->with('local')->andReturn($disk);

        $this->delete('/sewas/'.$lease->id)->assertRedirect('/sewas');

        $this->assertDatabaseMissing('sewas', ['id' => $lease->id]);
        $this->assertSame('tersedia', $room->fresh()->status);
        $this->assertDatabaseHas('file_cleanup_jobs', ['path' => 'bukti-sewa/fail.pdf', 'context' => 'lease delete', 'attempts' => 1]);
    }

    public function test_dashboard_warns_about_cleanup_backlog_without_exposing_paths_or_errors(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get('/')->assertOk()->assertDontSee('Antrean file privat perlu ditangani');

        FileCleanupJob::create([
            'path' => 'bukti-keuangan/private-resident-proof.pdf',
            'context' => 'finance replacement',
            'attempts' => 3,
            'last_error' => 'storage credential secret detail',
            'last_attempt_at' => now()->subHour(),
        ]);
        FileCleanupJob::create([
            'path' => 'identitas/private-resident-id.jpg',
            'context' => 'registration rollback',
            'attempts' => 1,
            'last_error' => 'offline',
            'last_attempt_at' => now(),
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Antrean file privat perlu ditangani')
            ->assertSee('2 file')
            ->assertSee('Percobaan tertinggi: 3')
            ->assertSee('private-files:cleanup --dry-run')
            ->assertDontSee('private-resident-proof.pdf')
            ->assertDontSee('storage credential secret detail');
    }
}
