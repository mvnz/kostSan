<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use League\Flysystem\UnableToWriteFile;

class PrivateUpload
{
    public function store(UploadedFile $file, string $directory, string $field): string
    {
        try {
            $path = $file->store($directory, 'local');
        } catch (UnableToWriteFile $exception) {
            $path = false;
        }
        if (! is_string($path) || $path === '') {
            throw ValidationException::withMessages([
                $field => 'Berkas belum berhasil disimpan. Transaksi belum diselesaikan; silakan unggah ulang atau hubungi pengelola.',
            ]);
        }

        return $path;
    }
}
