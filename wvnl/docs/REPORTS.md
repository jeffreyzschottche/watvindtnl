# Persoonlijke stemrapporten — 8 november 2026

## Productbesluit

€1,99 koopt **één vast persoonlijk A4-rapport** van de op dat moment vastgelegde antwoorden. Een nieuw rapport is een nieuwe aankoop, ook voor bestaande premiumaccounts. Eerder gekochte rapporten kunnen binnen hetzelfde account opnieuw worden bekeken en gedownload zonder LLM-aanroep of nieuwe betaling. Dit vervangt het eerdere voorstel `political_compass_lifetime`.

`/profiel/mijn-kompas` en `/kompas/rapport` delen één geauthenticeerde interface. Een success-querystring ontgrendelt niets. Pending, verwerking, klaar, mislukking, annulering en terugbetaling hebben eigen toestanden. Nieuwe aankopen in native clients zijn nog uitgeschakeld; er wordt geen Stripe-checkout aangeboden in de Capacitor-app.

## Vanuit ZschotCMS

Open http://localhost:3100/projects/watdenktnederland/integrations/5 en kies **Rapporten & kosten**.

- Provider: OpenAI of DeepSeek. De server beperkt de modelnamen tot de gecontroleerde catalogus in `config/wdnl_reports.php`. Standaard OpenAI / `gpt-6-luna`. Toekomstige providers vergen een aparte serveradapter, geen willekeurige endpoint-URL.
- AI-toelichting aan/uit. De gebruiker geeft daarnaast afzonderlijk optioneel toestemming voor doorgifte aan de gekozen provider. Zonder toestemming, sleutel of geldige AI-uitkomst werkt het rapport met deterministische toelichting.
- Maximaal 1–3 pogingen, standaard 1. Een tweede poging dient alleen om ongeldige tekst te herstellen, niet om elk rapport standaard meerdere keren te schrijven. Onbekend gebruik na een verbindingsfout wordt conservatief gereserveerd en niet blind opnieuw geprobeerd.
- Inputbytes, outputtokens inclusief redeneertokens, per-model tokenprijzen, handmatige USD/EUR-rekenkoers, veiligheidsfactor en **maximaal €0,20 AI-budget per rapport**. Modelwijzigingen nemen de centrale tariefpreset over. De backend weigert tarieven onder de gecontroleerde ongecachete/piekpreset.
- Minimumaantal stemmen (minimaal 10) en aantal nieuwe antwoorden vóór een updatevoorstel (standaard 20).
- Rekenen zonder LLM: een kostenvoorbeeld met de opgeslagen instellingen. Geen API-aanroep, geen proefrapport en geen fictieve meting.
- Gemiddelde/totaal berekende AI-kosten, rapport- en pogingenaantallen, input/outputgebruik en pogingen met onbekend gebruik. Het gemiddelde is leeg zolang er geen rapporten zijn gegenereerd. De kosten omvatten mislukte pogingen; onbekend gebruik telt met de reservering mee.

Het kostenplafond is een toelatingscontrole op basis van ingestelde tarieven, token-/bytegrenzen en reserveringen. Het is geen facturatiegarantie bij gewijzigde providerprijzen of onjuist beheerde wisselkoersen. De PDF-opmaak zelf gebruikt geen generatietokens. De calculator gebruikt standaard 10.000 input-/1.500 outputtokens en een veiligheidsfactor 1,25: bij Luna circa €0,002188 voor één poging met rekenkoers 1 USD = €1. Dit is een configureerbaar rekenvoorbeeld, geen actuele valutakoers of al gemeten rapportprijs.

Instellingen zijn versiegecontroleerd (409 bij een oude revision), worden geaudit en gelden voor nieuwe aankopen. Elke order bewaart de toen geldende instellingen. Providercredentials blijven uitsluitend in de WDNL-serverconfiguratie; het CMS ziet alleen of een sleutel aanwezig is. De CMS-koppeling vereist aparte `cms:reports:read/write`-rechten en ontvangt uitsluitend configuratie en geaggregeerde gebruiksmetingen, geen individuele rapporten of stemdata.

## Berekening en PDF

