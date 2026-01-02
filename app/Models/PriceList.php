<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PriceList extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'type',
        'zip_codes',
        'display_order',
        'active'
    ];

    public function items()
    {
        return $this->belongsToMany(PricingItem::class, 'pricing_item_prices')
            ->withPivot(['price', 'min_price'])
            ->withTimestamps();
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
}
