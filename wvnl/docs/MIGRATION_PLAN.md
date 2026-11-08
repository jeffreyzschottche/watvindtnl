# Migratie- en herstelplan

Geen productiegegevens verwijderen. Geen `migrate:fresh`, truncate of destructive rollback op bestaande databases. M1 verandert het schema niet.

## Herstel vóór M2

1. Identificeer expliciet omgeving, host, databasenaam en schema/migratiestatus. Gebruik geen applicatie-rootaccount voor productie.
2. Stop schrijvers tijdens de definitieve cutover of leg een consistente snapshot plus binlogpositie vast. Maak met `mysqldump --single-transaction --routines --triggers --events --no-tablespaces` een snapshot; credentials via beperkt optionfile/secretmanager, nooit CLI/history. Stuur stdout rechtstreeks naar beheerde encryptie (age/KMS), sla buiten repository/webroot op. Een backupbestand is zelf hoogsensitief.
3. Maak checksum, noteer schema/codeversie en versleutelde storage-ID. Bewaar sleutel apart. Stel bewaartermijn en toegang vast.
4. Restore in geïsoleerde database; controleer tabellen, constraints en tellingen per keuze; voer read-only vergelijkingen en login-/historietests uit. Backup is pas herstelbaar verklaard na geslaagde restore.
5. Laat cutover vrijgeven op basis van testbewijs en rollback-RTO/RPO. Productiewrites/deletes zijn niet onderdeel van M1.

## Expand / backfill / verify / cutover

- Voeg nieuwe votes, consents, roles, entitlements, source_references en processing_jobs toe zonder oude JSON/kolommen te verwijderen.
- Backfill in idempotente batches met checkpoints. Geldige unieke user/issue-combinaties overnemen, inclusief neutraal. Conflicterende keuzes, ontbrekende users/issues, ongeldige JSON en dubbele ID's naar versleutelde quarantaine + telrapport. Geen politieke keuze raden.
- Oude stemdatum onbekend: `source=legacy_json`, `voted_at=null`, `imported_at` apart; issue.updated_at is geen stemdatum.
- Draai shadow comparisons op counts, feed-exclusie, eigen historie en partijreferenties. Unieke DB-constraint `(user_id, issue_id)` is verplicht vóór writes op de nieuwe tabel.
- Schakel writes/reads via één gecontroleerde featureflag om. Geen onbegrensde dual writes zonder transacties. Controleer inflight requests en worker-versies.
- Rollback vóór cutover: code terug, nieuwe tabellen behouden voor diagnose. Na cutover geen votes droppen: reconcile nieuwe stemmen of herstel snapshot plus logs. Terug naar oude code kan anders nieuwe stemmen verliezen. Oude kolommen pas verwijderen na bewaartermijn, verificatie en expliciete vrijgave.

## Mijlpalen / acceptatie

M0 Audit: schema/data tellen, officiële dependencies controleren, documenten en risico's vastleggen.
M1 Veilige basis: publieke identiteitlekken/hulproutes sluiten, self-premium blokkeren, limiter/headers, geen LLM op GET, tokenrevocation, handmatig resultaat en tracking standaard uit. Unit/integration/frontend checks. Geen schemawijziging.
M2 Centrale data/auth: hersteltest, votes-constraint/backfill, consentledger, sessies, RBAC/MFA, cursorfeed, concurrency op MySQL, privacy-export/verwijdering.
M3 Content: officiële bron verifiëren, CMS-contract opvragen, queue/schema/bronvalidatie, 4+4 publicatiegate, menselijke review.
M4 Website: alle publieke/auth/accountroutes, SSR, design system, catalogus, toegankelijkheid en E2E.
M5 Kompas/commerce: deterministische methode, PDF, Stripe sandbox, ledger/webhooks, refunds, testbetaling.
M6 Native: compatibele Capacitor-upgrade, onboarding, tabs, OS secure storage, RevenueCat, restore, native privacyflows, echte devicebuilds.
M7 Ads: gecertificeerde CMP, afzonderlijke demo/liveconfig, AdSense/AdMob-testunits, caps, consenttests.
M8 Release: audits zonder blokkerende issues, load/security/E2E, encrypted restore, monitoring, CI/CD, juridische/contentgoedkeuring en releasechecklist.

Per mijlpaal: tests + contract + documentatie + concreet bewijs. Ontbrekende credentials zijn configuratieblokkades, geen reden om nepfunctionaliteit als voltooid te presenteren.

