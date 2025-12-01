<script setup lang="ts">
const siteName = "Wat Denkt Nederland";
const title = "Algemene voorwaarden | Wat Denkt Nederland";
const description =
  "Lees de algemene voorwaarden van Wat Denkt Nederland en ontdek welke regels gelden voor het gebruik van het platform.";
const url = "https://watvindtnl.nl/algemene-voorwaarden";

const jsonLd = {
  "@context": "https://schema.org",
  "@type": "WebPage",
  name: title,
  description,
  url,
  inLanguage: "nl-NL",
  isPartOf: {
    "@type": "WebSite",
    name: siteName,
    url: "https://watvindtnl.nl",
  },
};
</script>

<template>
  <div class="page">
    <section class="page-hero page-hero--terms">
      <div class="container narrow">
        <h1 class="page-hero__title">
          <span class="page-hero__accent page-hero__accent--blue"
            >Algemene</span
          >
          <span class="page-hero__accent page-hero__accent--red"
            >voorwaarden</span
          >
        </h1>
        <p class="page-hero__subtitle">
          Deze algemene voorwaarden zijn van toepassing op alle diensten en
          functionaliteiten die door WDNL worden aangeboden. Door gebruik te
          maken van ons platform, ga je akkoord met deze voorwaarden.
        </p>
      </div>
    </section>

    <section class="container narrow page-section">
      <h2>Definities</h2>
      <ul class="list">
        <li>
          <strong>WDNL:</strong> de exploitant van het platform, gevestigd in
          Nederland.
        </li>
        <li>
          <strong>Gebruiker:</strong> iedere natuurlijke of rechtspersoon die
          gebruikmaakt van het platform.
        </li>
        <li>
          <strong>Platform:</strong> de website of applicatie van WDNL waarop
          gebruikers informatie kunnen delen of raadplegen.
        </li>
      </ul>
    </section>

    <section class="section-light">
      <div class="container narrow page-section">
        <h2>Gebruik van het platform</h2>
        <p>
          WDNL stelt het platform beschikbaar voor burgers, organisaties en
          overheden die meningen, ideeën of feedback willen delen. Het gebruik
          van het platform is toegestaan zolang dit niet in strijd is met de
          wet, openbare orde of rechten van anderen.
        </p>
        <ul class="list">
          <li>
            De gebruiker is verantwoordelijk voor de inhoud die wordt geplaatst.
          </li>
          <li>
            Het is verboden om onjuiste, misleidende of discriminerende
            informatie te publiceren.
          </li>
          <li>
            Het is niet toegestaan het platform te gebruiken voor spam,
            commerciële doeleinden of verspreiding van virussen of schadelijke
            software.
          </li>
        </ul>
      </div>
    </section>

    <section class="container narrow page-section">
      <h2>Registratie en accountbeheer</h2>
      <p>
        Voor bepaalde onderdelen is registratie vereist. Gebruikers moeten
        correcte gegevens verstrekken en deze actueel houden. WDNL mag accounts
        blokkeren of verwijderen bij misbruik of overtreding van deze
        voorwaarden.
      </p>
    </section>

    <section class="section-light">
      <div class="container narrow page-section">
        <h2>Beschikbaarheid en onderhoud</h2>
        <p>
          WDNL streeft naar een hoge beschikbaarheid, maar kan geen absolute
          garantie bieden op ononderbroken toegang. Onderhoud en updates kunnen
          leiden tot tijdelijke onderbrekingen. WDNL is niet aansprakelijk voor
          schade als gevolg van storingen of downtime.
        </p>
      </div>
    </section>

    <section class="container narrow page-section">
      <h2>Intellectueel eigendom</h2>
      <p>
        Alle teksten, afbeeldingen, code en andere materialen op het platform
        zijn eigendom van WDNL of van de respectieve rechthebbenden. Het is niet
        toegestaan zonder schriftelijke toestemming van WDNL materialen te
        kopiëren, verspreiden of hergebruiken.
      </p>
    </section>

    <section class="section-light">
      <div class="container narrow page-section">
        <h2>Aansprakelijkheid</h2>
        <p>
          WDNL is niet aansprakelijk voor directe of indirecte schade die
          voortvloeit uit het gebruik van het platform. De totale
          aansprakelijkheid blijft beperkt tot het bedrag dat de gebruiker heeft
          betaald voor de betreffende dienst (indien van toepassing).
        </p>
      </div>
    </section>

    <section class="container narrow page-section">
      <h2>Wijzigingen in de voorwaarden</h2>
      <p>
        WDNL behoudt zich het recht voor deze voorwaarden te wijzigen. De meest
        recente versie is altijd beschikbaar op het platform. Bij ingrijpende
        wijzigingen wordt de gebruiker hiervan op de hoogte gesteld.
      </p>
    </section>

    <section class="section-light">
      <div class="container narrow page-section">
        <h2>Toepasselijk recht</h2>
        <p>
          Op deze voorwaarden is het Nederlands recht van toepassing. Geschillen
          worden voorgelegd aan de bevoegde rechter te Amsterdam, tenzij anders
          bepaald door dwingend recht.
        </p>
      </div>
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
  background: rgba(0, 51, 102, 0.92);
}

.page-hero__accent--red {
  background: rgba(255, 80, 3, 0.92);
}

.page-hero__subtitle {
  margin: 0.75rem auto 0;
  max-width: 60ch;
  font-size: 1.1rem;
  font-weight: 500;
}

.page-hero--terms {
  background-image: linear-gradient(
      135deg,
      rgba(0, 51, 102, 0.18),
      rgba(255, 80, 3, 0.22)
    ),
    url("/images/e-mens-yEpUny1hxoE-unsplash.webp");
}
</style>
