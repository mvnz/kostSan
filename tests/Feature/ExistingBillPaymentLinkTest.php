<?php

namespace Tests\Feature;

use App\Models\Kamar;
use App\Models\Pembayaran;
use App\Models\Penghuni;
use App\Models\Role;
use App\Models\Sewa;
use App\Models\SewaPaymentLink;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ExistingBillPaymentLinkTest extends TestCase
{
    use DatabaseMigrations;

    private function payment(array $attributes = []): Pembayaran
    {
        $room = Kamar::create(['nomor' => 'EXISTING-BILL', 'tipe' => 'A', 'harga_bulanan' => 550000, 'status' => 'terisi']);
        $resident = Penghuni::create(['nama' => 'Penghuni sintetis', 'telepon' => '']);
        $lease = Sewa::create(['kamar_id' => $room->id, 'penghuni_id' => $resident->id, 'tanggal_masuk' => '2026-10-01', 'tanggal_keluar' => '2027-01-01', 'biaya_bulanan' => 550000, 'status' => 'aktif']);

        return Pembayaran::create([...['sewa_id' => $lease->id, 'periode' => '2026-11-01', 'metode' => 'cash', 'jumlah' => 550000.25, 'status' => 'belum_lunas'], ...$attributes]);
    }

    private function generate(Pembayaran $payment): SewaPaymentLink
    {
        $this->actingAs(User::factory()->create())->post(route('payment-registrations.generate', $payment))->assertRedirect(route('pembayarans.show', $payment))->assertSessionHas('sewa_payment_link');

        return SewaPaymentLink::latest('id')->firstOrFail();
    }

    public function test_operator_can_link_existing_bill_without_changing_amount_or_period(): void
    {
        Storage::fake('local');
        $payment = $this->payment();
        $link = $this->generate($payment);
        $this->get('/pembayaran/sewa/'.$link->token)->assertOk()->assertSee('Tagihan yang sudah dibuat')->assertSee('550.000,25')->assertSee('11/2026');
        $this->post('/pembayaran/sewa/'.$link->token, ['metode' => 'transfer', 'jumlah' => 1, 'periode' => '1900-01-01', 'keterangan' => '<script>alert(1)</script>', 'bukti_pembayaran' => UploadedFile::fake()->create('proof.pdf', 10, 'application/pdf')])->assertOk();
        $payment->refresh();
        $this->assertSame('550000.25', $payment->jumlah);
        $this->assertSame('2026-11-01', $payment->periode->toDateString());
        $this->assertSame('belum_lunas', $payment->status);
        $this->assertSame('transfer', $payment->metode);
        $this->assertStringContainsString('<script>alert(1)</script>', $payment->keterangan);
        $this->assertNotNull($payment->tanggal_bayar);
        $this->assertNotNull($payment->bukti_pembayaran_path);
        $this->assertNotNull($link->fresh()->used_at);
        $this->assertDatabaseCount('pembayarans', 1);
        $this->assertDatabaseHas('invoices', ['payment_id' => $payment->id, 'jumlah_tagihan' => 550000.25, 'status' => 'draft']);
        $this->get('/pembayaran/sewa/'.$link->token)->assertOk()->assertViewIs('sewa-payment-registrations.expired');
    }

    public function test_new_link_revokes_previous_unused_link_and_paid_bill_cannot_get_or_use_link(): void
    {
        $payment = $this->payment();
        $first = $this->generate($payment);
        $second = $this->generate($payment);
        $this->assertNotNull($first->fresh()->used_at);
        $this->assertNull($second->fresh()->used_at);
        $payment->update(['status' => 'lunas']);
        $this->postJson(route('payment-registrations.generate', $payment))->assertUnprocessable()->assertJsonValidationErrors('pembayaran');
        $this->get('/pembayaran/sewa/'.$second->token)->assertViewIs('sewa-payment-registrations.expired');
        $this->post('/pembayaran/sewa/'.$second->token, ['metode' => 'transfer'])->assertViewIs('sewa-payment-registrations.expired');
    }

    public function test_failed_existing_bill_update_rolls_back_upload_and_preserves_token_and_old_proof(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('bukti-pembayaran-sewa/original.pdf', 'old');
        $payment = $this->payment(['bukti_pembayaran_path' => 'bukti-pembayaran-sewa/original.pdf']);
        $link = $this->generate($payment);
        DB::statement("CREATE TRIGGER fail_existing_invoice BEFORE UPDATE ON invoices BEGIN SELECT RAISE(ABORT, 'synthetic'); END");
        $this->postJson('/pembayaran/sewa/'.$link->token, ['metode' => 'transfer', 'bukti_pembayaran' => UploadedFile::fake()->create('replacement.pdf', 10, 'application/pdf')])->assertStatus(500);
        $this->assertNull($link->fresh()->used_at);
        $this->assertSame('cash', $payment->fresh()->metode);
        $this->assertSame('bukti-pembayaran-sewa/original.pdf', $payment->fresh()->bukti_pembayaran_path);
        Storage::disk('local')->assertExists('bukti-pembayaran-sewa/original.pdf');
        $this->assertSame(['bukti-pembayaran-sewa/original.pdf'], Storage::disk('local')->allFiles('bukti-pembayaran-sewa'));
    }

    public function test_existing_bill_link_generation_requires_update_permission(): void
    {
        $payment = $this->payment();
        $viewer = Role::create(['name' => 'Viewer', 'menu_permissions' => ['manajemen_sewa.data_sewa' => ['view']]]);
        $this->actingAs(User::factory()->create(['role_id' => $viewer->id]))->postJson(route('payment-registrations.generate', $payment))->assertForbidden();
        $this->assertDatabaseCount('sewa_payment_links', 0);
    }

    public function test_transfer_requires_proof_but_cash_can_be_confirmed_without_upload(): void
    {
        Storage::fake('local');
        $payment = $this->payment();
        $link = $this->generate($payment);
        $this->postJson('/pembayaran/sewa/'.$link->token, ['metode' => 'transfer'])->assertUnprocessable()->assertJsonValidationErrors('bukti_pembayaran');
        $this->assertNull($link->fresh()->used_at);
        $this->assertNull($payment->fresh()->tanggal_bayar);
        $this->post('/pembayaran/sewa/'.$link->token, ['metode' => 'cash'])->assertOk();
        $this->assertNotNull($link->fresh()->used_at);
        $this->assertSame('cash', $payment->fresh()->metode);
    }
}
