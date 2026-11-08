<script setup lang="ts">
import { Capacitor } from "@capacitor/core";
import ReportSheet from "~/components/reports/ReportSheet.vue";
import type { ReportOverview, VoteReport } from "~/types/voteReports";
definePageMeta({ middleware: "auth", alias: "/profiel/mijn-kompas" });
const api = useApi();
const auth = useAuthStore();
const route = useRoute();
const overview = ref<ReportOverview | null>(null);
const report = ref<VoteReport | null>(null);
const loading = ref(true);
const pending = ref(false);
const error = ref("");
const politicalConsent = ref(false);
const aiConsent = ref(false);
const requestKey = ref("");
const isNative = ref(false);
let timer: ReturnType<typeof setTimeout> | undefined;
let disposed = false;
let polls = 0;
let detailRequest = 0;
const canBuy = computed(
  () =>
    !!overview.value &&
    overview.value.vote_count >= overview.value.product.minimum_votes &&
    overview.value.product.checkout_available &&
    !isNative.value,
);
const statusLabels: Record<VoteReport["status"], string> = {
  pending: "Wacht op betaling",
  paid: "Betaling bevestigd · in de wachtrij",
  generating: "Je rapport wordt gemaakt",
  ready: "Rapport beschikbaar",
  failed: "Generatie onderbroken — neem contact op",
  canceled: "Betaling niet afgerond",
  refunded: "Terugbetaald",
};
const date = (value: string) =>
  new Intl.DateTimeFormat("nl-NL", { dateStyle: "medium" }).format(
    new Date(value),
  );
