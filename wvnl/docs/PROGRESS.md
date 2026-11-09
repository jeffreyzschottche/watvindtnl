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


## CMS-rapportbeheer, aankoop per PDF en zichtbare demo-ads — 8 november 2026

- [x] Productbesluit aangepast: €1,99 koopt één vaste rapportsnapshot. Eerder gekocht blijft bekijkbaar/downloadbaar; een nieuw rapport is een nieuwe aankoop. Geen lifetime-generatie of gratis nieuwe generaties via legacy premium. Historische analyses behouden; oude generatiemogelijkheid gesloten/verwezen.
- [x] Nieuw CMS-tabblad Rapporten & kosten: OpenAI/DeepSeek, gecontroleerde modellen (standaard Luna), maximaal 1–3 herstelpogingen, input-/outputlimieten, maximaal €0,20 generatiebudget, centrale tariefpresets, handmatige rekenkoers, calculator en geaggregeerde token-/kostenmetingen. Geen fictief gemiddelde vóór echte generaties. Expliciete aparte CMS-rapportscope, versieconflicten en auditlog; sleutels blijven backend-only.
- [x] Deterministisch stemoverzicht over eigen geldige antwoorden; historische ambigue records uitgesloten. Geen LLM-bepaalde links/rechtsscore of psychologische labels. Partijovereenkomsten alleen bij voldoende gecontroleerde motiekoppelingen; één motie eenmaal. Feitelijke update-CTA op het aantal nieuwe antwoorden, geen verzonnen politieke verschuiving.
- [x] Optionele korte AI-tekst na afzonderlijke providerconsent; codefallback bij geen toestemming/sleutel, budgetoverschrijding of fout. Minimalisatie, vaste providerendpoints, geen onbeperkte retries, conservatieve reserveringen bij onbekend gebruik. Geen echte betaalde modelaanroep gedaan. Achtergrondqueue is geen OpenAI Batch API.
- [x] Echte eenpagina-A4-PDF met Dompdf 3.1.6, lokale opmaak en uitgeschakelde externe resources/PHP/JS. Snapshot/tekst/PDF versleuteld in de database; alleen eigenaars-API. Een los QA-voorbeeld met fictieve antwoorden is gemarkeerd en blijft buiten de echte rapportadministratie.
- [x] Stripe PHP 22.1.0-adapter: vaste serverprijs, idempotente checkout, gesigneerde en replay-veilige webhook, controle bedrag/valuta/order/sessie, paid-only queuegeneratie en volledige refund-revocatie. Success-redirect ontgrendelt niets. Verkoop staat lokaal uit; geen echte aankoop of sandboxproviderclaim. Native consumable/storekoppeling en operationele releasecontroles blijven open.
- [x] Vier aanvullende rapporttabellen lokaal gemigreerd zonder bestaande gebruikers-/stemtabellen te wijzigen. Voor/na gelijk: 19 gebruikers, 4.598 stellingen, 31.887 argumenten en volledige historische vote-hash. Geen proeforders of rapporten in de echte DB. CMS-koppeling lokaal uitgebreid met expliciete rapportrechten.
- [x] Oorzaak onzichtbare demo: alleen na stemmen en ontbrekende lokale flag. Nu DEMO_ADS=1 / ADS_ENABLED=0; zichtbare plaatsingen in spel (ook vóór stemmen), overzicht, profiel en home. Klikbaar lokaal voorbeeld; sluitbare demo-pauze op de vijfde handmatige Volgende na een bevestigde stem, maximaal eenmaal per twee minuten. Geen politieke tracking, geen ads in koop-/privacyflows.
- [x] 48 backendtests / 294 assertions, 64 CMS-tests / 440 assertions, 10 frontend-unitchecks geslaagd. 32 Playwrightchecks desktop/mobiel geslaagd, inclusief betaling/consent/goedkope herdownload met expliciete fixtures, demo-openen/Escape, vijfde handmatige advertentiepauze en bestaande stemflow. Beide typechecks en productiebuilds geslaagd; CMS heeft de bekende niet-blokkerende Volar/Tailwind-waarschuwingen. Composer-audit na dependency-installatie zonder advisories.
- [x] Echte lokale CMS→WDNL-keten voor lezen/opslaan/kostencalculator gecontroleerd; geen politieke data naar CMS. Browser op 1440/390/320 pixels zonder pageerrors of paginabrede overflow. A4-voorbeeld met pdfinfo op één pagina en visueel gecontroleerd. Mobiele webassets opnieuw gegenereerd/gekopieerd; geen native signing, fysieke-devicecontrole of store-release.

