<template>
  <div class="page">
    <section class="page-hero page-hero--compass">
      <div class="container narrow">
        <h1 class="page-hero__title">
          <span class="page-hero__accent page-hero__accent--blue">Politiek</span>
          <span class="page-hero__accent page-hero__accent--red">Kompas</span>
        </h1>
        <p class="page-hero__subtitle">Deze pagina is nog in ontwikkeling.</p>
      </div>
    </section>

    <section class="container narrow page-section">
      <h2>Wat kun je hier verwachten?</h2>
      <p>
        Politiek Kompas houdt je keuzes bij en toont ze in een duidelijk
        overzicht. Door je antwoorden op verschillende stellingen inzichtelijk
        te maken, helpen we je om jouw positie in het politieke landschap te
        begrijpen.
      </p>
      <p>
        Met jouw mening over bepaalde stellingen kunnen we een politiek kompas
        genereren en de partij vinden die het beste bij je past.
      </p>
      <p>
        Dit is anders dan een reguliere stemwijzer, omdat het meebeweegt met
        jouw mening en continu wordt bijgewerkt.
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

.page-hero--compass {
  background-image: linear-gradient(
      135deg,
      rgba(0, 61, 165, 0.12),
      rgba(200, 16, 46, 0.2)
    ),
    url("/images/max-van-den-oetelaar-rvDMd5LvJRA-unsplash.jpg");
}


</style>
