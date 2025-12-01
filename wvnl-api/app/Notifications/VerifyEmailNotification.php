<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail as BaseVerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

class VerifyEmailNotification extends BaseVerifyEmail
{
    public function toMail($notifiable): MailMessage
    {
        $verificationUrl = $this->verificationUrl($notifiable);

        return (new MailMessage)
            ->subject('Bevestig je WDNL-account')
            ->view('emails.verify-email', [
                'name' => $notifiable->name,
                'verificationUrl' => $verificationUrl,
            ]);
    }
}
