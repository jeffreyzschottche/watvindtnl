# WDNL-content beheren vanuit ZschotCMS

Geïmplementeerd op 7 november 2026. Dezelfde aanpak als Allergie-ID en Spot & Find: ZschotCMS beheert content via de app-API. WDNL blijft eigenaar van de database. Het CMS heeft geen toegang tot gebruikers, individuele stemmen, betalingen of databasecredentials.

## Lokaal gebruiken

Open **http://localhost:3100/projects/watdenktnederland/integrations/5** en log in met je bestaande CMS-account. Kies **WatDenktNederland → WatDenktNederland content**.

- **Partijen**: volledige naam, afkorting, vaste slug en website toevoegen/wijzigen. **Partijen seeden / bijwerken** vult de gecontroleerde catalogus van 7 november 2026 aan. Deze knop bewaart bestaande IDs, historische namen en redactionele wijzigingen; hij verwijdert logoverwijzingen. Een naamswijziging of afsplitsing krijgt een apart record.
- **Stellingen**: zoeken, pagineren, openen, toevoegen en wijzigen. Context, achtergrond, bron, brondatum, acht argumenten met bronnen en partijstemmen zijn bewerkbaar. Opslaan publiceert pas na expliciete redactionele controle en servervalidatie. Wijzigingen bewaren stemmen, rapportages en bestaande argument-ID’s. Verandert de inhoudelijke vraag, maak dan een nieuwe stelling in plaats van oude stemmen een andere betekenis te geven.
- **Moties**: **Recente moties ophalen** haalt 5, 10 of 25 recente moties rechtstreeks uit de officiële Tweede Kamer OData-API. Bewaart de echte UUID (geen integercast), nummer, onderwerp, brondatum en exacte bronrecord-URL. Toekomstige brondata worden uitgesloten. Herhaling voegt niets dubbel toe en overschrijft geen handmatig gecorrigeerde moties. **Stelling maken** opent een editor met de motieverwijzing; er worden geen stellingen of partijstemmen verzonnen/gepubliceerd.
- **Contentbestand importeren / seeden**: JSON met `parties`, `motions` en/of `statements`. Eén knop verwerkt de batch in een transactie. Maximaal 50 partijen, 25 moties en 25 stellingen, maximaal 512 KiB. Een fout draait de hele batch terug.

Lokale start: vanuit `~/Sites/zschotcms` het bestaande `./start.sh --no-open`. Dit start ook de bestaande CMS-scheduler voor andere projecten. Voor uitsluitend handmatig WDNL-beheer zijn alleen de CMS-API en frontend nodig; WDNL vereist geen scheduler. De WDNL-API moet draaien op `http://127.0.0.1:8000/api`, de website op `http://localhost:3000`.

De CMS-database staat in de bestaande DBngin-instance **allergie-id**, MySQL 8.0.33, poort 8888. De nieuwere lege instance met naam `zschotcms` bevat niet de bestaande CMS-projecten. WDNL gebruikt een afzonderlijke MySQL-instance op 3306. De instellingen van die bestaande instances zijn niet gewijzigd.

## Publicatie- en importcontract

De backend accepteert voor stellingen alleen:

- `slug`, `title`, `description`, optioneel `more_info`;
- `url` (http/https), `source_date` (YYYY-MM-DD, niet toekomstig), optioneel `motion_id`;
- `arguments.pro` en `arguments.con`, elk precies vier objecten met `body` en `sources` (een of meer bron-URL’s);
- `party_stances.agree/disagree/neutral`: bestaande partij-ID’s; geen partij in twee groepen; een gekoppelde bronmotie is verplicht bij partijstemmen;
- `reviewed: true` na broncontrole; `revision` en `content_hash` voor wijzigingen aan bestaande stellingen.

Alle acht argumenten moeten verschillend en gewone tekst zijn. Deze technische gate bewijst geen feitelijke juistheid: redactionele broncontrole blijft nodig. Afwezigheid bij een Kamerstemming betekent geen neutrale stem. De koppeling genereert geen partijstandpunten met een LLM.

Een identieke herhaling retourneert dezelfde stelling zonder extra stemmen, argumenten of publicatieversies. Een inhoudelijke wijziging vereist de actuele `revision` en `content_hash`, opgehaald bij openen. De inhoudshash merkt ook wijzigingen via het oude beheerpaneel op; een nieuwe gebruikersstem veroorzaakt geen onterechte contentconflict. Een verouderde versie krijgt HTTP409. Historische stellingen met meer dan vier argumenten aan één kant worden niet automatisch ingekort: de aanvraag wordt geweigerd. Incomplete historische inhoud wordt niet automatisch verwijderd.

Moties gebruiken `external_id`, `number`, `title`, `source_url`, `source_date`, optioneel `context` en `revision`. Partijen gebruiken `slug`, `name`, `abbreviation`, optioneel `website_url`. Bij een partijimport wordt op slug bijgewerkt. Bij een motie op officiële bron-ID; bij een stelling op slug. Voor een gecombineerde import kunnen stellingen verwijzen naar een vooraf geïmporteerde WDNL-`motion_id`.

