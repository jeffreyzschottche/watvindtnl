# WDNL — huidige staat

Audit: 7 november 2026, basiscommit `92e87fe`, branch `refactor`. Dit beschrijft de aangetroffen toestand vóór M1; wijzigingen en testbewijs staan in PROGRESS.md. Alleen lokale data onderzocht, geen productieverbinding gemaakt.

## 1. Huidige architectuur

De Git-root is de bovenliggende map `watvindtnl/`. `wvnl/` bevat Nuxt 4.1.3 (lockfile), Vue 3.5.22, Pinia 3.0.3, npm en TypeScript in Vue-bestanden. De frontend is een SPA (`ssr: false`). `app/pages`, `components`, `composables`, `services` en `stores` zijn al gescheiden. `admin/cms.vue` heeft 2.578 regels. Algemene CSS en scoped styles lopen door elkaar. De README is nog de Nuxt-starter.

`wvnl-api/` bevat Laravel 12, PHP >=8.2, Eloquent, Sanctum bearer tokens, Composer en een aparte Vite-starter voor de Laravel-welkomstpagina. Er is geen API-versieprefix. De lokale database is MySQL `wvnl`; SQLite wordt ondersteund voor tests. Jobs/cache/tabellen bestaan maar productverwerking gebeurt veelal synchroon. Native iOS/Android-wrappers gebruiken Capacitor 7.4.3; app-ID is `com.example.watvindtnl`, Android target/compile SDK 35 en iOS Podfile noemt 14.0. Dit is geen bewezen store-ready configuratie.

Frontend en API hebben afzonderlijke Dockerfiles. Coolify-instructies staan in de Git-root. API-entrypoint kan migraties starten en gebruikt standaard synchrone queues. Geen CI-workflow aangetroffen. Geen productgerichte tests; alleen Laravel-voorbeeldtests. Geen aantoonbare coverage, loadtest, restoretest of native buildverificatie.

## 2. Wat functioneert / bewijs

Lokale API bereikbaar; demo-login en geauthenticeerd `/me` leverden HTTP 200. CORS-preflight voor localhost:3000 leverde 204. Dit is een smokecheck, geen bewijs voor alle flows.

Read-only telling op de lokale MySQL: 19 gebruikers, 4.598 issues, 31.887 argumenten, 15 partijen, 3 kompasanalyses en 7 artikelen. Stem-JSON bevat 29 voor, 24 tegen en 13 neutrale lidmaatschappen; dit is nog niet gevalideerd als 66 unieke geldige stemmen. Geen persoonsgegevens of hashes gekopieerd naar deze documentatie.

Aanwezige flows: registratie, verificatiemail, login, reset, profielbewerking, stemfeed, geschiedenis, argumenten/bronnen, partijstandpunten, rapporteren, delen, nieuws, contact en beheer/import. Niet alle flows handmatig doorlopen.

## 3. Veroudering en technische schuld

Lokale Node is standaard 20.14.0, zelfs lager dan de engine-eis van de geïnstalleerde Nuxt. Node 24.20.0 is beschikbaar via nvm. Er zijn geen expliciete lint/typecheck/testscripts in de frontend. npm-audit vóór wijzigingen meldt 49 kwetsbare dependency-items (7 critical, 25 high, 12 moderate, 5 low). Composer-audit meldt advisories in meerdere directe/transitieve packages. Scans zijn tijdsafhankelijk en geen bewijs van exploitability; release blijft geblokkeerd tot oplossen/herbeoordelen.

De feed verwijdert een kaart meteen na stemmen. Er zijn drie nieuwe keuzes in plaats van de gewenste twee. Resultaten zijn al vóór stemmen zichtbaar. Historie gebruikt issue.updated_at als datum: oorspronkelijke stemdatum is niet bekend. Er is offsetpagination, willekeurig schudden en achtergrondladen van de hele catalogus. `Issue.ts` en `issues.ts` overlappen. Publieke serialisatie is gekopieerd tussen controllers.

## 4. Hergebruik

Behoud Nuxt/Vue/Pinia, Laravel/Eloquent/Sanctum, bestaande account-ID's, password hashes, issue-ID's/slugs, argumenten, bronnen, partijen, mailtemplates en goede componenten. Behoud Capacitor totdat een bewezen native beperking een andere client rechtvaardigt. Geen React/Expo-rewrite of microservices nodig voor de eerste fasen.

## 5. Vervangen

Vervang JSON-stemopslag met genormaliseerde votes plus unieke `(user_id, issue_id)` constraint, dataprovenance en cursorfeed. Vervang LLM-bepaalde kompaspercentages door een versieerbare deterministische berekening. Vervang hardcoded adminidentiteit door rollen/MFA. Webauth migreert naar HttpOnly-sessies; native krijgt intrekbare tokens in OS secure storage. Splits CMS, publicatievalidatie en queuejobs; geen LLM op leesrequests.

## 6. Securityrisico's vóór M1

Kritiek: publieke issue-API's geven lijsten van gebruikers-ID's per politieke keuze. GET `/run-news-articles-migration` voert migratie uit en verwijdert bronbestand; GET `/test-mail` verstuurt mail. Publieke nieuws-GET's kunnen betaalde LLM-verwerking starten. Profielvalidatie staat self-service `premium` toe. Model verbergt niet expliciet `secret_url_pw`.

Hoog: geen API-rate limiting, client-leesbare token-cookie, logout wist alleen clientstate, wachtwoordreset trekt tokens niet in, tokens zonder expliciete levensduur. GA/AdSense starten zonder consent en worden op meerdere plaatsen geladen. Geen afzonderlijk vastgelegde politieke toestemming. LLM ontvangt politieke voorkeur en stemgegevens. SQL-dump `wvnl-api/wvnl-2026-03-24.sql` staat in Git: behandel geschiedenis als gevoelig; niet openen/uitrollen als seed. Secretrotation/historycleanup vraagt een aparte gecoördineerde procedure. Geen geheimwaarden gepubliceerd tijdens audit.

Autorisatie nuance: profiel- en wachtwoord-FormRequests controleren al de eigenaar; behoud dit en voeg regressietests toe. Niet onterecht als bestaand IDOR bestempelen.

## 7. Migratierisico's

De lokale database heeft niet alle kolommen die migraties/modellen verwachten: `secret_url_pw`, `status`, `published_at` ontbreken. `IssuePartyStance` heeft een relatie zonder aangetroffen bijbehorende migratie. Migratiestatus/schema moeten vóór ieder cutover worden vergeleken. Historische neutrale stemmen en onbekende stemdatums nooit omzetten/verzinnen. Dubbele of verweesde stem-ID's in quarantaine, niet stil verwijderen. Een oude SQL-dump is geen recente, versleutelde of geteste backup.

## 8. Doelarchitectuur

Zie ARCHITECTURE.md en MIGRATION_PLAN.md. Eerst M1 zonder schemawijziging, daarna gecontroleerde expand/backfill/verify/cutover. Geen automatische productiemigratie of deployment in deze branchfase.
