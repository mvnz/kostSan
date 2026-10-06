<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pendaftaran Penghuni Baru</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --bg: #f4f7fb;
            --card: #ffffff;
            --line: #dbe3ef;
            --text: #1d2736;
            --muted: #5b6b82;
            --primary: #3f66ff;
            --primary-soft: #ecf1ff;
        }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: "Segoe UI", Tahoma, sans-serif; background: var(--bg); color: var(--text); }
        .container { max-width: 980px; margin: 28px auto; padding: 0 16px 24px; }
        .top {
            margin-bottom: 16px;
            background: linear-gradient(130deg, #16346f 0%, #2855af 45%, #3f66ff 100%);
            color: #fff;
            border-radius: 14px;
            padding: 18px 20px;
        }
        .top h1 { margin: 0 0 6px; font-size: 1.38rem; }
        .top p { margin: 0; opacity: .95; }
        .card { background: var(--card); border: 1px solid var(--line); border-radius: 14px; padding: 22px; box-shadow: 0 10px 30px rgba(20, 33, 61, .08); }
        .section { border: 1px solid #e8edf6; border-radius: 10px; padding: 18px 18px 10px; margin-bottom: 18px; }
        .section h2 {
            margin: 0 0 16px;
            font-size: 1rem;
            background: var(--primary-soft);
            color: #233c8c;
            border: 1px solid #d9e2ff;
            border-radius: 8px;
            padding: 8px 10px;
        }
        .frow { display: grid; grid-template-columns: repeat(12, 1fr); gap: 14px; }
        .frow > div { min-width: 0; }
        .fcol-12 { grid-column: span 12; }
        .fcol-8 { grid-column: span 8; }
        .fcol-6 { grid-column: span 6; }
        .fcol-4 { grid-column: span 4; }
        .fcol-3 { grid-column: span 3; }
        .fcol-2 { grid-column: span 2; }
        label { display: block; font-weight: 600; margin-bottom: 6px; font-size: .9rem; }
        .form-control, .form-select {
            width: 100%; border: 1px solid #cfd8e6; border-radius: 8px;
            padding: 10px 11px; font: inherit; color: var(--text); background: #fff;
            min-height: calc(1.5em + .85rem + 2px);
        }
        textarea.form-control { min-height: 74px; resize: vertical; }
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
            border-color: #86a8ff;
            box-shadow: 0 0 0 .15rem rgba(63, 102, 255, .18);
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
        .bs-select-dropdown .bs-select-search-wrap {
            position: sticky;
            top: -.35rem;
            z-index: 2;
            background: #fff;
            padding-bottom: .35rem;
        }
        .bs-select-dropdown .bs-select-search {
            min-height: calc(1.5em + .5rem + 2px);
            font-size: .82rem;
        }
        .bs-select-dropdown .dropdown-item {
            border-radius: 8px;
            font-size: .9rem;
            padding: .45rem .65rem;
        }
        .bs-select-dropdown .dropdown-item.active,
        .bs-select-dropdown .dropdown-item:active {
            background: #3f66ff;
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
        .radio-group { display: flex; gap: 14px; padding-top: 4px; }
        .radio-inline { display: inline-flex; align-items: center; gap: 6px; color: var(--muted); }
        .radio-inline input { width: auto; }
        .help { color: var(--muted); font-size: .8rem; margin-top: 4px; }
        .req { color: #c52828; }
        .alert {
            border: 1px solid #ffd2d2; color: #8f2525; background: #fff1f1;
            border-radius: 10px; padding: 12px 14px; margin-bottom: 14px;
        }
        .info-card {
            border: 1px solid #cdd9ff;
            border-radius: 10px;
            padding: 10px 12px;
            background: #f3f6ff;
            color: #243774;
            margin-bottom: 14px;
        }
        .actions { margin-top: 16px; display: flex; gap: 10px; }
        .btn {
            border: 0; border-radius: 9px; padding: 11px 16px; font: inherit;
            font-weight: 600; cursor: pointer;
        }
        .btn-primary { background: var(--primary); color: #fff; }
        .btn-primary:hover { background: #3357e6; }
        @media (max-width: 860px) {
            .fcol-6, .fcol-4, .fcol-3, .fcol-2, .fcol-8 { grid-column: span 12; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="top">
            <h1>Formulir Penghuni Baru Kost Sanwa</h1>
            <p>Silakan isi data dengan lengkap. Link ini hanya dapat digunakan satu kali.</p>
        </div>

        <form class="card" method="POST" action="{{ route('penghuni-registrations.store', $token) }}" enctype="multipart/form-data">
            @csrf

            @if($link?->kamar)
                <div class="info-card">
                    Pendaftaran ini terhubung ke <strong>Kamar {{ $link->kamar->nomor }}</strong>.
                    Setelah form dikirim, data sewa kamar akan dibuat otomatis.
                </div>
            @endif

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

            @php($jenisKendaraan = old('jenis_kendaraan', 'tidak_ada'))

            <section class="section">
                <h2>1. Data Penghuni</h2>
                <div class="frow">
                    <div class="fcol-6">
                        <label>No. Kamar</label>
                        <input type="text" name="nomor_kamar" value="{{ old('nomor_kamar', $link?->kamar?->nomor) }}" placeholder="Contoh: 205" @if($link?->kamar) readonly @endif>
                    </div>
                    <div class="fcol-6">
                        <label>Jumlah kunci yang diterima</label>
                        <input type="number" name="jumlah_kunci" min="0" value="{{ old('jumlah_kunci') }}" placeholder="0">
                    </div>
                    <div class="fcol-6">
                        <label>Nama lengkap <span class="req">*</span></label>
                        <input type="text" name="nama" value="{{ old('nama') }}" required>
                    </div>
                    <div class="fcol-3">
                        <label>Tempat lahir</label>
                        <input type="text" name="tempat_lahir" value="{{ old('tempat_lahir') }}">
                    </div>
                    <div class="fcol-3">
                        <label>Tanggal lahir</label>
                        <input type="date" name="tanggal_lahir" value="{{ old('tanggal_lahir') }}">
                    </div>
                    <div class="fcol-4">
                        <label>NIK/KTP</label>
                        <input type="text" name="nik" value="{{ old('nik') }}">
                    </div>
                    <div class="fcol-4">
                        <label>Nomor HP/WhatsApp <span class="req">*</span></label>
                        <input type="text" name="telepon" value="{{ old('telepon') }}" required>
                    </div>
                    <div class="fcol-4">
                        <label>Email</label>
                        <input type="email" name="email" value="{{ old('email') }}">
                    </div>
                    <div class="fcol-6">
                        <label>Alamat sesuai KTP</label>
                        <textarea name="alamat_ktp">{{ old('alamat_ktp') }}</textarea>
                    </div>
                    <div class="fcol-6">
                        <label>Alamat domisili sekarang</label>
                        <textarea name="alamat">{{ old('alamat') }}</textarea>
                    </div>
                    <div class="fcol-4">
                        <label>Pekerjaan/instansi/kampus</label>
                        <input type="text" name="pekerjaan" value="{{ old('pekerjaan') }}">
                    </div>
                    <div class="fcol-8">
                        <label>Tanggal mulai tinggal</label>
                        <input type="date" name="tanggal_mulai_tinggal" value="{{ old('tanggal_mulai_tinggal') }}">
                    </div>
                </div>
            </section>

            <section class="section">
                <h2>2. Kontak Darurat</h2>
                <div class="frow">
                    <div class="fcol-4">
                        <label>Nama</label>
                        <input type="text" name="kontak_darurat_nama" value="{{ old('kontak_darurat_nama') }}">
                    </div>
                    <div class="fcol-4">
                        <label>Hubungan</label>
                        <input type="text" name="kontak_darurat_hubungan" value="{{ old('kontak_darurat_hubungan') }}">
                    </div>
                    <div class="fcol-4">
                        <label>Nomor HP/WhatsApp</label>
                        <input type="text" name="kontak_darurat_telepon" value="{{ old('kontak_darurat_telepon') }}">
                    </div>
                </div>
            </section>

            <section class="section">
                <h2>3. Data Kendaraan</h2>
                <div class="frow">
                    <div class="fcol-4">
                        <label>Jenis kendaraan</label>
                        <div class="radio-group">
                            <label class="radio-inline"><input type="radio" name="jenis_kendaraan" value="tidak_ada" {{ $jenisKendaraan === 'tidak_ada' ? 'checked' : '' }}> Tidak ada</label>
                            <label class="radio-inline"><input type="radio" name="jenis_kendaraan" value="motor" {{ $jenisKendaraan === 'motor' ? 'checked' : '' }}> Motor</label>
                        </div>
                    </div>
                    <div class="fcol-4">
                        <label>Merek/tipe</label>
                        <input type="text" class="kendaraan-detail" name="kendaraan_merek_tipe" value="{{ old('kendaraan_merek_tipe') }}">
                    </div>
                    <div class="fcol-2">
                        <label>Warna</label>
                        <input type="text" class="kendaraan-detail" name="kendaraan_warna" value="{{ old('kendaraan_warna') }}">
                    </div>
                    <div class="fcol-2">
                        <label>Nomor polisi</label>
                        <input type="text" class="kendaraan-detail" name="kendaraan_nomor_polisi" value="{{ old('kendaraan_nomor_polisi') }}">
                    </div>
                </div>
            </section>

            <section class="section">
                <h2>4. Data Pembayaran</h2>
                <div class="frow">
                    <div class="fcol-12">
                        <label>Waktu Sewa <span class="req">*</span></label>
                        <select name="waktu_sewa_bulan" required>
                            @foreach([1, 3, 6, 12] as $durasi)
                                <option value="{{ $durasi }}" @selected((int) old('waktu_sewa_bulan', 1) === $durasi)>{{ $durasi }} bulan</option>
                            @endforeach
                        </select>
                        <div class="help">Durasi ini dipakai sistem untuk menghitung akhir masa sewa otomatis.</div>
                    </div>
                </div>
            </section>

            <section class="section">
                <h2>5. Upload Dokumen</h2>
                <div class="frow">
                    <div class="fcol-6">
                        <label>Foto KTP <span class="req">*</span></label>
                        <input type="file" name="foto_ktp" accept="image/*" required>
                        <div class="help">Maksimal 4 MB.</div>
                    </div>
                    <div class="fcol-6">
                        <label>Foto Selfie <span class="req">*</span></label>
                        <input type="file" name="foto_selfie" accept="image/*" required>
                        <div class="help">Maksimal 4 MB.</div>
                    </div>
                </div>
            </section>

            <div class="actions">
                <button type="submit" class="btn btn-primary">Kirim Pendaftaran</button>
            </div>
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

                if (select.closest('.input-group')) {
                    return;
                }

                const wrapper = document.createElement('div');
                wrapper.className = 'dropdown bs-select-dropdown';

                if (select.getAttribute('style')) {
                    wrapper.setAttribute('style', select.getAttribute('style'));
                }

                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'btn bs-select-toggle dropdown-toggle w-100 text-start d-flex justify-content-between align-items-center';
                button.setAttribute('data-bs-toggle', 'dropdown');
                button.setAttribute('aria-expanded', 'false');
                button.setAttribute('data-bs-auto-close', 'outside');

                const menu = document.createElement('ul');
                menu.className = 'dropdown-menu';

                select.parentNode.insertBefore(wrapper, select);
                wrapper.appendChild(button);
                wrapper.appendChild(menu);
                wrapper.appendChild(select);

                select.classList.add('bs-select-native');
                select.dataset.dropdownified = '1';

                const escapeHtml = (str) => {
                    return String(str)
                        .replace(/&/g, '&amp;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;')
                        .replace(/"/g, '&quot;')
                        .replace(/'/g, '&#039;');
                };

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
                    const selectableOptions = Array.from(select.options).filter((opt) => opt.value !== '' && !opt.hidden);
                    const useSearch = selectableOptions.length >= 8;

                    if (useSearch) {
                        items.push(
                            '<li class="bs-select-search-wrap"><input type="search" class="form-control form-control-sm bs-select-search" placeholder="Cari opsi..."></li>' +
                            '<li><hr class="dropdown-divider my-1"></li>'
                        );
                    }

                    Array.from(select.options).forEach((opt) => {
                        if (opt.value === '' || opt.hidden) {
                            return;
                        }

                        const active = currentValue === opt.value ? ' active' : '';
                        const disabled = opt.disabled ? ' disabled' : '';
                        items.push(
                            '<li><button type="button" class="dropdown-item' + active + disabled + '" data-value="' + escapeHtml(opt.value) + '" data-label="' + escapeHtml(opt.text) + '"' + (opt.disabled ? ' disabled' : '') + '>' + escapeHtml(opt.text) + '</button></li>'
                        );
                    });

                    if (!items.length) {
                        items.push('<li><span class="dropdown-item-text text-body-secondary">Tidak ada opsi</span></li>');
                    }

                    if (useSearch) {
                        items.push('<li class="d-none bs-select-empty-state"><span class="dropdown-item-text text-body-secondary">Opsi tidak ditemukan</span></li>');
                    }

                    menu.innerHTML = items.join('');
                    button.textContent = getPlaceholder();

                    menu.querySelectorAll('button[data-value]').forEach((itemBtn) => {
                        itemBtn.addEventListener('click', function () {
                            select.value = this.getAttribute('data-value');
                            select.dispatchEvent(new Event('change', { bubbles: true }));
                            button.classList.remove('is-invalid');
                            bootstrap.Dropdown.getOrCreateInstance(button).hide();
                            renderMenu();
                        });
                    });

                    const searchInput = menu.querySelector('.bs-select-search');
                    const emptyState = menu.querySelector('.bs-select-empty-state');
                    if (searchInput) {
                        searchInput.addEventListener('click', (event) => {
                            event.stopPropagation();
                        });
                        searchInput.addEventListener('input', function () {
                            const keyword = this.value.trim().toLowerCase();
                            let visibleCount = 0;

                            menu.querySelectorAll('button[data-label]').forEach((optBtn) => {
                                const label = optBtn.getAttribute('data-label').toLowerCase();
                                const row = optBtn.closest('li');
                                const visible = label.includes(keyword);
                                row.classList.toggle('d-none', !visible);
                                if (visible) {
                                    visibleCount += 1;
                                }
                            });

                            if (emptyState) {
                                emptyState.classList.toggle('d-none', visibleCount > 0);
                            }
                        });
                    }
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

        function syncKendaraanFields() {
            const jenis = document.querySelector('input[name="jenis_kendaraan"]:checked')?.value || 'tidak_ada';
            const disabled = jenis !== 'motor';

            document.querySelectorAll('.kendaraan-detail').forEach((input) => {
                input.disabled = disabled;
                if (disabled) {
                    input.value = '';
                }
            });
        }

        document.querySelectorAll('input[name="jenis_kendaraan"]').forEach((radio) => {
            radio.addEventListener('change', syncKendaraanFields);
        });

        applyBootstrapFormClasses();
        setupBootstrapDropdownSelects();
        syncKendaraanFields();
    </script>
</body>
</html>
