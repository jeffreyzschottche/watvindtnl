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
