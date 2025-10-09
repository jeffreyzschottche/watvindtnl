<template>
  <div class="mx-auto max-w-4xl space-y-12 p-6" v-if="isLoggedIn">
    <section class="space-y-6">
      <header class="space-y-2">
        <h1 class="text-3xl font-semibold">Jouw profiel</h1>
        <p class="text-gray-600">Beheer je gegevens en bekijk je stemgedrag.</p>
      </header>

      <div v-if="profileError" class="rounded border border-red-200 bg-red-50 p-4 text-red-700">
        {{ profileError }}
      </div>
      <div v-if="profileMessage" class="rounded border border-green-200 bg-green-50 p-4 text-green-700">
        {{ profileMessage }}
      </div>

      <p v-if="loadingProfile" class="text-gray-500">Profiel wordt geladen...</p>
      <form v-else class="grid gap-6 md:grid-cols-2" @submit.prevent="saveProfile">
        <div class="md:col-span-2">
          <label class="block text-sm font-medium text-gray-700">Naam</label>
          <input
            v-model="profileForm.name"
            type="text"
            class="mt-1 w-full rounded border border-gray-300 px-3 py-2 focus:border-black focus:outline-none"
            required
          />
        </div>

        <div class="md:col-span-2">
          <label class="block text-sm font-medium text-gray-700">E-mailadres</label>
          <input
            v-model="profileForm.email"
            type="email"
            class="mt-1 w-full rounded border border-gray-300 px-3 py-2 focus:border-black focus:outline-none"
            required
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700">Taal</label>
          <select
            v-model="profileForm.language"
            class="mt-1 w-full rounded border border-gray-300 px-3 py-2 focus:border-black focus:outline-none"
          >
            <option value="nl">Nederlands</option>
            <option value="en">Engels</option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700"
            >Leeftijdscategorie</label
          >
          <select
            v-model="profileForm.age_category"
            class="mt-1 w-full rounded border border-gray-300 px-3 py-2 focus:border-black focus:outline-none"
          >
            <option value="">Selecteer</option>
            <option v-for="age in ageCategories" :key="age" :value="age">
              {{ age }}
            </option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700">Provincie</label>
          <input
            v-model="profileForm.province"
            type="text"
            class="mt-1 w-full rounded border border-gray-300 px-3 py-2 focus:border-black focus:outline-none"
            placeholder="Bijv. Noord-Holland"
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700">Gender</label>
          <select
            v-model="profileForm.gender"
            class="mt-1 w-full rounded border border-gray-300 px-3 py-2 focus:border-black focus:outline-none"
          >
            <option value="male">Man</option>
            <option value="female">Vrouw</option>
            <option value="unspecified">Niet opgegeven</option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700">Opleidingsniveau</label>
          <select
            v-model="profileForm.education_level"
            class="mt-1 w-full rounded border border-gray-300 px-3 py-2 focus:border-black focus:outline-none"
          >
            <option value="">Selecteer</option>
            <option value="mbo">MBO</option>
            <option value="hbo">HBO</option>
            <option value="universiteit">Universiteit</option>
            <option value="overig">Overig</option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700">Politieke voorkeur</label>
          <select
            v-model="profileForm.political_preference"
            class="mt-1 w-full rounded border border-gray-300 px-3 py-2 focus:border-black focus:outline-none"
          >
            <option value="">Selecteer</option>
            <option value="links">Links</option>
            <option value="midden">Midden</option>
            <option value="rechts">Rechts</option>
            <option value="geen">Geen voorkeur</option>
          </select>
        </div>

        <div class="md:col-span-2 flex justify-end">
          <button
            type="submit"
            class="inline-flex items-center rounded bg-black px-4 py-2 text-white disabled:opacity-50"
            :disabled="savingProfile || loadingProfile"
          >
            {{ savingProfile ? "Opslaan..." : "Profiel opslaan" }}
          </button>
        </div>
      </form>
    </section>

    <ProfileVoteHistory />

    <section class="space-y-6">
      <header>
        <h2 class="text-2xl font-semibold">Premium lidmaatschap</h2>
        <p class="text-gray-600">
          Zet je premium status aan of uit. De waarde 1 staat voor premium, 0 voor standaard.
        </p>
      </header>

      <div v-if="premiumError" class="rounded border border-red-200 bg-red-50 p-4 text-red-700">
        {{ premiumError }}
      </div>
      <div v-if="premiumMessage" class="rounded border border-green-200 bg-green-50 p-4 text-green-700">
        {{ premiumMessage }}
      </div>

      <form class="space-y-4 md:max-w-sm" @submit.prevent="savePremium">
        <label class="block text-sm font-medium text-gray-700">
          Premium status
          <select
            v-model="premiumForm.premium"
            class="mt-1 w-full rounded border border-gray-300 px-3 py-2 focus:border-black focus:outline-none"
            :disabled="savingPremium"
          >
            <option :value="false">0 - Niet premium</option>
            <option :value="true">1 - Premium</option>
          </select>
        </label>

        <button
          type="submit"
          class="inline-flex items-center rounded bg-black px-4 py-2 text-white disabled:opacity-50"
          :disabled="savingPremium"
        >
          {{ savingPremium ? "Opslaan..." : "Premium bijwerken" }}
        </button>
      </form>
    </section>

    <section class="space-y-6">
      <header class="space-y-2">
        <h2 class="text-2xl font-semibold">Wachtwoord wijzigen</h2>
        <p class="text-gray-600">
          Verifieer je huidige wachtwoord en kies vervolgens een nieuw wachtwoord om je
          wijzigingen op te slaan.
        </p>
      </header>

      <div v-if="passwordError" class="rounded border border-red-200 bg-red-50 p-4 text-red-700">
        {{ passwordError }}
      </div>
      <div
        v-if="passwordMessage"
        class="rounded border border-green-200 bg-green-50 p-4 text-green-700"
      >
        {{ passwordMessage }}
      </div>

      <form class="grid gap-6 md:grid-cols-2" @submit.prevent="updatePassword">
        <div class="md:col-span-2">
          <label class="block text-sm font-medium text-gray-700">Huidig wachtwoord</label>
          <input
            v-model="passwordForm.current_password"
            type="password"
            minlength="8"
            class="mt-1 w-full rounded border border-gray-300 px-3 py-2 focus:border-black focus:outline-none"
            required
          />
        </div>

        <div class="md:col-span-2">
          <label class="block text-sm font-medium text-gray-700">Nieuw wachtwoord</label>
          <input
            v-model="passwordForm.password"
            type="password"
            minlength="8"
            class="mt-1 w-full rounded border border-gray-300 px-3 py-2 focus:border-black focus:outline-none"
            required
          />
        </div>

        <div class="md:col-span-2">
          <label class="block text-sm font-medium text-gray-700"
            >Bevestig nieuw wachtwoord</label
          >
          <input
            v-model="passwordForm.password_confirmation"
            type="password"
            minlength="8"
            class="mt-1 w-full rounded border border-gray-300 px-3 py-2 focus:border-black focus:outline-none"
            required
          />
        </div>

        <div class="md:col-span-2 flex justify-end">
          <button
            type="submit"
            class="inline-flex items-center rounded bg-black px-4 py-2 text-white disabled:opacity-50"
            :disabled="savingPassword"
          >
            {{ savingPassword ? "Bijwerken..." : "Wachtwoord opslaan" }}
          </button>
        </div>
      </form>
    </section>
  </div>

  <div v-else class="flex min-h-[60vh] items-center justify-center p-6 text-gray-500">
    Je wordt doorgestuurd naar de inlogpagina...
  </div>
