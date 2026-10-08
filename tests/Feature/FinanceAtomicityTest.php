<?php

namespace Tests\Feature;

use App\Models\Keuangan;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FinanceAtomicityTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
        Storage::fake('local');
    }

    private function payload(): array
    {
        return ['tanggal' => '2026-10-09', 'jenis' => 'pengeluaran', 'kategori' => 'Internet', 'deskripsi' => 'Sintetis', 'jumlah' => 230000, 'bukti' => UploadedFile::fake()->create('proof.pdf', 10, 'application/pdf')];
    }

    private function entry(): Keuangan
    {
        Storage::disk('local')->put('bukti-keuangan/original.pdf', 'original');

        return Keuangan::create(['tanggal' => '2026-10-09', 'jenis' => 'pengeluaran', 'kategori' => 'Internet', 'deskripsi' => 'Sintetis', 'jumlah' => 230000, 'bukti_path' => 'bukti-keuangan/original.pdf']);
    }

    public function test_failed_insert_cleans_new_upload(): void
    {
        DB::statement("CREATE TRIGGER fail_finance BEFORE INSERT ON keuangans BEGIN SELECT RAISE(ABORT, 'synthetic'); END");
        $this->postJson('/keuangans', $this->payload())->assertStatus(500);
        $this->assertDatabaseCount('keuangans', 0);
        $this->assertSame([], Storage::disk('local')->allFiles('bukti-keuangan'));
    }

    public function test_failed_replacement_preserves_old_proof(): void
    {
        $entry = $this->entry();
        DB::statement("CREATE TRIGGER fail_finance BEFORE UPDATE ON keuangans BEGIN SELECT RAISE(ABORT, 'synthetic'); END");
        $this->putJson('/keuangans/'.$entry->id, $this->payload())->assertStatus(500);
        $this->assertSame(['bukti-keuangan/original.pdf'], Storage::disk('local')->allFiles('bukti-keuangan'));
        Storage::disk('local')->assertExists($entry->bukti_path);
    }

    public function test_failed_removal_and_delete_preserve_old_proof(): void
    {
        $entry = $this->entry();
        DB::statement("CREATE TRIGGER fail_finance BEFORE UPDATE ON keuangans BEGIN SELECT RAISE(ABORT, 'synthetic'); END");
        $data = $this->payload();
        unset($data['bukti']);
        $data['hapus_bukti'] = 1;
        $this->putJson('/keuangans/'.$entry->id, $data)->assertStatus(500);
        Storage::disk('local')->assertExists($entry->bukti_path);
        DB::statement("CREATE TRIGGER fail_delete BEFORE DELETE ON keuangans BEGIN SELECT RAISE(ABORT, 'synthetic'); END");
        $this->deleteJson('/keuangans/'.$entry->id)->assertStatus(500);
        Storage::disk('local')->assertExists($entry->bukti_path);
        $this->assertDatabaseHas('keuangans', ['id' => $entry->id]);
    }

    public function test_successful_replacement_removal_and_delete(): void
    {
        $entry = $this->entry();
        $this->put('/keuangans/'.$entry->id, $this->payload())->assertRedirect();
        Storage::disk('local')->assertMissing('bukti-keuangan/original.pdf');
        $new = $entry->fresh()->bukti_path;
        Storage::disk('local')->assertExists($new);
        $data = $this->payload();
        unset($data['bukti']);
        $data['hapus_bukti'] = 1;
        $this->put('/keuangans/'.$entry->id, $data)->assertRedirect();
        Storage::disk('local')->assertMissing($new);
        $this->assertNull($entry->fresh()->bukti_path);
        $this->put('/keuangans/'.$entry->id, $this->payload())->assertRedirect();
        $new = $entry->fresh()->bukti_path;
        $this->delete('/keuangans/'.$entry->id)->assertRedirect();
        Storage::disk('local')->assertMissing($new);
        $this->assertDatabaseCount('keuangans', 0);
    }
}