Voorbeeldstructuur, **geen publiceerbare content**:

```json
{
  "parties": [],
  "motions": [],
  "statements": []
}
```

Maak de inhoud via de editor of gebruik hetzelfde schema uit `app/Services/CmsContentService.php`. Een lege batch wordt geweigerd. Er worden geen voorbeeldstellingen in de appdatabase geplaatst.

## API en beveiliging

`/api/cms/content` is een afzonderlijke contentgrens:

| Methode      | Pad                          | Functie                                            |
| ------------ | ---------------------------- | -------------------------------------------------- |
| GET          | `/`                          | Partijen, aantallen en recente redactionele acties |
| GET          | `/statements?search=&page=`  | Gepagineerde stellingenlijst, 20 per pagina        |
| GET          | `/statements/{id}`           | Alleen redactionele velden, argumenten en revision |
| POST         | `/statements`                | Toevoegen / versiegecontroleerd wijzigen           |
| GET          | `/motions?search=&page=`     | Gepagineerde bronmoties                            |
| POST         | `/motions`                   | Toevoegen / versiegecontroleerd wijzigen           |
| POST         | `/motions/sync`              | Officiële bron ophalen; `limit: 1..25`             |
| POST / PATCH | `/parties` / `/parties/{id}` | Partij toevoegen / wijzigen                        |
| POST         | `/parties/seed`              | Herhaalbare partijcatalogus                        |
| POST         | `/import`                    | Atomische contentimport                            |

Een aparte willekeurige token heeft uitsluitend expliciete `cms:content:read` / `cms:content:write`-rechten. WDNL bewaart alleen SHA-256 van de token en een vervaldatum (een jaar). App-/adminlogins, cookies, Sanctum-wildcards en verlopen tokens worden geweigerd. Omgekeerd geeft de contenttoken geen toegang tot appaccounts. Het CMS bewaart de sleutel versleuteld in `ProjectIntegration.config`; hij wordt niet naar de browser gestuurd. HTTPS is verplicht buiten localhost, redirects met Authorization worden niet gevolgd.

Algemene API-limieten en de adminlimiet gelden. Bronophaling heeft daarnaast maximaal 10 aanvragen/uur per contenttoken en een gedeelde lock. Requests blijven begrensd tot één bronaanvraag met timeout en één beperkte databasebatch. Geen LLM, PDF/OCR, onbeperkte sync of achtergrondpublicatie in dit endpoint. Bronorigin is vast; gebruikers-URL’s worden niet door de server opgehaald.

`wdnl_content_audits` bewaart redactionele voor-/naversies voor herstel. Geen individuele stemmen, gebruikersrecords, wachtwoorden, aankoopgegevens of contenttokens. Er is geen hard-deleteactie voor partijen, stellingen of moties via deze koppeling.

## Nieuwe omgeving / live koppelen

Eerst backend en CMS-code deployen. WDNL: voer de nieuwe aanvullende migratie uit na back-up en review van de migratiestatus. De lokale legacy-migratie `2025_10_08_120000_add_secret_url_pw_to_users_table.php` staat nog open en hoort niet bij deze wijziging.

```sh
# WDNL / wvnl-api
php artisan migrate --path=database/migrations/2026_11_07_230000_create_wdnl_cms_content_tables.php --force
php artisan cms:content-token --output=/veilige/map/wdnl-content-token --name=zschotcms-productie

# ZschotCMS / backend; voer de sleutel veilig over buiten Git/logs
php artisan cms:connect-wdnl --api=https://api.watdenktnl.nl/api --token-file=/veilige/map/wdnl-content-token
```

Of wijzig in het bestaande CMS-paneel **Instellingen** de API-URL en contenttoken naar die van productie. Kies bewust de juiste omgeving; de lokale token werkt niet op een andere database. De knop **Test verbinding** controleert lezen én schrijven zonder content te publiceren (lege POST moet 422 teruggeven).

Verwijder het tijdelijke sleutelbestand na de versleutelde opslag. Roteer vóór expiratie door een nieuwe token aan te maken, het CMS bij te werken en de oude rij in `wdnl_cms_tokens` in te trekken. Geen tokens in `.env.example`, frontendbundels, terminaloutput of versiebeheer. Configcache herladen na deployment indien gebruikt.

Er is **geen productie-uitrol uitgevoerd**. Voor live gebruik zijn een bereikbare HTTPS-API en een token uit de productiedatabase nodig. Dezelfde content komt daarna via de bestaande app-API bij alle clients, zonder clientrelease voor een tekstwijziging.

## Wijzigingen in ZschotCMS

De bestaande CMS-map heeft geen Git-repository. Deze zes bronbestanden zijn toegevoegd/aangepast:

