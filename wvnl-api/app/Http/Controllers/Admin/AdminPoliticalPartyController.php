<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PoliticalParty;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdminPoliticalPartyController extends Controller
{
    public function index()
    {
        $parties = PoliticalParty::orderBy('name')->get();

        return response()->json(
            $parties->map(fn (PoliticalParty $party) => $this->serializeParty($party))->values()
        );
    }

    public function store(Request $request)
    {
        $data = $this->validatePartyData($request);

        $party = PoliticalParty::create($data);

        return response()->json($this->serializeParty($party), 201);
    }

    public function update(Request $request, PoliticalParty $politicalParty)
    {
        $data = $this->validatePartyData($request, $politicalParty);

        $politicalParty->update($data);

        return response()->json($this->serializeParty($politicalParty->fresh()));
    }

    private function validatePartyData(Request $request, ?PoliticalParty $party = null): array
    {
        $partyId = $party?->id;
        $slugRule = Rule::unique('political_parties', 'slug');
        $abbrRule = Rule::unique('political_parties', 'abbreviation');

        if ($partyId) {
            $slugRule = $slugRule->ignore($partyId);
            $abbrRule = $abbrRule->ignore($partyId);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'abbreviation' => ['required', 'string', 'max:50', $abbrRule],
            'slug' => ['nullable', 'string', 'max:255', $slugRule],
            'logo' => ['nullable', 'image', 'max:5120'],
            'logo_url' => ['nullable', 'string', 'max:2048'],
            'website_url' => ['nullable', 'string', 'max:2048'],
        ]);

        $name = trim($validated['name']);
        $abbreviation = trim($validated['abbreviation']);
        $slugInput = $this->normalizeNullableString($validated['slug'] ?? null);

        $slug = $slugInput ?? $this->generateUniqueSlug(
            $abbreviation ?: $name,
            $partyId
        );

        $logoUrl = $this->determineLogoUrl($request, $party, $validated);

        return [
            'name' => $name,
            'abbreviation' => $abbreviation,
            'slug' => $slug,
            'logo_url' => $logoUrl,
            'website_url' => $this->normalizeNullableString($validated['website_url'] ?? null),
        ];
    }

    private function determineLogoUrl(Request $request, ?PoliticalParty $party, array $validated): ?string
    {
        $current = $party?->logo_url;

        if ($request->hasFile('logo')) {
            $this->deleteLogo($current);

            $path = $request->file('logo')->store('logos', 'public');

            return Storage::disk('public')->url($path);
        }

        if (array_key_exists('logo_url', $validated)) {
            $value = $this->normalizeNullableString($validated['logo_url'] ?? null);

            if ($value === null) {
                $this->deleteLogo($current);
            }

            return $value;
        }

        return $current;
    }

    private function deleteLogo(?string $logoUrl): void
    {
        if (!$logoUrl) {
            return;
        }

        $disk = Storage::disk('public');
        $baseUrl = rtrim($disk->url(''), '/');

        $prefixes = array_filter([
            $baseUrl ? $baseUrl.'/' : null,
            $baseUrl ?: null,
            '/storage/',
        ]);

        foreach ($prefixes as $prefix) {
            if (!Str::startsWith($logoUrl, $prefix)) {
                continue;
            }

            $relative = ltrim(Str::after($logoUrl, $prefix), '/');

            if ($relative !== '') {
                $disk->delete($relative);
            }

            break;
        }
    }

    private function normalizeNullableString(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    private function serializeParty(PoliticalParty $party): array
    {
        return [
            'id' => $party->id,
            'name' => $party->name,
            'abbreviation' => $party->abbreviation,
            'slug' => $party->slug,
            'logo_url' => $party->logo_url,
            'website_url' => $party->website_url,
            'created_at' => $party->created_at?->toIso8601String(),
            'updated_at' => $party->updated_at?->toIso8601String(),
        ];
    }

    private function generateUniqueSlug(string $source, ?int $ignoreId = null): string
    {
        $base = Str::slug($source);
        $slug = $base;
        $counter = 2;

        while (
            PoliticalParty::where('slug', $slug)
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $base . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}
