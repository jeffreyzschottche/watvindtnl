<template>
  <div class="page">
    <section class="page-hero">
      <div class="container narrow">
        <h1>Contact</h1>
        <p>
          Wil je zelf een motie aanvragen? Heb je ideeën? Of wil je meer weten
          over WDNL?
        </p>
        <p>Laat het ons weten via onderstaand formulier.</p>
      </div>
    </section>

    <section class="container narrow">
      <form class="form form-card contact-form">
        <header class="form-header">
          <h2>Neem contact op</h2>
          <p>We reageren zo snel mogelijk op je bericht.</p>
        </header>

        <label>
          <span>Naam</span>
          <input type="text" name="name" placeholder="Jouw naam" required />
        </label>

        <label>
          <span>E-mailadres</span>
          <input
            type="email"
            name="email"
            placeholder="naam@voorbeeld.nl"
            required
          />
        </label>

        <label>
          <span>Bericht</span>
          <textarea
            name="message"
            rows="5"
            placeholder="Waarmee kunnen we helpen?"
            required
          ></textarea>
        </label>

        <div class="form-actions">
          <button type="submit" class="button">Verstuur bericht</button>
        </div>
      </form>
    </section>
  </div>
</template>
<style scoped>
.page-hero {
  background-image: url("/images/low-angle-shot-windmills-zaanse-schans-neighborhood-near-lake-netherlands.jpg");
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
