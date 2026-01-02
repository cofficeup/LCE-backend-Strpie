<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PricingItemPrice extends Model
{
    use HasFactory;

    protected $table = 'pricing_item_prices';

    protected $fillable = [
        'pricing_item_id',
        'price_list_id',
        'price',
        'min_price'
    ];
}
