<script setup lang="ts">
import CompassExamples from "~/components/compass/CompassExamples.vue";
import CompassProgress from "~/components/compass/CompassProgress.vue";
import AppIcon from "~/components/ui/AppIcon.vue";
import type { CompassExample, ReportProduct } from "~/types/voteReports";

definePageMeta({ alias: "/kompas" });

const auth = useAuthStore();
const api = useApi();
const showExamples = computed(() => !auth.isLoggedIn);
const {
  data: examples,
  status: examplesStatus,
  refresh: refreshExamples,
} = useAsyncData(
  "compass-examples",
  async () =>
    showExamples.value ? api.get<CompassExample[]>("/reports/examples") : [],
  { lazy: true, watch: [showExamples] },
);
const product = ref<ReportProduct | null>(null);
onMounted(async () => {
  try {
    product.value = await api.get<ReportProduct>("/reports/product");
  } catch {
    // The example gallery can still be used while product availability is unknown.
  }
});

const insights = [
  {
    icon: "compass",
    title: "Je politieke type",
    description:
      "Zie welk type het best bij het patroon in je antwoorden past en welke themascores erbij horen.",
  },
  {
    icon: "grid",
    title: "Jouw afwegingen",
    description:
      "Ontdek op welke thema’s je keuzes een duidelijke richting hebben en waar je antwoorden gemengd zijn.",
  },
  {
    icon: "shield",
    title: "Historische stemovereenkomsten",
    description:
      "Vergelijk je keuzes met partijstemmen wanneer er voldoende gekoppelde officiële stemmingen zijn.",
  },
];

useSeoMeta({
  title: "Jouw politieke kompas | WatDenktNederland",
  description:
    "Verken de zestien kompastypen en ontdek wat een persoonlijk stemrapport over je politieke keuzes laat zien.",
});
</script>

<template>
  <div class="compass-page container">
    <header class="compass-page__intro">
      <div>
        <h1>Jouw politieke kompas</h1>
        <p v-if="showExamples">
          Ontdek wat je antwoorden samen zeggen over je politieke keuzes.
        </p>
      </div>
      <NuxtLink
        v-if="showExamples"
        to="/profiel/mijn-kompas"
        class="button button-red"
      >
        Naar mijn kompas <AppIcon name="arrow" :size="18" />
      </NuxtLink>
    </header>

    <template v-if="showExamples">
      <CompassExamples v-if="examples?.length" :examples="examples" />
      <div v-else class="compass-page__examples-status">
        <template v-if="examplesStatus === 'error'">
          <p>De voorbeeldtypen konden niet worden opgehaald.</p>
          <button
            type="button"
            class="button button-secondary"
            @click="refreshExamples()"
          >
            Opnieuw proberen
          </button>
          <a href="/examples/WDNL-kompas-voorbeeld.pdf"
            >Bekijk het voorbeeldrapport (PDF)</a
          >
        </template>
        <p v-else role="status">Voorbeeldrapporten worden geladen…</p>
      </div>

      <section
        class="compass-page__learn"
        aria-labelledby="compass-insights-title"
      >
        <h2 id="compass-insights-title">Wat haal je uit je rapport?</h2>
        <div class="compass-page__insights">
          <article v-for="insight in insights" :key="insight.title">
            <span class="compass-page__icon"
              ><AppIcon :name="insight.icon"
            /></span>
            <h3>{{ insight.title }}</h3>
            <p>{{ insight.description }}</p>
          </article>
        </div>
        <NuxtLink to="/methodologie" class="compass-page__method">
          Zo worden de scores berekend <AppIcon name="arrow" :size="16" />
        </NuxtLink>
      </section>
    </template>

    <section class="compass-page__own" aria-labelledby="compass-own-title">
      <div>
        <h2 id="compass-own-title">Je persoonlijke rapport</h2>
        <p>
          Je krijgt je kompas als A4-pdf. Een gekocht rapport blijft
          beschikbaar. Een nieuw rapport kost €1,99.
        </p>
        <p v-if="product?.checkout_available === false">
          De verkoop wordt nog voorbereid.
        </p>
        <NuxtLink
          :to="auth.isLoggedIn ? '/profiel/mijn-kompas' : '/spel'"
          class="button button-secondary"
        >
          {{ auth.isLoggedIn ? "Bekijk mijn rapporten" : "Begin met stemmen" }}
          <AppIcon name="arrow" :size="18" />
        </NuxtLink>
      </div>
      <CompassProgress v-if="auth.isLoggedIn" variant="card" />
    </section>
  </div>
</template>

<style scoped>
.compass-page {
  display: grid;
  gap: clamp(2.3rem, 5vw, 4.25rem);
  padding-block: clamp(2rem, 4vw, 3rem) 4rem;
  color: #001f4d;
}
.compass-page__intro {
  display: flex;
  align-items: end;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 1.1rem 2rem;
  margin-bottom: -1rem;
}
.compass-page h1 {
  margin: 0 0 0.65rem;
  font-size: clamp(2.45rem, 5.6vw, 4.2rem);
  font-weight: 800;
  letter-spacing: -0.06em;
  line-height: 1.06;
}
.compass-page__intro p {
  margin: 0;
  color: #4e617b;
  font-size: clamp(0.96rem, 2vw, 1.12rem);
  line-height: 1.65;
}
.compass-page__examples-status {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.8rem 1.1rem;
  padding: 1.25rem;
  border: 1px solid #dce5ef;
  border-radius: 1rem;
  background: #fff;
}
.compass-page__examples-status p {
  margin: 0;
  color: #4e617b;
}
.compass-page__learn h2,
.compass-page__own h2 {
  margin: 0 0 1rem;
  font-size: clamp(1.5rem, 3.5vw, 2rem);
  letter-spacing: -0.04em;
}
.compass-page__insights {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 0.9rem;
}
.compass-page__insights article {
  padding: 1.3rem;
  border: 1px solid #dce5ef;
  border-radius: 19px;
  background: white;
}
.compass-page__icon {
  display: grid;
  place-items: center;
  width: 44px;
  height: 44px;
  border-radius: 11px;
  color: #0057b8;
  background: #edf3ff;
}
.compass-page__insights h3 {
  margin: 0.85rem 0 0.45rem;
  font-size: 1.05rem;
}
.compass-page__insights p,
.compass-page__own p {
  margin: 0;
  color: #4e617b;
  font-size: 0.9rem;
  line-height: 1.65;
}
.compass-page__method {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  margin-top: 1.25rem;
  color: #0057b8;
  font-size: 0.86rem;
  font-weight: 750;
  text-underline-offset: 3px;
}
.compass-page__own {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(min(100%, 350px), 1fr));
  align-items: center;
  gap: 1.5rem;
  padding: clamp(1.3rem, 4vw, 2.25rem);
  border: 1px solid #dce5ef;
  border-radius: 22px;
  background: #edf3fc;
}
.compass-page__own h2 {
  margin-bottom: 0.6rem;
}
.compass-page__own .button {
  margin-top: 1rem;
}
@media (max-width: 720px) {
  .compass-page__insights {
    grid-template-columns: 1fr;
  }
}
</style>
