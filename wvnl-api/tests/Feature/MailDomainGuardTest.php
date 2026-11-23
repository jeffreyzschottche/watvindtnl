<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class MailDomainGuardTest extends TestCase
{
    public function test_only_mail_from_the_app_domain_is_sent(): void
    {
        config(['mail.from.address' => 'noreply@watdenktnl.nl']);
        $sent = fn () => app('mailer')->getSymfonyTransport()->messages()->count();

        Mail::raw('ok', fn ($m) => $m->to('a@example.org'));
        Mail::raw('ok', fn ($m) => $m->to('a@example.org')->from('info@WatDenktNL.nl'));
        $this->assertSame(2, $sent());

        Mail::raw('nee', fn ($m) => $m->to('a@example.org')->from('noreply@andere-site.nl'));
        Mail::raw('nee', fn ($m) => $m->to('a@example.org')->from('noreply@watdenktnl.nl.evil.com'));
        $this->assertSame(2, $sent());
    }
}
