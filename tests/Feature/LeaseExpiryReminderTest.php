<?php

namespace Tests\Feature;

use App\Models\Kamar;
use App\Models\Penghuni;
use App\Models\Sewa;
use App\Services\WhatsAppService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class LeaseExpiryReminderTest extends TestCase
{
    use RefreshDatabase;

    public function test_expiry_command_includes_active_and_overdue_occupants_but_not_completed_leases(): void
    {
        $this->travelTo(Carbon::parse('2026-10-10 09:00:00'));
        foreach (['aktif', 'menunggak', 'selesai'] as $index => $status) {
            $room = Kamar::create([
                'nomor' => 'REMINDER-'.$index,
                'tipe' => 'A',
                'harga_bulanan' => 550000,
                'status' => $status === 'selesai' ? 'tersedia' : 'terisi',
            ]);
            $resident = Penghuni::create([
                'nama' => 'Reminder '.$status,
                'telepon' => '08000000000'.$index,
            ]);
            Sewa::create([
                'kamar_id' => $room->id,
                'penghuni_id' => $resident->id,
                'tanggal_masuk' => '2026-09-01',
                'tanggal_keluar' => '2026-10-15',
                'biaya_bulanan' => 550000,
                'status' => $status,
            ]);
        }

        $this->mock(WhatsAppService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('sendByType')
                ->twice()
                ->withArgs(fn (string $type, string $target) => $type === 'masa_sewa_akan_habis' && str_starts_with($target, '08000000000'))
                ->andReturn(true);
        });

        $this->artisan('notifications:send-sewa-expiry-reminders')
            ->expectsOutput('Notifikasi masa sewa terkirim: 2')
            ->assertExitCode(0);
        $this->travelBack();
    }
}
