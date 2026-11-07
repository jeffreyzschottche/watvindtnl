# Eenvoudige WDNL-ervaring

Bijgesteld op verzoek van de gebruiker: de uitgebreide V2-paginalijst blijft een functie-inventaris, geen navigatiestructuur met tientallen gelijkwaardige ingangen. Website en mobiele wrapper gebruiken dezelfde accounts, API en stemlogica.

## Vier hoofdtaken

1. **Stemmen** — `/spel`: één vraag, tegen links/voor rechts, uitslag blijft tot handmatig volgende.
2. **Ontdekken** — `/stellingen`: zoeken en, na inloggen, alle/niet-beantwoord/beantwoord. `/stellingen/[slug]` geeft hetzelfde stemcomponent met context en argumenten.
3. **Jezelf begrijpen** — `/kompas`: overzichtelijke uitleg. Nieuwe betaalde versie nog niet te koop; geen verzonnen scores, claims of checkout.
4. **Account beheren** — `/profiel`: twee hoofdkaarten voor Mijn stemmen en Mijn kompas, daaronder gegevens/beveiliging/privacy-informatie.

Op desktop: merklogo/home, Stellingen, Kompas, Over ons, Inloggen/Mijn profiel en Begin met stemmen. Op mobiel: compacte header met menu; ingelogde app-pagina's hebben vier tabs Spel/Overzicht/Kompas/Profiel. Details en instellingen blijven binnen hun hoofdtaak gemarkeerd.

## Ondersteunende routes

`/mijn-stemmen`, `/profiel/instellingen?tab=profile|security|compass`, `/inloggen`, `/registreren`, `/wachtwoord-vergeten`, `/wachtwoord-resetten` en `/email-verifieren`. Informatie: `/over-ons`, `/faq`, `/contact`, `/methodologie`, `/privacy`, `/voorwaarden`.

Legacy URLs blijven bereikbaar via aliases of de bestaande pagina's. Geen aparte database/businesslogica voor mobiele UI. Bestaande profielbewerking/geschiedenis/contact/reset behouden. De registratievelden volgen nog het bestaande backendcontract; een verdere dataminimalisatie hoort bij de consent/auth-mijlpaal, niet bij een cosmetische inkorting die onjuiste gegevens verstuurt.

## Vormgeving

Witte header en kaarten, #F5F7FA basis, #001F4D tekst, #0057B8 accenten, #E1062E hoofd-CTA. Rustige randen, lichte schaduwen, heldere typografie en lokale bestaande fotografie. Het gereconstrueerde molenlogo staat in de lokale ontwerpversie ter beoordeling; geen finale merkgoedkeuring of native app-icon-release aangenomen.

Homepage: Nederlandse landschapshero, echte API-stellingen, kort proces, kompas, FAQ en compacte footer. Geen verzonnen deelnemersaantallen, actuele labels op historische content of percentages vóór stemmen. Authpagina's krijgen compacte formulieren zonder oude fotografische banners. Hoofdfunctionaliteit blijft hergebruikt.

## Verificatie en grenzen

Browsertests gebruiken expliciete fixtures en controleren desktop/mobiel, witte header, nieuwe kleuren, geen horizontale overflow, zoeken, detailroute, profiel, login en bestaande stemflow. Screenshots zijn QA-artifacts, geen productiegegevens. Daarnaast is de draaiende localhost:3000-homepage met echte read-only API-data gecontroleerd: drie echte stellingen en geen pageerrors.

Deze ontwerpstap voltooit niet alle V2-onderdelen. Server-side rendering/SEO, consentledger, privacy-acties, deterministisch kompas, betalingen, native signing/builds en CMP blijven de open mijlpalen uit MIGRATION_PLAN.md. Privacy-informatielinks zijn geen claims van functionele export/verwijdering.
