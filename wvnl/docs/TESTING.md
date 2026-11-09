# Tests

API: vanuit ../wvnl-api `php artisan test` met phpunit.xml SQLite :memory:, array cache/mail en sync testqueue. Nooit MySQL productie selecteren voor tests, migrate:fresh of RefreshDatabase. M1-regressies voor publieke identiteitlekken, resultgating, dubbele stemmen, transactionele updates, binary-only nieuwe stemmen, legacy neutraal, ownership, premium, login/verificatie/logout/revocation, rate limits, headers, no-LLM GET en verwijderde hulproutes. Geen mail versturen/LLM/betaling in tests.

Frontend: npm test voor logische stemresultaten/handmatige navigatie en demo-adflags; npm run typecheck; npm run build. Node24 via nvm. Leg daadwerkelijke resultaten vast in PROGRESS.md; scripts/testbestanden alleen bestaan is geen geslaagde verificatie.

Nog nodig vóór release: Playwright E2E (registreren/consent/inloggen/stemmen/resultaat/handmatig volgende/feed-exclusie/catalogus/historie), betaalwebhooks sandbox/refund/restore, export/delete, consent/adnetworkinspectie, native devices, WCAG-toets, MySQL-concurrency met tientallen identieke/parallelle votes en invariantcounts, loadtest, securityautorisatie/SSRF, backups terugzetten. SQLite tests bewijzen geen rowlockconcurrency. Geen claims over percentage testdekking zonder meetrapport.

Browserregressies: npm run test:e2e na npm run build. Playwright start/ruimt een tijdelijke server op poort3010 op en gebruikt lokaal bestaande Chrome, desktop en Pixel7-viewport. API-fixtures zijn expliciet testdata en raken MySQL niet. Controleert geen voortijdige percentages, links/rechts, blijvende uitslag, handmatig volgende, mislukte stemmen en geen trackingrequests; demo-AdSlot na afronden. Dit vervangt geen live backend/client E2E of echte Android/iOS-devicecheck.

Nieuwe regressies: eigen stellingen zonder motie/bron; nieuws met verplichte bronnaam/link/datum; publieke herkomstserialisatie; confetti bij beide stemkeuzes; geen confetti bij mislukking of reduced-motion; gedeelde animatievoorkeur na reload; geen externe artikelophaling bij bronweergave. API-tests blijven uitsluitend SQLite :memory:, browserfixtures uitsluitend op de tijdelijke testserver.

`npm run build:mobile` is lokaal geslaagd: de statische index en nieuwe webassets staan in de Android-/iOS-projecten. Dit is een asset-/packagingcontrole; signing, simulator/device-WebView en store-binaries zijn niet getest.


## Kompasrapport V2

`CompassReportsTest` dekt de vaste formule, richting/gewicht, minimale dekking, neutrale/onbeoordeelde/oude data, deduplicatie van moties binnen én over thema’s, economische as los van de thema’s, PDF met maximale teksten/vijf partijen en behoud van legacy-PDF. CMS-tests dekken toegestane keys/gewichten, audit en behoud bij oudere clients. Rapporttests weigeren checkout bij ontbrekende themadekking. De hele backend: 65 tests / 407 assertions geslaagd.

40 Playwrightchecks op desktop/mobiel geslaagd, inclusief de nieuwe typekaart, wheelasset, scores/spectrum, volledige partijnamen, PDF-knop, ontbrekende scores zonder verzonnen percentage/type en 320/390/1440px zonder overflow. De bestaande legacyrapport-, privacy-, stem- en productie-devblokkadechecks blijven behouden. De betaal-/rapportfixtures gebruiken geen echte aankopen of stemmen.

Live lokaal: het bestaande backend-env-model was bij de controle `gpt-6-luna`; een echte generatie leverde 395 input- en 97 outputtokens. Een eerste generatie werd door de tekstvalidatie afgewezen; zichtbaar foutpad, geen codefallback of automatische retry. Een afzonderlijke diagnostische aanvraag slaagde zonder ruwe tekst/key te loggen. De geslaagde preview is op 1440/390/320px geopend en als één A4 gedownload. De testlogin is ingetrokken. Er zijn nul redactionele mappings in de lokale DB; het echte rapport toont dus correct ‘in opbouw’. Geen partijpercentages uit testdata worden als echte resultaten getoond.

Webcheck (`npm run check`), CMS-typecheck/build en 5 CMS-koppeltests / 60 assertions geslaagd. CMS behoudt de reeds bekende niet-blokkerende Volar/Tailwind-waarschuwingen. Mobiele statische generatie en Capacitor-copy geslaagd; geen native binary/device-download- of storetest geclaimd.


## Laden van tijdelijke rapporten