Zie REPORTS.md, PAYMENTS.md, ADS.md en ZSCHOTCMS.md voor configuratie, beperkingen en herstelbeleid. Stripe-keys/webhook, duurzame reportworker, echte sandboxbetalingen, native IAP, inhoudelijke bron-/dimensiekwaliteit en juridische/privacyreview blijven releasevoorwaarden. Deelrefunds/disputen en operationeel herstel vereisen nog een expliciete policy/test. Deze stap claimt geen volledige V2-releasegereedheid.


## Neutraal stemmen en tijdelijke devrapporten — 8 november 2026

- [x] Neutraal / maakt me niet uit / geen voorkeur als volwaardige derde keuze hersteld in de gedeelde stemkaart én backendvalidatie. Wordt opgeslagen, telt mee als antwoord, krijgt eigen percentage/aantal, staat in geschiedenis en verdwijnt uit de pending feed. Dubbele stemmen en e-mailverificatie blijven gecontroleerd.
- [x] Verwarrende tekst over historische neutrale stemmen verwijderd uit de actuele uitslag en nieuwe rapporten/PDF. FAQ en methodologie aangepast. Neutraal levert geen politieke richting of partijovereenkomst op.
- [x] Opvallende tijdelijke devknop in spel en rapportpagina, alleen met Nuxt dev + expliciet toegestane lokale demo-account. API vereist local environment, aangezette serverflag en exact geconfigureerd demo-e-mailadres. Lokale flag aangezet, voorbeeldconfig standaard uit.
- [x] Echte eenpagina-PDF van eigen actuele antwoorden, code-only zonder minimumstemmen/fictieve inhoud, geen betaal- of LLM-aanroep. Encrypted cache, TTL één uur, idempotente aanvraagkey en rate limit. Geen nieuwe tabellen, orders of kostenmetingen. Bekijken en downloaden blijven eigenaar-/omgeving-gecontroleerd.
- [x] 53 backendtests / 337 assertions geslaagd: neutraal opslaan/feeduitsluiting, behoud/duplicate rejection, productieblokkade ondanks actieve flag, niet-demo geweigerd, eigenaarcontrole, encryptie, expiratie en geen betaal/modelrecords. 10 unit tests/typecheck geslaagd; productiebuild geslaagd.
- [x] 36 Playwright desktop/mobiel geslaagd: neutrale stem + eigen percentage + handmatig volgende, bestaande flows, en devknop afwezig zonder dev-accessrequests in productiebuild.
- [x] Echte lokale browsercontrole met appdemo@mail.com op 1440/390/320 pixels: neutrale knop, devknop, generatie, rapportweergave en PDF-download werken zonder pageerrors/overflow. Geen stemmen gewijzigd, geen aankoop of betaalde modelaanroep gestart. pdfinfo bevestigt één A4-pagina. Tijdelijke testsessie ingetrokken; previewcache vervalt vanzelf.

De ontwikkelknop is geen productiebetaalomzeiling. Normale rapportverkoop blijft apart beveiligd. Voor bestaande geïnstalleerde native apps is een nieuwe build nodig; de devknop wordt niet opgenomen als zichtbare bediening in de productiebuild.


## Paarse devknop gebruikt echte OpenAI-generatie — 8 november 2026

