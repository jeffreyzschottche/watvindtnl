# Deployment

Bestaande Coolify/Docker-instructies: ../COOLIFY-DOCKER.md. Geen productie uitgerold in M1. Publiceer frontend en API atomair wegens gewijzigd stemresourcecontract; houd oude native clients tegen of versieer de API vóór store-release.

Doel: gereproduceerbare builds met Node24/PHP-supported images, npm ci/composer install --no-dev, scan + tests vóór release, APP_DEBUG=false, secretsmanager, centrale TLS edge, expliciete CORS en trusted proxies. API + queueworker + scheduler afzonderlijke processen; Redis shared cache/limiter/queue, least-privilege DB met TLS, CDN uitsluitend publieke assets. Politieke API's niet publiek cachen. Gezondheidscheck /up uitbreiden met minimale dependency readiness zonder gevoelige metadata.

Migraties zijn een aparte gecontroleerde releasestap na backup/restorebewijs; niet onbeheerst op iedere containerstart. Geen productie-LLM/Checkout of ads zonder configuratie, consent en goedkeuring. Monitoring: errors, latency, 4xx/5xx/429, queue lag, sync freshness, webhook failures en recovery alerts; geen requestbodies/tokens/politieke keuzes in logs. Rollback via codeversie + MIGRATION_PLAN.md, geen DB-tabeldrop.

Native releases: definitieve appidentifiers/signing, compatibele iOS deploymenttarget/Xcode, Android SDK/Gradle/Play-policy, privacylabels, in-app accountdelete en sandbox-IAP. Builds en fysieke apparaten nog te verifiëren.
