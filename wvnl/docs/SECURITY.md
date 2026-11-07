# Security

M1 is risicoreductie, geen verklaring van production readiness. Uitgangspunt: politieke data hoogsensitief; geen ID-lijsten in publieke resources, geen tokens/PII in logs, geen publieke side effects, ownership server-side, throttle, tokenrevocation en tracking uit.

Resterende releaseblokkades: resterende frontenddependencyadvisories (na M1 12 meldingen: 1 high/5 moderate/6 low, geen critical; glob/minimatch/deepmerge transitief), gevoelige SQL-dump in Gitgeschiedenis, HttpOnly websessies/native secure storage, RBAC/admin-MFA, consentledger, genormaliseerde votes/constraint, auditlogging, CSP rollout, HSTS aan TLS edge, encrypted database/backups, restoretest, providercontracten, security/E2E/loadtests. Secrets roteren indien ze ooit in bron/history/dumps stonden; geschiedenis niet destructief herschrijven zonder afgesproken procedure.

API-headers: nosniff, no-referrer, DENY framing, permissions-policy zonder camera/microfoon/geolocation; API-responses private/no-store. Frontend CSP eerst op compatibiliteit testen; geen universele unsafe-inline policy als valse securityclaim. HSTS alleen op gecontroleerde HTTPS-hosts. CORS allowlist met lokale developmentorigins apart van productie.

Limiterkeys gebruiken gehashte e-mail/IP-combinaties om plaintext identifiers uit cachekeys te houden; combineer IP- en accountgrenzen. Trusted proxyconfig vooraf vastleggen. Redis gedeeld bij meerdere workers. Default grenzen zijn configureerbaar; geen permanente bans op gedeelde IP's. Incidentprocedure: beperk toegang, verzamel minimale auditmetadata, triage, herstel/rotate, juridische beoordeling en tijdige melding waar vereist.

Composer-audit na updates: geen advisories. npm-scan is niet schoon: geen gedwongen glob-majoroverride of frameworkdowngrade toegepast om een groen getal te krijgen. Capacitor-native lockfiles/builds moeten vóór native release worden bijgewerkt; alleen npm-packages patchen maakt een reeds gebundelde app niet veilig.
