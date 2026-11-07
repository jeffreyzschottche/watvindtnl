# Database

Huidig: MySQL lokaal, Eloquent, SQLite :memory: voor regressietests. Entiteiten users, issues, arguments, political_parties, political_compasses, news_articles, Sanctum tokens, password resets, jobs/cache. Stemmen zitten in issues.votes JSON met user-ID's per bucket en users.voted_issue_ids. Geen unieke relationele stemconstraint, geen echte stemtimestamps. Zie CURRENT_STATE.md voor drift.

Doel: votes(id,user_id,issue_id,choice,voted_at,imported_at,source), unique(user_id,issue_id), index(issue_id,choice), index(user_id,id). Historische neutraal behouden; alleen nieuwe binary keuzes toestaan. Consentrecord versie + doel + timestamp + withdrawal; rollen/permissions; immutable payment event keys en entitlementledger; bronreferenties/processing provenance; categorieën en dimensiemappings. Exacte SQL/foreign-key-delete policies pas vastzetten bij M2 met privacy-/retentiebesluiten.

M1: geen DDL of backfill uitgevoerd. Tijdelijke bescherming gebruikt MySQL-transacties met rowlocks; dit vervangt de toekomstige DB-constraint niet. SQLitetests bewijzen geen MySQL-concurrency. Backups, restore en rollout: MIGRATION_PLAN.md.
