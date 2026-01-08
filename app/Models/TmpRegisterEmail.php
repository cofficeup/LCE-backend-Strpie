<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TmpRegisterEmail extends Model
{
    protected $table = 'lce_tmp_register_emails';
    public $timestamps = false;

    protected $fillable = [
        'zip',
        'email',
        'cdate',
    ];

    protected $casts = [
        'cdate' => 'date',
    ];

    /**
     * Scope to filter by zip code
     */
    public function scopeForZip($query, string $zip)
    {
        return $query->where('zip', $zip);
    }
}
