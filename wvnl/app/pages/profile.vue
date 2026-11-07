<template>
  <div class="page" v-if="isLoggedIn">
    <section class="page-hero page-hero--profile">
      <div class="container narrow">
        <h1 class="page-hero__title">Instellingen</h1>
        <p class="page-hero__subtitle">
          Je gegevens en beveiliging, overzichtelijk op één plek.
        </p>
      </div>
    </section>

    <section class="container narrow profile">
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

      <div class="profile__card">
        <div class="profile__content">
          <section
            v-if="activeTab === 'profile'"
            id="profile-panel"
            class="profile-panel"
            role="tabpanel"
            aria-labelledby="profile-tab"
          >
            <form
              class="form form-card profile-form profile-panel-section"
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
                    class="button profiel-opslaan"
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
              class="form form-card profile-form profile-panel-section"
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
            <ProfilePoliticalCompass v-if="hasCompassAccess" />
            <div
              v-else
              class="profile-panel-section profile-panel-section--maintenance"
            >
              <h2>Politiek kompas</h2>
              <p>{{ politicalCompassMaintenanceMessage }}</p>
              <p>
                Het Politiek Kompas wordt onderdeel van onze aankomende betaalde
                ervaring. We laten het weten zodra je kunt upgraden.
              </p>
            </div>
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
definePageMeta({ alias: "/profiel/instellingen", middleware: "auth" });
import { computed, nextTick } from "vue";
import { storeToRefs } from "pinia";
import { AGE_CATEGORIES, type AgeCategory, type User } from "~/types/User";
import ProfilePoliticalCompass from "~/components/profile/ProfilePoliticalCompass.vue";
import { translateErrorMessage } from "~/utils/translateErrorMessage";

const auth = useAuthStore();
const api = useApi();
const router = useRouter();
const route = useRoute();
const { user, isLoggedIn } = storeToRefs(auth);

const ageCategories = AGE_CATEGORIES;
const politicalCompassMaintenanceMessage =
  "Het politiek kompas ondergaat momenteel onderhoud, we proberen dit zo snel mogelijk werkend te krijgen";
const hasCompassAccess = computed(() => Boolean(user.value?.premium));

const tabDefinitions = [
  { key: "profile", label: "Gegevens" },
  { key: "votes", label: "Stemgeschiedenis" },
  { key: "security", label: "Beveiliging" },
  { key: "compass", label: "Politiek kompas" },
] as const;

type TabDefinition = (typeof tabDefinitions)[number];
type TabKey = TabDefinition["key"];
type Tab = TabDefinition & {
  disabled?: boolean;
  description?: string;
};

const tabs = computed<Tab[]>(() =>
  tabDefinitions.map((tab) => {
    if (tab.key === "compass") {
      return {
        ...tab,
        disabled: !hasCompassAccess.value,
        description: !hasCompassAccess.value ? "Premium" : undefined,
      };
    }
    return tab;
  }),
);

const activeTab = ref<TabKey>("profile");
const voteHistoryRef = ref<{ refresh?: () => Promise<void> } | null>(null);

function setActiveTab(tab: TabKey) {
  const match = tabs.value.find((item) => item.key === tab);

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

function handleAuthFailure(error: unknown) {
  const statusCode =
    typeof error === "object" && error && "statusCode" in (error as any)
      ? Number((error as any).statusCode)
      : null;
  const rawMessage =
    typeof error === "object" && error && "message" in (error as any)
      ? String((error as any).message).toLowerCase()
      : "";

  if (
    statusCode === 401 ||
    statusCode === 419 ||
    rawMessage.includes("unauth")
  ) {
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
  { immediate: true },
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

    const match = tabs.value.find((item) => item.key === tab);
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
  { immediate: true },
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
  { immediate: true },
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
    const message = translateErrorMessage(error, {
      fallback: "Kon profiel niet laden",
    });
    if (!handleAuthFailure(error)) {
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
    const message = translateErrorMessage(error, {
      fallback: "Bijwerken mislukt.",
    });
    if (!handleAuthFailure(error)) {
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
    const message = translateErrorMessage(error, {
      fallback: "Bijwerken mislukt.",
    });
    if (!handleAuthFailure(error)) {
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
.profile {
  padding-bottom: 3rem;
}
.profile-tabs {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  margin-bottom: 1.5rem;
}
.profile-tab {
  border: 1px solid #dce3ee;
  background: #fff;
  color: #63738a;
  border-radius: 8px;
  padding: 0.65rem 1rem;
  font-size: 0.78rem;
  min-height: 44px;
}
.profile-tab.is-active {
  background: #001f4d;
  color: #fff;
  border-color: #001f4d;
}
.profile-tab.is-disabled {
  opacity: 0.5;
}
.profile-tab__status {
  margin-left: 0.5rem;
  font-size: 0.6rem;
}
.profile__card {
  background: #fff;
  border: 1px solid #dce3ee;
  border-radius: 16px;
  padding: 1.5rem;
}
.profile-form {
  box-shadow: none;
  border: 0;
  padding: 0.5rem;
}
.profile-loading {
  color: #63738a;
  font-size: 0.9rem;
}
.profile-panel-section--maintenance {
  padding: 1.5rem;
  color: #63738a;
}
.profile-panel-section--maintenance h2 {
  font-size: 1.4rem;
  color: #001f4d;
}
.profile-redirect {
  padding: 3rem;
  text-align: center;
  color: #63738a;
}
@media (max-width: 720px) {
  .profile__card {
    padding: 1rem;
  }
  .profile-tabs {
    gap: 0.4rem;
  }
  .profile-tab {
    padding: 0.6rem 0.75rem;
    font-size: 0.73rem;
  }
}
</style>