</template>

<script setup lang="ts">
import { storeToRefs } from "pinia";
import { AGE_CATEGORIES, type AgeCategory, type User } from "~/types/User";

const auth = useAuthStore();
const api = useApi();
const router = useRouter();
const { user, isLoggedIn } = storeToRefs(auth);

const ageCategories = AGE_CATEGORIES;

const profileForm = reactive({
  name: "",
  email: "",
  language: "nl" as "nl" | "en",
  age_category: "" as AgeCategory | "",
  province: "",
  gender: "unspecified" as User["gender"],
  education_level: "",
  political_preference: "",
});

const premiumForm = reactive({
  premium: false,
});

const passwordForm = reactive({
  current_password: "",
  password: "",
  password_confirmation: "",
});

const profileMessage = ref<string | null>(null);
const profileError = ref<string | null>(null);
const savingProfile = ref(false);
const loadingProfile = ref(true);

const premiumMessage = ref<string | null>(null);
const premiumError = ref<string | null>(null);
const savingPremium = ref(false);

const passwordMessage = ref<string | null>(null);
const passwordError = ref<string | null>(null);
const savingPassword = ref(false);

function handleAuthFailure(message: string) {
  if (message.toLowerCase().includes("unauth")) {
    auth.logout();
    router.replace("/login");
    return true;
  }

  return false;
}

