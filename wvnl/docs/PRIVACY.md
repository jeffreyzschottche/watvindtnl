# Privacy — technische voorbereiding, juridische beoordeling open

Stemrelaties en kompas kunnen politieke opvattingen onthullen en zijn mogelijk bijzondere persoonsgegevens. Er is nog geen onderbouwde verklaring van AVG-compliance. Vóór productie moet een bevoegde jurist grondslag onder artikel 6, uitzondering artikel 9, uitdrukkelijke toestemming, DPIA-plicht, verwerkersovereenkomsten, EER-doorgiften, leeftijdsbeleid en bewaartermijnen beoordelen. [Autoriteit Persoonsgegevens](https://www.autoriteitpersoonsgegevens.nl/themas/basis-avg/privacy-en-persoonsgegevens/bijzondere-persoonsgegevens).

M2: afzonderlijke niet-vooraangevinkte politieke toestemming, tekstversie/doel/tijdstip/withdrawal op backend; registratie mag geen marketingtoestemming afdwingen. Blokkeer verwerking na withdrawal, maak gevolgen duidelijk, bied herauthenticatie voor delete/export. Minimaal identificerend profiel; vraag geen demografische/politieke voorkeur zonder noodzakelijk doel.

Werkelijke flows vereist: machineleesbare export met korte privédownload, stemverwijdering, consentintrekking, accountverwijdering, tokenrevocation, report/export cleanup en providerverwijdering. Financiële bewaarplicht apart vastleggen zonder politieke keuze te bewaren. Na delete geaggregeerde counts herberekenen; herstel uit backup vereist deletion ledger. Geen individuele politieke meningen in publiek resultaat, caches, analytics of advertentiedoelgroepen. Een contactformulier is geen implementatie van deze rechten.

Datacategorieën: identiteit/contact, authenticatie, politieke stemmen, broncontent, rapporten, minimale transactie-ID's, consent/audit. Retentietabel en procedure pas publiceren na eigenaarsbesluit/juridische toets; nu geen verzonnen termijnen als feiten.
