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

Witte header en kaarten, #F5F7FA basis, #001F4D tekst, #0057B8 accenten, #E1062E hoofd-CTA. Manrope wordt lokaal geladen. Gelaagde oppervlakken, tactiele 3D-illustraties en rustige beweging geven karakter. Het gereconstrueerde molenlogo staat in de lokale ontwerpversie ter beoordeling; geen finale merkgoedkeuring of native app-icon-release aangenomen.

Homepage: lichtblauwe hero met Nederlands 3D-buurtje, echte API-stellingen, kort proces, kompasillustratie, FAQ en compacte footer. Geen verzonnen deelnemersaantallen, actuele labels op historische content of percentages vóór stemmen. Authpagina's krijgen compacte formulieren zonder oude fotografische banners. Hoofdfunctionaliteit blijft hergebruikt. Assetherkomst, prompts en fontlicentie staan in [ASSETS.md](ASSETS.md).

## Laws of UX toegepast — 8 november 2026

De principes uit [Laws of UX](https://lawsofux.com/) dienen als ontwerphouvast. Het zijn geen garantie op gebruiksgemak, prestatiecertificering of vervanging van gebruikersonderzoek. We passen ze toe in de bestaande gedeelde Nuxt/Capacitor-interface, zonder een extra uitlegscherm voor eindgebruikers toe te voegen.

| Principe | Concrete toepassing |
| --- | --- |
| Hick's Law / Choice Overload | Vier hoofdtaken; twee gelijkwaardige stemkeuzes. Eén duidelijke primaire actie per stap. Rapporteren en delen krijgen een rustige secundaire stijl. |
| Fitts's Law | Minimaal 44 px hoge stem-, meld-, deel-, filter-, zoekwis- en animatieknoppen. Het hele stellingkaartje is aanklikbaar. |
| Jakob's Law | Logo naar home, herkenbare zoekbalk, vaste mobiele tabs, expliciete labels en terugkerende knopstijlen. Actieve accountsectie blijft gemarkeerd bij instellingen. |
| Chunking / Common Region / Proximity | Profiel, stemmen en kompas zijn aparte groepen. Context, bronnen en partijinformatie staan bij hun stelling in een uitklapbaar blok. |
| Cognitive Load | Argumenten en achtergrond openen niet automatisch na een stem. Een handmatig geopend blok blijft beschikbaar; de volgende stelling begint weer compact. |
| Doherty Threshold | Directe ingedrukte/disabled feedback en laadmeldingen. Zoeken meldt laden, resultaat of fout; wissen herstelt focus. Geen claim van een gemeten responstijd onder 400 ms. |
| Von Restorff Effect | Rode hoofd-CTA, rustige secundaire acties. Voor en tegen houden gelijke afmetingen en gelijkwaardige visuele nadruk. |
| Peak-End Rule | Bevestiging en korte, identieke deelnameconfetti na een geslaagde stem; uitslag blijft staan tot bewust volgende. Geen nepbeloningen of afteltimers. |
| Aesthetic-Usability Effect | Illustraties en diepte ondersteunen de hiërarchie. Tekst, contrast, focus en echte foutafhandeling blijven nodig; mooi alleen is onvoldoende. |

Decoratieve beweging staat los van de taak: pauzeren kan bij de illustratie en in de footer, de keuze wordt bewaard, systeem-reduced-motion wordt gerespecteerd. Floatanimaties stoppen buiten beeld en in een verborgen tabblad. Toetsenbordgebruikers kunnen meld-/deelopties openen, met Escape sluiten en terugkeren naar de trigger. De mobiele tabbar benoemt de actieve sectie met `aria-current`.

We vermijden het letterlijk toepassen van iedere wet: bijvoorbeeld een kunstmatig doelpercentage, schaarste of verplicht aantal keuzes levert bij politieke meningen ongewenste sturing op. Een volgende productcontrole hoort echte gebruikers te laten stemmen, zoeken en terugnavigeren; automatische tests bewijzen alleen het gecontroleerde gedrag.

## Verificatie en grenzen

Browsertests gebruiken expliciete fixtures en controleren desktop/mobiel, witte header, nieuwe kleuren, geen horizontale overflow, zoeken, detailroute, profiel, login en bestaande stemflow. Screenshots zijn QA-artifacts, geen productiegegevens. Daarnaast is de draaiende localhost:3000-homepage met echte read-only API-data gecontroleerd: drie echte stellingen en geen pageerrors.

Deze ontwerpstap voltooit niet alle V2-onderdelen. Server-side rendering/SEO, consentledger, privacy-acties, deterministisch kompas, betalingen, native signing/builds en CMP blijven de open mijlpalen uit MIGRATION_PLAN.md. Privacy-informatielinks zijn geen claims van functionele export/verwijdering.
