# Politiek kompas — compass-v4.2

Nieuwe rapporten gebruiken `CompassAnalysis` en `VoteSnapshot`, niet de historische LLM-score uit `PoliticalCompassService`. De LLM schrijft een korte samenvatting en vier observaties bij al berekende feiten. De actuele zestien typen staan in `CompassTypes`; de oude typetabel hieronder beschrijft het eerdere V2-formaat. Geen scores, partijstanden of politieke types worden door het model bepaald.

## Redactionele invoer

De bestaande stellingmetadata hebben een nullable JSON-veld `compass_weights`. Instellen via ZschotCMS → WDNL → Stellingen → stelling openen → **Politiek kompas — themagewichten**. De bestaande JSON-import/seeding ondersteunt hetzelfde veld. Leg de inhoudelijke onderbouwing vast bij Achtergrond en controleer bronnen en formulering voordat je publiceert. Een VOOR-stem telt richting de eerste pool bij +1/+2, richting de tweede bij −1/−2. TEGEN keert dat om. Gewicht twee telt tweemaal zo zwaar; nul/ontbrekend telt niet mee.

| Sleutel | Positieve pool | Negatieve pool |
| --- | --- | --- |
| social | Sociaal | Individueel |
| progressive | Progressief | Behoudend |
| international | Internationaal | Nationaal |
| green | Groen | Economische groei |
| economic | Economisch links | Economisch rechts |

Deze afwegingen zijn een vereenvoudiging, geen algemene tegenstelling tussen alle vormen van sociale betrokkenheid, duurzaamheid en economische groei. Alleen stellingen die de specifieke afweging daadwerkelijk bevatten mogen daarop worden gewogen. De economische as wordt afzonderlijk ingevoerd, nooit afgeleid van de aantallen VOOR/TEGEN of van de sociale score.

Voorbeeld voor een inhoudelijk gecontroleerde stelling (niet automatisch gebruiken op bestaande stellingen):

```json
{"compass_weights": {"social": 1, "progressive": 0, "international": 0, "green": 0, "economic": 0}}
```

CMS-validatie accepteert uitsluitend de vijf sleutels en gehele waarden −2…2. Publicatie vereist de bestaande redactionele controle en versie/hashcheck, wijzigingen worden geaudit. Oudere CMS-clients die het veld weglaten behouden bestaande gewichten. Expliciet leeg of nul maakt een weging leeg. Een mapping is geen bewijs voor een officieel partijstandpunt.

## Berekening en dekking

Voor dimensie d: `observed = 50 × (1 + Σ(w × choice) / Σ(abs(w)))`, `choice = +1` voor VOOR, `−1` voor TEGEN. Vanaf V4.1: `adjusted = 50 + (observed − 50) × n / (n + 6)`, met n het aantal unieke bruikbare niet-neutrale antwoorden op deze dimensie. `BalancedScore` rondt de afwijking van 50 symmetrisch af, met maximaal 49 punten afwijking (weergave 1–99). De andere pool is exact `100 − score`. Neutraal blijft in de antwoordtelling, zonder richting. Geen score uit nul of één bruikbare vraag; ontbrekende data wordt geen 50.

De zes neutrale ankerantwoorden zijn een redactionele productkeuze, geen echte toegevoegde stemmen en geen gekalibreerd statistisch model. Twee gelijkgerichte antwoorden geven 63/37, vier 70/30, tien 81/19. Gewicht ±2 beïnvloedt de richting maar verdubbelt niet de dekking. De oorspronkelijke richting blijft als `observed_percentage` bewaard; methode en anker staan in `score_adjustment`. Een voorzichtige score bewijst geen voorkeur voor het politieke midden. Absolute zekerheid wordt niet getoond, ook niet door afronding bij grote aantallen. De methode valideert niet automatisch de redactionele gewichten.

