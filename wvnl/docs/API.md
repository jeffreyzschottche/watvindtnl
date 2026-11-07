# API-contract

Basis `/api`; centrale Laravel-backend. Stabiele bestaande issue-ID's/slugs blijven behouden. JSON Accept en Content-Type, bearer token voor huidige client. CORS expliciete origins; geen wildcard credentials.

M1-contract: `issues-args` en `issues-args/{id}` retourneren inhoud/argumenten/partijreferenties en alleen de eigen `user_vote` indien geldig geauthenticeerd. `votes` is null vóór stemmen; na stemmen zijn agree/disagree/neutral gehele aantallen, nooit ID-lijsten. `Cache-Control: private, no-store` voorkomt gedeelde caching van politieke keuzes. Pendingfeed bevat alleen onbeantwoorde items. POST issues/{id}/vote accepteert agree/disagree, 409 voor herhaling, 422 voor ongeldig, 401 zonder login, 403 bij niet-geverifieerd, 429 bij limiter. Succes bevat eigen keuze, geaggregeerde counts en eigen voted_issue_ids. Neutraal wordt uitsluitend historisch weergegeven.

GET me/votes blijft eigen geschiedenis; de legacy updated_at is geen betrouwbare stemdatum. API en frontend samen uitrollen. Bestaande native builds moeten contractversiebeleid krijgen vóór productie.

Login/register/reset/contact/read/write/admin limieten via config/wdnl.php; standaard API-cap plus specifieke limiter. POST logout trekt huidige token in. Nieuwe tokens verlopen standaard na 14 dagen, configureerbaar via AUTH_TOKEN_DAYS; bestaande tokens worden niet massaal ingetrokken. Wachtwoordverandering/reset trekt alle tokens in. Premium kan niet via profielpayload worden gewijzigd. GET nieuws leest bestaande publicatie, geen LLM-generatie. Generatie alleen beschermd adminpad tot queuepipeline bestaat. Hulproutes test-mail/run-migration verwijderd.

M2: versieerbare resources, cursor-meta, expliciete consent- en privacyroutes, genormaliseerde vote-query, gevalideerde sources en permissies. OpenAPI/schema CI nog open.
