# Illustratie en beweging — WDNL

## Nederlandse buurtillustratie

- Gebruik: decoratieve illustratie op de homepage en bij een afgeronde stemfeed. Het is geen foto of weergave van een werkelijk nieuwsincident.
- Bestand in de applicatie: `public/illustrations/dutch-neighbours-v1.jpg` (1536 × 1024, JPEG, lazy loading).
- Generatie: ingebouwde `image_gen.imagegen`-tool, via de imagegen-skill. Geen CLI/API-fallback, geen toegevoegde logos of vervanging van de molenidentiteit.
- Origineel: `/Users/jeffreyzschottche/.codex/generated_images/01a1b77e-801b-73a1-bf81-24807bd38533/exec-68ac9bc8-1537-4c7b-95d7-85276ab1c1f5.png`.
- Conversie: standaard macOS `sips`, JPEG-kwaliteit 80; dezelfde inhoud en verhouding.

Definitieve generatieprompt:

> Use case: illustration-story. Asset type: original landscape editorial illustration for the WatDenktNederland Dutch public-opinion web and mobile app, usable in a welcoming home section and empty states. Primary request: a beautiful, playful yet premium miniature Dutch neighbourhood where different adults exchange ideas. Scene: a small Amsterdam-style canal-side square, three charming brick gabled houses, one bicycle, a small green tree, a bench, and six diverse adult neighbours having relaxed conversations, with a few empty rounded speech bubbles floating subtly above them. Style: refined handcrafted paper-and-matte-clay 3D editorial illustration, soft tactile texture, clean generous composition, attractive detailed architecture, not a plastic toy or political campaign. Composition: landscape 3:2, complete little neighbourhood centred with plenty of clean light off-white space around it, all objects fully contained, warm daylight, soft contact shadows. Palette: white/light grey #F5F7FA, deep navy #001F4D, rich blue #0057B8, balanced small Dutch red #E1062E accents and restrained muted greens. Mood: welcoming, curious, optimistic, accessible, a serious consumer product with personality. Text: none. Constraints: no lettering, no party symbols, no voting preference or thumbs-up/down, no flags or logos, no UI mockup, no watermarks. This is decorative illustration, not documentary photography.

## GSAP

