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

      <div class="form-option">
        <input
          id="notif"
          v-model="wantsNotifications"
          type="checkbox"
        />
        <label for="notif">Ik wil notificaties ontvangen (e-mail)</label>
      </div>

      <div class="form-option">
        <input
          id="cookies"
          v-model="acceptsCookies"
          type="checkbox"
          required
        />
        <label for="cookies">Ik ga akkoord met het gebruik van cookies.</label>
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

const wantsNotifications = ref<boolean>(true);
const acceptsCookies = ref<boolean>(false);
const confirmPassword = ref<string>("");

const error = ref<string | null>(null);
const loading = ref(false);

async function onSubmit() {
  error.value = null;
  loading.value = true;
  try {
    if (form.password !== confirmPassword.value) {
      throw new Error("Wachtwoorden komen niet overeen.");
    }
    if (!acceptsCookies.value) {
      throw new Error("Je moet akkoord gaan met het cookiegebruik.");
    }
    // payload normaliseren; voorkom nulls door lege strings te vermijden
    const payload = {
      ...form,
      password_confirmation: confirmPassword.value,
      notification_prefs: { email: !!wantsNotifications.value },
      cookie_prefs: {
        accepted: acceptsCookies.value,
        accepted_at: new Date().toISOString(),
      },
    };
    await register(payload as any);
    router.push("/");
  } catch (e: any) {
    error.value = e?.message || "Registratie mislukt";
  } finally {
    loading.value = false;
  }
}
</script>
