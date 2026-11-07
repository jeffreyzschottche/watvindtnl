# Database

Huidig: MySQL lokaal, Eloquent, SQLite :memory: voor regressietests. Entiteiten users, issues, arguments, political_parties, political_compasses, news_articles, Sanctum tokens, password resets, jobs/cache. Stemmen zitten in issues.votes JSON met user-ID's per bucket en users.voted_issue_ids. Geen unieke relationele stemconstraint, geen echte stemtimestamps. Zie CURRENT_STATE.md voor drift.

Doel: votes(id,user_id,issue_id,choice,voted_at,imported_at,source), unique(user_id,issue_id), index(issue_id,choice), index(user_id,id). Historische neutraal behouden; alleen nieuwe binary keuzes toestaan. Consentrecord versie + doel + timestamp + withdrawal; rollen/permissions; immutable payment event keys en entitlementledger; bronreferenties/processing provenance; categorieën en dimensiemappings. Exacte SQL/foreign-key-delete policies pas vastzetten bij M2 met privacy-/retentiebesluiten.

M1: geen DDL of backfill uitgevoerd. Tijdelijke bescherming gebruikt MySQL-transacties met rowlocks; dit vervangt de toekomstige DB-constraint niet. SQLitetests bewijzen geen MySQL-concurrency. Backups, restore en rollout: MIGRATION_PLAN.md.

## Aanvullende CMS-tabellen (7 november 2026)

Migratie `2026_11_07_230000_create_wdnl_cms_content_tables.php` voegt uitsluitend `wdnl_cms_tokens`, `wdnl_motions`, `wdnl_statement_metadata` en `wdnl_content_audits` toe. Geen wijziging of backfill van gebruikers of historische stemmen. Moties hebben een unieke tekstuele `external_id` (Tweede Kamer UUID), metadata koppelt naar bestaande issues en moties. Tokens worden gehasht opgeslagen; audits bevatten uitsluitend redactionele voor-/naversies. Het CMS kan geen appdata hard verwijderen. Lokale seed behoudt alle 15 bestaande partij-ID’s en voegt vier groepen toe. Herstel- en migratieprocedure: [ZSCHOTCMS.md](ZSCHOTCMS.md).

Aanvulling `2026_11_08_000000_add_editorial_sources_to_wdnl_statements.php`: drie nullable kolommen `origin_kind`, `source_name`, `source_title` op `wdnl_statement_metadata`, plus herkomstindex. Geen issue-/voterij gewijzigd. Een `down()` verwijdert uitsluitend deze aanvullende metadata; exporteer de bronvelden vóór een rollback.
