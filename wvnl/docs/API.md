# API-contract

Basis `/api`; centrale Laravel-backend. Stabiele bestaande issue-ID's/slugs blijven behouden. JSON Accept en Content-Type, bearer token voor huidige client. CORS expliciete origins; geen wildcard credentials.

M1-contract: `issues-args` en `issues-args/{id}` retourneren inhoud/argumenten/partijreferenties en alleen de eigen `user_vote` indien geldig geauthenticeerd. `votes` is null vóór stemmen; na stemmen zijn agree/disagree/neutral gehele aantallen, nooit ID-lijsten. `Cache-Control: private, no-store` voorkomt gedeelde caching van politieke keuzes. Pendingfeed bevat alleen onbeantwoorde items. POST issues/{id}/vote accepteert agree/disagree, 409 voor herhaling, 422 voor ongeldig, 401 zonder login, 403 bij niet-geverifieerd, 429 bij limiter. Succes bevat eigen keuze, geaggregeerde counts en eigen voted_issue_ids. Neutraal wordt uitsluitend historisch weergegeven.

GET me/votes blijft eigen geschiedenis; de legacy updated_at is geen betrouwbare stemdatum. API en frontend samen uitrollen. Bestaande native builds moeten contractversiebeleid krijgen vóór productie.

Login/register/reset/contact/read/write/admin limieten via config/wdnl.php; standaard API-cap plus specifieke limiter. POST logout trekt huidige token in. Nieuwe tokens verlopen standaard na 14 dagen, configureerbaar via AUTH_TOKEN_DAYS; bestaande tokens worden niet massaal ingetrokken. Wachtwoordverandering/reset trekt alle tokens in. Premium kan niet via profielpayload worden gewijzigd. GET nieuws leest bestaande publicatie, geen LLM-generatie. Generatie alleen beschermd adminpad tot queuepipeline bestaat. Hulproutes test-mail/run-migration verwijderd.

M2: versieerbare resources, cursor-meta, expliciete consent- en privacyroutes, genormaliseerde vote-query, gevalideerde sources en permissies. OpenAPI/schema CI nog open.

## Eenvoudige catalogus en detailroutes

`GET /issues-args?search=...&answered=all|yes|no&limit=24&offset=0` zoekt server-side in titels (maximaal 120 tekens) vóór paginering. Eigen stemfilters vereisen Sanctum-authenticatie; unauthenticated krijgt 401. Resultaatcontract blijft counts-na-eigen-stem, geen politieke identiteiten. Sortering op created_at en id geeft stabiele volgorde binnen de huidige offsetoplossing. Cursorpagination blijft de genormaliseerde datamijlpaal.

`GET /statements/{slug}` geeft dezelfde veilige detailresource. Onbekende slug geeft 404. Frontend `/stellingen/[slug]` gebruikt dezelfde stemactie en kaart als `/spel`; geen tweede stemimplementatie.

## ZschotCMS-contentgrens (7 november 2026)

`/api/cms/content` heeft een eigen beperkte bearer-token, los van app-/adminaccounts. Partijen, stellingen en bronmoties kunnen worden gelezen, gewijzigd en atomisch geïmporteerd. De API exporteert geen gebruikers, individuele stemmen of rapportages. Zie [ZSCHOTCMS.md](ZSCHOTCMS.md) voor alle routes, publicatievoorwaarden, revisions/contenthashes en live-configuratie.

## Stemrapporten — 8 november 2026

Publiek `GET /reports/product`: vaste prijs 199 EUR-cent, koop-/AI-beschikbaarheid en minimumstemmen. Auth: `GET /me/reports` (eigen aanvragen en aantoonbaar aantal nieuwe antwoorden), `GET /me/reports/{uuid}` (snapshot/narratief alleen bij klaar), `GET /me/reports/{uuid}/pdf` (eigen betaalde PDF), `POST /me/reports/orders` (UUID request_key, expliciete political_consent, optionele ai_consent en platform). Replays hergebruiken de aanvraag; bewust nieuwe request_key betekent afzonderlijke aankoop. Native checkout wordt geweigerd. Oude gratis `POST /me/political-compass` geeft 409 met verwijzing naar de afzonderlijke rapportaankoop; legacy historie is behouden.

