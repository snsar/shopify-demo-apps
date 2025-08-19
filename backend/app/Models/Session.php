<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Session extends Model
{
    protected $fillable = [
        'session_id',
        'shop',
        'is_online',
        'state',
        'scope',
        'access_token',
        'expires_at',
        'user_id',
        'user_first_name',
        'user_last_name',
        'user_email',
        'user_email_verified',
        'account_owner',
        'locale',
        'collaborator',
    ];

    protected $casts = [
        'is_online' => 'boolean',
        'user_email_verified' => 'boolean',
        'account_owner' => 'boolean',
        'collaborator' => 'boolean',
        'expires_at' => 'datetime',
    ];
}
