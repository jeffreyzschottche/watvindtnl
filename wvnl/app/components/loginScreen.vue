<template>
  <form class="form form-card auth-form" @submit.prevent="onSubmit">
    <header class="form-header">
      <h2>Inloggen</h2>
      <p>Voer je e-mailadres en wachtwoord in om verder te gaan.</p>
    </header>

    <label>
      <span>E-mailadres</span>
      <input v-model="email" type="email" autocomplete="email" required />
    </label>

    <label>
      <span>Wachtwoord</span>
      <input
        v-model="password"
        type="password"
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
        <NuxtLink to="/register">Registreren</NuxtLink>
      </p>
    </div>
  </form>
</template>

<script setup lang="ts">
const email = ref("");
const password = ref("");
const error = ref<string | null>(null);
const loading = ref(false);

const { login } = useAuth();
const router = useRouter();

async function onSubmit() {
  error.value = null;
  loading.value = true;
  try {
    await login(email.value, password.value);
    router.push("/");
  } catch (e: any) {
    error.value = e?.message || "Inloggen mislukt";
  } finally {
    loading.value = false;
  }
}
</script>