Stripe: `POST /reports/stripe-webhook`, SDK-signaturecheck, geen clientontgrendeling. Alleen passende bevestigde betaling maakt de order paid en dispatcht een reportjob. Success-redirect heeft geen rechtentoekenning. CMS: `GET/POST /cms/reports/settings`, `GET /cms/reports/stats`, `POST /cms/reports/estimate`, met afzonderlijke expliciete read/write-rechten. Geen rapportinhoud of individuele stemgegevens in CMS-responses. Details in REPORTS.md/PAYMENTS.md.


Neutraal hersteld: `POST /issues/{id}/vote` accepteert opnieuw `neutral` naast agree/disagree. Deze keuze heeft dezelfde verificatie, duplicate rejection, geschiedenis en feeduitsluiting. Alleen de eigen keuze en geaggregeerde telling worden teruggegeven.

Lokale preview: `/me/reports/dev-access`, `POST /me/reports/dev`, `GET /me/reports/dev/{uuid}` en `/pdf`. Geauthenticeerd, expliciete lokale flag en uitsluitend het geconfigureerde demo-account; productie weigert genereren/downloaden. Encrypted cache met één uur TTL, geen betaaladministratie of LLM-call. Zie REPORTS.md.

### Kompasvelden in rapportsnapshots

`GET /api/me/reports` geeft nu ook `compass_progress: { ready, used_votes, dimensions: [{ name, compared, minimum }] }`. Aankoop van een nieuw volledig rapport vereist alle themascores, aparte economische as en minimumaantallen; anders 422, vóór order/checkout. Alleen de eigenaar ziet deze voortgang.

Nieuwe `snapshot.compass` bevat `version`, `status`, nullable `type`, vier `dimensions`, aparte `spectrum`, `used_votes`, `evidence` en `highlights`. Dimensies bevatten nullable `percentage`/`opposite_percentage`, coverage en een niet-statistische leave-one-out `sensitivity`. Ontbrekende data is null, nooit een fictieve 50%. Historische snapshots zonder dit veld blijven leesbaar.

CMS statement GET/save/import ondersteunt optioneel `compass_weights` met uitsluitend social/progressive/international/green/economic, gehele waarden −2…2. Afwezig bij een update behoudt bestaande weging; leeg/nul verwijdert haar expliciet. Publicatie blijft redactioneel gereviewd, revision/hash-gebonden en geaudit. Geen gebruikersdata of rapporten naar CMS.


### compass-v2.1

Nieuwe `snapshot.compass` velden: status `ready|provisional|insufficient_data`; type.provisional en type.based_on_dimensions; historical_votes, unique_answers, duplicate_answers, conflicting_groups, topics. Dimensies hebben early_minimum (2), provisional en historical. Een enkele vraag levert geen percentage. Minimum (5) blijft de volledige dekkingsdrempel. Koopvalidatie vereist ook een niet-voorlopige economische as.

CMS statement GET/save: compass_context met canonical_key, rationale, topics, historical, catalogue, basis en text_hash. compass_reviewed_at is servergegenereerd. De hash wordt bij gereviewd opslaan opnieuw berekend; oude mappings worden niet toegepast op gewijzigde tekst. De broncontrole via verified_at blijft afzonderlijk.

`POST /api/cms/content/compass/seed` vereist content-read én content-write en is geaudit/idempotent. Alleen exact bekende bronformuleringen worden meegenomen; een bestaande eigen weging of gewijzigde tekst blijft behouden. Gewone app- of alleen-lezentokens kunnen dit niet starten.

Kompasvoorstellen (contenttoken; lezen voor GET, schrijven voor POST):

