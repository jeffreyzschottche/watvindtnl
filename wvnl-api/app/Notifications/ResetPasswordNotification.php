<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword as BaseResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use App\Support\FrontendUrl;

class ResetPasswordNotification extends BaseResetPassword
{
    public function toMail($notifiable): MailMessage
    {
        $resetUrl = FrontendUrl::make('password-reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);

        return (new MailMessage)
            ->subject('Reset je WVNL-wachtwoord')
            ->markdown('emails.password-reset', [
                'name' => $notifiable->name,
                'resetUrl' => $resetUrl,
            ]);
    }
}
