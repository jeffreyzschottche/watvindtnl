<?php

namespace App\Services;

use App\Models\Issue;
use App\Models\PoliticalParty;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/** Only editorial fields cross this boundary. App accounts and votes are never exported or assigned. */
class CmsContentService
{
    public function party(PoliticalParty $party): array
    {
        return $party->only(['id', 'slug', 'name', 'abbreviation', 'website_url']);
    }

    public function statement(Issue $issue): array
    {
        $issue->loadMissing('arguments');
        $meta = DB::table('wdnl_statement_metadata')->where('issue_id', $issue->id)->first();
        $result = array_merge($issue->only(['id', 'slug', 'title', 'description', 'more_info', 'url']), [
            'party_stances' => array_merge(['agree' => [], 'disagree' => [], 'neutral' => []], $issue->party_stances ?? []),
            'arguments' => collect(['pro', 'con'])->mapWithKeys(fn ($side) => [$side => $issue->arguments->where('side', $side)->sortBy('id')->map(fn ($arg) => ['body' => $arg->body, 'sources' => $arg->sources ?? []])->values()->all()])->all(),
            'motion_id' => $meta?->motion_id,
            'source_date' => $meta?->source_date,
            'revision' => $meta?->revision ?? 0,
        ]);
        $result['content_hash'] = hash('sha256', json_encode($result, JSON_THROW_ON_ERROR));

        return $result;
    }

    public function saveParty(array $payload, ?int $id = null, ?int $actor = null): array
    {
        $party = $id ? PoliticalParty::findOrFail($id) : null;
        $data = Validator::make($payload, [
            'name' => ['required', 'string', 'max:255'],
            'abbreviation' => ['required', 'string', 'max:20'],
            'slug' => ['required', 'string', 'max:180', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('political_parties')->ignore($id)],
            'website_url' => ['nullable', 'url:http,https', 'max:2048'],
        ])->validate();
        if ($party && $data['slug'] !== $party->slug) {
            throw ValidationException::withMessages(['slug' => ['Een bestaande partij houdt zijn slug. Voeg een nieuwe fractienaam als aparte partij/groep toe.']]);
        }

        return DB::transaction(function () use ($party, $data, $actor) {
            $before = $party ? $this->party($party) : null;
            $party ??= new PoliticalParty;
            $party->fill($data + ['logo_url' => null])->save();
            $after = $this->party($party);
            $this->audit('party.save', $party->slug, $before, $after, $actor);

            return $after;
        });
    }

    public function saveMotion(array $payload, ?int $actor = null, bool $sourceSync = false): array
    {
        $data = Validator::make($payload, [
            'external_id' => ['required', 'string', 'max:100', 'regex:/^[a-zA-Z0-9._:-]+$/'],
            'number' => ['required', 'string', 'max:100'],
            'title' => ['required', 'string', 'max:4000'],
            'source_url' => ['required', 'url:http,https', 'max:2048'],
            'source_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'context' => ['nullable', 'string', 'max:20000'],
            'revision' => ['nullable', 'integer', 'min:0'],
        ])->validate();
        $revision = $data['revision'] ?? null;
        unset($data['revision']);
        $data['context'] ??= null;

        return DB::transaction(function () use ($data, $revision, $actor, $sourceSync) {
            $existing = DB::table('wdnl_motions')->where('external_id', $data['external_id'])->lockForUpdate()->first();
            $before = $existing ? (array) $existing : null;
            // Source sync only inserts: a reviewed manual correction is never overwritten by a later fetch.
            if ($existing && ($sourceSync || collect($data)->every(fn ($v, $k) => $v === $existing->$k))) {
                return (array) $existing;
            }
            abort_if($existing && (int) $revision !== (int) $existing->revision, 409, 'Deze motie is gewijzigd. Haal de nieuwste versie op.');
            $now = now();
            if ($existing) {
                DB::table('wdnl_motions')->where('id', $existing->id)->update($data + ['revision' => $existing->revision + 1, 'updated_at' => $now]);
                $id = $existing->id;
            } else {
                $id = DB::table('wdnl_motions')->insertGetId($data + ['revision' => 1, 'created_at' => $now, 'updated_at' => $now]);
            }
            $after = (array) DB::table('wdnl_motions')->find($id);
            $this->audit('motion.save', $data['external_id'], $before, $after, $actor);

            return $after;
        });
    }

