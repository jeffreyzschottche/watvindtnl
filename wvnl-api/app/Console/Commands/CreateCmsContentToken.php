<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateCmsContentToken extends Command
{
    protected $signature = 'cms:content-token {--output= : Nieuw bestand, nooit terminaloutput} {--name=zschotcms} {--read-only}';

    protected $description = 'Maak een beperkte WDNL-contenttoken zonder toegang tot accounts, stemmen of betalingen';

    public function handle(): int
    {
        $path = $this->option('output');
        if (! is_string($path) || $path === '' || file_exists($path)) {
            $this->error('Gebruik --output met een nieuw bestandspad.');

            return self::FAILURE;
        }
        $handle = @fopen($path, 'x');
        if (! $handle) {
            $this->error('Tokenbestand kan niet worden gemaakt.');

            return self::FAILURE;
        }
        chmod($path, 0600);
        $plain = Str::random(80);
        try {
            DB::table('wdnl_cms_tokens')->insert([
                'name' => mb_substr($this->option('name'), 0, 255),
                'token_hash' => hash('sha256', $plain),
                'abilities' => json_encode($this->option('read-only') ? ['cms:content:read'] : ['cms:content:read', 'cms:content:write']),
                'expires_at' => now()->addYear(), 'created_at' => now(), 'updated_at' => now(),
            ]);
            if (fwrite($handle, $plain) !== strlen($plain)) {
                DB::table('wdnl_cms_tokens')->where('token_hash', hash('sha256', $plain))->delete();
                throw new \RuntimeException('Tokenbestand kon niet volledig worden geschreven.');
            }
        } finally {
            fclose($handle);
        }
        $this->info('WDNL-contenttoken opgeslagen (0600), geldig tot '.now()->addYear()->toDateString().'.');

        return self::SUCCESS;
    }
}
