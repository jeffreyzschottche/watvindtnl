<template>
  <form class="form form-card auth-form" @submit.prevent="onSubmit">
    <header class="form-header">
      <h2>Registreren</h2>
      <p>Vul je gegevens in en maak je account compleet.</p>
    </header>

    <fieldset class="form-fieldset">
      <legend>Persoonlijke gegevens</legend>
      <label>
        <span>Naam</span>
        <input v-model="form.name" autocomplete="name" required />
      </label>

      <label>
        <span>Gebruikersnaam</span>
        <input v-model="form.username" autocomplete="nickname" required />
      </label>

      <label>
        <span>E-mailadres</span>
        <input v-model="form.email" type="email" autocomplete="email" required />
      </label>

      <label>
        <span>Wachtwoord</span>
        <input
          v-model="form.password"
          type="password"
          autocomplete="new-password"
          minlength="8"
          required
        />
      </label>

      <label>
        <span>Bevestig wachtwoord</span>
        <input
          v-model="confirmPassword"
          type="password"
          autocomplete="new-password"
          minlength="8"
          required
        />
      </label>
    </fieldset>

    <fieldset class="form-fieldset">
      <legend>Profielinformatie</legend>
      <label>
        <span>Leeftijdscategorie</span>
        <select v-model="form.age_category" required>
          <option disabled value="">Maak een keuze</option>
          <option v-for="age in ageCategories" :key="age" :value="age">
            {{ age }}
          </option>
        </select>
      </label>

      <label>
        <span>Provincie</span>
        <select v-model="form.province" required>
          <option disabled value="">Maak een keuze</option>
          <option v-for="p in provinces" :key="p" :value="p">{{ p }}</option>
        </select>
      </label>

      <label>
        <span>Geslacht</span>
        <select v-model="form.gender" required>
          <option value="unspecified">Zeg ik niet</option>
          <option value="male">Man</option>
          <option value="female">Vrouw</option>
        </select>
      </label>

      <label>
        <span>Onderwijsniveau</span>
        <select v-model="form.education_level" required>
          <option disabled value="">Maak een keuze</option>
          <option value="mbo">MBO</option>
          <option value="hbo">HBO</option>
          <option value="universiteit">Universiteit</option>
          <option value="overig">Overig</option>
        </select>
      </label>

      <label>
        <span>Politieke voorkeur</span>
        <select v-model="form.political_preference" required>
          <option disabled value="">Maak een keuze</option>
          <option value="links">Links</option>
          <option value="midden">Midden</option>
          <option value="rechts">Rechts</option>
          <option value="geen">Geen / Onbekend</option>
        </select>
      </label>
    </fieldset>

    <fieldset class="form-fieldset">
      <legend>Voorkeuren</legend>
      <label>
        <span>Taal</span>
        <select v-model="form.language" required>
          <option value="nl">Nederlands</option>
          <option value="en">English</option>
        </select>
      </label>

      <div
        class="form-option"
        :class="{ 'form-option--error': showTermsError }"
      >
        <input id="terms" v-model="acceptsTerms" type="checkbox" />
        <label for="terms">
          Ik accepteer de
          <NuxtLink to="/algemene-voorwaarden">algemene voorwaarden</NuxtLink>.
          <span class="form-option__required">*verplicht</span>
        </label>
      </div>
      <p v-if="showTermsError" class="form-option__error">
        Vink dit aan om een account aan te maken.
      </p>

      <div class="form-option">
        <input id="notif" v-model="wantsNotifications" type="checkbox" />
        <label for="notif">Ik wil notificaties ontvangen (e-mail)</label>
      </div>

      <div class="form-option">
        <input id="cookies" v-model="acceptsCookies" type="checkbox" />
        <label for="cookies">Ik ga akkoord met het gebruik van cookies (optioneel).</label>
      </div>
    </fieldset>

    <p v-if="error" class="form-error">{{ error }}</p>

    <div class="form-actions">
      <button class="button" :disabled="loading">
        {{ loading ? "Bezig..." : "Maak account" }}
      </button>
      <p class="form-subtext">
        Al een account?
        <NuxtLink to="/login">Inloggen</NuxtLink>
      </p>
    </div>
  </form>