## Kompasrapport: additive themametadata — 8 november 2026

1. Nieuwe migratie: `2026_11_08_020000_add_compass_weights_to_statement_metadata.php`, uitsluitend een nullable JSON-kolom. Eerst migreren, dan backend/frontend/CMS publiceren. Geen bestaande gebruikers-, stem-, stelling- of rapportrecords verwijderen/herberekenen.
2. Voor de lokale MySQL-migratie is een consistente dump gemaakt met single-transaction/no-tablespaces, triggers en events, een tijdelijk 0600-optionfile zonder CLI-secret en gzip + Laravel Crypt. Deze omgeving heeft geen opgeslagen routines (gecontroleerd). De geïnstalleerde mysqldump 9.3 vroeg bij `--routines` een niet-ondersteunde INFORMATION_SCHEMA.LIBRARIES-tabel; die optie is uitsluitend na controle op nul routines overgeslagen. Gebruik bij een productiebackup een passende client en neem aanwezige routines wel mee.
3. Lokale backup: `/tmp/wdnl-compass-v2/database-before.sql.gz.enc`, directory 0700, bestand 0600. Encryptie/decryptie en SHA-256-roundtrip gecontroleerd. Dit is geen uitgevoerde restoretest. Bewaar voor herstel de oorspronkelijke APP_KEY via secretbeheer. Voor langdurige bewaring moet deze tijdelijke lokale backup naar beheerde backupopslag; geen publiek downloadbestand of repositorybestand.
4. Na de lokale migratie: 19 gebruikers, 4.598 stellingen, 31.887 argumenten en de volledige geordende vote-hash gelijk aan vóór de wijziging. Nul ingevulde kompaswegingen; geen politieke mappings of fictieve content in echte data geseed.
5. Bij rollback eerst de vorige applicatieversies terugzetten; laat de extra nullable kolom staan. Export/backup eventuele nieuwe mappings voordat je de migratie later expliciet terugdraait. De `down` verwijdert alleen dit nieuwe veld, maar daarmee ook nieuw ingevulde wegingen. Een databaseherstel alleen in een geïsoleerde omgeving testen vóór cutover; nooit blind over de actuele database herstellen.
6. Verifieer CMS→stelling→snapshot→PDF en eigenaar-/productieblokkades vóór verkoop. Betaalde historische snapshots/PDF's niet overschrijven. De CMS-editorwijziging staat tevens als toepasbare patch in `docs/integrations/zschotcms-compass.patch`.


## Reviewcontext en eerste inhoudelijke basisweging

Migratie `2026_11_08_030000_add_compass_review_context_to_statements.php` voegt uitsluitend nullable compass_reviewed_at en compass_context toe. Eerst database, daarna API/CMS/web publiceren. Het afzonderlijke reviewveld voorkomt dat interpretatie van de vraagtekst wordt voorgesteld als verificatie van originele bronnen of partijstemmen.

Voor de lokale migratie/seed is een nieuwe consistente MySQL-dump gemaakt en versleuteld opgeslagen in `/tmp/wdnl-compass-content/database-before.sql.gz.enc` (directory 0700, bestand 0600). Gzip/encryptie/decryptie/SHA-256-roundtrip gecontroleerd; geen volledige database-restoretest. Credentials stonden alleen kort in een beperkt optionfile en zijn opgeruimd. Bewaar de oorspronkelijke APP_KEY via secretbeheer voor herstel en verplaats de tijdelijke backup naar beheerde opslag als langdurige bewaring nodig is.

18 stellingmetadatarecords toegevoegd met controle op de exacte teksthash. Herhaling: 0 updates, 18 unchanged. Vóór/na gelijk: 19 gebruikers, 4.598 stellingen, 31.887 argumenten en de volledige geordende stemhash. Geen originele stelling, stem, partijpositie of argument herschreven. De nieuwe analyse herkent vier dubbele antwoorden en één tegenstrijdige groep; dit verandert de interpretatie, niet de opgeslagen geschiedenis.

Rollback bij voorkeur vorige code terugzetten en de extra nullable velden behouden. Exporteer nieuwe metadata en auditinhoud vóór expliciete rollback; het verwijderen van de nieuwe reviewvelden maakt de bijbehorende historische wegingen onbruikbaar. Verwijder gesedeerde mappings uitsluitend gericht met controle op catalogue-versie en backup, zonder latere handmatige correcties te overschrijven. Betaalde snapshots/PDF’s worden niet herschreven.
