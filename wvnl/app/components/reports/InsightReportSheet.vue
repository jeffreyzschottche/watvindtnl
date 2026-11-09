<script setup lang="ts">
import type { VoteReport, ReportEntry } from "~/types/voteReports";
const props = defineProps<{ report: VoteReport }>();
const snapshot = computed(() => props.report.snapshot!);
const compass = computed(() => snapshot.value.compass!);
const context = computed(() => snapshot.value.analysis_context!);
const narrative = computed(() => props.report.narrative!);
const votes = computed(
  () => new Map(context.value.votes.map((v) => [v.id, v])),
);
const labels: Record<ReportEntry["choice"], string> = {
  agree: "Voor",
  disagree: "Tegen",
  neutral: "Neutraal",
};
const date = computed(() =>
  new Intl.DateTimeFormat("nl-NL", { dateStyle: "long" }).format(
    new Date(snapshot.value.generated_at),
  ),
);
const dimensions = computed(() => [
  ...compass.value.dimensions,
  compass.value.spectrum,
]);
</script>
<template>
  <article class="insight-report" aria-label="Jouw persoonlijke stemrapport">
    <p v-if="report.is_development" class="insight-report__dev">
      TIJDELIJK DEV-RAPPORT · GEEN AANKOOP
    </p>
    <section class="insight-page" aria-label="Je politieke richting">
      <header class="insight-header">
        <div class="insight-brand">
          <img
            src="/brand/wdnl-mark-concept.svg"
            width="30"
            height="37"
            alt=""
          /><strong>WatDenkt<span>Nederland</span></strong>
        </div>
        <p>{{ date }}<br />Persoonlijk rapport · 01 / 02</p>
      </header>
      <p class="insight-kicker">Jouw keuzes. Jouw richting.</p>
      <h2 class="insight-title">Jouw Politieke Kompas</h2>
      <section class="insight-hero" aria-label="Jouw politieke type">
        <div>
          <p class="insight-kicker">
            {{
              compass.type?.provisional !== false
                ? "Een eerste indicatie"
                : "Jouw politieke type"
            }}
          </p>
          <h3>{{ compass.type?.name || "Jouw keuzes in beeld" }}</h3>
          <p>{{ narrative.summary }}</p>
          <ul v-if="compass.type" class="insight-traits">
            <li v-for="trait in compass.type.traits" :key="trait">
              {{ trait }}
            </li>
          </ul>
        </div>
        <img
          src="/illustrations/reports/compass-sculpture-v3.webp"
          width="260"
          height="260"
          alt=""
        />
      </section>
      <dl class="insight-stats">
        <div>
          <dt>antwoorden vastgelegd</dt>
          <dd>{{ snapshot.total }}</dd>
        </div>
        <div>
          <dt>unieke, eenduidige keuzes</dt>
          <dd>{{ context.eligible_count }}</dd>
        </div>
        <div>
          <dt>met themagewichten</dt>
          <dd>{{ compass.used_votes }}</dd>
        </div>
        <div>
          <dt>neutraal geantwoord</dt>
          <dd>{{ snapshot.counts.neutral }}</dd>
        </div>
      </dl>
      <div class="insight-grid">
        <div>
          <section class="insight-panel">
            <p class="insight-kicker">De richting in je antwoorden</p>
            <h3>Jouw politieke waarden</h3>
            <div
              v-for="dimension in dimensions"
              :key="dimension.id"
              class="insight-score"
            >
              <div class="insight-between">
                <span>{{ dimension.left }}</span
                ><span>{{ dimension.right }}</span>
              </div>
              <div
                v-if="dimension.percentage !== null"
                role="img"
                :aria-label="`${dimension.percentage}% ${dimension.left}, ${dimension.opposite_percentage}% ${dimension.right}; ${dimension.compared} antwoorden${dimension.provisional ? ', voorlopig' : ''}`"
              >
                <div class="insight-track">
                  <span :style="{ width: `${dimension.percentage}%` }" />
                </div>
                <div class="insight-between insight-value">
                  <strong>{{ dimension.percentage }}%</strong
                  ><strong>{{ dimension.opposite_percentage }}%</strong>
                </div>
                <p class="insight-caption">
                  {{ dimension.compared }} verschillende antwoorden<span
                    v-if="dimension.provisional"
                  >
                    · voorlopig</span
                  >
                </p>
              </div>
              <template v-else
                ><div class="insight-track" />
                <p class="insight-caption">
                  Nog geen score · {{ dimension.compared }} van minimaal
                  {{ dimension.early_minimum || dimension.minimum }} antwoorden
                </p></template
              >
            </div>
          </section>
          <p class="insight-note">
            <strong>Een momentopname van jouw antwoorden.</strong><br />{{
              compass.historical_votes || 0
            }}
            gewogen antwoorden hebben een historische context. Kleine aantallen
            geven een voorlopige richting, geen vaststaand profiel. De
            verhouding voor/tegen is geen links-rechtsscore.
          </p>
        </div>
        <div>
          <section class="insight-panel">
            <p class="insight-kicker">Wat je antwoorden vertellen</p>
            <h3>Dit valt op bij jou</h3>
            <div
              v-for="(insight, index) in narrative.insights"
              :key="index"
              class="insight-finding"
            >
              <h4>{{ insight.heading }}</h4>
              <p>{{ insight.body }}</p>
              <ul class="insight-evidence">
                <li v-for="id in insight.evidence_ids" :key="id">
                  <a v-if="votes.get(id)" :href="`#rapport-antwoord-${id}`"
                    ><span
                      :class="`insight-choice insight-choice--${votes.get(id)!.choice}`"
                      >{{ labels[votes.get(id)!.choice] }}</span
                    >{{ votes.get(id)!.title
                    }}<span class="sr-only"> · Onderbouwing bekijken</span></a
                  >
                </li>
              </ul>
            </div>
            <p v-if="!narrative.insights?.length" class="insight-caption">
              AI-toelichting is niet beschikbaar voor dit rapport. Je
              vastgelegde antwoorden en berekende waarden staan wel in dit
              document.
            </p>
          </section>
          <div class="insight-note">
            <strong>Jouw stemverdeling</strong>
            <p>
              {{ snapshot.counts.agree }} voor ·
              {{ snapshot.counts.disagree }} tegen ·
              {{ snapshot.counts.neutral }} neutraal.
            </p>
            <p>{{ narrative.reflection }}</p>
          </div>
        </div>
      </div>
      <footer class="insight-footer">
        <span>Gebaseerd op je eigen antwoorden</span
        ><span>WatDenktNederland · 01 / 02</span>
      </footer>
    </section>
    <section class="insight-page" aria-label="Jouw antwoorden uitgelegd">
      <header class="insight-header">
        <div class="insight-brand">
          <img
            src="/brand/wdnl-mark-concept.svg"
            width="30"
            height="37"
            alt=""
          /><strong>WatDenkt<span>Nederland</span></strong>
        </div>
        <p>{{ date }}<br />Jouw antwoorden uitgelegd · 02 / 02</p>
      </header>
      <p class="insight-kicker">Van keuze naar inzicht</p>
      <h3 class="insight-title">Dit zijn jouw standpunten</h3>
      <p class="insight-intro">
        Concrete keuzes die de analyse onderbouwen. De toelichting duidt je
        antwoord; waarom je precies zo koos, blijft aan jou.
      </p>
      <div class="insight-grid insight-positions">
        <section
          v-for="position in narrative.positions"
          :key="position.statement_id"
          class="insight-position"
        >
          <template v-if="votes.get(position.statement_id)"
            ><span
              :class="`insight-choice insight-choice--${votes.get(position.statement_id)!.choice}`"
              >{{ labels[votes.get(position.statement_id)!.choice] }}</span
            >
            <h4>{{ votes.get(position.statement_id)!.title }}</h4>
            <p>{{ position.interpretation }}</p>
            <NuxtLink
              :to="`/stellingen/${votes.get(position.statement_id)!.slug}`"
              >Bekijk de stelling <span aria-hidden="true">↗</span></NuxtLink
            ></template
          >
        </section>
      </div>
      <div class="insight-grid">
        <section class="insight-panel">
          <h3>De keuzes achter de inzichten</h3>
          <div
            v-for="vote in context.votes.filter((v) =>
              narrative.insights?.some((i) => i.evidence_ids.includes(v.id)),
            )"
            :id="`rapport-antwoord-${vote.id}`"
            :key="vote.id"
            class="insight-source"
          >
            <span :class="`insight-choice insight-choice--${vote.choice}`">{{
              labels[vote.choice]
            }}</span>
            <h4>{{ vote.title }}</h4>
            <p>{{ vote.description }}</p>
            <NuxtLink :to="`/stellingen/${vote.slug}`"
              >Context en argumenten <span aria-hidden="true">↗</span></NuxtLink
            >
          </div>
        </section>
        <div>
          <section
            v-for="(nuance, index) in narrative.nuances"
            :key="index"
            class="insight-panel"
          >
            <p class="insight-kicker">Ruimte voor nuance</p>
            <h3>{{ nuance.heading }}</h3>
            <p>{{ nuance.body }}</p>
            <ul class="insight-evidence">
              <li v-for="id in nuance.evidence_ids" :key="id">
                <template v-if="votes.get(id)"
                  ><span
                    :class="`insight-choice insight-choice--${votes.get(id)!.choice}`"
                    >{{ labels[votes.get(id)!.choice] }}</span
                  >{{ votes.get(id)!.title }}</template
                >
              </li>
            </ul>
          </section>
          <section
            v-for="(question, index) in narrative.questions"
            :key="index"
            class="insight-question"
          >
            <p class="insight-kicker">Een vraag om mee te nemen</p>
            <h4>{{ question.text }}</h4>
            <p class="insight-caption">
              Bij
              {{
                question.evidence_ids
                  .map((id) => votes.get(id)?.title)
                  .filter(Boolean)
                  .join(" · ")
              }}
            </p>
          </section>
          <section v-if="snapshot.party_matches.length" class="insight-panel">
            <h3>Overeenkomst bij officiële moties</h3>
            <div
              v-for="party in snapshot.party_matches"
              :key="party.name"
              class="insight-source"
            >
              <div class="insight-between">
                <strong>{{ party.name }}</strong
                ><strong>{{ party.percentage }}%</strong>
              </div>
              <p class="insight-caption">
                {{ party.matched }} van {{ party.compared }} vergelijkbare
                moties · geen stemadvies
              </p>
            </div>
          </section>
          <section v-if="compass.topics?.length" class="insight-panel">
            <h3>Onderwerpen in je antwoorden</h3>
            <div
              v-for="topic in compass.topics"
              :key="topic.name"
              class="insight-source"
            >
              <h4>{{ topic.name }}</h4>
              <p class="insight-caption">
                {{ topic.agree }} voor · {{ topic.disagree }} tegen ·
                {{ topic.neutral }} neutraal
              </p>
            </div>
          </section>
        </div>
      </div>
      <details class="insight-method">
        <summary>Hoe dit rapport tot stand komt</summary>
        <p>
          {{
            narrative.analysis_origin === "code"
              ? "Het overzicht gebruikt"
              : "De AI-toelichting gebruikt"
          }}
          {{ context.included_count }} van {{ context.eligible_count }} unieke,
          eenduidige antwoorden met beschikbare context. Cijfers en type worden
          door code berekend met redactionele gewichten.
        </p>
        <p>
          {{ compass.duplicate_answers || 0 }} dubbele antwoorden zijn eenmaal
          geteld; {{ context.excluded_conflicts }} groepen met verschillende
          antwoorden op dezelfde vraag zijn buiten de inhoudelijke analyse
          gehouden. Neutraal geeft geen richting. Historische keuzes geven geen
          actueel partijstandpunt. Partijovereenkomsten verschijnen alleen vanaf
          tien gecontroleerd gekoppelde moties per partij. Een type is een
          samenvatting, geen psychologische diagnose of stemadvies.
        </p>
        <NuxtLink to="/methodologie">Methode en beperkingen ↗</NuxtLink>
      </details>
      <footer class="insight-footer">
        <span>Persoonlijk en vertrouwelijk · {{ compass.version }}</span
        ><span>WatDenktNederland · 02 / 02</span>
      </footer>
    </section>
  </article>
