<script setup lang="ts">
const highlights = [
  {
    title: "Burgerparticipatie centraal",
    description:
      "WDNL maakt het eenvoudig om je mening te delen over huidige politieke moties uit de tweede kamer. Zie wat de politieke partijen & mede-Nederlanders denken.",
  },
  {
    title: "",
    // Toon een afbeelding i.p.v. tekst
    image: "/images/map-netherlands-polygonal-mesh-line-map-flag-map.jpg",
  },
  {
    title: "Veilig en transparant",
    description:
      "Compleet anoniem. Andere deelnemers zien niet wat jij stemt. We verwerken gegevens met zorg en volgens de hoogste beveiligingsstandaarden zodat iedereen zijn mening vrij kan geven.",
  },
];

const steps = [
  {
    title: "1. Stem",
    description:
      "Alle stellingen zijn moties die in de tweede kamer behandeld worden. Stem, geef je mening over die kwesties en zie wat andere erover denken.",
  },
  {
    title: "2. Vraag moties aan",
    description:
      "Via het contactformulier is het mogelijk om je eigen motie aan te vragen. Wij zullen contact met je opnemen als we een besluit hebben genomen.",
  },
  {
    title: "3. Leer jezelf beter kennen",
    description:
      "We houden bij wat je stemt op welke kwesties via het politiek kompas, zo maken we een profiel van je beslissingen en kunnen zo de perfecte politieke partij aanraden.",
  },
];
</script>

<template>
  <div class="landing">
    <section class="hero">
      <div class="container hero-content">
        <div>
          <p class="eyebrow">Burgerparticipatie platform</p>
          <h1>Ontdek wat Nederland écht denkt</h1>
          <p class="lead-home">
            Geef je mening over politieke besluiten die momenteel in de tweede
            kamer behandeld worden. Leer jezelf en de voor-tegen argumenten
            beter begrijpen.
          </p>
          <div class="ctahome">
            <NuxtLink to="/register" class="button">Maak account</NuxtLink>
            <NuxtLink to="/over-wdnl" class="button button-secondary"
              >Over ons</NuxtLink
            >
          </div>
        </div>
        <div class="hero-card">
          <h2 style="margin-top: -0.5em">Waarom Wat Denkt Nederland?</h2>
          <ul>
            <li>Actuele politieke moties</li>
            <li>Zien hoeveel % Nederlanders het met je eens/oneens is</li>
            <li>Veilig opgeslagen data binnen de EU</li>
            <li>Helder geformuleerde vraagstellingen</li>
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
          <h2>Klaar om jouw mening te geven?</h2>
          <p>
            Maak direct een account aan en start met het beoordelen van moties.
          </p>
        </div>
        <NuxtLink to="/issues" class="button">Start</NuxtLink>
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

h2 {
  color: black;
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

/* Basis */
.ctahome .button {
  display: inline-flex;
  justify-content: center;
  align-items: center;
  background: #ffffff;
  color: var(--color-primary);
  border: 2px solid transparent;
  box-shadow: 0 18px 36px rgba(255, 255, 255, 0.35);
  margin-right: 1em;
  width: 7.5em;
  min-height: 2em;
  border-radius: 50px;
  font-weight: 700;
  text-decoration: none;
  transition: background 0.2s ease, color 0.2s ease, box-shadow 0.2s ease,
    border-color 0.2s ease, transform 0.05s ease;
}

/* 1e knop: ROOD */
.ctahome .button:first-child {
  background: rgba(200, 16, 46, 0.95);
  color: #fff;
  border-color: transparent;
  box-shadow: 0 18px 36px rgba(200, 16, 46, 0.35);
}
.ctahome .button:first-child:hover,
.ctahome .button:first-child:focus {
  background: #fff;
  color: rgba(200, 16, 46, 0.95);
  border-color: rgba(200, 16, 46, 0.95);
  box-shadow: 0 22px 40px rgba(200, 16, 46, 0.45);
}

/* 2e knop: BLAUW */
.ctahome .button:nth-child(2) {
  background: rgba(0, 61, 165, 0.92);
  color: #fff;
  border-color: transparent;
  box-shadow: 0 18px 36px rgba(0, 61, 165, 0.35);
}
.ctahome .button:nth-child(2):hover,
.ctahome .button:nth-child(2):focus {
  background: #fff;
  color: rgba(0, 61, 165, 0.92);
  border-color: rgba(0, 61, 165, 0.92);
  box-shadow: 0 22px 40px rgba(0, 61, 165, 0.45);
}

/* Interactie */
.ctahome .button:active {
  transform: translateY(1px);
}
.ctahome .button:focus-visible {
  outline: 3px solid currentColor;
  outline-offset: 2px;
}

/* Kleine schermen: eventueel iets hoger kader */
@media (max-width: 768px) {
  .hero {
    height: min(60vh, 500px);
  }
}
</style>
