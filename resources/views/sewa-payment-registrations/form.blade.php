<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Pembayaran Sewa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --bg: #f4f7fb;
            --card: #ffffff;
            --line: #dbe3ef;
            --text: #1d2736;
            --muted: #5b6b82;
            --primary: #2f6be0;
            --accent: #11a36a;
        }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: "Segoe UI", Tahoma, sans-serif; background: var(--bg); color: var(--text); }
        .container { max-width: 860px; margin: 26px auto; padding: 0 16px 24px; }
        .head {
            margin-bottom: 16px;
            border-radius: 14px;
            padding: 18px 20px;
            background: linear-gradient(130deg, #1a3f8f 0%, #2f6be0 55%, #4d86f2 100%);
            color: #fff;
        }
        .head h1 { margin: 0 0 6px; font-size: 1.32rem; }
        .head p { margin: 0; opacity: .95; }
        .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 14px;
            padding: 18px;
            box-shadow: 0 12px 34px rgba(20, 33, 61, .08);
        }
        .summary {
            border: 1px dashed #c8d6f2;
            border-radius: 10px;
            background: #f8fbff;
            padding: 12px;
            margin-bottom: 14px;
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 10px;
        }
        .summary .label { color: var(--muted); font-size: .8rem; margin-bottom: 4px; }
        .summary .value { font-weight: 700; }
        .grid { display: grid; grid-template-columns: repeat(12, 1fr); gap: 10px; }
        .grid > div { min-width: 0; }
        .col-12 { grid-column: span 12; }
        .col-6 { grid-column: span 6; }
        .col-4 { grid-column: span 4; }
        label { display: block; margin-bottom: 6px; font-weight: 600; font-size: .9rem; }
        .form-control, .form-select {
            width: 100%;
            border: 1px solid #cfd8e6;
            border-radius: 8px;
            padding: 10px 11px;
            font: inherit;
            color: var(--text);
            background: #fff;
            min-height: calc(1.5em + .85rem + 2px);
        }
        textarea.form-control { min-height: 90px; resize: vertical; }
        .bs-select-dropdown {
            position: relative;
            width: 100%;
        }
        .bs-select-dropdown .bs-select-toggle {
            border: 1px solid #cfd8e6;
            border-radius: 8px;
            background: #fff;
            color: var(--text);
            min-height: calc(1.5em + .85rem + 2px);
        }
        .bs-select-dropdown .bs-select-toggle:focus,
        .bs-select-dropdown .bs-select-toggle.show {
            border-color: #7aa0f2;
            box-shadow: 0 0 0 .15rem rgba(47, 107, 224, .18);
            color: var(--text);
        }
        .bs-select-dropdown .dropdown-menu {
            width: 100%;
            max-height: 260px;
            overflow-y: auto;
            border-radius: 10px;
            border: 1px solid #d6deec;
            box-shadow: 0 12px 26px rgba(20, 33, 61, .14);
            padding: .35rem;
        }
        .bs-select-dropdown .dropdown-item {
            border-radius: 8px;
            font-size: .9rem;
            padding: .45rem .65rem;
        }
        .bs-select-dropdown .dropdown-item.active,
        .bs-select-dropdown .dropdown-item:active {
            background: #2f6be0;
        }
        .bs-select-dropdown .bs-select-toggle.is-invalid {
            border-color: #dc3545;
            box-shadow: 0 0 0 .15rem rgba(220, 53, 69, .15);
        }
        .bs-select-native {
            position: absolute !important;
            width: 1px !important;
            height: 1px !important;
            opacity: 0 !important;
            pointer-events: none !important;
        }
        .alert {
            border: 1px solid #ffd2d2;
            color: #8f2525;
            background: #fff1f1;
            border-radius: 10px;
            padding: 12px 14px;
            margin-bottom: 14px;
        }
        .required { color: #c52828; }
        .btn {
            border: 0;
            border-radius: 9px;
            padding: 11px 16px;
            font: inherit;
            font-weight: 600;
            cursor: pointer;
            margin-top: 14px;
        }
        .btn-primary { background: var(--accent); color: #fff; }
        .btn-primary:hover { background: #0b8d5b; }
        @media (max-width: 860px) {
            .col-6, .col-4 { grid-column: span 12; }
            .summary { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="head">
            <h1>Form Pembayaran Sewa</h1>
            <p>Silakan isi pembayaran untuk sewa aktif. Link ini hanya bisa dipakai satu kali.</p>
        </div>

        <form class="card" method="POST" action="{{ route('sewa-payment-registrations.store', $link->token) }}" enctype="multipart/form-data">
            @csrf

            @php
                $biayaBulanan = (float) ($billing['biaya_bulanan'] ?? 0);
                $hargaTierBulanan = (float) ($billing['harga_tier_bulanan'] ?? $biayaBulanan);
                $durasiBulan = (int) ($billing['durasi_bulan'] ?? 1);
                $subtotalHargaDasar = (float) ($billing['subtotal_harga_dasar'] ?? 0);
                $subtotalTagihan = (float) ($billing['subtotal'] ?? 0);
                $hematTagihan = (float) ($billing['diskon'] ?? 0);
                $totalSebelumPembulatan = (float) ($billing['total_sebelum_pembulatan'] ?? $billing['total'] ?? 0);
                $kelipatanPembulatan = (int) ($billing['pembulatan_kelipatan'] ?? 50000);
                $totalTagihan = (float) ($billing['total'] ?? 0);
            @endphp

            @if($errors->any())
                <div class="alert">
                    <strong>Data belum lengkap:</strong>
                    <ul style="margin: 8px 0 0 18px; padding: 0;">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="summary">
                <div>
                    <div class="label">Nama Penghuni</div>
                    <div class="value">{{ $link->sewa->penghuni->nama ?? '-' }}</div>
                </div>
                <div>
                    <div class="label">Nomor Kamar</div>
                    <div class="value">{{ $link->sewa->kamar->nomor ?? '-' }}</div>
                </div>
                <div>
                    <div class="label">Biaya/Bulan</div>
                    <div class="value">Rp {{ number_format($biayaBulanan, 0, ',', '.') }}</div>
                </div>
                <div>
                    <div class="label">Harga Tier/Bulan</div>
                    <div class="value">Rp {{ number_format($hargaTierBulanan, 0, ',', '.') }}</div>
                </div>
                <div>
                    <div class="label">Durasi Sewa</div>
                    <div class="value">{{ $durasiBulan }} bulan</div>
                </div>
                <div>
                    <div class="label">Total Tagihan</div>
                    <div class="value">Rp {{ number_format($totalTagihan, 0, ',', '.') }}</div>
                </div>
                @if($hematTagihan > 0)
                    <div>
                        <div class="label">Hemat dari Harga Dasar</div>
                        <div class="value">- Rp {{ number_format($hematTagihan, 0, ',', '.') }}</div>
                    </div>
                @endif
            </div>

            <div class="grid">
                <div class="col-12">
                    <small style="display:block;color:#5b6b82;margin:-2px 0 6px;">Rumus: (Rp {{ number_format($hargaTierBulanan, 0, ',', '.') }} x {{ $durasiBulan }} bulan) = Rp {{ number_format($subtotalTagihan, 0, ',', '.') }}, lalu dibulatkan ke kelipatan Rp {{ number_format($kelipatanPembulatan, 0, ',', '.') }} menjadi Rp {{ number_format($totalTagihan, 0, ',', '.') }}. Harga dasar tanpa tier: Rp {{ number_format($subtotalHargaDasar, 0, ',', '.') }}.</small>
                    <label>Metode <span class="required">*</span></label>
                    <select name="metode" required>
                        @foreach(['cash', 'transfer'] as $metode)
                            <option value="{{ $metode }}" @selected(old('metode', 'transfer') === $metode)>{{ ucfirst($metode) }}</option>
                        @endforeach
                    </select>
                </div>

                <input type="hidden" name="periode" value="{{ old('periode', now()->toDateString()) }}">
                <input type="hidden" name="tanggal_bayar" value="{{ old('tanggal_bayar', now()->toDateString()) }}">
                <input type="hidden" name="jumlah" value="{{ old('jumlah', $totalTagihan) }}">
                <input type="hidden" name="status" value="{{ old('status', 'lunas') }}">

                <div class="col-12">
                    <label>Bukti Pembayaran (jpg, png, pdf)</label>
                    <input type="file" name="bukti_pembayaran" accept=".jpg,.jpeg,.png,.pdf,image/*,application/pdf">
                </div>

                <div class="col-12">
                    <label>Keterangan</label>
                    <textarea name="keterangan">{{ old('keterangan') }}</textarea>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Kirim Pembayaran</button>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function applyBootstrapFormClasses() {
            document.querySelectorAll('input, select, textarea').forEach((el) => {
                if (el.tagName === 'SELECT') {
                    el.classList.add('form-select');
                    return;
                }

                if (el.tagName === 'TEXTAREA') {
                    el.classList.add('form-control');
                    return;
                }

                const type = (el.getAttribute('type') || 'text').toLowerCase();

                if (type === 'hidden' || type === 'submit' || type === 'button' || type === 'reset') {
                    return;
                }

                if (type === 'checkbox' || type === 'radio') {
                    el.classList.add('form-check-input');
                    return;
                }

                if (type === 'range') {
                    el.classList.add('form-range');
                    return;
                }

                el.classList.add('form-control');
            });
        }

        function setupBootstrapDropdownSelects() {
            document.querySelectorAll('select').forEach((select) => {
                if (select.dataset.dropdownified === '1') {
                    return;
                }

                if (select.multiple || Number(select.getAttribute('size') || 1) > 1) {
                    return;
                }

                const wrapper = document.createElement('div');
                wrapper.className = 'dropdown bs-select-dropdown';

                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'btn bs-select-toggle dropdown-toggle w-100 text-start d-flex justify-content-between align-items-center';
                button.setAttribute('data-bs-toggle', 'dropdown');
                button.setAttribute('aria-expanded', 'false');

                const menu = document.createElement('ul');
                menu.className = 'dropdown-menu';

                select.parentNode.insertBefore(wrapper, select);
                wrapper.appendChild(button);
                wrapper.appendChild(menu);
                wrapper.appendChild(select);

                select.classList.add('bs-select-native');
                select.dataset.dropdownified = '1';

                const getPlaceholder = () => {
                    const selected = select.options[select.selectedIndex];
                    if (selected && selected.value !== '') {
                        return selected.text;
                    }
                    const emptyOption = Array.from(select.options).find((opt) => opt.value === '');
                    return (emptyOption && emptyOption.text) || 'Pilih opsi';
                };

                const renderMenu = () => {
                    const currentValue = select.value;
                    const items = [];

                    Array.from(select.options).forEach((opt) => {
                        if (opt.value === '' || opt.hidden) {
                            return;
                        }

                        const active = currentValue === opt.value ? ' active' : '';
                        const disabled = opt.disabled ? ' disabled' : '';
                        items.push(
                            '<li><button type="button" class="dropdown-item' + active + disabled + '" data-value="' + opt.value.replace(/"/g, '&quot;') + '"' + (opt.disabled ? ' disabled' : '') + '>' + opt.text + '</button></li>'
                        );
                    });

                    if (!items.length) {
                        items.push('<li><span class="dropdown-item-text text-body-secondary">Tidak ada opsi</span></li>');
                    }

                    menu.innerHTML = items.join('');
                    button.textContent = getPlaceholder();

                    menu.querySelectorAll('button[data-value]').forEach((itemBtn) => {
                        itemBtn.addEventListener('click', function () {
                            select.value = this.getAttribute('data-value');
                            select.dispatchEvent(new Event('change', { bubbles: true }));
                            button.classList.remove('is-invalid');
                            renderMenu();
                        });
                    });
                };

                const observer = new MutationObserver(() => {
                    renderMenu();
                });
                observer.observe(select, { childList: true, subtree: true, attributes: true, attributeFilter: ['selected', 'disabled', 'hidden', 'value'] });

                select.addEventListener('change', () => {
                    button.classList.remove('is-invalid');
                    renderMenu();
                });

                if (select.form) {
                    select.form.addEventListener('submit', (event) => {
                        if (select.required && !select.value) {
                            event.preventDefault();
                            button.classList.add('is-invalid');
                        }
                    });
                }

                renderMenu();
            });
        }

        applyBootstrapFormClasses();
        setupBootstrapDropdownSelects();
    </script>
</body>
</html>
