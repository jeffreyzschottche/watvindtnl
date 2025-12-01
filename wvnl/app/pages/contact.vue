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
      <form class="form form-card contact-form" @submit="onSubmit" novalidate>
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

        <!-- Honeypot (verborgen, maar in DOM aanwezig) -->
        <label class="hp" aria-hidden="true">
          <span>Laat dit veld leeg</span>
          <input
            v-model="hp"
            type="text"
            name="company"
            tabindex="-1"
            autocomplete="off"
          />
        </label>

        <!-- Simpele 'ik ben geen robot' check -->
        <label class="robot-check">
          <input v-model="human" type="checkbox" />
          <span>Ik ben geen robot</span>
        </label>

        <div class="form-actions">
          <button type="submit" class="button" :disabled="disableSubmit">
            {{ sending ? "Versturen…" : "Verstuur bericht" }}
          </button>
        </div>

        <p v-if="sent">Bedankt! We hebben je bericht ontvangen.</p>
        <p v-if="error" class="error">{{ error }}</p>
      </form>
    </section>
  </div>
</template>

<script setup lang="ts">
const siteName = "Wat Denkt Nederland";
const title = "Contact opnemen | Wat Denkt Nederland";
const description =
  "Neem contact op met het team van Wat Denkt Nederland voor vragen, ideeën of het indienen van nieuwe moties via het contactformulier.";
const url = "https://watvindtnl.nl/contact";

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

const sending = ref(false);
const sent = ref(false);
const error = ref<string | null>(null);

// Front-end bot shields
const startedAt = ref<number>(Date.now());
const minDelayMs = 2500;
const hp = ref("");
const human = ref(false);

const disableSubmit = computed(() => {
  const tooSoon = Date.now() - startedAt.value < minDelayMs;
  return sending.value || !human.value || tooSoon;
});

const apiBase = useRuntimeConfig().public.apiBase;

async function onSubmit(e: Event) {
  e.preventDefault();
  error.value = null;
  sent.value = false;

  if (hp.value.trim() !== "") {
    error.value = "Ongeldige invoer.";
    return;
  }

  if (Date.now() - startedAt.value < minDelayMs) {
    error.value = "Even geduld en probeer nogmaals.";
    return;
  }

  if (!human.value) {
    error.value = "Bevestig dat je geen robot bent.";
    return;
  }

  const form = e.target as HTMLFormElement;
  const fd = new FormData(form);

  try {
    sending.value = true;
    await $fetch("/contact", {
      method: "POST",
      body: fd,
      baseURL: apiBase,
    });
    sent.value = true;
    form.reset();
    hp.value = "";
    human.value = false;
    startedAt.value = Date.now();
  } catch (err: any) {
    error.value = "Versturen mislukt. Probeer het opnieuw.";
  } finally {
    sending.value = false;
  }
}
</script>

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

.page-hero--contact {
  background-image: linear-gradient(
      135deg,
      rgba(0, 51, 102, 0.12),
      rgba(255, 80, 3, 0.2)
    ),
    url("/images/norali-nayla-eDvG3IO8-Io-unsplash.webp");
}

.contact-form {
  margin-top: 3rem;
}

.hp {
  position: absolute;
  height: 0;
  width: 0;
  overflow: hidden;
  opacity: 0;
}

.robot-check {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-top: 1rem;
  font-weight: 600;
}

.robot-check input[type="checkbox"] {
  width: 1.1rem;
  height: 1.1rem;
}

.form-actions {
  margin-top: 1.5rem;
}

.error {
  color: #ff5003;
  font-weight: 600;
  margin-top: 1rem;
}
</style>