- `GET /api/cms/content/compass/proposals?status=pending|approved|rejected|outdated&confidence=high|medium|low&page=` geeft voorstellen met stellingtitel, gewichten, onderwerpen en onderbouwing, plus tellingen, het aantal stellingen zonder weging en de totale modelkosten.
- `POST /api/cms/content/compass/proposals/generate` met `{limit: 1–50}` laat het rapportmodel nieuwe voorstellen maken (maximaal €0,10 per aanroep, eigen rate-limit van 40 per uur).
- `POST /api/cms/content/compass/proposals/{id}/approve` met optioneel `{weights: {social: -2…2, …}}` schrijft de beoordeelde weging. 409 als de tekst sindsdien is gewijzigd of al handmatig is gewogen; het voorstel wordt dan `outdated`.
- `POST /api/cms/content/compass/proposals/{id}/reject`.
- `GET /api/cms/content/compass/weights` exporteert alle beoordeelde gewichten (`compass-weights-v1`: slug, text_hash, weights, context). `POST` met dezelfde inhoud importeert ze; reactie applied, already_reviewed, changed_text, missing.
- Een via `POST /statements` of `/import` opgeslagen stelling zonder gekozen gewichten krijgt na het antwoord automatisch een voorstel.
- `POST /api/cms/content/compass/proposals/approve` met `{confidence: high|medium, limit≤250}` of `{ids: […]}` keurt in bulk goed en slaat gewijzigde stellingen over.

`GET /api/issues/pending` zet maximaal twaalf gewogen stellingen vooraan die ontbrekende kompasthema's van de gebruiker aanvullen, in één vaste volgorde voor alle offsets.


### Rapportontwerp insight-v3

Nieuwe order-/devsnapshots bevatten `analysis_context` met version=3, votes (eigen keuze, titel en begrensde context), eligible_count, included_count, omitted_count en excluded_conflicts. Deze extra informatie is uitsluitend beschikbaar via geauthenticeerde eigenaarsrapporten; niet via het CMS of de publieke API. Nieuwe bestellingen registreren consent_version=report-v3.

`narrative` bevat analysis_version=3, analysis_origin=ai|code, summary, reflection, insights[{heading,body,evidence_ids}], positions[{statement_id,interpretation}], nuances[{heading,body,evidence_ids}] en questions[{text,evidence_ids}]. Alle AI-verwijzingen moeten in de bewaarde context voorkomen. Nieuwe devpreviews hebben layout_version=insight-v3; oudere tijdelijke previews geven 409. Oude gekochte rapporten blijven ondersteund. Het PDF-endpoint levert dezelfde gegevens als twee A4’s; downloaden maakt geen nieuwe modelaanvraag.


### compass-poster-v4

`compass.version=compass-v4.1`, `type_catalogue` bevat de zestien presentatietypen. Een gekozen type bevat ook color/description; toelaatbaarheid volgt uit vaste regels in de servercatalogus. Nieuwe `narrative.analysis_version=4` bevat summary, highlights[{text,evidence_ids}], analysis_origin en prompt_version. Alle vier observaties zijn kort en volledig, met bestaande antwoordverwijzingen. Nieuwe devpreviews hebben layout_version=compass-poster-v4.

`party_coverage` geeft verified_motions, minimum=10 en early_minimum=2. Nieuwe uitgebreide rapportsnapshots kunnen partijovereenkomsten vanaf twee unieke officiële moties tonen; bij minder dan tien hebben ze provisional=true. Elke partij behoudt matched/compared/percentage. Conflicterende eigen antwoorden op dezelfde motie tellen niet mee. Oude rapportformaten blijven als legacy contract ondersteund. De actuele PDF is één A4.


### Voorzichtige scores (V4.1)

`compass.score_adjustment` bevat `{version: "neutral-anchor-v1", neutral_anchor: 6, display_range: [1,99]}`. Een dimensie bevat naast de afgezwakte `percentage` en complementaire `opposite_percentage` de oorspronkelijke nullable `observed_percentage`. Formule en afronding staan in COMPASS_ALGORITHM.md. N is het aantal verschillende bruikbare niet-neutrale antwoorden, geen som van gewichten. Geen score bij minder dan twee antwoorden.

Partijen behouden het feitelijke `percentage`, `matched` en `compared`. Het nieuwe `balanced_score` is de afgezwakte indicator op 100; nieuwe rangschikking en UI/PDF gebruiken dit veld en noemen het expliciet een matchscore. `party_coverage.score_adjustment` beschrijft dezelfde regel. Oude snapshots zonder deze velden behouden hun oorspronkelijke cijfers. Nieuwe previews hebben methodology_version=compass-v4.1; narrative.analysis_version en layout_version blijven 4/compass-poster-v4 (één A4).
