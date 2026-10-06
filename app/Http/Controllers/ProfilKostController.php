<?php

namespace App\Http\Controllers;

use App\Models\Kamar;
use App\Models\KostProfile;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProfilKostController extends Controller
{
    public function profile()
    {
        $profile = $this->resolveProfile();

        return view('profil-kost.profile', compact('profile'));
    }

    public function updateProfile(Request $request)
    {
        $validated = $request->validate([
            'nama_kost' => ['required', 'string', 'max:150'],
            'pemilik' => ['nullable', 'string', 'max:150'],
            'telepon' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:150'],
            'alamat' => ['nullable', 'string'],
            'deskripsi' => ['nullable', 'string'],
        ]);

        $validated['jumlah_kamar'] = Kamar::count();

        $profile = KostProfile::query()->firstOrNew(['id' => 1]);
        $profile->fill($validated)->save();

        return redirect()->route('profil-kost.profile')->with('success', 'Profil kost berhasil diperbarui.');
    }

    public function notification()
    {
        $profile = $this->resolveProfile();
        $notificationTypeOptions = $this->notificationTypeOptions();
        $profile = $this->syncNotificationTemplatesWithCurrentDefaults($profile, $notificationTypeOptions);

        return view('profil-kost.notification', compact('profile', 'notificationTypeOptions'));
    }

    public function updateNotification(Request $request)
    {
        $notificationTypeOptions = $this->notificationTypeOptions();
        $availableTypes = array_keys($notificationTypeOptions);

        $validated = $request->validate([
            'whatsapp_enabled' => ['nullable', 'boolean'],
            'whatsapp_provider' => ['required', 'in:fonnte'],
            'whatsapp_base_url' => ['nullable', 'url', 'max:255'],
            'whatsapp_token' => ['nullable', 'string', 'max:255'],
            'whatsapp_timeout' => ['nullable', 'integer', 'min:3', 'max:60'],
            'whatsapp_default_country_code' => ['nullable', 'string', 'max:10', 'regex:/^[0-9]+$/'],
            'whatsapp_sewa_habis_reminder_days' => ['nullable', 'integer', 'min:1', 'max:60'],
            'whatsapp_sewa_habis_repeat_until_paid' => ['nullable', 'boolean'],
            'notification_types' => ['nullable', 'array'],
            'notification_types.*' => ['string', Rule::in($availableTypes)],
            'message_templates' => ['nullable', 'array'],
            'message_templates.*' => ['nullable', 'string', 'max:2000'],
        ]);

        $defaultTemplates = collect($notificationTypeOptions)
            ->mapWithKeys(fn (array $item, string $key) => [$key => $item['default_template']])
            ->all();

        $selectedTypes = array_values(array_intersect(
            $availableTypes,
            $validated['notification_types'] ?? []
        ));

        $rawTemplates = $validated['message_templates'] ?? [];
        $messageTemplates = [];
        foreach ($availableTypes as $type) {
            $template = trim((string) ($rawTemplates[$type] ?? $defaultTemplates[$type]));
            $normalizedTemplate = $template !== '' ? $template : $defaultTemplates[$type];
            $messageTemplates[$type] = $this->ensureTemplateContainsNamaKos($normalizedTemplate);
        }

        $profile = KostProfile::query()->firstOrNew(['id' => 1]);
        $profile->fill([
            'whatsapp_enabled' => $request->boolean('whatsapp_enabled'),
            'whatsapp_provider' => $validated['whatsapp_provider'],
            'whatsapp_base_url' => $validated['whatsapp_base_url'] ?? null,
            'whatsapp_token' => $validated['whatsapp_token'] ?? null,
            'whatsapp_timeout' => (int) ($validated['whatsapp_timeout'] ?? 10),
            'whatsapp_default_country_code' => $validated['whatsapp_default_country_code'] ?? '62',
            'whatsapp_sewa_habis_reminder_days' => (int) ($validated['whatsapp_sewa_habis_reminder_days'] ?? 7),
            'whatsapp_sewa_habis_repeat_until_paid' => $request->boolean('whatsapp_sewa_habis_repeat_until_paid', true),
            'whatsapp_notification_types' => $selectedTypes,
            'whatsapp_message_templates' => $messageTemplates,
        ])->save();

        return redirect()->route('profil-kost.notification')->with('success', 'Pengaturan notifikasi berhasil diperbarui.');
    }

    private function resolveProfile(): KostProfile
    {
        $defaultTypes = array_keys($this->notificationTypeOptions());
        $defaultTemplates = collect($this->notificationTypeOptions())
            ->mapWithKeys(fn (array $item, string $key) => [$key => $item['default_template']])
            ->all();

        return KostProfile::query()->firstOrCreate(
            ['id' => 1],
            [
                'nama_kost' => 'Kost San',
                'jumlah_kamar' => Kamar::count(),
                'whatsapp_enabled' => (bool) config('whatsapp.enabled'),
                'whatsapp_provider' => (string) config('whatsapp.provider', 'fonnte'),
                'whatsapp_base_url' => (string) config('whatsapp.base_url'),
                'whatsapp_token' => (string) config('whatsapp.token'),
                'whatsapp_timeout' => (int) config('whatsapp.timeout', 10),
                'whatsapp_default_country_code' => (string) config('whatsapp.default_country_code', '62'),
                'whatsapp_sewa_habis_reminder_days' => 7,
                'whatsapp_sewa_habis_repeat_until_paid' => true,
                'whatsapp_notification_types' => $defaultTypes,
                'whatsapp_message_templates' => $defaultTemplates,
                'diskon_sewa_1_bulan' => 0,
                'diskon_sewa_3_bulan' => 0,
                'diskon_sewa_6_bulan' => 0,
                'diskon_sewa_12_bulan' => 0,
            ]
        );
    }

    private function notificationTypeOptions(): array
    {
        return [
            'sewa_aktif' => [
                'label' => 'Sewa Aktif',
                'description' => 'Dikirim saat data sewa berhasil dibuat dan status sewa aktif.',
                'default_template' => 'Halo {{nama}}, sewa kamar {{nomor_kamar}} sudah aktif mulai {{tanggal_masuk}}. Salam, {{nama_kos}}.',
            ],
            'pembayaran_dicatat' => [
                'label' => 'Pembayaran Dicatat',
                'description' => 'Dikirim saat pembayaran manual dicatat dari menu pembayaran.',
                'default_template' => 'Halo {{nama}}, pembayaran kos periode {{periode}} sebesar Rp {{jumlah}} dengan status {{status}} telah dicatat. Salam, {{nama_kos}}.',
            ],
            'link_pembayaran' => [
                'label' => 'Link Pembayaran Sewa',
                'description' => 'Dikirim setelah pendaftaran penghuni selesai dan link pembayaran dibuat.',
                'default_template' => 'Halo {{nama}}, pendaftaran sewa Anda sudah diterima. Silakan lanjutkan pembayaran melalui link berikut: {{payment_link}}\n\nSalam, {{nama_kos}}.',
            ],
            'masa_sewa_akan_habis' => [
                'label' => 'Masa Sewa Akan Habis',
                'description' => 'Dikirim H-<hari> sebelum masa sewa berakhir. Bisa diulang tiap hari sampai pembayaran lunas.',
                'default_template' => 'Halo {{nama}}, masa sewa kamar {{nomor_kamar}} akan berakhir pada {{tanggal_keluar}} ({{sisa_hari}} hari lagi). Segera lakukan pembayaran agar sewa tetap aktif. Salam, {{nama_kos}}.',
            ],
            'ulang_tahun_penghuni' => [
                'label' => 'Ulang Tahun Penghuni',
                'description' => 'Dikirim otomatis saat tanggal lahir penghuni, beserta usia/ulang tahun ke-berapa.',
                'default_template' => 'Hai {{nama}}, selamat ulang tahun ya! Hari ini kamu genap {{ulang_tahun_ke}} tahun. Semoga harimu bahagia, sehat selalu, dan semua doa baiknya terkabul. Salam hangat dari {{nama_kos}}.',
            ],
        ];
    }

    private function syncNotificationTemplatesWithCurrentDefaults(KostProfile $profile, array $notificationTypeOptions): KostProfile
    {
        $defaultTemplates = collect($notificationTypeOptions)
            ->mapWithKeys(fn (array $item, string $key) => [$key => $item['default_template']])
            ->all();

        $storedTemplates = is_array($profile->whatsapp_message_templates)
            ? $profile->whatsapp_message_templates
            : [];

        $normalizedTemplates = [];
        $hasChanges = false;

        foreach ($defaultTemplates as $type => $defaultTemplate) {
            $rawTemplate = trim((string) ($storedTemplates[$type] ?? ''));
            $baseTemplate = $rawTemplate !== '' ? $rawTemplate : $defaultTemplate;
            $normalizedTemplate = $this->ensureTemplateContainsNamaKos($baseTemplate);

            $normalizedTemplates[$type] = $normalizedTemplate;

            if (! array_key_exists($type, $storedTemplates) || $storedTemplates[$type] !== $normalizedTemplate) {
                $hasChanges = true;
            }
        }

        if ($hasChanges) {
            $profile->fill([
                'whatsapp_message_templates' => $normalizedTemplates,
            ])->save();
        } else {
            $profile->setAttribute('whatsapp_message_templates', $normalizedTemplates);
        }

        return $profile;
    }

    private function ensureTemplateContainsNamaKos(string $template): string
    {
        $template = trim($template);

        if ($template === '' || str_contains($template, '{{nama_kos}}')) {
            return $template;
        }

        return $template."\n\nSalam, {{nama_kos}}.";
    }
}