async function openReport(id: string) {
  clearTimeout(timer);
  const request = ++detailRequest;
  error.value = "";
  try {
    const result = await api.get<VoteReport>(
      `/me/reports/${encodeURIComponent(id)}`,
    );
    if (disposed || request !== detailRequest) return;
    report.value = result;
    if (
      !disposed &&
      ["pending", "paid", "generating"].includes(report.value.status) &&
      polls++ < 30
    )
      timer = setTimeout(() => {
        void openReport(id);
      }, 3000);
  } catch {
    error.value = "Dit rapport kon niet worden geladen. Probeer het opnieuw.";
  }
}
async function load() {
  clearTimeout(timer);
  polls = 0;
  loading.value = true;
  error.value = "";
  try {
    overview.value = await api.get<ReportOverview>("/me/reports");
    const id =
      typeof route.query.order === "string"
        ? route.query.order
        : overview.value.orders.find((o) => o.status === "ready")?.id;
    if (id) await openReport(id);
  } catch {
    error.value =
      "Je rapporten konden niet worden geladen. Probeer het opnieuw.";
  } finally {
    loading.value = false;
  }
}
async function buy() {
  if (!canBuy.value || !politicalConsent.value || pending.value) return;
  pending.value = true;
  error.value = "";
  requestKey.value ||= crypto.randomUUID();
  try {
    const order = await api.post<VoteReport>("/me/reports/orders", {
      request_key: requestKey.value,
      political_consent: politicalConsent.value,
      ai_consent: aiConsent.value,
      platform: "web",
    });
    if (order.checkout_url) {
      const url = new URL(order.checkout_url);
      if (url.protocol !== "https:" || url.hostname !== "checkout.stripe.com")
        throw new Error("Onverwachte betaalpagina.");
      window.location.assign(url.href);
    } else await openReport(order.id);
  } catch (e) {
    error.value =
      e instanceof Error
        ? e.message
        : "Betalen kon niet worden gestart. Er is geen rapport ontgrendeld.";
  } finally {
    pending.value = false;
  }
}
async function download() {
  if (!report.value?.download_available || pending.value) return;
  pending.value = true;
  error.value = "";
  try {
    const base = useRuntimeConfig().public.apiBase;
    const response = await fetch(`${base}/me/reports/${report.value.id}/pdf`, {
      headers: {
        Accept: "application/pdf",
        Authorization: `Bearer ${auth.token}`,
      },
      signal: AbortSignal.timeout(20000),
    });
    if (!response.ok)
      throw new Error("Download niet beschikbaar. Probeer het opnieuw.");
    const blob = await response.blob();
    const url = URL.createObjectURL(blob);
    const link = document.createElement("a");
    link.href = url;
    link.download = "WDNL-stemrapport.pdf";
    link.click();
    setTimeout(() => URL.revokeObjectURL(url), 1000);
  } catch (e) {
    error.value = e instanceof Error ? e.message : "Downloaden is niet gelukt.";
  } finally {
    pending.value = false;
  }
}
onMounted(() => {
  isNative.value = Capacitor.isNativePlatform();
  void load();
});
onBeforeUnmount(() => {
  disposed = true;
  clearTimeout(timer);
});
watch(() => route.query.order, load);
useSeoMeta({
  title: "Mijn stemrapport | WatDenktNederland",
  robots: "noindex, nofollow",
});
</script>
<template>
  <section class="reports-page container">
    <NuxtLink to="/profiel" class="reports-page__back">← Mijn profiel</NuxtLink>
    <header class="reports-page__header">
      <div>
        <p class="eyebrow">Jouw mening, bij elkaar</p>
        <h1>Mijn stemrapporten<span>.</span></h1>
        <p>
          Een vast overzicht van je antwoorden. Een nieuw moment, een nieuw
          rapport.
        </p>
      </div>
      <span class="reports-page__price"
        >€ 1,99 <small>per nieuw rapport</small></span
      >
    </header>
    <p v-if="loading" role="status">Je rapporten worden geladen…</p>
    <p v-if="error" role="alert" class="form-error">{{ error }}</p>
    <button
      v-if="error"
      type="button"
      class="button button-secondary"
      @click="load"
    >
      Opnieuw proberen
    </button>
    <template v-if="overview && !loading">
      <aside
        v-if="overview.comparison.suggest_update"
        class="reports-page__update"
      >
        <span aria-hidden="true">✦</span>
        <div>
          <h2>Er zijn nieuwe antwoorden om bij stil te staan.</h2>
          <p>{{ overview.comparison.message }}</p>
          <a href="#nieuw-rapport">Nieuw rapport bekijken · € 1,99 →</a>
        </div>
      </aside>
      <div class="reports-page__layout">
        <section id="nieuw-rapport" class="reports-page__purchase">
          <h2>
            {{
              overview.orders.some((o) => o.status === "ready")
                ? "Een nieuw rapport maken"
                : "Jouw eerste stemrapport"
            }}
          </h2>
          <p>
            {{ overview.vote_count }} stellingen beantwoord. We bundelen je
            antwoorden in een helder A4-overzicht met tellingen, voorbeelden en
            beschikbare partijovereenkomsten.
          </p>
          <p>
            Dit rapport blijft bewaard. Later opnieuw downloaden kost niets; een
            nieuw rapport kost weer € 1,99.
          </p>
          <p
            v-if="overview.vote_count < overview.product.minimum_votes"
            class="reports-page__hint"
          >
            Beantwoord eerst nog
            {{ overview.product.minimum_votes - overview.vote_count }}
            stellingen.
          </p>
          <NuxtLink
            v-if="overview.vote_count < overview.product.minimum_votes"
            to="/spel"
            class="button"
            >Verder met stemmen →</NuxtLink
          >
          <p v-else-if="isNative" class="reports-page__hint">
            Nieuwe rapporten kopen in de app is nog niet beschikbaar. Eerder
            gekochte rapporten kun je hier wel bekijken.
          </p>
          <p
            v-else-if="!overview.product.checkout_available"
            class="reports-page__hint"
          >
            De rapportverkoop wordt voorbereid. Je kunt nu nog niet betalen; er
            wordt niets afgeschreven.
          </p>
          <form v-else @submit.prevent="buy">
            <label class="reports-page__consent"
              ><input
                v-model="politicalConsent"
                type="checkbox"
                required
              /><span
                >Ik geef uitdrukkelijk toestemming om mijn politieke antwoorden
                voor dit persoonlijke rapport te verwerken.
                <NuxtLink to="/privacy">Meer over privacy</NuxtLink>.</span
              ></label
            ><label
              v-if="overview.product.ai_available"
              class="reports-page__consent"
              ><input v-model="aiConsent" type="checkbox" /><span
                >Ik wil ook een korte AI-toelichting. Hiervoor gaat een beperkte
                samenvatting van mijn politieke antwoorden naar
                {{
                  overview.product.ai_provider === "openai"
                    ? "OpenAI"
                    : "DeepSeek"
                }}. Dit is optioneel.</span
              ></label
            ><button
              class="button button-red"
              :disabled="!politicalConsent || pending"
            >
              {{
                pending
                  ? "Betaalpagina openen…"
                  : "Nieuw rapport kopen · € 1,99 →"
              }}
            </button>
          </form>
          <NuxtLink to="/methodologie" class="reports-page__method"
            >Hoe wordt het rapport gemaakt?</NuxtLink
          >
        </section>
        <section class="reports-page__history">
          <h2>Je eerdere aanvragen</h2>
          <p v-if="!overview.orders.length" class="reports-page__hint">
            Je hebt nog geen rapport gekocht.
          </p>
          <button
            v-for="order in overview.orders"
            :key="order.id"
            class="reports-page__order"
            type="button"
            @click="
              polls = 0;
              openReport(order.id);
            "
          >
            <span
              ><strong>{{ date(order.created_at) }}</strong
              ><small
                >{{ order.vote_count }} antwoorden ·
                {{ statusLabels[order.status] }}</small
              ></span
            ><span aria-hidden="true">→</span>
          </button>
        </section>
      </div>
      <section v-if="report" class="reports-page__result">
        <div class="reports-page__result-heading">
          <div>
            <h2>{{ statusLabels[report.status] }}</h2>
            <p
              v-if="
                route.query.betaling === 'geannuleerd' &&
                report.status === 'pending'
              "
            >
              Je hebt de betaalpagina verlaten. Alleen een bevestigde betaling
              ontgrendelt je rapport.
            </p>
            <p
              v-if="['paid', 'generating'].includes(report.status)"
              role="status"
            >
              Je antwoorden staan vast. Zodra de verwerking klaar is verschijnt
              je rapport hier.
            </p>
            <p v-if="report.status === 'failed'">
              Je eerdere betaling blijft geregistreerd. Neem contact op voor
              herstel of terugbetaling; koop hiervoor geen tweede rapport.
            </p>
          </div>
          <button
            v-if="report.download_available"
            class="button"
            :disabled="pending"
            @click="download"
          >
            Download PDF ↓
          </button>
        </div>
        <ReportSheet
          v-if="report.status === 'ready'"
          :report="report"
        /><NuxtLink v-if="report.status === 'failed'" to="/contact"
          >Neem contact op →</NuxtLink
        ><button
          v-if="['paid', 'generating', 'pending'].includes(report.status)"
          class="button button-secondary"
          @click="
            polls = 0;
            openReport(report.id);
          "
        >
          Status vernieuwen
        </button>
      </section>
    </template>
  </section>
