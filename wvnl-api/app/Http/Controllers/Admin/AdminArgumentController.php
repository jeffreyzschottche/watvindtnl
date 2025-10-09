<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Argument;
use App\Models\Issue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AdminArgumentController extends Controller
{
    public function store(Request $request, Issue $issue)
    {
        $data = $request->validate([
            'side' => ['required', 'in:pro,con'],
            'body' => ['required', 'string'],
            'sources' => ['nullable', 'array'],
            'sources.*' => ['string'],
        ]);

        $argument = $issue->arguments()->create([
            'side' => $data['side'],
            'body' => $data['body'],
            'sources' => $this->normalizeSources($data['sources'] ?? []),
        ]);

        return response()->json($this->serializeArgument($argument), 201);
    }

    public function destroy(Argument $argument)
    {
        $argument->delete();

        return response()->json(['status' => 'deleted']);
    }

    public function update(Request $request, Argument $argument)
    {
        $data = $request->validate([
            'side' => ['required', 'in:pro,con'],
            'body' => ['required', 'string'],
            'sources' => ['nullable', 'array'],
            'sources.*' => ['string'],
        ]);

        $argument->side = $data['side'];
        $argument->body = $data['body'];
        $argument->sources = $this->normalizeSources($data['sources'] ?? []);
        $argument->save();

        return response()->json($this->serializeArgument($argument->fresh()));
    }

    public function bulkStore(Request $request)
    {
        $payload = $request->validate([
            'arguments' => ['required', 'array', 'min:1'],
        ]);

        $results = collect();

        foreach ($payload['arguments'] as $argumentPayload) {
            if (!is_array($argumentPayload)) {
                continue;
            }

            $argument = $this->persistArgumentPayload($argumentPayload);

            if ($argument) {
                $results->push($this->serializeArgument($argument));
            }
        }

        return response()->json($results->all());
    }

    private function persistArgumentPayload(array $payload): ?Argument
    {
        $validator = Validator::make($payload, [
            'id' => ['nullable', 'integer', 'exists:arguments,id'],
            'issue_id' => ['nullable', 'integer', 'exists:issues,id'],
            'issue_slug' => ['nullable', 'string'],
            'side' => ['required', 'in:pro,con'],
            'body' => ['required', 'string'],
            'sources' => ['nullable', 'array'],
            'sources.*' => ['string'],
        ]);

        $validator->after(function ($validator) use ($payload) {
            if (empty($payload['issue_id']) && empty($payload['issue_slug'])) {
                $validator->errors()->add('issue', 'Een issue_id of issue_slug is verplicht.');
            }
        });

        $validated = $validator->validate();

        $issue = null;

        if (!empty($validated['issue_id'])) {
            $issue = Issue::find($validated['issue_id']);
        }

        if (!$issue && !empty($validated['issue_slug'])) {
            $issue = Issue::where('slug', $validated['issue_slug'])->first();
        }

        if (!$issue) {
            throw ValidationException::withMessages([
                'issue' => ['Kon de gekoppelde kwestie niet vinden.'],
            ]);
        }

        $attributes = [
            'side' => $validated['side'],
            'body' => $validated['body'],
            'sources' => $this->normalizeSources($validated['sources'] ?? []),
        ];

        $argument = null;

        if (!empty($validated['id'])) {
            $argument = Argument::find($validated['id']);
        }

        if ($argument) {
            $argument->fill($attributes);
            if ($argument->issue_id !== $issue->id) {
                $argument->issue()->associate($issue);
            }
            $argument->save();
        } else {
            $argument = $issue->arguments()->create($attributes);
        }

        return $argument->fresh();
    }

    private function serializeArgument(Argument $argument): array
    {
        return [
            'id' => $argument->id,
            'issue_id' => $argument->issue_id,
            'side' => $argument->side,
            'body' => $argument->body,
            'sources' => $argument->sources ?? [],
            'source_reports' => $argument->source_reports ?? [],
            'created_at' => $argument->created_at?->toIso8601String(),
            'updated_at' => $argument->updated_at?->toIso8601String(),
        ];
    }

    private function normalizeSources(array $sources): array
    {
        return array_values(array_filter(array_map('strval', $sources)));
    }
}
