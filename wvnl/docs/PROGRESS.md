# WDNL V2 — voortgang

Branch `refactor`, gestart 2026-11-07. Referentie: user masterbrief + drie ontwerpbeelden. Geen productie-uitrol. Lokale aanvullende CMS-migratie en contentseed op 7 november 2026, zie de laatste voortgangssectie.

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

ZschotCMS is lokaal gekoppeld; productie-API en afzonderlijke productietoken blijven te configureren. Stripe/RevenueCat/storeproductconfig, gecertificeerde CMP, native signing/appidentifiers, mail sandbox, encrypted backupstorage/keybeheer en juridische toets nodig. Goedgekeurd origineel molenlogo niet aangetroffen; SVG-concept moet worden beoordeeld. Historische politieke data niet automatisch als nieuwe toestemming behandelen. Geen van deze punten als afgerond/live presenteren.

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

## Vervolg: zichtbare huisstijl en eenvoud

Op gebruikersverzoek de productnavigatie teruggebracht tot vier taken, zie UX.md. Nieuwe witte header/footer met gereconstrueerd molenlogo, navy/blauw/rood tokens, nieuwe homepage met bestaande Nederlandse foto en echte API-preview, compacte authpagina's, eenvoudiger kompas, profielstart, geschiedenis en instellingen. Oude fotobanners verwijderd uit ondersteunende pagina's.

Werkend stellingenoverzicht met server-side zoeken en eigen stemfilters, detailroutes op slug en hetzelfde stemcomponent. De API retourneert daarbij geen individuele stemidentiteiten. FAQ, methodologie en wachtwoord-vergeten toegevoegd; bestaande URLs blijven werken. Geen nieuwe betaal-/privacyfunctionaliteit als afgerond gepresenteerd.

Backend na catalogusuitbreiding: 23 tests, 101 assertions, geslaagd. Eerste ontwerpcontrole: 12 browserchecks desktop/mobiel geslaagd, typecheck en build geslaagd. Laatste nav/lege-toestandaanpassing krijgt eindcontrole hieronder. Bestaande localhost:3000 met echte read-only content gecontroleerd: juiste nieuwe homepage, drie stellingen, geen pageerrors.

Eindcontrole zichtbare ontwerpstap: 23 backendtests / 101 assertions, 10 Vitest-tests, 12 desktop/mobiele browsertests plus 2 aanvullende profielbeveiligingschecks. Typecheck, Nuxt-productiebuild, Prettier-check op gewijzigde bronnen en git diff --check geslaagd. De twee aanvullende profielbeveiligingschecks zijn ook geslaagd. Geen databasemigratie of inhoudelijke seed/import uitgevoerd.

UI-verfijning partijstandpunten: donker driekolomsblok vervangen door lichte tegen-/voorkaarten met compacte logo/afkorting-labels, volledige namen als tooltip en toegankelijke tekst, aantallen en alleen een neutrale groep bij aanwezige data. Ontbrekende/beschadigde logo's krijgen een fallback. Typecheck en git diff --check geslaagd. Chrome-controle op echte /spel-data met 15 partijen: desktophoogte 365px; mobiele weergave zonder interne horizontale overflow. Geen backend- of datawijzigingen.

Onderzoek partijen/logorechten per 2026-11-07: alle 15 bestaande partijrecords en vier ontbrekende actuele Kamervermeldingen onderzocht. Officiële Kamerlijst bevat 17 groepen/fracties (150 zetels); PRO, 50PLUS, Groep Markuszower en Lid Keijzer ontbreken als actuele vermelding. GL-PvdA/NSC historisch behouden. Openbare logo-/huisstijlvoorwaarden met bronnen en beperkingen, asset-hashes en een niet-verstuurd conceptverzoek vastgelegd in PARTY_AUDIT_2026-11-07.md en research/party-audit-2026-11-07.json. Geen rechten verleend/aangevraagd, geen database-/weergavewijzigingen. Ook verouderde importaliases en vaste kompaspartij-ID’s vastgesteld. JSON-consistentie (15/17/150/4) en git diff --check gecontroleerd.

## CMS-contentbeheer en partijen zonder logo’s — 7 november 2026

