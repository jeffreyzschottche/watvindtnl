<template>
  <div class="page">
    <section class="page-hero">
      <div class="container narrow">
        <h1>Over WDNL</h1>
        <p>
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
  background-image: url("/images/bicycles-street-amsterdam.jpg");
  background-size: cover;
  background-position: center; /* focus midden */
  background-repeat: no-repeat;

  height: min(50vh, 500px); /* max 500px hoog */
  display: grid; /* i.p.v. flex, verstoort container niet */
  place-items: center; /* center inhoud verticaal/horizontaal */

  color: #fff;
  position: relative;
  isolation: isolate;
  padding: 0; /* hoogte uit height */
}

/* Donkerdere top/bottom gradient */
.page-hero::before {
  content: "";
  position: absolute;
  inset: 0;
  background: linear-gradient(
    to bottom,
    rgba(0, 0, 0, 0.6) 0%,
    rgba(0, 0, 0, 0.12) 35%,
    rgba(0, 0, 0, 0.12) 65%,
    rgba(0, 0, 0, 0.6) 100%
  );
  z-index: -1;
}

/* Vignette richting randen (meer focus midden) */
.page-hero::after {
  content: "";
  position: absolute;
  inset: 0;
  background: radial-gradient(
    circle at center,
    rgba(0, 0, 0, 0) 0%,
    rgba(0, 0, 0, 0.12) 55%,
    rgba(0, 0, 0, 0.32) 100%
  );
  z-index: -1;
}

/* BELANGRIJK: laat je eigen container-styling intact */
.page-hero .container {
  position: relative; /* boven overlays */
  z-index: 1;
  /* GEEN width:100% hier! Dan blijft .container.narrow gewoon werken */
  padding-inline: 1rem; /* klein beetje lucht aan de randen */
}

.page-hero h1 {
  margin: 0 0 0.5rem;
}
.page-hero p {
  max-width: 60ch;
}

@media (max-width: 768px) {
  .page-hero {
    height: min(60vh, 500px);
  }
}
</style>
