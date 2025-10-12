<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ContactMessageMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly string $body,
    ) {
    }

    public function build(): self
    {
        return $this
            ->subject('Nieuw bericht via contactformulier')
            ->replyTo($this->email, $this->name)
            ->markdown('emails.contact-message', [
                'name' => $this->name,
                'email' => $this->email,
                'content' => $this->body,
            ]);
    }
}
