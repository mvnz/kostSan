<?php

namespace App\Services;

use App\Models\FileCleanupJob;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class PrivateFileCleanup
{
    public function deleteOrQueue(?string $path, string $context): bool
    {
        if (! is_string($path) || trim($path) === '') {
            return true;
        }

        $error = 'Storage delete returned false.';
        try {
            if (Storage::disk('local')->delete($path)) {
                FileCleanupJob::where('path', $path)->delete();

                return true;
            }
        } catch (Throwable $exception) {
            $error = class_basename($exception).': '.$exception->getMessage();
        }

        try {
            $job = FileCleanupJob::firstOrCreate(['path' => $path], [
                'context' => Str::limit($context, 255, ''),
                'attempts' => 0,
            ]);
            $job->forceFill([
                'context' => Str::limit($context, 255, ''),
                'last_error' => Str::limit($error, 1000),
                'last_attempt_at' => now(),
            ])->save();
            $job->increment('attempts');
        } catch (Throwable $queueException) {
            report($queueException);
        }

        return false;
    }
}
