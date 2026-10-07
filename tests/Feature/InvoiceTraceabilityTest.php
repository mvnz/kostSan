<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Kamar;
use App\Models\Pembayaran;
use App\Models\Penghuni;
use App\Models\Sewa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class InvoiceTraceabilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    private function lease(Penghuni $resident, string $room): Sewa
    {
        $kamar = Kamar::create([
            'nomor' => $room,
            'tipe' => 'A',
            'harga_bulanan' => 1000000,
            'status' => 'terisi',
        ]);

        return Sewa::create([
            'kamar_id' => $kamar->id,
            'penghuni_id' => $resident->id,
            'tanggal_masuk' => '2026-10-01',
            'biaya_bulanan' => 1000000,
            'status' => 'aktif',
        ]);
    }

    private function payment(Sewa $lease, int $amount = 1000000): Pembayaran
    {
        return Pembayaran::create([
            'sewa_id' => $lease->id,
            'periode' => '2026-10-01',
            'metode' => 'transfer',
            'jumlah' => $amount,
            'status' => 'belum_lunas',
        ]);
    }

    public function test_each_payment_has_its_own_invoice_even_for_same_resident_and_period(): void
    {
        $resident = Penghuni::create(['nama' => 'Penghuni '.Str::random(5), 'telepon' => '']);
        $first = $this->payment($this->lease($resident, 'TRACE-1'), 1000000);
        $second = $this->payment($this->lease($resident, 'TRACE-2'), 1500000);

        $this->assertDatabaseCount('invoices', 2);
        $this->assertDatabaseHas('invoices', ['payment_id' => $first->id, 'jumlah_tagihan' => 1000000]);
        $this->assertDatabaseHas('invoices', ['payment_id' => $second->id, 'jumlah_tagihan' => 1500000]);
    }

    public function test_manual_invoice_is_not_overwritten_by_payment_sync(): void
    {
        $resident = Penghuni::create(['nama' => 'Penghuni Manual', 'telepon' => '']);
        $manual = Invoice::create([
            'penghuni_id' => $resident->id,
            'nomor_invoice' => 'INV-MANUAL-001',
            'periode' => '2026-10-01',
            'jatuh_tempo' => '2026-10-10',
            'jumlah_tagihan' => 275000,
            'status' => 'terkirim',
            'keterangan' => 'Tagihan tambahan manual',
        ]);

        $payment = $this->payment($this->lease($resident, 'TRACE-3'));

        $this->assertDatabaseCount('invoices', 2);
        $this->assertDatabaseHas('invoices', [
            'id' => $manual->id,
            'payment_id' => null,
            'jumlah_tagihan' => 275000,
            'status' => 'terkirim',
            'keterangan' => 'Tagihan tambahan manual',
        ]);
        $this->assertDatabaseHas('invoices', ['payment_id' => $payment->id, 'jumlah_tagihan' => 1000000]);
    }

    public function test_deleting_unapproved_payment_removes_only_its_generated_invoice(): void
    {
        $resident = Penghuni::create(['nama' => 'Penghuni Hapus', 'telepon' => '']);
        $payment = $this->payment($this->lease($resident, 'TRACE-4'));
        $manual = Invoice::create([
            'penghuni_id' => $resident->id,
            'nomor_invoice' => 'INV-MANUAL-002',
            'periode' => '2026-10-01',
            'jatuh_tempo' => '2026-10-10',
            'jumlah_tagihan' => 100000,
            'status' => 'draft',
        ]);

        $this->delete('/pembayarans/'.$payment->id)->assertRedirect();

        $this->assertDatabaseMissing('invoices', ['payment_id' => $payment->id]);
        $this->assertDatabaseHas('invoices', ['id' => $manual->id]);
    }

    public function test_generated_invoice_cannot_be_edited_or_deleted_separately(): void
    {
        $resident = Penghuni::create(['nama' => 'Penghuni Terkunci', 'telepon' => '']);
        $payment = $this->payment($this->lease($resident, 'TRACE-5'));
        $invoice = Invoice::where('payment_id', $payment->id)->firstOrFail();
        $payload = [
            'penghuni_id' => $resident->id,
            'periode' => '2026-10-01',
            'jatuh_tempo' => '2026-10-10',
            'jumlah_tagihan' => 1,
            'status' => 'lunas',
            'keterangan' => 'diubah',
        ];

        $this->putJson('/invoices/'.$invoice->id, $payload)
            ->assertUnprocessable()->assertJsonValidationErrors('invoice');
        $this->deleteJson('/invoices/'.$invoice->id)
            ->assertUnprocessable()->assertJsonValidationErrors('invoice');
        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'payment_id' => $payment->id,
            'jumlah_tagihan' => 1000000,
            'status' => 'draft',
        ]);
    }

    public function test_invoice_list_shows_payment_origin_and_sent_status(): void
    {
        $resident = Penghuni::create(['nama' => 'Penghuni Asal', 'telepon' => '']);
        $payment = $this->payment($this->lease($resident, 'TRACE-6'));
        $invoice = Invoice::where('payment_id', $payment->id)->firstOrFail();
        $invoice->update(['status' => 'terkirim']);

        $this->get('/invoices')->assertOk()
            ->assertSee('Pembayaran #'.$payment->id)
            ->assertSee('Kamar TRACE-6')
            ->assertSee('Terkirim');
    }
}
