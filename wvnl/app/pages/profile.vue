<template>
  <div class="page" v-if="isLoggedIn">
    <section class="page-hero page-hero--profile">
      <div class="container narrow">
        <h1 class="page-hero__title">
          <span class="page-hero__accent page-hero__accent--blue">Mijn</span>
          <span class="page-hero__accent page-hero__accent--red">profiel</span>
        </h1>
        <p class="page-hero__subtitle">
          Beheer je gegevens, blader door je stemgeschiedenis en houd je
          wachtwoord up-to-date.
        </p>
      </div>
    </section>

    <section class="container narrow profile">
      <div class="profile__card">
        <nav class="profile-tabs" role="tablist" aria-label="Profielnavigatie">
          <button
            v-for="tab in tabs"
            :key="tab.key"
            type="button"
            class="profile-tab"
            :id="`${tab.key}-tab`"
            role="tab"
            :aria-selected="activeTab === tab.key"
            :aria-controls="`${tab.key}-panel`"
            :aria-disabled="tab.disabled ? 'true' : 'false'"
            :tabindex="activeTab === tab.key ? 0 : -1"
            :disabled="tab.disabled"
            :class="{
              'is-active': activeTab === tab.key,
              'is-disabled': tab.disabled,
            }"
            @click="!tab.disabled && setActiveTab(tab.key)"
          >
            <span class="profile-tab__label">{{ tab.label }}</span>
            <span
              v-if="tab.disabled && tab.description"
              class="profile-tab__status"
            >
              {{ tab.description }}
            </span>
          </button>
        </nav>

        <div class="profile__content">
          <section
            v-if="activeTab === 'profile'"
            id="profile-panel"
            class="profile-panel"
            role="tabpanel"
            aria-labelledby="profile-tab"
          >
            <form
              class="form form-card profile-form"
              @submit.prevent="saveProfile"
            >
              <header class="form-header">
                <h2>Persoonlijke gegevens</h2>
                <p>
                  Werk je profielgegevens bij zodat we je beter kunnen leren
                  kennen.
                </p>
              </header>

              <p v-if="profileError" class="alert alert--error">
                {{ profileError }}
              </p>
              <p v-if="profileMessage" class="alert alert--success">
                {{ profileMessage }}
              </p>
              <p v-if="loadingProfile" class="profile-loading">
                Profiel wordt geladen...
              </p>

              <template v-else>
                <fieldset class="form-fieldset">
                  <legend>Contact</legend>
                  <label>
                    <span>Naam</span>
                    <input
                      v-model="profileForm.name"
                      type="text"
                      autocomplete="name"
                      required
                    />
                  </label>
                  <label>
                    <span>E-mailadres</span>
                    <input
                      v-model="profileForm.email"
                      type="email"
                      autocomplete="email"
                      required
                    />
                  </label>
                  <label>
                    <span>Taal</span>
                    <select v-model="profileForm.language">
                      <option value="nl">Nederlands</option>
                      <option value="en">Engels</option>
                    </select>
                  </label>
                </fieldset>

                <fieldset class="form-fieldset">
                  <legend>Profielinformatie</legend>
                  <label>
                    <span>Leeftijdscategorie</span>
                    <select v-model="profileForm.age_category">
                      <option value="">Selecteer</option>
                      <option
                        v-for="age in ageCategories"
                        :key="age"
                        :value="age"
                      >
                        {{ age }}
                      </option>
                    </select>
                  </label>
                  <label>
                    <span>Provincie</span>
                    <input
                      v-model="profileForm.province"
                      type="text"
                      placeholder="Bijv. Noord-Holland"
                      autocomplete="address-level1"
                    />
                  </label>
                  <label>
                    <span>Gender</span>
                    <select v-model="profileForm.gender">
                      <option value="male">Man</option>
                      <option value="female">Vrouw</option>
                      <option value="unspecified">Niet opgegeven</option>
                    </select>
                  </label>
                  <label>
                    <span>Opleidingsniveau</span>
                    <select v-model="profileForm.education_level">
                      <option value="">Selecteer</option>
                      <option value="mbo">MBO</option>
                      <option value="hbo">HBO</option>
                      <option value="universiteit">Universiteit</option>
                      <option value="overig">Overig</option>
                    </select>
                  </label>
                  <label>
                    <span>Politieke voorkeur</span>
                    <select v-model="profileForm.political_preference">
                      <option value="">Selecteer</option>
                      <option value="links">Links</option>
                      <option value="midden">Midden</option>
                      <option value="rechts">Rechts</option>
                      <option value="geen">Geen voorkeur</option>
                    </select>
                  </label>
                </fieldset>

                <div class="form-actions">
                  <button
                    class="button"
                    type="submit"
                    :disabled="savingProfile"
                  >
                    {{ savingProfile ? "Opslaan..." : "Profiel opslaan" }}
                  </button>
                </div>
              </template>
            </form>
          </section>

          <section
            v-else-if="activeTab === 'votes'"
            id="votes-panel"
            class="profile-panel"
            role="tabpanel"
            aria-labelledby="votes-tab"
          >
            <ProfileVoteHistory ref="voteHistoryRef" />
          </section>

          <section
            v-else-if="activeTab === 'security'"
            id="security-panel"
            class="profile-panel"
            role="tabpanel"
            aria-labelledby="security-tab"
          >
            <form
              class="form form-card profile-form"
              @submit.prevent="updatePassword"
            >
              <header class="form-header">
                <h2>Wachtwoord wijzigen</h2>
                <p>
                  Verifieer je huidige wachtwoord en kies vervolgens een nieuw
                  wachtwoord.
                </p>
              </header>

              <p v-if="passwordError" class="alert alert--error">
                {{ passwordError }}
              </p>
              <p v-if="passwordMessage" class="alert alert--success">
                {{ passwordMessage }}
              </p>

              <fieldset class="form-fieldset">
                <legend>Beveiliging</legend>
                <label>
                  <span>Huidig wachtwoord</span>
                  <input
                    v-model="passwordForm.current_password"
                    type="password"
                    minlength="8"
                    autocomplete="current-password"
                    required
                  />
                </label>
                <label>
                  <span>Nieuw wachtwoord</span>
                  <input
                    v-model="passwordForm.password"
                    type="password"
                    minlength="8"
                    autocomplete="new-password"
                    required
                  />
                </label>
                <label>
                  <span>Bevestig nieuw wachtwoord</span>
                  <input
                    v-model="passwordForm.password_confirmation"
                    type="password"
                    minlength="8"
                    autocomplete="new-password"
                    required
                  />
                </label>
              </fieldset>

              <div class="form-actions">
                <button class="button" type="submit" :disabled="savingPassword">
                  {{ savingPassword ? "Bijwerken..." : "Wachtwoord opslaan" }}
                </button>
              </div>
            </form>
          </section>

          <section
            v-else-if="activeTab === 'compass'"
            id="compass-panel"
            class="profile-panel"
            role="tabpanel"
            aria-labelledby="compass-tab"
          >
            <ProfilePoliticalCompass />
          </section>
        </div>
      </div>
    </section>
  </div>

  <div v-else class="profile-redirect">
    Je wordt doorgestuurd naar de inlogpagina...
  </div>
