<template>
  <div class="mx-auto max-w-4xl space-y-10 p-6" v-if="isLoggedIn">
    <section class="space-y-4">
      <header class="space-y-2">
        <h1 class="text-3xl font-semibold">Jouw profiel</h1>
        <p class="text-gray-600">Beheer je gegevens en bekijk je stemgedrag.</p>
      </header>
    </section>

    <div class="overflow-hidden rounded-lg border border-gray-200 shadow-sm">
      <nav class="flex flex-col gap-2 border-b border-gray-200 bg-gray-50 px-4 py-3 text-sm font-medium text-gray-600 sm:flex-row">
        <button
          v-for="tab in tabs"
          :key="tab.key"
          type="button"
          class="rounded px-3 py-2 transition"
          :class="
            activeTab === tab.key
              ? 'bg-white text-black shadow-sm'
              : 'text-gray-500 hover:text-black'
          "
          @click="activeTab = tab.key"
        >
          {{ tab.label }}
        </button>
      </nav>

      <div class="space-y-8 p-6">
        <section v-if="activeTab === 'profile'" class="space-y-6">
          <header class="space-y-2">
            <h2 class="text-2xl font-semibold">Persoonlijke gegevens</h2>
            <p class="text-gray-600">Werk je profielgegevens bij.</p>
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

        <section v-else-if="activeTab === 'votes'" class="space-y-6">
          <ProfileVoteHistory />
        </section>

        <!--
          Premium lidmaatschap-tab tijdelijk verwijderd op verzoek. Kan opnieuw geactiveerd worden
          door deze sectie terug te plaatsen.
        -->

        <section v-else-if="activeTab === 'security'" class="space-y-6">
          <header class="space-y-2">
            <h2 class="text-2xl font-semibold">Wachtwoord wijzigen</h2>
            <p class="text-gray-600">
              Verifieer je huidige wachtwoord en kies vervolgens een nieuw wachtwoord om je wijzigingen
              op te slaan.
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
    </div>
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

const tabs = [
  { key: "profile", label: "Profiel" },
  { key: "votes", label: "Stemgeschiedenis" },
  { key: "security", label: "Wachtwoord" },
] as const;

type TabKey = (typeof tabs)[number]["key"];

const activeTab = ref<TabKey>("profile");

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

// Premium lidmaatschap functionaliteit is tijdelijk uitgeschakeld; zie template-opmerking voor details.

const passwordForm = reactive({
  current_password: "",
  password: "",
  password_confirmation: "",
});

const profileMessage = ref<string | null>(null);
const profileError = ref<string | null>(null);
const savingProfile = ref(false);
const loadingProfile = ref(true);

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