```text
backend/app/Integrations/Apps/Wdnl/ContentIntegration.php
backend/app/Console/Commands/ConnectWdnl.php
backend/config/integrations.php
backend/tests/Feature/WdnlContentTest.php
frontend/components/panels/WdnlContentPanel.vue
frontend/utils/panels.ts
```

Ze hergebruiken `Integration`, `Action`, versleutelde configuratie, projectscoping, `Activity`, `useIntegrationAction` en `AppModal` van de bestaande integraties. Er zijn geen nieuwe dependencies geïnstalleerd.

## Verificatie en herstel

- WDNL: 35 backendtests / 179 assertions; 10 frontendtests; typecheck en productiebuild geslaagd.
- CMS: 64 backendtests / 432 assertions; typecheck en productiebuild geslaagd. Bestaande niet-blokkerende Vue Router Volar- en Tailwind-nestingwaarschuwingen blijven aanwezig.
- Echte browserketen: partijseed → 0 duplicaten; bronophaling → 5 nieuwe officiële moties; herhaling → 0 nieuwe. Editor met vier argumenten per kant geopend. Desktop, 390 en 320 pixels gecontroleerd zonder paginabrede overflow of pageerrors.
- Lokale integriteit: 19 gebruikers, 4.598 stellingen en 31.887 argumenten ongewijzigd. Hash over alle historische `issues.votes` vóór/na gelijk. Partijen 15 → 19; logoverwijzingen 15 → 0. Geen oude partij-ID’s of historische stellingen hernoemd.
- Privéherstelkopie partijen: `/private/tmp/wdnl-cms-parties-before-2026-11-07.json`, modus 0600. Bewaar desgewenst duurzaam buiten Git. Oude logobestanden zijn niet verwijderd; de app gebruikt ze niet meer.
- Screenshots lokaal: `/tmp/wdnl-cms-parties-desktop.png`, `/tmp/wdnl-cms-motions-desktop.png`, `/tmp/wdnl-cms-statement-editor.png`, `/tmp/wdnl-cms-390.png`, `/tmp/wdnl-cms-320.png`.

Rollback van alleen code: CMS-koppeling uitschakelen/ontkoppelen en oude code terugzetten. Dit hoeft geen appcontent te verwijderen. De nieuwe migratie verwijdert bij `down()` uitsluitend haar vier nieuwe tabellen; maak vóór zo’n rollback eerst een export van tokens/bronmoties/metadata/audits. Voer geen algemene rollback/reset uit op een live database. Een redactionele correctie kan met de audit-voorversie en de actuele revision worden hersteld; zet nooit een oude volledige issue-rij terug over actuele stemmen.

Officiële bron: [Tweede Kamer OData API](https://opendata.tweedekamer.nl/documentatie/odata-api). Partijcontrole en historische naamkeuzes: [PARTY_AUDIT_2026-11-07.md](PARTY_AUDIT_2026-11-07.md).

## Eigen stellingen en nieuwsaanleidingen

De editor bevat nu **Eigen maatschappelijke stelling**, **Stelling naar aanleiding van nieuws** en **Stelling bij een officiële motie**. Dit is handmatig contentbeheer: er worden geen externe nieuwsartikelen gescrapet, geïmporteerd of door een LLM herschreven.

- `origin_kind`: `editorial`, `news` of `motion`. Nieuwe eigen stellingen vereisen geen motie of externe bron. De acht verschillende argumenten en expliciete redactionele review blijven verplicht; bronlinks per argument zijn bij eigen stellingen optioneel. Voeg bij feitelijke claims wel controleerbare bronnen toe.
- Nieuws vereist de naam van de bron/redactie (`source_name`), bron-URL en brondatum. `source_title` is optioneel. Bij nieuws/moties blijven bron-URL’s voor argumenten verplicht. Een motiestelling vereist een bestaande `motion_id`.
- Drie aanvullende, nullable velden in `wdnl_statement_metadata` worden toegevoegd met `2026_11_08_000000_add_editorial_sources_to_wdnl_statements.php`. Bestaande inhoud en stemmen worden niet herschreven. Gebruik deze migratie ook bij een uitrol van deze uitbreiding.
- De publieke API geeft `source.kind/name/title/url/published_at/verified_at` mee. Web en app tonen een eigen-stelling-, nieuws- of motielabel en de bronverwijzing. Oude inhoud zonder vastgelegde herkomst blijft `legacy`; er wordt geen nieuwe herkomst verzonnen. Metadata wordt in dezelfde querybatch eager geladen.
- Homepage, FAQ en methodologie leggen uit dat WDNL eigen stellingen bij maatschappelijke onderwerpen en nieuws kan maken. Een verwijzing suggereert geen samenwerking of goedkeuring door een nieuwsredactie.

Voor publicatie blijft de redactie verantwoordelijk voor inhoud en passende hergebruikrechten. Deze UI voegt geen licentie voor een externe nieuwsbron toe. Er zijn bij deze uitbreiding geen voorbeeldstellingen in de echte database geplaatst.
