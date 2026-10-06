@php
    $pageTitle = 'Notifikasi WhatsApp';
@endphp
@extends('layouts.app')

@section('content')
<div class="page-head d-flex align-items-center gap-3">
    <div class="page-head-icon"><i class="bx bx-bell"></i></div>
    <div>
        <h2>Notifikasi WhatsApp</h2>
        <p>Kelola integrasi dan template pesan notifikasi otomatis.</p>
    </div>
</div>

@include('profil-kost._submenu')

<style>
    .template-card {
        border: 1px solid #e4ebf7;
        border-radius: .9rem;
        padding: .95rem;
        background: #fff;
        box-shadow: 0 8px 18px rgba(16, 34, 75, .04);
    }

    .template-card-icon {
        width: 38px;
        height: 38px;
        border-radius: .7rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.05rem;
        flex-shrink: 0;
    }

    .template-card textarea {
        min-height: 120px;
        resize: vertical;
    }

    .template-card-active {
        border-bottom: 1px dashed #e4ebf7;
        padding-bottom: .65rem;
        margin-bottom: .75rem;
    }

    @media (max-width: 767.98px) {
        .template-card {
            padding: .85rem;
        }

        .template-card textarea {
            min-height: 105px;
        }
    }
</style>

<form method="POST" action="{{ route('profil-kost.notification.update') }}">
    @csrf
    @method('PUT')

    <div class="card mb-3">
        <div class="card-body">
            <div class="mb-3 form-check">
                <input type="hidden" name="whatsapp_enabled" value="0">
                <input class="form-check-input" type="checkbox" name="whatsapp_enabled" id="whatsapp_enabled" value="1" @checked(old('whatsapp_enabled', $profile->whatsapp_enabled))>
                <label class="form-check-label" for="whatsapp_enabled">Aktifkan notifikasi WhatsApp</label>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Provider</label>
                    <select name="whatsapp_provider" class="form-select">
                        <option value="fonnte" @selected(old('whatsapp_provider', $profile->whatsapp_provider ?? 'fonnte') === 'fonnte')>Fonnte</option>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Timeout (detik)</label>
                    <input type="number" name="whatsapp_timeout" min="3" max="60" class="form-control" value="{{ old('whatsapp_timeout', $profile->whatsapp_timeout ?? 10) }}">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Base URL API</label>
                <input type="url" name="whatsapp_base_url" class="form-control" value="{{ old('whatsapp_base_url', $profile->whatsapp_base_url ?? 'https://api.fonnte.com') }}" placeholder="https://api.fonnte.com">
            </div>

            <div class="row">
                <div class="col-md-8 mb-3">
                    <label class="form-label">Token API WhatsApp</label>
                    <input type="text" name="whatsapp_token" class="form-control" value="{{ old('whatsapp_token', $profile->whatsapp_token) }}" placeholder="Masukkan token API">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Kode Negara Default</label>
                    <input type="text" name="whatsapp_default_country_code" class="form-control" value="{{ old('whatsapp_default_country_code', $profile->whatsapp_default_country_code ?? '62') }}" placeholder="62">
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <div class="mb-2">
                <h6 class="mb-1">Jenis Notifikasi Aktif</h6>
                <small class="text-body-secondary">Aktifkan notifikasi langsung dari tick di bagian atas masing-masing card template.</small>
            </div>

            <div class="border rounded p-3 mt-3">
                <h6 class="mb-2">Pengaturan Khusus: Reminder Masa Sewa</h6>
                <small class="text-body-secondary d-block mb-3">Pengaturan ini dipakai untuk jenis notifikasi <strong>Masa Sewa Akan Habis</strong>.</small>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Kirim Mulai H- (hari)</label>
                        <input
                            type="number"
                            name="whatsapp_sewa_habis_reminder_days"
                            min="1"
                            max="60"
                            class="form-control"
                            value="{{ old('whatsapp_sewa_habis_reminder_days', $profile->whatsapp_sewa_habis_reminder_days ?? 7) }}">
                        <small class="text-body-secondary d-block mt-1">Contoh isi 7 berarti kirim mulai H-7 sebelum tanggal keluar.</small>
                    </div>
                    <div class="col-md-6 mb-3 d-flex align-items-end">
                        <div class="form-check mb-2">
                            <input type="hidden" name="whatsapp_sewa_habis_repeat_until_paid" value="0">
                            <input
                                class="form-check-input"
                                type="checkbox"
                                name="whatsapp_sewa_habis_repeat_until_paid"
                                id="whatsapp_sewa_habis_repeat_until_paid"
                                value="1"
                                @checked(old('whatsapp_sewa_habis_repeat_until_paid', $profile->whatsapp_sewa_habis_repeat_until_paid ?? true))>
                            <label class="form-check-label" for="whatsapp_sewa_habis_repeat_until_paid">
                                Kirim berulang setiap hari sampai pembayaran lunas
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <div class="mb-2">
                <h6 class="mb-1">Template Pesan Per Jenis</h6>
                <small class="text-body-secondary">Gunakan placeholder: &#123;&#123;nama&#125;&#125;, &#123;&#123;nama_kos&#125;&#125;, &#123;&#123;nomor_kamar&#125;&#125;, &#123;&#123;tanggal_masuk&#125;&#125;, &#123;&#123;tanggal_keluar&#125;&#125;, &#123;&#123;sisa_hari&#125;&#125;, &#123;&#123;periode&#125;&#125;, &#123;&#123;jumlah&#125;&#125;, &#123;&#123;status&#125;&#125;, &#123;&#123;payment_link&#125;&#125;, &#123;&#123;ulang_tahun_ke&#125;&#125;, &#123;&#123;usia&#125;&#125;.</small>
            </div>

            @php
                $templateIcons = [
                    'sewa_aktif' => ['icon' => 'bx-home-heart', 'bg' => 'bg-label-primary', 'text' => 'text-primary'],
                    'pembayaran_dicatat' => ['icon' => 'bx-wallet', 'bg' => 'bg-label-success', 'text' => 'text-success'],
                    'link_pembayaran' => ['icon' => 'bx-link-alt', 'bg' => 'bg-label-info', 'text' => 'text-info'],
                    'masa_sewa_akan_habis' => ['icon' => 'bx-timer', 'bg' => 'bg-label-warning', 'text' => 'text-warning'],
                    'ulang_tahun_penghuni' => ['icon' => 'bx-gift', 'bg' => 'bg-label-danger', 'text' => 'text-danger'],
                ];
            @endphp

            <div class="row g-3 mt-1">
                @foreach(($notificationTypeOptions ?? []) as $typeKey => $typeOption)
                    @php
                        $meta = $templateIcons[$typeKey] ?? ['icon' => 'bx-message-square-detail', 'bg' => 'bg-label-secondary', 'text' => 'text-secondary'];
                        $isActive = in_array($typeKey, (array) old('notification_types', $profile->whatsapp_notification_types ?? array_keys($notificationTypeOptions ?? [])), true);
                    @endphp
                    <div class="col-12 col-lg-6">
                        <div class="template-card h-100">
                            <div class="template-card-active d-flex align-items-center justify-content-between gap-2">
                                <span class="fw-semibold">Aktifkan Notifikasi Ini</span>
                                <div class="form-check form-switch m-0">
                                    <input
                                        class="form-check-input"
                                        type="checkbox"
                                        role="switch"
                                        name="notification_types[]"
                                        id="notif_{{ $typeKey }}"
                                        value="{{ $typeKey }}"
                                        @checked($isActive)>
                                </div>
                            </div>

                            <div class="d-flex align-items-start gap-3 mb-3">
                                <span class="template-card-icon {{ $meta['bg'] }} {{ $meta['text'] }}">
                                    <i class="bx {{ $meta['icon'] }}"></i>
                                </span>
                                <div>
                                    <h6 class="mb-1"><label for="notif_{{ $typeKey }}" class="mb-0">{{ $typeOption['label'] }}</label></h6>
                                    <small class="text-body-secondary">{{ $typeOption['description'] }}</small>
                                </div>
                            </div>
                            <textarea
                                name="message_templates[{{ $typeKey }}]"
                                class="form-control"
                                rows="4"
                                placeholder="Tulis template pesan notifikasi">{{ old('message_templates.' . $typeKey, $profile->whatsapp_message_templates[$typeKey] ?? $typeOption['default_template']) }}</textarea>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Simpan Notifikasi</button>
                <a href="{{ route('dashboard') }}" class="btn btn-secondary">Kembali</a>
            </div>
        </div>
    </div>
</form>
@endsection