</template>

<script setup lang="ts">
import { nextTick } from "vue";
import { storeToRefs } from "pinia";
import { AGE_CATEGORIES, type AgeCategory, type User } from "~/types/User";
import ProfilePoliticalCompass from "~/components/profile/ProfilePoliticalCompass.vue";

const auth = useAuthStore();
const api = useApi();
const router = useRouter();
const route = useRoute();
const { user, isLoggedIn } = storeToRefs(auth);

const ageCategories = AGE_CATEGORIES;

const tabs = [
  { key: "profile", label: "Profiel" },
  { key: "votes", label: "Stemgeschiedenis" },
  { key: "security", label: "Wachtwoord" },
  { key: "compass", label: "Politiek kompas" },
] as const;

type Tab = (typeof tabs)[number];
type TabKey = Tab["key"];

const activeTab = ref<TabKey>("profile");
const voteHistoryRef = ref<{ refresh?: () => Promise<void> } | null>(null);

function setActiveTab(tab: TabKey) {
  const match = tabs.find((item) => item.key === tab);

  if (!match || match.disabled) {
    return;
  }

  if (tab === activeTab.value) {
    if (import.meta.client && route.query.tab !== tab) {
      router.replace({ query: { ...route.query, tab } });
    }
    return;
  }

  activeTab.value = tab;

  if (import.meta.client && route.query.tab !== tab) {
    router.replace({ query: { ...route.query, tab } });
  }
}

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
  () => route.query.tab,
  (tab) => {
    if (typeof tab !== "string") {
      if (import.meta.client && route.query.tab) {
        const { tab: _tab, ...query } = route.query;
        router.replace({ query });
      }
      return;
    }

    const match = tabs.find((item) => item.key === tab);
    if (!match) {
      return;
    }

    if (match.disabled) {
      if (import.meta.client && route.query.tab) {
        const { tab: _tab, ...query } = route.query;
        router.replace({ query });
      }
      return;
    }

    if (activeTab.value !== match.key) {
      activeTab.value = match.key;
    }
  },
  { immediate: true }
);