`VoteSnapshot` gebruikt alle geldige eigen stemrecords uit de bestaande historie, tot een expliciet begrensde maximumomvang van 20.000 IDs. Ontbrekende records en ambigue historische stemmen worden uitgesloten. Voor/tegenverhoudingen worden nooit vertaald naar links/rechts of een persoonlijkheidstype. Er zijn nog geen gecontroleerde dimensiemappings; die worden niet door een LLM ingevuld.

Partijovereenkomsten gebruiken uitsluitend stellingen met een gecontroleerde motiekoppeling, binaire eigen keuze en een expliciet vastgelegde binaire partijstem. Eén motie telt eenmaal, ook bij meerdere afgeleide stellingen. Neutraal, afwezig/ontbrekend en ambigue partijkeuzes worden uitgesloten. Een partij wordt pas getoond bij tien vergelijkbare moties. Dit is geen onafhankelijke bronvalidatie: de redactionele koppeling moet nog steeds inhoudelijk juist zijn.

Het rapport bewaart een onveranderlijke snapshot, berekeningsversie en generatiedatum. `ReportWriter` stuurt alleen tellingen, beschikbare overeenkomsten en maximaal zes korte antwoordvoorbeelden naar de provider: geen naam, e-mail, account-ID, zelfverklaarde partijvoorkeur of volledige motiedocumenten. Externe tekst wordt als onbetrouwbare data behandeld. AI mag slechts twee korte JSON-tekstvelden leveren; code vult cijfers/grafieken in. Output met cijfers, HTML/URLs of onjuiste vorm wordt geweigerd. Dit bewijst niet automatisch de feitelijke neutraliteit van vrije tekst; kwaliteitsevaluatie blijft nodig.

`ReportPdf` gebruikt Dompdf 3.1.6 met remote resources, PHP en JavaScript uitgeschakeld. Het vaste Blade-sjabloon escapet inhoud, gebruikt lokale fonts en controleert precies één A4-pagina. Een PDF en de politieke snapshot/tekst worden met Laravel-encryptie opgeslagen en zijn alleen via de eigenaars-API toegankelijk. De gedownloade PDF is een gewoon leesbaar document; beschermingsclaims gelden voor opslag/toegang in WDNL, niet voor door de gebruiker gedeelde bestanden. Bewaar APP_KEY en versleutelde backups veilig: zonder sleutel zijn opgeslagen rapporten niet te herstellen.

De nieuwe queue `reports` voert het werk uit; geen LLM of PDF-generatie tijdens een stemactie. Dit implementeert **geen OpenAI Batch API**. De eerste aflevering gebruikt gewone begrensde modelaanvragen in een achtergrondjob. API Batch voor optionele latere verwerking blijft een aparte keuze vanwege de mogelijke wachttijd.

## Eerlijke updatevoorstellen

Een updatevoorstel verschijnt bij het ingestelde aantal nieuwe geldige antwoorden sinds het vorige beschikbare rapport. De tekst benoemt dat aantal en de prijs. Er wordt niet beweerd dat de politieke voorkeur is verschoven op basis van alleen een ander vraagaanbod, meer stemmen of een veranderd voor/tegenpercentage. Er zijn geen fictieve meldingen, timers of automatische herhaalaankopen.

## Lokale migratie en configuratie

Alleen vier nieuwe tabellen worden toegevoegd met `2026_11_08_120000_create_report_purchase_tables.php`: settings, orders, attempts en payment_events. Geen bestaande gebruikers-/stemtabellen worden aangepast. Vóór en na de lokale migratie zijn aantallen en volledige historische vote-hash gelijk bevonden. Geen proeforders of rapporten zijn in de echte database geplaatst.

```sh
# WDNL API — voer alleen deze aanvullende migratie uit na review/back-upbeleid.
php artisan migrate --path=database/migrations/2026_11_08_120000_create_report_purchase_tables.php --force
php artisan cms:content-token --reports --output=/veilige/map/wdnl-cms-token

# ZschotCMS/backend
php artisan cms:connect-wdnl --api=https://api.watdenktnl.nl/api --token-file=/veilige/map/wdnl-cms-token
```

