# Architectuur — besluitlog

Status: incrementele doelarchitectuur, M1 is de eerste implementatiemijlpaal. De roadmap is geen claim dat alle onderdelen bestaan.

## ADR-001: bestaande stack behouden

Nuxt voor publieke SSR-website en webapp, Laravel als modulaire centrale API, MySQL als centrale database en Capacitor voor gedeelde mobiele UI. Gebruik bestaande `wvnl/` en `wvnl-api/`; een mappenverhuizing levert nu risico zonder productwinst. Modules in API: identity/consent, statements/content, voting, compass, commerce, privacy en administration. Redis voor queues/cache/rate limits in productie. Workers draaien apart, dezelfde code en contracten. Geen microservices.

## ADR-002: versies en herhaalbaarheid

Op 2026-11-07 gecontroleerd via npm-registry en officiële releases: Nuxt 4.6.4; engine `^22.22.3 || ^24.15.0 || >=26`. Kies Node 24 LTS (lokaal 24.20.0 beschikbaar), exacte Nuxt-versie en npm-lockfile. Nieuwste geregistreerde Vue 3.5.45, Pinia 4.1.0, @pinia/nuxt 1.0.3, TypeScript 7.0.2, vue-tsc 3.3.14; niet blind alle majors installeren. Uitgevoerd in M1: Nuxt 4.6.4, Vue 3.5.45, Vue Router 5.4.0 (door Nuxt vereist), TypeScript 5.9.3 en vue-tsc 3.3.14. TypeScript 7 is bewust niet in dezelfde mijlpaal meegenomen; vue-tsc staat >=5 toe en de gekozen combinatie is getypecheckt. Pinia blijft voorlopig in de bestaande compatibele major. Capacitor 7 is in M1 gepatcht naar 7.6.10. Native Podfile.lock/generated projects zijn nog niet gesynchroniseerd of als release gebouwd. Capacitor 8.6.0 is beschikbaar; native major-upgrade pas met Xcode/Gradle/store-compatibiliteitscontrole.

Laravel 13 vereist PHP >=8.3. Laravel 12 krijgt securityfixes tot 2027-02-24; M1 heeft de 12.x dependencygraph gepatcht naar Laravel 12.71.0 / Sanctum 4.3.3 en regressietests uitgevoerd; Laravel 13 als aparte mijlpaal met compatibiliteitstest. PHP 8.3 lokaal; doel ondersteunde PHP-runtime vastzetten na container-test. Geen `latest` tags als releasebewijs. Bewaar auditresultaten per release en faal CI op onopgeloste high/critical advisories.

Bronnen: [Nuxt releases](https://github.com/nuxt/nuxt/releases), [Nuxt npm](https://www.npmjs.com/package/nuxt), [Laravel support](https://laravel.com/docs/13.x/releases), [Node releases](https://nodejs.org/en/about/previous-releases), [Capacitor migratie](https://capacitorjs.com/docs/updating/8-0).

## ADR-003: contract en data

Bestaande issue-ID's blijven stabiel. Publieke antwoorden bevatten nooit gebruikers-ID's per keuze. Resultaten zijn alleen geaggregeerd beschikbaar nadat de huidige gebruiker stemde. Nieuwe keuzes zijn voor/tegen; historische neutraal blijft apart. Frontend en API worden samen uitgebracht bij deze contractwijziging; native clients hebben versiebeleid nodig voor productie.

Genormaliseerde votes met unieke constraint en echte created_at voor nieuwe stemmen. Oude datums blijven unknown met provenance. Cursorpagination en gescheiden results-endpoint. API-resourcecontract wordt in API.md bijgehouden; later OpenAPI genereren/valideren in CI.

## ADR-004: privacy en betalingen

Web: HttpOnly secure sessie met CSRF; native: Sanctum tokens in keychain/keystore. Politieke consent is afzonderlijk, versieerbaar, intrekbaar en backend-verplicht. Geen persoonsgebonden stemdata naar advertenties. Kompasberekening deterministisch, uitleg en confidence apart; alleen optionele rapporttekst door LLM na passende toestemming en leverancierscontrole.

Stripe web en RevenueCat native leveren events aan centrale entitlementledger. `political_compass_lifetime` is voorgestelde productbetekenis: levenslange toegang met begrensde hergeneratie. Webhook verwerkt idempotent, success redirect kent geen rechten toe. Geen checkout in native zolang storebeleid/configuratie niet geverifieerd is.

## ADR-005: referentieontwerp

De drie userreferenties bepalen kleur, logo-concept, website-layout en mobiele navigatie. Tokens: navy #001F4D, blue #0057B8, red #E1062E, background #F5F7FA, white #FFFFFF. Inter/system fallback, weinig schaduw, focuszichtbaarheid, reduced motion, 44px aanraakdoelen, toetsenbord en contrast controleren. Links tegen, rechts voor. Uitslag blijft staan tot Volgende stelling. Geen fictieve statistieken. Het oude SVG is geen goedgekeurd molenlogo: een nieuwe vector wordt als concept ter beoordeling aangeboden; geen finale logogoedkeuring aangenomen.

## Uitgevoerde keuze: ZschotCMS-contentbeheer

De bestaande CMS-modules van Allergie-ID en Spot & Find zijn als referentie geïnspecteerd. WDNL gebruikt dezelfde Integration/Action-registratie, versleutelde serverconfiguratie en projectgebonden acties. Eén centrale Laravel-content-API beheert partijen, stellingen/argumenten en officiële bronmoties voor alle clients. Geen databasecredentials in het CMS; afzonderlijke contenttokens kunnen geen appaccounts of stemmen lezen. Een begrensde officiële motieophaling vereist geen LLM/worker en publiceert niets. Zware PDF/OCR/LLM-verwerking blijft buiten deze handmatige beheerstap en wordt niet via een publieke GET gestart. Contract, grenzen en uitrol: [ZSCHOTCMS.md](ZSCHOTCMS.md).

## Interactie en maatschappelijke stellingen

GSAP 3.15.1 verzorgt korte paginareveals en stemfeedback in de gedeelde Nuxt/Capacitor-client. Geen extra confetti-dependency: één lokaal GSAP-component animeert uitsluitend na een bevestigde stem. Systeemvoorkeur en een gedeelde, bewaarde gebruikersvoorkeur kunnen alle decoratieve beweging uitschakelen. Details: [ASSETS.md](ASSETS.md).

De contentgrens ondersteunt onafhankelijke redactionele vragen en nieuwsaanleidingen naast gekoppelde moties. `StatementMetadata` draagt herkomst en bronlabels; publieke serialisatie bevat uitsluitend broninformatie. Het CMS haalt geen nieuwsartikelen op. De bronvalidatie hangt af van de gekozen herkomst, terwijl versiecontrole, stembehoud en de gate op acht verschillende argumenten blijven bestaan.
