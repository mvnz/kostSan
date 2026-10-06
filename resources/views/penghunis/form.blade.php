@php
$pageTitle = $penghuni->exists ? 'Edit Penghuni' : 'Tambah Penghuni';
$pageSubtitle = 'Form data penghuni kost.';
@endphp
@extends('layouts.app')

@section('content')
<div class="page-head d-flex align-items-center gap-3">
    <div class="page-head-icon"><i class="bx bx-user"></i></div>
    <div>
        <h2>{{ $penghuni->exists ? 'Edit Penghuni' : 'Tambah Penghuni Baru' }}</h2>
        <p>Isi form secara bertahap per bagian agar data lebih rapi dan mudah dicek.</p>
    </div>
</div>

@php($jenisKendaraan = old('jenis_kendaraan', $penghuni->jenis_kendaraan ?? 'tidak_ada'))

<form method="POST" action="{{ $penghuni->exists ? route('penghunis.update', $penghuni) : route('penghunis.store') }}" id="form-penghuni">
	@csrf
	@if($penghuni->exists)
		@method('PUT')
	@endif

	@if($errors->any())
		<div class="alert alert-danger mb-4" role="alert">
			<strong>Periksa kembali data berikut:</strong>
			<ul class="mb-0 mt-2 ps-3">
				@foreach($errors->all() as $error)
					<li>{{ $error }}</li>
				@endforeach
			</ul>
		</div>
	@endif

	<div class="row g-4">
		<div class="col-lg-3">
			<div class="card sticky-top section-nav-card">
				<div class="card-body">
					<h6 class="mb-3">Navigasi Form</h6>
					<div class="d-grid gap-2">
						<button type="button" class="btn btn-label-primary text-start section-nav-btn" data-target="#section-identitas">1. Data Penghuni</button>
						<button type="button" class="btn btn-label-primary text-start section-nav-btn" data-target="#section-darurat">2. Kontak Darurat</button>
						<button type="button" class="btn btn-label-primary text-start section-nav-btn" data-target="#section-kendaraan">3. Data Kendaraan</button>
						<button type="button" class="btn btn-label-primary text-start section-nav-btn" data-target="#section-pembayaran">4. Data Pembayaran</button>
					</div>
					<hr>
					<small class="text-body-secondary d-block mb-2">Field wajib:</small>
					<ul class="small text-body-secondary ps-3 mb-0">
						<li>Nama Lengkap</li>
						<li>Nomor HP/WhatsApp</li>
					</ul>
				</div>
			</div>
		</div>

		<div class="col-lg-9">
			<div class="accordion" id="accordionPenghuniForm">
				<div class="accordion-item section-block" id="section-identitas">
					<h2 class="accordion-header" id="heading-identitas">
						<button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-identitas" aria-expanded="true" aria-controls="collapse-identitas">
							1. Data Penghuni
						</button>
					</h2>
					<div id="collapse-identitas" class="accordion-collapse collapse show" aria-labelledby="heading-identitas" data-bs-parent="#accordionPenghuniForm">
						<div class="accordion-body">
							<div class="row">
								<div class="col-md-6 mb-3">
									<label class="form-label">No. Kamar</label>
									<input type="text" name="nomor_kamar" class="form-control" value="{{ old('nomor_kamar', $penghuni->nomor_kamar) }}" placeholder="Contoh: 205">
								</div>
								<div class="col-md-6 mb-3">
									<label class="form-label">Jumlah Kunci Diterima</label>
									<input type="number" name="jumlah_kunci" class="form-control" min="0" value="{{ old('jumlah_kunci', $penghuni->jumlah_kunci) }}" placeholder="0">
								</div>
							</div>
							<div class="row">
								<div class="col-md-6 mb-3">
									<label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
									<input type="text" name="nama" class="form-control" value="{{ old('nama', $penghuni->nama) }}" required>
								</div>
								<div class="col-md-3 mb-3">
									<label class="form-label">Tempat Lahir</label>
									<input type="text" name="tempat_lahir" class="form-control" value="{{ old('tempat_lahir', $penghuni->tempat_lahir) }}">
								</div>
								<div class="col-md-3 mb-3">
									<label class="form-label">Tanggal Lahir</label>
									<input type="date" name="tanggal_lahir" class="form-control" value="{{ old('tanggal_lahir', optional($penghuni->tanggal_lahir)->format('Y-m-d')) }}">
								</div>
							</div>
							<div class="row">
								<div class="col-md-4 mb-3">
									<label class="form-label">NIK/KTP</label>
									<input type="text" name="nik" class="form-control" value="{{ old('nik', $penghuni->nik) }}">
								</div>
								<div class="col-md-4 mb-3">
									<label class="form-label">Nomor HP/WhatsApp <span class="text-danger">*</span></label>
									<input type="text" name="telepon" class="form-control" value="{{ old('telepon', $penghuni->telepon) }}" required>
								</div>
								<div class="col-md-4 mb-3">
									<label class="form-label">Email</label>
									<input type="email" name="email" class="form-control" value="{{ old('email', $penghuni->email) }}">
								</div>
							</div>
							<div class="row">
								<div class="col-md-6 mb-3">
									<label class="form-label">Alamat Sesuai KTP</label>
									<textarea name="alamat_ktp" class="form-control" rows="2">{{ old('alamat_ktp', $penghuni->alamat_ktp) }}</textarea>
								</div>
								<div class="col-md-6 mb-3">
									<label class="form-label">Alamat Domisili Sekarang</label>
									<textarea name="alamat" class="form-control" rows="2">{{ old('alamat', $penghuni->alamat) }}</textarea>
								</div>
							</div>
							<div class="row">
								<div class="col-md-4 mb-3">
									<label class="form-label">Pekerjaan/Instansi/Kampus</label>
									<input type="text" name="pekerjaan" class="form-control" value="{{ old('pekerjaan', $penghuni->pekerjaan) }}">
								</div>
								<div class="col-md-4 mb-3">
									<label class="form-label">Tanggal Mulai Tinggal</label>
									<input type="date" name="tanggal_mulai_tinggal" class="form-control" value="{{ old('tanggal_mulai_tinggal', optional($penghuni->tanggal_mulai_tinggal)->format('Y-m-d')) }}">
								</div>
								<div class="col-md-4 mb-0">
									<label class="form-label">Lama Sewa (bulan)</label>
									<input type="number" name="lama_sewa_bulan" min="1" class="form-control" value="{{ old('lama_sewa_bulan', $penghuni->lama_sewa_bulan) }}" placeholder="Contoh: 6">
								</div>
							</div>
						</div>
					</div>
				</div>

				<div class="accordion-item section-block" id="section-darurat">
					<h2 class="accordion-header" id="heading-darurat">
						<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-darurat" aria-expanded="false" aria-controls="collapse-darurat">
							2. Kontak Darurat
						</button>
					</h2>
					<div id="collapse-darurat" class="accordion-collapse collapse" aria-labelledby="heading-darurat" data-bs-parent="#accordionPenghuniForm">
						<div class="accordion-body">
							<div class="row">
								<div class="col-md-4 mb-3">
									<label class="form-label">Nama</label>
									<input type="text" name="kontak_darurat_nama" class="form-control" value="{{ old('kontak_darurat_nama', $penghuni->kontak_darurat_nama) }}">
								</div>
								<div class="col-md-4 mb-3">
									<label class="form-label">Hubungan</label>
									<input type="text" name="kontak_darurat_hubungan" class="form-control" value="{{ old('kontak_darurat_hubungan', $penghuni->kontak_darurat_hubungan) }}" placeholder="Contoh: Orang tua / Saudara">
								</div>
								<div class="col-md-4 mb-0">
									<label class="form-label">Nomor HP/WhatsApp</label>
									<input type="text" name="kontak_darurat_telepon" class="form-control" value="{{ old('kontak_darurat_telepon', $penghuni->kontak_darurat_telepon) }}">
								</div>
							</div>
						</div>
					</div>
				</div>

				<div class="accordion-item section-block" id="section-kendaraan">
					<h2 class="accordion-header" id="heading-kendaraan">
						<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-kendaraan" aria-expanded="false" aria-controls="collapse-kendaraan">
							3. Data Kendaraan
						</button>
					</h2>
					<div id="collapse-kendaraan" class="accordion-collapse collapse" aria-labelledby="heading-kendaraan" data-bs-parent="#accordionPenghuniForm">
						<div class="accordion-body">
							<div class="row">
								<div class="col-md-4 mb-3">
									<label class="form-label">Jenis Kendaraan</label>
									<div class="d-flex align-items-center gap-4 mt-2">
										<div class="form-check">
											<input class="form-check-input" type="radio" name="jenis_kendaraan" id="kendaraan_tidak_ada" value="tidak_ada" {{ $jenisKendaraan === 'tidak_ada' ? 'checked' : '' }}>
											<label class="form-check-label" for="kendaraan_tidak_ada">Tidak ada</label>
										</div>
										<div class="form-check">
											<input class="form-check-input" type="radio" name="jenis_kendaraan" id="kendaraan_motor" value="motor" {{ $jenisKendaraan === 'motor' ? 'checked' : '' }}>
											<label class="form-check-label" for="kendaraan_motor">Motor</label>
										</div>
									</div>
								</div>
								<div class="col-md-4 mb-3">
									<label class="form-label">Merek/Tipe</label>
									<input type="text" name="kendaraan_merek_tipe" class="form-control kendaraan-detail" value="{{ old('kendaraan_merek_tipe', $penghuni->kendaraan_merek_tipe) }}">
								</div>
								<div class="col-md-2 mb-3">
									<label class="form-label">Warna</label>
									<input type="text" name="kendaraan_warna" class="form-control kendaraan-detail" value="{{ old('kendaraan_warna', $penghuni->kendaraan_warna) }}">
								</div>
								<div class="col-md-2 mb-0">
									<label class="form-label">Nomor Polisi</label>
									<input type="text" name="kendaraan_nomor_polisi" class="form-control kendaraan-detail" value="{{ old('kendaraan_nomor_polisi', $penghuni->kendaraan_nomor_polisi) }}">
								</div>
							</div>
						</div>
					</div>
				</div>

				<div class="accordion-item section-block" id="section-pembayaran">
					<h2 class="accordion-header" id="heading-pembayaran">
						<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-pembayaran" aria-expanded="false" aria-controls="collapse-pembayaran">
							4. Data Pembayaran
						</button>
					</h2>
					<div id="collapse-pembayaran" class="accordion-collapse collapse" aria-labelledby="heading-pembayaran" data-bs-parent="#accordionPenghuniForm">
						<div class="accordion-body">
							<div class="row">
								<div class="col-md-6 mb-3">
									<label class="form-label">Harga Sewa</label>
									<div class="input-group">
										<span class="input-group-text">Rp</span>
										<input type="number" name="harga_sewa" class="form-control" min="0" step="1000" value="{{ old('harga_sewa', $penghuni->harga_sewa) }}">
									</div>
								</div>
								<div class="col-md-6 mb-0">
									<label class="form-label">Tanggal Jatuh Tempo</label>
									<input type="date" name="tanggal_jatuh_tempo" class="form-control" value="{{ old('tanggal_jatuh_tempo', optional($penghuni->tanggal_jatuh_tempo)->format('Y-m-d')) }}">
								</div>
							</div>
						</div>
					</div>
				</div>

			</div>

			<div class="d-flex flex-wrap gap-2 mt-4">
				<button type="submit" class="btn btn-primary">
					<i class="bx bx-save me-1"></i>{{ $penghuni->exists ? 'Perbarui Data' : 'Simpan Data' }}
				</button>
				<a href="{{ route('penghunis.index') }}" class="btn btn-outline-secondary">
					<i class="bx bx-arrow-back me-1"></i>Kembali
				</a>
			</div>
		</div>
	</div>
