<script setup lang="ts">
const highlights = [
  {
    title: "Burgerparticipatie centraal",
    description:
      "WDNL maakt het eenvoudig om meningen te verzamelen en gedeeld inzicht zichtbaar te maken voor iedere gemeente.",
  },
  {
    title: "Inzicht in real-time",
    description:
      "Bekijk direct hoe bewoners denken over actuele thema's en vertaal de resultaten naar actiegerichte besluiten.",
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
          <p class="lead">
            WDNL helpt organisaties en overheden om snel inzichten te krijgen in
            wat er leeft. Verzamel meningen, deel inzichten en werk aan besluiten
            waar iedereen achter staat.
          </p>
          <div class="hero-actions">
            <NuxtLink to="/register" class="button">Start vandaag</NuxtLink>
            <NuxtLink to="/over-wvnl" class="button button-secondary"
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
            <p>{{ item.description }}</p>
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
        <NuxtLink to="/contact" class="button button--accent"
          >Plan een gesprek</NuxtLink
        >
      </div>
    </section>
  </div>
</template>