- [x] Code-only preview vervangen door een echte Responses API-aanroep met backend-`OPENAI_MODEL` en bestaande `OPENAI_API_KEY`. Devselectie gaat vóór het CMS-model; `MOTION_OPENAI_MODEL` blijft voor oude motie-import. Key niet gewijzigd of naar de client gestuurd.
- [x] Gedeelde provideradapter en tekstvalidatie voor betaalde en devrapporten, met behoud van de betaalde pipeline. GPT-4.1-mini krijgt geen ongeldige reasoning-optie. Devfouten zijn zichtbaar en leveren geen statisch vervangrapport op. Eén aanroep per aanvraagkey, geen automatische retries; budget-, token-, demoaccount- en productiecontroles behouden.
- [x] Gebruikt model, daadwerkelijke tokens en berekende eurokosten zichtbaar bij het tijdelijke rapport; PDF-footer noemt het model en geen onjuiste gratis-AI-claim meer. Browser wacht maximaal zestig seconden op de begrensde serveraanroep. Cache versleuteld en één uur geldig; geen betaalorders of databasewijzigingen.
- [x] 57 backendtests / 363 assertions, 10 frontendtests, typecheck, productiebuild en 36 desktop-/mobiele Playwrightchecks geslaagd, inclusief afwezige devknop en nul dev-accessrequests in productie. Extra checks voor env-model/key, CMS-override, providerfouten zonder fallback, herhaalde aanvraag zonder extra call en ontbrekende key.
- [x] Eén echte klik met de bestaande lokale demoaccount en GPT-4.1-mini: 311 inputtokens, 168 outputtokens, berekend €0,000394. Eén A4-PDF gedownload. Rapportweergave op 1440/390/320 pixels zonder pageerrors of overflow. Geen stem of aankoop gedaan; tijdelijke testsessie ingetrokken. Dit bedrag is een berekening op basis van geconfigureerde tarieven, geen factuurcontrole.


## Visueel kompasrapport en A4-download — 8 november 2026

- [x] Rapport vernieuwd volgens de aangeleverde referentie: WDNL-header, turquoise typebanner, negen mogelijke types in een scherp vectorwiel, themabalken, afzonderlijke economische as, maximaal vijf volledige partijnamen, inzichten en methodiek. Geen partijlogo’s. Mobiele webweergave stapelt de kaarten; de download blijft een A4-compositie.
- [x] Deterministische, geversioneerde dimensieberekening en typekeuze, met gecontroleerde signed weights, richting/gewicht, minimumdekking, bronleeftijd, neutrale uitsluiting van richting, leave-one-out-gevoeligheid en motiededuplicatie binnen én over thema’s. Geen LLM-bepaalde cijfers of psychologische validiteitsclaim. Bestaande gekochte rapporten/PDF’s blijven behouden.
- [x] ZschotCMS-editor uitgebreid met vijf themagewichten per stelling; bestaande contentimport/seeding werkt met hetzelfde veld. Gecontroleerde validation/revision/hash/audit, oude clients behouden mappings. Toepasbare CMS-patch opgenomen voor reproduceerbaarheid. Geen stellingen of wegingen willekeurig in echte data geseed.
- [x] Alleen een nullable metadata-kolom toegevoegd, na versleutelde en roundtrip-gecontroleerde MySQL-backup. 19 gebruikers, 4.598 stellingen, 31.887 argumenten en volledige historische vote-hash ongewijzigd. Backup-/clientcompatibiliteit en rollback gedocumenteerd. Geen productie-uitrol.
- [x] Nieuwe aankopen server-side geblokkeerd zolang een volledig kompas onvoldoende gegevens heeft; voortgang in de eigenaarsinterface. De devknop blijft previews met echte OpenAI-tekst kunnen maken. Nul huidige mappings betekent dat het eigen type/scores nog ‘in opbouw’ tonen. Salesflag blijft daarnaast gesloten. Inhoudelijke review/kalibratie van mappings en de redactionele typeprofielen is nog nodig voor verkoop.
- [x] Eenpagina-PDF met vectorart/selecteerbare tekst en beschermd bestaand downloadendpoint. Ook maximale toegestane teksten en vijf lange partijnamen passen. Duidelijk gemarkeerd fictief voorbeeld downloadbaar op /kompas. PDF ondersteunt geen SVG-gradientpainting; de as gebruikt daarom gelijkwaardige gekleurde vectorstroken.
- [x] 65 backendtests / 407 assertions, 10 frontendchecks, typecheck en webbuild geslaagd; 40 desktop-/mobiele browserchecks geslaagd. CMS-typecheck/build en 5 integratietests / 60 assertions geslaagd (bekende niet-blokkerende CMS-waarschuwingen). Statische mobiele assets gegenereerd/gekopieerd; geen device-/store-release.
- [x] Echte lokale Luna-preview (395 input, 97 output) en eenpagina-download gecontroleerd op 1440/390/320px, geen pageerrors/overflow. Eén eerder modelantwoord zichtbaar geweigerd door tekstvalidatie; afzonderlijke aanvraag slaagde. Geen automatische herstelcall/fallback, geen stemmen of aankoop gewijzigd. Tijdelijke QA-login ingetrokken.

