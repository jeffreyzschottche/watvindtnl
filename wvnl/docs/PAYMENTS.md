# Betaling per rapport

Productbesluit 8 november 2026: **€1,99 per afzonderlijke rapport-snapshot**. Opnieuw bekijken/downloaden is inbegrepen. Een nieuw rapport vraagt een nieuwe aankoop. Het eerdere voorstel voor levenslange kompasgeneratie vervalt; `users.premium` geeft geen gratis toegang tot nieuwe generaties.

## Geïmplementeerd voor web

Stripe PHP 22.1.0; Checkout in payment-modus met vaste serverprijs 199 EUR-cent. Geen bedrag/product of success-URL uit de client. Alleen een ondoorzichtige orderreferentie gaat naar Stripe; geen stemgedrag. Idempotente aanvraagkey en Stripe-idempotency-key voorkomen dubbele sessies voor herhaalde verzoeken. Een bewust nieuwe aankoop krijgt een nieuwe order en een nieuwe snapshot.

Webhook `/api/reports/stripe-webhook` gebruikt de officiële SDK voor signature-/timestampcontrole op de ruwe body, bewaart event-IDs uniek en controleert mode, bedrag, valuta, order- en sessiereferentie. `checkout.session.completed` en `checkout.session.async_payment_succeeded` verwerken alleen `payment_status=paid`. Een redirect betaalt/ontgrendelt nooit zelf. Na de transactie start de reportqueue. Expiratie/mislukte asynchrone betaling sluit de pending order; een volledige terugbetaling trekt alleen dat specifieke rapport in. Een refund tijdens genereren kan niet door een worker worden overschreven. Deelrefunds/disputen vragen nog een expliciete operationele policy en tests vóór verkoop.

De politieke snapshot, rapporttekst en PDF zijn versleuteld opgeslagen en alleen toegankelijk voor de eigenaar. Generatie en downloads zijn apart: een download start geen modelaanroep. Een mislukte generatie houdt de oorspronkelijke betaalregistratie; de UI verwijst naar herstel/terugbetaling en vraagt niet opnieuw te betalen.

## Configuratie en releasecontrole

Server-only: `STRIPE_SECRET_KEY`, `STRIPE_WEBHOOK_SECRET`, `WDNL_WEB_URL` en `WDNL_REPORT_SALES_ENABLED=false`. De laatste blijft uit totdat testbetalingen, webhookbezorging, worker, refund/herstelprocedure en privacy-/producttekst zijn gecontroleerd. Configureer bovengenoemde events op dezelfde Stripe test/live-omgeving. Betaalmethoden worden door Checkout/accountconfiguratie bepaald; iDEAL-beschikbaarheid is nog niet als getest geclaimd.

Worker: `DB_QUEUE_RETRY_AFTER=240` en `php artisan queue:work --queue=reports --tries=1 --timeout=160`. Gebruik duurzaam procesbeheer en alerts. Het rapportjob heeft een eigen statusclaim en begrensde providerpogingen. Voer vóór nieuwe verkoop een betaalde-sandboxflow uit, inclusief dubbele events, asynchrone betaling, verkeerde bedragen en refund. Tests in deze wijziging gebruiken gesigneerde fictieve events en doen geen echte aankopen.

## iOS en Android

Nieuwe in-app aankopen zijn bewust nog niet aangesloten; de app meldt dit en toont geen webcheckoutlink. Reeds gekochte rapporten delen wel de centrale eigenaars-API. De nieuwe herhaalaankoop past bij een **consumable / eenmalig krediet per rapport**, niet bij een non-consumable lifetime entitlement. RevenueCat/storeproductmapping, serververificatie, transactie-uniciteit, refund/revocation en herstel van de accountgebonden rapportbibliotheek moeten hiervoor worden geïmplementeerd en getest. Een consumable opnieuw verkopen mag geen tweede besteding van dezelfde storetransactie mogelijk maken.

Er is lokaal geen echte betaling of store-release uitgevoerd. Details van instellingen, berekening en kosten staan in [REPORTS.md](REPORTS.md). Bronnen: [Stripe fulfillment](https://docs.stripe.com/checkout/fulfillment.md?ui=full-page), [webhookcontrole](https://docs.stripe.com/events/manage-webhook-endpoints).
