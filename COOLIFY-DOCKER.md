# Coolify Docker setup

Deze repository heeft nu twee aparte Docker services:

- Frontend: `wvnl/Dockerfile`
- Backend: `wvnl-api/Dockerfile`

## Frontend service

- Build context: `wvnl`
- Dockerfile: `Dockerfile`
- Port: `3000`
- Belangrijke env:
  - `NUXT_PUBLIC_API_BASE_URL=https://jouw-api-domein/api`

## Backend service

- Build context: `wvnl-api`
- Dockerfile: `Dockerfile`
- Port: `8080`
- Vereiste env:
  - `APP_KEY=base64:...`
  - `APP_URL=https://jouw-api-domein`
  - `FRONT_URL=https://jouw-frontend-domein`

- Aanbevolen env:
  - `APP_ENV=production`
  - `APP_DEBUG=false`
  - `RUN_MIGRATIONS=true`

## Database

Standaard werkt de backend ook met SQLite in de container als je geen andere DB-config meegeeft.
Voor productie op Hetzner is een managed of aparte MySQL/PostgreSQL service beter. Zet dan minimaal:

- `DB_CONNECTION=mysql` of `pgsql`
- `DB_HOST=...`
- `DB_PORT=...`
- `DB_DATABASE=...`
- `DB_USERNAME=...`
- `DB_PASSWORD=...`

## Opmerking

De backend-container herstelt bij startup automatisch de Laravel storage symlink, zodat lokale absolute paden geen problemen geven in Coolify.
