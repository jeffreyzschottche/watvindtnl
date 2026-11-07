# WatDenktNederland

Nuxt/Vue-webclient met Capacitor iOS/Android en een centrale Laravel-API in `../wvnl-api`. V2 wordt incrementeel ontwikkeld op branch `refactor`. De gedeelde huisstijl en eenvoudige navigatie zijn vernieuwd. De volledige V2-release is nog niet afgerond. [De vier hoofdtaken en werkende routes](docs/UX.md). [Voortgang en testbewijs](docs/PROGRESS.md), [audit](docs/CURRENT_STATE.md), [architectuur](docs/ARCHITECTURE.md) en [migratieplan](docs/MIGRATION_PLAN.md).

## Lokaal starten

Benodigd: Node 24 LTS (zie `.nvmrc`), npm, PHP 8.3+, Composer, MySQL en een lokale mailopvang voor verificatie/reset. Redis is het doel voor productiequeues/cache/limiting; de huidige lokale backend kan database/filedrivers gebruiken.

```sh
nvm use
npm ci
cp .env.example .env
npm run dev
```

Frontend `http://localhost:3000`. `.env`: `NUXT_PUBLIC_API_BASE_URL=http://localhost:8000/api`. Herstart Nuxt bij env-wijzigingen. HTTPS naar de gewone Artisan-developmentserver veroorzaakt een netwerkfout. Zet geen Stripe/LLM/databasegeheimen in de frontend.

API, in een tweede terminal:

```sh
cd ../wvnl-api
composer install
# Alleen bij een nieuwe lokale omgeving: cp .env.example .env en php artisan key:generate.
# Configureer DB_* en FRONT_URL=http://localhost:3000; overschrijf bestaande .env niet.
php artisan serve --host=127.0.0.1 --port=8000
```

Bij een lege, expliciet lokale database: bekijk eerst `php artisan migrate:status`, daarna `php artisan migrate`. Gebruik op een bestaande database eerst het [backup-/restoreplan](docs/MIGRATION_PLAN.md). De aangetroffen lokale database heeft schemadrift: niet blind migraties of oude seeders uitvoeren. M1 heeft geen schema- of datamigratie nodig. Gebruik nooit `migrate:fresh` op bestaande gegevens. Geen echte mail, betalingen of LLM-calls nodig voor tests.

## Controleren

```sh
npm test
npm run typecheck
npm run build
npm run test:e2e
npm audit
```

`test:e2e` start tijdelijk de gebouwde server op poort 3010 en gebruikt lokaal de bestaande Google Chrome-installatie. In CI wordt Playwright Chromium geïnstalleerd. De browsertests gebruiken expliciete API-fixtures, geen echte politieke data. Volledige API/client E2E, native devicechecks en MySQL-concurrency staan nog open. Backend:

```sh
cd ../wvnl-api
php artisan test
composer audit
```

Testbootstrap weigert andere databases dan SQLite `:memory:`. [Teststrategie](docs/TESTING.md).

## Advertenties

`DEMO_ADS=1` activeert de lokale demo-component bij development/build; `DEMO_ADS=0` schakelt die uit. `ADS_ENABLED` is onafhankelijk. Voor een gedeployde Nuxt-server zijn de runtimeoverrides `NUXT_PUBLIC_DEMO_ADS` en `NUXT_PUBLIC_ADS_ENABLED` beschikbaar. Live advertenties blijven uit zolang de gecertificeerde CMP/provideradapter ontbreekt. Geen trackingrequests vóór toestemming. [Advertentieplan](docs/ADS.md).

## Content en kompas

Bestaande importservice en eigen beheerinterface worden behouden. Publieke nieuws-GET's genereren geen AI-content meer. Generatie is voorlopig admin-only; de queue/review/publicatiepipeline en het echte ZschotCMS-contract moeten nog worden gebouwd. Niet ongericht een import starten: dit kan betaalde AI-calls en publicatie veroorzaken. [Contentpipeline](docs/CONTENT_PIPELINE.md).

Het huidige LLM-kompas is legacy. De deterministische V2-methode, PDF en premiumaankopen zijn nog niet klaar. Geen functionele Stripe-/RevenueCat-integratie aanwezig; configureer pas na implementatie testkeys en sandboxproducten op de backend. [Kompas](docs/COMPASS_ALGORITHM.md), [betalingen](docs/PAYMENTS.md).

## Web en native bouwen

```sh
npm run build           # Nuxt-server in .output/server/index.mjs
npm run generate        # Statische client voor Capacitor
npx cap sync
npx cap open ios
npx cap open android
```

Native openen vereist Xcode/CocoaPods of Android Studio/JDK/SDK. Geen bewezen store-ready build: placeholder appidentifiers, deploymenttargets, signing en Podfile.lock moeten nog worden afgestemd op de bijgewerkte Capacitor-packages. `cap sync` pas na generate; bundel geen verouderde .output. Geen native release vanuit M1. iOS/Android blijven dezelfde API gebruiken.

## Productie

Bestaande Coolify-instructies: `../COOLIFY-DOCKER.md`. [Deployment](docs/DEPLOYMENT.md) beschrijft de releasevoorwaarden. Frontend en backend samen uitrollen bij gewijzigde resourcecontracten; oudere native clients vereisen versiebeleid. Geen automatische productiedatamigraties. Resterende dependencyadvisories, consent/privacyflows, RBAC/MFA, betalingen, CMP en native releases blokkeren production readiness. Juridische documenten en verwerking van politieke gegevens vereisen beoordeling. [Security](docs/SECURITY.md), [privacy](docs/PRIVACY.md).

### Content beheren vanuit ZschotCMS

Partijen, stellingen, argumenten en moties zijn lokaal te beheren via [WatDenktNederland in ZschotCMS](http://localhost:3100/projects/watdenktnederland/integrations/5). Het paneel heeft een partijseed, officiële motieophaling en een atomische contentimport. Zie [docs/ZSCHOTCMS.md](docs/ZSCHOTCMS.md) voor gebruik, API-contract, veilige live-koppeling en herstel.
