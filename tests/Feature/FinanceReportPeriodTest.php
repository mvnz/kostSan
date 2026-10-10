<?php

namespace Tests\Feature;

use App\Models\Keuangan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceReportPeriodTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_report_selects_february_on_day_31_without_rolling_into_march(): void
    {
        $this->actingAs(User::factory()->create());
        Carbon::setTestNow('2026-01-31 12:00:00');
        foreach (['2026-02-01' => 100.25, '2026-02-28' => 200, '2026-03-01' => 999] as $date => $amount) {
            Keuangan::create(['tanggal' => $date, 'jenis' => 'pemasukan', 'kategori' => 'Sewa Kamar', 'deskripsi' => 'Sintetis', 'jumlah' => $amount]);
        }
        $this->get('/laporan-keuangan?bulan=2026-02')->assertOk()->assertViewHas('totalPemasukan', fn ($total) => (float) $total === 300.25)->assertViewHas('items', fn ($rows) => $rows->count() === 2);
        $this->get('/laporan-keuangan/pdf?bulan=2026-02')->assertOk()->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_html_and_pdf_reject_invalid_month_and_blank_defaults_to_current_month(): void
    {
        $this->actingAs(User::factory()->create());
        Carbon::setTestNow('2026-01-31 12:00:00');
        foreach (['/laporan-keuangan', '/laporan-keuangan/pdf'] as $path) {
            foreach (['2026-13', 'garbage', '2026-02-01'] as $month) {
                $this->getJson($path.'?bulan='.$month)->assertUnprocessable()->assertJsonValidationErrors('bulan');
            }
        }
        $this->get('/laporan-keuangan?bulan=')->assertOk()->assertViewHas('bulan', '2026-01');
    }
}