</form>
@endsection

@section('scripts')
<style>
	.section-nav-card {
		top: 88px;
	}
	.section-nav-btn {
		text-align: left !important;
		justify-content: flex-start;
		padding-left: .9rem;
	}
	.section-nav-btn.active {
		border-color: #696cff;
		background: rgba(105, 108, 255, 0.14);
		color: #3f43b4;
	}
	.section-block {
		border: 1px solid #e9ecf2;
		border-radius: 10px;
		overflow: hidden;
		margin-bottom: .75rem;
	}
	.section-block .accordion-button {
		font-weight: 600;
	}
	.section-block .accordion-button:not(.collapsed) {
		background-color: #f5f6ff;
		color: #31366e;
	}
	@media (max-width: 991.98px) {
		.section-nav-card {
			position: static;
		}
	}
</style>

<script>
	const navButtons = document.querySelectorAll('.section-nav-btn');

	function setActiveNav(target) {
		navButtons.forEach((btn) => {
			btn.classList.toggle('active', btn.dataset.target === target);
		});
	}

	navButtons.forEach((btn) => {
		btn.addEventListener('click', function () {
			const target = this.dataset.target;
			const section = document.querySelector(target);
			if (!section) return;

			const collapseEl = section.querySelector('.accordion-collapse');
			if (collapseEl) {
				const collapse = bootstrap.Collapse.getOrCreateInstance(collapseEl, { toggle: false });
				collapse.show();
			}

			setActiveNav(target);
			setTimeout(() => section.scrollIntoView({ behavior: 'smooth', block: 'start' }), 120);
		});
	});

	document.querySelectorAll('.section-block .accordion-collapse').forEach((el) => {
		el.addEventListener('shown.bs.collapse', function () {
			const sectionId = '#' + this.closest('.section-block').id;
			setActiveNav(sectionId);
		});
	});

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

	setActiveNav('#section-identitas');
	syncKendaraanFields();
</script>
@endsection
