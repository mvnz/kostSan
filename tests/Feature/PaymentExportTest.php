<?php

namespace Tests\Feature;

use App\Models\Kamar;
use App\Models\Pembayaran;
use App\Models\Penghuni;
use App\Models\Role;
use App\Models\Sewa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_export_matches_filters_and_escapes_spreadsheet_formulas(): void
    {
        $this->actingAs(User::factory()->create());
        $room = Kamar::create(['nomor' => 'A-01', 'tipe' => 'A', 'harga_bulanan' => 1000000, 'status' => 'terisi']);
        $resident = Penghuni::create(['nama' => ' =1+1', 'telepon' => '']);
        $lease = Sewa::create(['kamar_id' => $room->id, 'penghuni_id' => $resident->id, 'tanggal_masuk' => '2026-10-01', 'biaya_bulanan' => 1000000, 'status' => 'aktif']);
        foreach ([['2026-10-01', 'belum_lunas'], ['2026-10-15', 'lunas'], ['2026-11-01', 'belum_lunas']] as [$period, $status]) {
            Pembayaran::create(['sewa_id' => $lease->id, 'periode' => $period, 'metode' => 'cash', 'jumlah' => 1000000, 'status' => $status]);
        }

        $response = $this->get('/pembayarans/export?bulan=2026-10&status=belum_lunas');
        $response->assertOk()->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $csv = $response->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $lines = explode("\n", trim(substr($csv, 3)));
        $this->assertCount(2, $lines);
        $row = str_getcsv($lines[1], ',', '"', '');
        $this->assertSame('A-01', $row[1]);
        $this->assertSame("' =1+1", $row[2]);
        $this->assertSame('2026-10-01', $row[3]);
        $this->assertSame('1000000.00', $row[6]);
        $this->assertSame('belum_lunas', $row[7]);

        $this->get('/pembayarans?bulan=2026-10&status=belum_lunas')->assertOk()->assertViewHas('pembayarans', fn ($payments) => $payments->count() === 1);
    }

    public function test_export_requires_payment_view_permission_and_login(): void
    {
        $this->get('/pembayarans/export')->assertRedirect('/login');
        $role = Role::create(['name' => 'Tanpa Pembayaran', 'menu_permissions' => ['dashboard' => ['view']]]);
        $user = User::factory()->create(['role_id' => $role->id]);
        $this->actingAs($user)->getJson('/pembayarans/export')->assertForbidden();
        $role->update(['menu_permissions' => ['manajemen_sewa.data_sewa' => ['view']]]);
        $this->actingAs($user->fresh())->get('/pembayarans/export')->assertOk();
    }

    public function test_invalid_export_filters_return_validation_errors(): void
    {
        $this->actingAs(User::factory()->create());
        $this->getJson('/pembayarans/export?bulan=2026-99&status=invalid')->assertUnprocessable()->assertJsonValidationErrors(['bulan', 'status']);
    }
}
