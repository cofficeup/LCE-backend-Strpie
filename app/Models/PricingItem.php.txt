<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PricingItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'sku',
        'service_type',
        'item_name',
        'description',
        'unit',
        'display_order',
        'active'
    ];

    public function prices()
    {
        return $this->hasMany(PricingItemPrice::class);
    }

    public function getPriceForList($priceListId)
    {
        return $this->prices()->where('price_list_id', $priceListId)->first()?->price;
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
}
