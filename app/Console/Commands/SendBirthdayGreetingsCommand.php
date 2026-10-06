<?php

namespace App\Console\Commands;

use App\Models\Penghuni;
use App\Services\WhatsAppService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendBirthdayGreetingsCommand extends Command
{
    protected $signature = 'notifications:send-birthday-greetings';

    protected $description = 'Kirim ucapan ulang tahun otomatis untuk penghuni berdasarkan tanggal lahir';

    public function handle(WhatsAppService $whatsAppService): int
    {
        $today = Carbon::today();

        $penghunis = Penghuni::query()
            ->whereNotNull('tanggal_lahir')
            ->whereNotNull('telepon')
            ->where(function ($query) use ($today): void {
                $query->whereDate('last_birthday_notified_on', '!=', $today)
                    ->orWhereNull('last_birthday_notified_on');
            })
            ->whereHas('sewas', function ($query): void {
                $query->where('status', 'aktif');
            })
            ->get();

        $sent = 0;

        foreach ($penghunis as $penghuni) {
            $tanggalLahir = $penghuni->tanggal_lahir instanceof Carbon
                ? $penghuni->tanggal_lahir->copy()
                : Carbon::parse($penghuni->tanggal_lahir);

            if ((int) $tanggalLahir->month !== (int) $today->month || (int) $tanggalLahir->day !== (int) $today->day) {
                continue;
            }

            $usia = $tanggalLahir->diffInYears($today);
            if ($usia <= 0) {
                continue;
            }

            $sentNow = $whatsAppService->sendByType(
                'ulang_tahun_penghuni',
                (string) $penghuni->telepon,
                [
                    'nama' => (string) ($penghuni->nama ?? 'Penghuni'),
                    'usia' => (string) $usia,
                    'ulang_tahun_ke' => (string) $usia,
                ],
                "Selamat ulang tahun {$penghuni->nama}! Hari ini Anda berulang tahun yang ke-{$usia}."
            );

            if ($sentNow) {
                $penghuni->forceFill([
                    'last_birthday_notified_on' => $today->toDateString(),
                ])->save();

                $sent++;
            }
        }

        $this->info("Ucapan ulang tahun terkirim: {$sent}");

        return self::SUCCESS;
    }
}
