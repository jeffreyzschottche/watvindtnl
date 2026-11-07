# Advertenties en consent

Huidig vóór M1: GA en AdSense hardcoded, meervoudige scriptinjectie, analytics zelfs in development, geen geldige CMP-gating aangetroffen. Geen AdMob/RevenueCat-native integratie.

M1: automatische tracking-/advertentiescripts verwijderd. De demo-AdSlot staat alleen bij een afgeronde stemuitslag, onder de kaart. `DEMO_ADS=1` toont alleen duidelijk gelabelde, lokale advertentieplaatsing. `DEMO_ADS=0` verbergt die. `ADS_ENABLED` is onafhankelijk; live ads blijven fail-closed zolang gecertificeerde CMP/provideradapter niet geconfigureerd en getest is. Een eenvoudige eigen voorkeurenbanner is geen vervanger van een Google-gecertificeerde CMP.

M7: gecertificeerde CMP met vereiste TCF/Google UMP-integratie; deny/accept/update/revoke en logs, geen requests vóór toestemming. Gebruik development testunits voor AdMob. Frequentiecap, geen stemknop-overlays, geen ads in auth/payment/privacyflows. Politieke keuzes/categorieprofielen nooit naar advertenties of audience targeting sturen. Test beide flags onafhankelijk en inspecteer netwerk vóór/na consent.

DEMO_ADS/ADS_ENABLED worden lokaal bij Nuxt-config/build gelezen. Gebruik voor reeds gebouwde Nuxt-servers NUXT_PUBLIC_DEMO_ADS=true/false en NUXT_PUBLIC_ADS_ENABLED=true/false. Demo=uit zet geen live provider aan.
