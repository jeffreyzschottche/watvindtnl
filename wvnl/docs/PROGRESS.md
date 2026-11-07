# WDNL V2 — voortgang

Branch `refactor`, gestart 2026-11-07. Referentie: user masterbrief + drie ontwerpbeelden. Geen productie-uitrol of datamigratie.

## Geanalyseerd

- [x] Git, frontend/API/native, Docker/Coolify, routes/models/migraties, auth, stemmen, content, AI, tracking, tests.
- [x] Lokale read-only schemacontrole en aantallen; historische neutraal en schemadrift vastgesteld.
- [x] Officiële dependencyregistries/releasenotes en kwetsbaarheidsscans.
- [x] Architectuur, backup/cutover/rollback en mijlpalen beschreven.

## Implementatie

- [x] M1 veilige basis: publieke politieke ID-lijsten gesloten, resultaten alleen na eigen stem, binary nieuwe stemmen, duplicate rejection en tijdelijke rowlocktransacties; legacy neutraal behouden.
- [x] Publieke mail/migratiehulproutes verwijderd; LLM-generatie uit nieuws-GET en admin-only. Self-premium geblokkeerd, secretveld verborgen, tokenrevocation/nieuwe tokenexpiratie, eigenaarschapsregressies, configureerbare limiters en API-headers.
- [x] Uitslag blijft staan tot handmatig volgende. Ook delen/detailmodal gebruikt geaggregeerd contract; geen client-side reconstructie van andere stemmers.
- [x] Tracking automatisch uit, onafhankelijke demo/liveflags, lokale demo-AdSlot na afronden, zoom/reduced-motion/skiplink en eerste V2-stemkaart.
- [x] Node24 vastgelegd, Nuxt/Vue/Router/Capacitor7 en Composer-patches, typecheck/test/buildscripts, CMS-typefouten hersteld.
- [x] SVG-molenconcept ter beoordeling toegevoegd; geen finale logoapproval aangenomen. Overige pagina's blijven legacy tot M4.
- [ ] M2 genormaliseerde votes, consent, auth/RBAC en privacyflows.
- [ ] M3 contentpipeline/CMS/review.
- [ ] M4 complete nieuwe website.
- [ ] M5 deterministisch kompas/PDF/Stripe/entitlements.
- [ ] M6 native apps/RevenueCat.
- [ ] M7 CMP/AdSense/AdMob.
- [ ] M8 hardening/release.

## Baseline checks

Lokale login + /me: HTTP200. CORS preflight: 204. npm audit: 49 issues (7 critical/25 high/12 moderate/5 low). Composer audit: meerdere advisories; nog geen schone scan. Geen producttests vóór M1.

## Externe configuratie / open beslissingen

ZschotCMS-contract ontbreekt. Stripe/RevenueCat/storeproductconfig, gecertificeerde CMP, native signing/appidentifiers, mail sandbox, encrypted backupstorage/keybeheer en juridische toets nodig. Goedgekeurd origineel molenlogo niet aangetroffen; SVG-concept moet worden beoordeeld. Historische politieke data niet automatisch als nieuwe toestemming behandelen. Geen van deze punten als afgerond/live presenteren.

## M1 verificatie

- Backend na dependencyupdates: 20 tests geslaagd, 82 assertions; uitsluitend SQLite :memory:. Geen bewijs van MySQL-concurrency onder belasting.
- Frontend: 10 Vitest-tests geslaagd. Typecheck geslaagd. Nuxt-productiebuild geslaagd met Node24.20.0.
- Playwright desktop/mobile: 4 tests geslaagd op de definitieve build, inclusief demo-AdSlot na stemmen. Expliciete API-fixtures, geen MySQL-schrijfacties.
- Composer update binnen bestaande majorgrenzen: Laravel12.71.0, Sanctum4.3.3; scan meldt geen advisories.
- npm: 49 -> 12 meldingen, geen critical meer, resterend 1 high/5 moderate/6 low. Transitieve glob/minimatch/deepmerge-keten blijft releaseblokkade. Geen ongeteste majoroverride toegepast.
- Read-only controle na tests: nog steeds 19 users, 4.598 issues, 31.887 argumenten; stemlidmaatschappen 29 voor/24 tegen/13 neutraal. Geen schemawijziging/backfill/import uitgevoerd.
- git diff --check geslaagd. Native builds, podsync, juridische beoordeling, volledige E2E en productie-release niet uitgevoerd.

## Eerstvolgende stap

M2 start met schema-/migratieinventarisatie plus een echte versleutelde backup en geïsoleerde restoretest; daarna pas nieuwe vote-/consenttabellen en idempotente backfill. Het bestaande SQL-bestand geldt niet als restorebewijs. Het totale V2-programma is nog niet afgerond.

Lokale HTTP-smokecheck na M1: publieke issue-GET geeft HTTP200, votes=null, user_vote=null en private/no-store. Browseropslag en echte devices nog niet volledig getest.
