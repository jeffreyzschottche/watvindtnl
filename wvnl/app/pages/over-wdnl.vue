<template>
  <div class="page">
    <section class="page-hero page-hero--about">
      <div class="container narrow">
        <h1 class="page-hero__title">
          <span class="page-hero__accent page-hero__accent--blue">Over</span>
          <span class="page-hero__accent page-hero__accent--red">WDNL</span>
        </h1>
        <p class="page-hero__subtitle">
          WDNL is een platform voor burgerparticipatie. Wij brengen inwoners,
          organisaties en overheden bij elkaar om meningen te verzamelen en
          beslissingen beter te onderbouwen.
        </p>
      </div>
    </section>

    <section class="container narrow page-section">
      <h2>Hoe werkt het?</h2>
      <p>
        Met WDNL creëer je binnen enkele minuten een vraag of enquête. Nodig je
        doelgroep uit, deel de resultaten en bespreek vervolgstappen met je
        team. Ons platform is ontworpen om helder inzicht te bieden en het
        besluitvormingsproces te versnellen.
      </p>
      <ul class="list">
        <li>Stel vragen en stellingen op maat samen.</li>
        <li>Verzamel antwoorden via web, mobiel en evenementen.</li>
        <li>Volg de respons live in overzichtelijke dashboards.</li>
      </ul>
    </section>

    <section class="section-light">
      <div class="container narrow page-section">
        <h2>Wat doen we?</h2>
        <p>
          We helpen organisaties om draagvlak te creëren voor belangrijke
          besluiten. Dit doen we door data te vertalen naar verhalen, workshops
          te faciliteren en begeleiding te bieden tijdens het hele traject.
        </p>
        <ul class="list">
          <li>Strategische begeleiding voor participatietrajecten.</li>
          <li>Workshops en trainingen voor teams en bestuurders.</li>
          <li>Rapportages en advies op basis van verzamelde inzichten.</li>
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
