<?php

namespace Tests\Feature;

use App\Models\Kamar;
use App\Models\KamarTipeHarga;
use App\Models\Pembayaran;
use App\Models\Penghuni;
use App\Models\PenghuniRegistrationLink;
use App\Models\Reservasi;
use App\Models\Role;
use App\Models\Sewa;
use App\Models\SewaPaymentLink;
use App\Models\User;
use App\Services\WhatsAppService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class OperationalIntegrityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        Storage::fake('local');
        $this->actingAs(User::factory()->create());
    }

    private function lease(array $attributes = []): Sewa
    {
        $room = Kamar::create(['nomor' => Str::random(8), 'tipe' => 'A', 'harga_bulanan' => 1000000, 'status' => 'tersedia']);
        $resident = Penghuni::create(['nama' => 'Penghuni Uji', 'telepon' => '']);

        return Sewa::create(array_merge([
            'kamar_id' => $room->id, 'penghuni_id' => $resident->id,
            'tanggal_masuk' => '2026-10-01', 'tanggal_keluar' => '2026-11-01',
            'biaya_bulanan' => 1000000, 'uang_jaminan' => 0, 'status' => 'aktif',
        ], $attributes));
    }

    private function payment(Sewa $lease): Pembayaran
    {
        return Pembayaran::create(['sewa_id' => $lease->id, 'periode' => '2026-10-01', 'metode' => 'cash', 'jumlah' => 1000000, 'status' => 'belum_lunas']);
    }

    private function leasePayload(Sewa $lease): array
    {
        return array_merge($lease->only(['kamar_id', 'penghuni_id', 'biaya_bulanan', 'uang_jaminan', 'status']), [
            'tanggal_masuk' => '2026-10-01', 'tanggal_keluar' => '2026-11-01',
        ]);
    }

    public function test_new_lease_cannot_displace_existing_resident(): void
    {
        $existing = $this->lease();
        $response = $this->post('/sewas', $this->leasePayload($existing));
        $response->assertSessionHasErrors('kamar_id');
        $this->assertSame('aktif', $existing->fresh()->status);
        $this->assertDatabaseCount('sewas', 1);
    }

    public function test_master_room_edit_cannot_silently_end_an_active_lease(): void
    {
        $lease = $this->lease();
        KamarTipeHarga::create(['tipe' => 'A', 'harga_1_bulan' => 1000000, 'harga_3_bulan' => 1000000, 'harga_6_bulan' => 1000000, 'harga_12_bulan' => 1000000]);
        $this->put('/kamars/'.$lease->kamar_id, ['nomor' => $lease->kamar->nomor, 'tipe' => 'A', 'status' => 'tersedia', 'layout_floor' => 1])->assertSessionHasErrors('status');
        $this->assertSame('aktif', $lease->fresh()->status);
    }

    public function test_updating_lease_cannot_move_into_occupied_room(): void
    {
        $existing = $this->lease();
        $moving = $this->lease();
        $payload = $this->leasePayload($moving);
        $payload['kamar_id'] = $existing->kamar_id;
        $this->put('/sewas/'.$moving->id, $payload)->assertSessionHasErrors('kamar_id');
        $this->assertSame($moving->kamar_id, $moving->fresh()->kamar_id);
    }

    public function test_payment_approval_cannot_displace_another_resident(): void
    {
        $existing = $this->lease();
        $pending = $this->lease(['kamar_id' => $existing->kamar_id, 'status' => 'menunggak']);
        $payment = $this->payment($pending);
        $this->post('/pembayarans/'.$payment->id.'/approve')->assertSessionHasErrors('kamar_id');
        $this->assertSame('aktif', $existing->fresh()->status);
        $this->assertSame('belum_lunas', $payment->fresh()->status);
        $this->assertDatabaseHas('invoices', ['status' => 'draft']);
    }

    public function test_historical_payment_does_not_reopen_completed_lease(): void
    {
        $completed = $this->lease(['status' => 'selesai']);
        $completed->kamar->update(['status' => 'tersedia']);
        $payment = $this->payment($completed);
        $this->post('/pembayarans/'.$payment->id.'/approve')->assertRedirect();
        $this->assertSame('selesai', $completed->fresh()->status);
        $this->assertSame('tersedia', $completed->kamar->fresh()->status);
        $this->assertSame('lunas', $payment->fresh()->status);
    }

    public function test_approval_is_idempotent_and_notifies_only_once(): void
    {
        $lease = $this->lease(['status' => 'menunggak']);
        $lease->penghuni->update(['telepon' => '080000000000']);
        $payment = $this->payment($lease);
        $this->mock(WhatsAppService::class)->shouldReceive('sendByType')->once()->andReturn(false);
        $this->post('/pembayarans/'.$payment->id.'/approve')->assertRedirect();
        $this->post('/pembayarans/'.$payment->id.'/approve')->assertRedirect();
        $this->assertSame('aktif', $lease->fresh()->status);
        $this->assertSame('terisi', $lease->kamar->fresh()->status);
        $this->assertSame(1, substr_count($payment->fresh()->keterangan, '[Approved admin]'));
        $this->assertDatabaseCount('invoices', 1);
    }

    public function test_stale_public_registration_cannot_close_an_active_lease(): void
    {
        $existing = $this->lease();
        $link = PenghuniRegistrationLink::create(['kamar_id' => $existing->kamar_id, 'token' => Str::random(64), 'expires_at' => now()->addDays(7)]);
        auth()->logout();
        $this->post('/pendaftaran/penghuni/'.$link->token, [
            'nama' => 'Calon Penghuni Uji', 'telepon' => '080000000000', 'waktu_sewa_bulan' => 1,
            'tanggal_mulai_tinggal' => '2026-10-01',
            'foto_ktp' => UploadedFile::fake()->createWithContent('ktp.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aOQAAAABJRU5ErkJggg==')),
            'foto_selfie' => UploadedFile::fake()->createWithContent('selfie.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aOQAAAABJRU5ErkJggg==')),
        ])->assertSessionHasErrors('kamar_id');
        $this->assertSame('aktif', $existing->fresh()->status);
        $this->assertNull($link->fresh()->used_at);
        $this->assertDatabaseCount('penghunis', 1);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_confirmed_reservation_conflicts_are_rejected(): void
    {
        $existing = $this->lease();
        $this->post('/reservasis', [
            'kamar_id' => $existing->kamar_id, 'penghuni_id' => $existing->penghuni_id,
            'tanggal_reservasi' => '2026-09-20', 'rencana_masuk' => '2026-10-15',
            'rencana_keluar' => '2026-11-15', 'status' => 'dikonfirmasi',
        ])->assertSessionHasErrors('kamar_id');
        $this->assertDatabaseCount('reservasis', 0);
    }

    public function test_lease_cannot_overlap_another_residents_confirmed_reservation(): void
    {
        $lease = $this->lease(['status' => 'selesai']);
        $other = Penghuni::create(['nama' => 'Pemesan Uji', 'telepon' => '']);
        Reservasi::create(['kamar_id' => $lease->kamar_id, 'penghuni_id' => $other->id, 'tanggal_reservasi' => '2026-09-20', 'rencana_masuk' => '2026-10-15', 'rencana_keluar' => '2026-11-15', 'status' => 'dikonfirmasi']);
        $payload = $this->leasePayload($lease);
        $payload['status'] = 'aktif';
        $this->post('/sewas', $payload)->assertSessionHasErrors('kamar_id');
    }

    public function test_extension_cannot_shorten_lease_or_overlap_reservation(): void
    {
        $lease = $this->lease();
        $this->post('/kamars/'.$lease->kamar_id.'/perpanjang-sewa', ['tanggal_keluar' => '2026-09-01'])->assertSessionHasErrors('tanggal_keluar');
        $other = Penghuni::create(['nama' => 'Pemesan Uji', 'telepon' => '']);
        Reservasi::create(['kamar_id' => $lease->kamar_id, 'penghuni_id' => $other->id, 'tanggal_reservasi' => '2026-09-20', 'rencana_masuk' => '2026-11-01', 'rencana_keluar' => '2026-12-01', 'status' => 'dikonfirmasi']);
        $this->post('/kamars/'.$lease->kamar_id.'/perpanjang-sewa', ['tanggal_keluar' => '2026-11-15'])->assertSessionHasErrors('kamar_id');
        $this->assertSame('2026-11-01', $lease->fresh()->tanggal_keluar->toDateString());
    }

    public function test_adjacent_reservation_is_allowed_and_can_be_updated(): void
    {
        $lease = $this->lease();
        $payload = ['kamar_id' => $lease->kamar_id, 'penghuni_id' => $lease->penghuni_id, 'tanggal_reservasi' => '2026-09-20', 'rencana_masuk' => '2026-11-01', 'rencana_keluar' => '2026-12-01', 'status' => 'dikonfirmasi'];
        $this->post('/reservasis', $payload)->assertSessionHasNoErrors();
        $reservation = Reservasi::firstOrFail();
        $this->put('/reservasis/'.$reservation->id, $payload)->assertSessionHasNoErrors();
    }

    public function test_repair_room_cannot_be_leased(): void
    {
        $lease = $this->lease(['status' => 'selesai']);
        $lease->kamar->update(['status' => 'perbaikan']);
        $payload = $this->leasePayload($lease);
        $payload['status'] = 'aktif';
        $this->post('/sewas', $payload)->assertSessionHasErrors('kamar_id');
    }

    public function test_month_end_three_month_lease_is_billed_for_three_months(): void
    {
        $lease = $this->lease(['tanggal_masuk' => '2026-01-31', 'tanggal_keluar' => '2026-04-30']);
        $link = SewaPaymentLink::create(['sewa_id' => $lease->id, 'token' => Str::random(64), 'expires_at' => now()->addDays(7)]);
        $this->get('/pembayaran/sewa/'.$link->token)->assertOk()
            ->assertViewHas('billing', fn ($bill) => $bill['durasi_bulan'] === 3 && $bill['total'] == 3000000);
        // Existing dates created with overflow retain their previous billing duration.
        $lease->tanggal_keluar = '2026-05-01';
        $this->assertSame(3, $lease->billingMonths());
        $lease->tanggal_masuk = '2026-01-15';
        $lease->tanggal_keluar = '2026-02-20';
        $this->assertSame(1, $lease->billingMonths());
    }

    public function test_main_pages_and_pdf_contract_render_with_test_data(): void
    {
        $lease = $this->lease();
        $this->payment($lease);
        foreach (['/', '/kamars', '/kamars/sewa', '/penghunis', '/sewas', '/sewas/create', '/sewas/'.$lease->id, '/pembayarans', '/pembayarans-bulk', '/reservasis', '/keuangans', '/invoices', '/laporan-hunian', '/laporan-keuangan', '/log-aktivitas', '/roles', '/users', '/bantuan'] as $path) {
            $this->get($path)->assertOk();
        }
        $response = $this->get('/sewas/'.$lease->id.'/kontrak');
        $response->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }

    public function test_public_registration_payment_and_approval_flow(): void
    {
        $this->mock(WhatsAppService::class)->shouldReceive('sendByType')->twice()->andReturn(false);
        $owner = auth()->user();
        $room = Kamar::create(['nomor' => 'E2E-1', 'tipe' => 'A', 'harga_bulanan' => 1000000, 'status' => 'tersedia']);
        $link = PenghuniRegistrationLink::create(['kamar_id' => $room->id, 'token' => Str::random(64), 'expires_at' => now()->addDays(7)]);
        auth()->logout();
        $this->get('/pendaftaran/penghuni/'.$link->token)->assertOk();
        $payload = [
            'nama' => 'Penghuni E2E Uji', 'telepon' => '080000000000', 'waktu_sewa_bulan' => 3,
            'tanggal_mulai_tinggal' => '2026-01-31',
            'foto_ktp' => UploadedFile::fake()->createWithContent('ktp.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aOQAAAABJRU5ErkJggg==')),
            'foto_selfie' => UploadedFile::fake()->createWithContent('selfie.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aOQAAAABJRU5ErkJggg==')),
        ];
        $this->post('/pendaftaran/penghuni/'.$link->token, $payload)->assertOk()->assertViewIs('penghuni-registrations.success');
        $lease = Sewa::firstOrFail();
        $this->assertSame('2026-04-30', $lease->tanggal_keluar->toDateString());
        $this->assertSame('menunggak', $lease->status);
        $this->post('/pendaftaran/penghuni/'.$link->token, $payload)->assertViewIs('penghuni-registrations.expired');
        $this->assertDatabaseCount('penghunis', 1);
        $paymentLink = SewaPaymentLink::firstOrFail();
        $this->post('/pembayaran/sewa/'.$paymentLink->token, ['metode' => 'cash'])->assertOk()->assertViewIs('sewa-payment-registrations.success');
        $this->post('/pembayaran/sewa/'.$paymentLink->token, ['metode' => 'cash'])->assertViewIs('sewa-payment-registrations.expired');
        $this->assertDatabaseCount('pembayarans', 1);
        $payment = Pembayaran::firstOrFail();
        $this->assertEquals(3000000, $payment->jumlah);
        $this->assertSame('belum_lunas', $payment->status);
        $this->actingAs($owner)->post('/pembayarans/'.$payment->id.'/approve')->assertSessionHasNoErrors();
        $this->assertSame('aktif', $lease->fresh()->status);
        $this->assertSame('terisi', $room->fresh()->status);
        $this->assertDatabaseHas('invoices', ['status' => 'lunas', 'jumlah_tagihan' => 3000000]);
    }

    public function test_document_requires_its_own_module_permission(): void
    {
        $role = Role::create(['name' => 'Hanya Dashboard', 'menu_permissions' => ['dashboard' => ['view']]]);
        $this->actingAs(User::factory()->create(['role_id' => $role->id]));
        Storage::disk('local')->put('penghuni-dokumen/ktp/test.jpg', 'dokumen-uji');
        $this->get('/secure-files/penghuni-dokumen/ktp/test.jpg')->assertForbidden();
        $role->update(['menu_permissions' => ['master_data.data_penghuni' => ['view']]]);
        $this->actingAs(User::findOrFail(auth()->id()));
        $this->get('/secure-files/penghuni-dokumen/ktp/test.jpg')->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        Storage::disk('local')->put('bukti-keuangan/test.pdf', 'keuangan-uji');
        $this->get('/secure-files/bukti-keuangan/test.pdf')->assertForbidden();
    }
}
