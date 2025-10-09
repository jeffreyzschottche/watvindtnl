<template>
  <section class="relative isolate overflow-hidden py-16 sm:py-20">
    <div
      class="pointer-events-none absolute inset-0 -z-10 bg-gradient-to-br from-sky-50 via-white to-blue-100"
      aria-hidden="true"
    ></div>
    <div
      class="mx-auto grid w-full max-w-5xl gap-12 px-4 sm:px-6 lg:grid-cols-[1.1fr_1fr] lg:items-center"
    >
      <div class="space-y-6 text-center lg:text-left">
        <p class="inline-flex items-center rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-blue-800">
          Veilig inloggen
        </p>
        <h1 class="text-3xl font-bold text-slate-900 sm:text-4xl">
          Welkom terug bij Wat vindt NL
        </h1>
        <p class="text-base leading-relaxed text-slate-600 sm:text-lg">
          Log in om jouw stem te laten horen, actuele kwesties te volgen en te ontdekken hoe de rest van Nederland denkt. Na het verifiëren van je e-mailadres heb je toegang tot al je persoonlijke voorkeuren en stemgeschiedenis.
        </p>
        <ul class="grid gap-4 text-left text-sm text-slate-600 sm:grid-cols-2">
          <li class="flex items-start gap-3">
            <span class="mt-1 inline-flex h-6 w-6 items-center justify-center rounded-full bg-blue-600 text-xs font-semibold text-white">1</span>
            <span>Volg de nieuwste kwesties en ontdek hoe anderen stemmen.</span>
          </li>
          <li class="flex items-start gap-3">
            <span class="mt-1 inline-flex h-6 w-6 items-center justify-center rounded-full bg-blue-600 text-xs font-semibold text-white">2</span>
            <span>Stem en ontvang meldingen zodra er nieuwe thema's beschikbaar zijn.</span>
          </li>
          <li class="flex items-start gap-3">
            <span class="mt-1 inline-flex h-6 w-6 items-center justify-center rounded-full bg-blue-600 text-xs font-semibold text-white">3</span>
            <span>Beheer je voorkeuren en houd je profiel up-to-date.</span>
          </li>
          <li class="flex items-start gap-3">
            <span class="mt-1 inline-flex h-6 w-6 items-center justify-center rounded-full bg-blue-600 text-xs font-semibold text-white">4</span>
            <span>Ontvang exclusieve inzichten met een premium account.</span>
          </li>
        </ul>
      </div>

      <div
        class="mx-auto w-full max-w-md rounded-2xl border border-slate-200/70 bg-white/90 p-8 shadow-xl backdrop-blur"
      >
        <div v-if="infoMessage" class="rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-slate-700">
          {{ infoMessage }}
        </div>

        <form class="mt-6 space-y-5" @submit.prevent="onSubmit">
          <div>
            <label class="mb-1 block text-sm font-medium text-slate-700" for="email"
              >E-mailadres</label
            >
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

          <div>
            <label class="mb-1 block text-sm font-medium text-slate-700" for="password"
              >Wachtwoord</label
            >
            <input
              id="password"
              v-model="password"
              type="password"
              autocomplete="current-password"
              class="input"
              placeholder="••••••••"
              required
            />
          </div>

          <div class="flex items-center justify-between text-sm">
            <NuxtLink class="text-slate-600 underline transition hover:text-slate-900" to="/register">
              Nog geen account?
            </NuxtLink>
            <NuxtLink class="text-slate-600 underline transition hover:text-slate-900" to="/forgot-password">
              Wachtwoord vergeten?
            </NuxtLink>
          </div>

          <button class="btn w-full" type="submit" :disabled="loading">
            {{ loading ? "Bezig..." : "Inloggen" }}
          </button>
        </form>

        <p v-if="error" class="mt-4 text-sm text-red-600">{{ error }}</p>

        <div
          v-if="showResend"
          class="mt-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-4 text-sm text-slate-700"
        >
          <p class="font-medium text-amber-800">Geen verificatiemail ontvangen?</p>
          <p class="mt-2">
            Controleer eerst je spamfolder. We sturen de link graag opnieuw naar <strong>{{ email }}</strong>.
          </p>
          <div class="mt-4 flex flex-wrap items-center gap-3">
            <button
              type="button"
              class="inline-flex items-center justify-center rounded-full bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-blue-300"
              @click="resendVerification"
              :disabled="resendLoading || !email"
            >
              {{ resendLoading ? "Versturen..." : "Stuur opnieuw" }}
            </button>
            <p v-if="resendError" class="text-red-600">{{ resendError }}</p>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
const email = ref("");
const password = ref("");
const error = ref<string | null>(null);
const loading = ref(false);
const resendLoading = ref(false);
const resendError = ref<string | null>(null);
const dynamicInfo = ref<string | null>(null);

const { login } = useAuth();
const api = useApi();
const router = useRouter();
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

const infoMessage = computed(() => {
  if (dynamicInfo.value) return dynamicInfo.value;
  if (route.query.verify) {
    return "Je account is aangemaakt. Controleer je e-mail en klik op de verificatielink voordat je inlogt.";
  }
  if (route.query.reset) {
    return "Je wachtwoord is vernieuwd. Log in met je nieuwe gegevens.";
  }
  if (route.query.resent) {
    return "We hebben de verificatielink opnieuw verzonden. Controleer je inbox.";
  }
  return null;
});

const showResend = computed(() => error.value === "verifieer eerst je emailadress");

async function onSubmit() {
  error.value = null;
  resendError.value = null;
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

async function resendVerification() {
  if (!email.value) return;

  resendError.value = null;
  dynamicInfo.value = null;
  resendLoading.value = true;

  try {
    const { message } = await api.post<{ message: string }>("/email/resend", {
      email: email.value,
    });
    dynamicInfo.value = message ||
      "Als het e-mailadres bekend is, hebben we een nieuwe verificatiemail verstuurd.";
    error.value = null;
  } catch (e: any) {
    resendError.value = e?.message || "Verzenden mislukt";
  } finally {
    resendLoading.value = false;
  }
}
</script>

<style scoped>
.input {
  @apply w-full rounded-xl border border-slate-200/70 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm transition placeholder:text-slate-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200;
}
.btn {
  @apply inline-flex h-11 items-center justify-center rounded-full bg-slate-900 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-700 disabled:cursor-not-allowed disabled:bg-slate-400;
}
</style>
