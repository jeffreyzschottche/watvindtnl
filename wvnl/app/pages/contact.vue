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
      <form class="form form-card contact-form" @submit.prevent="handleSubmit">
        <header class="form-header">
          <h2>Neem contact op</h2>
          <p>We reageren zo snel mogelijk op je bericht.</p>
        </header>

        <label>
          <span>Naam</span>
          <input
            v-model="form.name"
            type="text"
            name="name"
            placeholder="Jouw naam"
            autocomplete="name"
            required
          />
        </label>

        <label>
          <span>E-mailadres</span>
          <input
            v-model="form.email"
            type="email"
            name="email"
            placeholder="naam@voorbeeld.nl"
            autocomplete="email"
            required
          />
        </label>

        <label>
          <span>Bericht</span>
          <textarea
            v-model="form.message"
            name="message"
            rows="5"
            placeholder="Waarmee kunnen we helpen?"
            required
          ></textarea>
        </label>

        <div class="form-field">
          <altcha-widget
            ref="altchaRef"
            class="altcha"
            theme="auto"
            @altcha-success="handleAltchaSuccess"
            @altcha-error="handleAltchaError"
            @altcha-reset="handleAltchaReset"
          ></altcha-widget>
        </div>

        <p
          v-if="status === 'success'"
          class="form-feedback form-feedback--success"
          role="status"
        >
          Bedankt voor je bericht! We nemen zo snel mogelijk contact met je op.
        </p>
        <p
          v-else-if="status === 'error'"
          class="form-feedback form-feedback--error"
          role="alert"
        >
          {{ errorMessage }}
        </p>

        <div class="form-actions">
          <button type="submit" class="button" :disabled="submitting">
            <span v-if="submitting" class="loader" aria-hidden="true"></span>
            <span>{{ submitting ? "Versturen..." : "Verstuur bericht" }}</span>
          </button>
        </div>
      </form>
    </section>
  </div>
</template>

<script setup lang="ts">
import { nextTick, onMounted, reactive, ref, watch } from "vue";
import { submitContactForm } from "~/services/contact";

type AltchaDetail = {
  token: string;
  challenge?: string;
  answer?: string;
  signature?: string;
};

const ALTCHA_SCRIPT_ID = "altcha-widget-script";

const form = reactive({
  name: "",
  email: "",
  message: "",
});

const status = ref<"idle" | "success" | "error">("idle");
const submitting = ref(false);
const errorMessage = ref("Onbekende fout. Probeer het later opnieuw.");
const altchaDetail = ref<AltchaDetail | null>(null);
const altchaRef = ref<HTMLElement | null>(null);
const isResetting = ref(false);

const resetForm = () => {
  isResetting.value = true;
  form.name = "";
  form.email = "";
  form.message = "";
  altchaDetail.value = null;
  status.value = "success";
  if (altchaRef.value && "reset" in altchaRef.value) {
    try {
      // @ts-expect-error - altcha webcomponent exposes reset()
      altchaRef.value.reset();
    } catch (error) {
      console.warn("Kon Altcha-widget niet resetten", error);
    }
  }
  nextTick(() => {
    isResetting.value = false;
  });
};

const handleAltchaSuccess = (event: CustomEvent<AltchaDetail>) => {
  altchaDetail.value = event.detail;
};

const handleAltchaError = () => {
  altchaDetail.value = null;
  status.value = "error";
  errorMessage.value =
    "Er ging iets mis bij het laden van de controle. Probeer het opnieuw.";
};

const handleAltchaReset = () => {
  altchaDetail.value = null;
  if (status.value === "error") {
    status.value = "idle";
  }
};

const handleSubmit = async () => {
  if (!altchaDetail.value?.token) {
    status.value = "error";
    errorMessage.value =
      "Bevestig dat je geen robot bent voordat je het formulier verzendt.";
    return;
  }

  submitting.value = true;
  status.value = "idle";
  try {
    await submitContactForm({
      ...form,
      altcha: altchaDetail.value,
    });
    resetForm();
  } catch (error) {
    status.value = "error";
    errorMessage.value =
      error instanceof Error
        ? error.message
        : "Er ging iets mis. Probeer het opnieuw.";
    if (altchaRef.value && "reset" in altchaRef.value) {
      try {
        // @ts-expect-error - altcha webcomponent exposes reset()
        altchaRef.value.reset();
      } catch (resetError) {
        console.warn("Kon Altcha-widget niet resetten na fout", resetError);
      }
    }
  } finally {
    submitting.value = false;
  }
};

onMounted(() => {
  if (typeof window === "undefined" || typeof customElements === "undefined") {
    return;
  }

  if (!customElements.get("altcha-widget")) {
    const existingScript = document.getElementById(ALTCHA_SCRIPT_ID);
    if (!existingScript) {
      const script = document.createElement("script");
      script.id = ALTCHA_SCRIPT_ID;
      script.type = "module";
      script.src =
        "https://cdn.jsdelivr.net/npm/altcha@latest/dist/altcha.min.js";
      script.addEventListener("error", () => {
        status.value = "error";
        errorMessage.value =
          "De veiligheidscontrole kon niet geladen worden. Vernieuw de pagina en probeer het opnieuw.";
      });
      document.head.appendChild(script);
    }
  }
});

watch(
  () => ({ ...form }),
  () => {
    if (isResetting.value) return;
    if (status.value !== "idle") {
      status.value = "idle";
    }
  }
);
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

.form-card {
  display: grid;
  gap: 1.5rem;
}

.form-field {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.form-feedback {
  margin: -0.5rem 0 0;
  font-weight: 600;
}

.form-feedback--success {
  color: var(--color-success, #237804);
}

.form-feedback--error {
  color: var(--color-error, #d0021b);
}

.form-actions .button {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
}

.button[disabled] {
  opacity: 0.7;
  cursor: not-allowed;
}

.loader {
  width: 1.1rem;
  height: 1.1rem;
  border-radius: 999px;
  border: 2px solid currentColor;
  border-top-color: transparent;
  animation: spin 0.75s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}
</style>
