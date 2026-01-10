<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TmpInvitedGroupMembersLog extends Model
{
    protected $connection = 'client';
    protected $table = 'lce_tmp_invited_group_members_log';
    public $timestamps = false;

    protected $fillable = [
        'invited_emails',
        'restricted_emails',
    ];

    protected $casts = [
        'invited_emails' => 'array',
        'restricted_emails' => 'array',
    ];
}
