<script setup lang="ts">
const highlights = [
  {
    title: "Burgerparticipatie centraal",
    description:
      "WDNL maakt het eenvoudig om meningen te verzamelen en gedeeld inzicht zichtbaar te maken voor iedere gemeente.",
  },
  {
    title: "",
    // Toon een afbeelding i.p.v. tekst
    image: "/images/map-netherlands-polygonal-mesh-line-map-flag-map.jpg",
  },
  {
    title: "Veilig en transparant",
    description:
      "We verwerken gegevens met zorg en volgens de hoogste beveiligingsstandaarden zodat iedereen kan vertrouwen op het proces.",
  },
];

const steps = [
  {
    title: "1. Verzamel",
    description:
      "Start een vraag of enquête en nodig inwoners uit om hun mening te delen in een paar eenvoudige stappen.",
  },
  {
    title: "2. Analyseer",
    description:
      "Krijg onmiddellijk inzicht met duidelijke dashboards en vergelijk resultaten tussen wijken en doelgroepen.",
  },
  {
    title: "3. Handel",
    description:
      "Gebruik de inzichten om beleid vorm te geven, acties te plannen en draagvlak te laten groeien.",
  },
];
</script>

<template>
  <div class="landing">
    <section class="hero">
      <div class="container hero-content">
        <div>
          <p class="eyebrow">Burgerparticipatie platform</p>
          <h1>Ontdek wat Nederland écht vindt</h1>
          <p class="lead-home">
            WDNL helpt organisaties en overheden om snel inzichten te krijgen in
            wat er leeft. Verzamel meningen, deel inzichten en werk aan
            besluiten waar iedereen achter staat.
          </p>
          <div class="hero-actions">
            <NuxtLink to="/register" class="button">Start vandaag</NuxtLink>
            <NuxtLink to="/over-wdnl" class="button button-secondary"
              >Lees meer</NuxtLink
            >
          </div>
        </div>
        <div class="hero-card">
          <h2>Waarom WDNL?</h2>
          <ul>
            <li>Gebruiksvriendelijke enquêtes en stellingen</li>
            <li>Realtime dashboards voor teams en bestuurders</li>
            <li>Veilig opgeslagen data binnen de EU</li>
          </ul>
        </div>
      </div>
    </section>

    <section class="section-light">
      <div class="container">
        <h2 class="section-title">Wat je kunt verwachten</h2>
        <div class="card-grid">
          <article v-for="item in highlights" :key="item.title" class="card">
            <h3>{{ item.title }}</h3>

            <!-- Als er een image is, toon die; anders de description -->
            <template v-if="item.image">
              <img
                :src="item.image"
                alt="Inzicht in real-time – kaart van Nederland"
                class="card-illustration"
              />
            </template>
            <template v-else>
              <p>{{ item.description }}</p>
            </template>
          </article>
        </div>
      </div>
    </section>

    <section>
      <div class="container steps">
        <h2 class="section-title">Zo werkt het</h2>
        <div class="step-list">
          <article v-for="step in steps" :key="step.title" class="step-card">
            <h3>{{ step.title }}</h3>
            <p>{{ step.description }}</p>
          </article>
        </div>
      </div>
    </section>

    <section class="section-light">
      <div class="container cta">
        <div>
          <h2>Klaar om in gesprek te gaan met jouw inwoners?</h2>
          <p>
            Maak direct een account aan of neem contact op met ons team. Samen
            bouwen we aan draagvlak en vertrouwen.
          </p>
        </div>
        <NuxtLink to="/contact" class="button">Plan een gesprek</NuxtLink>
      </div>
    </section>
  </div>
</template>

<style scoped>
/* HERO met achtergrond uit /public/images/... */
.hero {
  background-image: url("/images/alix-greenman-ac-g-HU243Y-unsplash.jpg");
  background-size: cover;
  background-position: center; /* focus op het midden */
  background-repeat: no-repeat;

  height: min(50vh, 500px); /* max 500px hoog, krimpt mee */
  display: flex; /* verticaal centreren zonder container te breken */
  align-items: center;

  color: #fff;
  position: relative;
  isolation: isolate; /* nodig voor overlays */
  padding: 0; /* hoogte komt uit height */
}

/* Top/Bottom gradient voor leesbaarheid */
.hero::before {
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

/* Vignette naar randen, meer focus op midden */
.hero::after {
  content: "";
  position: absolute;
  inset: 0;
  background: radial-gradient(
    circle at center,
    rgba(0, 0, 0, 0) 0%,
    rgba(0, 0, 0, 0.52) 105%,
    rgba(0, 0, 0, 0.62) 100%
  );
  z-index: -1;
}

/* Zorg dat je content boven de overlays staat, zonder breedte te forceren */
.hero .hero-content {
  position: relative;
  z-index: 1;
  gap: 2rem; /* optioneel: ruimte tussen tekst en kaart */
}

/* ---- Kaart-afbeelding in highlight-card ---- */
.card-illustration {
  width: 100%;
  height: auto;
  display: block;
  margin-top: 0.5rem;
  border-radius: 0.75rem;
  object-fit: cover;
  /* Als je vaste hoogte wilt, uncomment:
  max-height: 220px; 
  object-fit: cover;
  */
}

/* Kleine schermen: eventueel iets hoger kader */
@media (max-width: 768px) {
  .hero {
    height: min(60vh, 500px);
  }
}
</style>
