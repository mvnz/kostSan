<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;

class SecureFileController extends Controller
{
    // Hanya path di bawah prefix ini yang boleh diakses, cegah path traversal ke file lain di disk lokal.
    private const ALLOWED_PREFIXES = [
        'bukti-keuangan/',
        'bukti-pembayaran-sewa/',
        'bukti-sewa/',
        'penghuni-dokumen/ktp/',
        'penghuni-dokumen/selfie/',
    ];

    public function show(string $path)
    {
        $path = ltrim($path, '/');

        if (str_contains($path, '..')) {
            abort(404);
        }

        $allowed = collect(self::ALLOWED_PREFIXES)->contains(fn ($prefix) => str_starts_with($path, $prefix));

        if (!$allowed || !Storage::disk('local')->exists($path)) {
            abort(404);
        }

        return Storage::disk('local')->response($path);
    }
}
