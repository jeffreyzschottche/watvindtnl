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
