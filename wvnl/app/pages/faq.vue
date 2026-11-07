<script setup lang="ts">
import { faqItems } from "~/data/faq";
useSeoMeta({ title: "Veelgestelde vragen | WatDenktNederland" });
</script>
<template>
  <article class="info-layout">
    <header>
      <p class="eyebrow">Goed om te weten</p>
      <h1>Veelgestelde vragen</h1>
      <p>Heldere antwoorden over stemmen, de uitslagen en je account.</p>
    </header>
    <div class="info-body faq-list">
      <details v-for="item in faqItems" :key="item.question">
        <summary>{{ item.question }}</summary>
        <p>{{ item.answer }}</p>
      </details>
    </div>
    <p style="margin-top: 1.5rem; font-size: 0.9rem">
      Staat je vraag er niet bij?
      <NuxtLink to="/contact">Neem contact op →</NuxtLink>
    </p>
  </article>
</template>
