<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceLine extends Model
{
    protected $table = 'lce_user_invoice_line';

    protected $fillable = [
        'invoice_id',
        'item_id',
        'site_id',
        'sku',
        'type',
        'name',
        'quantity',
        'wholesale_price',
        'wholesale_amount',
        'price',
        'amount',
        'note',
        'deleted',
        'order',
    ];

    protected $casts = [
        'quantity' => 'float',
        'wholesale_price' => 'float',
        'wholesale_amount' => 'float',
        'price' => 'float',
        'amount' => 'float',
        'order' => 'integer',
    ];

    const CREATED_AT = 'cdate';
    const UPDATED_AT = 'mdate';

    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    public function priceItem()
    {
        return $this->belongsTo(Price::class, 'item_id');
    }

    public function processingSite()
    {
        return $this->belongsTo(ProcessingSite::class, 'site_id');
    }

    public function scopeNotDeleted($query)
    {
        return $query->where('deleted', 'No');
    }
}