    public function saveStatement(array $payload, ?int $actor = null): array
    {
        $data = Validator::make($payload, [
            'slug' => ['required', 'string', 'max:180', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:4000'],
            'more_info' => ['nullable', 'string', 'max:20000'],
            'url' => ['required', 'url:http,https', 'max:2048'],
            'source_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'motion_id' => ['nullable', 'integer', 'exists:wdnl_motions,id'],
            'revision' => ['nullable', 'integer', 'min:0'],
            'reviewed' => ['required', 'accepted'],
            'content_hash' => ['nullable', 'string', 'regex:/^[a-f0-9]{64}$/'],
            'arguments' => ['required', 'array:pro,con'],
            'arguments.pro' => ['required', 'array', 'size:4'],
            'arguments.con' => ['required', 'array', 'size:4'],
            'arguments.*.*.body' => ['required', 'string', 'max:4000'],
            'arguments.*.*.sources' => ['present', 'array', 'min:1', 'max:10'],
            'arguments.*.*.sources.*' => ['required', 'url:http,https', 'max:2048'],
            'party_stances' => ['nullable', 'array:agree,disagree,neutral'],
            'party_stances.*' => ['array', 'max:100'],
            'party_stances.*.*' => ['integer', 'exists:political_parties,id'],
            'votes' => ['prohibited'], 'reports' => ['prohibited'], 'user_id' => ['prohibited'],
        ])->validate();
        foreach (['title', 'description', 'more_info'] as $field) {
            if (isset($data[$field]) && strip_tags($data[$field]) !== $data[$field]) {
                throw ValidationException::withMessages([$field => ['Gebruik gewone tekst, zonder HTML.']]);
            }
        }
        $allBodies = [];
        foreach (['pro', 'con'] as $side) {
            $data['arguments'][$side] = array_map(function ($arg) use (&$allBodies) {
                $body = trim($arg['body']);
                if ($body === '' || strip_tags($body) !== $body) {
                    throw ValidationException::withMessages(['arguments' => ['Elk argument bevat gewone, niet-lege tekst.']]);
                }
                $allBodies[] = mb_strtolower($body);

                return ['body' => $body, 'sources' => array_values(array_unique($arg['sources']))];
            }, $data['arguments'][$side]);
        }
        if (count(array_unique($allBodies)) !== 8) {
            throw ValidationException::withMessages(['arguments' => ['Gebruik acht inhoudelijk verschillende argumenten.']]);
        }
        $stances = [];
        foreach (['agree', 'disagree', 'neutral'] as $side) {
            $stances[$side] = array_values(array_map('intval', $data['party_stances'][$side] ?? []));
            sort($stances[$side]);
        }
        $ids = array_merge(...array_values($stances));
        if (count($ids) !== count(array_unique($ids))) {
            throw ValidationException::withMessages(['party_stances' => ['Een partij mag maar aan één kant staan.']]);
        }
        if ($ids && empty($data['motion_id'])) {
            throw ValidationException::withMessages(['motion_id' => ['Koppel officiële partijstemmen aan een bronmotie.']]);
        }

        return DB::transaction(function () use ($data, $stances, $actor) {
            $issue = Issue::where('slug', $data['slug'])->lockForUpdate()->first();
            $before = $issue ? $this->statement($issue) : null;
            $attributes = [
                'slug' => $data['slug'], 'title' => $data['title'], 'description' => $data['description'],
                'more_info' => $data['more_info'] ?? null, 'url' => $data['url'], 'party_stances' => $stances,
            ];
            $content = $attributes + ['arguments' => $data['arguments'], 'motion_id' => $data['motion_id'] ?? null, 'source_date' => $data['source_date']];
            if ($before && collect($content)->every(fn ($v, $k) => $before[$k] == $v)) {
                return $before + ['unchanged' => true];
            }
            abort_if($before && (! array_key_exists('revision', $data) || (int) $data['revision'] !== (int) $before['revision'] || ! hash_equals($before['content_hash'], $data['content_hash'] ?? '')), 409, 'Deze stelling bestaat of is gewijzigd. Open haar opnieuw voordat je wijzigingen opslaat.');
            if (! $issue) {
                $issue = Issue::create($attributes + ['votes' => ['agree' => [], 'disagree' => [], 'neutral' => []]]);
            } else {
                $issue->update($attributes); // never assign votes, reports or user relations
            }
            foreach (['pro', 'con'] as $side) {
                $existing = $issue->arguments()->where('side', $side)->orderBy('id')->get();
                abort_if($existing->count() > 4, 409, 'Deze historische stelling bevat meer dan vier argumenten aan een kant. Controleer haar eerst handmatig; er wordt niets verwijderd.');
                foreach ($data['arguments'][$side] as $i => $arg) {
                    if ($existing->has($i)) {
                        $existing[$i]->update($arg); // keep argument IDs and reports
                    } else {
                        $issue->arguments()->create($arg + ['side' => $side]);
                    }
                }
            }
            DB::table('wdnl_statement_metadata')->updateOrInsert(['issue_id' => $issue->id], [
                'motion_id' => $data['motion_id'] ?? null, 'source_date' => $data['source_date'],
                'verified_at' => now(), 'revision' => ($before['revision'] ?? 0) + 1,
                'created_at' => DB::table('wdnl_statement_metadata')->where('issue_id', $issue->id)->value('created_at') ?? now(),
                'updated_at' => now(),
            ]);
            $after = $this->statement($issue->fresh());
            $this->audit('statement.save', $issue->slug, $before, $after, $actor);

            return $after;
        }, 3);
    }

    public function audit(string $action, string $key, ?array $before, ?array $after, ?int $actor): void
    {
        if ($before == $after) {
            return;
        }
        DB::table('wdnl_content_audits')->insert([
            'action' => $action, 'resource_key' => $key, 'token_id' => $actor,
            'before' => $before === null ? null : json_encode($before, JSON_THROW_ON_ERROR),
            'after' => $after === null ? null : json_encode($after, JSON_THROW_ON_ERROR), 'created_at' => now(),
        ]);
    }
}
