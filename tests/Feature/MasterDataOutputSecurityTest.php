<?php

namespace Tests\Feature;

use App\Models\KamarFloor;
use App\Models\KamarTipeHarga;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterDataOutputSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    public function test_floor_delete_confirmation_does_not_embed_name_in_javascript(): void
    {
        KamarFloor::create([
            'number' => 99,
            'name' => "Lantai ');alert(1);// <script>window.floorXss=1</script>",
        ]);

        $this->get('/master-data/lantai')
            ->assertOk()
            ->assertSee('onsubmit="return confirm(this.dataset.confirmMessage)"', false)
            ->assertSee('data-confirm-message="Hapus Lantai &#039;);alert(1);// &lt;script&gt;window.floorXss=1&lt;/script&gt;?"', false)
            ->assertDontSee('<script>window.floorXss', false);
    }

    public function test_room_type_delete_confirmation_does_not_embed_name_in_javascript(): void
    {
        KamarTipeHarga::create([
            'tipe' => "Tipe ');alert(2);// <script>window.typeXss=1</script>",
            'harga_1_bulan' => 500000,
            'harga_3_bulan' => 1400000,
            'harga_6_bulan' => 2700000,
            'harga_12_bulan' => 5200000,
        ]);

        $this->get('/pengaturan/tipe-kamar')
            ->assertOk()
            ->assertSee('onsubmit="return confirm(this.dataset.confirmMessage)"', false)
            ->assertSee('data-confirm-message="Hapus tipe Tipe &#039;);alert(2);// &lt;script&gt;window.typeXss=1&lt;/script&gt;?"', false)
            ->assertDontSee('<script>window.typeXss', false);
    }
}
