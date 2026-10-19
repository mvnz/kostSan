<?php

namespace Tests\Feature;

use Illuminate\Database\MySqlConnection;
use Illuminate\Database\Schema\MySqlBuilder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

class MySqlReservationPreflightTest extends TestCase
{
    public function test_sqlite_is_not_accepted_as_mysql_evidence_and_is_not_queried(): void
    {
        $this->app['db']->connection()->beforeExecuting(function (): void {
            throw new \RuntimeException('Unsupported driver must not be queried.');
        });
        $this->assertSame(1, Artisan::call('kost:check-mysql-reservations'));
        $this->assertStringContainsString('memerlukan koneksi MySQL', Artisan::output());
    }

    public function test_metadata_failure_is_actionable_without_leaking_exception_details(): void
    {
        $connection = Mockery::mock(MySqlConnection::class);
        $connection->shouldReceive('getDriverName')->once()->andReturn('mysql');
        $connection->shouldReceive('getSchemaBuilder')->once()->andThrow(new \RuntimeException('synthetic-password private-host private-database /private-path'));
        DB::shouldReceive('connection')->once()->andReturn($connection);

        $this->assertSame(1, Artisan::call('kost:check-mysql-reservations'));
        $output = Artisan::output();
        $this->assertStringContainsString('Tidak dapat membaca metadata', $output);
        foreach (['synthetic-password', 'private-host', 'private-database', '/private-path'] as $secret) {
            $this->assertStringNotContainsString($secret, $output);
        }
    }

    public function test_missing_status_column_is_rejected_without_creating_it(): void
    {
        $builder = Mockery::mock(MySqlBuilder::class);
        $builder->shouldReceive('getColumns')->once()->with('reservasis')->andReturn([]);
        $connection = Mockery::mock(MySqlConnection::class);
        $connection->shouldReceive('getDriverName')->once()->andReturn('mysql');
        $connection->shouldReceive('getSchemaBuilder')->once()->andReturn($builder);
        DB::shouldReceive('connection')->once()->andReturn($connection);

        $this->assertSame(1, Artisan::call('kost:check-mysql-reservations'));
        $this->assertStringContainsString('Kolom reservasis.status tidak ditemukan', Artisan::output());
    }
}
