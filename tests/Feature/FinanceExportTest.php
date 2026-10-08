<?php

namespace Tests\Feature;

use App\Models\Keuangan;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceExportTest extends TestCase
{
    use RefreshDatabase;

    private function entry(array $attributes = []): Keuangan
    {
        return Keuangan::create(array_merge(['tanggal' => '2026-10-01', 'jenis' => 'pengeluaran', 'kategori' => 'Internet', 'deskripsi' => 'Sintetis', 'jumlah' => 230000.25], $attributes));
    }

    private function rows(string $path): array
    {
        $response = $this->get($path)->assertOk()->assertHeader('Content-Type', 'text/csv; charset=UTF-8')->assertHeader('Cache-Control', 'no-store, private');
        $content = $response->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);
        $stream = fopen('php://memory', 'w+');
        fwrite($stream, substr($content, 3));
        rewind($stream);
        $rows = [];
        while (($row = fgetcsv($stream, 0, ',', '"', '')) !== false) {
            $rows[] = $row;
        }fclose($stream);

        return $rows;
    }

    public function test_csv_filters_month_and_kind_including_boundaries_and_preserves_decimal_amounts(): void
    {
        $this->actingAs(User::factory()->create());
        $first = $this->entry();
        $last = $this->entry(['tanggal' => '2026-10-31']);
        $this->entry(['tanggal' => '2026-11-01']);
        $this->entry(['tanggal' => '2026-09-30']);
        $this->entry(['jenis' => 'pemasukan']);
        $rows = $this->rows('/keuangans/export?bulan=2026-10&jenis=pengeluaran');
        $this->assertCount(3, $rows);
        $this->assertSame([(string) $first->id, (string) $last->id], array_column(array_slice($rows, 1), 0));
        $this->assertSame('230000.25', $rows[1][5]);
        $this->assertSame('manual', $rows[1][6]);
        $this->assertCount(6, $this->rows('/keuangans/export'));
        $this->assertCount(1, $this->rows('/keuangans/export?bulan=2026-12'));
    }

    public function test_csv_escapes_formula_and_roundtrips_commas_newlines_and_quotes_without_private_paths(): void
    {
        $this->actingAs(User::factory()->create());
        $this->entry(['kategori' => '=SUM(1,2)', 'deskripsi' => "\n @formula, \"quote\"", 'bukti_path' => 'bukti-keuangan/private.pdf']);
        $rows = $this->rows('/keuangans/export');
        $this->assertSame("'=SUM(1,2)", $rows[1][3]);
        $this->assertSame("'\n @formula, \"quote\"", $rows[1][4]);
        $this->assertNotContains('bukti-keuangan/private.pdf', $rows[1]);
    }

    public function test_export_requires_login_and_finance_view_and_rejects_invalid_filters(): void
    {
        $this->get('/keuangans/export')->assertRedirect('/login');
        $role = Role::create(['name' => 'No finance', 'menu_permissions' => ['dashboard' => ['view']]]);
        $this->actingAs(User::factory()->create(['role_id' => $role->id]))->getJson('/keuangans/export')->assertForbidden();
        $this->actingAs(User::factory()->create());
        foreach (['bulan=2026-13', 'bulan=invalid', 'jenis=invalid'] as $query) {
            $this->getJson('/keuangans/export?'.$query)->assertUnprocessable();
        }
        $this->get('/keuangans')->assertOk()->assertSee('Export CSV')->assertSee('id="export-keuangan"', false);
    }

    public function test_export_streams_multiple_chunks_once_without_mutating_entries(): void
    {
        $role = Role::create(['name' => 'Finance view', 'menu_permissions' => ['keuangan.data_keuangan' => ['view']]]);
        $this->actingAs(User::factory()->create(['role_id' => $role->id]));
        for ($i = 0; $i < 205; $i++) {
            $this->entry(['deskripsi' => 'Row '.$i]);
        }
        $rows = $this->rows('/keuangans/export');
        $this->assertCount(206, $rows);
        $this->assertCount(205, array_unique(array_column(array_slice($rows, 1), 0)));
        $this->assertDatabaseCount('keuangans',205);
    }
}