Vanaf twee verschillende bruikbare vragen verschijnt een **voorlopige** richting. Een vollediger profiel vereist vijf actuele vragen per thema en tien gewogen vragen totaal. Historische, afzonderlijk gewogen vragen kunnen meetellen als indicatie; ze maken het profiel altijd voorlopig. De economische as heeft dezelfde dekkingseisen voor verkoop.

`compass_reviewed_at` en `compass_context` registreren de inhoudelijke weging afzonderlijk van `verified_at` (bron-/publicatiecontrole). Seeded legacywegingen zijn beredeneerde, door de coding agent aangebrachte interpretaties van de aanwezige tekst. Ze zijn geen menselijke wetenschappelijke validatie of controle van oorspronkelijke partijstemmen. Alle gesedeerde legacyitems zijn gemarkeerd als historisch. Ongewogen actuele items blijven zonder score; oude bronnen tellen alleen mee bij die expliciete historische aanduiding. Bestaande actuele geverifieerde mappings blijven ondersteund.

Dubbelen worden gegroepeerd op bronmotie of expliciete inhoudssleutel. Gelijke keuzes tellen één keer. Bij tegenstrijdige keuzes wordt de groep uitgesloten; we kiezen niet stilzwijgend een van de antwoorden. De totale stemhistorie blijft ongewijzigd zichtbaar, met aparte aantallen voor dubbelen/tegenstrijdigheden. Onderwerpverdelingen tellen unieke, consistente vragen; een vraag kan meerdere onderwerpen hebben.

De score-snapshot bevat gebruikte IDs, canonieke sleutels, gewichten, onderbouwing, datum en historische context. Een SHA-256 van de beoordeelde titel/toelichting/achtergrond beschermt tegen hergebruik van oude wegingen na een tekstwijziging. Gewijzigde tekst vraagt nieuwe beoordeling.

Een eerste type vereist acht gewogen vragen en twee beschikbare thema’s. Alleen beschikbare dimensies bepalen de afstand tot de profielpunten; er wordt geen fictieve middenpositie voor onbekende dimensies gebruikt. Een volledig type vereist alle vier de thema’s met actuele dekking; kopen vereist daarnaast een niet-voorlopige economische as. De API blokkeert daarvoor order/checkout; de devpreview toont wel de bestaande indicatie. Eerder gekochte snapshots blijven toegankelijk.

Gevoeligheid is het minimum/maximum van dezelfde afgezwakte score bij één antwoord weglaten, mits minstens twee bruikbare antwoorden overblijven. Dit is geen statistisch betrouwbaarheidsinterval of representativiteitsclaim.

## Historische V2-types

Het type is het dichtstbijzijnde onderstaande presentatieprofiel, met de kleinste som van gekwadrateerde verschillen over de beschikbare gelijkwaardige themascores. Bij ontbrekende thema’s is het type altijd voorlopig. Bij een exact gelijkspel wint de eerste profielrij. De economische as bepaalt het type niet.

| Type | Sociaal | Progressief | Internationaal | Groen |
| --- | ---: | ---: | ---: | ---: |
| De Sociale Vernieuwer | 75 | 75 | 65 | 65 |
| De Pragmaticus | 50 | 50 | 50 | 50 |
| De Beschermer | 75 | 25 | 35 | 40 |
| De Traditionele Realist | 30 | 20 | 25 | 25 |
| De Ondernemer | 20 | 50 | 65 | 20 |
| De Vrijdenker | 25 | 85 | 75 | 50 |
| De Nationale Verbinder | 80 | 50 | 15 | 50 |
| De Groene Voorloper | 55 | 75 | 65 | 95 |
| De Idealist | 95 | 95 | 90 | 70 |

Dit zijn expliciete redactionele productkeuzes, geen wetenschappelijk gevalideerd persoonlijkheidsmodel. Ook minimumaantallen en gewichten vereisen inhoudelijke evaluatie voordat productiegebruik/verkoop wordt geopend. Bij onvoldoende gegevens volgt geen type, label of gefingeerde score.

## Partijen en historische rapporten

