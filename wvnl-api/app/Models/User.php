<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'voted_issue_ids',
        'requests',
        'age_category',
        'province',
        'gender',
        'education_level',
        'political_preference',
        'notification_prefs',
        'cookie_prefs',
        'language',
        'premium',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'voted_issue_ids' => 'array',
        'requests' => 'array',
        'notification_prefs' => 'array',
        'cookie_prefs' => 'array',
        'premium' => 'boolean',
        'email_verified_at' => 'datetime',
    ];
}
