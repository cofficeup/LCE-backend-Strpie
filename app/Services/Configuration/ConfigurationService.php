<?php

namespace App\Services\Configuration;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class ConfigurationService
{
    /**
     * Get a configuration value by key.
     */
    public function get(string $key, $default = null)
    {
        return Cache::remember("config_{$key}", 3600, function () use ($key, $default) {
            $value = DB::table('lce_configurations')->where('key', $key)->value('value');
            return $value ?? $default;
        });
    }

    /**
     * Get configuration value as float.
     */
    public function getFloat(string $key, float $default = 0.0): float
    {
        return (float) $this->get($key, $default);
    }

    /**
     * Set a configuration value.
     */
    public function set(string $key, string $value): void
    {
        DB::table('lce_configurations')->updateOrInsert(
            ['key' => $key],
            ['value' => $value, 'updated_at' => now()]
        );
        Cache::forget("config_{$key}");
    }
}