Nog nodig: redactionele weging van echte stellingen, validatie van die inhoudelijke methode, eerder beschreven betaal-/store-/privacyreleasecontroles. Deze stap levert de opmaak en werkende berekenings-/downloadketen; geen gefingeerd gevuld gebruikersprofiel.


## Herstel laadflow tijdelijke rapporten — 8 november 2026

- [x] Actieve lokale API bevestigd op compass-v2.0; het bestaande actuele demo-devrapport is opvraagbaar en toont de nieuwe kompasheader. Geen nieuwe modelaanroep nodig voor deze controle.
- [x] Gekozen rapport onafhankelijk geladen van het aankoop-/rapportoverzicht. Een fout in dat overzicht blokkeert de devpreview/PDF niet meer. Gemeenschappelijke requestversie voorkomt dat trage oude aanvragen de nieuwe selectie of foutmelding overschrijven; vorige rapportweergave wordt bij navigatie gewist.
- [x] Tijdelijke 404/409, verlopen sessies en rate limits krijgen concrete foutmeldingen. Oude tijdelijke layouts worden expliciet geweigerd; de paarse knop controleert het generatieantwoord vóór navigatie. Betaalde historische rapporten behouden hun v1-weergave.
- [x] Zes dev-browserregressies geslaagd met geïsoleerde fixtures; negen backendtests / 69 assertions geslaagd voor productie-/eigenaarsblokkade, modelkeuze, versiecontrole en expiry. Frontend-unitchecks, typecheck en productiebuild geslaagd. Geen gebruikers-/stemwijzigingen of extra modelkosten.


## Generatiefout achter ‘Controleer de invoer’ — 8 november 2026

- [x] Screenshot van /profiel/mijn-kompas gekoppeld aan de werkelijk bewaarde mislukte generatieaanvragen: rapporttekst voldeed niet aan validatie. De prompt verbood alleen nieuwe cijfers, terwijl de validator alle cijfers afwees. Instructie nu expliciet zonder cijfers, ook bestaande invoeraantallen; OpenAI JSON-schema begrenst dezelfde tekens en lengte met een ondersteund pattern. Model/key onveranderd uit backend-env. Geen automatische extra herstelcalls of codefallback.
- [x] Devknop toont de door de rapportservice gecontroleerde, gesanitiseerde 422-melding in plaats van de generieke formuliervertaling. Onbekende ruwe serverdetails blijven afgeschermd. Vier gerichte frontendtests toegevoegd; de aparte dev-browserchecks uitgebreid met deze regressie en de profiel-alias.
- [x] Echte generatie met de paarse knop op exact /profiel/mijn-kompas geslaagd met 18 eigen antwoorden, gpt-6-luna, 417 input-/102 outputtokens en berekend €0,000093. Actuele compass-v2.0-header zichtbaar, geen pageerrors; PDF succesvol gedownload. Voldoende themagewichten ontbreken nog, dus geen type/percentages verzonnen. Tijdelijke QA-token direct ingetrokken.
- [x] 14 frontend-unitchecks en typecheck geslaagd; 22 gerichte backendrapporttests / 179 assertions geslaagd. Bestaande 40 productie-browserflows waren na de laadflowfix geslaagd. Geen stemmen of betalingen gewijzigd.

