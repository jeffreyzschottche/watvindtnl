<template>
  <div class="page">
    <section class="page-hero page-hero--privacy">
      <div class="container narrow">
        <h1 class="page-hero__title">
          <span class="page-hero__accent page-hero__accent--blue">Privacy</span>
          <span class="page-hero__accent page-hero__accent--red"
            >verklaring</span
          >
        </h1>
        <p class="page-hero__subtitle">
          WDNL hecht veel waarde aan jouw privacy. We verwerken persoonsgegevens
          zorgvuldig en in overeenstemming met de Algemene Verordening
          Gegevensbescherming (AVG) en de Nederlandse Uitvoeringswet AVG (UAVG).
        </p>
      </div>
    </section>

    <section class="container narrow page-section">
      <h2>Welke gegevens verzamelen we?</h2>
      <p>
        WDNL verwerkt uitsluitend gegevens die nodig zijn voor het aanbieden,
        verbeteren en beveiligen van ons platform. We verzamelen de volgende
        gegevens:
      </p>
      <ul class="list">
        <li>
          Naam en contactgegevens (zoals e-mailadres) voor communicatie en
          inlog.
        </li>
        <li>
          Accountinformatie, zoals gebruikersnaam en wachtwoord (versleuteld
          opgeslagen).
        </li>
        <li>Antwoorden op vragen, polls of enquêtes binnen het platform.</li>
        <li>
          Technische gegevens, zoals IP-adres, browserinformatie en sessiedata,
          uitsluitend voor beveiliging en prestatieanalyse.
        </li>
      </ul>
    </section>

    <section class="section-light">
      <div class="container narrow page-section">
        <h2>Doeleinden van de verwerking</h2>
        <p>We gebruiken je gegevens uitsluitend voor:</p>
        <ul class="list">
          <li>Het aanmaken en beheren van gebruikersaccounts.</li>
          <li>Het uitvoeren van de dienstverlening van WDNL.</li>
          <li>Communicatie over je account of gebruik van het platform.</li>
          <li>
            Het verbeteren van functionaliteit, veiligheid en
            gebruikerservaring.
          </li>
          <li>
            Voldoen aan wettelijke verplichtingen (zoals administratie- of
            beveiligingseisen).
          </li>
        </ul>
      </div>
    </section>

    <section class="container narrow page-section">
      <h2>Rechtsgrond voor verwerking</h2>
      <p>
        We verwerken persoonsgegevens alleen op basis van één of meer van de
        volgende grondslagen:
      </p>
      <ul class="list">
        <li>Toestemming van de gebruiker.</li>
        <li>
          Noodzakelijk voor de uitvoering van een overeenkomst (zoals een
          accountregistratie).
        </li>
        <li>
          Wettelijke verplichting of gerechtvaardigd belang (zoals beveiliging
          en misbruikpreventie).
        </li>
      </ul>
    </section>

    <section class="section-light">
      <div class="container narrow page-section">
        <h2>Hoe beveiligen we je gegevens?</h2>
        <p>
          Gegevens worden opgeslagen op beveiligde servers binnen de Europese
          Unie. We gebruiken versleuteling, toegangscontrole, firewalls en
          monitoring om misbruik te voorkomen. Regelmatige audits en updates
          helpen om onze beveiliging actueel te houden.
        </p>
      </div>
    </section>

    <section class="container narrow page-section">
      <h2>Bewaartermijn</h2>
      <p>
        We bewaren persoonsgegevens niet langer dan noodzakelijk. Account- en
        contactgegevens worden verwijderd zodra je account wordt beëindigd of
        zodra het doel van verwerking vervalt, tenzij een wettelijke plicht een
        langere bewaartermijn vereist.
      </p>
    </section>

    <section class="section-light">
      <div class="container narrow page-section">
        <h2>Jouw rechten</h2>
        <p>
          Je hebt recht op inzage, correctie, verwijdering, overdraagbaarheid en
          beperking van verwerking van je persoonsgegevens. Ook kun je bezwaar
          maken tegen verwerking. Neem hiervoor contact met ons op via:
        </p>
        <p><strong>E-mail:</strong> privacy@wdnl.nl</p>
        <p>
          We reageren binnen 30 dagen op ieder verzoek. Indien gewenst kun je
          een klacht indienen bij de Autoriteit Persoonsgegevens.
        </p>
      </div>
    </section>

    <section class="container narrow page-section">
      <h2>Wijzigingen in deze verklaring</h2>
      <p>
        WDNL behoudt zich het recht voor deze privacyverklaring aan te passen.
        De laatste versie is altijd te vinden op deze pagina. Laatste update:
        <strong>oktober 2025</strong>.
      </p>
    </section>
  </div>
</template>

<style scoped>
.page-hero {
  background-size: cover;
  background-position: center;
  background-repeat: no-repeat;
  color: #fff;
  display: grid;
  place-items: center;
  text-align: center;
  min-height: min(48vh, 420px);
  position: relative;
  isolation: isolate;
  padding: 0;
}

.page-hero::before {
  content: "";
  position: absolute;
  inset: 0;
  background: linear-gradient(
    to bottom,
    rgba(0, 0, 0, 0.65) 0%,
    rgba(0, 0, 0, 0.25) 45%,
    rgba(0, 0, 0, 0.55) 100%
  );
  z-index: -2;
}

.page-hero::after {
  content: "";
  position: absolute;
  inset: 0;
  background: radial-gradient(
    circle at center,
    rgba(0, 0, 0, 0) 0%,
    rgba(0, 0, 0, 0.35) 100%
  );
  z-index: -1;
}

.page-hero .container {
  position: relative;
  z-index: 1;
  padding-inline: 1.25rem;
}

.page-hero__title {
  margin: 0;
  font-size: clamp(2.4rem, 4vw, 3.2rem);
  display: flex;
  gap: 0.65rem;
  justify-content: center;
  flex-wrap: wrap;
}

.page-hero__accent {
  display: inline-block;
  padding: 0.1rem 0.35rem;
  border-radius: 10px;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.page-hero__accent--blue {
  background: rgba(0, 61, 165, 0.92);
}

.page-hero__accent--red {
  background: rgba(200, 16, 46, 0.92);
}

.page-hero__subtitle {
  margin: 0.75rem auto 0;
  max-width: 60ch;
  font-size: 1.1rem;
  font-weight: 500;
}

.page-hero--privacy {
  background-image: linear-gradient(
      135deg,
      rgba(0, 61, 165, 0.18),
      rgba(200, 16, 46, 0.22)
    ),
    url("/images/martijn-baudoin-C4rDHqqmu4w-unsplash.jpg");
}
</style>
