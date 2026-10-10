<?php

namespace Tests\Feature;

use App\Models\Kamar;
use App\Models\Keuangan;
use App\Models\Pembayaran;
use App\Models\Penghuni;
use App\Models\Role;
use App\Models\Sewa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentDetailTest extends TestCase
{
    use RefreshDatabase;

    private function payment(): Pembayaran
    {
        $r = Kamar::create(['nomor' => 'DETAIL', 'tipe' => 'A', 'harga_bulanan' => 550000, 'status' => 'terisi']);
        $p = Penghuni::create(['nama' => '<script>alert(1)</script>', 'telepon' => '']);
        $s = Sewa::create(['kamar_id' => $r->id, 'penghuni_id' => $p->id, 'tanggal_masuk' => '2026-10-01', 'biaya_bulanan' => 550000, 'status' => 'aktif']);

        return Pembayaran::create(['sewa_id' => $s->id, 'periode' => '2026-10-01', 'metode' => 'transfer', 'jumlah' => 550000.25, 'status' => 'belum_lunas', 'bukti_pembayaran_path' => 'bukti-pembayaran-sewa/proof.pdf']);
    }

    public function test_read_only_payment_user_can_trace_details_without_mutation_or_finance_access(): void
    {
        $payment = $this->payment();
        $ledger = Keuangan::create(['payment_id' => $payment->id, 'tanggal' => '2026-10-09', 'jenis' => 'pemasukan', 'kategori' => 'Sewa Kamar', 'deskripsi' => 'Sintetis', 'jumlah' => 550000.25]);
        $role = Role::create(['name' => 'Payment viewer', 'menu_permissions' => ['manajemen_sewa.data_sewa' => ['view']]]);
        $this->actingAs(User::factory()->create(['role_id' => $role->id]));
        $this->get('/pembayarans/'.$payment->id)->assertOk()->assertHeader('Cache-Control', 'no-store, private')->assertSee('550.000,25')->assertSee('DETAIL')->assertDontSee('<script>alert(1)</script>', false)->assertDontSee('/pembayarans/'.$payment->id.'/edit', false)->assertDontSee('/pembayarans/'.$payment->id.'/approve', false)->assertDontSee('/keuangans/'.$ledger->id, false)->assertSee('secure-files/bukti-pembayaran-sewa/proof.pdf', false);
        $this->getJson('/pembayarans/'.$payment->id.'/edit')->assertForbidden();
    }

    public function test_admin_can_trace_ledger_and_edit_pending_but_not_paid_payment(): void
    {
        $payment = $this->payment();
        $this->actingAs(User::factory()->create());
        $ledger = Keuangan::create(['payment_id' => $payment->id, 'tanggal' => '2026-10-09', 'jenis' => 'pemasukan', 'kategori' => 'Sewa Kamar', 'deskripsi' => 'Sintetis', 'jumlah' => 550000.25]);
        $this->get('/pembayarans/'.$payment->id)->assertOk()->assertSee('/pembayarans/'.$payment->id.'/edit', false)->assertSee('/keuangans/'.$ledger->id, false);
        $payment->update(['status' => 'lunas']);
        $this->get('/pembayarans/'.$payment->id)->assertOk()->assertDontSee('/pembayarans/'.$payment->id.'/edit', false);
    }

    public function test_payment_detail_requires_login_and_payment_view_permission(): void
    {
        $payment = $this->payment();
        $this->get('/pembayarans/'.$payment->id)->assertRedirect('/login');
        $role = Role::create(['name' => 'Other module', 'menu_permissions' => ['keuangan.data_keuangan' => ['view']]]);
        $this->actingAs(User::factory()->create(['role_id' => $role->id]))->getJson('/pembayarans/'.$payment->id)->assertForbidden();
    }
}
