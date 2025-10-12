<?php

namespace App\Support;

class FrontendUrl
{
    public static function make(string $path = '', array $query = []): string
    {
        $base = rtrim(config('app.front_url', config('app.url')), '/');
        $trimmedPath = trim($path, '/');
        $url = $base . ($trimmedPath !== '' ? '/' . $trimmedPath : '');

        if (!empty($query)) {
            $url .= '?' . http_build_query($query);
        }

        return $url;
    }
}
