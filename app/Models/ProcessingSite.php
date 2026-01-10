<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProcessingSite extends Model
{
    protected $connection = 'client';
    protected $table = 'lce_processing_sites';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'phone_1',
        'phone_2',
        'address_1',
        'address_2',
        'city',
        'country',
        'zip',
        'area',
        'print_align',
        'wf',
        'dc',
        'email',
        'prices_lists_id',
        'area_group_id',
        'user_group_id',
    ];

    protected $casts = [
        'wf' => 'boolean',
        'dc' => 'boolean',
    ];

    public function priceList()
    {
        return $this->belongsTo(PriceList::class, 'prices_lists_id');
    }

    /**
     * Check if site handles wash & fold.
     */
    public function handlesWashFold(): bool
    {
        return (bool) $this->wf;
    }

    /**
     * Check if site handles dry cleaning.
     */
    public function handlesDryCleaning(): bool
    {
        return (bool) $this->dc;
    }
}