</template>
<style scoped>
.reports-page {
  max-width: 1060px;
  padding-block: 35px 70px;
}
.reports-page__back {
  font-size: 0.8rem;
}
.reports-page__header {
  display: flex;
  justify-content: space-between;
  gap: 1.5rem;
  margin: 2rem 0;
  align-items: center;
}
.reports-page h1 {
  font-size: clamp(2rem, 5vw, 3rem);
  margin: 0.6rem 0;
}
.reports-page h1 span {
  color: #e1062e;
}
.reports-page__header p:last-child {
  color: #526277;
  font-size: 0.9rem;
}
.reports-page__price {
  border: 1px solid #dce3ee;
  background: #fff;
  border-radius: 18px;
  padding: 1rem;
  font-size: 1.8rem;
  font-weight: 800;
  white-space: nowrap;
}
.reports-page__price small {
  display: block;
  font-size: 0.65rem;
  color: #526277;
  font-weight: 500;
}
.reports-page__layout {
  display: grid;
  grid-template-columns: 1.15fr 1fr;
  gap: 1.5rem;
}
.reports-page__purchase,
.reports-page__history {
  border: 1px solid #dce3ee;
  background: #fff;
  border-radius: 20px;
  padding: 1.5rem;
}
.reports-page h2 {
  font-size: 1.25rem;
  margin: 0 0 1rem;
}
.reports-page__purchase p {
  color: #526277;
  font-size: 0.83rem;
}
.reports-page__hint {
  background: #f0f5fd;
  padding: 1rem;
  border-radius: 12px;
  color: #526277;
  font-size: 0.8rem;
}
.reports-page__consent {
  display: flex;
  align-items: flex-start;
  gap: 0.7rem;
  font-size: 0.76rem;
  margin: 1.2rem 0;
}
.reports-page__consent input {
  flex-shrink: 0;
  width: 20px;
  height: 20px;
  margin-top: 0.15rem;
}
.reports-page__method {
  display: block;
  margin-top: 1.2rem;
  font-size: 0.77rem;
}
.reports-page__order {
  display: flex;
  justify-content: space-between;
  gap: 1rem;
  align-items: center;
  border: 0;
  border-bottom: 1px solid #e5ebf3;
  background: white;
  width: 100%;
  text-align: left;
  padding: 1rem 0;
  color: #001f4d;
  min-height: 60px;
}
.reports-page__order strong {
  font-size: 0.84rem;
}
.reports-page__order small {
  display: block;
  color: #526277;
  font-size: 0.7rem;
}
.reports-page__update {
  display: flex;
  gap: 1.3rem;
  padding: 1.5rem;
  background: #eaf3ff;
  border: 1px solid #cadcf4;
  border-radius: 20px;
  margin-bottom: 1.5rem;
}
.reports-page__update > span {
  color: #0057b8;
  font-size: 35px;
}
.reports-page__update p,
.reports-page__update a {
  font-size: 0.83rem;
}
.reports-page__result {
  margin-top: 2.5rem;
}
.reports-page__result-heading {
  display: flex;
  justify-content: space-between;
  gap: 1rem;
  align-items: center;
  margin-bottom: 1.5rem;
}
.reports-page__result-heading p {
  font-size: 0.83rem;
  color: #526277;
}
@media (max-width: 720px) {
  .reports-page__header {
    align-items: flex-start;
  }
  .reports-page__price {
    font-size: 1.2rem;
    padding: 0.75rem;
  }
  .reports-page__layout {
    grid-template-columns: 1fr;
  }
  .reports-page__result-heading {
    flex-wrap: wrap;
  }
}
@media (max-width: 360px) {
  .reports-page__header {
    flex-direction: column;
  }
}
</style>
