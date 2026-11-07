# Tests

API: vanuit ../wvnl-api `php artisan test` met phpunit.xml SQLite :memory:, array cache/mail en sync testqueue. Nooit MySQL productie selecteren voor tests, migrate:fresh of RefreshDatabase. M1-regressies voor publieke identiteitlekken, resultgating, dubbele stemmen, transactionele updates, binary-only nieuwe stemmen, legacy neutraal, ownership, premium, login/verificatie/logout/revocation, rate limits, headers, no-LLM GET en verwijderde hulproutes. Geen mail versturen/LLM/betaling in tests.

Frontend: npm test voor logische stemresultaten/handmatige navigatie en demo-adflags; npm run typecheck; npm run build. Node24 via nvm. Leg daadwerkelijke resultaten vast in PROGRESS.md; scripts/testbestanden alleen bestaan is geen geslaagde verificatie.

Nog nodig vóór release: Playwright E2E (registreren/consent/inloggen/stemmen/resultaat/handmatig volgende/feed-exclusie/catalogus/historie), betaalwebhooks sandbox/refund/restore, export/delete, consent/adnetworkinspectie, native devices, WCAG-toets, MySQL-concurrency met tientallen identieke/parallelle votes en invariantcounts, loadtest, securityautorisatie/SSRF, backups terugzetten. SQLite tests bewijzen geen rowlockconcurrency. Geen claims over percentage testdekking zonder meetrapport.

Browserregressies: npm run test:e2e na npm run build. Playwright start/ruimt een tijdelijke server op poort3010 op en gebruikt lokaal bestaande Chrome, desktop en Pixel7-viewport. API-fixtures zijn expliciet testdata en raken MySQL niet. Controleert geen voortijdige percentages, links/rechts, blijvende uitslag, handmatig volgende, mislukte stemmen en geen trackingrequests; demo-AdSlot na afronden. Dit vervangt geen live backend/client E2E of echte Android/iOS-devicecheck.

Nieuwe regressies: eigen stellingen zonder motie/bron; nieuws met verplichte bronnaam/link/datum; publieke herkomstserialisatie; confetti bij beide stemkeuzes; geen confetti bij mislukking of reduced-motion; gedeelde animatievoorkeur na reload; geen externe artikelophaling bij bronweergave. API-tests blijven uitsluitend SQLite :memory:, browserfixtures uitsluitend op de tijdelijke testserver.

`npm run build:mobile` is lokaal geslaagd: de statische index en nieuwe webassets staan in de Android-/iOS-projecten. Dit is een asset-/packagingcontrole; signing, simulator/device-WebView en store-binaries zijn niet getest.
