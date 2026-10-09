<?php

namespace Tests\Feature;

use App\Models\Kamar;
use App\Models\Penghuni;
use App\Models\Role;
use App\Models\Sewa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LeaseDetailTest extends TestCase
{
    use RefreshDatabase;

    private Sewa $lease;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::disk('local')->put('bukti-sewa/private-proof.pdf', 'synthetic');
        $room = Kamar::create(['nomor' => 'LEASE-DETAIL', 'tipe' => 'A', 'harga_bulanan' => 550000, 'status' => 'terisi']);
        $resident = Penghuni::create(['nama' => 'Lease Detail Synthetic', 'telepon' => '080000000000']);
        $this->lease = Sewa::create([
            'kamar_id' => $room->id,
            'penghuni_id' => $resident->id,
            'tanggal_masuk' => '2026-10-01',
            'tanggal_keluar' => '2026-11-01',
            'biaya_bulanan' => 550000,
            'status' => 'aktif',
            'bukti_pembayaran' => 'bukti-sewa/private-proof.pdf',
        ]);
    }

    public function test_view_only_operator_can_trace_private_proof_without_mutation_actions(): void
    {
        $role = Role::create(['name' => 'Lease viewer', 'menu_permissions' => ['manajemen_sewa.data_sewa' => ['view']]]);
        $user = User::factory()->create(['role_id' => $role->id]);

        $this->actingAs($user)->get('/sewas/'.$this->lease->id)
            ->assertOk()
            ->assertSee('Lihat bukti privat')
            ->assertSee('secure-files/bukti-sewa/private-proof.pdf', false)
            ->assertDontSee('/sewas/'.$this->lease->id.'/edit', false)
            ->assertDontSee('action="'.route('sewas.destroy', $this->lease).'"', false);
        $this->get('/secure-files/bukti-sewa/private-proof.pdf')
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private');
        $this->putJson('/sewas/'.$this->lease->id, [])->assertForbidden();
        $this->deleteJson('/sewas/'.$this->lease->id)->assertForbidden();
    }

    public function test_operator_without_lease_view_permission_cannot_open_detail_or_proof(): void
    {
        $role = Role::create(['name' => 'No lease access', 'menu_permissions' => ['dashboard' => ['view']]]);
        $this->actingAs(User::factory()->create(['role_id' => $role->id]));

        $this->get('/sewas/'.$this->lease->id)->assertRedirect('/');
        $this->get('/secure-files/bukti-sewa/private-proof.pdf')->assertForbidden();
    }
}
