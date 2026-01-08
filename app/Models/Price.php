<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Price extends Model
{
    protected $table = 'lce_prices';

    protected $fillable = [
        'sku',
        'type',
        'name',
        'description',
        'order',
        'deleted',
        // Note: Legacy has price_1 through price_198 columns
        // These are accessed dynamically via getPriceForList()
    ];

    const CREATED_AT = 'cdate';
    const UPDATED_AT = 'mdate';

    /**
     * Get price for a specific price list ID.
     * Legacy uses column names like price_1, price_2, etc.
     */
    public function getPriceForList(int $listId): float
    {
        $column = "price_{$listId}";
        return (float) ($this->$column ?? 0);
    }

    /**
     * Set price for a specific price list ID.
     */
    public function setPriceForList(int $listId, float $price): void
    {
        $column = "price_{$listId}";
        $this->$column = $price;
    }

    public function scopeNotDeleted($query)
    {
        return $query->where('deleted', 'No');
    }

    public function scopeWashFold($query)
    {
        return $query->where('type', 'WF');
    }

    public function scopeDryCleaning($query)
    {
        return $query->where('type', 'DC');
    }
}