- [x] Afronding: zeven geïsoleerde dev-browserchecks geslaagd (inclusief de profiel-URL en echte 422-oorzaak), definitieve webbuild en mobiele assetgeneratie/Capacitor-copy geslaagd. De werkelijk gedownloade nieuwe PDF is één A4-pagina.

## Van leeg rapport naar onderbouwde indicatie - 8/9 november 2026

- [x] Achttien legacyformuleringen inhoudelijk beoordeeld en voorzien van weging/onderwerpen voor veertien vraagfamilies. De eigen keuzes zijn niet gebruikt om gewichten op een gewenste uitslag af te stemmen. Procesvragen zonder duidelijke richting krijgen geen geforceerd gewicht. Dit zijn assistent-ondersteunde interpretaties van de vraagtekst, geen wetenschap of verificatie van oorspronkelijke partijposities.
- [x] Reviewdatum/context gescheiden van bronverificatie. Exacte teksthash, rationale en canonieke vraagherkenning toegevoegd. Eigen wegingen en gewijzigde teksten blijven behouden. Seed geaudit en idempotent; alle historische basisitems expliciet historisch, geen verified_at of motiekoppelingen verzonnen.
- [x] Compass-v2.1 toont vanaf twee verschillende antwoorden een voorlopige richting. Een voorlopig type vereist acht gewogen vragen en twee beschikbare thema's. Ontbrekende dimensies worden niet opgevuld. Volledige verkoop vereist nog steeds vijf actuele antwoorden per thema/economische as, tien gewogen vragen en overige releasevoorwaarden.
- [x] Vier dubbele antwoorden eenmaal geteld, een tegenstrijdige groep uitgesloten. Alle oorspronkelijke antwoorden behouden. Nieuwe versleutelde MySQL-backup en hash-roundtrip voor migratie/seed; voor/na gelijk: 19 gebruikers, 4.598 stellingen, 31.887 argumenten en volledige stemhash. Seedherhaling: 0 wijzigingen / 18 unchanged.
- [x] Echte indicatie: negen gewogen vragen, voorlopige De Sociale Vernieuwer, sociale/internationale/groene score en economische indicatie. Progressief heeft een bruikbare vraag en blijft onberekend. Zonder gecontroleerde partijvergelijking toont de kaart echte onderwerpen. Geen matches verzonnen.
- [x] CMS heeft seedknop en bewerkbare onderbouwing, inhoudssleutel, onderwerpen en historische context. Scope- en forwardingtests geslaagd; uitrolpatch opgenomen.
- [x] Live generatie via profielpagina met Luna: 586 input-/115 outputtokens, berekend EUR 0,000117. Gevuld profiel zichtbaar, PDF gedownload, geen pageerrors. PDF is een A4; leesbaarheid later aangescherpt met dezelfde snapshot/tekst zonder extra modelcall. QA-login ingetrokken.
- [x] 73 backendtests / 445 assertions geslaagd; daarna extra aankoopdekking in 13 rapporttests / 113 assertions geslaagd. 14 frontendchecks, typecheck en webbuild, CMS-typecheck/build en 5 CMS-integratietests / 62 assertions geslaagd; bekende niet-blokkerende CMS-waarschuwingen blijven.

