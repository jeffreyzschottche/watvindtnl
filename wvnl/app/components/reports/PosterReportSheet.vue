<script setup lang="ts">
import ReportTypeWheel from "./ReportTypeWheel.vue";
import type { CompassDimension, VoteReport } from "~/types/voteReports";
const props = defineProps<{ report: VoteReport; example?: boolean }>();
const emit = defineEmits<{ selectType: [id: string] }>();
const root = ref<HTMLElement | null>(null);
const { enabled: reportMotionEnabled } = useReportMotion(
  root,
  () => props.report.id,
);
const snapshot = computed(() => props.report.snapshot!);
const compass = computed(() => snapshot.value.compass!);
const type = computed(() => compass.value.type);
const color = computed(() => type.value?.color || "#008f87");
const date = computed(() =>
  new Intl.DateTimeFormat("nl-NL", { dateStyle: "long" }).format(
    new Date(snapshot.value.generated_at),
  ),
);
const highlights = computed(() => props.report.narrative?.highlights || []);
const axis = computed(() => compass.value.spectrum);
const label = { agree: "Voor", disagree: "Tegen", neutral: "Neutraal" };
// How far one answer more or less could move a score; shown as a lighter band, never as extra data.
function band(d: CompassDimension) {
  if (d.percentage === null || !d.sensitivity) return null;
  const low = Math.min(d.sensitivity.min, d.percentage);
  const high = Math.max(d.sensitivity.max, d.percentage);
  return high > low ? { low, high } : null;
}
const answerMix = computed(() =>
  (["agree", "disagree", "neutral"] as const).map((choice) => ({
    choice,
    label: label[choice].toLowerCase(),
    count: snapshot.value.counts[choice],
    percentage: snapshot.value.total
      ? (100 * snapshot.value.counts[choice]) / snapshot.value.total
      : 0,
  })),
);
</script>
<template>
  <article
    ref="root"
    class="compass-poster"
    :data-report-motion="reportMotionEnabled ? 'on' : 'off'"
    :style="{ '--type-color': color }"
    :aria-label="
      example
        ? 'Voorbeeldrapport · fictieve antwoorden'
        : 'Jouw persoonlijke stemrapport'
    "
  >
    <p v-if="report.is_development" class="poster-notice">
      Devrapport · geen aankoop
    </p>
    <header class="poster-header">
      <img
        class="poster-logo"
        src="/brand/wdnl-logo.png"
        width="900"
        height="170"
        alt="WatDenktNederland"
      />
      <div>
        <h2>Jouw Politieke Kompas</h2>
        <p>
          {{
            example
              ? "Voorbeeldrapport · fictieve antwoorden"
              : "Persoonlijk rapport op basis van jouw stemgedrag"
          }}
        </p>
        <span class="poster-accent" />
      </div>
    </header>
    <section
      data-report-reveal
      class="poster-hero"
      aria-label="Jouw politieke type"
    >
      <div class="poster-type-icon">
        <img
          :src="`/report-art/${type?.icon || 'balance'}-white.svg`"
          alt=""
          width="64"
          height="64"
        />
      </div>
      <div class="poster-hero-copy">
        <h3 :aria-live="example ? 'polite' : undefined">
          {{ type?.name || "Jouw kompas in opbouw" }}
        </h3>
        <p>{{ report.narrative?.summary }}</p>
      </div>
      <div class="poster-traits">
        <h4>
          {{
            type?.provisional !== false
              ? "Voorlopig politiek type"
              : "Jouw politieke type"
          }}
        </h4>
        <ul>
          <li
            v-for="trait in type?.traits || [
              `${snapshot.total} eigen antwoorden`,
              `${compass.used_votes} gewogen stellingen`,
              'Jouw persoonlijke richting',
            ]"
            :key="trait"
          >
            <img
              :src="`/report-art/${type?.icon || 'balance'}.svg`"
              alt=""
              width="22"
              height="22"
            />{{ trait }}
          </li>
        </ul>
      </div>
    </section>
    <div class="poster-grid">
      <section data-report-reveal class="poster-card poster-personality">
        <h3>Jouw politieke persoonlijkheid</h3>
        <span class="poster-accent" />
        <ReportTypeWheel
          :type="type"
          :catalogue="compass.type_catalogue || []"
          :animate="reportMotionEnabled"
          :example="example"
          @select-type="example && emit('selectType', $event)"
        />
      </section>
      <div class="poster-scores">
        <section data-report-reveal class="poster-card">
          <h3>Jouw positie op het politieke spectrum</h3>
          <span class="poster-accent" /><template
            v-if="axis.percentage !== null"
            ><div
              class="poster-spectrum"
              role="img"
              :aria-label="`Economisch beleid: ${axis.percentage}% links, ${axis.opposite_percentage}% rechts${axis.provisional ? ', voorlopig' : ''}`"
            >
              <span
                class="poster-you"
                :style="{ left: `${100 - axis.percentage}%` }"
                >Jij</span
              ><span
                class="poster-marker"
                :style="{ left: `${100 - axis.percentage}%` }"
              />
            </div>
            <div class="poster-between">
              <strong>Links {{ axis.percentage }}%</strong
              ><strong>{{ axis.opposite_percentage }}% Rechts</strong>
            </div>
            <p class="poster-caption">
              Economisch beleid · {{ axis.compared }} antwoorden<span
                v-if="axis.provisional"
              >
                · voorlopig</span
              >
            </p></template
          >
          <p v-else class="poster-caption">
            Nog geen economische aspositie · {{ axis.compared }} gewogen
            antwoorden.
          </p>
        </section>
        <section data-report-reveal class="poster-card">
          <h3>Jouw scores op belangrijke thema’s</h3>
          <span class="poster-accent" />
          <div
            v-for="dimension in compass.dimensions"
            :key="dimension.id"
            class="poster-score"
          >
            <img
              :src="`/report-art/${dimension.icon}.svg`"
              alt=""
              width="25"
              height="25"
            />
            <div>
              <div class="poster-between">
                <span>{{ dimension.left }}</span
                ><span>{{ dimension.right }}</span>
              </div>
              <template v-if="dimension.percentage !== null"
                ><div
                  role="img"
                  :aria-label="`${dimension.percentage}% ${dimension.left}, ${dimension.opposite_percentage}% ${dimension.right}${dimension.provisional ? ', voorlopig' : ''}`"
                >
                  <div class="poster-track poster-track--band">
                    <span
                      data-report-bar
                      :style="{ width: `${dimension.percentage}%` }"
                    />
                    <template v-if="band(dimension)">
                      <i
                        class="poster-band poster-band--low"
                        :style="{
                          left: `${band(dimension)!.low}%`,
                          width: `${dimension.percentage - band(dimension)!.low}%`,
                        }" /><i
                        class="poster-band poster-band--high"
                        :style="{
                          left: `${dimension.percentage}%`,
                          width: `${band(dimension)!.high - dimension.percentage}%`,
                        }"
                    /></template>
                  </div>
                  <div class="poster-between poster-percentages">
                    <strong>{{ dimension.percentage }}%</strong
                    ><strong>{{ dimension.opposite_percentage }}%</strong>
                  </div>
                </div>
                <p class="poster-caption">
                  {{ dimension.compared }} antwoorden<span
                    v-if="band(dimension)"
                  >
                    · bandbreedte {{ band(dimension)!.low }}–{{
                      band(dimension)!.high
                    }}%</span
                  ><span v-if="dimension.provisional"> · voorlopig</span>
                </p></template
              ><template v-else
                ><div class="poster-track" />
                <p class="poster-caption">
                  Nog geen score · {{ dimension.compared }}/{{
                    dimension.early_minimum || 2
                  }}
                  antwoorden
                </p></template
              >
            </div>
          </div>
        </section>
      </div>
      <section data-report-reveal class="poster-card poster-parties">
        <h3>Partijen die bij je passen</h3>
        <span class="poster-accent" />
        <p class="poster-caption">
          {{
            compass.score_adjustment
              ? "Matchscore op 100 · voorzichtig bij weinig moties."
              : "Overeenkomst bij officieel gekoppelde moties."
          }}
        </p>
        <div
          v-for="party in snapshot.party_matches.slice(0, 5)"
          :key="party.name"
          class="poster-party"
        >
          <div>
            <h4>{{ party.name }}</h4>
            <div class="poster-track">
              <span
                data-report-bar
                :style="{
                  width: `${party.balanced_score ?? party.percentage}%`,
                }"
              />
            </div>
            <p class="poster-caption">
              {{ party.matched }} van {{ party.compared }} moties<span
                v-if="party.provisional"
              >
                · eerste indicatie</span
              >
            </p>
          </div>
          <strong
            class="poster-party-percentage"
            :aria-label="
              party.balanced_score !== undefined
                ? `Matchscore ${party.balanced_score} op 100; ${party.matched} van ${party.compared} moties gelijk gestemd`
                : undefined
            "
            >{{ party.balanced_score ?? party.percentage
            }}{{ party.balanced_score === undefined ? "%" : "" }}</strong
          >
        </div>
        <template v-if="!snapshot.party_matches.length"
          ><p class="poster-empty-number">
            {{ snapshot.party_coverage?.verified_motions || 0 }}
            <span>vergelijkbare officiële moties</span>
          </p>
          <p class="poster-empty">
            Er zijn nog te weinig gecontroleerde partijstemmen gekoppeld aan
            jouw antwoorden om partijen te rangschikken.
          </p>
          <p class="poster-caption">
            De stemmen blijven bewaard. Een vergelijking wordt mogelijk zodra
            meer officiële stemmingen zijn gecontroleerd.
          </p></template
        >
        <p class="poster-caption poster-parties-note">
          <span v-if="snapshot.party_matches[0]?.provisional"
            >Eerste vergelijking op weinig historische moties. </span
          >Overeenkomst in stemgedrag, geen stemadvies.
        </p>
      </section>
      <div class="poster-interpretation">
        <section data-report-reveal class="poster-card">
          <h3>Wat typeert jou?</h3>
          <span class="poster-accent" />
          <ul class="poster-highlights">
            <li
              v-for="(highlight, index) in highlights"
              :key="index"
              data-report-point
            >
              <span class="poster-check" aria-hidden="true">✓</span>
              <p>
                {{ highlight.text }}
                <small v-if="highlight.evidence_ids.length > 1"
                  >Blijkt uit {{ highlight.evidence_ids.length }} van je
                  antwoorden</small
                >
              </p>
            </li>
          </ul>
        </section>
        <section data-report-reveal class="poster-card poster-method">
          <span aria-hidden="true" class="poster-info">i</span>
          <div>
            <h3>Hoe dit rapport werkt</h3>
            <p>
              Gebaseerd op {{ snapshot.total }} eigen antwoorden;
              {{ compass.used_votes }} unieke stellingen bepalen de gewogen
              scores. {{ compass.historical_votes || 0 }} daarvan zijn
              historisch. Neutraal telt mee zonder richting. Type en cijfers
              volgen uit vaste wegingen;
              {{
                report.narrative?.analysis_origin === "code"
                  ? "de toelichting is zonder AI gemaakt."
                  : "AI duidt je antwoorden."
              }}
              <template v-if="compass.score_adjustment">
                Bij weinig antwoorden blijven scores dichter bij het midden. Dit
                maakt je keuzes niet gematigder.
              </template>
              Kleine aantallen zijn voorlopig. Het type is een samenvatting,
              geen diagnose.
            </p>
            <div v-if="snapshot.total" class="poster-answer-mix">
              <h4>Verdeling van je {{ snapshot.total }} antwoorden</h4>
              <div
                class="poster-answer-mix__track"
                role="img"
                :aria-label="
                  answerMix
                    .map((part) => `${part.count} ${part.label}`)
                    .join(', ')
                "
              >
                <span
                  v-for="part in answerMix"
                  :key="part.choice"
                  data-report-bar
                  :class="`poster-answer-mix__part poster-answer-mix__part--${part.choice}`"
                  :style="{ width: `${part.percentage}%` }"
                />
              </div>
              <div class="poster-answer-mix__legend">
                <span v-for="part in answerMix" :key="part.choice"
                  ><i
                    :class="`poster-answer-mix__dot--${part.choice}`"
                    aria-hidden="true"
                  /><strong>{{ part.count }}</strong> {{ part.label }}</span
                >
              </div>
            </div>
            <NuxtLink to="/methodologie">Methode en beperkingen ↗</NuxtLink>
          </div>
        </section>
      </div>
    </div>
    <footer class="poster-footer">
      <p>
        <strong>{{ snapshot.total }}</strong> beantwoorde stellingen<br />{{
          snapshot.counts.neutral
        }}
        neutraal · {{ compass.duplicate_answers || 0 }} dubbelen eenmaal geteld
      </p>
      <p>
        {{ date }}<br />Kompas
        {{ compass.version.replace(/^compass-v/, "").replace(/^4$/, "4.0") }} ·
        {{
          example
            ? "voorbeeld"
            : type?.provisional
              ? "voorlopig"
              : "persoonlijk"
        }}
      </p>
      <p><strong>WatDenktNederland</strong><br />watdenktnl.nl</p>
    </footer>
  </article>
  <details v-if="!example && highlights.length" class="poster-evidence">
    <summary>Onderbouwing van jouw rapport</summary>
    <section v-for="(highlight, index) in highlights" :key="index">
      <h3>{{ highlight.text }}</h3>
      <ul>
        <li v-for="id in highlight.evidence_ids" :key="id">
          <template
            v-for="entry in snapshot.entries.filter((e) => e.id === id)"
            :key="entry.id"
            ><strong>{{ label[entry.choice] }}</strong> ·
            <NuxtLink :to="`/stellingen/${entry.slug}`">{{
              entry.title
            }}</NuxtLink></template
          >
        </li>
      </ul>
    </section>
  </details>
