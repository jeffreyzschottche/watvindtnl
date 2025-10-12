<template>
  <div class="page">
    <section class="page-hero">
      <div class="container narrow">
        <h1>Politiek Kompas</h1>
        <p>Deze pagina is nog onder ontwikkeling.</p>
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
  background-image: url("/images/Warm Elegance in Bloom_ A Rhythmic Tapestry of Red and White Tulips.png");
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
