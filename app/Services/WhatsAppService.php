<?php

namespace App\Services;

use App\Models\KostProfile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class WhatsAppService
{
    public function sendByType(string $type, string $target, array $context = [], ?string $fallbackMessage = null): bool
    {
        $settings = $this->settings();

        if (! $settings['enabled']) {
            return false;
        }

        if (! in_array($type, $settings['notification_types'], true)) {
            return false;
        }

        $template = (string) ($settings['message_templates'][$type] ?? '');
        if ($template === '') {
            $defaults = $this->notificationTypeDefaults();
            $template = (string) ($defaults[$type] ?? '');
        }

        $context = array_merge([
            'nama_kos' => $settings['kost_name'] ?? '',
        ], $context);

        $message = $this->renderTemplate($template, $context);
        if ($message === '' && $fallbackMessage !== null) {
            $message = trim($fallbackMessage);
        }

        $message = $this->appendKostNameIfMissing($message, (string) ($settings['kost_name'] ?? ''));

        if ($message === '') {
            return false;
        }

        return $this->send($target, $message);
    }

    public function send(string $target, string $message): bool
    {
        $settings = $this->settings();

        if (! $settings['enabled']) {
            return false;
        }

        if (! $this->isConfigured($settings)) {
            Log::warning('WhatsApp notification skipped because configuration is incomplete.');
            return false;
        }

        $provider = $settings['provider'];

        if ($provider !== 'fonnte') {
            Log::warning('WhatsApp notification skipped because provider is unsupported.', [
                'provider' => $provider,
            ]);
            return false;
        }

        $normalizedTarget = $this->normalizePhoneNumber($target, $settings['default_country_code']);

        if ($normalizedTarget === '') {
            return false;
        }

        try {
            $response = Http::timeout((int) config('whatsapp.timeout', 10))
                ->withHeaders([
                    'Authorization' => $settings['token'],
                ])
                ->asForm()
                ->post($settings['base_url'].'/send', [
                    'target' => $normalizedTarget,
                    'message' => $message,
                ]);

            if (! $response->successful()) {
                Log::warning('WhatsApp notification failed.', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return false;
            }

            return true;
        } catch (Throwable $e) {
            Log::warning('WhatsApp notification error.', [
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function isConfigured(array $settings): bool
    {
        return filled($settings['token'])
            && filled($settings['base_url']);
    }

    private function normalizePhoneNumber(string $phone, string $countryCode): string
    {
        $digits = preg_replace('/[^0-9]/', '', $phone) ?? '';

        if ($digits === '') {
            return '';
        }

        if (str_starts_with($digits, '0')) {
            $normalizedCountryCode = preg_replace('/[^0-9]/', '', $countryCode) ?? '';
            $normalizedCountryCode = $normalizedCountryCode !== '' ? $normalizedCountryCode : '62';
            $digits = $normalizedCountryCode.ltrim($digits, '0');
        }

        return $digits;
    }

    private function settings(): array
    {
        $profile = KostProfile::query()->first();
        $defaults = $this->notificationTypeDefaults();
        $defaultTypes = array_keys($defaults);

        $configuredTypes = null;
        if ($profile && $profile->whatsapp_notification_types !== null) {
            $configuredTypes = array_values(array_intersect(
                $defaultTypes,
                is_array($profile->whatsapp_notification_types) ? $profile->whatsapp_notification_types : []
            ));
        }

        $configuredTemplates = is_array($profile?->whatsapp_message_templates)
            ? array_intersect_key($profile->whatsapp_message_templates, $defaults)
            : [];

        return [
            'enabled' => (bool) ($profile->whatsapp_enabled ?? config('whatsapp.enabled', false)),
            'provider' => (string) ($profile->whatsapp_provider ?? config('whatsapp.provider', 'fonnte')),
            'base_url' => rtrim((string) ($profile->whatsapp_base_url ?? config('whatsapp.base_url', '')), '/'),
            'token' => (string) ($profile->whatsapp_token ?? config('whatsapp.token', '')),
            'timeout' => (int) ($profile->whatsapp_timeout ?? config('whatsapp.timeout', 10)),
            'default_country_code' => (string) ($profile->whatsapp_default_country_code ?? config('whatsapp.default_country_code', '62')),
            'kost_name' => (string) ($profile->nama_kost ?? config('app.name', 'Kost San')),
            'notification_types' => $configuredTypes ?? $defaultTypes,
            'message_templates' => array_replace($defaults, $configuredTemplates),
        ];
    }

    private function notificationTypeDefaults(): array
    {
        return [
            'sewa_aktif' => 'Halo {{nama}}, sewa kamar {{nomor_kamar}} sudah aktif mulai {{tanggal_masuk}}. Salam, {{nama_kos}}.',
            'pembayaran_dicatat' => 'Halo {{nama}}, pembayaran kos periode {{periode}} sebesar Rp {{jumlah}} dengan status {{status}} telah dicatat. Salam, {{nama_kos}}.',
            'link_pembayaran' => 'Halo {{nama}}, pendaftaran sewa Anda sudah diterima. Silakan lanjutkan pembayaran melalui link berikut: {{payment_link}}\n\nSalam, {{nama_kos}}.',
            'masa_sewa_akan_habis' => 'Halo {{nama}}, masa sewa kamar {{nomor_kamar}} akan berakhir pada {{tanggal_keluar}} ({{sisa_hari}} hari lagi). Segera lakukan pembayaran agar sewa tetap aktif. Salam, {{nama_kos}}.',
            'ulang_tahun_penghuni' => 'Hai {{nama}}, selamat ulang tahun ya! Hari ini kamu genap {{ulang_tahun_ke}} tahun. Semoga harimu bahagia, sehat selalu, dan semua doa baiknya terkabul. Salam hangat dari {{nama_kos}}.',
        ];
    }

    private function appendKostNameIfMissing(string $message, string $kostName): string
    {
        $message = trim($message);
        $kostName = trim($kostName);

        if ($message === '' || $kostName === '') {
            return $message;
        }

        if (mb_stripos($message, $kostName) !== false) {
            return $message;
        }

        return $message."\n\nSalam, {$kostName}.";
    }

    private function renderTemplate(string $template, array $context): string
    {
        $message = trim($template);
        if ($message === '') {
            return '';
        }

        $replacements = [];
        foreach ($context as $key => $value) {
            $replacements['{{'.$key.'}}'] = (string) $value;
        }

        $message = strtr($message, $replacements);
        $message = preg_replace('/\{\{\s*[a-zA-Z0-9_]+\s*\}\}/', '', $message) ?? $message;

        return trim($message);
    }
}