- [x] Partijlogo’s uit de steminterface, volledige partijnamen zichtbaar zonder afkappen.
- [x] Herhaalbare lokale partijseed: PRO, Groep Markuszower, 50PLUS en Lid Keijzer toegevoegd; 19 records inclusief historische GL-PvdA/NSC. Alle 15 logoverwijzingen verwijderd; bestaande IDs behouden. Privéherstelkopie vooraf gemaakt.
- [x] Bestaande ZschotCMS-projecten en referentie-integraties geïnspecteerd. Echte WDNL-module, drie-tab-contentpaneel, beperkte versleutelde koppeling en verbindingscommando toegevoegd. Geen dependencies toegevoegd; andere projectkoppelingen behouden.
- [x] Afzonderlijke afgeschermde API voor partijen, stellingen, argumenten en moties; geen toegang tot account-/stemdata. Versiecontrole, bron-/publicatievalidatie, atomische batches, audit-voorversies, tijdslimieten, bronallowlist en rate limiting.
- [x] Eenknops-ophaling officiële moties werkt: vijf bronrecords geïmporteerd, herhaling nul duplicaten. UUIDs blijven strings; bronlinks verwijzen naar de exact opgehaalde officiële OData-records. Geen LLM-aanroep of automatische publicatie.
- [x] Verouderde partijaliasing in de oude importer gecorrigeerd: actuele namen uit de DB worden exact herkend; GroenLinks/PvdA niet blind aan een latere coalitie toegeschreven. Volledige oude AI-import en kompas-ID-lijst blijven afzonderlijke technische schuld.
- [x] WDNL 35 backendtests / 179 assertions, CMS 64 backendtests / 432 assertions, frontend 10 unit tests; beide typechecks en productiebuilds geslaagd. Browsercontroles op echte lokale CMS/API: 1440/390/320 pixels, acht argumentvelden, geen pageerrors/overflow. Zie ZSCHOTCMS.md.
- [x] Integriteitscheck na seed/import: gebruikers/stellingen/argumenten gelijk; volledige historische vote-JSON-hash gelijk. Tijdelijke browsertoken na controle ingetrokken.

Lokaal paneel: http://localhost:3100/projects/watdenktnederland/integrations/5. Productie niet uitgerold; daarvoor backend/CMS deployen en HTTPS-productie-API met eigen beperkte token configureren. De bestaande CMS-map heeft geen Git-repository. De volledige queue/LLM/reviewpipeline, genormaliseerde stemmen/consent en overige V2-mijlpalen blijven open; deze concrete contentbeheerstap is geen claim dat heel M3 of V2 gereed is.

## Eigen stellingen, bronvermelding en interactie — 7 november 2026

- [x] CMS-editor: eigen maatschappelijke stelling, nieuwsaanleiding of officiële motie. Eigen stellingen kunnen zonder externe bron of motie; acht verschillende argumenten en review blijven vereist. Nieuws vereist bronnaam/link/datum; een motiestelling een bestaande motie. Geen geautomatiseerde nieuwsimport toegevoegd.
- [x] Nullable herkomstvelden toegevoegd aan CMS-metadata via één aanvullende lokale migratie. Publieke API geeft herkomst, bronnaam/titel/link en datum door; gebundeld geladen, zonder politieke identiteiten. Bestaande bronloze inhoud wordt niet achteraf als nieuws of gecontroleerde motie gelabeld.
- [x] Homepage, FAQ, methodologie en stellingdetail verduidelijken de eigen redactionele formulering en bronverwijzing. Nieuwsbronnen worden niet als samenwerkingspartner of onderschrijver gepresenteerd.
- [x] GSAP 3.15.1 uit de officiële npm-registry exact vastgezet. Korte revealanimaties, kaartinteracties, stembevestiging/uitslagbalk en confetti, gelijk voor tegen/voor en uitsluitend na serverbevestiging. Handmatig volgende behouden. Systeemvoorkeur en een bewaarde gebruikerskeuze kunnen animaties uitschakelen.
- [x] Nieuwe buurtillustratie met ingebouwde imagegen-tool gemaakt en in public/illustrations opgeslagen. Prompt en herkomst in ASSETS.md. In homepage en feed-empty-state gebruikt; geen documentaire nieuwsafbeelding.
- [x] Homepage met echte-stellingpreview, onderwerp-shortcuts naar zoekfunctie, maatschappelijke uitleg en buurtillustratie. Stemfeed toont eigen beantwoorde aantal; geen fictieve aantallen/streaks.
- [x] WDNL 37 backendtests / 194 assertions; 10 Vitest-tests; frontend-typecheck en productiebuild geslaagd. CMS-typecheck en productiebuild geslaagd, met de eerder bekende niet-blokkerende Volar/Tailwind-waarschuwingen.
- [x] 22 desktop/mobiele browserchecks voor bestaand ontwerp/stemgedrag plus beide confettikeuzes, geen confetti bij reduced-motion, bewaarde opt-out en bronvermelding zonder artikelophaling. Willekeurige feedvolgorde wordt in de test gerespecteerd.
- [x] Echte lokale browsercontrole 1440/390/320 pixels: homepage/afbeelding laden, bronsoortkeuze en optionele/verplichte CMS-velden werken, geen pageerrors of paginabrede overflow. Geen teststellingen of stemmen aan de echte database toegevoegd. Tijdelijke CMS-browsersessie ingetrokken.
- [x] Bestaande gebruikers/stellingen/argumenten en volledige historische vote-hash vóór/na gelijk. Partijcatalogus blijft 19 records zonder logoverwijzingen; vijf eerder geïmporteerde officiële bronmoties behouden.