Met een actieve Nuxt-devserver op poort 3000: `npm run test:dev-reports`. Zes afzonderlijke browserregressies met volledig onderschept API-verkeer, zonder echte gebruikersdata/modelkosten: een geldig devrapport bij falend overzicht, de paarse knop naar de nieuwe V2-weergave, een vertraagde oude aanvraag na een nieuwe selectie, een verlopen preview zonder achterblijvend vorig rapport, een oude cached layout en een verouderd generatieantwoord. Geen pageerrors.

De pagina laadt het gekozen rapport onafhankelijk van het aankoopoverzicht en bewaakt alle antwoorden met dezelfde requestversie. Bij een nieuwe selectie wordt het vorige rapport verwijderd. 404/409 voor previews krijgen herstelbare meldingen. Alleen tijdelijke previews met de actuele snapshotversie zijn opvraagbaar; betaalde historische rapporten blijven ondersteund. De gerichte backendtests voor deze devflow: 9 tests / 69 assertions.


Aanvulling generatie: `tests/report-generation.test.ts` controleert de zichtbare gecontroleerde 422-oorzaak, gesanitiseerde providerdiagnostiek, verborgen overige serverdetails en timeouts. 14 frontend-unitchecks totaal. `npm run test:dev-reports` bevat nu ook de profiel-alias en een afgewezen modelantwoord dat als echte servicefout zichtbaar moet zijn. Live generatie op /profiel/mijn-kompas: 18 antwoorden, Luna, 417 input-/102 outputtokens, circa €0,000093; actuele layout en PDF-download bevestigd, geen pageerrors. Geen betaalde purchase of stemmutatie.

## Inhoudelijke kompasweging v2.1

73 backendtests / 445 assertions: twee-versus-vijf dekking, historische indicatie, ontbrekende dimensies niet invullen, dubbele/tegenstrijdige groepen, gewijzigde teksthash, behoud eigen weging, seedherhaling, stemmen behouden, aparte bronverificatie en contentrechten. Extra aankoopbescherming voor een voorlopige economische as is daarna gecontroleerd in 13 rapporttests / 113 assertions.

De nieuwe browserregressie controleert voorlopig type, echte onderwerpen, deduplicatie en een onberekende score op 1440/390/320px. De eerste ronde miste alleen de twee nieuwe verwachtingen doordat de tekst nog de volledige drempel vijf toonde; dit is gecorrigeerd naar de vroege drempel twee.

CMS: 5 koppeltests / 62 assertions, inclusief de seedactie. Geen echte stemmen of betalingen via fixtures. Afzonderlijk live: achttien demo-antwoorden, een Luna-aanroep (586 input-/115 outputtokens), gevuld profiel en een A4-download; tijdelijke login ingetrokken. Geen fictieve inhoud als persoonlijke uitslag gepresenteerd.

Afronding: 42 browserchecks geslaagd. Extra test controleert dat profielkenmerken geen ontbrekende dimensie invullen en een lage groene score niet als duurzaam kenmerk weergeven. Deze inhouds-/PDF-groep heeft 13 geslaagde tests / 59 assertions. Mobiele assetgeneratie en Capacitor-copy geslaagd; geen fysieke-device- of store-release geclaimd.


## Rapport V3 — 9 november 2026

- Backend: 82 tests / 493 assertions. Nieuwe tests dekken alle unieke antwoorden i.p.v. zes titels, neutraal, deduplicatie/conflicten, verwijzingen naar bestaande antwoorden, ongeldige cijfers en dubbele standpunten, grote historie/selectie/invoerbudget, expliciete codevariant en twee A4’s met maximale tekstlengtes. Oude PDF’s, eigenaarscontrole, productiegating en betaal-/budgetregressies blijven slagen. Uitsluitend SQLite :memory:, geen echte provider-/betaalverzoeken.
- Frontend: 14 unitchecks, typecheck en build geslaagd. 44 browserchecks desktop/mobiel, inclusief concrete V3-inzichten, echte keuze-labels, reflectievraag, verwijzingen, geladen artwork en 320/390/1440px zonder paginabrede overflow. Zeven geïsoleerde Nuxt-devregressies blijven slagen. Nieuwe toestemmingscopy expliciet getest; oude rapportfixtures blijven leesbaar.
- Live lokale demo: werkelijke Nuxt-paarse-knop → Laravel → OpenAI → PDF-download. 18 antwoorden leveren 13 unieke eenduidige groepen, negen gewogen antwoorden en een voorlopig type. Drie tekstinzichten en vier concrete standpunten staan werkelijk op de pagina. Definitieve Luna-aanvraag: 4.987 input-/450 outputtokens; berekend €0,000724 op de ingestelde tarieven, geen facturatiegarantie. De eerste aanvraag was HTTP 400/invalid_prompt; een aparte diagnose en de uiteindelijke flow slaagden. Geen automatische retry of verborgen fallback.
- PDF visueel gecontroleerd op beide pagina’s: twee A4’s (pdfinfo), selecteerbare tekst, circa 665 kB, lokale 3D-art, keuze-labels en ontdubbelde klikbare bronverwijzingen. Reële PDF/JSON/screenshots blijven privé onder /tmp; nooit naar public/Git gekopieerd. Tijdelijke testsessie ingetrokken. Geen stemmutaties, betaling of productie-uitrol.
- Openbaar voorbeeld opnieuw uit expliciet fictieve fixture opgebouwd en op twee A4’s gecontroleerd. Imagegen-art met transparante alpha gecontroleerd. Mobiele statische generatie en assetkopie naar beide native projecten geslaagd; signing, stores en fysieke WebViews niet getest.

