<?php

namespace Tests\Feature;

use App\Models\Penghuni;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class ResidentDocumentCleanupTest extends TestCase
{
    use RefreshDatabase;

    private Penghuni $resident;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
        Storage::fake('local');
        Storage::disk('local')->put('penghuni-dokumen/ktp/synthetic.png', 'ktp');
        Storage::disk('local')->put('penghuni-dokumen/selfie/synthetic.png', 'selfie');
        $this->resident = Penghuni::create([
            'nama' => 'Resident Document Synthetic',
            'telepon' => '080000000000',
            'foto_ktp_path' => 'penghuni-dokumen/ktp/synthetic.png',
            'foto_selfie_path' => 'penghuni-dokumen/selfie/synthetic.png',
        ]);
    }

    public function test_successful_resident_delete_removes_private_documents_after_commit(): void
    {
        $this->delete('/penghunis/'.$this->resident->id)->assertRedirect('/penghunis');

        $this->assertDatabaseMissing('penghunis', ['id' => $this->resident->id]);
        Storage::disk('local')->assertMissing('penghuni-dokumen/ktp/synthetic.png');
        Storage::disk('local')->assertMissing('penghuni-dokumen/selfie/synthetic.png');
        $this->assertDatabaseCount('file_cleanup_jobs', 0);
    }

    public function test_resident_delete_queues_each_document_when_storage_cleanup_fails(): void
    {
        $disk = Mockery::mock();
        $disk->shouldReceive('delete')->once()->with('penghuni-dokumen/ktp/synthetic.png')->andReturn(false);
        $disk->shouldReceive('delete')->once()->with('penghuni-dokumen/selfie/synthetic.png')->andReturn(false);
        Storage::shouldReceive('disk')->twice()->with('local')->andReturn($disk);

        $this->delete('/penghunis/'.$this->resident->id)->assertRedirect('/penghunis');

        $this->assertDatabaseMissing('penghunis', ['id' => $this->resident->id]);
        $this->assertDatabaseHas('file_cleanup_jobs', ['path' => 'penghuni-dokumen/ktp/synthetic.png', 'context' => 'resident delete', 'attempts' => 1]);
        $this->assertDatabaseHas('file_cleanup_jobs', ['path' => 'penghuni-dokumen/selfie/synthetic.png', 'context' => 'resident delete', 'attempts' => 1]);
    }

    public function test_failed_resident_delete_preserves_database_and_private_documents(): void
    {
        DB::statement("CREATE TRIGGER fail_resident_delete BEFORE DELETE ON penghunis BEGIN SELECT RAISE(ABORT, 'synthetic'); END");

        $this->delete('/penghunis/'.$this->resident->id)
            ->assertRedirect('/penghunis')
            ->assertSessionHas('error');

        $this->assertDatabaseHas('penghunis', ['id' => $this->resident->id]);
        Storage::disk('local')->assertExists('penghuni-dokumen/ktp/synthetic.png');
        Storage::disk('local')->assertExists('penghuni-dokumen/selfie/synthetic.png');
        $this->assertDatabaseCount('file_cleanup_jobs', 0);
    }
}
