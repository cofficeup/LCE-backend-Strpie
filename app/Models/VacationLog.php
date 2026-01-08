<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VacationLog extends Model
{
    protected $table = 'lce_users_vacation_logs';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'start_date',
        'end_date',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Check if vacation is currently active.
     */
    public function isActive(): bool
    {
        $today = now()->format('Y-m-d');
        return $this->status &&
            $this->start_date <= $today &&
            $this->end_date >= $today;
    }
}
