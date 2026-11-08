<script setup lang="ts">
import { useAuthStore } from "~/stores/auth";
import type { VoteReport } from "~/types/voteReports";
withDefaults(defineProps<{ floating?: boolean }>(), { floating: false });
const auth = useAuthStore();
const api = useApi();
const allowed = ref(false);
const pending = ref(false);
const error = ref("");
const model = ref("");
const isDevelopment = import.meta.dev;
let check = 0;
let disposed = false;
let requestKey = "";
async function access() {
  const current = ++check;
  allowed.value = false;
  if (!import.meta.dev || !auth.isLoggedIn) return;
  try {
    const result = await api.get<{ available: boolean; model?: string }>(
      "/me/reports/dev-access",
    );
    if (!disposed && check === current) {
      allowed.value = result.available === true;
      model.value = result.model || "OPENAI_MODEL";
    }
  } catch {
    /* No dev control when the server does not explicitly allow it. */
  }
}
async function generate() {
  if (!import.meta.dev || !allowed.value || pending.value) return;
  pending.value = true;
  error.value = "";
  requestKey ||= crypto.randomUUID();
  try {
    const report = await api.post<VoteReport>(
      "/me/reports/dev",
      {
        request_key: requestKey,
      },
      { signal: AbortSignal.timeout(60000) },
    );
    await navigateTo({
      path: "/kompas/rapport",
      query: { dev_report: report.id },
    });
    requestKey = "";
  } catch (e) {
    requestKey = "";
    error.value =
      e instanceof Error
        ? e.message
        : "Het devrapport kon niet worden gemaakt.";
  } finally {
    pending.value = false;
  }
}
onMounted(access);
watch(() => auth.token, access);
onBeforeUnmount(() => {
  disposed = true;
  check++;
});
</script>
<template>
  <aside
    v-if="isDevelopment && allowed"
    class="dev-report"
    :class="{ 'dev-report--floating': floating }"
    aria-label="Tijdelijke ontwikkeltool"
  >
    <span class="dev-report__badge">ALLEEN LOCAL DEV</span>
    <button
      class="dev-report__button"
      type="button"
      :disabled="pending"
      @click="generate"
    >
      <span aria-hidden="true">🧪</span
      ><strong>{{
        pending ? "Even brouwen…" : "BROUW MIJN DEV-RAPPORT"
      }}</strong
      ><span>{{
        pending
          ? "Je eigen antwoorden worden opgemaakt"
          : "Geen rapportbetaling · wel OpenAI-tokenkosten"
      }}</span>
    </button>
    <p>
      OpenAI-model: <strong>{{ model }}</strong> uit je backend-.env. Gebruikt
      je bestaande OPENAI_API_KEY. Het rapport verloopt na één uur; de knop
      verdwijnt in de productiebuild.
    </p>
    <p v-if="error" role="alert">{{ error }}</p>
  </aside>
</template>
<style scoped>
.dev-report {
  border: 3px dashed #8a53e4;
  border-radius: 22px;
  padding: 1.1rem;
  background: repeating-linear-gradient(
    135deg,
    #fff7c9,
    #fff7c9 12px,
    #fffbe5 12px,
    #fffbe5 24px
  );
  box-shadow: 5px 6px 0 #dcccf6;
  color: #35195c;
  margin: 1.2rem 0 1.8rem;
  text-align: center;
}
.dev-report__badge {
  display: inline-block;
  font-size: 0.6rem;
  font-weight: 800;
  letter-spacing: 0.13em;
  background: #3a1f68;
  color: white;
  border-radius: 6px;
  padding: 0.3rem 0.6rem;
  margin-bottom: 0.7rem;
}
.dev-report__button {
  display: grid;
  gap: 0.55rem;
  place-items: center;
  width: 100%;
  padding: 1.3rem 0.9rem;
  border: 2px solid #432073;
  border-radius: 15px;
  background: #7a38ca;
  color: white;
  box-shadow: 0 6px 0 #3b185f;
  transform: rotate(-1.5deg);
  min-height: 140px;
}
.dev-report__button > span:first-child {
  font-size: 2.4rem;
  line-height: 1;
}
.dev-report__button strong {
  font-size: 1rem;
  letter-spacing: 0.025em;
  line-height: 1.4;
}
.dev-report__button > span:last-child {
  font-size: 0.64rem;
}
.dev-report__button:hover:not(:disabled) {
  background: #6627b3;
}
.dev-report__button:active:not(:disabled) {
  transform: rotate(0) translateY(3px);
  box-shadow: 0 3px 0 #3b185f;
}
.dev-report p {
  font-size: 0.69rem;
  margin: 1.1rem 0 0;
  line-height: 1.65;
}
.dev-report p[role="alert"] {
  color: #8f0927;
}
@media (min-width: 1320px) {
  .dev-report--floating {
    position: fixed;
    width: 225px;
    top: 110px;
    right: 22px;
    z-index: 40;
    margin: 0;
  }
}
</style>
