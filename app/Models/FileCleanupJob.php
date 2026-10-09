<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FileCleanupJob extends Model
{
    protected $fillable = [
        'path',
        'context',
        'attempts',
        'last_error',
        'last_attempt_at',
    ];

    protected $casts = [
        'attempts' => 'integer',
        'last_attempt_at' => 'datetime',
    ];
}
