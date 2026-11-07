<script setup lang="ts">
definePageMeta({ alias: "/voorwaarden" });
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

<style scoped></style>
