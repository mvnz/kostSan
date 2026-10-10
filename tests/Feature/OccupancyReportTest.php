<?php

namespace Tests\Feature;

use App\Models\Kamar;
use App\Models\Penghuni;
use App\Models\Sewa;
use App\Models\User;
use Carbon\Carbon;
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

    private function historicalRooms(): void
    {
        $resident = Penghuni::create(['nama' => 'Historis sintetis', 'telepon' => '']);
        foreach ([['HISTORY-1', '2026-10-01', '2026-11-15', 'selesai'], ['HISTORY-1', '2026-11-15', '2026-12-01', 'aktif'], ['HISTORY-2', '2026-11-01', '2026-12-01', 'menunggak'], ['HISTORY-3', '2026-10-01', '2026-11-01', 'selesai']] as [$number, $start, $end, $status]) {
            $room = Kamar::firstOrCreate(['nomor' => $number], ['tipe' => 'A', 'harga_bulanan' => 550000, 'status' => 'tersedia']);
            Sewa::create(['kamar_id' => $room->id, 'penghuni_id' => $resident->id, 'tanggal_masuk' => $start, 'tanggal_keluar' => $end, 'biaya_bulanan' => 550000, 'status' => $status]);
        }
    }

    public function test_dashboard_month_counts_unique_occupied_rooms_with_exclusive_checkout(): void
    {
        $this->travelTo(Carbon::parse('2026-11-30 12:00:00'));
        $this->actingAs(User::factory()->create());
        $this->historicalRooms();
        $this->get('/')->assertOk()->assertViewHas('hunianChart', fn ($chart) => end($chart['data']) === 2 && end($chart['pct']) == 67);
        $this->travelBack();
    }

    public function test_history_filter_includes_completed_leases_without_counting_them_twice(): void
    {
        $this->actingAs(User::factory()->create());
        $this->historicalRooms();
        $this->get('/laporan-hunian?tahun=2026')->assertOk()->assertViewHas('bulanData', fn ($months) => $months[9]['terisi'] === 0 && $months[10]['terisi'] === 2);
        $this->get('/laporan-hunian?tahun=2026&cakupan=riwayat')->assertOk()->assertViewHas('cakupan', 'riwayat')->assertViewHas('bulanData', fn ($months) => $months[9]['terisi'] === 2 && $months[10]['terisi'] === 2 && $months[11]['terisi'] === 0);
        $this->getJson('/laporan-hunian?cakupan=invalid')->assertUnprocessable()->assertJsonValidationErrors('cakupan');
    }

    public function test_current_report_and_dashboard_include_overdue_current_occupant_but_not_future_one(): void
    {
        $this->travelTo(Carbon::parse('2026-10-10 12:00:00'));
        $this->actingAs(User::factory()->create());
        foreach ([['OVERDUE-NOW', '2026-10-01'], ['OVERDUE-FUTURE', '2026-11-01']] as [$number, $start]) {
            $resident = Penghuni::create(['nama' => 'Penghuni '.$number, 'telepon' => '']);
            $room = Kamar::create(['nomor' => $number, 'tipe' => 'A', 'harga_bulanan' => 550000, 'status' => 'terisi']);
            Sewa::create(['kamar_id' => $room->id, 'penghuni_id' => $resident->id, 'tanggal_masuk' => $start, 'tanggal_keluar' => $number === 'OVERDUE-NOW' ? '2026-10-15' : null, 'biaya_bulanan' => 550000, 'status' => 'menunggak']);
        }

        $this->get('/laporan-hunian?tahun=2026')
            ->assertOk()
            ->assertSee('Terisi · Menunggak')
            ->assertViewHas('kamars', fn ($rooms) => $rooms->firstWhere('nomor', 'OVERDUE-NOW')->sewas->count() === 1
                && $rooms->firstWhere('nomor', 'OVERDUE-FUTURE')->sewas->isEmpty());
        $this->assertSame('2026-10-10', Carbon::today()->toDateString());
        $this->assertSame(1, Sewa::whereIn('status', ['aktif', 'menunggak'])
            ->whereDate('tanggal_keluar', '>=', Carbon::today())
            ->whereDate('tanggal_keluar', '<=', Carbon::today()->addDays(7))
            ->count());
        $dashboard = $this->get('/');
        $dashboard->assertOk()
            ->assertViewHas('stat', fn ($stat) => $stat['penghuni_aktif'] === 1)
            ->assertSee('Menunggak');
        $expiring = $dashboard->viewData('sewaAkanBerakhir');
        $this->assertCount(1, $expiring);
        $this->assertSame('menunggak', $expiring->first()->status);
        $this->travelBack();
    }

    public function test_history_excludes_missing_checkout_and_invalid_intervals_but_keeps_open_active_lease(): void
    {
        $this->actingAs(User::factory()->create());
        $resident = Penghuni::create(['nama' => 'Batas sintetis', 'telepon' => '']);
        foreach ([['selesai', null], ['aktif', '2026-01-01'], ['selesai', '2025-12-31'], ['aktif', null]] as $i => [$status, $end]) {
            $room = Kamar::create(['nomor' => 'EDGE-'.$i, 'tipe' => 'A', 'harga_bulanan' => 550000, 'status' => 'tersedia']);
            Sewa::create(['kamar_id' => $room->id, 'penghuni_id' => $resident->id, 'tanggal_masuk' => '2026-01-01', 'tanggal_keluar' => $end, 'biaya_bulanan' => 550000, 'status' => $status]);
        }
        $this->get('/laporan-hunian?tahun=2026&cakupan=riwayat')->assertOk()->assertViewHas('bulanData', fn ($months) => collect($months)->every(fn ($month) => $month['terisi'] === 1 && $month['pct'] === 25.0));
        $this->get('/laporan-hunian?tahun=2020&cakupan=riwayat')->assertOk()->assertViewHas('tahunList', fn ($years) => in_array(2020, $years, true))->assertViewHas('bulanData', fn ($months) => collect($months)->sum('terisi') === 0);
    }
}