watch(activeTab, async (tab) => {
  if (tab === "votes") {
    await nextTick();
    voteHistoryRef.value?.refresh?.();
  }
});

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

  if (
    !passwordForm.current_password ||
    passwordForm.current_password.length < 8
  ) {
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
const title = "Mijn profiel | Wat Denkt Nederland";
const description =
  "Beheer je persoonlijke gegevens, stemgeschiedenis en voorkeuren binnen je Wat Denkt Nederland-profiel.";
</script>

<style scoped>
.page-hero--profile {
  background-image: linear-gradient(
      135deg,
      rgba(0, 61, 165, 0.12),
      rgba(200, 16, 46, 0.2)
    ),
    url("/images/stockvault-cheese147191.webp");
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

.page-hero--profile::before {
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

.page-hero--profile::after {
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

.page-hero--profile .container {
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
  max-width: 58ch;
  font-size: 1.1rem;
  font-weight: 500;
}

.profile {
  margin-top: -4rem;
  padding-bottom: 4rem;
}

.profile__card {
  background: var(--color-surface);
  border-radius: calc(var(--border-radius) + 4px);
  box-shadow: var(--shadow-soft);
  border: 1px solid rgba(0, 61, 165, 0.12);
  overflow: hidden;
}

.profile-tabs {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
  padding: 1.25rem clamp(1rem, 3vw, 2rem) 1rem;
  border-bottom: 1px solid rgba(0, 61, 165, 0.16);
  margin-top: 4.5em;
}

.profile-tab {
  appearance: none;
  background: #ffffff;
  border: 2px solid rgba(0, 61, 165, 0.25);
  border-radius: 999px;
  padding: 0.65rem 1.5rem;
  font-weight: 700;
  font-size: 0.95rem;
  color: rgba(16, 24, 40, 0.82);
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
}

.profile-tab__label {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
}

.profile-tab__status {
  font-size: 0.75rem;
  font-weight: 600;
  color: rgba(0, 61, 165, 0.9);
  padding: 0.125rem 0.45rem;
  border-radius: 999px;
  background: rgba(0, 61, 165, 0.1);
}

.profile-tab:not(.is-disabled):is(:hover, :focus-visible) {
  border-color: black;
  box-shadow: 0 8px 18px rgba(0, 61, 165, 0.2);
  outline: none;
  transform: translateY(-1px);
}

.profile-tab.is-disabled {
  cursor: not-allowed;
  opacity: 0.55;
  border-style: dashed;
  border-color: rgba(0, 61, 165, 0.2);
  box-shadow: none;
  background: rgba(248, 250, 252, 0.8);
  color: rgba(16, 24, 40, 0.6);
  transition: none;
}

.profile-tab.is-disabled .profile-tab__status {
  background: rgba(255, 111, 0, 0.14);
  color: rgba(255, 111, 0, 0.9);
}

.profile-tab.is-active {
  color: #ffffff;
  border-color: black;
  background-image: linear-gradient(
    135deg,
    rgba(0, 61, 165, 0.95),
    rgba(200, 16, 46, 0.95)
  );
  box-shadow: 0 12px 24px rgba(0, 61, 165, 0.28);
}

.profile-tab.is-active .profile-tab__label {
  text-shadow: 0 0 6px rgba(0, 0, 0, 0.2);
}

.profile__content {
  padding: 2.5rem clamp(1.5rem, 4vw, 3rem);
}

.profile-panel {
  display: block;
}

.profile-form {
  margin: 0 auto;
}

.profile-loading {
  margin: 1rem 0 0;
  color: var(--color-muted);
  font-weight: 500;
}

.alert {
  margin: 0;
  padding: 0.85rem 1.1rem;
  border-radius: 12px;
  font-weight: 600;
}

.alert + .alert {
  margin-top: 0.75rem;
}

.alert--error {
  background: rgba(200, 16, 46, 0.08);
  border: 1px solid rgba(200, 16, 46, 0.35);
  color: var(--color-accent);
}

.alert--success {
  background: rgba(0, 148, 94, 0.08);
  border: 1px solid rgba(0, 148, 94, 0.35);
  color: #087443;
}

.profile-redirect {
  min-height: 60vh;
  display: grid;
  place-items: center;
  color: var(--color-muted);
  font-weight: 500;
  text-align: center;
  padding: 3rem 1.5rem;
}

@media (max-width: 768px) {
  .profile {
    margin-top: -3rem;
  }

  .profile__content {
    padding: 2rem 1.25rem;
  }

  .profile-tabs {
    justify-content: center;
  }
}
</style>
