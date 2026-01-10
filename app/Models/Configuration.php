<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Configuration extends Model
{
    protected $connection = 'client';
    protected $table = 'lce_configurations';
    public $timestamps = false;

    protected $fillable = [
        'name',
        'value',
        'state',
    ];

    protected $casts = [
        'state' => 'boolean',
    ];

    /**
     * Get a configuration value by name
     */
    public static function getValue(string $name, $default = null)
    {
        $config = static::where('name', $name)
            ->where('state', 1)
            ->first();

        return $config ? $config->value : $default;
    }

    /**
     * Set a configuration value
     */
    public static function setValue(string $name, $value): void
    {
        static::updateOrCreate(
            ['name' => $name],
            ['value' => $value, 'state' => 1]
        );
    }

    /**
     * Scope to get only active configurations
     */
    public function scopeActive($query)
    {
        return $query->where('state', 1);
    }
}
