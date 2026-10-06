<?php

namespace Tests\Feature;

use App\Models\Kamar;
use App\Models\Pembayaran;
use App\Models\Penghuni;
use App\Models\Sewa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PaymentSearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        $this->actingAs(User::factory()->create());
    }

    private function payment(string $name, string $roomNumber, array $attributes = []): Pembayaran
    {
        $room = Kamar::create(['nomor' => $roomNumber, 'tipe' => 'A', 'harga_bulanan' => 1000000, 'status' => 'terisi']);
        $resident = Penghuni::create(['nama' => $name, 'telepon' => '']);
        $lease = Sewa::create([
            'kamar_id' => $room->id, 'penghuni_id' => $resident->id,
            'tanggal_masuk' => '2026-10-01', 'biaya_bulanan' => 1000000, 'status' => 'aktif',
        ]);

        return Pembayaran::create(array_merge([
            'sewa_id' => $lease->id, 'periode' => '2026-10-01',
            'metode' => 'transfer', 'jumlah' => 1000000, 'status' => 'belum_lunas',
        ], $attributes));
    }

    public function test_name_or_room_search_combines_with_all_other_filters_and_csv(): void
    {
        $matching = $this->payment('Nadia Putri', 'A-01');
        $this->payment('Penghuni Lain', 'NADIA-02', ['metode' => 'cash']);
        $this->payment('Nadia Lunas', 'A-03', ['status' => 'lunas']);
        $this->payment('Nadia November', 'A-04', ['periode' => '2026-11-01']);
        $this->payment('Budi Santoso', 'A-05');
        $filters = ['q' => 'Nadia', 'bulan' => '2026-10', 'status' => 'belum_lunas', 'metode' => 'transfer'];

        $response = $this->get('/pembayarans?'.http_build_query($filters));
        $response->assertOk()->assertViewHas('pembayarans', fn ($rows) => $rows->modelKeys() === [$matching->id]);
        $response->assertSee('name="q"', false)->assertSee('name="metode"', false);
        $response->assertSee(route('pembayarans.export', $filters));
        $csv = $this->get('/pembayarans/export?'.http_build_query($filters))->assertOk()->streamedContent();
        $lines = explode("\n", trim(substr($csv, 3)));
        $this->assertCount(2, $lines);
        $this->assertSame((string) $matching->id, str_getcsv($lines[1], ',', '"', '')[0]);
        $this->assertDatabaseCount('pembayarans', 5);
    }

    public function test_search_finds_room_numbers_without_matching_resident_names(): void
    {
        $matching = $this->payment('Siti', 'B-204');
        $this->payment('Nadia', 'A-102');
        $this->get('/pembayarans?q=B-204')->assertOk()
            ->assertViewHas('pembayarans', fn ($rows) => $rows->modelKeys() === [$matching->id]);
    }

    public function test_search_treats_sql_wildcards_and_escape_characters_as_literal_text(): void
    {
        $matching = $this->payment('Diskon 50%_! Uji', 'C-01');
        $this->payment('Diskon 50XYZ Uji', 'C-02');
        $this->get('/pembayarans?'.http_build_query(['q' => '50%_!']))->assertOk()
            ->assertViewHas('pembayarans', fn ($rows) => $rows->modelKeys() === [$matching->id]);
        $this->get('/pembayarans?'.http_build_query(['q' => "' OR 1=1 --"]))->assertOk()
            ->assertViewHas('pembayarans', fn ($rows) => $rows->isEmpty());
    }

    public function test_zero_and_whitespace_search_have_predictable_results(): void
    {
        $matching = $this->payment('Siti', '0');
        $this->payment('Budi', 'X');
        $this->get('/pembayarans?'.http_build_query(['q' => ' 0 ']))->assertOk()
            ->assertViewHas('pembayarans', fn ($rows) => $rows->modelKeys() === [$matching->id]);
        $this->get('/pembayarans?'.http_build_query(['q' => '   ']))->assertOk()
            ->assertViewHas('pembayarans', fn ($rows) => $rows->count() === 2);
    }

    public function test_summary_separates_paid_and_unpaid_amounts_for_the_filtered_resident(): void
    {
        $this->payment('Nadia', 'D-01', ['status' => 'lunas', 'jumlah' => 1100000]);
        $this->payment('Nadia', 'D-02', ['jumlah' => 750000]);
        $this->payment('Budi', 'D-03', ['jumlah' => 9000000]);
        $response = $this->get('/pembayarans?q=Nadia')->assertOk();
        $response->assertSee('Rp 1.100.000')->assertSee('Rp 750.000')->assertSee('Rp 1.850.000');
        $response->assertDontSee('Rp 9.000.000');
    }

    public function test_invalid_search_and_method_filters_are_rejected_on_list_and_export(): void
    {
        foreach (['/pembayarans', '/pembayarans/export'] as $path) {
            $this->getJson($path.'?'.http_build_query(['q' => str_repeat('x', 101), 'metode' => 'invalid']))
                ->assertUnprocessable()->assertJsonValidationErrors(['q', 'metode']);
            $this->getJson($path.'?q[]=invalid')->assertUnprocessable()->assertJsonValidationErrors(['q']);
        }
    }
}