De lokale koppeling is uitgebreid met deze rechten. Oude contentsleutels krijgen niet automatisch rapportrechten. Houd nieuwe secrets buiten Git en terminaloutput; trek vervangen sleutels in nadat de nieuwe verbinding is gecontroleerd.

Servervariabelen staan in `wvnl-api/.env.example`: `OPENAI_API_KEY`, optioneel `DEEPSEEK_API_KEY`, Stripe-secrets, `WDNL_WEB_URL` en `WDNL_REPORT_SALES_ENABLED=false`. De oude `OPENAI_MODEL` voor andere legacy-contentdiensten bepaalt het rapportmodel niet meer. Voor de worker: `DB_QUEUE_RETRY_AFTER=240` en `php artisan queue:work --queue=reports --tries=1 --timeout=160`. Laat Supervisor/systemd de worker beheren. Zie PAYMENTS.md voor webhook/purchase-setup.

Rollback: schakel nieuwe verkopen uit en stop de reportworker. Zet code terug zonder bestaande rapporttabellen te verwijderen. Een schema-down verwijdert uitsluitend deze nieuwe rapporttabellen, maar vernietigt wel inmiddels opgebouwde aankopen/rapporten: **niet uitvoeren op een gevulde omgeving zonder export, hersteltest en expliciete toestemming**. Bewaar voor code-upgrades een versleutelde databaseback-up met sleutelherstelplan. Geen algemene migrate:rollback/reset gebruiken.

## Grenzen en bronnen

Verkoop staat lokaal uit. Er is geen echte Stripe-betaling, betaalde modelaanroep, RevenueCat-aankoop of store-release uitgevoerd. De betaal-/generatietests gebruiken expliciete fixtures; een apart voorbeeld-PDF is duidelijk als fictief gemarkeerd. Native betaalconfiguratie, sandboxbetalingen, providercontracten/toestemming, bron-/dimensiekwaliteit, fiscale/juridische producttekst en operationele restore/loadtests blijven releasecontroles.

