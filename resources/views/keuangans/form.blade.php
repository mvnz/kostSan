@php($pageTitle = $keuangan->exists ? 'Edit Keuangan' : 'Tambah Keuangan')
@php($pageSubtitle = 'Form pencatatan kas operasional.')
@extends('layouts.app')

@section('content')
<style>
	.finance-form-shell {
		border: 1px solid #e7ecf6;
		box-shadow: 0 14px 30px rgba(23, 39, 71, .08);
		overflow: hidden;
	}
	.finance-form-head {
		background: linear-gradient(135deg, #eef3ff 0%, #f3f9ff 58%, #edf8f3 100%);
		border-bottom: 1px solid #e6edf8;
		padding: 1rem 1.25rem;
	}
	.finance-form-head h5 {
		margin: 0;
		color: #2d3e55;
		font-weight: 700;
	}
	.finance-form-head p {
		margin: .3rem 0 0;
		color: #6a7d96;
		font-size: .86rem;
	}
	.finance-section {
		border: 1px solid #e8edf7;
		border-radius: .9rem;
		padding: 1rem;
		background: #fff;
	}
	.finance-section-title {
		font-weight: 700;
		color: #405673;
		margin-bottom: .8rem;
		font-size: .92rem;
		text-transform: uppercase;
		letter-spacing: .45px;
	}
	#kategori_dropdown_button {
		background: #fff;
		border: 1px solid #dfe7f5;
		color: #4a5f7b;
		font-weight: 500;
		padding: .54rem .75rem;
	}
	#kategori_dropdown_button:focus,
	#kategori_dropdown_button.show {
		border-color: #8fb0f0;
		box-shadow: 0 0 0 .2rem rgba(86, 130, 234, .15);
	}
	#kategori_dropdown_menu {
		max-height: 220px;
		overflow-y: auto;
		border: 1px solid #dfe7f5;
		box-shadow: 0 10px 20px rgba(17, 35, 74, .12);
	}
	#kategori_dropdown_menu .dropdown-item {
		font-size: .9rem;
		padding: .5rem .85rem;
	}
	#kategori_dropdown_menu .dropdown-item.active,
	#kategori_dropdown_menu .dropdown-item:active {
		background: #2e6fd8;
	}
	#kategori_hint {
		font-size: .78rem;
	}
	.evidence-box {
		border: 1px dashed #c8d8f5;
		border-radius: .8rem;
		padding: .9rem;
		background: #f9fbff;
	}
	.finance-actions {
		border-top: 1px solid #edf2fb;
		padding-top: 1rem;
	}
	@media (max-width: 576px) {
		.finance-form-head {
			padding: .9rem 1rem;
		}
	}
</style>

<div class="page-head d-flex align-items-center gap-3">
    <div class="page-head-icon"><i class="bx bx-wallet"></i></div>
    <div>
        <h2>{{ $keuangan->exists ? 'Edit Keuangan' : 'Tambah Keuangan' }}</h2>
        <p>Catat pemasukan atau pengeluaran operasional.</p>
    </div>
</div>