GSAP 3.15.1 is exact vastgezet in package.json en de lockfile, gecontroleerd bij npm en de [officiële installatiehandleiding](https://gsap.com/docs/v3/Installation/). Alleen de lokale core wordt gebruikt: geen CDN, tracking, nieuwe externe runtime-aanvragen of extra confetti-library.

- Pagina-elementen komen kort in beeld via IntersectionObserver en GSAP; kaarten reageren op een muisaanwijzer. Animaties worden per route en unmount opgeruimd via context.revert.
- Na een bevestigde nieuwe stem bewegen de uitslagbalk en bevestiging. Een korte confettianimatie viert deelname, identiek voor vóór en tegen. Mislukte stemmen en reeds beantwoorde stellingen starten geen confetti.
- Geen geluid, automatische volgende stelling, ongetoetste haptics of fictieve streaks. De persoonlijke teller gebruikt de bestaande accountgegevens. De decoratieve floatanimaties hieronder starten alleen bij ingeschakelde beweging en hebben een directe pauzeknop.
- `prefers-reduced-motion` schakelt decoratieve beweging uit. Een aparte footerknop laat gebruikers animaties uitschakelen; deze functionele voorkeur wordt lokaal bewaard in de cookie `wdnl_motion` (maximaal een jaar).
- Website en Capacitor iOS/Android gebruiken dezelfde Vue-componenten. `npm run build:mobile` genereert de statische app-entry en kopieert de nieuwe assets naar beide native projecten. Deze stap is lokaal geslaagd. Er is geen nieuwe native binary, App Store/Play Store-build of release gemaakt.

## Nieuwe 3D-art — 8 november 2026

- `public/illustrations/dutch-island-v2.webp`: 900 × 1125, circa 206 KiB, transparant Nederlands buurteiland voor de hero. Decoratief; de bestaande logo-identiteit wordt niet vervangen.
- `public/illustrations/compass-v2.webp`: 850 × 850, circa 70 KiB, transparant keramiek/glazen kompas op home en kompaspagina. Geen echte score of voorbeeld van een berekend gebruikersrapport.
- Beide zijn gemaakt met de ingebouwde `image_gen.imagegen`-tool via de imagegen-skill, zonder CLI/API-fallback. Definitieve prompts, originele bestanden en WebP-conversie staan in [design/v3-assets.json](design/v3-assets.json). Echte alpha behouden bij conversie met cwebp.
- GSAP in `MotionScene.vue`: langzaam zweven en lichte pointerparallax via [quickTo](https://gsap.com/docs/v3/GSAP/gsap.quickTo/). Geen camera-afhankelijke toestemming, WebGL-scène of nieuwe runtime-library nodig.
- IntersectionObserver en document visibility pauzeren floats buiten beeld/in verborgen tabs. Pauzeknop, globale voorkeur en reduced-motion stoppen decoratie; listeners, tweens en observers worden bij unmount opgeruimd. Content blijft leesbaar zonder beweging.
- Hero-afbeelding laadt met hoge prioriteit en opgegeven afmetingen; illustraties verderop laden lazy. Transparante art wordt met CSS-chips en achtergrondlagen samengesteld, niet als afbeelding met ingebakken UI-tekst.

## Lettertype en licentie

Voorheen vermeldde CSS Inter zonder meegeleverd fontbestand; het daadwerkelijke font kon daardoor het lokale systeemfont zijn. Nu laadt `Manrope` als lokaal variable font, gewichten 200–800, met preload en `font-display: swap`. Geen Google Fonts CDN-aanvragen. De officiële TTF is ongewijzigd overgenomen uit [google/fonts/ofl/manrope](https://github.com/google/fonts/tree/main/ofl/manrope).

Manrope is auteursrechtelijk beschermd en uitgegeven onder de **SIL Open Font License 1.1**. Die staat commercieel gebruik en bundeling in website/software toe. Bewaar bij distributie de copyrightmelding en licentietekst; het font mag niet op zichzelf worden verkocht. Het is geen publiek domein en de applicatiecode hoeft hierdoor niet onder de OFL te vallen. De officiële tekst staat lokaal in `public/fonts/Manrope-OFL.txt` en [bij de bron](https://raw.githubusercontent.com/google/fonts/main/ofl/manrope/OFL.txt). Beide bestanden gaan mee naar de mobiele projecten.

## Politiek-kompasrapport — 8 november 2026

`public/report-art/`: lokaal gemaakte SVG-vormen, negen typewielen plus een neutrale toestand, pictogrammen, vlaglint, spectrum en golf. Gebaseerd op de door de gebruiker aangeleverde rapportreferentie, met het reeds gebruikte WDNL-molenasset. Geen gedownloade partijlogo’s, externe iconenbundel of bitmap van een gebruikersrapport. Regenereren via `python3 scripts/design/generate-compass-art.py`. Voor de PDF staan dezelfde SVG's onder `wvnl-api/resources/views/reports/art`; na regenereren daar de overeenkomstige bestanden bijwerken (mark.svg komt uit het bestaande merkasset).

De PDF gebruikt ingebedde DejaVu Sans via Dompdf; web gebruikt lokale Manrope. Het openbare V3-voorbeeld bevat expliciet fictieve voorbeeldstellingen en antwoorden, met een zichtbare VOORBEELDRAPPORT-melding. De echte PDF wordt op de server opgebouwd uit de eigenaarsnapshot, niet door een schermafbeelding van de referentie te leveren.


## Rapportillustratie V3 — 9 november 2026

- Imagegen-skill met de ingebouwde `image_gen`-tool gebruikt; geen CLI-fallback of gegenereerde tekst/cijfers. Eenmalige decoratieve illustratie van een keramieken kompas met Nederlandse landschapselementen. Geen partijlogo’s.
- `public/illustrations/reports/compass-sculpture-v3.webp` (circa 100 KiB) voor web en `compass-sculpture-v3.png` (900px, circa 742 KiB) voor de PDF. Alpha behouden bij verkleining/conversie met sips/cwebp. De identieke PNG is lokaal onder `wvnl-api/resources/views/reports/art` opgeslagen.
- Definitieve prompt, toolmodus, bronbestand en SHA-256-hashes: [design/compass-report-v3-assets.json](design/compass-report-v3-assets.json). De politieke inhoud en grafieken worden afzonderlijk met code en de eigen bewaarde antwoorden opgebouwd.
- Het openbare voorbeeld-PDF is twee A4’s, zichtbaar als fictief gemarkeerd. Geen echte gebruikersdata in `public/`, native assets of Git.


## Eén-A4-kompas V4

- Het rapport gebruikt het bestaande WDNL-molenasset en de bestaande Manrope-woordnaam. `public/brand/wdnl-logo.png` is een scherpe export van diezelfde lockup, gemaakt met `scripts/design/export-report-logo.mjs`; geen nieuw AI-logo of partijlogo. Een identieke lokale kopie staat binnen Dompdfs chroot.
- Zestientypenwielen: `public/report-art/wheel-v4-*.svg`. Bron: `CompassTypes::ALL`; `php scripts/design/export-compass-types.php` exporteert de definitie voor `python3 scripts/design/generate-poster-art.py`. Bestaande pictogrammen en het merkasset worden hergebruikt.
- Dompdf tekende enkele SVG-segmenten onjuist. Daarom zijn dezelfde SVG-wielen via de bestaande browserrenderer omgezet naar lokale print-PNG’s met `node scripts/design/rasterize-poster-art.mjs`; deze staan uitsluitend onder de backendrapport-assets. Geen modelaanroep of gebruikersgegevens in de afbeeldingen.
- De eerdere 3D-illustratie is uit de actuele rapportcompositie gehaald. De openbare voorbeeld-PDF is zichtbaar fictief en precies één A4. De echte rapporttekst, type, scores en partijovereenkomsten zijn geen onderdeel van de logo-/wielafbeeldingen.
