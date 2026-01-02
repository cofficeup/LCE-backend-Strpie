<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProcessingSite extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'wash_fold_enabled',
        'dry_clean_enabled',
        'daily_capacity_lbs',
        'address_line1',
        'address_line2',
        'city',
        'state',
        'zip_code',
        'served_area_codes',
        'active'
    ];

    /**
     * Check if site serves a specific area code.
     */
    public function servesArea(string $areaCode): bool
    {
        if (empty($this->served_area_codes)) return true; // Serves all if empty? Or serves none? Let's assume specific routing required.

        $areas = array_map('trim', explode(',', $this->served_area_codes));
        return in_array($areaCode, $areas);
    }
}
