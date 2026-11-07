<script setup lang="ts">
definePageMeta({ alias: "/over-ons" });
useSeoMeta({
  title: "Over ons | WatDenktNederland",
  description:
    "Meer begrip begint bij een goede vraag. Lees waarom WatDenktNederland bestaat.",
});
</script>
<template>
  <article class="info-layout">
    <header>
      <p class="eyebrow">Achter WatDenktNederland</p>
      <h1>Meer begrip begint<br />bij een goede vraag.</h1>
      <p>
        Wat er in Nederland gebeurt, raakt ons allemaal. Maar een eigen mening vormen hoeft niet
        ingewikkeld te zijn.
      </p>
    </header>
    <div class="info-body">
      <h2>Ruimte voor jouw perspectief</h2>
      <p>
        WatDenktNederland maakt politieke en maatschappelijke onderwerpen toegankelijk. Je leest een
        stelling, bekijkt de argumenten van beide kanten en kiest wat jij vindt.
        Daarna ontdek je hoe andere deelnemers erover denken.
      </p>
      <h2>Nieuwsgierigheid boven gelijk krijgen</h2>
      <p>
        We willen een plek bieden waar je je eigen ideeën onderzoekt en ook de
        andere kant beter leert begrijpen. Je hoeft geen politieke voorkennis te
        hebben. En van mening verschillen mag.
      </p>
      <h2>Helder over de uitslag</h2>
      <p>
        Onze resultaten laten de mening van WDNL-deelnemers zien. Ze zijn geen
        representatieve peiling van Nederland. We houden de stemuitslag en
        officiële politieke besluitvorming uit elkaar.
      </p>
      <h2>Een platform in ontwikkeling</h2>
      <p>
        We verbeteren de broninformatie en werken aan een vernieuwd politiek
        kompas. Heb je een vraag, zie je een fout of heb je een suggestie?
        <NuxtLink to="/contact">Laat het ons weten</NuxtLink>.
      </p>
    </div>
    <div style="margin-top: 1.5rem; display: flex; gap: 1rem; flex-wrap: wrap">
      <NuxtLink to="/spel" class="button button-red"
        >Begin met stemmen →</NuxtLink
      ><NuxtLink to="/methodologie" class="button button-secondary"
        >Onze werkwijze</NuxtLink
      >
    </div>
  </article>
</template>
