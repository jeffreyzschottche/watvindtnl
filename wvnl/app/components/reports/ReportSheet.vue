<script setup lang="ts">
import type { VoteReport } from "~/types/voteReports";
const props = defineProps<{ report: VoteReport }>();
const snapshot = computed(() => props.report.snapshot);
const date = computed(() =>
  new Intl.DateTimeFormat("nl-NL", { dateStyle: "long" }).format(
    new Date(snapshot.value?.generated_at || props.report.created_at),
  ),
);
const sides = [
  { key: "disagree" as const, label: "Tegen", color: "#E1062E" },
  { key: "neutral" as const, label: "Historisch neutraal", color: "#8193aa" },
  { key: "agree" as const, label: "Voor", color: "#0057B8" },
];
</script>
<template>
  <article
    v-if="snapshot && report.narrative"
    class="report-sheet"
    aria-label="Jouw persoonlijke stemrapport"
  >
    <header>
      <strong>WatDenkt<span>Nederland</span></strong
      ><span>JOUW MENING, BIJ ELKAAR</span>
    </header>
    <section class="report-sheet__intro">
      <p>{{ date }}</p>
      <h2>Jouw stemoverzicht<span>.</span></h2>
      <p>{{ report.narrative.summary }}</p>
    </section>
    <div class="report-sheet__stats">
      <div v-for="side in sides" :key="side.key">
        <strong :style="{ color: side.color }">{{
          snapshot.counts[side.key]
        }}</strong
        ><span>{{ side.label }}</span>
      </div>
    </div>
    <div
      class="report-sheet__bar"
      role="img"
      :aria-label="`${snapshot.counts.disagree} tegen, ${snapshot.counts.agree} voor en ${snapshot.counts.neutral} historisch neutraal`"
    >
      <span
        v-for="side in sides"
        :key="side.key"
        :style="{
          width: `${(100 * snapshot.counts[side.key]) / Math.max(1, snapshot.total)}%`,
          background: side.color,
        }"
      />
    </div>
    <p class="report-sheet__note">
      {{ snapshot.total }} vastgelegde antwoorden. Deze verhouding is geen
      links-rechtsscore.
    </p>
    <h3>Een greep uit je laatste antwoorden</h3>
    <ul class="report-sheet__votes">
      <li v-for="entry in snapshot.entries.slice(-5)" :key="entry.id">
        <NuxtLink :to="`/stellingen/${entry.slug}`">{{ entry.title }}</NuxtLink
        ><strong>{{
          { agree: "Voor", disagree: "Tegen", neutral: "Neutraal" }[
            entry.choice
          ]
        }}</strong>
      </li>
    </ul>
    <h3>Overeenkomsten bij gekoppelde moties</h3>
    <ul v-if="snapshot.party_matches.length" class="report-sheet__matches">
      <li v-for="party in snapshot.party_matches" :key="party.name">
        <strong>{{ party.name }} · {{ party.percentage }}%</strong
        ><span
          >{{ party.matched }} van {{ party.compared }} vergelijkbare
          moties</span
        >
      </li>
    </ul>
    <p v-else class="report-sheet__note">
      Nog onvoldoende gecontroleerde motiekoppelingen om een partijovereenkomst
      te tonen.
    </p>
    <h3>Om over na te denken</h3>
    <p>{{ report.narrative.reflection }}</p>
    <footer>
      Methode {{ snapshot.methodology_version }}. Geen stemadvies,
      psychologische test of representatieve peiling.
      <NuxtLink to="/methodologie">Lees hoe dit overzicht werkt.</NuxtLink>
    </footer>
  </article>
</template>
<style scoped>
.report-sheet {
  max-width: 795px;
  margin: auto;
  padding: clamp(1.3rem, 5vw, 3.3rem);
  background: white;
  border: 1px solid #dce3ee;
  border-radius: 18px;
  box-shadow:
    0 7px 0 #e5edf8,
    0 22px 60px #001f4d0a;
  color: #001f4d;
}
.report-sheet header {
  display: flex;
  flex-wrap: wrap;
  gap: 0.7rem;
  justify-content: space-between;
  align-items: center;
}
.report-sheet header strong {
  letter-spacing: -0.04em;
  font-size: 1.15rem;
}
.report-sheet header strong span {
  color: #0057b8;
}
.report-sheet header > span {
  font-size: 0.55rem;
  letter-spacing: 0.12em;
  color: #526277;
}
.report-sheet__intro {
  background: #edf4ff;
  border-radius: 17px;
  padding: 1.4rem;
  margin: 1.8rem 0 1.2rem;
}
.report-sheet__intro > p:first-child {
  font-size: 0.7rem;
  color: #526277;
}
.report-sheet__intro h2 {
  font-size: clamp(1.8rem, 4vw, 2.6rem);
  margin: 0.4rem 0 1rem;
}
.report-sheet__intro h2 span {
  color: #e1062e;
}
.report-sheet p {
  font-size: 0.82rem;
  line-height: 1.8;
}
.report-sheet__stats {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 0.5rem;
  text-align: center;
}
.report-sheet__stats > div {
  padding: 1rem 0.3rem;
  background: #f6f8fb;
  border-radius: 12px;
  display: grid;
}
.report-sheet__stats strong {
  font-size: 1.8rem;
}
.report-sheet__stats span {
  font-size: 0.65rem;
  color: #526277;
}
.report-sheet__bar {
  display: flex;
  height: 18px;
  border-radius: 20px;
  overflow: hidden;
  margin: 1.3rem 0 0.7rem;
  background: #f5f7fa;
}
.report-sheet__note {
  color: #526277;
  font-size: 0.72rem;
}
.report-sheet h3 {
  font-size: 1rem;
  margin: 1.8rem 0 0.8rem;
}
.report-sheet ul {
  padding: 0;
  margin: 0;
  list-style: none;
}
.report-sheet__votes li {
  display: flex;
  justify-content: space-between;
  gap: 1rem;
  padding: 0.75rem 0;
  border-bottom: 1px solid #e8edf5;
  font-size: 0.74rem;
}
.report-sheet__votes a {
  min-width: 0;
}
.report-sheet__votes strong {
  white-space: nowrap;
}
.report-sheet__matches li {
  display: grid;
  gap: 0.2rem;
  margin-bottom: 0.6rem;
  font-size: 0.75rem;
}
.report-sheet__matches span {
  font-size: 0.68rem;
  color: #526277;
}
.report-sheet footer {
  margin-top: 2rem;
  border-top: 1px solid #e4eaf3;
  padding-top: 1rem;
  color: #526277;
  font-size: 0.62rem;
}
@media print {
  .report-sheet {
    box-shadow: none;
    border: 0;
    padding: 0;
  }
}
</style>