Partijmatch: alleen officieel herleidbare, gecontroleerde motiekoppelingen; geen LLM-invulling, gebruikersvoorkeur of logo. Neutraal/afwezig/ambigue partijkeuzes uitgesloten. Vanaf twee unieke moties een voorlopige indicatie; vanaf tien een vollediger vergelijking. `percentage` blijft het feitelijke percentage overeenkomsten (`matched / compared`). Vanaf V4.1 is de getoonde **matchscore op 100** het aparte veld `balanced_score`, met dezelfde afzwakking en n = vergeleken unieke moties. Vier van vier overeenkomsten blijft feitelijk 100%, maar geeft indicator 70. UI/PDF tonen de indicator met de werkelijke aantallen en een expliciete legenda, niet als letterlijk matchpercentage. Sorteer aflopend op indicator, dan coverage, dan naam; maximaal vijf volledige partijnamen. Dit is overeenkomst in stemgedrag, geen stemadvies.

Snapshot, optionele AI-tekst en PDF blijven versleuteld en eigenaarsgebonden. Web en PDF lezen dezelfde vaste snapshot. Bestaande `vote-overview-v1`-rapporten behouden hun data, opgeslagen PDF en oorspronkelijke weergave via `LegacyReportSheet` / `snapshot-v1`. Een nieuw rapport is een afzonderlijke aankoop van €1,99; geen lifetime-herberekeningen.


## Basiswegingen en CMS

ZschotCMS → WDNL → Stellingen / Rapporten → **Basiswegingen bestaande stellingen seeden** roept de content-write-route `/api/cms/content/compass/seed` aan. De versie `editorial-2026-11-08-v1` herkent uitsluitend achttien exact beoordeelde formuleringen (veertien inhoudelijke families). Dit is geen automatische classificatie van de hele catalogus. Procesvragen zonder heldere richting krijgen bewust geen asgewicht, wel onderwerpen.

De seed bewaart aangepaste wegingen, slaat gewijzigde teksten over, is idempotent en audit alle wijzigingen. `verified_at`, motiekoppelingen, officiële partijposities, stemmen en gebruikers worden niet als onderdeel van deze seed gewijzigd. De editor laat onderbouwing, onderwerpen, gedeelde inhoudssleutel en historische context wijzigen. Publicatie met expliciete review blijft verplicht. Bronverificatie en inhoudelijke weging zijn afzonderlijke zaken.

De kenmerken in de typekaart komen uit de sterkst uitslaande beschikbare dimensies met vaste, uitlegbare teksten. Het zijn geen standaardkenmerken die automatisch bij de archetypenaam worden geplakt. Een ontbrekende progressieve score kan dus geen kenmerk over vernieuwing opleveren; een lage groene score wordt niet als duurzaam kenmerk gepresenteerd.


## V4: zestien typen en het A4-typewiel

De actuele catalogus staat in `App\Services\Reports\CompassTypes::ALL`. De zestien profielpunten zijn redactionele presentatiekeuzes, geen psychologische classificatie of bewezen clusters. Elk profiel heeft aanvullende voorwaarden op beschikbare richtingsscores. Ontbrekende scores tellen niet mee in afstand en voldoen niet aan een profielvoorwaarde. Bij gelijke afstanden geldt de vaste catalogusvolgorde. De bestaande minimale dekkingsgrenzen en voorlopige/historische aanduiding blijven gelden.

Alle zestien profielpunten zijn getest op bereikbaarheid. Een middenprofiel met alle vier scores vijftig selecteert De Betrokken Middenkoers. Alleen sociale/internationale antwoorden kunnen geen Vernieuwer opleveren: de vereiste progressieve score ontbreekt dan. De drie weergegeven eigenschappen blijven uit de daadwerkelijke beschikbare scores afgeleid. Het wiel toont alle zestien typen en licht één type uit, geen kansen/percentages. De aparte economische as blijft onafhankelijk berekend.