<div class="card finance-form-shell">
	<div class="finance-form-head d-flex justify-content-between align-items-start flex-wrap gap-2">
		<div>
			<h5>{{ $keuangan->exists ? 'Perbarui Transaksi' : 'Transaksi Baru' }}</h5>
			<p>Isi data transaksi operasional dan lampirkan bukti bila tersedia.</p>
		</div>
		<span class="badge bg-label-primary px-3 py-2">
			{{ $keuangan->exists ? 'Mode Edit' : 'Mode Tambah' }}
		</span>
	</div>

	<div class="card-body p-4">
		<form method="POST" action="{{ $keuangan->exists ? route('keuangans.update', $keuangan) : route('keuangans.store') }}" enctype="multipart/form-data">
			@csrf
			@if($keuangan->exists)
				@method('PUT')
			@endif

			<div class="finance-section mb-3">
				<div class="finance-section-title">Data Utama</div>
				<div class="row">
					<div class="col-md-4 mb-3">
						<label class="form-label">Tanggal</label>
						<input type="date" name="tanggal" class="form-control" value="{{ old('tanggal', optional($keuangan->tanggal)->format('Y-m-d') ?? now()->format('Y-m-d')) }}" required>
					</div>
					<div class="col-md-4 mb-3">
						<label class="form-label">Jenis</label>
						<select name="jenis" id="jenis_keuangan" class="form-select" required>
							@foreach(['pemasukan','pengeluaran'] as $jenis)
								<option value="{{ $jenis }}" @selected(old('jenis', $keuangan->jenis ?? 'pemasukan') === $jenis)>
									{{ ucfirst($jenis) }}
								</option>
							@endforeach
						</select>
					</div>
					<div class="col-md-4 mb-3">
						<label class="form-label">Jumlah</label>
						<div class="input-group">
							<span class="input-group-text">Rp</span>
							<input type="number" step="1000" min="0" name="jumlah" class="form-control" value="{{ old('jumlah', $keuangan->jumlah) }}" required>
						</div>
						<small class="text-body-secondary">Masukkan nominal bulat, contoh: 150000</small>
					</div>
				</div>
			</div>

			<div class="finance-section mb-3">
				<div class="finance-section-title">Kategori Dan Catatan</div>
				<div class="mb-2">
					<label class="form-label">Kategori</label>
					<div class="dropdown">
						<button
							class="btn dropdown-toggle w-100 text-start d-flex justify-content-between align-items-center"
							type="button"
							id="kategori_dropdown_button"
							data-bs-toggle="dropdown"
							aria-expanded="false"
						>
							Pilih kategori
						</button>
						<ul class="dropdown-menu w-100" id="kategori_dropdown_menu"></ul>
					</div>
					<input type="hidden" name="kategori" id="kategori_keuangan" value="{{ old('kategori', $keuangan->kategori) }}" required>
					<small class="text-body-secondary" id="kategori_hint">Pilih kategori dari daftar Bootstrap dropdown.</small>
				</div>

				<div class="mb-0">
					<label class="form-label">Deskripsi</label>
					<textarea name="deskripsi" class="form-control" rows="3">{{ old('deskripsi', $keuangan->deskripsi) }}</textarea>
				</div>
			</div>

			<div class="finance-section mb-3">
				<div class="finance-section-title">Bukti Transaksi</div>
				<div class="evidence-box">
					<label class="form-label">Upload Bukti Transaksi (jpg, png, pdf)</label>
					<input type="file" name="bukti" class="form-control" accept=".jpg,.jpeg,.png,.pdf,image/*,application/pdf">
					<small class="text-body-secondary">Opsional. Maksimal 4MB.</small>

					@if($keuangan->exists && $keuangan->bukti_path)
						<div class="mt-2 d-flex align-items-center gap-2 flex-wrap">
							<a href="{{ route('secure-files.show', ['path' => $keuangan->bukti_path]) }}" target="_blank" class="btn btn-sm btn-outline-info">
								<i class="bx bx-file me-1"></i>Lihat Bukti Saat Ini
							</a>
							<label class="form-check m-0">
								<input type="hidden" name="hapus_bukti" value="0">
								<input class="form-check-input" type="checkbox" name="hapus_bukti" value="1">
								<span class="form-check-label">Hapus bukti lama</span>
							</label>
						</div>
					@endif
				</div>
			</div>

			<div class="d-flex gap-2 finance-actions">
				<button type="submit" class="btn btn-primary">
					<i class="bx bx-save me-1"></i>Simpan
				</button>
				<a href="{{ route('keuangans.index') }}" class="btn btn-secondary">
					<i class="bx bx-arrow-back me-1"></i>Kembali
				</a>
			</div>
		</form>
	</div>
</div>
@endsection

@section('scripts')
<script>
	const kategoriPresets = @json($kategoriPresets ?? []);
	const selectedKategori = @json(old('kategori', $keuangan->kategori));

	function renderKategoriPreset() {
		const jenis = document.getElementById('jenis_keuangan').value;
		const kategoriInput = document.getElementById('kategori_keuangan');
		const kategoriButton = document.getElementById('kategori_dropdown_button');
		const kategoriMenu = document.getElementById('kategori_dropdown_menu');
		const categories = kategoriPresets[jenis] || [];
		const currentValue = kategoriInput.value || selectedKategori || '';

		const menuItems = [];

		categories.forEach((name) => {
			const isActive = currentValue === name ? ' active' : '';
			menuItems.push('<li><button type="button" class="dropdown-item' + isActive + '" data-kategori="' + name + '">' + name + '</button></li>');
		});

		if (currentValue && !categories.includes(currentValue)) {
			menuItems.push('<li><button type="button" class="dropdown-item active" data-kategori="' + currentValue + '">' + currentValue + ' (saat ini)</button></li>');
		}

		if (!menuItems.length) {
			menuItems.push('<li><span class="dropdown-item-text text-body-secondary">Tidak ada kategori</span></li>');
		}

		kategoriMenu.innerHTML = menuItems.join('');
		kategoriButton.textContent = currentValue || 'Pilih kategori';

		kategoriMenu.querySelectorAll('button[data-kategori]').forEach((btn) => {
			btn.addEventListener('click', function () {
				const value = this.getAttribute('data-kategori');
				kategoriInput.value = value;
				kategoriButton.textContent = value;
				renderKategoriPreset();
			});
		});
	}

	document.addEventListener('DOMContentLoaded', function () {
		document.getElementById('jenis_keuangan').addEventListener('change', renderKategoriPreset);
		renderKategoriPreset();
	});
</script>
@endsection
