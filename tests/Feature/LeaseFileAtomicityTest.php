<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Kamar;
use App\Models\Keuangan;
use App\Models\Pembayaran;
use App\Models\Penghuni;
use App\Models\Sewa;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LeaseFileAtomicityTest extends TestCase
{
    use RefreshDatabase;

    private Kamar $room;

    private Penghuni $resident;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
        Storage::fake('local');
        $this->room = Kamar::create(['nomor' => 'LEASE-FILE', 'tipe' => 'A', 'harga_bulanan' => 550000, 'status' => 'tersedia']);
        $this->resident = Penghuni::create(['nama' => 'Lease File Synthetic', 'telepon' => '']);
    }

    private function payload(): array
    {
        return [
            'kamar_id' => $this->room->id,
            'penghuni_id' => $this->resident->id,
            'tanggal_masuk' => '2026-10-01',
            'tanggal_keluar' => '2026-11-01',
            'biaya_bulanan' => 550000,
            'status' => 'aktif',
            'bukti_pembayaran' => UploadedFile::fake()->create('proof.pdf', 10, 'application/pdf'),
        ];
    }

    private function lease(): Sewa
    {
        Storage::disk('local')->put('bukti-sewa/original.pdf', 'synthetic');

        return Sewa::create([
            'kamar_id' => $this->room->id,
            'penghuni_id' => $this->resident->id,
            'tanggal_masuk' => '2026-10-01',
            'tanggal_keluar' => '2026-11-01',
            'biaya_bulanan' => 550000,
            'status' => 'aktif',
            'bukti_pembayaran' => 'bukti-sewa/original.pdf',
        ]);
    }

    public function test_failed_lease_insert_cleans_new_proof(): void
    {
        DB::statement("CREATE TRIGGER fail_lease_file BEFORE INSERT ON sewas BEGIN SELECT RAISE(ABORT, 'synthetic'); END");

        $this->postJson('/sewas', $this->payload())->assertStatus(500);

        $this->assertDatabaseCount('sewas', 0);
        $this->assertSame([], Storage::disk('local')->allFiles('bukti-sewa'));
        $this->assertSame('tersedia', $this->room->fresh()->status);
    }

    public function test_failed_lease_update_cleans_replacement_and_preserves_original(): void
    {
        $lease = $this->lease();
        DB::statement("CREATE TRIGGER fail_lease_file_update BEFORE UPDATE ON sewas BEGIN SELECT RAISE(ABORT, 'synthetic'); END");

        $this->putJson('/sewas/'.$lease->id, $this->payload())->assertStatus(500);

        $this->assertSame('bukti-sewa/original.pdf', $lease->fresh()->bukti_pembayaran);
        $this->assertSame(['bukti-sewa/original.pdf'], Storage::disk('local')->allFiles('bukti-sewa'));
    }

    public function test_successful_lease_delete_removes_proof_after_commit(): void
    {
        $lease = $this->lease();
        $this->room->update(['status' => 'terisi']);

        $this->delete('/sewas/'.$lease->id)->assertRedirect('/sewas');

        $this->assertDatabaseMissing('sewas', ['id' => $lease->id]);
        Storage::disk('local')->assertMissing('bukti-sewa/original.pdf');
        $this->assertSame('tersedia', $this->room->fresh()->status);
    }

    public function test_lease_with_payment_history_cannot_be_deleted_or_cascade_financial_records(): void
    {
        $lease = $this->lease();
        $this->room->update(['status' => 'terisi']);
        Storage::disk('local')->put('bukti-pembayaran-sewa/history.pdf', 'synthetic payment');
        $payment = Pembayaran::create([
            'sewa_id' => $lease->id,
            'periode' => '2026-10-01',
            'tanggal_bayar' => '2026-10-02',
            'metode' => 'transfer',
            'jumlah' => 550000,
            'status' => 'lunas',
            'bukti_pembayaran_path' => 'bukti-pembayaran-sewa/history.pdf',
        ]);
        $invoice = Invoice::where('payment_id', $payment->id)->sole();
        $ledger = Keuangan::create([
            'payment_id' => $payment->id,
            'tanggal' => '2026-10-02',
            'jenis' => 'pemasukan',
            'kategori' => 'Sewa Kamar',
            'deskripsi' => 'Synthetic approved payment',
            'jumlah' => 550000,
        ]);

        $this->get('/sewas/'.$lease->id)
            ->assertOk()
            ->assertSee('Riwayat pembayaran tersimpan')
            ->assertDontSee('action="'.route('sewas.destroy', $lease).'"', false);

        $this->delete('/sewas/'.$lease->id)
            ->assertRedirect('/sewas')
            ->assertSessionHas('error');

        $this->assertDatabaseHas('sewas', ['id' => $lease->id]);
        $this->assertDatabaseHas('pembayarans', ['id' => $payment->id]);
        $this->assertDatabaseHas('invoices', ['id' => $invoice->id, 'payment_id' => $payment->id]);
        $this->assertDatabaseHas('keuangans', ['id' => $ledger->id, 'payment_id' => $payment->id]);
        Storage::disk('local')->assertExists('bukti-sewa/original.pdf');
        Storage::disk('local')->assertExists('bukti-pembayaran-sewa/history.pdf');
        $this->assertSame('terisi', $this->room->fresh()->status);
    }

    public function test_database_restricts_direct_lease_delete_when_payment_exists(): void
    {
        $lease = $this->lease();
        $payment = Pembayaran::create([
            'sewa_id' => $lease->id,
            'periode' => '2026-10-01',
            'metode' => 'cash',
            'jumlah' => 550000,
            'status' => 'belum_lunas',
        ]);

        try {
            DB::table('sewas')->where('id', $lease->id)->delete();
            $this->fail('Database allowed a lease with payment history to be deleted.');
        } catch (QueryException) {
            // Expected: the database is the final guard against financial-history cascade.
        }

        $this->assertDatabaseHas('sewas', ['id' => $lease->id]);
        $this->assertDatabaseHas('pembayarans', ['id' => $payment->id]);
    }
}
