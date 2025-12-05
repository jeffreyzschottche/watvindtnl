<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from "vue";

const siteName = "Wat Denkt Nederland";
const title = "Politiek Kompas | Wat Denkt Nederland";
const description =
  "Ontdek hoe jouw antwoorden op politieke stellingen zich vertalen naar het Politiek Kompas en welke partijen bij je passen.";
const url = "https://watvindtnl.nl/politiek-kompas";

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

const politicalCompassMaintenanceMessage =
  "Het politiek kompas ondergaat momenteel onderhoud, we proberen dit zo snel mogelijk werkend te krijgen";

const isBoardFlipped = ref(false);
let flipInterval: ReturnType<typeof setInterval> | null = null;
let flipResetTimeout: ReturnType<typeof setTimeout> | null = null;

function triggerBoardFlip() {
  isBoardFlipped.value = true;

  if (flipResetTimeout) {
    clearTimeout(flipResetTimeout);
  }

  flipResetTimeout = setTimeout(() => {
    isBoardFlipped.value = false;
  }, 650);
}

onMounted(() => {
  triggerBoardFlip();
  flipInterval = setInterval(triggerBoardFlip, 4500);
});

onBeforeUnmount(() => {
  if (flipInterval) {
    clearInterval(flipInterval);
  }

  if (flipResetTimeout) {
    clearTimeout(flipResetTimeout);
  }
});
</script>

<template>
  <div class="page">
    <section class="page-hero page-hero--compass">
      <div class="container narrow">
        <h1 class="page-hero__title">
          <span class="page-hero__accent page-hero__accent--blue"
            >Politiek</span
          >
          <span class="page-hero__accent page-hero__accent--red">Kompas</span>
        </h1>
        <p class="page-hero__subtitle">
          Krijg meer inzichten op je eigen politieke voorkeur.
          <br />
          <i>Je vind het politieke kompas onder 'mijn profiel'</i>
        </p>
        <p class="page-hero__maintenance" role="status">
          {{ politicalCompassMaintenanceMessage }}
        </p>
      </div>
    </section>

    <section class="container narrow page-section compass-section">
      <div class="compass-layout">
        <div class="compass-content">
          <h2>Wat is het politieke kompas?</h2>
          <p>
            Politiek Kompas houdt je keuzes bij en toont ze in een duidelijk
            overzicht. Door je antwoorden op verschillende stellingen
            inzichtelijk te maken, helpen we je om jouw positie in het politieke
            landschap te begrijpen.
          </p>
          <p>
            Met jouw mening over bepaalde stellingen kunnen we een politiek
            kompas genereren en de partij vinden die het beste bij je past.
          </p>
          <p>
            Binnenkort koppel je jouw Politiek Kompas aan een
            premium-lidmaatschap zodat alleen betaalde leden toegang hebben tot
            hun persoonlijke inzichten.
          </p>
          <p>
            Dit is anders dan een reguliere stemwijzer, omdat het meebeweegt met
            jouw mening en continu wordt bijgewerkt. We koppelen verschillende
            belangrijke politieke onderwerpen aan verschillende partijen. De
            partij die het meeste voorkomt is jouw beste partij.
          </p>
          <i>{{ politicalCompassMaintenanceMessage }}</i>
        </div>

        <div class="compass-coming-soon" role="presentation">
          <div
            class="compass-board"
            :class="{ 'is-flipped': isBoardFlipped }"
            aria-hidden="true"
          >
            <span class="compass-board__badge">Coming soon</span>
            <strong class="compass-board__title">Politiek Kompas</strong>
            <p class="compass-board__message">
              {{ politicalCompassMaintenanceMessage }}
            </p>
          </div>
          <div class="compass-board__shadow" aria-hidden="true"></div>
        </div>
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