</template>
<style scoped>
.insight-report {
  color: #001f4d;
  --report-teal: #008079;
  overflow-wrap: anywhere;
}
.insight-page {
  padding: clamp(1rem, 4vw, 3rem);
  border: 1px solid #dfe7ef;
  border-radius: 20px;
  background: #fff;
  margin-bottom: 1.4rem;
}
.insight-header,
.insight-brand,
.insight-between,
.insight-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}
.insight-header {
  margin-bottom: 2rem;
}
.insight-brand {
  justify-content: flex-start;
  font-size: clamp(0.7rem, 1.8vw, 1rem);
  gap: 7px;
  letter-spacing: -0.04em;
}
.insight-brand span {
  color: #0057b8;
}
.insight-header > p {
  font-size: 0.65rem;
  color: #53697d;
  text-align: right;
  line-height: 1.6;
}
.insight-kicker {
  font-size: 0.64rem;
  font-weight: 800;
  letter-spacing: 0.13em;
  text-transform: uppercase;
  color: var(--report-teal);
  margin: 0 0 0.45rem;
}
.insight-title {
  font-size: clamp(1.7rem, 3.5vw, 2.7rem);
  line-height: 1.12;
  letter-spacing: -0.045em;
  margin: 0 0 1.3rem;
  font-weight: 850;
}
.insight-hero {
  display: grid;
  grid-template-columns: 1fr 28%;
  align-items: center;
  background: linear-gradient(115deg, #edf7f6, #f5fbf9);
  padding: clamp(1rem, 2.5vw, 1.8rem);
  border: 1px solid #d9ece9;
  border-radius: 20px;
  margin-bottom: 1.3rem;
  gap: 0.5rem;
}
.insight-hero h3 {
  font-size: clamp(1.5rem, 3vw, 2.35rem);
  line-height: 1.1;
  letter-spacing: -0.045em;
  margin: 0.5rem 0 0.8rem;
}
.insight-hero p:not(.insight-kicker) {
  font-size: 0.86rem;
  line-height: 1.75;
  margin: 0;
}
.insight-hero img {
  width: 125%;
  max-width: none;
  transform: translateX(-4%);
  height: auto;
}
.insight-traits {
  display: flex;
  flex-wrap: wrap;
  gap: 6px 12px;
  list-style: none;
  padding: 0;
  margin: 0.9rem 0 0;
  color: var(--report-teal);
  font-size: 0.67rem;
  font-weight: 650;
}
.insight-stats {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 1rem;
  padding: 0.7rem 0 1.3rem;
  border-bottom: 1px solid #dfe7ef;
  margin: 0 0 1.5rem;
}
.insight-stats > div {
  display: flex;
  flex-direction: column-reverse;
  border-right: 1px solid #dfe7ef;
  padding-right: 8px;
}
.insight-stats > div:last-child {
  border: 0;
}
.insight-stats dd {
  font-size: 2.25rem;
  line-height: 1.1;
  font-weight: 800;
  margin: 0 0 6px;
  letter-spacing: -0.04em;
}
.insight-stats dt {
  font-size: 0.65rem;
  line-height: 1.4;
  color: #53697d;
}
.insight-grid {
  display: grid;
  grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
  gap: 1.2rem;
  align-items: start;
}
.insight-panel {
  border: 1px solid #dfe7ef;
  border-radius: 16px;
  padding: 1.2rem;
  margin-bottom: 1.2rem;
}
.insight-panel h3 {
  font-size: 1.2rem;
  line-height: 1.25;
  letter-spacing: -0.025em;
  margin: 0 0 0.8rem;
}
.insight-panel h4 {
  font-size: 0.89rem;
  line-height: 1.5;
  margin: 0 0 0.35rem;
}
.insight-panel p:not(.insight-kicker):not(.insight-caption) {
  font-size: 0.8rem;
  line-height: 1.75;
  margin: 0;
}
.insight-score {
  margin-top: 1.3rem;
}
.insight-between {
  font-size: 0.76rem;
  align-items: start;
}
.insight-track {
  height: 8px;
  background: #e3e9ef;
  border-radius: 8px;
  margin: 0.5rem 0 0.35rem;
  overflow: hidden;
}
.insight-track > span {
  display: block;
  height: 100%;
  background: #008f87;
  border-radius: 8px;
}
.insight-value {
  color: var(--report-teal);
  font-size: 0.86rem;
}
.insight-caption {
  font-size: 0.65rem;
  color: #53697d;
  line-height: 1.6;
  margin: 0.35rem 0 0;
}
.insight-note {
  background: #f4f7fa;
  color: #53697d;
  border-radius: 12px;
  padding: 1rem;
  font-size: 0.75rem;
  line-height: 1.75;
  margin: 0 0 1rem;
}
.insight-note p {
  margin: 0.4rem 0 0;
}
.insight-finding {
  padding: 1rem 0;
  border-bottom: 1px solid #e6edf3;
}
.insight-finding:last-child {
  padding-bottom: 0;
  border: 0;
}
.insight-evidence {
  padding: 0;
  list-style: none;
  margin: 0.65rem 0 0;
  font-size: 0.66rem;
  line-height: 1.65;
  color: #53697d;
}
.insight-evidence li {
  margin-top: 0.45rem;
}
.insight-evidence a {
  text-decoration: none;
  display: block;
  color: inherit;
}
.insight-evidence a:hover {
  text-decoration: underline;
}
.insight-choice {
  display: inline-block;
  color: #0057b8;
  background: #e3edfb;
  padding: 2px 6px;
  margin-right: 6px;
  border-radius: 4px;
  font-size: 0.59rem;
  font-weight: 800;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  line-height: 1.5;
}
.insight-choice--disagree {
  background: #fce5eb;
  color: #ac1033;
}
.insight-choice--neutral {
  background: #e6eaf0;
  color: #4f637b;
}
.insight-footer {
  font-size: 0.6rem;
  color: #53697d;
  border-top: 1px solid #dfe7ef;
  padding-top: 1rem;
  margin-top: 1.2rem;
  align-items: start;
}
.insight-footer span:last-child {
  text-align: right;
}
.insight-intro {
  font-size: 0.85rem;
  line-height: 1.75;
  color: #53697d;
  max-width: 65ch;
  margin: 0 0 1.4rem;
}
.insight-positions {
  align-items: stretch;
  margin-bottom: 1.3rem;
}
.insight-position {
  background: #f5f8fb;
  border: 1px solid #e2e9f1;
  border-radius: 14px;
  padding: 1.2rem;
}
.insight-position h4 {
  font-size: 0.95rem;
  line-height: 1.5;
  margin: 0.7rem 0 0.45rem;
}
.insight-position p {
  font-size: 0.8rem;
  line-height: 1.75;
  color: #53697d;
  margin: 0 0 0.8rem;
}
.insight-position a,
.insight-source a,
.insight-method a {
  font-size: 0.68rem;
  color: #0057b8;
  text-decoration: underline;
  text-underline-offset: 3px;
}
.insight-source {
  padding: 1rem 0;
  border-top: 1px solid #e6edf3;
  scroll-margin-top: 110px;
}
.insight-source h4 {
  margin: 0.5rem 0 0.4rem;
}
.insight-source a {
  display: inline-block;
  margin-top: 0.6rem;
}
.insight-question {
  padding: 1.3rem;
  border-radius: 16px;
  background: #001f4d;
  color: white;
  margin-bottom: 1.2rem;
}
.insight-question .insight-kicker {
  color: #8adbd1;
}
.insight-question h4 {
  font-size: 1.1rem;
  line-height: 1.5;
  margin: 0.7rem 0;
}
.insight-question .insight-caption {
  color: #c3d5e3;
}
.insight-method {
  padding: 1.1rem;
  background: #f4f7fa;
  border-radius: 12px;
  font-size: 0.75rem;
  line-height: 1.8;
  color: #53697d;
}
.insight-method summary {
  color: #001f4d;
  font-weight: 750;
  cursor: pointer;
}
.insight-dev,
.insight-report__dev {
  padding: 0.7rem;
  background: #fff4d6;
  font-size: 0.64rem;
  border-radius: 8px;
  margin: 0 0 1rem;
}
.insight-report a:focus-visible,
.insight-method summary:focus-visible {
  outline: 3px solid #0057b8;
  outline-offset: 4px;
  border-radius: 3px;
}
@media (max-width: 620px) {
  .insight-grid {
    grid-template-columns: 1fr;
  }
  .insight-hero {
    grid-template-columns: 1fr 27%;
    gap: 0;
  }
  .insight-hero img {
    width: 150%;
    transform: translateX(-14%);
  }
  .insight-hero h3 {
    font-size: 1.55rem;
  }
  .insight-hero p:not(.insight-kicker) {
    font-size: 0.76rem;
  }
  .insight-traits {
    font-size: 0.6rem;
  }
  .insight-stats {
    gap: 0.7rem;
    grid-template-columns: repeat(2, 1fr);
  }
  .insight-stats > div:nth-child(2) {
    border: 0;
  }
  .insight-header {
    align-items: start;
    margin-bottom: 1.5rem;
  }
  .insight-header > p {
    font-size: 0.52rem;
  }
  .insight-brand {
    font-size: 0.65rem;
    gap: 4px;
  }
  .insight-brand img {
    width: 22px;
    height: auto;
  }
  .insight-page {
    border-radius: 12px;
  }
  .insight-stats dd {
    font-size: 1.8rem;
  }
}
@media print {
  .insight-page {
    page-break-after: always;
    border: 0;
    border-radius: 0;
  }
  .insight-page:last-child {
    page-break-after: auto;
  }
  .insight-method {
    display: block;
  }
  .insight-report__dev {
    display: none;
  }
}
</style>
