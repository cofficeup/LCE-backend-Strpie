<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PriceList extends Model
{
    protected $connection = 'client';
    protected $table = 'lce_prices_lists';

    protected $fillable = [
        'type',
        'name',
        'description',
        'zip',
        'order',
        'deleted',
    ];

    const CREATED_AT = 'cdate';
    const UPDATED_AT = 'mdate';

    public function scopeNotDeleted($query)
    {
        return $query->where('deleted', 'No');
    }

    public function scopeResidential($query)
    {
        return $query->where('type', 're');
    }

    public function scopeCommercial($query)
    {
        return $query->where('type', 'co');
    }

    /**
     * Get all prices for this list.
     */
    public function getPrices()
    {
        return Price::notDeleted()->get()->map(function ($price) {
            return [
                'id' => $price->id,
                'sku' => $price->sku,
                'name' => $price->name,
                'type' => $price->type,
                'price' => $price->getPriceForList($this->id),
            ];
        });
    }
}