- [OpenAI Luna](https://developers.openai.com/api/docs/models/gpt-6-luna), [Structured Outputs](https://developers.openai.com/api/docs/guides/structured-outputs).
- [DeepSeek API en prijzen](https://api-docs.deepseek.com/quick_start/pricing/), [Chat Completions](https://api-docs.deepseek.com/api/create-chat-completion/).
- [Stripe fulfillment](https://docs.stripe.com/checkout/fulfillment.md?ui=full-page), [webhookverificatie](https://docs.stripe.com/events/manage-webhook-endpoints).
- [Dompdf](https://github.com/dompdf/dompdf); gecontroleerde stabiele packages: Dompdf 3.1.6 en Stripe PHP 22.1.0. Composer-audit meldde na installatie geen kwetsbaarheden.


## Tijdelijke lokale rapportknop — 8 november 2026

Een opvallende paarse/geel gestreepte **BROUW MIJN DEV-RAPPORT** staat alleen tijdens `nuxt dev` voor het toegestane demo-account. In het spel staat hij op ruime desktops naast de kaart, op mobiel erboven; ook de rapportpagina heeft de knop.

De API vereist gezamenlijk `APP_ENV=local`, `WDNL_DEV_REPORTS_ENABLED=true` en het ingelogde account met exact de ingestelde `WDNL_DEV_REPORT_EMAIL` (standaard `appdemo@mail.com`). De lokale flag is aangezet; `.env.example` heeft bewust `false`. Geen flags of gebruikersgegevens uit de client kunnen deze controle vervangen. De productiebuild toont de knop niet en start geen dev-accessaanvraag; de server weigert generatie én PDF-toegang in productie, zelfs met een per ongeluk ingeschakelde flag.

`GET /api/me/reports/dev-access` geeft beschikbaarheid en het model alleen aan het toegestane account. `POST /api/me/reports/dev` met een UUID `request_key` maakt synchroon een echte A4-PDF van de eigen actuele antwoorden met echte OpenAI-toelichting. De lokale knop gebruikt expliciet backend-`OPENAI_MODEL` en `OPENAI_API_KEY`, onafhankelijk van het CMS-model of de AI-flag voor betaalde rapporten. `MOTION_OPENAI_MODEL` bestuurt alleen de oude motie-import, niet deze knop. Geen minimumstemmen voor deze ontwikkelpreview, geen ontbrekende antwoorden verzonnen. Een aanvraagherhaling hergebruikt de preview. Maximaal zes generatieverzoeken per minuut.

Het resultaat is met een DEV-watermerk gemarkeerd, één uur geldig en versleuteld opgeslagen in de servercache. Er worden geen orders of betalingen gemaakt. Er is wél één betaalde OpenAI-aanroep per generatiekey, zonder automatische herstelpogingen. Het model, daadwerkelijke input-/outputtokens en berekende kosten worden in de tijdelijke preview getoond, buiten de betaalde rapportadministratie. De key blijft op de server. Fouten leveren een zichtbare melding op, geen codefallback. Alleen samengevatte tellingen, partijovereenkomsten en maximaal zes ingekorte eigen antwoorden gaan naar de provider; geen naam, e-mail of account-ID. Budget- en tokenlimieten blijven uit de CMS-instellingen komen. GPT-4.1-mini wordt voor deze devknop ondersteund zonder reasoning-parameter; GPT-6-modellen gebruiken de passende reasoning-instelling. Bekijken/downloaden via `/api/me/reports/dev/{uuid}` en `/pdf` vereist opnieuw dezelfde local/demo-controle en eigenaar. De rapportpagina opent via `?dev_report=...`; verlopen voorbeelden zijn niet meer opvraagbaar. De knop hoort niet in een distributie-/storebuild: `import.meta.dev` is daar false. Er is geen schemawijziging nodig.

Nieuwe én bestaande neutrale stemmen worden in actuele rapporten als **Neutraal** weergegeven, niet meer als historisch. Zij tellen mee als antwoord en in de totaalverdeling, maar niet als richting voor partijovereenkomsten.


Lokale backendconfig voor de bestaande GPT-4.1-mini-instelling (bewaar je bestaande volledige key):

```dotenv
OPENAI_MODEL=gpt-4.1-mini
MOTION_OPENAI_MODEL=gpt-4.1-mini
OPENAI_API_KEY=<je bestaande volledige OpenAI API-key>
```

Voer na wijzigingen `php artisan config:clear` uit vanuit `wvnl-api`; herstart langlopende processen als die configuratie in het geheugen houden. Voor een ander ondersteund OpenAI-model verander je alleen `OPENAI_MODEL` voor de devknop; de modelnaam staat bij de knop. Een mislukte aanvraag kan al tokens hebben verbruikt. De betaald-rapportpipeline blijft het model uit het CMS gebruiken.

## Visueel politiek kompas — 8 november 2026

Nieuwe snapshots bevatten `compass` met berekende themascores, aparte economische as, type, kernpunten, dekking en gevoeligheid. `ReportSheet` en de server-PDF volgen het aangeleverde ontwerp: WDNL-header, turquoise typekaart, vectorwiel met negen types, scorebalken, maximaal vijf volledige partijnamen, inzichten en methodiek. Geen partijlogo's. De normale downloadknop blijft eigenaar-/betaalstatus-gecontroleerd; de paarse devknop blijft het ingestelde backend-OPENAI_MODEL gebruiken.

De PDF blijft één A4 met selecteerbare tekst en scherpe SVG-art. Dompdf gebruikt ingebedde DejaVu Sans; de webinterface gebruikt lokale Manrope. Een begrensde compacte PDF-opmaak vangt maximale toelichtingen op; de renderer weigert onverwachte meerpagina-overflow. Externe resources, JS en PHP blijven uitgeschakeld. De webweergave stapelt op mobiel; de PDF behoudt de A4-compositie. Details/formule: COMPASS_ALGORITHM.md.

**Configuratie vereist:** bestaande stellingen hebben niet automatisch gevalideerde themagewichten. Nieuwe CMS-invoervelden bij de stelling en de bestaande bulkimport ondersteunen `compass_weights`. Zonder voldoende dekking toont de devpreview 'Jouw kompas in opbouw', geen willekeurige percentages/type. Volledige rapportverkoop wordt ook server-side tegengehouden tot alle thema’s, economische as en minimumaantallen beschikbaar zijn. Normale verkoop blijft bovendien gesloten via de bestaande salesflag en externe configuratie.

Een duidelijk gemarkeerd voorbeeld met fictieve antwoorden én fictieve partijen staat op `/examples/WDNL-kompas-voorbeeld.pdf`, gelinkt vanaf `/kompas`. Regenereren: `php scripts/design/render-compass-example.php` vanuit de frontend, met naastliggende geïnstalleerde backend. Dit script gebruikt geen accountgegevens, LLM of databasewrites. Native assets worden meegenomen met `npm run build:mobile`. Geen geïnstalleerde app-update of store-release wordt hierdoor automatisch uitgevoerd.

Oudere gekochte snapshots worden niet opnieuw berekend of overschreven. Bewaarde v1-snapshots en PDF's blijven geldig. Nieuwe redactionele gewichten veranderen alleen volgende rapporten. Releasesequentie, lokale backup en rollback: MIGRATION_PLAN.md.


### Generatievalidatie en foutmeldingen

De OpenAI-tekstvelden hebben een strikt JSON-schema met pattern `^[^0-9<>:]{1,650}$`; dit sluit aan op de eigen validatie (geen cijfers/HTML/URL, begrensde lengte). De prompt vraagt expliciet geen cijfers, ook geen bestaande invoeraantallen, en maximaal vijfenveertig woorden per veld. Dit voorkomt de eerdere tegenstrijdigheid ‘geen nieuwe cijfers’ tegenover een validator die alle cijfers weigerde. Ondersteunde patroonvalidatie gecontroleerd in de [officiële OpenAI Structured Outputs-documentatie](https://developers.openai.com/api/docs/guides/structured-outputs). DeepSeek behoudt de eigen JSON-adapter en servervalidatie.

De devknop gebruikt `reportGenerationError`: alleen gecontroleerde rapportserviceberichten bij 422 worden overgenomen uit rawMessage. Daardoor blijft bijvoorbeeld ‘OpenAI gaf rapporttekst…’ zichtbaar in plaats van ‘Controleer de invoer’. Onbekende debugdetails/5xx blijven vertaald. Geen automatische extra modelcall of stille fallback.


## Inhoudelijke invulling — compass-v2.1, 8/9 november 2026

De legacyvragen hadden geen metadata of themagewichten. Nu bestaat een expliciete basisset voor achttien exact beoordeelde formuleringen (veertien inhoudelijke families), met inhoudelijke onderbouwing, onderwerpen, historische context en teksthash. Dit zijn door de coding agent gemaakte redactionele interpretaties/voorstellen van de aanwezige vraagtekst, geen onafhankelijke verificatie van de oorspronkelijke moties of een wetenschappelijk gevalideerde indeling. De seed zet `compass_reviewed_at` en `compass_context`; `verified_at`, officiële partijgegevens, stemrecords en accounts blijven onaangeroerd. Gewijzigde tekst en eigen weging worden niet overschreven.

Een vroege richtingsscore verschijnt vanaf twee verschillende bruikbare vragen en is voorlopig bij lage dekking of historische context. Een voorlopig type vereist acht gewogen vragen en minstens twee beschikbare thema’s. Alleen beschikbare dimensies bepalen de afstand; ontbrekende scores worden niet ingevuld. De bestaande strengere dekking voor een volledig verkoopbaar rapport blijft vereist: vijf actuele antwoorden per thema/economische as, tien gewogen vragen en de overige verkoop-/releasevoorwaarden.

Dubbelen tellen eenmaal; tegenstrijdige keuzes op dezelfde inhoudelijke vraag worden uitgesloten. Alle ruwe historische antwoorden blijven zichtbaar in de totaaltelling. Het rapport toont de deduplicatie apart. Zonder verifieerbare partijmatch komt er een echte onderwerpverdeling in die kaart, geen verzonnen partijen. De resterende ontbrekende themascore krijgt een zichtbare uitleg, ook in de A4-PDF.

ZschotCMS bevat nu **Basiswegingen bestaande stellingen seeden**, plus bewerkbare onderbouwing, inhoudssleutel, onderwerpen en historische context per stelling. De seed kan herhaald worden zonder duplicaten of kosten. Het is geen automatische weging van de hele catalogus; overige inhoud vraagt nieuwe beoordeling.
