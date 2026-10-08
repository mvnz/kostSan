<?php

namespace Tests\Feature;

use App\Models\Kamar;
use App\Models\Keuangan;
use App\Models\Pembayaran;
use App\Models\Penghuni;
use App\Models\PenghuniRegistrationLink;
use App\Models\Sewa;
use App\Models\SewaPaymentLink;
use App\Models\User;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class UploadFailureTest extends TestCase
{
    use DatabaseMigrations;

    private function failDisk(): void
    {
        $disk = \Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('putFileAs')->andReturn(false);
        $disk->shouldNotReceive('delete');
        Storage::shouldReceive('disk')->with('local')->andReturn($disk);
    }

    private function lease(): Sewa
    {
        $r = Kamar::create(['nomor' => 'UPLOAD-FAIL', 'tipe' => 'A', 'harga_bulanan' => 550000, 'status' => 'terisi']);
        $p = Penghuni::create(['nama' => 'Sintetis', 'telepon' => '']);

        return Sewa::create(['kamar_id' => $r->id, 'penghuni_id' => $p->id, 'tanggal_masuk' => '2026-10-01', 'biaya_bulanan' => 550000, 'status' => 'aktif']);
    }

    public function test_failed_finance_upload_rejects_creation_and_preserves_existing_proof_on_update(): void
    {
        $this->actingAs(User::factory()->create());
        $this->failDisk();
        $entry = Keuangan::create(['tanggal' => '2026-10-09', 'jenis' => 'pengeluaran', 'kategori' => 'Internet', 'deskripsi' => 'Original', 'jumlah' => 230000, 'bukti_path' => 'bukti-keuangan/original.pdf']);
        $data = ['tanggal' => '2026-10-09', 'jenis' => 'pengeluaran', 'kategori' => 'Internet', 'deskripsi' => 'Changed', 'jumlah' => 1, 'bukti' => UploadedFile::fake()->create('proof.pdf', 10, 'application/pdf')];
        $this->postJson('/keuangans', $data)->assertUnprocessable()->assertJsonValidationErrors('bukti');
        $data['bukti'] = UploadedFile::fake()->create('proof.pdf', 10, 'application/pdf');
        $this->putJson('/keuangans/'.$entry->id, $data)->assertUnprocessable()->assertJsonValidationErrors('bukti');
        $this->assertDatabaseCount('keuangans', 1);
        $this->assertSame('bukti-keuangan/original.pdf', $entry->fresh()->bukti_path);
        $this->assertSame('Original', $entry->fresh()->deskripsi);
    }

    public function test_failed_manual_payment_upload_creates_no_payment_or_invoice(): void
    {
        $this->actingAs(User::factory()->create());
        $lease = $this->lease();
        $this->failDisk();
        $this->postJson('/pembayarans', ['sewa_id' => $lease->id, 'periode' => '2026-10-01', 'metode' => 'transfer', 'jumlah' => 550000, 'bukti_pembayaran' => UploadedFile::fake()->create('proof.pdf', 10, 'application/pdf')])->assertUnprocessable()->assertJsonValidationErrors('bukti_pembayaran');
        $this->assertDatabaseCount('pembayarans', 0);
        $this->assertDatabaseCount('invoices', 0);
    }

    public function test_failed_public_payment_upload_preserves_unused_token(): void
    {
        $lease = $this->lease();
        $link = SewaPaymentLink::create(['sewa_id' => $lease->id, 'token' => Str::random(64), 'expires_at' => now()->addDays(7)]);
        $this->failDisk();
        $this->postJson('/pembayaran/sewa/'.$link->token, ['metode' => 'transfer', 'bukti_pembayaran' => UploadedFile::fake()->create('proof.pdf', 10, 'application/pdf')])->assertUnprocessable()->assertJsonValidationErrors('bukti_pembayaran');
        $this->assertNull($link->fresh()->used_at);
        $this->assertDatabaseCount('pembayarans', 0);
        $this->assertDatabaseCount('invoices', 0);
    }

    public function test_failed_manual_payment_replacement_preserves_original_proof_and_invoice(): void
    {
        $this->actingAs(User::factory()->create());
        $lease = $this->lease();
        $payment = Pembayaran::create(['sewa_id' => $lease->id, 'periode' => '2026-10-01', 'metode' => 'cash', 'jumlah' => 550000, 'status' => 'belum_lunas', 'bukti_pembayaran_path' => 'bukti-pembayaran-sewa/original.pdf']);
        $this->failDisk();
        $this->putJson('/pembayarans/'.$payment->id, ['sewa_id' => $lease->id, 'periode' => '2026-10-01', 'metode' => 'transfer', 'jumlah' => 1, 'bukti_pembayaran' => UploadedFile::fake()->create('proof.pdf', 10, 'application/pdf')])->assertUnprocessable()->assertJsonValidationErrors('bukti_pembayaran');
        $this->assertSame('bukti-pembayaran-sewa/original.pdf', $payment->fresh()->bukti_pembayaran_path);
        $this->assertSame('550000.00', $payment->fresh()->jumlah);
        $this->assertDatabaseHas('invoices', ['payment_id' => $payment->id, 'jumlah_tagihan' => 550000]);
    }

    public function test_second_identity_upload_failure_removes_first_upload_and_keeps_registration_token(): void
    {
        $room = Kamar::create(['nomor' => 'UPLOAD-ID', 'tipe' => 'A', 'harga_bulanan' => 550000, 'status' => 'tersedia']);
        $link = PenghuniRegistrationLink::create(['kamar_id' => $room->id, 'token' => Str::random(64), 'expires_at' => now()->addDays(7)]);
        $disk = \Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('putFileAs')->twice()->andReturn('penghuni-dokumen/ktp/partial.png', false);
        $disk->shouldReceive('delete')->once()->with('penghuni-dokumen/ktp/partial.png')->andReturn(true);
        Storage::shouldReceive('disk')->with('local')->andReturn($disk);
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII=');
        $this->postJson('/pendaftaran/penghuni/'.$link->token, ['nama' => 'Sintetis', 'telepon' => '080000000000', 'waktu_sewa_bulan' => 3, 'tanggal_mulai_tinggal' => '2026-10-01', 'foto_ktp' => UploadedFile::fake()->createWithContent('ktp.png', $png), 'foto_selfie' => UploadedFile::fake()->createWithContent('selfie.png', $png)])->assertUnprocessable()->assertJsonValidationErrors('foto_selfie');
        $this->assertNull($link->fresh()->used_at);
        $this->assertDatabaseCount('penghunis', 0);
        $this->assertDatabaseCount('sewas', 0);
    }
}
