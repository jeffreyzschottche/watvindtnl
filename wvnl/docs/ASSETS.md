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
- Geen autoplay, geluid, automatische volgende stelling, ongetoetste haptics of fictieve streaks. De persoonlijke teller gebruikt de bestaande accountgegevens.
- `prefers-reduced-motion` schakelt decoratieve beweging uit. Een aparte footerknop laat gebruikers animaties uitschakelen; deze functionele voorkeur wordt lokaal bewaard in de cookie `wdnl_motion` (maximaal een jaar).
- Website en Capacitor iOS/Android gebruiken dezelfde Vue-componenten. `npm run build:mobile` genereert de statische app-entry en kopieert de nieuwe assets naar beide native projecten. Deze stap is lokaal geslaagd. Er is geen nieuwe native binary, App Store/Play Store-build of release gemaakt.
