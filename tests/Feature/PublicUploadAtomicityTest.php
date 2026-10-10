<?php

namespace Tests\Feature;

use App\Models\Kamar;
use App\Models\Penghuni;
use App\Models\PenghuniRegistrationLink;
use App\Models\Sewa;
use App\Models\SewaPaymentLink;
use App\Services\WhatsAppService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class PublicUploadAtomicityTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->mock(WhatsAppService::class)->shouldIgnoreMissing();
    }

    private function paymentLink(): SewaPaymentLink
    {
        $room = Kamar::create(['nomor' => 'PUBLIC-PROOF', 'tipe' => 'A', 'harga_bulanan' => 550000, 'status' => 'terisi']);
        $resident = Penghuni::create(['nama' => 'Sintetis', 'telepon' => '']);
        $lease = Sewa::create(['kamar_id' => $room->id, 'penghuni_id' => $resident->id, 'tanggal_masuk' => '2026-10-01', 'tanggal_keluar' => '2027-01-01', 'biaya_bulanan' => 550000, 'status' => 'menunggak']);

        return SewaPaymentLink::create(['sewa_id' => $lease->id, 'token' => Str::random(64), 'expires_at' => now()->addDays(7)]);
    }

    private function proof(): array
    {
        return ['metode' => 'transfer', 'bukti_pembayaran' => UploadedFile::fake()->create('proof.pdf', 10, 'application/pdf')];
    }

    public function test_public_payment_failure_cleans_upload_rolls_back_payment_invoice_and_preserves_token_for_retry(): void
    {
        $link = $this->paymentLink();
        DB::statement("CREATE TRIGGER fail_invoice BEFORE INSERT ON invoices BEGIN SELECT RAISE(ABORT, 'synthetic'); END");
        $this->postJson('/pembayaran/sewa/'.$link->token, $this->proof())->assertStatus(500);
        $this->assertDatabaseCount('pembayarans', 0);
        $this->assertDatabaseCount('invoices', 0);
        $this->assertNull($link->fresh()->used_at);
        $this->assertSame([], Storage::disk('local')->allFiles('bukti-pembayaran-sewa'));
        DB::statement('DROP TRIGGER fail_invoice');
        $this->post('/pembayaran/sewa/'.$link->token, $this->proof())->assertOk()->assertViewIs('sewa-payment-registrations.success');
        $this->assertDatabaseCount('pembayarans', 1);
        $this->assertDatabaseCount('invoices', 1);
        $this->assertCount(1, Storage::disk('local')->allFiles('bukti-pembayaran-sewa'));
        $this->post('/pembayaran/sewa/'.$link->token, $this->proof())->assertViewIs('sewa-payment-registrations.expired');
        $this->assertCount(1, Storage::disk('local')->allFiles('bukti-pembayaran-sewa'));
    }

    private function registrationPayload(): array
    {
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII=');

        return ['nama' => 'Sintetis', 'telepon' => '080000000000', 'waktu_sewa_bulan' => 3, 'tanggal_mulai_tinggal' => '2026-10-01', 'foto_ktp' => UploadedFile::fake()->createWithContent('ktp.png', $png), 'foto_selfie' => UploadedFile::fake()->createWithContent('selfie.png', $png)];
    }

    public function test_public_registration_failure_cleans_private_identity_uploads_and_allows_retry(): void
    {
        $room = Kamar::create(['nomor' => 'PUBLIC-REGISTER', 'tipe' => 'A', 'harga_bulanan' => 550000, 'status' => 'tersedia']);
        $link = PenghuniRegistrationLink::create(['kamar_id' => $room->id, 'token' => Str::random(64), 'expires_at' => now()->addDays(7)]);
        DB::statement("CREATE TRIGGER fail_lease BEFORE INSERT ON sewas BEGIN SELECT RAISE(ABORT, 'synthetic'); END");
        $this->postJson('/pendaftaran/penghuni/'.$link->token, $this->registrationPayload())->assertStatus(500);
        $this->assertDatabaseCount('penghunis', 0);
        $this->assertDatabaseCount('sewas', 0);
        $this->assertDatabaseCount('sewa_payment_links', 0);
        $this->assertNull($link->fresh()->used_at);
        $this->assertSame([], Storage::disk('local')->allFiles('penghuni-dokumen'));
        DB::statement('DROP TRIGGER fail_lease');
        $this->post('/pendaftaran/penghuni/'.$link->token, $this->registrationPayload())->assertOk()->assertViewIs('penghuni-registrations.success');
        $this->assertDatabaseCount('penghunis', 1);
        $this->assertDatabaseCount('sewas', 1);
        $this->assertCount(2,Storage::disk('local')->allFiles('penghuni-dokumen'));
    }
}
