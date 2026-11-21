<script setup lang="ts">
useSeoMeta({ title: "Hoe werken de uitslagen? | WatDenktNederland" });
</script>
<template>
  <article class="info-layout">
    <header>
      <p class="eyebrow">Onze werkwijze</p>
      <h1>Een mening in perspectief.</h1>
      <p>
        We maken onderscheid tussen wat deelnemers vinden, officiële politieke
        besluiten en onze uitleg daarbij.
      </p>
    </header>
    <div class="info-body">
      <h2>De mening van deelnemers</h2>
      <p>
        Iedere gebruiker kan één keer op een stelling stemmen. Na je stem zie je
        hoeveel deelnemers voor of tegen hebben gekozen. Dit is een uitslag
        onder deelnemers aan WDNL, geen representatieve peiling van heel
        Nederland.
      </p>
      <h2>Eerst je mening, dan de uitslag</h2>
      <p>
        We tonen de percentages nadat je zelf hebt gestemd. Zo kun je eerst je
        eigen keuze maken. Je bepaalt daarna zelf wanneer je doorgaat naar de
        volgende stelling.
      </p>
      <h2>Neutraal of geen voorkeur</h2>
      <p>
        Je kunt tegen, voor of neutraal stemmen. Neutraal betekent dat je geen
        voorkeur aangeeft of dat het onderwerp je niet uitmaakt. Deze keuze telt
        mee in het totaal en krijgt een eigen percentage. Bij
        partijvergelijkingen gebruiken we neutraal niet als keuze vóór of tegen.
        Bestaande neutrale stemmen blijven gewoon bewaard.
      </p>
      <h2>Bronnen en argumenten</h2>
      <p>
        Stellingen kunnen ontstaan uit officiële moties, actuele gebeurtenissen
        of eigen maatschappelijke vragen van de WDNL-redactie. Bij iedere
        stelling tonen we de vastgelegde herkomst en beschikbare bronverwijzing.
        Een vereenvoudigde stelling is onze formulering. Controleer voor de
        volledige context ook de oorspronkelijke bron.
      </p>
      <h2>Nieuws als aanleiding</h2>
      <p>
        Een nieuwsbericht kan aanleiding zijn voor een maatschappelijke vraag.
        WDNL formuleert de stelling en argumenten zelf en verwijst naar de
        oorspronkelijke nieuwsbron. Een bronverwijzing betekent geen
        samenwerking of goedkeuring door die redactie. We nemen niet automatisch
        volledige nieuwsartikelen of foto's over.
      </p>
      <h2>Redactie en hulpmiddelen</h2>
      <p>
        De redactie beheert stellingen en controleert de beschikbare bronnen.
        Bij het begrijpelijk formuleren kan AI als hulpmiddel worden gebruikt.
        AI-tekst is geen officiële uitspraak of bewijs van een partijstandpunt;
        officiële partijstemmen moeten afzonderlijk naar een motie herleidbaar
        zijn.
      </p>
      <h2>Partijen en het politieke kompas</h2>
      <p>
        Partijverwijzingen bij oudere stellingen worden nog gecontroleerd op
        herkomst. Nieuwe partijovereenkomsten gebruiken alleen gecontroleerde
        motiekoppelingen. Het politieke kompas gebruikt vaste, redactioneel
        beheerde gewichten. De politieke types zijn een visuele samenvatting van
        je antwoorden, geen wetenschappelijk gevalideerde persoonlijkheidstest.
      </p>
      <h2>Een fout gevonden?</h2>
      <p>
        Je kunt een onjuistheid melden bij een stelling of
        <NuxtLink to="/contact">contact met ons opnemen</NuxtLink>. Kritisch
        blijven hoort erbij.
      </p>
      <h2>Themascores en politieke types</h2>
      <p>
        Per stelling legt de redactie een richting en gewicht vast op
        sociaal/individueel, progressief/behoudend, internationaal/nationaal,
        groen/economische groei en een afzonderlijke economische
        links/rechts-as. Deze weging is een inhoudelijke interpretatie van de
        vraagtekst, geen automatisch bewijs voor een partijstandpunt. Je kunt de
        onderbouwing in de gebruikte content terugvinden.
      </p>
      <p>
        Voor veel stellingen stelt een taalmodel eerst een weging met korte
        onderbouwing voor. Zo'n voorstel telt pas mee nadat de redactie het
        heeft gecontroleerd en goedgekeurd of aangepast. Het model berekent
        nooit je score, type of partijovereenkomst; dat doet vaste code.
      </p>
      <p>
        Een weging beschrijft de tekst die je beantwoordt. Daarom telt je
        antwoord op een oudere stelling als je huidige mening en maakt de
        leeftijd van de motie je profiel niet voorlopig. Alleen bij
        partijvergelijkingen blijft de datum belangrijk: een oude partijstem is
        geen actueel partijstandpunt.
      </p>
      <p>
        In het spel tonen we eerst stellingen over thema's waarop je nog weinig
        antwoorden hebt. Zo krijgt elk thema sneller genoeg onderbouwing. De
        inhoud van de stellingen verandert daar niet door.
      </p>
      <p>
        Voor telt als plus één, tegen als min één; een positief of negatief
        redactioneel gewicht bepaalt de richting. Eerst berekenen we vijftig
        maal één plus de gewogen gemiddelde richting. Neutraal blijft in de
        antwoordtelling, zonder richting. Dubbelen bij dezelfde vraag of motie
        tellen één keer. Bij tegenstrijdige antwoorden op dubbele versies
        sluiten we die vraag uit.
      </p>
      <p>
        Vanaf kompasversie 4.1 zwakken we die richting af bij weinig gegevens.
        De getoonde score is 50 + (oorspronkelijke score − 50) × n / (n + 6).
        Hierbij is n het aantal verschillende bruikbare, niet-neutrale
        antwoorden op dat thema; een zwaardere weging levert geen extra
        antwoorden op. Twee gelijkgerichte antwoorden geven zo 63/37 in plaats
        van 100/0; tien geven 81/19. Meer onderbouwing maakt een sterkere
        richting mogelijk. We ronden symmetrisch af en reserveren 0 en 100 voor
        absolute zekerheid, die dit kompas niet kan bieden. De getoonde scores
        liggen daarom tussen 1 en 99. Deze afzwakking met zes neutrale
        ankerantwoorden is een transparante productkeuze, geen wetenschappelijk
        gekalibreerde kans of betrouwbaarheidsmaat. Er worden geen stemmen
        toegevoegd. Ontbrekende gegevens blijven ontbreken; een voorzichtige
        score bewijst geen voorkeur voor het politieke midden. Bestaande
        rapporten behouden hun methode.
      </p>
      <p>
        Vanaf twee verschillende bruikbare antwoorden per thema tonen we een
        voorlopige richtingsscore. Een vollediger profiel vraagt minimaal vijf
        antwoorden per thema en tien gewogen vragen in totaal.
      </p>
      <p>
        Naast elke themascore staat een bandbreedte. Die laat zien hoe ver de
        score verschuift als we telkens één van je antwoorden weglaten. Een
        smalle band betekent dat je score op veel antwoorden steunt; een brede
        band dat enkele antwoorden de doorslag geven. Het is een eerlijke
        indicatie van gevoeligheid, geen statistische foutmarge.
      </p>
      <p>
        Een voorlopig politiek type gebruikt minimaal acht gewogen vragen en
        minstens twee beschikbare thema’s. De code kiest het dichtstbijzijnde
        vaste presentatieprofiel op uitsluitend de beschikbare dimensies. We
        zwakken de profielpunten op dezelfde manier af en beoordelen de
        richtingseisen op je oorspronkelijke keuzes. Zo wegen weinig onderbouwde
        thema’s minder zwaar zonder een middenvoorkeur te verzinnen. Ontbrekende
        scores worden niet ingevuld. Het type is geen psychologische diagnose of
        wetenschappelijk gevalideerde indeling. De economische as volgt uit
        eigen wegingen, nooit uit hoeveel keer je voor of tegen stemt.
      </p>
      <p>
        De gevoeligheidsrange laat zien hoe de score verandert wanneer één
        antwoord wordt weggelaten. Dit is geen statistisch
        betrouwbaarheidsinterval. De wegingen, minimumaantallen en
        presentatieprofielen zijn redactionele productkeuzes. De betaalde
        verkoop blijft gesloten totdat de volledige vereiste dekking én de
        overige releasevoorwaarden zijn bereikt.
      </p>
      <h2>Zestien politieke typen</h2>
      <p>
        Het type is de best aansluitende visuele samenvatting binnen zestien
        redactioneel beschreven profielen. We vergelijken beschikbare
        themascores met vaste profielpunten. Een type heeft ook voorwaarden: een
        Vernieuwer vereist bijvoorbeeld een onderbouwde progressieve richting.
        Een ontbrekend thema wordt niet als een voorkeur ingevuld. De namen zijn
        geen psychologische diagnose. Het wiel toont de typen en licht jouw
        indicatie uit; het is geen grafiek van kansen of typepercentages.
      </p>
      <h2>Een persoonlijk kompas op één A4</h2>
      <p>
        Het rapport is één downloadbare A4 met een typewiel, politieke waarden,
        economische as, partijvergelijking en vier persoonlijke observaties. Het
        is een vast overzicht van je vastgelegde antwoorden op de
        generatiedatum. Code telt de antwoorden, maakt de grafieken en vult de
        opmaak van het downloadbare PDF-rapport. Een inhoudelijke AI-toelichting
        is optioneel en verbindt concrete antwoorden aan thema’s en afwegingen.
        De toelichting verwijst naar de gebruikte antwoorden en verandert geen
        scores. De verhouding tussen voor en tegen zegt op zichzelf niets over
        links of rechts.
      </p>
      <p>
        Voor de AI-toelichting worden maximaal veertig unieke, eenduidige
        antwoorden met stellingtekst en beschikbare context gebruikt. Bij meer
        antwoorden wordt een vaste spreiding door de volledige antwoordlijst
        geselecteerd; het rapport vermeldt hoeveel antwoorden zijn gebruikt.
        Dubbelen tellen eenmaal. Verschillende antwoorden op dezelfde vraag
        worden niet als een eenduidig standpunt uitgelegd. De AI kent je redenen
        niet en kan een antwoord verkeerd duiden; lees de bijbehorende stelling
        terug. Neutraal geeft geen richting aan.
      </p>
      <p>
        Een eerste partijvergelijking verschijnt vanaf twee verschillende,
        gecontroleerde officiële moties per partij, met het aantal
        vergelijkingen en het label voorlopig. Vanaf tien moties wordt de
        vergelijking vollediger. Historische partijstemmen zijn geen actuele
        partijstandpunten. Ontbrekende, afwezige en neutrale partijstemmen
        worden niet ingevuld. Een persoonlijkheidslabel of politieke aspositie
        wordt niet zonder een gecontroleerde berekeningsmethode toegekend.
      </p>
      <p>
        De partijrangschikking gebruikt vanaf versie 4.1 een matchscore op 100,
        met dezelfde afzwakking naar 50 bij weinig vergelijkbare moties. Vier
        van vier gelijke stemmen leveren bijvoorbeeld een matchscore van 70 op.
        Die score is een voorzichtige indicator, niet het letterlijke percentage
        gelijke stemmen. Het werkelijke aantal, zoals vier van vier, blijft
        zichtbaar. Bij gelijke scores krijgt de vergelijking met meer moties
        voorrang. Er worden geen partijstandpunten bij bedacht.
      </p>
      <p>
        Een updatevoorstel benoemt uitsluitend aantoonbare nieuwe antwoorden
        sinds je vorige rapport. Andere onderwerpen of meer antwoorden bewijzen
        geen politieke verschuiving. Een nieuw rapport kost opnieuw €1,99;
        eerder gekochte rapporten blijven beschikbaar om te bekijken en
        downloaden.
      </p>
    </div>
  </article>
</template>
