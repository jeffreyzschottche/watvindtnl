# Advertenties en consent

Huidig vóór M1: GA en AdSense hardcoded, meervoudige scriptinjectie, analytics zelfs in development, geen geldige CMP-gating aangetroffen. Geen AdMob/RevenueCat-native integratie.

M1: automatische tracking-/advertentiescripts verwijderd. De demo-AdSlot staat alleen bij een afgeronde stemuitslag, onder de kaart. `DEMO_ADS=1` toont alleen duidelijk gelabelde, lokale advertentieplaatsing. `DEMO_ADS=0` verbergt die. `ADS_ENABLED` is onafhankelijk; live ads blijven fail-closed zolang gecertificeerde CMP/provideradapter niet geconfigureerd en getest is. Een eenvoudige eigen voorkeurenbanner is geen vervanger van een Google-gecertificeerde CMP.

M7: gecertificeerde CMP met vereiste TCF/Google UMP-integratie; deny/accept/update/revoke en logs, geen requests vóór toestemming. Gebruik development testunits voor AdMob. Frequentiecap, geen stemknop-overlays, geen ads in auth/payment/privacyflows. Politieke keuzes/categorieprofielen nooit naar advertenties of audience targeting sturen. Test beide flags onafhankelijk en inspecteer netwerk vóór/na consent.

DEMO_ADS/ADS_ENABLED worden lokaal bij Nuxt-config/build gelezen. Gebruik voor reeds gebouwde Nuxt-servers NUXT_PUBLIC_DEMO_ADS=true/false en NUXT_PUBLIC_ADS_ENABLED=true/false. Demo=uit zet geen live provider aan.

Met `DEMO_ADS=1` op localhost in `nuxt dev` tonen de advertentieplekken en de stempauze [Googles officiële GPT-testadvertentie](https://developers.google.com/publisher-tag/samples/display-test-ad) in een iframe. De advertentie komt uit Googles testnetwerk. Bij andere builds blijft de bestaande demo-opmaak beschikbaar volgens de demo-flag.

## Zichtbare demo-plaatsen — 8 november 2026

De eerdere plaatsing alleen ná een stem is uitgebreid. Lokale `.env`: `DEMO_ADS=1`, `ADS_ENABLED=0`. Nuxt herlaadt de configuratie; de mobiele build neemt de waarden mee.

- Spel: plaatsing onder de actieve kaart, ook vóór het stemmen. Niet over stemknoppen of argumenten.
- Overzicht: plaatsing bij zoeken/lijst. Home: tussen inhoudssecties. Profiel: onder het accountoverzicht. Geen advertenties in rapportbetaling, rapport-PDF, authenticatie of privacyflows.
- De plaatsingen zijn gelabeld als **Advertentie · demo** of **Advertentie · Google-test**. **Bekijk voorbeeld** opent dezelfde weergave in een dialoog. Je kunt meteen sluiten met de sluitknop, Verder of Escape.
- Na vijf afgeronde stemmen opent bij de expliciete klik op **Volgende stelling** een demo-pauze; niet op de stemklik en niet na overslaan van een onbeantwoorde vraag. Na sluiten gaat de gebruiker verder. Maximaal eenmaal per twee minuten in de actieve appsessie. Alleen aantal/tijdstip blijven in geheugen, geen stelling-ID of keuze.
- Native HTML-dialog verzorgt focus/inert-achtergrond en Escape op ondersteunde browsers/WebViews. Chrome desktop en Android-emulatie zijn getest; echte devicecontrole blijft apart nodig.
- `DEMO_ADS=0` schakelt de advertentieplekken en de demo-pauze uit. Live advertenties hebben een aparte configuratie voor provider en toestemming.

De lokale demo is geen geïmplementeerde AdMob SDK of gecertificeerde CMP. Browserregressies controleren zichtbaarheid vóór stemmen, openen/sluiten, de vijfde handmatige overgang en ontbreken van advertentie-/analyticsnetwerkverzoeken.
