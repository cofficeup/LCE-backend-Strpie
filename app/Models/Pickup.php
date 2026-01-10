<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pickup extends Model
{
    protected $connection = 'client';
    protected $table = 'lce_user_pickup';

    protected $fillable = [
        'user_id',
        'pickup_type',
        'pickup_date',
        'status',
        'pickup_time',
        'no_laundry_time',
        'unload_wf_time',
        'unload_dc_time',
        'start_wf_time',
        'start_dc_time',
        'processing_wf_time',
        'processing_dc_time',
        'invoice_time',
        'hold_time',
        'unhold_time',
        'load_wf_time',
        'load_dc_time',
        'delivery_time',
        'cancelled_time',
        'hold_status',
        'wf_items',
        'wf_bags_items',
        'wf_hanger_items',
        'wf_site_id',
        'wf_washer_cost',
        'wf_dryer_cost',
        'wf_weight',
        'wf_slip_number',
        'dc_items',
        'dc_bags_items',
        'dc_hanger_items',
        'dc_site_id',
        'dc_slip_number',
        'invoice_id',
        'customerPaymentTransId',
        'customerPaymentTransAmount',
        'plist_printed',
        'keep_record',
        'geo_location',
        'pickup_driver_id',
        'skipped_pickup',
        'deliver_driver_id',
        'on_vacation',
        'group_admin_id',
        'group_code',
        'partial_invoice',
        'group_invoice_id',
        'log',
        'cuser_id',
    ];

    protected $casts = [
        'pickup_time' => 'datetime',
        'no_laundry_time' => 'datetime',
        'unload_wf_time' => 'datetime',
        'unload_dc_time' => 'datetime',
        'start_wf_time' => 'datetime',
        'start_dc_time' => 'datetime',
        'processing_wf_time' => 'datetime',
        'processing_dc_time' => 'datetime',
        'invoice_time' => 'datetime',
        'hold_time' => 'datetime',
        'unhold_time' => 'datetime',
        'load_wf_time' => 'datetime',
        'load_dc_time' => 'datetime',
        'delivery_time' => 'datetime',
        'cancelled_time' => 'datetime',
        'wf_items' => 'integer',
        'wf_bags_items' => 'integer',
        'wf_hanger_items' => 'integer',
        'wf_weight' => 'integer',
        'dc_items' => 'integer',
        'dc_bags_items' => 'integer',
        'dc_hanger_items' => 'integer',
        'plist_printed' => 'boolean',
        'keep_record' => 'integer',
        'skipped_pickup' => 'boolean',
        'on_vacation' => 'boolean',
        'partial_invoice' => 'integer',
    ];

    const CREATED_AT = 'cdate';
    const UPDATED_AT = null; // No updated_at in legacy

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    public function wfSite()
    {
        return $this->belongsTo(ProcessingSite::class, 'wf_site_id');
    }

    public function dcSite()
    {
        return $this->belongsTo(ProcessingSite::class, 'dc_site_id');
    }

    public function pickupDriver()
    {
        return $this->belongsTo(User::class, 'pickup_driver_id');
    }

    public function deliverDriver()
    {
        return $this->belongsTo(User::class, 'deliver_driver_id');
    }
}
