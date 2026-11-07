# Contentpipeline

Aangetroffen: MotionImportService gebruikt Tweede Kamer OData `https://gegevensmagazijn.tweedekamer.nl/OData/v4/2.0`, haalt moties/documenten op, downloadt PDF, probeert pdftotext/mutool/OCR, bouwt context, vraagt gestructureerde AI-output, dedupliceert op slugs en importeert via admincode. Status wordt naar bestanden geschreven. Geen werkende scheduler/queue-entrypoint aangetroffen voor deze service; routes/console.php bevat alleen inspire. Niet bewezen dat live sourcequeries nog correct zijn. Geen sync uitgevoerd: kan kosten/publicatie veroorzaken.

Geen ZschotCMS-client, configuratie of API-contract aangetroffen. Bestaande eigen CMS is geen bewijs van ZschotCMS-integratie. Benodigd: echte API-documentatie, authenticatie, bron-/publicatieschema, webhook/signature/retrycontract en sandbox. Geen interface raden.

Doel: ingest -> raw immutable bronhash -> normalisatie -> canonical-keydeduplicatie -> queue -> LLM schema -> claim/broncontrole -> review -> CMS -> publicatie. Bewaar model/prompt/schema-versie, latency, kosten, inputreferenties en reviewactor. Onbetrouwbare broninhoud niet als instructies uitvoeren; geen tools/URL-fetches door model laten autoriseren. Egress-allowlist/SSRF-controle vóór downloads. Geen echte partijstemmen afleiden uit AI. Publicatie alleen bij vier verschillende onderbouwde argumenten per kant, context, bron, brondatum, verificatiedatum en geldige herkomst.

M1 haalt LLM-side effects uit publieke GET-routes en beperkt expliciete generatie tot beheerders. De volledige asynchrone pipeline en publicatiegate blijven M3; oude incomplete content niet automatisch verwijderen/publiceren.
