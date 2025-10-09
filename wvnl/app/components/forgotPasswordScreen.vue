<template>
  <section class="relative isolate overflow-hidden py-16 sm:py-20">
    <div class="absolute inset-0 -z-10 bg-gradient-to-br from-indigo-50 via-white to-sky-100" aria-hidden="true"></div>
    <div class="mx-auto flex w-full max-w-4xl flex-col-reverse gap-10 px-4 sm:px-6 lg:flex-row lg:items-center">
      <div class="w-full rounded-2xl border border-slate-200/70 bg-white/90 p-8 shadow-xl backdrop-blur">
        <h1 class="text-2xl font-semibold text-slate-900 sm:text-3xl">Wachtwoord vergeten</h1>
        <p class="mt-3 text-sm text-slate-600 sm:text-base">
          Vul je e-mailadres in. We sturen je een e-mail met daarin een link om je wachtwoord opnieuw in te stellen.
        </p>

        <div
          v-if="success"
          class="mt-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800"
        >
          {{ success }}
        </div>

        <div v-if="error" class="mt-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
          {{ error }}
        </div>

        <form class="mt-6 space-y-5" @submit.prevent="onSubmit">
          <div>
            <label class="mb-1 block text-sm font-medium text-slate-700" for="email">E-mailadres</label>
            <input
              id="email"
              v-model="email"
              type="email"
              autocomplete="email"
              class="input"
              placeholder="naam@voorbeeld.nl"
              required
            />
          </div>

          <button class="btn w-full" type="submit" :disabled="loading">
            {{ loading ? "Bezig..." : "Verstuur herstel-link" }}
          </button>
        </form>

        <p class="mt-6 text-center text-sm text-slate-600">
          Toch je wachtwoord herinnerd? <NuxtLink class="underline" to="/login">Terug naar inloggen</NuxtLink>
        </p>
      </div>

      <div class="space-y-6 text-center lg:w-1/2 lg:text-left">
        <p class="inline-flex items-center rounded-full bg-indigo-100 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-indigo-800">
          Ondersteuning
        </p>
        <h2 class="text-3xl font-bold text-slate-900 sm:text-4xl">We helpen je zo weer op weg</h2>
        <p class="text-base leading-relaxed text-slate-600">
          Binnen enkele minuten ontvang je een e-mail met daarin een beveiligde link. Via die link kun je een nieuw wachtwoord kiezen.
        </p>
        <ul class="space-y-4 text-left text-sm text-slate-600">
          <li class="flex items-start gap-3">
            <span class="mt-1 inline-flex h-6 w-6 items-center justify-center rounded-full bg-indigo-600 text-xs font-semibold text-white">1</span>
            <span>Controleer je inbox (en eventueel de spambox) voor een e-mail van Wat vindt NL.</span>
          </li>
          <li class="flex items-start gap-3">
            <span class="mt-1 inline-flex h-6 w-6 items-center justify-center rounded-full bg-indigo-600 text-xs font-semibold text-white">2</span>
            <span>Klik op de link in de e-mail om een nieuw wachtwoord in te stellen.</span>
          </li>
          <li class="flex items-start gap-3">
            <span class="mt-1 inline-flex h-6 w-6 items-center justify-center rounded-full bg-indigo-600 text-xs font-semibold text-white">3</span>
            <span>Log daarna opnieuw in met je nieuwe wachtwoord.</span>
          </li>
        </ul>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
const email = ref("");
const loading = ref(false);
const success = ref<string | null>(null);
const error = ref<string | null>(null);

const api = useApi();
const route = useRoute();

watch(
  () => route.query.email,
  (value) => {
    if (typeof value === "string") {
      email.value = value;
    }
  },
  { immediate: true }
);

async function onSubmit() {
  error.value = null;
  success.value = null;
  loading.value = true;

  try {
    const { message } = await api.post<{ message: string }>("/forgot-password", {
      email: email.value,
    });
    success.value =
      message || "Als het e-mailadres bekend is, ontvang je zo een herstel-link.";
  } catch (e: any) {
    error.value = e?.message || "Versturen mislukt";
  } finally {
    loading.value = false;
  }
}
</script>

<style scoped>
.input {
  @apply w-full rounded-xl border border-slate-200/70 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm transition placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200;
}
.btn {
  @apply inline-flex h-11 items-center justify-center rounded-full bg-indigo-600 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500 disabled:cursor-not-allowed disabled:bg-indigo-300;
}
</style>
