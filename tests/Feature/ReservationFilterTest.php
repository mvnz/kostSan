<?php

namespace Tests\Feature;

use App\Models\Kamar;
use App\Models\Penghuni;
use App\Models\Reservasi;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReservationFilterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $room = Kamar::create(['nomor' => 'FILTER', 'tipe' => 'A', 'harga_bulanan' => 550000, 'status' => 'tersedia']);
        foreach (['menunggu', 'dikonfirmasi', 'dibatalkan', 'dikonversi', 'kedaluwarsa'] as $status) {
            $resident = Penghuni::create(['nama' => 'Filter '.$status, 'telepon' => '']);
            Reservasi::create([
                'kamar_id' => $room->id,
                'penghuni_id' => $resident->id,
                'tanggal_reservasi' => '2026-10-13',
                'rencana_masuk' => '2026-11-01',
                'status' => $status,
            ]);
        }
        $role = Role::create(['name' => 'Reservation filter viewer', 'menu_permissions' => ['manajemen_sewa.data_sewa' => ['view']]]);
        $this->actingAs(User::factory()->create(['role_id' => $role->id]));
    }

    public function test_each_status_filters_database_results_and_preserves_read_only_access(): void
    {
        foreach (['menunggu', 'dikonfirmasi', 'dibatalkan', 'dikonversi', 'kedaluwarsa'] as $status) {
            $response = $this->get('/reservasis?status='.$status)->assertOk();
            $response->assertViewHas('reservasis', fn ($rows) => $rows->count() === 1 && $rows->first()->status === $status)
                ->assertSee('Filter '.$status)
                ->assertSee('value="'.$status.'" selected', false)
                ->assertDontSee('Tambah Reservasi');
        }
        $this->assertDatabaseCount('reservasis', 5);
    }

    public function test_empty_and_absent_filter_keep_all_history(): void
    {
        foreach (['/reservasis', '/reservasis?status='] as $url) {
            $this->get($url)->assertOk()
                ->assertViewHas('reservasis', fn ($rows) => $rows->count() === 5)
                ->assertSee('Semua status');
        }
    }

    public function test_unknown_and_array_status_are_rejected(): void
    {
        foreach (['invalid', '%27%20OR%201%3D1', '%3Cscript%3E', 'menunggu&status[]=dikonfirmasi'] as $status) {
            $this->getJson('/reservasis?status='.$status)->assertUnprocessable()->assertJsonValidationErrors('status');
        }
        $this->assertDatabaseCount('reservasis', 5);
    }

    public function test_filter_with_no_matches_renders_an_empty_list(): void
    {
        Reservasi::where('status', 'menunggu')->delete();
        $this->get('/reservasis?status=menunggu')->assertOk()
            ->assertViewHas('reservasis', fn ($rows) => $rows->isEmpty());
    }

    public function test_invalid_html_filter_recovers_even_when_previous_url_is_invalid(): void
    {
        $invalidUrl = route('reservasis.index', ['status' => 'invalid']);
        $this->from($invalidUrl)->get($invalidUrl)
            ->assertRedirect(route('reservasis.index'))
            ->assertSessionHasErrors('status');
        $this->get('/reservasis')->assertOk()
            ->assertViewHas('reservasis', fn ($rows) => $rows->count() === 5);
    }

    public function test_filter_does_not_bypass_view_permission(): void
    {
        $role = Role::create(['name' => 'No reservation filter access', 'menu_permissions' => ['dashboard' => ['view']]]);
        $this->actingAs(User::factory()->create(['role_id' => $role->id]));
        $this->getJson('/reservasis?status=menunggu')->assertForbidden();
    }
}
