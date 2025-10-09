<?php

namespace App\Models;

use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Illuminate\Auth\Notifications\VerifyEmail as VerifyEmailNotification;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'language',
        'voted_issue_ids',
        'requests',
        'age_category',
        'province',
        'gender',
        'education_level',
        'political_preference',
        'notification_prefs',
        'cookie_prefs',
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

    public function sendEmailVerificationNotification(): void
    {
        Notification::sendNow($this, new VerifyEmailNotification());
    }

    public function sendPasswordResetNotification($token): void
    {
        Notification::sendNow($this, new ResetPasswordNotification($token));
    }
}
