<template>
  <form class="form form-card auth-form" @submit.prevent="onSubmit">
    <header class="form-header">
      <img
        class="auth-mark"
        src="/brand/wdnl-mark-concept.svg"
        alt=""
        width="42"
        height="48"
      />
      <h1>Welkom terug.</h1>
      <p>Log in en ontdek wat jij ervan vindt.</p>
    </header>

    <label>
      <span>E-mailadres</span>
      <input
        v-model="email"
        type="email"
        placeholder="jij@voorbeeld.nl"
        autocomplete="email"
        required
      />
    </label>

    <label>
      <span>Wachtwoord</span>
      <input
        v-model="password"
        type="password"
        placeholder="Je wachtwoord"
        autocomplete="current-password"
        required
      />
    </label>

    <p v-if="error" class="form-error">{{ error }}</p>

    <div class="form-actions">
      <button class="button" :disabled="loading">
        {{ loading ? "Bezig..." : "Log in" }}
      </button>
      <p class="form-subtext">
        Nog geen account?
        <NuxtLink to="/registreren">Registreren</NuxtLink>
      </p>
      <p class="form-subtext">
        Wachtwoord vergeten?
        <button
          type="button"
          class="form-subtext__link"
          @click="openForgotPassword"
        >
          Reset aanvragen
        </button>
      </p>
    </div>

    <ForgotPasswordModal
      v-if="showForgotPassword"
      :initial-email="email"
      @close="showForgotPassword = false"
    />
  </form>
</template>

<script setup lang="ts">
import ForgotPasswordModal from "~/components/auth/ForgotPasswordModal.vue";
import { translateErrorMessage } from "~/utils/translateErrorMessage";

const email = ref("");
const password = ref("");
const error = ref<string | null>(null);
const loading = ref(false);
const showForgotPassword = ref(false);

const { login } = useAuth();
const router = useRouter();

async function onSubmit() {
  error.value = null;
  loading.value = true;
  try {
    await login(email.value, password.value);
    router.push("/spel");
  } catch (e: any) {
    error.value = translateErrorMessage(e, { fallback: "Inloggen mislukt" });
  } finally {
    loading.value = false;
  }
}

function openForgotPassword() {
  showForgotPassword.value = true;
}
</script>

<style scoped>
.auth-form {
  margin-top: 0;
}
.auth-mark {
  margin: 0 auto 0.6rem;
}
.form-subtext__link {
  appearance: none;
  border: none;
  background: none;
  padding: 0;
  margin-left: 0.35rem;
  color: var(--color-primary, #003366);
  font-weight: 600;
  cursor: pointer;
  text-decoration: underline;
  text-decoration-thickness: 2px;
}

.form-subtext__link:hover,
.form-subtext__link:focus-visible {
  color: var(--color-accent, #ff5003);
}
</style>