Vorm-/referentievalidatie is geen volledige semantische factcheck van AI-tekst. Redactionele kwaliteit, gevoelige-gegevensafspraken en betaal-/native-releasecontroles blijven nodig; verkoopgating is behouden.


## Eén-A4-poster V4

87 backendtests / 544 assertions geslaagd. Nieuwe checks: alle zestien typen uniek en bereikbaar, bijbehorende printassets aanwezig, geen vernieuwingsprofiel zonder progressieve data, echte unieke partijmoties en uitsluiting conflicterende antwoorden, voorlopige partijdekking, bestaande antwoord-ID’s in AI-observaties en één A4 met logo/typewiel/partijen/vier observaties. Bestaande privacy-/auth-/betalings-/V3-regressies blijven ondersteund.

14 frontend-unitchecks, typecheck en build geslaagd; 46 Playwrightchecks op desktop/mobiel. De nieuwe test controleert de éénkaartcompositie, zeven gevraagde onderdelen, vier observaties, vijf partijkoppelingen, zestien catalogustypen, geladen logo/wiel, onderbouwing en 320/390/1440px zonder overflow. Zeven geïsoleerde devregressies blijven slagen.

Live: de daadwerkelijke paarse knop genereert `compass-poster-v4` via het bestaande env-model Luna. Definitieve aanvraag 4.261 input-/165 outputtokens, berekend €0,000509 volgens ingestelde tarieven. Modeltekst en referenties gecontroleerd; een eerdere testzin bleek ondanks de oude tekenlimiet afgebroken, waarna schema en server complete eindinterpunctie verplichten en de prompt één kort voorbeeld per kenmerk vraagt. Geen verborgen tekstafkapping of fallback bij devgeneratie.

Gedownloade PDF: pdfinfo bevestigt één A4 (595,28 × 841,89pt), circa 247 kB. Visueel beoordeeld: alle zes blokken, volledig logo, alle zestien wielsegmenten, volledige zinnen en footer op dezelfde pagina. De oorspronkelijke vectorrenderer verloor enkele segmenten; de gevalideerde printafbeeldingen voorkomen dat. Echte JSON/PDF/screenshots blijven privé onder /tmp, openbare voorbeelddata is fictief.

Lokale verwijdering uitgevoerd op expliciet verzoek. Drie legacyrecords en 23 oude cache-items verwijderd, geen betaalorders aanwezig. Accounts en volledige stemhash voor/na identiek. Officiële bronseed is na correctie idempotent; laatste herhaling nul updates. Geen productiedatabase benaderd of gewijzigd.


## Interactief webrapport

14 frontend-unitchecks, typecheck en productiebuild geslaagd; 50 Playwrightchecks op desktop/mobiel en zeven geïsoleerde devregressies. Nieuw: segmentklik, toetsenbordfocus/pijltjestoetsen, dropdown, vorige/volgende en reset. De persoonlijke typekop en scores veranderen niet tijdens verkenning, er ontstaan geen API-POSTs. De antwoordmix gebruikt de echte bewaarde tellingen (ook nulwaarden). De tests controleren gelijke boven-/onderkolomhoogtes en 1440/390/320px zonder overflow.

Reduced-motion en de gedeelde opt-out worden afzonderlijk gecontroleerd: inhoud en typebediening blijven beschikbaar, zonder tijdelijke GSAP-transforms. Een eerste testvergelijking begon vóór het asynchrone rapport geladen was; de test wacht nu op de vier aanwezige themascores. Geen willekeurige sleep of automatische retry toegevoegd. Screenshots met expliciet fictieve rapportdata visueel bekeken; echte gebruikersdata/modelcalls zijn niet gebruikt. PDF-rendercode is niet gewijzigd; pdfinfo op de bestaande download bevestigt één A4.