watch(
  user,
  (value) => {
    if (!value) return;

    hydrateFromUser(value);
    loadingProfile.value = false;
  },
  { immediate: true }
);

watch(
  isLoggedIn,
  (loggedIn) => {
    if (!loggedIn) {
      router.replace("/login");
    }
  },
  { immediate: true }
);

onMounted(() => {
  if (isLoggedIn.value) {
    refreshProfile();
  }
});

async function refreshProfile() {
  profileError.value = null;
  try {
    const me = await api.get<User>("/me");
    auth.updateUser(me);
  } catch (error: any) {
    const message = error?.message || "Kon profiel niet laden";
    if (!handleAuthFailure(message)) {
      profileError.value = message;
    }
  } finally {
    loadingProfile.value = false;
  }
}

function hydrateFromUser(value: User) {
  profileForm.name = value.name ?? "";
  profileForm.email = value.email ?? "";
  profileForm.language = (value.language as "nl" | "en") ?? "nl";
  profileForm.age_category = (value.age_category as AgeCategory | null) ?? "";
  profileForm.province = value.province ?? "";
  profileForm.gender = (value.gender ?? "unspecified") as User["gender"];
  profileForm.education_level = value.education_level ?? "";
  profileForm.political_preference = value.political_preference ?? "";
  premiumForm.premium = !!value.premium;
}

async function saveProfile() {
  if (!user.value) return;

  profileMessage.value = null;
  profileError.value = null;
  savingProfile.value = true;

  const payload = {
    name: profileForm.name.trim(),
    email: profileForm.email.trim(),
    language: profileForm.language,
    age_category: profileForm.age_category || null,
    province: profileForm.province ? profileForm.province.trim() : null,
    gender: profileForm.gender,
    education_level: profileForm.education_level || null,
    political_preference: profileForm.political_preference || null,
  };

  try {
    const updated = await api.patch<User>(`/users/${user.value.id}`, payload);
    auth.updateUser(updated);
    profileMessage.value = "Profiel succesvol bijgewerkt.";
  } catch (error: any) {
    const message = error?.message || "Bijwerken mislukt.";
    if (!handleAuthFailure(message)) {
      profileError.value = message;
    }
  } finally {
    savingProfile.value = false;
  }
}

async function savePremium() {
  if (!user.value) return;

  premiumMessage.value = null;
  premiumError.value = null;
  savingPremium.value = true;

  try {
    const updated = await api.patch<User>(`/users/${user.value.id}`, {
      premium: premiumForm.premium,
    });
    auth.updateUser(updated);
    premiumMessage.value = "Premiumstatus bijgewerkt.";
  } catch (error: any) {
    const message = error?.message || "Bijwerken mislukt.";
    if (!handleAuthFailure(message)) {
      premiumError.value = message;
    }
  } finally {
    savingPremium.value = false;
  }
}

async function updatePassword() {
  if (!user.value) return;

  passwordMessage.value = null;
  passwordError.value = null;

  if (!passwordForm.current_password || passwordForm.current_password.length < 8) {
    passwordError.value = "Vul je huidige wachtwoord (minimaal 8 tekens) in.";
    return;
  }

  if (!passwordForm.password || passwordForm.password.length < 8) {
    passwordError.value = "Het wachtwoord moet minimaal 8 tekens bevatten.";
    return;
  }

  if (passwordForm.password !== passwordForm.password_confirmation) {
    passwordError.value = "Wachtwoorden komen niet overeen.";
    return;
  }

  savingPassword.value = true;

  try {
    await api.patch(`/users/${user.value.id}/password`, {
      current_password: passwordForm.current_password,
      password: passwordForm.password,
      password_confirmation: passwordForm.password_confirmation,
    });
    passwordMessage.value = "Wachtwoord succesvol bijgewerkt.";
    passwordForm.current_password = "";
    passwordForm.password = "";
    passwordForm.password_confirmation = "";
  } catch (error: any) {
    const message = error?.message || "Bijwerken mislukt.";
    if (!handleAuthFailure(message)) {
      passwordError.value = message;
    }
  } finally {
    savingPassword.value = false;
  }
}

</script>