</template>

<script setup lang="ts">
import { useAuth } from "~/composables/useAuth";
import { AGE_CATEGORIES, type AgeCategory } from "~/types/User";

const { register } = useAuth();
const router = useRouter();

const provinces = [
  "Drenthe",
  "Flevoland",
  "Friesland",
  "Gelderland",
  "Groningen",
  "Limburg",
  "Noord-Brabant",
  "Noord-Holland",
  "Overijssel",
  "Utrecht",
  "Zeeland",
  "Zuid-Holland",
];

const ageCategories = AGE_CATEGORIES;

const form = reactive({
  name: "",
  username: "",
  email: "",
  password: "",
  language: "nl" as "nl" | "en",
  // Wat-vindt-NL velden
  age_category: "" as AgeCategory | "",
  province: "" as string,
  gender: "unspecified" as "male" | "female" | "unspecified",
  education_level: "" as "mbo" | "hbo" | "universiteit" | "overig" | "",
  political_preference: "" as "links" | "midden" | "rechts" | "geen" | "",
});

const wantsNotifications = ref<boolean>(false);
const acceptsCookies = ref<boolean>(false);
const acceptsTerms = ref<boolean>(false);
const confirmPassword = ref<string>("");

const error = ref<string | null>(null);
const loading = ref(false);
const showTermsError = ref(false);

const TERMS_ERROR_MESSAGE = "Vink de algemene voorwaarden aan om door te gaan.";

watch(acceptsTerms, (value) => {
  if (value && showTermsError.value) {
    showTermsError.value = false;
    if (error.value === TERMS_ERROR_MESSAGE) {
      error.value = null;
    }
  }
});

async function onSubmit() {
  error.value = null;
  showTermsError.value = false;
  loading.value = true;
  try {
    if (form.password !== confirmPassword.value) {
      throw new Error("Wachtwoorden komen niet overeen.");
    }
    if (!acceptsTerms.value) {
      showTermsError.value = true;
      throw new Error(TERMS_ERROR_MESSAGE);
    }
    // payload normaliseren; voorkom nulls door lege strings te vermijden
    const now = new Date().toISOString();
    const payload = {
      ...form,
      password_confirmation: confirmPassword.value,
      notification_prefs: { email: !!wantsNotifications.value },
      cookie_prefs: {
        accepted: acceptsCookies.value,
        accepted_at: acceptsCookies.value ? now : null,
      },
      terms: {
        accepted: acceptsTerms.value,
        accepted_at: now,
      },
    };
    await register(payload as any);
    router.push({ path: "/login", query: { verify: "sent" } });
  } catch (e: any) {
    error.value = e?.message || "Registratie mislukt";
  } finally {
    loading.value = false;
  }
}
</script>

<style scoped>
.form-option {
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
  margin: 0.5rem 0;
}

.form-option input[type="checkbox"] {
  margin-top: 0.25rem;
}

.form-option label {
  margin: 0;
  line-height: 1.45;
}

.form-option a {
  color: inherit;
  text-decoration: underline;
  text-decoration-thickness: 2px;
  text-decoration-color: rgba(0, 61, 165, 0.45);
}

.form-option--error {
  outline: 2px solid rgba(200, 16, 46, 0.65);
  border-radius: 12px;
  padding: 0.75rem;
  background: rgba(200, 16, 46, 0.05);
}

.form-option__error {
  margin: -0.35rem 0 0.85rem 2.4rem;
  font-size: 0.95rem;
  font-weight: 600;
  color: #c8102e;
}

.form-option__required {
  color: #c8102e;
  font-size: 0.8rem;
  font-weight: 600;
  margin-left: 0.35rem;
}
</style>
