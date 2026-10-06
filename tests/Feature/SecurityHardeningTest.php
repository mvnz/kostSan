<?php

namespace Tests\Feature;

use App\Models\Kamar;
use App\Models\KamarFloor;
use App\Models\PenghuniRegistrationLink;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_security_headers_are_present_on_responses(): void
    {
        $response = $this->get('/login');

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    public function test_login_is_rate_limited_after_too_many_failed_attempts(): void
    {
        User::factory()->create([
            'email' => 'admin@example.test',
            'password' => Hash::make('correct-password'),
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', [
                'email' => 'admin@example.test',
                'password' => 'wrong-password',
            ]);
        }

        $response = $this->post('/login', [
            'email' => 'admin@example.test',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(429);
    }

    public function test_login_rejects_sql_injection_style_payload_without_error(): void
    {
        User::factory()->create([
            'email' => 'admin2@example.test',
            'password' => Hash::make('correct-password'),
        ]);

        $response = $this->post('/login', [
            'email' => "admin2@example.test' OR '1'='1",
            'password' => "' OR '1'='1",
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_mass_assignment_cannot_spoof_primary_key(): void
    {
        $kamar = Kamar::create([
            'id' => 999999,
            'nomor' => 'T-1',
            'tipe' => 'Standar',
            'harga_bulanan' => 500000,
            'status' => 'tersedia',
        ]);

        $this->assertNotEquals(999999, $kamar->id);
    }

    public function test_public_registration_link_route_is_rate_limited(): void
    {
        $link = PenghuniRegistrationLink::create([
            'token' => Str::random(64),
            'expires_at' => now()->addDays(7),
        ]);

        for ($i = 0; $i < 20; $i++) {
            $this->get('/pendaftaran/penghuni/' . $link->token);
        }

        $response = $this->get('/pendaftaran/penghuni/' . $link->token);

        $response->assertStatus(429);
    }

    public function test_expired_or_invalid_registration_token_does_not_leak_data(): void
    {
        $response = $this->get('/pendaftaran/penghuni/' . Str::random(64));

        $response->assertOk();
        $response->assertViewIs('penghuni-registrations.expired');
    }

    public function test_sensitive_uploads_are_not_publicly_accessible(): void
    {
        \Illuminate\Support\Facades\Storage::fake('local');
        \Illuminate\Support\Facades\Storage::fake('public');

        \Illuminate\Support\Facades\Storage::disk('local')->put('bukti-keuangan/contoh.jpg', 'isi-file-rahasia');

        // Tidak login: akses file rahasia harus ditolak (redirect ke login), bukan tampil.
        $response = $this->get('/secure-files/bukti-keuangan/contoh.jpg');
        $response->assertRedirect('/login');

        // File tidak boleh nyangkut di disk publik yang bisa diakses tanpa auth.
        \Illuminate\Support\Facades\Storage::disk('public')->assertMissing('bukti-keuangan/contoh.jpg');
    }

    public function test_secure_file_route_blocks_path_traversal(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/secure-files/bukti-keuangan/../../../.env');

        $response->assertNotFound();
    }
}
