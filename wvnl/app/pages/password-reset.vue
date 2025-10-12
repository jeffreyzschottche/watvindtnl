<template>
  <div class="password-reset-page">
    <section class="container narrow">
      <div class="reset-card">
        <h1>Nieuw wachtwoord instellen</h1>
        <p class="reset-card__lead">
          Maak een nieuw wachtwoord aan om weer veilig in te loggen.
        </p>

        <div v-if="state === 'invalid'" class="reset-card__state reset-card__state--warning">
          <h2>Deze link werkt niet meer</h2>
          <p>
            De resetlink is ongeldig of verlopen. Vraag via de loginpagina een nieuwe e-mail aan.
          </p>
          <NuxtLink class="reset-card__link" to="/login">Terug naar login</NuxtLink>
        </div>

        <div v-else-if="state === 'error'" class="reset-card__state reset-card__state--error">
          <h2>Er ging iets mis</h2>
          <p>{{ serverMessage || "We konden je verzoek niet verwerken. Probeer het opnieuw." }}</p>
          <NuxtLink class="reset-card__link" to="/login">Probeer opnieuw</NuxtLink>
        </div>

        <div v-else-if="state === 'success'" class="reset-card__state reset-card__state--success">
          <h2>Je wachtwoord is aangepast</h2>
          <p>{{ successMessage }}</p>
          <NuxtLink class="reset-card__link" to="/login">Ga naar login</NuxtLink>
        </div>

        <form v-else class="reset-form" @submit.prevent="onSubmit">
          <label>
            <span>E-mailadres</span>
            <input v-model="form.email" type="email" autocomplete="email" required />
          </label>

          <label>
            <span>Nieuw wachtwoord</span>
            <input
              v-model="form.password"
              type="password"
              autocomplete="new-password"
              minlength="8"
              required
            />
          </label>

          <label>
            <span>Bevestig nieuw wachtwoord</span>
            <input
              v-model="form.confirm"
              type="password"
              autocomplete="new-password"
              minlength="8"
              required
            />
          </label>

          <p v-if="error" class="reset-form__error">{{ error }}</p>

          <div class="reset-form__actions">
            <button class="button" :disabled="loading">
              {{ loading ? "Bezig..." : "Wachtwoord instellen" }}
            </button>
            <NuxtLink class="reset-form__back" to="/login">Annuleren</NuxtLink>
          </div>
        </form>
      </div>
    </section>
  </div>
</template>

<script setup lang="ts">
const route = useRoute();
const router = useRouter();
const { resetPassword } = useAuth();

const token = computed(() => {
  const value = route.query.token;
  return typeof value === "string" ? value : "";
});

const emailFromQuery = computed(() => {
  const value = route.query.email;
  return typeof value === "string" ? value : "";
});

const routeStatus = computed(() => {
  const value = route.query.status;
  return typeof value === "string" ? value : null;
});

const routeMessage = computed(() => {
  const value = route.query.message;
  return typeof value === "string" ? value : null;
});

const state = ref<"form" | "invalid" | "error" | "success">(
  routeStatus.value === "invalid"
    ? "invalid"
    : routeStatus.value === "error"
    ? "error"
    : token.value && emailFromQuery.value
    ? "form"
    : "invalid"
);

const serverMessage = ref(routeMessage.value);

const form = reactive({
  email: emailFromQuery.value,
  password: "",
  confirm: "",
});

watch(emailFromQuery, (value) => {
  if (state.value === "form") {
    form.email = value;
  }
});

const error = ref<string | null>(null);
const loading = ref(false);
const successMessage = ref("Je kunt nu met je nieuwe wachtwoord inloggen.");

async function onSubmit() {
  if (!token.value) {
    state.value = "invalid";
    return;
  }

  error.value = null;

  if (form.password.length < 8) {
    error.value = "Het wachtwoord moet minimaal 8 tekens bevatten.";
    return;
  }
  if (form.password !== form.confirm) {
    error.value = "De wachtwoorden komen niet overeen.";
    return;
  }

  loading.value = true;
  try {
    const { message } = await resetPassword({
      token: token.value,
      email: form.email,
      password: form.password,
      password_confirmation: form.confirm,
    });
    successMessage.value =
      message || "Je wachtwoord is succesvol gewijzigd. Je wordt doorgestuurd naar de loginpagina.";
    state.value = "success";
    window.setTimeout(() => {
      router.replace({ path: "/login", query: { reset: "success" } });
    }, 2000);
  } catch (e: any) {
    error.value = e?.message || "We konden je wachtwoord niet resetten.";
  } finally {
    loading.value = false;
  }
}
</script>

<style scoped>
.password-reset-page {
  min-height: calc(100vh - 180px);
  display: grid;
  align-items: center;
  padding: 4rem 1rem;
  background: linear-gradient(135deg, rgba(0, 61, 165, 0.1), rgba(200, 16, 46, 0.08));
}

.reset-card {
  background: #fff;
  border-radius: 1.5rem;
  padding: 2.5rem;
  box-shadow: 0 24px 60px rgba(0, 26, 77, 0.18);
  border: 1px solid rgba(0, 61, 165, 0.12);
  display: grid;
  gap: 1.75rem;
}

.reset-card h1 {
  margin: 0;
  font-size: clamp(1.8rem, 3vw, 2.4rem);
}

.reset-card__lead {
  margin: 0;
  color: rgba(15, 23, 42, 0.75);
  font-size: 1.05rem;
}

.reset-card__state {
  display: grid;
  gap: 0.75rem;
  text-align: center;
  padding: 1.5rem;
  border-radius: 1rem;
  border: 1px solid transparent;
}

.reset-card__state h2 {
  margin: 0;
  font-size: 1.4rem;
}

.reset-card__state--warning {
  border-color: rgba(250, 204, 21, 0.4);
  background: rgba(250, 204, 21, 0.12);
}

.reset-card__state--error {
  border-color: rgba(200, 16, 46, 0.45);
  background: rgba(200, 16, 46, 0.08);
}

.reset-card__state--success {
  border-color: rgba(4, 120, 87, 0.4);
  background: rgba(16, 185, 129, 0.08);
}

.reset-card__link {
  justify-self: center;
  display: inline-flex;
  padding: 0.55rem 1.45rem;
  border-radius: 999px;
  background: linear-gradient(130deg, var(--color-accent, #c8102e), var(--color-primary, #003da5));
  color: #fff;
  font-weight: 700;
}

.reset-form {
  display: grid;
  gap: 1.25rem;
}

.reset-form label {
  display: grid;
  gap: 0.45rem;
}

.reset-form input {
  padding: 0.7rem 0.9rem;
  border-radius: 0.75rem;
  border: 1px solid rgba(0, 61, 165, 0.2);
  font-size: 1rem;
}

.reset-form__error {
  margin: 0;
  color: #b91c1c;
  font-weight: 600;
}

.reset-form__actions {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.reset-form__back {
  color: var(--color-primary, #003da5);
  font-weight: 600;
}
</style>
