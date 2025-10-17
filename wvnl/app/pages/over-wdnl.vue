<script setup lang="ts">
const siteName = "Wat Denkt Nederland"
const title = "Over Wat Denkt Nederland | Burgerparticipatie platform"
const description =
  "Lees hoe Wat Denkt Nederland burgers helpt om hun mening te geven over actuele moties en het politieke debat toegankelijk maakt."
const url = "https://watvindtnl.nl/over-wdnl"

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
}

useHead({
  title,
  meta: [
    { name: "description", content: description },
    { property: "og:title", content: title },
    { property: "og:description", content: description },
    { property: "og:type", content: "website" },
    { property: "og:locale", content: "nl_NL" },
    { name: "twitter:card", content: "summary_large_image" },
    { name: "twitter:title", content: title },
    { name: "twitter:description", content: description },
  ],
  script: [
    {
      id: "ld-json-over-wdnl",
      type: "application/ld+json",
      innerHTML: JSON.stringify(jsonLd),
    },
  ],
  __dangerouslyDisableSanitizersByTagID: {
    "ld-json-over-wdnl": ["innerHTML"],
  },
})
</script>

<template>
  <div class="page">
    <section class="page-hero page-hero--about">
      <div class="container narrow">
        <h1 class="page-hero__title">
          <span class="page-hero__accent page-hero__accent--blue">Over</span>
          <span class="page-hero__accent page-hero__accent--red">WDNL</span>
        </h1>
        <p class="page-hero__subtitle">
          WDNL is een platform voor burgerparticipatie. Wij proberen de inwoners
          van Nederland weer een stem te geven.
        </p>
      </div>
    </section>

    <section class="container narrow page-section">
      <h2>Hoe werkt het?</h2>
      <p>
        WDNL geeft gebruikers de optie om je mening te geven over huidige
        actuele moties in de tweede kamer. Via het politiek kompas proberen wij
        de ideale politieke partij te koppelen aan jouw stemgeschiedenis. Alle
        moties zijn voorzien van voor-tegen argumenten om je het onderwerp beter
        te laten begrijpen.
      </p>
      <ul class="list">
        <li>Geef je mening.</li>
        <li>Zie wat andere Nederlanders denken.</li>
        <li>Leer politieke onderwerpen beter begrijpen.</li>
      </ul>
    </section>

    <section class="section-light">
      <div class="container narrow page-section">
        <h2>Wat doen we nog meer?</h2>
        <p>
          Heb je een vraag of wil je zelf een motie indienen om te zien wat
          Nederland over een onderwerp denkt? Dien dit in via de
          <a href="/contact">contactpagina</a>. Zie je informatie die niet
          klopt? Meld het via de rapporteer knop naast het argument of de motie.
          Alle moties komen rechtstreeks van
          <a href="https://www.tweedekamer.nl/kamerstukken/moties"
            >https://www.tweedekamer.nl/kamerstukken/moties</a
          >
        </p>
        <ul class="list">
          <li>Zie wat andere Nederlanders denken.</li>
          <li>Echte moties uit de tweede kamer.</li>
          <li>Elke motie is voorzien van voor-tegen argumenten.</li>
        </ul>
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
    rgba(0, 0, 0, 0.6) 0%,
    rgba(0, 0, 0, 0.2) 45%,
    rgba(0, 0, 0, 0.5) 100%
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

.page-hero--about {
  background-image: linear-gradient(
      135deg,
      rgba(0, 61, 165, 0.12),
      rgba(200, 16, 46, 0.2)
    ),
    url("/images/bicycles-street-amsterdam.jpg");
}
</style>
