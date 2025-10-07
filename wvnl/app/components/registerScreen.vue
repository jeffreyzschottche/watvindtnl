<template>
  <form class="max-w-md mx-auto space-y-5 p-6" @submit.prevent="onSubmit">
    <h1 class="text-2xl font-semibold">Registreren</h1>

    <!-- Basis -->
    <div>
      <label class="block text-sm mb-1">Naam</label>
      <input v-model="form.name" class="input" required />
    </div>

    <div>
      <label class="block text-sm mb-1">Gebruikersnaam</label>
      <input v-model="form.username" class="input" required />
    </div>

    <div>
      <label class="block text-sm mb-1">E-mail</label>
      <input v-model="form.email" type="email" class="input" required />
    </div>

    <div>
      <label class="block text-sm mb-1">Wachtwoord</label>
      <input
        v-model="form.password"
        type="password"
        class="input"
        minlength="8"
        required
      />
    </div>

    <!-- Profielvelden Wat-vindt-NL -->
    <div>
      <label class="block text-sm mb-1">Leeftijdscategorie</label>
      <select v-model="form.age_category" class="input" required>
        <option disabled value="">Maak een keuze</option>
        <option value="16-17">16–17</option>
        <option value="18-24">18–24</option>
        <option value="25-34">25–34</option>
        <option value="35-44">35–44</option>
        <option value="45-54">45–54</option>
        <option value="55-64">55–64</option>
        <option value="65+">65+</option>
      </select>
    </div>

    <div>
      <label class="block text-sm mb-1">Provincie</label>
      <select v-model="form.province" class="input" required>
        <option disabled value="">Maak een keuze</option>
        <option v-for="p in provinces" :key="p" :value="p">{{ p }}</option>
      </select>
    </div>

    <div>
      <label class="block text-sm mb-1">Geslacht</label>
      <select v-model="form.gender" class="input" required>
        <option value="unspecified">Zeg ik niet</option>
        <option value="male">Man</option>
        <option value="female">Vrouw</option>
      </select>
    </div>

    <div>
      <label class="block text-sm mb-1">Onderwijsniveau</label>
      <select v-model="form.education_level" class="input" required>
        <option disabled value="">Maak een keuze</option>
        <option value="mbo">MBO</option>
        <option value="hbo">HBO</option>
        <option value="universiteit">Universiteit</option>
        <option value="overig">Overig</option>
      </select>
    </div>

    <div>
      <label class="block text-sm mb-1">Politieke voorkeur</label>
      <select v-model="form.political_preference" class="input" required>
        <option disabled value="">Maak een keuze</option>
        <option value="links">Links</option>
        <option value="midden">Midden</option>
        <option value="rechts">Rechts</option>
        <option value="geen">Geen / Onbekend</option>
      </select>
    </div>

    <!-- App instellingen -->
    <div>
      <label class="block text-sm mb-1">Taal</label>
      <select v-model="form.language" class="input" required>
        <option value="nl">Nederlands</option>
        <option value="en">English</option>
      </select>
    </div>

    <div class="flex items-center gap-3">
      <input
        id="notif"
        v-model="wantsNotifications"
        type="checkbox"
        class="h-4 w-4"
      />
      <label for="notif" class="text-sm"
        >Ik wil notificaties ontvangen (e-mail)</label
      >
    </div>

    <p v-if="error" class="text-red-600 text-sm">{{ error }}</p>

    <button class="btn w-full" :disabled="loading">
      {{ loading ? "Bezig..." : "Maak account" }}
    </button>

    <p class="text-sm text-center">
      Al een account?
      <NuxtLink class="underline" to="/login">Inloggen</NuxtLink>
    </p>
  </form>
</template>

<script setup lang="ts">
import { useAuth } from "~/composables/useAuth";

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

const form = reactive({
  name: "",
  username: "",
  email: "",
  password: "",
  language: "nl" as "nl" | "en",
  // Wat-vindt-NL velden
  age_category: "" as string,
  province: "" as string,
  gender: "unspecified" as "male" | "female" | "unspecified",
  education_level: "" as "mbo" | "hbo" | "universiteit" | "overig" | "",
  political_preference: "" as "links" | "midden" | "rechts" | "geen" | "",
});

const wantsNotifications = ref<boolean>(true);

const error = ref<string | null>(null);
const loading = ref(false);

async function onSubmit() {
  error.value = null;
  loading.value = true;
  try {
    // payload normaliseren; voorkom nulls door lege strings te vermijden
    const payload = {
      ...form,
      notification_prefs: { email: !!wantsNotifications.value },
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

<style scoped>
.input {
  @apply w-full border rounded px-3 py-2;
}
.btn {
  @apply bg-black text-white rounded px-4 py-2;
}
</style>
