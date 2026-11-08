<script setup lang="ts">
definePageMeta({ alias: "/kompas" });
import MotionScene from "~/components/art/MotionScene.vue";
import type { ReportProduct } from "~/types/voteReports";
const product = ref<ReportProduct | null>(null);
onMounted(async () => {
  try {
    product.value = await useApi().get<ReportProduct>("/reports/product");
  } catch {
    /* Product remains honestly unavailable. */
  }
});
import AppIcon from "~/components/ui/AppIcon.vue";
useSeoMeta({
  title: "Jouw politieke kompas | WatDenktNederland",
  description:
    "Leer de samenhang in je politieke standpunten kennen. Ontdek het persoonlijke kompas van WatDenktNederland.",
});
</script>
<template>
  <div class="compass-page container">
    <section class="compass-page__intro" data-reveal>
      <p class="eyebrow">Jouw persoonlijke richting</p>
      <h1>Leer jezelf<br /><span>beter kennen.</span></h1>
      <p class="compass-page__lead">
        Wat zeggen jouw keuzes samen? Je persoonlijke stemrapport bundelt je
        antwoorden in een visueel A4-rapport met je politieke type, themascores
        en toelichting.
      </p>
      <ul>
        <li>
          <AppIcon name="check" :size="18" /> Je politieke type en gewogen
          themascores
        </li>
        <li>
          <AppIcon name="check" :size="18" /> Partijovereenkomsten waar
          voldoende brondata bestaat
        </li>
        <li>
          <AppIcon name="check" :size="18" /> Een vast persoonlijk A4-rapport
          als PDF
        </li>
      </ul>
      <NuxtLink to="/profiel/mijn-kompas" class="button button-red"
        >Bekijk jouw rapportmogelijkheden <AppIcon name="arrow" :size="18"
      /></NuxtLink>
      <p>
        <a
          href="/examples/WDNL-kompas-voorbeeld.pdf"
          download
          class="compass-page__example"
          >Download voorbeeldrapport (PDF) ↗</a
        ><br /><small class="compass-page__example-note"
          >Opmaakvoorbeeld met fictieve antwoorden en partijen.</small
        >
      </p>
      <p class="compass-page__status">
        €1,99 per nieuw rapport. Een gekocht rapport blijft beschikbaar; later
        opnieuw laten maken is een nieuwe aankoop.
        {{
          product?.checkout_available
            ? ""
            : "De verkoop wordt nog voorbereid; je kunt nu niet betalen."
        }}
      </p>
    </section>
    <aside class="compass-page__art" data-reveal>
      <MotionScene kind="compass" priority />
      <div class="compass-page__art-caption">
        <span aria-hidden="true">✦</span
        ><strong>Je eigen richting vinden.</strong
        ><span>Dat begint bij jouw mening.</span>
      </div>
    </aside>
    <section class="compass-page__explain">
      <h2>Je bent meer dan een percentage.</h2>
      <p>
        Een politiek profiel is een hulpmiddel om over je eigen keuzes na te
        denken. Het is geen psychologische test of stemadvies. We leggen uit
        welke antwoorden worden gebruikt en hoe beschikbare overeenkomsten
        worden berekend. De verhouding voor/tegen is op zichzelf geen
        links-rechtsscore.
      </p>
      <NuxtLink to="/methodologie">Lees meer over onze werkwijze →</NuxtLink>
    </section>
  </div>
</template>
<style scoped>
.compass-page {
  display: grid;
  grid-template-columns: 1.15fr 1fr;
  gap: 4rem;
  padding-block: 65px;
  align-items: center;
}
.compass-page h1 {
  font-size: clamp(2.6rem, 5vw, 4rem);
  margin: 0 0 1.4rem;
  line-height: 1.07;
}
.compass-page h1 span {
  color: #0057b8;
}
.compass-page__lead {
  color: #63738a;
  font-size: 1rem;
  line-height: 1.9;
  max-width: 46ch;
}
.compass-page ul {
  display: grid;
  gap: 0.8rem;
  padding: 0;
  list-style: none;
  margin: 1.5rem 0 2rem;
  font-size: 0.87rem;
}
.compass-page li {
  display: flex;
  align-items: center;
  gap: 0.65rem;
}
.compass-page li svg {
  color: #24743b;
}
.compass-page__example {
  font-size: 0.85rem;
  font-weight: 750;
  color: #0057b8;
  text-decoration: underline;
  text-underline-offset: 3px;
}
.compass-page__example-note {
  color: #63738a;
  font-size: 0.7rem;
}
.compass-page__status {
  font-size: 0.75rem;
  color: #63738a;
  margin: 1rem 0 0;
  max-width: 49ch;
}
.compass-page__card {
  padding: 2.5rem;
  text-align: center;
  background: #fff;
  border: 1px solid #dce3ee;
  border-radius: 20px;
  box-shadow: 0 12px 50px #0057b808;
}
.compass-page__icon {
  color: #0057b8;
  margin: 0 auto 1.5rem;
  width: 130px;
  height: 130px;
  background: #edf3ff;
  border-radius: 50%;
  display: grid;
  place-items: center;
}
.compass-page__tag {
  font-size: 0.62rem;
  font-weight: 750;
  text-transform: uppercase;
  letter-spacing: 0.12em;
  color: #0057b8;
}
.compass-page__card h2 {
  font-size: 1.65rem;
  margin: 0.9rem 0;
}
.compass-page__card > p {
  color: #63738a;
  font-size: 0.83rem;
}
.compass-page__line {
  display: flex;
  align-items: center;
  justify-content: space-between;
  border-top: 1px solid #e5eaf1;
  padding: 0.85rem 0;
  font-size: 0.8rem;
}
.compass-page__line svg {
  color: #0057b8;
}
.compass-page__explain {
  grid-column: 1/-1;
  max-width: 750px;
  text-align: center;
  margin: 1rem auto 0;
}
.compass-page__explain h2 {
  font-size: 1.8rem;
}
.compass-page__explain p {
  color: #63738a;
  font-size: 0.92rem;
}
.compass-page__explain a {
  font-size: 0.85rem;
}
@media (max-width: 720px) {
  .compass-page {
    grid-template-columns: 1fr;
    gap: 2.5rem;
    padding-block: 40px;
  }
  .compass-page__card {
    padding: 1.7rem;
  }
  .compass-page__explain {
    grid-column: auto;
  }
}

.compass-page {
  gap: 3rem;
}
.compass-page h1 {
  font-weight: 800;
  font-size: clamp(2.6rem, 5.2vw, 4.8rem);
  letter-spacing: -0.06em;
  line-height: 1.07;
}
.compass-page__art {
  min-width: 0;
}
.compass-page__art-caption {
  display: grid;
  justify-items: center;
  gap: 0.3rem;
  color: #526d90;
  font-size: 0.7rem;
  margin-top: 1rem;
}
.compass-page__art-caption > strong {
  font-size: 0.95rem;
  color: #001f4d;
}
.compass-page__art-caption > span:first-child {
  color: #0057b8;
  font-size: 24px;
}
.compass-page__explain {
  border-radius: 22px;
  padding: 2rem;
  border: 1px solid #dce7f5;
  background: #f0f5fd;
  box-shadow: 0 5px 0 #e5edf8;
}
@media (max-width: 720px) {
  .compass-page {
    gap: 2rem;
  }
  .compass-page h1 {
    font-size: 3rem;
  }
  .compass-page__art {
    max-width: 380px;
    width: 100%;
    margin-inline: auto;
  }
  .compass-page__explain {
    padding: 1.4rem;
  }
}
</style>
