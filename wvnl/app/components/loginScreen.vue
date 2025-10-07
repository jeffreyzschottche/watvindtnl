<template>
  <form class="max-w-sm mx-auto space-y-4" @submit.prevent="onSubmit">
    <h1 class="text-2xl font-semibold">Inloggen</h1>

    <div>
      <label class="block text-sm mb-1">E-mail</label>
      <input v-model="email" type="email" class="input" required />
    </div>

    <div>
      <label class="block text-sm mb-1">Wachtwoord</label>
      <input v-model="password" type="password" class="input" required />
    </div>

    <p v-if="error" class="text-red-600 text-sm">{{ error }}</p>

    <button class="btn w-full" :disabled="loading">
      {{ loading ? "Bezig..." : "Log in" }}
    </button>

    <p class="text-sm text-center">
      Nog geen account?
      <NuxtLink class="underline" to="/register">Registreren</NuxtLink>
    </p>
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

<style scoped>
.input {
  @apply w-full border rounded px-3 py-2;
}
.btn {
  @apply bg-black text-white rounded px-4 py-2;
}
</style>
