<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    protected $fillable = [
        'name',
        'description',
        'menu_access',
        'menu_permissions',
    ];

    protected function casts(): array
    {
        return [
            'menu_access' => 'array',
            'menu_permissions' => 'array',
        ];
    }

    public static function crudActions(): array
    {
        return ['view', 'create', 'update', 'delete'];
    }

    public static function crudActionLabels(): array
    {
        return [
            'view' => 'Lihat',
            'create' => 'Tambah',
            'update' => 'Edit',
            'delete' => 'Hapus',
        ];
    }

    public static function menuActionOptions(): array
    {
        return [
            'dashboard' => ['view'],

            'manajemen_sewa.sewa_kamar' => ['view', 'create', 'update'],
            'manajemen_sewa.data_sewa' => ['view', 'create', 'update', 'delete'],

            'keuangan.data_keuangan' => ['view', 'create', 'update', 'delete'],
            'keuangan.laporan_keuangan' => ['view'],
            'keuangan.laporan_hunian' => ['view'],
            'keuangan.invoice' => ['view', 'create', 'update', 'delete'],

            'master_data.data_penghuni' => ['view', 'create', 'update', 'delete'],
            'master_data.data_kamar' => ['view', 'create', 'update', 'delete'],
            'master_data.data_lantai' => ['view', 'create', 'update', 'delete'],

            'pengaturan.profil_kost' => ['view', 'update'],
            'pengaturan.tipe_kamar' => ['view', 'create', 'update', 'delete'],
            'pengaturan.notifikasi_wa' => ['view', 'update'],

            'manajemen_akses.role' => ['view', 'create', 'update', 'delete'],
            'manajemen_akses.user' => ['view', 'create', 'update', 'delete'],

            'sistem.log_aktivitas' => ['view'],
        ];
    }

    public static function menuGroups(): array
    {
        return [
            'Umum' => [
                'dashboard' => 'Dashboard',
            ],
            'Manajemen Sewa' => [
                'manajemen_sewa.sewa_kamar' => 'Sewa Kamar',
                'manajemen_sewa.data_sewa'  => 'Data Sewa',
            ],
            'Keuangan' => [
                'keuangan.data_keuangan'   => 'Data Keuangan',
                'keuangan.laporan_keuangan'=> 'Laporan Keuangan',
                'keuangan.laporan_hunian'  => 'Laporan Hunian',
                'keuangan.invoice'         => 'Invoice',
            ],
            'Master Data' => [
                'master_data.data_penghuni' => 'Data Penghuni',
                'master_data.data_kamar'    => 'Data Kamar',
                'master_data.data_lantai'   => 'Data Lantai',
            ],
            'Sistem > Pengaturan' => [
                'pengaturan.profil_kost'   => 'Profil Kost',
                'pengaturan.tipe_kamar'    => 'Tipe Kamar',
                'pengaturan.notifikasi_wa' => 'Notifikasi WhatsApp',
            ],
            'Sistem > Manajemen Akses' => [
                'manajemen_akses.role' => 'Role',
                'manajemen_akses.user' => 'User',
            ],
            'Sistem > Log Aktivitas' => [
                'sistem.log_aktivitas' => 'Log Aktivitas',
            ],
        ];
    }

    public static function menuKeys(): array
    {
        return collect(self::menuGroups())
            ->flatMap(fn (array $items) => array_keys($items))
            ->values()
            ->all();
    }

    public static function normalizeMenuPermissions(?array $rawPermissions): array
    {
        $rawPermissions = $rawPermissions ?? [];
        $menuActionOptions = self::menuActionOptions();
        $normalized = [];

        foreach (self::menuKeys() as $menuKey) {
            $actions = $rawPermissions[$menuKey] ?? [];
            if (!is_array($actions)) {
                continue;
            }

            $allowedActions = $menuActionOptions[$menuKey] ?? ['view'];
            $cleaned = array_values(array_unique(array_intersect($allowedActions, array_map('strval', $actions))));
            if (!empty($cleaned)) {
                $normalized[$menuKey] = $cleaned;
            }
        }

        return $normalized;
    }

    public function getMenuLabelsAttribute(): array
    {
        $flat = collect(self::menuGroups())->flatMap(fn (array $items) => $items);

        return collect($this->menu_access ?? [])
            ->filter(fn ($key) => isset($flat[$key]))
            ->map(fn ($key) => $flat[$key])
            ->values()
            ->all();
    }

    public function getMenuCrudSummariesAttribute(): array
    {
        $flatMenus = collect(self::menuGroups())->flatMap(fn (array $items) => $items);
        $actionLabels = self::crudActionLabels();
        $permissions = self::normalizeMenuPermissions($this->menu_permissions ?? []);

        return collect($permissions)
            ->map(function (array $actions, string $menuKey) use ($flatMenus, $actionLabels) {
                $menuLabel = $flatMenus[$menuKey] ?? $menuKey;
                $actionsText = collect($actions)
                    ->map(fn ($action) => $actionLabels[$action] ?? ucfirst($action))
                    ->implode(', ');

                return $menuLabel . ' (' . $actionsText . ')';
            })
            ->values()
            ->all();
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