De wijzigingen betreffen de gedeelde Nuxt/Capacitor-interface. `npm run build:mobile` toegevoegd en geslaagd: statische index-entry gegenereerd en webassets inclusief illustratie naar Android en iOS gekopieerd. Alleen `nuxt build` levert de Node-serverbuild en is geen geldige Capacitor-entry. Geen productie-uitrol, signing of store-release uitgevoerd. Een nieuwe native binary/devicecontrole blijft een aparte releasecontrole. Bestaande dependency-scan meldt nog dezelfde 12 transitieve kwetsbaarheden; deze stap claimt geen volledige V2-releasegereedheid.

## Rijker ontwerp, 3D-art en Laws of UX — 8 november 2026

- [x] Homepage opnieuw vormgegeven met lichtblauwe hero, prominent 3D-buurtje, gelaagde kaarten, onderwerpsnelkoppelingen, visuele procesuitleg en donkere kompassectie. Echte stellingen blijven uit dezelfde API komen; geen verzonnen aantallen of scores.
- [x] Twee transparante 3D-illustraties gemaakt met ingebouwde imagegen-tool en geoptimaliseerd tot WebP. Nieuw kompas op home en productpagina; bestaande buurtillustratie hergebruikt. Definitieve prompts/herkomst/conversie in `docs/design/v3-assets.json` en ASSETS.md.
- [x] Manrope variable font lokaal geladen en gepreload. Voorheen alleen Inter vermeld in CSS zonder fontbestand. Officiële OFL 1.1 met copyrightmelding opgenomen in public/fonts; geen externe fontrequests. Licentie en bron gecontroleerd bij de officiële Google Fonts-repository. Commercieel gebruik/bundeling toegestaan onder de licentievoorwaarden, geen claim van publiek domein.
- [x] GSAP-floats en lichte pointerparallax, directe pauzeknop, bewaarde globale voorkeur en systeem-reduced-motion. Animaties pauzeren buiten beeld/verborgen tabs; lifecyclecleanup aanwezig. Scrollbeweging respecteert ook de globale opt-out.
- [x] Gedeelde navigatie, formulieren, profiel, overzicht en stemkaart krijgen dezelfde typografie, diepte en focusstijl. Voor/tegen blijven gelijkwaardig. Geen automatische overgang na een stem.
- [x] Laws of UX concreet toegepast en vastgelegd in UX.md: vier hoofdtaken, duidelijke CTA-hiërarchie, grotere klikvlakken, gegroepeerde accountfuncties en uitklapbare achtergrond. Stemmen opent niet automatisch alle achtergrondinformatie; handmatig geopende inhoud blijft staan tot de volgende stelling.
- [x] Zoekfeedback met aantal zichtbare stellingen/laad-/foutmelding, zoekopdracht wissen met focusherstel, grotere filters. Meld-/deelknoppen rustiger gemaakt, popovers via toetsenbord en Escape bruikbaar, actieve mobiele accountsectie met aria-current. Geen nieuwe privacy-/betaalfunctionaliteit gesimuleerd.
- [x] 10 Vitest-tests, Nuxt-typecheck en productiebuild geslaagd. 26 desktop/mobiele Playwrightchecks geslaagd, inclusief lokale font/art, parallax, animatiepauze/reduced-motion, zoekfeedback/wissen/focus, meldpopover/escape en blijvende handmatige stemflow. API-fixtures; geen teststemmen in de echte database.
- [x] Read-only Chromecontrole echte lokale site op 1440/390/320 pixels: art en Manrope laden, geen externe fontrequests, pageerrors of consolewaarschuwingen. Kompaspagina aanvullend gecontroleerd op 320/390/721/768/900/1024/1200 pixels; geen paginabrede horizontale overflow. Screenshots lokaal in `/tmp/wdnl-v3` beoordeeld.
- [x] `npm run build:mobile` geslaagd: nieuwe WebP-art, font en OFL naar beide native projecten gekopieerd. Dit is de gedeelde webinterface; native binaries, deviceprestaties en signing/store-release blijven afzonderlijke controles.

Geen backend-, CMS-, database- of dependencywijziging in deze ontwerpstap. Bestaande releaseblokkades uit eerdere mijlpalen blijven open. Een gebruikerstest en volledige WCAG-audit zijn hiermee niet vervangen.
