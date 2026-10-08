<?php

namespace Tests\Feature;

use App\Models\Kamar;
use App\Models\Penghuni;
use App\Models\Sewa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OccupancyReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_month_counts_distinct_rooms_and_excludes_checkout_boundary(): void
    {
        $this->actingAs(User::factory()->create());
        $resident = Penghuni::create(['nama' => 'Sintetis', 'telepon' => '']);
        $room = Kamar::create(['nomor' => 'HUNIAN-1', 'tipe' => 'A', 'harga_bulanan' => 550000, 'status' => 'terisi']);
        $other = Kamar::create(['nomor' => 'HUNIAN-2', 'tipe' => 'A', 'harga_bulanan' => 550000, 'status' => 'tersedia']);
        foreach ([[$room, '2026-01-01', '2026-01-15'], [$room, '2026-01-15', '2026-02-01'], [$other, '2025-12-01', '2026-01-01']] as [$r,$start,$end]) {
            Sewa::create(['kamar_id' => $r->id, 'penghuni_id' => $resident->id, 'tanggal_masuk' => $start, 'tanggal_keluar' => $end, 'biaya_bulanan' => 550000, 'status' => 'aktif']);
        }
        $this->get('/laporan-hunian?tahun=2026')->assertOk()->assertViewHas('bulanData', fn ($months) => $months[0]['terisi'] === 1 && $months[0]['pct'] === 50.0 && $months[1]['terisi'] === 0);
    }

    public function test_invalid_year_is_rejected_and_empty_inventory_has_zero_occupancy(): void
    {
        $this->actingAs(User::factory()->create());
        foreach (['nonsense', '1899', '10000', '2026.5'] as $year) {
            $this->getJson('/laporan-hunian?tahun='.$year)->assertUnprocessable()->assertJsonValidationErrors('tahun');
        }
        $this->get('/laporan-hunian?tahun=2026')->assertOk()->assertViewHas('bulanData', fn ($months) => count($months) === 12 && collect($months)->sum('terisi') === 0 && collect($months)->sum('pct') == 0);
    }
}
