<template>
  <section class="relative isolate overflow-hidden py-16 sm:py-20">
    <div class="absolute inset-0 -z-10 bg-gradient-to-br from-sky-50 via-white to-blue-100" aria-hidden="true"></div>
    <div class="mx-auto flex w-full max-w-4xl flex-col gap-10 px-4 sm:px-6 lg:flex-row lg:items-center">
      <div class="w-full rounded-2xl border border-slate-200/70 bg-white/90 p-8 shadow-xl backdrop-blur">
        <h1 class="text-2xl font-semibold text-slate-900 sm:text-3xl">Nieuw wachtwoord instellen</h1>
        <p class="mt-3 text-sm text-slate-600 sm:text-base">
          Kies een sterk wachtwoord dat je alleen voor Wat vindt NL gebruikt.
        </p>

        <div
          v-if="error"
          class="mt-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
        >
          {{ error }}
        </div>

        <form v-if="hasValidToken" class="mt-6 space-y-5" @submit.prevent="onSubmit">
          <div>
            <label class="mb-1 block text-sm font-medium text-slate-700" for="password">Nieuw wachtwoord</label>
            <input
              id="password"
              v-model="password"
              type="password"
              autocomplete="new-password"
              class="input"
              minlength="8"
              placeholder="••••••••"
              required
            />
          </div>

          <div>
            <label class="mb-1 block text-sm font-medium text-slate-700" for="password_confirmation"
              >Bevestig wachtwoord</label
            >
            <input
              id="password_confirmation"
              v-model="passwordConfirmation"
              type="password"
              autocomplete="new-password"
              class="input"
              minlength="8"
              placeholder="••••••••"
              required
            />
          </div>

          <button class="btn w-full" type="submit" :disabled="loading">
            {{ loading ? "Opslaan..." : "Bewaar nieuw wachtwoord" }}
          </button>
        </form>

        <div v-else class="mt-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-4 text-sm text-amber-800">
          Deze herstel-link is ongeldig of verlopen. Vraag een nieuwe link aan via de
          <NuxtLink class="underline" to="/forgot-password">wachtwoord reset pagina</NuxtLink>.
        </div>

        <p class="mt-6 text-center text-sm text-slate-600">
          Klaar? <NuxtLink class="underline" to="/login">Terug naar inloggen</NuxtLink>
        </p>
      </div>

      <div class="space-y-6 text-center lg:w-1/2 lg:text-left">
        <p class="inline-flex items-center rounded-full bg-sky-100 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-sky-800">
          Veilig en makkelijk
        </p>
        <h2 class="text-3xl font-bold text-slate-900 sm:text-4xl">Maak je account weer veilig</h2>
        <p class="text-base leading-relaxed text-slate-600">
          Gebruik minimaal acht tekens en combineer hoofdletters, kleine letters, cijfers en symbolen voor een sterk wachtwoord.
        </p>
        <ul class="space-y-4 text-left text-sm text-slate-600">
          <li class="flex items-start gap-3">
            <span class="mt-1 inline-flex h-6 w-6 items-center justify-center rounded-full bg-sky-600 text-xs font-semibold text-white">1</span>
            <span>Vul twee keer hetzelfde nieuwe wachtwoord in.</span>
          </li>
          <li class="flex items-start gap-3">
            <span class="mt-1 inline-flex h-6 w-6 items-center justify-center rounded-full bg-sky-600 text-xs font-semibold text-white">2</span>
            <span>Klik op “Bewaar nieuw wachtwoord”.</span>
          </li>
          <li class="flex items-start gap-3">
            <span class="mt-1 inline-flex h-6 w-6 items-center justify-center rounded-full bg-sky-600 text-xs font-semibold text-white">3</span>
            <span>Log opnieuw in met je nieuwe wachtwoord.</span>
          </li>
        </ul>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
const password = ref("");
const passwordConfirmation = ref("");
const loading = ref(false);
const error = ref<string | null>(null);

const api = useApi();
const router = useRouter();
const route = useRoute();

const token = computed(() => (typeof route.query.token === "string" ? route.query.token : ""));
const email = computed(() => (typeof route.query.email === "string" ? route.query.email : ""));
const hasValidToken = computed(() => !!token.value && !!email.value);

async function onSubmit() {
  if (!hasValidToken.value) {
    error.value = "De herstel-link is ongeldig of verlopen.";
    return;
  }

  if (password.value !== passwordConfirmation.value) {
    error.value = "De wachtwoorden komen niet overeen.";
    return;
  }

  error.value = null;
  loading.value = true;

  try {
    await api.post<{ message: string }>("/reset-password", {
      token: token.value,
      email: email.value,
      password: password.value,
      password_confirmation: passwordConfirmation.value,
    });

    router.replace({
      path: "/login",
      query: { reset: "1", email: email.value },
    });
  } catch (e: any) {
    error.value = e?.message || "Opslaan mislukt";
  } finally {
    loading.value = false;
  }
}
</script>

<style scoped>
.input {
  @apply w-full rounded-xl border border-slate-200/70 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm transition placeholder:text-slate-400 focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-200;
}
.btn {
  @apply inline-flex h-11 items-center justify-center rounded-full bg-sky-600 text-sm font-semibold text-white shadow-sm transition hover:bg-sky-500 disabled:cursor-not-allowed disabled:bg-sky-300;
}
</style>
