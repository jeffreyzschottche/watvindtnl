<script setup lang="ts">
definePageMeta({ middleware: "auth" });
import ProfileVoteHistory from "~/components/profile/ProfileVoteHistory.vue";
useSeoMeta({
  title: "Mijn stemmen | WatDenktNederland",
  robots: "noindex, nofollow",
});
</script>
<template>
  <section class="container history-page">
    <NuxtLink to="/profiel" class="history-back">← Mijn profiel</NuxtLink
    ><ProfileVoteHistory />
  </section>
</template>
<style scoped>
.history-page {
  max-width: 900px;
  padding-block: 35px 60px;
}
.history-back {
  display: block;
  font-size: 0.8rem;
  color: #63738a;
  margin-bottom: 1.5rem;
}
</style>
