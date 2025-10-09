<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PoliticalParty;
use Illuminate\Http\Request;
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
        $data = $this->validatePartyData($request, $politicalParty->id);

        $politicalParty->update($data);

        return response()->json($this->serializeParty($politicalParty->fresh()));
    }

    private function validatePartyData(Request $request, ?int $partyId = null): array
    {
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
            'logo_url' => ['nullable', 'string', 'max:2048'],
            'website_url' => ['nullable', 'string', 'max:2048'],
        ]);

        $slug = $validated['slug'] ?? $this->generateUniqueSlug(
            $validated['abbreviation'] ?: $validated['name'],
            $partyId
        );

        return [
            'name' => $validated['name'],
            'abbreviation' => $validated['abbreviation'],
            'slug' => $slug,
            'logo_url' => $validated['logo_url'] ?? null,
            'website_url' => $validated['website_url'] ?? null,
        ];
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