.page-hero__maintenance {
  margin: 1.25rem auto 0;
  max-width: 52ch;
  font-size: 1rem;
  font-weight: 600;
  color: #ffffff;
  background: rgba(0, 0, 0, 0.35);
  padding: 0.75rem 1.2rem;
  border-radius: 14px;
  border: 1px dashed rgba(255, 255, 255, 0.5);
}

.page-hero--compass {
  background-image: linear-gradient(
      135deg,
      rgba(0, 51, 102, 0.12),
      rgba(255, 80, 3, 0.2)
    ),
    url("/images/max-van-den-oetelaar-rvDMd5LvJRA-unsplash.webp");
}

.compass-section {
  padding: clamp(3rem, 5vw, 4.5rem);
}

.compass-layout {
  display: flex;
  flex-direction: column;
  gap: clamp(2rem, 4vw, 3rem);
  align-items: center;
  text-align: left;
}

.compass-content {
  display: grid;
  gap: 1.35rem;
  padding: 2rem;
  border-radius: 1.25rem;
  background: rgba(0, 35, 71, 0.04);
  border: 1px solid rgba(0, 35, 71, 0.1);
  box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.35);
  width: 100%;
}

.compass-content h2 {
  margin: 0;
  color: var(--color-midnight);
  font-size: 1.85rem;
}

.compass-content p {
  margin: 0;
  color: rgba(15, 23, 42, 0.82);
  line-height: 1.65;
  font-weight: 500;
}

.compass-content p + p {
  margin-top: 0.25rem;
}

.compass-content i {
  font-style: normal;
  font-weight: 600;
  color: var(--color-orange);
  background: rgba(255, 142, 0, 0.12);
  border-radius: 999px;
  padding: 0.35rem 0.85rem;
  width: fit-content;
}

.compass-coming-soon {
  position: relative;
  width: min(420px, 100%);
  margin: 0 auto;
  perspective: 1200px;
  display: grid;
  place-items: center;
}

.compass-board {
  width: 100%;
  padding: clamp(1.75rem, 4vw, 2.5rem);
  border-radius: 28px;
  background: linear-gradient(140deg, #003da5, #ff5003);
  color: #ffffff;
  text-align: center;
  display: grid;
  gap: 0.85rem;
  transform-style: preserve-3d;
  transform: rotateY(0deg) rotateX(0deg);
  transition: transform 0.6s ease, box-shadow 0.6s ease;
  box-shadow: 0 25px 60px rgba(0, 0, 0, 0.25);
  border: 1px solid rgba(255, 255, 255, 0.25);
}

.compass-board.is-flipped {
  transform: rotateY(-12deg) rotateX(6deg);
  box-shadow: 0 35px 80px rgba(0, 0, 0, 0.35);
}

.compass-board__badge {
  display: inline-flex;
  align-self: center;
  justify-self: center;
  text-transform: uppercase;
  font-size: 0.8rem;
  letter-spacing: 0.1em;
  padding: 0.3rem 0.9rem;
  border-radius: 999px;
  border: 1px solid rgba(255, 255, 255, 0.4);
  background: rgba(255, 255, 255, 0.12);
}

.compass-board__title {
  font-size: clamp(1.75rem, 3vw, 2.15rem);
  line-height: 1.2;
}

.compass-board__message {
  margin: 0;
  font-weight: 500;
  line-height: 1.5;
}

.compass-board__tag {
  font-size: 0.85rem;
  letter-spacing: 0.05em;
  text-transform: uppercase;
  color: rgba(255, 255, 255, 0.85);
}

.compass-board__shadow {
  width: 70%;
  height: 30px;
  border-radius: 999px;
  background: radial-gradient(
    circle,
    rgba(0, 0, 0, 0.35),
    rgba(0, 0, 0, 0.05) 65%,
    transparent
  );
  filter: blur(12px);
  margin-top: 1.25rem;
}

@media (max-width: 640px) {
  .compass-content {
    padding: 1.5rem;
  }
}
</style>
