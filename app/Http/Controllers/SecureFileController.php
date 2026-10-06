<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SecureFileController extends Controller
{
    // Hanya path di bawah prefix ini yang boleh diakses, cegah path traversal ke file lain di disk lokal.
    private const PREFIX_PERMISSIONS = [
        'bukti-keuangan/' => 'keuangan.data_keuangan',
        'bukti-pembayaran-sewa/' => 'manajemen_sewa.data_sewa',
        'bukti-sewa/' => 'manajemen_sewa.data_sewa',
        'penghuni-dokumen/ktp/' => 'master_data.data_penghuni',
        'penghuni-dokumen/selfie/' => 'master_data.data_penghuni',
    ];

    public function show(Request $request, string $path)
    {
        $path = ltrim($path, '/');

        if (str_contains($path, '..')) {
            abort(404);
        }

        $permission = collect(self::PREFIX_PERMISSIONS)->first(fn ($menu, $prefix) => str_starts_with($path, $prefix));

        if (! $permission) {
            abort(404);
        }

        abort_unless($request->user()?->hasMenuPermission($permission, 'view'), 403);
        abort_unless(Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, null, ['Cache-Control' => 'no-store, private']);
    }
}