Nog nodig: overige inhoud wegen, originele bronnen/partijstemmen verifiëren, beoordeling door de redactie en toetsing van de heuristiek, actuele dekking en eerdere productie-/betalings-/privacyvoorwaarden. Dit is een eerste indicatie, geen bewezen persoonlijkheidstest.
- [x] Afronding: alle 42 desktop-/mobiele browserchecks geslaagd. De drie profielkenmerken volgen uit de echt beschikbare scores, ook bij ontbrekende dimensies; gerichte inhouds-/PDF-tests hiervoor geslaagd. Voorbeeld en mobiele assets bijgewerkt. De tijdelijke persoonlijke preview is voor de beoordeling ververst met dezelfde AI-tekst, zonder extra modelcall.


## Kompasrapport V3 — 9 november 2026

- [x] Oorzaak onderzocht: slechts zes ingekorte titels en twee korte AI-tekstvelden; de eigen stemcontext ging grotendeels verloren.
- [x] Begrensde analysecontext met unieke antwoorden, beschrijving/context en argumenten. De huidige demohistorie levert 13 bruikbare groepen uit 18 antwoorden; dubbelen en conflicterende groepen worden correct behandeld. Grote lijsten: vaste spreiding tot 40, expliciete dekking en achtergrond inkorten vóór antwoorden wegvallen. Geen identiteit naar de provider.
- [x] Echte gestructureerde AI-analyse: inhoudelijke inzichten, vier concrete standpunten, optionele nuance en een reflectievraag. Server valideert verwijzingen/veldgrenzen en weigert verzonnen scores. AI mag geen motieven, diagnoses, veranderde opvattingen of partijstandpunten invullen. Referentievalidering is geen volledige semantische factcheck.
- [x] Twee A4’s met 3D-art via de expliciet gevraagde imagegen-skill. Webweergave en echte PDF gebruiken dezelfde bewaarde gegevens. Selecteerbare tekst en codegrafieken; geen gegenereerde afbeelding met nepgegevens. Openbaar fictief voorbeeld bijgewerkt.
- [x] Maximaal €0,20 blijft configureerbaar via CMS; provider/model blijven CMS-gestuurd voor betaalde rapporten en backend-env-gestuurd voor de paarse knop. Geen extra verplichte AI-rondes of beeldgeneratie per gebruiker. Eerlijke codevariant bij opt-out/fout in betaalde rapporten; devflow gebruikt geen verborgen fallback.
- [x] Oude rapporten behouden, geen database-/seedmigratie of nieuwe dependencies. Nieuwe expliciete toestemmingstekst en methodologie; bron-PDF’s blijven bij de eigenaar.
- [x] Backend: 82 tests / 493 assertions; extra stresstest met lange teksten en codevariant. Frontend: 14 unitchecks, typecheck/build en 44 browserchecks op desktop/mobiel.
- [x] Definitieve live controle: POST 200 via de paarse knop, V3-interface zichtbaar, drie inzichten/vier standpunten, echte PDF-download met twee A4’s, 320/390px zonder horizontale overflow en nul pageerrors. Luna: 4.987 input-/450 outputtokens, berekend €0,000724. Tijdelijke QA-login ingetrokken; geen stemmen of aankopen gewijzigd.
- [x] Zeven geïsoleerde devregressies en mobiele statische generatie/Capacitor-copy voor iOS en Android geslaagd. Geen native binary of fysieke-devicecheck geclaimd.
- Extern/release: echte betalingen, native aankoopconfiguratie, providercontracten/juridische beoordeling, redactionele bronkwaliteit en fysieke-devicechecks blijven buiten deze rapportverbetering.


## Definitieve één-A4-kompasposter V4 — 9 november 2026

