<?php

namespace App\Models;

use App\Notifications\ResetPasswordNotification;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Auth\MustVerifyEmail as MustVerifyEmailTrait;
use Illuminate\Contracts\Auth\MustVerifyEmail as MustVerifyEmailContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmailContract
{
    use HasApiTokens, HasFactory, MustVerifyEmailTrait, Notifiable;

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
        $this->notify(new VerifyEmailNotification());
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }
}
