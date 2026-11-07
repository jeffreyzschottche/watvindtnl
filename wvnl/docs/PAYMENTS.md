# Betalingen

Audit: geen Stripe/RevenueCat SDK, betaalcontroller, webhook of entitlementledger aangetroffen. Losse lege environmentvelden zijn geen betaalintegratie. Geen echte betalingen uitgevoerd.

Doel M5/M6: eenmalig EUR 1,99, `political_compass_lifetime`, één WDNL user-ID over web/native. Stripe Checkout op server (iDEAL waar ondersteund), success/pending/cancel flows, server-side reeds-bestaand entitlement check. Checkoutmetadata bevat alleen opaque referenties. Signaturecontrole op ruwe webhookbody, event-ID uniek, transactioneel ledger + entitlement, replay-safe, refund/revoke events. Success URL kent geen rechten toe. Bewaar geen kaartgegevens. Concurrente checkout-/webhooktests en aankoopherstel verplicht.

RevenueCat non-consumable/lifetime met vaste app_user_id, geauthenticeerde serverevents en idempotente synchronisatie van bronrechten. Een refund bij provider A trekt een geldig onafhankelijk recht bij provider B niet in. RevenueCat Web Billing/Stripe als alternatief onderzoeken vóór definitief adaptercontract. Geen Stripe-links in native zonder actuele policycheck. Adapters moeten fail-closed reageren zolang credentials/product mapping ontbreken; geen fake entitlement in productie.

Nog nodig: Stripe test/live account en webhooksecret, price/product-ID, RevenueCat project/product/offering/entitlement, App Store/Play overeenkomsten en sandboxaccounts, publiek bereikbaar sandboxwebhookendpoint. Zie officiële [Stripe fulfillment](https://docs.stripe.com/checkout/fulfillment) en [RevenueCat webhooks](https://www.revenuecat.com/docs/integrations/webhooks). Secrets uitsluitend backend, geen echte aankopen in CI.
