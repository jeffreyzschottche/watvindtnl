# Politiek kompas — compass-v2.0

Nieuwe rapporten gebruiken `CompassAnalysis` en `VoteSnapshot`, niet de historische LLM-score uit `PoliticalCompassService`. De LLM schrijft uitsluitend twee korte toelichtingen bij al berekende feiten. Geen scores, partijstanden of politieke types worden door het model bepaald.

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

Voor dimensie d: `score = round(50 × (1 + Σ(w × choice) / Σ(abs(w))))`, `choice = +1` voor VOOR, `−1` voor TEGEN. De andere pool is `100 − score`. Neutraal blijft in de antwoordtelling, zonder richting, en vult geen ontbrekende score met 50 in. Ambigue historische stemrecords zijn uitgesloten.

Alleen redactioneel geverifieerde mappings tellen. De brondatum mag niet ouder zijn dan 730 dagen; voor bronloze eigen stellingen geldt de controledatum. Per dimensie telt dezelfde gekoppelde motie eenmaal; ongekoppelde stellingen tellen op ID. Minimaal vijf verschillende directionele stellingen/moties per dimensie, anders `null`. De snapshot bevat de gebruikte IDs, motiekoppelingen, gewichten en controledatums.

Een type vereist vier beschikbare themascores en minstens tien verschillende bruikbare stellingen/moties over de dimensies samen. Dezelfde motie telt hierbij ook over verschillende thema’s eenmaal. Een volledig betaald rapport vereist daarnaast een beschikbare economische as. De API blokkeert aankoop vóór voldoende dekking; de lokale devpreview toont de ontbrekende data wel. Bestaande betaalde rapporten blijven toegankelijk.

De gevoeligheidsrange is het minimum/maximum bij één antwoord weglaten uit de gewogen score. Dit is **geen statistisch betrouwbaarheidsinterval** en geen bewijs van representativiteit.

## Types

Het type is het dichtstbijzijnde onderstaande presentatieprofiel, met de kleinste som van gekwadrateerde verschillen over vier gelijkwaardige themascores. Bij een exact gelijkspel wint de eerste profielrij. De economische as bepaalt het type niet.

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

Partijmatch: alleen officieel herleidbare, gecontroleerde motiekoppelingen; geen LLM-invulling, gebruikersvoorkeur of logo. Neutraal/afwezig/ambigue partijkeuzes uitgesloten. Minimaal tien verschillende vergelijkbare moties per partij. Percentage = overeenkomsten / vergeleken moties. Sorteer aflopend op percentage en coverage, dan op naam; maximaal vijf volledige partijnamen. Dit is overeenkomst in stemgedrag, geen stemadvies.

Snapshot, optionele AI-tekst en PDF blijven versleuteld en eigenaarsgebonden. Web en PDF lezen dezelfde vaste snapshot. Bestaande `vote-overview-v1`-rapporten behouden hun data, opgeslagen PDF en oorspronkelijke weergave via `LegacyReportSheet` / `snapshot-v1`. Een nieuw rapport is een afzonderlijke aankoop van €1,99; geen lifetime-herberekeningen.