Nieuwe rapporten tonen een eerste partijvergelijking vanaf twee unieke bevestigde moties per partij, met `provisional=true` onder tien moties en het aantal overeenkomsten/vergelijkingen. Historische en conflicterende keuzes worden expliciet behandeld. Geen bestaande partijscore is vanuit een model of het profieltype afgeleid. Zie REPORTS.md en de vastgelegde openbare bronrecords.


## V4.1: voorzichtige scores bij beperkte dekking

Voor de typeafstand worden zowel de oorspronkelijke themarichting als het kandidaatprofielpunt met dezelfde factor n/(n+6) naar het midden gebracht. Zo beïnvloeden beperkt gedekte dimensies het type minder sterk en blijven alle zestien typen bereikbaar. Richtingsvoorwaarden en tekstuele kenmerken volgen de oorspronkelijke keuzes; beperkte dekking wordt niet als inhoudelijke gematigdheid geïnterpreteerd. De sterktevolgorde van kenmerken volgt de afgezwakte scores.

Bestaande gekochte snapshots/PDF’s blijven ongewijzigd. Nieuwe devpreviews vereisen methodeversie 4.1; een oudere gecachte preview vraagt om opnieuw genereren. Stemmen, gewichten, officiële partijposities en aankooprechten worden niet gewijzigd.


## V4.2: meer gewogen stellingen, eerlijkere voorlopigheid

- **Gewichtsvoorstellen.** `CompassProposals` laat het rapportmodel (provider/model uit de rapportinstellingen) per batch van 20–25 stellingen gewichten, onderwerpen, zekerheid en een korte onderbouwing voorstellen. Ongeldige items worden weggegooid, niet gerepareerd. Voorstellen staan in `wdnl_compass_proposals` met status `pending` en tellen pas mee na goedkeuring in zschotcms (los, aangepast of in bulk per zekerheid). Goedkeuring schrijft `compass_weights`, `compass_reviewed_at` en `compass_context.basis = statement_wording` met de tekst-hash; een daarna gewijzigde of al handmatig gewogen stelling wordt `outdated` en nooit overschreven. `verified_at` en partijstemmen blijven onaangeroerd. Kosten per run staan in `wdnl_compass_proposal_runs`; terminal: `php artisan compass:propose --limit=… --max-eur=…` (met `--dry-run` voor een schatting), CMS: maximaal 50 per klik en €0,10.
- **Voorlopigheid.** Een themascore is alleen nog voorlopig bij minder dan vijf antwoorden. Historische context maakt een score niet meer voorlopig: een gewicht beschrijft de beantwoorde tekst, dus het antwoord is een actuele mening. Om dezelfde reden geldt de grens van 730 dagen niet voor `basis = statement_wording`. Voor partijvergelijkingen blijft historische context een beperking.
- **Bandbreedte.** De web- en PDF-poster tonen het bestaande `sensitivity`-bereik (score bij weglaten van telkens één antwoord) als lichtere band op de themabalk.
- **Vraagvolgorde.** `CompassQuestionPriority` zet bij `/issues/pending` maximaal twaalf gewogen stellingen vooraan die thema's met minder dan zes antwoorden aanvullen. Eén vaste volgorde voor alle offsets, zodat paginering niets overslaat.

- **Automatische voorstellen bij CMS-opslag.** `CmsContentService::saveStatement` zet `compass_reviewed_at` alleen bij gekozen gewichten of een onderbouwing; anders blijft de stelling open en zet `CompassProposals::queue` één batch klaar die na het antwoord draait (`app()->terminating`). Kandidaten zijn tekst-bewust: een voorstel of beoordeling van een oudere formulering telt niet voor de huidige.
- **Draagbare gewichten.** `CompassWeightsFile` exporteert beoordeelde gewichten voor de huidige tekst (slug + tekst-hash) en past ze toe via CMS-import of via de seed uit `resources/content/compass-weights.json` (`php artisan compass:export`).
