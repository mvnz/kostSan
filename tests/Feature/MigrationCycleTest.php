<?php

namespace Tests\Feature;

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
}
