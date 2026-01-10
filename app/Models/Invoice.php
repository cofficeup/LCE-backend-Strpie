<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $connection = 'client';
    protected $table = 'lce_user_invoice';

    protected $fillable = [
        'number',
        'user_id',
        'status',
        'wf_site_id',
        'dc_site_id',
        'wholesale_total_wf',
        'wholesale_total_dc',
        'wholesale_total',
        'sub_total_wf',
        'sub_total_dc',
        'sub_total',
        'pickup_charge',
        'total',
        'deleted',
        'errors',
        'promo_id',
        'promocode',
        'promo_amount',
        'group_admin_id',
        'group_admin_discount_amount',
        'partial_invoice',
    ];

    protected $casts = [
        'wholesale_total_wf' => 'float',
        'wholesale_total_dc' => 'float',
        'wholesale_total' => 'float',
        'sub_total_wf' => 'float',
        'sub_total_dc' => 'float',
        'sub_total' => 'float',
        'pickup_charge' => 'float',
        'total' => 'float',
        'promo_amount' => 'decimal:2',
        'group_admin_discount_amount' => 'float',
        'partial_invoice' => 'boolean',
    ];

    const CREATED_AT = 'cdate';
    const UPDATED_AT = 'mdate';

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function lines()
    {
        return $this->hasMany(InvoiceLine::class, 'invoice_id');
    }

    public function pickup()
    {
        return $this->hasOne(Pickup::class, 'invoice_id');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'invoice_id');
    }

    public function scopeNotDeleted($query)
    {
        return $query->where('deleted', 'No');
    }
}
