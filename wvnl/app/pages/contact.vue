<template>
  <div class="page">
    <section class="page-hero page-hero--contact">
      <div class="container narrow">
        <h1 class="page-hero__title">
          <span class="page-hero__accent page-hero__accent--blue">Contact</span>
          <span class="page-hero__accent page-hero__accent--red">opnemen</span>
        </h1>
        <p class="page-hero__subtitle">
          Wil je zelf een motie aanvragen? Heb je ideeën? Of wil je meer weten
          over WDNL? Laat het ons weten via onderstaand formulier.
        </p>
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

.page-hero--contact {
  background-image: linear-gradient(
      135deg,
      rgba(0, 61, 165, 0.12),
      rgba(200, 16, 46, 0.2)
    ),
    url("/images/norali-nayla-eDvG3IO8-Io-unsplash.jpg");
}
</style>
