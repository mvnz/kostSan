<?php

namespace App\Console\Commands;

use App\Models\KostProfile;
use App\Models\Sewa;
use App\Services\WhatsAppService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendSewaExpiryReminderCommand extends Command
{
    protected $signature = 'notifications:send-sewa-expiry-reminders';

    protected $description = 'Kirim notifikasi harian masa sewa akan habis sesuai konfigurasi hari reminder di profil kost';

    public function handle(WhatsAppService $whatsAppService): int
    {
        $profile = KostProfile::query()->first();
        // Jumlah hari sebelum masa berakhir untuk mulai kirim reminder (dari pengaturan)
        $daysBeforeEnd = (int) ($profile?->whatsapp_sewa_habis_reminder_days ?? 7);
        $daysBeforeEnd = max(1, min($daysBeforeEnd, 60));
        // Jika false, hanya kirim satu kali pada hari pertama window (H-N)
        $repeatUntilPaid = (bool) ($profile?->whatsapp_sewa_habis_repeat_until_paid ?? true);

        $today = Carbon::today();
        $endWindow = $today->copy()->addDays($daysBeforeEnd);

        $sewas = Sewa::query()
            ->with(['penghuni', 'kamar'])
            ->whereIn('status', ['aktif', 'menunggak'])
            ->whereNotNull('tanggal_keluar')
            ->whereDate('tanggal_keluar', '>=', $today)
            ->whereDate('tanggal_keluar', '<=', $endWindow)
            ->get();

        $sent = 0;

        foreach ($sewas as $sewa) {
            if (! filled($sewa->penghuni?->telepon)) {
                continue;
            }

            $tanggalKeluar = $sewa->tanggal_keluar instanceof Carbon
                ? $sewa->tanggal_keluar->copy()
                : Carbon::parse($sewa->tanggal_keluar);

            $sisaHari = (int) $today->diffInDays($tanggalKeluar, false);

            // Kirim setiap hari selama dalam window (misal: H-7 s/d H-1)
            if ($sisaHari < 1 || $sisaHari > $daysBeforeEnd) {
                continue;
            }

            // Jika repeat dimatikan, hanya kirim tepat pada H-N pertama kali
            if (! $repeatUntilPaid && $sisaHari !== $daysBeforeEnd) {
                continue;
            }

            if ($this->isPaidForExitPeriod($sewa, $tanggalKeluar)) {
                continue;
            }

            $jumlah = number_format((float) ($sewa->biaya_bulanan ?? 0), 0, ',', '.');

            $sentNow = $whatsAppService->sendByType(
                'masa_sewa_akan_habis',
                (string) $sewa->penghuni->telepon,
                [
                    'nama' => (string) ($sewa->penghuni->nama ?? 'Penghuni'),
                    'nomor_kamar' => (string) ($sewa->kamar->nomor ?? '-'),
                    'tanggal_keluar' => (string) $tanggalKeluar->format('d-m-Y'),
                    'sisa_hari' => (string) $sisaHari,
                    'periode' => (string) $tanggalKeluar->format('m-Y'),
                    'jumlah' => $jumlah,
                    'status' => 'belum_lunas',
                ],
                "Halo {$sewa->penghuni->nama}, masa sewa kamar {$sewa->kamar->nomor} akan berakhir pada {$tanggalKeluar->format('d-m-Y')} ({$sisaHari} hari lagi). Mohon segera lakukan pembayaran agar sewa tetap aktif."
            );

            if ($sentNow) {
                $sent++;
            }
        }

        $this->info("Notifikasi masa sewa terkirim: {$sent}");

        return self::SUCCESS;
    }

    private function isPaidForExitPeriod(Sewa $sewa, Carbon $tanggalKeluar): bool
    {
        return $sewa->pembayarans()
            ->where('status', 'lunas')
            ->whereYear('periode', $tanggalKeluar->year)
            ->whereMonth('periode', $tanggalKeluar->month)
            ->exists();
    }
}
