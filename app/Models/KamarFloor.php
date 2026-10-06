<?php

namespace App\Models;

use App\Models\Kamar;
use Illuminate\Database\Eloquent\Model;

class KamarFloor extends Model
{
    protected $fillable = [
        'number',
        'name',
    ];

    protected function casts(): array
    {
        return [
            'number' => 'integer',
        ];
    }

    public static function syncFromKamars(): void
    {
        if (!self::query()->exists()) {
            self::create([
                'number' => 1,
                'name' => 'Lantai 1',
            ]);
        }

        $usedFloors = Kamar::query()
            ->whereNotNull('layout_floor')
            ->where('layout_floor', '>', 0)
            ->pluck('layout_floor')
            ->map(fn ($n) => (int) $n)
            ->unique()
            ->values()
            ->all();

        $numbers = collect($usedFloors)
            ->filter(fn ($n) => $n >= 1 && $n <= 255)
            ->unique()
            ->sort()
            ->values();

        foreach ($numbers as $number) {
            self::firstOrCreate(
                ['number' => $number],
                ['name' => 'Lantai ' . $number]
            );
        }
    }
}