</template>
<style scoped>
.compass-poster {
  --type-color: #008f87;
  color: #001f4d;
  position: relative;
  max-width: 1050px;
  margin-inline: auto;
  padding: clamp(1rem, 3vw, 2.1rem);
  background: white;
  border: 1px solid #dce3ec;
  border-radius: 16px;
  overflow: hidden;
  overflow-wrap: anywhere;
}
.poster-header {
  display: grid;
  grid-template-columns: 38% 1fr;
  align-items: center;
  gap: 1.5rem;
  margin-bottom: 1.4rem;
}
.poster-logo {
  width: 100%;
  height: auto;
  object-fit: contain;
}
.poster-header > div {
  border-left: 1px solid #cdd8e3;
  padding-left: 1.5rem;
}
.poster-header h2 {
  font-size: clamp(1.3rem, 2.5vw, 2rem);
  letter-spacing: -0.04em;
  line-height: 1.15;
  margin: 0;
}
.poster-header p {
  font-size: 0.8rem;
  margin: 0.35rem 0 0;
}
.poster-accent {
  display: block;
  height: 4px;
  width: 42px;
  background: #e1062e;
  border-radius: 4px;
  margin: 0.7rem 0 0.9rem;
}
.poster-header .poster-accent {
  margin-bottom: 0;
}
.poster-hero {
  display: grid;
  grid-template-columns: 76px minmax(0, 1fr) 28%;
  gap: 1rem;
  align-items: center;
  background: linear-gradient(110deg, #e8f6f4, #f4fbfa);
  border: 1px solid #dcecea;
  border-radius: 15px;
  padding: 1.2rem;
  margin-bottom: 1.2rem;
}
.poster-type-icon {
  background: var(--type-color);
  border-radius: 17px;
  aspect-ratio: 1;
  display: grid;
  place-items: center;
}
.poster-type-icon img {
  width: 64%;
  height: 64%;
}
.poster-hero-copy h3 {
  font-size: clamp(1.4rem, 2.65vw, 2.2rem);
  letter-spacing: -0.045em;
  line-height: 1.08;
  margin: 0 0 0.7rem;
}
.poster-hero-copy p {
  font-size: 0.82rem;
  line-height: 1.65;
  margin: 0;
}
.poster-traits {
  padding-left: 1rem;
  border-left: 1px solid #9dc9c7;
}
.poster-traits h4 {
  font-size: 0.65rem;
  background: var(--type-color);
  border-radius: 30px;
  color: white;
  text-align: center;
  padding: 0.45rem 0.5rem;
  margin: 0 0 0.7rem;
}
.poster-traits ul {
  list-style: none;
  padding: 0;
  margin: 0;
}
.poster-traits li {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.7rem;
  line-height: 1.5;
  margin: 0.55rem 0;
}
.poster-traits img {
  flex: 0 0 21px;
}
.poster-grid {
  display: grid;
  grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
  gap: 1.1rem;
  align-items: stretch;
}
.poster-card {
  border: 1px solid #dce3ec;
  border-radius: 14px;
  padding: 1.15rem;
  margin-bottom: 0;
}
.poster-card h3 {
  font-size: clamp(0.95rem, 1.8vw, 1.2rem);
  letter-spacing: -0.025em;
  line-height: 1.25;
  margin: 0;
  font-weight: 800;
}
.poster-personality {
  display: flex;
  flex-direction: column;
  min-width: 0;
}
.poster-scores,
.poster-interpretation {
  display: flex;
  flex-direction: column;
  gap: 1.1rem;
  min-width: 0;
}
.poster-scores > .poster-card:last-child {
  flex: 1;
  display: flex;
  flex-direction: column;
}
.poster-scores > .poster-card:last-child .poster-score {
  flex: 1;
  margin-top: 0.5rem;
  padding: 0.35rem 0;
}
.poster-interpretation > .poster-method {
  flex: 1;
}
.poster-grid {
  margin-bottom: 1.1rem;
}

.poster-between {
  display: flex;
  align-items: start;
  justify-content: space-between;
  gap: 10px;
  font-size: 0.73rem;
}
.poster-between > span:last-child {
  text-align: right;
}
.poster-caption {
  font-size: 0.6rem;
  color: #53697d;
  line-height: 1.6;
  margin: 0.3rem 0 0;
}
.poster-spectrum {
  position: relative;
  background: linear-gradient(90deg, #0057b8, #abb9ce, #e1062e);
  height: 15px;
  border-radius: 20px;
  margin: 2.4rem 0.3rem 0.7rem;
}
.poster-you {
  position: absolute;
  top: -31px;
  transform: translateX(-50%);
  background: var(--type-color);
  border-radius: 18px;
  color: white;
  padding: 3px 12px;
  font-size: 0.73rem;
  font-weight: 800;
}
.poster-you:after {
  content: "";
  position: absolute;
  bottom: -5px;
  left: 50%;
  transform: translateX(-50%);
  border-left: 5px solid transparent;
  border-right: 5px solid transparent;
  border-top: 6px solid var(--type-color);
}
.poster-marker {
  position: absolute;
  top: 50%;
  transform: translate(-50%, -50%);
  width: 21px;
  height: 21px;
  border: 3px solid white;
  border-radius: 50%;
  background: var(--type-color);
  box-shadow: 0 1px 3px #001f4d25;
}
.poster-score {
  display: grid;
  grid-template-columns: 25px minmax(0, 1fr);
  gap: 0.7rem;
  margin-top: 1.05rem;
  align-items: center;
}
.poster-track {
  height: 8px;
  border-radius: 7px;
  background: #dce4eb;
  margin: 0.4rem 0 0.2rem;
  overflow: hidden;
}
.poster-track--band {
  position: relative;
}
.poster-band {
  position: absolute;
  top: 0;
  bottom: 0;
}
.poster-band--low {
  background: rgb(255 255 255 / 0.45);
}
.poster-band--high {
  background: var(--type-color);
  opacity: 0.32;
}
.poster-track > span {
  display: block;
  height: 100%;
  border-radius: 7px;
  background: var(--type-color);
}
.poster-percentages {
  font-size: 0.72rem;
}
.poster-score .poster-caption {
  font-size: 0.56rem;
  margin-top: 0.1rem;
}
.poster-parties {
  display: flex;
  flex-direction: column;
}
.poster-party {
  flex: 1;
  display: grid;
  grid-template-columns: minmax(0, 1fr) auto;
  align-items: center;
  gap: 0.8rem;
  border-bottom: 1px solid #e8edf3;
  padding: 0.8rem 0;
}
.poster-party h4 {
  font-size: 0.82rem;
  line-height: 1.45;
  margin: 0;
}
.poster-party-percentage {
  font-size: 1.45rem;
  letter-spacing: -0.04em;
  background: #f1f5f8;
  border-radius: 12px;
  padding: 0.4rem 0.5rem;
}
.poster-party .poster-track {
  height: 8px;
  margin-top: 0.45rem;
}
.poster-parties-note {
  margin-top: 0.8rem;
}
.poster-empty {
  font-size: 0.85rem;
  line-height: 1.75;
  color: #53697d;
}
.poster-empty-number {
  font-size: 2.3rem;
  color: var(--type-color);
  font-weight: 800;
  line-height: 1.2;
  margin: 1.3rem 0 1rem;
}
.poster-empty-number span {
  font-size: 0.8rem;
  font-weight: 500;
}
.poster-highlights {
  list-style: none;
  padding: 0;
  margin: 0;
}
.poster-highlights li {
  display: flex;
  align-items: flex-start;
  gap: 0.7rem;
  margin: 1rem 0;
}
.poster-highlights li:last-child {
  margin-bottom: 0;
}
.poster-highlights small {
  display: block;
  font-size: 0.66rem;
  color: #627587;
  line-height: 1.3;
}
.poster-highlights p {
  font-size: 0.85rem;
  line-height: 1.65;
  margin: 0;
}
.poster-check {
  flex: 0 0 21px;
  background: var(--type-color);
  color: white;
  width: 21px;
  height: 21px;
  display: grid;
  place-items: center;
  border-radius: 50%;
  font-size: 0.85rem;
  font-weight: 800;
  line-height: 1;
}
.poster-method {
  display: flex;
  align-items: start;
  gap: 0.8rem;
  background: #f0f7f7;
  border-color: #d8e9e8;
}
.poster-info {
  font-family: serif;
  font-size: 1.6rem;
  color: var(--type-color);
  font-weight: 800;
  background: #dcefed;
  border-radius: 9px;
  width: 34px;
  flex: 0 0 34px;
  text-align: center;
}
.poster-method h3 {
  font-size: 1rem;
  margin-bottom: 0.5rem;
}
.poster-method p {
  font-size: 0.65rem;
  line-height: 1.7;
  margin: 0;
}
.poster-method a {
  display: inline-block;
  color: #0057b8;
  font-size: 0.63rem;
  margin-top: 0.5rem;
  text-decoration: underline;
  text-underline-offset: 2px;
}
.poster-answer-mix {
  margin-top: 0.9rem;
  border-top: 1px solid #d8e9e8;
  padding-top: 0.7rem;
}
.poster-answer-mix h4 {
  margin: 0 0 0.45rem;
  font-size: 0.65rem;
  font-weight: 750;
}
.poster-answer-mix__track {
  display: flex;
  overflow: hidden;
  height: 8px;
  border-radius: 8px;
  background: #dce4eb;
}
.poster-answer-mix__part {
  display: block;
  height: 100%;
  flex-shrink: 0;
}
.poster-answer-mix__part--agree,
.poster-answer-mix__dot--agree {
  background: #0057b8;
}
.poster-answer-mix__part--disagree,
.poster-answer-mix__dot--disagree {
  background: #e1062e;
}
.poster-answer-mix__part--neutral,
.poster-answer-mix__dot--neutral {
  background: #8494a8;
}
.poster-answer-mix__legend {
  display: flex;
  flex-wrap: wrap;
  gap: 0.25rem 0.8rem;
  margin-top: 0.45rem;
}
.poster-answer-mix__legend span {
  display: inline-flex;
  align-items: center;
  gap: 0.2rem;
  font-size: 0.6rem;
  color: #425c72;
}
.poster-answer-mix__legend i {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  margin-right: 0.05rem;
}
.poster-footer {
  display: grid;
  grid-template-columns: 1.35fr 1fr 1fr;
  border-top: 1px solid #d2dce8;
  padding-top: 0.8rem;
  gap: 1rem;
  align-items: center;
}
.poster-footer p {
  font-size: 0.59rem;
  line-height: 1.6;
  color: #53697d;
  margin: 0;
}
.poster-footer strong {
  color: #001f4d;
}
.poster-footer p:last-child {
  text-align: right;
}
.poster-notice {
  text-align: right;
  font-size: 0.55rem;
  color: #6c4f13;
  margin: 0 0 0.5rem;
}
.poster-evidence {
  max-width: 1050px;
  margin: 1rem auto;
  font-size: 0.8rem;
  color: #53697d;
  line-height: 1.7;
}
.poster-evidence summary {
  cursor: pointer;
  font-weight: 700;
  color: #001f4d;
}
.poster-evidence section {
  padding: 1rem;
  border-bottom: 1px solid #dce3ec;
}
.poster-evidence h3 {
  font-size: 0.85rem;
  margin: 0 0 0.4rem;
}
.poster-evidence a {
  color: #0057b8;
  text-decoration: underline;
}
.poster-evidence summary:focus-visible,
.poster-evidence a:focus-visible,
.poster-method a:focus-visible {
  outline: 3px solid #0057b8;
  outline-offset: 4px;
}
@media (max-width: 650px) {
  .poster-header {
    grid-template-columns: 1fr;
    gap: 1rem;
  }
  .poster-logo {
    max-width: 260px;
  }
  .poster-header > div {
    border: 0;
    padding: 0;
  }
  .poster-header h2 {
    font-size: 1.7rem;
  }
  .poster-header p {
    font-size: 0.7rem;
  }
  .poster-hero {
    grid-template-columns: 58px minmax(0, 1fr);
    gap: 0.8rem;
    padding: 1rem;
  }
  .poster-hero-copy h3 {
    font-size: 1.5rem;
  }
  .poster-hero-copy p {
    font-size: 0.76rem;
  }
  .poster-traits {
    grid-column: 1/-1;
    border-left: 0;
    border-top: 1px solid #c5dfdd;
    padding: 0.8rem 0 0;
  }
  .poster-traits h4 {
    display: inline-block;
    margin-bottom: 0.3rem;
  }
  .poster-traits ul {
    display: flex;
    flex-wrap: wrap;
    gap: 0.2rem 0.7rem;
  }
  .poster-traits li {
    font-size: 0.64rem;
    margin: 0.2rem 0;
  }
  .poster-grid {
    grid-template-columns: 1fr;
  }
  .poster-card {
    padding: 1rem;
  }
  .poster-personality {
    order: 0;
  }
  .poster-scores {
    order: 1;
  }
  .poster-parties {
    order: 2;
  }
  .poster-interpretation {
    order: 3;
  }
  .poster-card h3 {
    font-size: 1.15rem;
  }
  .poster-footer {
    grid-template-columns: 1fr 1fr;
  }
  .poster-footer p:last-child {
    grid-column: 1/-1;
    text-align: left;
  }
  .poster-footer p {
    font-size: 0.55rem;
  }
}
@media print {
  .compass-poster {
    border: 0;
    border-radius: 0;
  }
  .poster-evidence {
    display: none;
  }
}
</style>
