<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GroupAdmin extends Model
{
    protected $connection = 'client';
    protected $table = 'lce_user_group_admin';

    protected $primaryKey = 'group_id';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'group_name',
        'group_code',
        'group_type',
        'is_admin',
        'date_added',
        'published',
    ];

    protected $casts = [
        'is_admin' => 'boolean',
        'published' => 'boolean',
        'date_added' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function members()
    {
        return $this->hasMany(GroupMember::class, 'group_id', 'group_id');
    }
}