- [x] Feedback uitgevoerd: één A4 in de opgegeven referentiecompositie, met daadwerkelijk WDNL-logo, zestientypenwiel, economische as, themabalken, volledige partijnamen en vier persoonlijke observaties. De tweepagina-opmaak wordt niet meer voor nieuwe rapporten gebruikt.
- [x] Zestien expliciete, bereikbare profielen met centroid/voorwaarden/kleur/icoon. Ontbrekende progressieve gegevens leiden niet tot een Vernieuwer; de demo valt nu voorlopig onder De Internationale Verbinder. Geen nieuwe scores verzonnen.
- [x] Echte officiële bronnen gecontroleerd. Vijf bronrecords, acht passende bestaande stellingkoppelingen, één afwijkende tekst veilig overgeslagen. Vier verschillende bruikbare moties voor de demo; vijf voorlopige partijvergelijkingen met teller/noemer. Na JSON-keyvolgordecorrectie is de seed herhaalbaar: nul wijzigingen bij herhaling. CMS-seedknop verwerkt dezelfde gecontroleerde bronnen.
- [x] Op expliciet verzoek drie oude legacyrapporten en 23 preview-/generatiecache-items lokaal verwijderd; nul betaalorders aanwezig. Privé versleuteld herstelbestand; accounts en volledige stemhash behouden. Geen productie-opruiming.
- [x] Nieuwe korte AI-tekst met echte context en verplichte bronverwijzingen; complete zinnen, geen geforceerd afgebroken woorden. Provider/model/budget blijven zoals ingesteld. Definitieve live-aanvraag: Luna, 4.261 input-/165 outputtokens, berekend €0,000509.
- [x] Werkelijke paarse knop → V4-interface → echte download gecontroleerd. Eén A4, circa 247 kB; visueel bekeken. 320/390px zonder paginabrede overflow, nul pageerrors, testsessie ingetrokken.
- [x] Backend 87 tests/544 assertions; frontend 14 unitchecks, typecheck/build, 46 browserchecks en zeven geïsoleerde devregressies geslaagd. Mobiele statische generatie en Capacitor-copy naar iOS/Android geslaagd; daarna de serverbuild hersteld. Geen signing, device- of store-release geclaimd.


## Rapport: witruimte en GSAP-interactie — 9 november 2026

- [x] Webrapport: kaarten sluiten in hoogte op elkaar aan; grotere zestiensegmentencirkel en een profielbeschrijving benutten de ruimte onder het wiel. Partijrijen verdelen de beschikbare hoogte en de methodekaart bevat de echte verdeling voor/tegen/neutraal.
- [x] Elk type is met klikken, pijltjestoetsen, keuzelijst of vorige/volgende te verkennen. De eigen uitslag en scores blijven vast; een expliciete terugknop herstelt de eigen typeweergave. Geen extra generatie, betaling of API-POST bij deze interacties.
- [x] Bestaande GSAP-core gebruikt voor korte kaartopbouw, schaalanimaties van score-/partijbalken, een eenmalige wielintro en de wisselende profielbeschrijving. Alleen zichtbare kaarten worden geanimeerd. Contexten/observers worden bij wisseling en unmount opgeruimd; systeem-reduced-motion en de gedeelde animatievoorkeur werken ook hier.
- [x] 14 frontend-unitchecks, typecheck/build, 50 browserchecks en zeven devregressies geslaagd. Klikken/toetsenbord, onveranderde uitslag, echte antwoordverdeling, gelijke kolomhoogtes, drie viewportbreedtes en beide bewegingsvoorkeuren gecontroleerd. Desktop-/mobiele fixture-screenshots visueel bekeken.
- [x] Backend, stemgegevens, rapportberekening en PDF-generator blijven onaangeroerd. De bestaande PDF is opnieuw op precies één A4 gecontroleerd. Geen modelaanvraag of echte aankoop uitgevoerd.
- [x] Mobiele statische generatie en Capacitor-copy naar iOS/Android geslaagd; daarna de webserverbuild hersteld. Geen native signing, device- of store-release geclaimd.
