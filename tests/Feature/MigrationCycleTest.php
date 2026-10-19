<?php

namespace Tests\Feature;

use App\Models\Kamar;
use App\Models\Penghuni;
use App\Models\Reservasi;
use App\Models\Sewa;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MigrationCycleTest extends TestCase
{
    use DatabaseMigrations;

    public function test_coverage_rollback_preserves_the_lease_foreign_key_and_index(): void
    {
        $migration = require database_path('migrations/2026_10_09_010000_add_billing_coverage_to_pembayarans.php');
        $migration->down();
        $this->assertFalse(Schema::hasColumn('pembayarans', 'coverage_start'));
        $this->assertFalse(Schema::hasColumn('pembayarans', 'coverage_end'));
        $this->assertTrue(Schema::hasForeignKey('pembayarans', ['sewa_id']));
        $this->assertTrue(Schema::hasIndex('pembayarans', ['sewa_id']));
        $migration->up();
        $this->assertTrue(Schema::hasColumn('pembayarans', 'coverage_start'));
        $this->assertTrue(Schema::hasColumn('pembayarans', 'coverage_end'));
    }

    public function test_all_empty_test_schema_migrations_can_roll_back_and_reapply(): void
    {
        $count = DB::table('migrations')->count();
        $this->assertGreaterThan(0, $count);
        $this->assertDatabaseCount('users', 0);
        $this->artisan('migrate:rollback', ['--step' => $count, '--force' => true])->assertSuccessful();
        $this->assertDatabaseCount('migrations', 0);
        foreach (['users', 'kamars', 'penghunis', 'sewas', 'reservasis', 'pembayarans', 'invoices', 'keuangans'] as $table) {
            $this->assertFalse(Schema::hasTable($table), $table.' remained after rollback');
        }

        $this->artisan('migrate', ['--force' => true])->assertSuccessful();
        $this->assertDatabaseCount('migrations', $count);
        foreach (['users', 'kamars', 'penghunis', 'sewas', 'reservasis', 'pembayarans', 'invoices', 'keuangans'] as $table) {
            $this->assertTrue(Schema::hasTable($table), $table.' missing after reapply');
            $this->assertDatabaseCount($table, 0);
        }
        $this->assertTrue(Schema::hasColumn('invoices', 'payment_id'));
        $this->assertTrue(Schema::hasColumn('keuangans', 'payment_id'));
        $this->assertTrue(Schema::hasColumn('reservasis', 'sewa_id'));
    }

    public function test_reservation_status_migration_preserves_legacy_rows_and_refuses_lossy_rollback(): void
    {
        $migration = require database_path('migrations/2026_10_19_090000_expand_reservation_lifecycle_statuses.php');
        $room = Kamar::create(['nomor' => 'ENUM-TEST', 'tipe' => 'Test', 'harga_bulanan' => 550000, 'status' => 'tersedia']);
        $resident = Penghuni::create(['nama' => 'Synthetic enum migration', 'telepon' => '']);
        $ids = [];
        try {
            foreach (['menunggu', 'dikonfirmasi', 'dibatalkan'] as $status) {
                $ids[] = Reservasi::create(['kamar_id' => $room->id, 'penghuni_id' => $resident->id,
                    'tanggal_reservasi' => '2026-10-19', 'rencana_masuk' => '2026-11-01', 'rencana_keluar' => '2026-12-01',
                    'uang_muka' => 125000.25, 'status' => $status])->id;
            }
            $default = Reservasi::create(['kamar_id' => $room->id, 'penghuni_id' => $resident->id,
                'tanggal_reservasi' => '2026-10-19', 'rencana_masuk' => '2027-01-01']);
            $ids[] = $default->id;
            $this->assertSame('menunggu', $default->fresh()->status);
            $history = DB::table('reservasis')->orderBy('id')->get()->map(fn ($row) => (array) $row)->all();
            $migration->down();
            $this->assertSame($history, DB::table('reservasis')->orderBy('id')->get()->map(fn ($row) => (array) $row)->all());
            $migration->up();
            $this->assertSame($history, DB::table('reservasis')->orderBy('id')->get()->map(fn ($row) => (array) $row)->all());
            $lease = Sewa::create(['kamar_id' => $room->id, 'penghuni_id' => $resident->id,
                'tanggal_masuk' => '2026-11-01', 'tanggal_keluar' => '2026-12-01', 'biaya_bulanan' => 550000, 'status' => 'aktif']);
            Reservasi::findOrFail($ids[1])->update(['status' => 'dikonversi', 'sewa_id' => $lease->id]);
            Reservasi::findOrFail($ids[0])->update(['status' => 'kedaluwarsa']);
            $history = DB::table('reservasis')->orderBy('id')->get()->map(fn ($row) => (array) $row)->all();
            try {
                $migration->down();
                $this->fail('Rollback narrowed statuses that are still in use.');
            } catch (\RuntimeException $exception) {
                $this->assertStringContainsString('Rollback status reservasi ditolak', $exception->getMessage());
            }
            $this->assertSame($history, DB::table('reservasis')->orderBy('id')->get()->map(fn ($row) => (array) $row)->all());
            $this->assertSame($lease->id, Reservasi::findOrFail($ids[1])->sewa_id);
            $this->assertTrue(Schema::hasForeignKey('reservasis', ['sewa_id']));
            $this->assertTrue(Schema::hasIndex('reservasis', ['sewa_id'], 'unique'));
        } finally {
            // Only these synthetic rows; allow DatabaseMigrations to tear down its test schema.
            DB::table('reservasis')->whereIn('id', $ids)->delete();
        }
    }
}
