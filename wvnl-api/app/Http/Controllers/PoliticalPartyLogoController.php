<?php

namespace App\Http\Controllers;

use App\Http\Requests\UploadPartyLogoRequest;
use App\Models\PoliticalParty;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PoliticalPartyLogoController extends Controller
{
    public function store(UploadPartyLogoRequest $request, PoliticalParty $politicalParty): JsonResponse
    {
        $file = $request->validated()['logo'];

        $this->deleteExistingLogo($politicalParty);

        $path = $file->store('political-party-logos', 'public');
        $url = url(Storage::disk('public')->url($path));

        $politicalParty->forceFill(['logo_url' => $url])->save();
        $politicalParty->refresh();

        return response()->json([
            'political_party' => [
                'id' => $politicalParty->id,
                'name' => $politicalParty->name,
                'abbreviation' => $politicalParty->abbreviation,
                'slug' => $politicalParty->slug,
                'logo_url' => $politicalParty->logo_url,
                'website_url' => $politicalParty->website_url,
                'status' => $politicalParty->status,
                'published_at' => $politicalParty->published_at?->toIso8601String(),
            ],
        ]);
    }

    protected function deleteExistingLogo(PoliticalParty $politicalParty): void
    {
        $existing = $politicalParty->logo_url;
        if (!$existing) {
            return;
        }

        $path = parse_url($existing, PHP_URL_PATH);
        if (!$path) {
            return;
        }

        $storagePrefix = '/storage/';
        if (!Str::startsWith($path, $storagePrefix)) {
            return;
        }

        $relativePath = ltrim(Str::after($path, $storagePrefix), '/');
        if ($relativePath !== '') {
            Storage::disk('public')->delete($relativePath);
        }
    }
}
