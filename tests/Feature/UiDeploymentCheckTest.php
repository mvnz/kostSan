<?php

namespace Tests\Feature;

use Illuminate\Filesystem\Filesystem;
use Tests\TestCase;

class UiDeploymentCheckTest extends TestCase
{
    private string $publicDirectory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->publicDirectory = sys_get_temp_dir().'/kostsan-ui-check-'.bin2hex(random_bytes(12));
        mkdir($this->publicDirectory.'/ui', 0700, true);
        $this->app->usePublicPath($this->publicDirectory);
        // Any accidental query fails; this command must work without a database.
        $this->app['db']->connection()->beforeExecuting(function () {
            throw new \RuntimeException('UI package check must not query the database.');
        });
    }

    protected function tearDown(): void
    {
        try {
            (new Filesystem)->deleteDirectory($this->publicDirectory);
        } finally {
            parent::tearDown();
        }
    }

    public function test_missing_package_lists_every_required_file_and_build_recovery(): void
    {
        $this->artisan('kost:check-ui')
            ->expectsOutputToContain('ui/helpers.js')
            ->expectsOutputToContain('ui/core.js')
            ->expectsOutputToContain('ui/LICENSES.txt')
            ->expectsOutputToContain('npm ci --ignore-scripts && npm run build')
            ->assertFailed();
        $this->assertSame([], (new Filesystem)->files($this->publicDirectory.'/ui'));
    }

    public function test_partial_package_is_rejected_without_modifying_existing_files(): void
    {
        file_put_contents($this->publicDirectory.'/ui/helpers.js', 'synthetic helpers');
        $this->artisan('kost:check-ui')->expectsOutputToContain('ui/core.js')->assertFailed();
        $this->assertSame('synthetic helpers', file_get_contents($this->publicDirectory.'/ui/helpers.js'));
        $this->assertFileDoesNotExist($this->publicDirectory.'/ui/core.js');
    }

    public function test_empty_file_and_directory_instead_of_file_are_rejected(): void
    {
        file_put_contents($this->publicDirectory.'/ui/helpers.js', '');
        mkdir($this->publicDirectory.'/ui/core.js');
        file_put_contents($this->publicDirectory.'/ui/LICENSES.txt', 'synthetic license');
        $this->artisan('kost:check-ui')
            ->expectsOutputToContain('ui/helpers.js')
            ->expectsOutputToContain('ui/core.js')
            ->assertFailed();
    }

    public function test_complete_nonempty_package_passes_without_database_or_writes(): void
    {
        foreach (['helpers.js', 'core.js', 'LICENSES.txt'] as $file) {
            file_put_contents($this->publicDirectory.'/ui/'.$file, 'synthetic '.$file);
        }
        $paths = glob($this->publicDirectory.'/ui/*');
        $before = array_map(fn ($path) => hash_file('sha256', $path), $paths);
        $this->artisan('kost:check-ui')->expectsOutputToContain('Paket UI lengkap')->assertSuccessful();
        $this->assertSame($before, array_map(fn ($path) => hash_file('sha256', $path), $paths));
    }
}
