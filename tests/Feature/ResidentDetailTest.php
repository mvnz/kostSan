<?php

namespace Tests\Feature;

use App\Models\Penghuni;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ResidentDetailTest extends TestCase
{
    use RefreshDatabase;

    private Penghuni $resident;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::disk('local')->put('penghuni-dokumen/ktp/detail.png', 'synthetic');
        Storage::disk('local')->put('penghuni-dokumen/selfie/detail.png', 'synthetic');
        $this->resident = Penghuni::create([
            'nama' => '<script>alert(1)</script> Resident',
            'telepon' => '080000000000',
            'nik' => 'SYNTHETIC-ONLY',
            'foto_ktp_path' => 'penghuni-dokumen/ktp/detail.png',
            'foto_selfie_path' => 'penghuni-dokumen/selfie/detail.png',
        ]);
    }

    public function test_view_only_operator_can_open_resident_detail_and_private_documents_without_mutations(): void
    {
        $role = Role::create(['name' => 'Resident viewer', 'menu_permissions' => ['master_data.data_penghuni' => ['view']]]);
        $this->actingAs(User::factory()->create(['role_id' => $role->id]));

        $this->get('/penghunis/'.$this->resident->id)
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertSee('SYNTHETIC-ONLY')
            ->assertSee('Lihat foto KTP')
            ->assertSee('Lihat foto selfie')
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertDontSee('/penghunis/'.$this->resident->id.'/edit', false)
            ->assertDontSee('action="'.route('penghunis.destroy', $this->resident).'"', false);
        $this->get('/secure-files/penghuni-dokumen/ktp/detail.png')->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        $this->getJson('/penghunis/'.$this->resident->id.'/edit')->assertForbidden();
        $this->deleteJson('/penghunis/'.$this->resident->id)->assertForbidden();
    }

    public function test_operator_without_resident_view_cannot_open_detail_or_documents(): void
    {
        $role = Role::create(['name' => 'No resident access', 'menu_permissions' => ['dashboard' => ['view']]]);
        $this->actingAs(User::factory()->create(['role_id' => $role->id]));

        $this->get('/penghunis/'.$this->resident->id)->assertRedirect('/');
        $this->get('/secure-files/penghuni-dokumen/ktp/detail.png')->assertForbidden();
    }
}
