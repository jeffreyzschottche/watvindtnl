<script setup lang="ts">
import type { VoteReport } from "~/types/voteReports";
import LegacyReportSheet from "./LegacyReportSheet.vue";
import PosterReportSheet from "./PosterReportSheet.vue";
import InsightReportSheet from "./InsightReportSheet.vue";
const props = defineProps<{ report: VoteReport }>();
const snapshot = computed(() => props.report.snapshot);
const compass = computed(() => snapshot.value?.compass);
const politicalType = computed(() => compass.value?.type);
const date = computed(() =>
  new Intl.DateTimeFormat("nl-NL", { dateStyle: "long" }).format(
    new Date(snapshot.value?.generated_at || props.report.created_at),
  ),
);
</script>
<template>
  <PosterReportSheet
    v-if="report.narrative?.analysis_version === 4 && snapshot?.compass"
    :report="report"
  />
  <InsightReportSheet
    v-else-if="
      report.narrative?.analysis_version === 3 && snapshot?.analysis_context
    "
    :report="report"
  />
  <LegacyReportSheet v-else-if="!compass" :report="report" />
  <article
    v-else-if="snapshot && report.narrative"
    class="compass-paper"
    aria-label="Jouw persoonlijke stemrapport"
  >
    <p v-if="report.is_development" class="compass-paper__dev">
      🧪 DEV-RAPPORT · GEEN AANKOOP · TIJDELIJK
    </p>
    <header class="compass-paper__header">
      <div class="compass-paper__brand">
        <img
          src="/brand/wdnl-mark-concept.svg"
          alt=""
          width="49"
          height="56"
        /><strong>WatDenkt<span>Nederland</span></strong>
      </div>
      <div>
        <h2>Jouw Politieke Kompas</h2>
        <p>Persoonlijk rapport op basis van jouw stemgedrag</p>
        <span class="compass-paper__accent" />
      </div>
      <img
        class="compass-paper__flag"
        src="/report-art/flag.svg"
        alt=""
        width="130"
        height="54"
      />
    </header>
    <p v-if="politicalType?.provisional" class="compass-paper__indication">
      <strong>Voorlopige indicatie.</strong> Gebaseerd op
      {{ compass.used_votes }} verschillende gewogen stellingen.
      {{ compass.historical_votes || 0 }} hebben een historische context.
      Beschikbare thema’s bepalen de indicatie; ontbrekende scores worden niet
      ingevuld.
    </p>
    <section class="compass-paper__hero" aria-label="Jouw politieke type">
      <div class="compass-paper__hero-icon">
        <img
          :src="`/report-art/${politicalType?.id === 'sociale-vernieuwer' ? 'leaf' : politicalType?.icon || 'leaf'}-white.svg`"
          alt=""
          width="70"
          height="70"
        />
      </div>
      <div class="compass-paper__hero-copy">
        <h3>{{ politicalType?.name || "Jouw kompas in opbouw" }}</h3>
        <p>
          {{
            politicalType
              ? report.narrative.summary
              : "Je antwoorden staan vast. Voor je politieke type zijn meer antwoorden op redactioneel gewogen stellingen nodig."
          }}
        </p>
      </div>
      <div class="compass-paper__traits">
        <h4>
          {{
            politicalType
              ? politicalType.provisional
                ? "Voorlopig politiek type"
                : "Jouw politieke type"
              : "Jouw antwoorden"
          }}
        </h4>
        <ul>
          <li
            v-for="trait in politicalType?.traits || [
              `${snapshot.total} beantwoorde stellingen`,
              `${snapshot.counts.neutral} neutrale antwoorden`,
              `${compass.used_votes} gewogen antwoorden`,
            ]"
            :key="trait"
          >
            <img
              :src="`/report-art/${politicalType?.icon || 'leaf'}.svg`"
              alt=""
              width="23"
              height="23"
            />{{ trait }}
          </li>
        </ul>
      </div>
      <img class="compass-paper__wave" src="/report-art/wave.svg" alt="" />
    </section>
    <div class="compass-paper__grid">
      <section class="compass-card compass-card--wheel">
        <h3>Jouw politieke persoonlijkheid</h3>
        <span class="compass-paper__accent" />
        <img
          class="compass-paper__wheel"
          :src="`/report-art/wheel-${politicalType?.id || 'pending'}.svg`"
          :alt="
            politicalType
              ? `Politieke types, ${politicalType.name} uitgelicht`
              : 'Politieke types; er is nog geen type uitgelicht'
          "
          width="480"
          height="480"
        />
        <p class="compass-paper__caption">
          {{
            politicalType
              ? `${politicalType.name}${politicalType.provisional ? " · voorlopig" : ""} · een samenvatting van je antwoorden, geen psychologische diagnose.`
              : "Nog geen type uitgelicht. Er zijn nog niet genoeg gewogen antwoorden over alle vier de thema’s."
          }}
        </p>
      </section>
      <div class="compass-paper__scores">
        <section class="compass-card">
          <h3>Jouw positie op het politieke spectrum</h3>
          <span class="compass-paper__accent" />
          <template v-if="compass.spectrum.percentage !== null"
            ><div
              class="compass-paper__spectrum"
              role="img"
              :aria-label="`Economische as: ${compass.spectrum.percentage}% links, ${compass.spectrum.opposite_percentage}% rechts`"
            >
              <span
                class="compass-paper__you"
                :style="{ left: `${100 - compass.spectrum.percentage}%` }"
                >Jij</span
              ><span
                class="compass-paper__dot"
                :style="{ left: `${100 - compass.spectrum.percentage}%` }"
              />
            </div>
            <div class="compass-paper__axis-labels">
              <strong>Links</strong><strong>Rechts</strong>
            </div>
            <p class="compass-paper__caption">
              {{ compass.spectrum.compared }} gewogen antwoorden · economische
              as<span v-if="compass.spectrum.provisional">
                · voorlopige indicatie</span
              >
            </p></template
          >
          <p v-else class="compass-paper__empty">
            Nog geen economische aspositie: {{ compass.spectrum.compared }}/{{
              compass.spectrum.early_minimum || compass.spectrum.minimum
            }}
            gewogen antwoorden. Voor/tegen is geen links/rechtsscore.
          </p>
        </section>
        <section class="compass-card compass-card--themes">
          <h3>Jouw scores op belangrijke thema’s</h3>
          <span class="compass-paper__accent" />
          <div
            v-for="dimension in compass.dimensions"
            :key="dimension.id"
            class="compass-theme"
          >
            <div class="compass-theme__icon">
              <img
                :src="`/report-art/${dimension.icon}.svg`"
                alt=""
                width="25"
                height="25"
              />
            </div>
            <div class="compass-theme__body">
              <div class="compass-theme__labels">
                <span>{{ dimension.left }}</span
                ><span>{{ dimension.right }}</span>
              </div>
              <template v-if="dimension.percentage !== null"
                ><div
                  class="compass-theme__track"
                  role="img"
                  :aria-label="`${dimension.percentage}% ${dimension.left}, ${dimension.opposite_percentage}% ${dimension.right}, gebaseerd op ${dimension.compared} gewogen antwoorden`"
                >
                  <span :style="{ width: `${dimension.percentage}%` }" /><i
                    :style="{ left: `${dimension.percentage}%` }"
                  />
                </div>
                <div class="compass-theme__percentages">
                  <strong>{{ dimension.percentage }}%</strong
                  ><strong>{{ dimension.opposite_percentage }}%</strong>
                </div>
                <p class="compass-paper__caption">
                  {{ dimension.provisional ? "Voorlopig · " : ""
                  }}{{ dimension.compared }} gewogen antwoorden<span
                    v-if="dimension.sensitivity"
                  >
                    · gevoeligheid {{ dimension.sensitivity.min }}–{{
                      dimension.sensitivity.max
                    }}%</span
                  >
                </p></template
              >
              <template v-else
                ><div
                  class="compass-theme__track compass-theme__track--empty"
                />
                <p class="compass-paper__caption">
                  Nog niet genoeg gegevens · {{ dimension.compared }}/{{
                    dimension.early_minimum || dimension.minimum
                  }}
                  antwoorden
                </p></template
              >
            </div>
          </div>
        </section>
      </div>
      <section class="compass-card">
        <h3>
          {{
            snapshot.party_matches.length
              ? "Partijen die bij je passen"
              : compass.topics?.length
                ? "Jouw onderwerpen"
                : "Partijen die bij je passen"
          }}
        </h3>
        <span class="compass-paper__accent" />
        <p class="compass-paper__subheading">
          {{
            snapshot.party_matches.length
              ? "Overeenkomst bij officieel gekoppelde moties."
              : compass.topics?.length
                ? "Waar je eigen antwoorden over gaan. Dubbelen tellen één keer."
                : "Overeenkomst bij officieel gekoppelde moties."
          }}
        </p>
        <ol v-if="snapshot.party_matches.length" class="compass-parties">
          <li v-for="party in snapshot.party_matches" :key="party.name">
            <div>
              <strong>{{ party.name }}</strong>
              <div class="compass-theme__track">
                <span :style="{ width: `${party.percentage}%` }" />
              </div>
              <small>{{ party.matched }} van {{ party.compared }} moties</small>
            </div>
            <b>{{ party.percentage }}%</b>
          </li>
        </ol>
        <ul v-else-if="compass.topics?.length" class="compass-topics">
          <li v-for="topic in compass.topics" :key="topic.name">
            <strong>{{ topic.name }}</strong
            ><span
              >{{ topic.count }}
              {{ topic.count === 1 ? "stelling" : "stellingen" }}</span
            ><small
              >{{ topic.agree }} voor · {{ topic.disagree }} tegen ·
              {{ topic.neutral }} neutraal</small
            >
          </li>
        </ul>
        <p v-else class="compass-paper__empty">
          Nog onvoldoende gecontroleerde motiekoppelingen. Voor een
          partijovereenkomst zijn minimaal tien vergelijkbare moties per partij
          nodig.
        </p>
        <p class="compass-paper__caption">
          {{
            snapshot.party_matches.length
              ? "Overeenkomst in stemgedrag, geen stemadvies."
              : "Partijvergelijking volgt pas bij voldoende gecontroleerde officiële motiekoppelingen."
          }}
        </p>
      </section>
      <div class="compass-paper__insights">
        <section class="compass-card">
          <h3>Wat typeert jou?</h3>
          <span class="compass-paper__accent" />
          <ul v-if="compass.highlights.length" class="compass-highlights">
            <li v-for="line in compass.highlights" :key="line">
              <span aria-hidden="true">✓</span>
              <p>{{ line }}</p>
            </li>
          </ul>
          <p v-else class="compass-paper__empty">
            Je hebt {{ snapshot.total }} stellingen beantwoord:
            {{ snapshot.counts.agree }} voor,
            {{ snapshot.counts.disagree }} tegen en
            {{ snapshot.counts.neutral }} neutraal. Thematische inzichten volgen
            zodra voldoende gewogen antwoorden beschikbaar zijn.
          </p>
          <p class="compass-paper__reflection">
            {{ report.narrative.reflection }}
          </p>
        </section>
        <section class="compass-card compass-card--method">
          <div class="compass-paper__info" aria-hidden="true">i</div>
          <div>
            <h3>Hoe dit rapport werkt</h3>
            <p>
              Scores volgen uit vaste redactionele wegingen. Vanaf twee
              verschillende antwoorden per thema tonen we een voorlopige
              richting; voor een vollediger profiel zijn minimaal vijf actuele
              antwoorden per thema nodig. Historische context blijft zichtbaar.
              Dubbelen tellen eenmaal; tegenstrijdige antwoorden op dezelfde
              vraag worden uitgesloten. Neutraal geeft geen richting. Een
              voorlopig type gebruikt minstens acht gewogen vragen en twee
              beschikbare thema’s. Gevoeligheid is geen statistisch
              betrouwbaarheidsinterval.
            </p>
            <NuxtLink to="/methodologie"
              >Bekijk de methode en beperkingen ↗</NuxtLink
            >
          </div>
        </section>
      </div>
    </div>
    <footer class="compass-paper__footer">
      <span
        ><strong>{{ snapshot.total }}</strong> beantwoorde stellingen<br /><small
          >{{ compass.used_votes }} met bruikbare themagewichten ·
          {{ snapshot.counts.neutral }} neutraal<span
            v-if="compass.duplicate_answers"
          >
            · {{ compass.duplicate_answers }} dubbele antwoorden eenmaal
            geteld</span
          ><span v-if="compass.conflicting_groups">
            · {{ compass.conflicting_groups }} tegenstrijdige groepen
            uitgesloten</span
          ></small
        ></span
      ><span
        >{{ compass.version }}<br /><small>{{ date }}</small></span
      ><strong>WatDenktNederland<span>.nl</span></strong
      ><img src="/report-art/flag.svg" alt="" />
    </footer>
  </article>
</template>
<style scoped>
.compass-paper__indication {
  padding: 12px 16px;
  background: #fff5dc;
  border: 1px solid #ecd8a5;
  border-radius: 12px;
  font-size: 12px;
  line-height: 1.65;
  margin-bottom: 16px !important;
  color: #674c15;
}
.compass-topics {
  list-style: none;
  padding: 0;
  margin: 0;
  display: grid;
  gap: 10px;
}
.compass-topics li {
  border-bottom: 1px solid #e5ebf1;
  padding-bottom: 10px;
  display: grid;
  grid-template-columns: 1fr auto;
  gap: 4px;
  font-size: 12px;
}
.compass-topics small {
  grid-column: 1/-1;
  color: #52677b;
  font-size: 10px;
}
.compass-paper {
  --ink: #001f4d;
  --teal: #00877e;
  --line: #dce4ed;
  position: relative;
  max-width: 1080px;
  margin: auto;
  padding: clamp(20px, 3.5vw, 38px);
  background: #fff;
  border: 1px solid var(--line);
  border-radius: 20px;
  color: var(--ink);
  box-shadow: 0 18px 60px #001f4d0a;
  overflow: hidden;
  font-size: 14px;
  line-height: 1.5;
}
.compass-paper h2,
.compass-paper h3,
.compass-paper h4,
.compass-paper p {
  margin: 0;
}
.compass-paper h3 {
  font-size: clamp(17px, 2vw, 23px);
  font-weight: 850;
  line-height: 1.25;
  letter-spacing: -0.045em;
}
.compass-paper__header {
  display: flex;
  position: relative;
  gap: 24px;
  align-items: center;
  padding: 0 0 26px;
}
.compass-paper__header > div:nth-child(2) {
  border-left: 1px solid var(--line);
  padding-left: 24px;
  flex: 1;
  min-width: 0;
}
.compass-paper__header h2 {
  font-size: clamp(22px, 3vw, 33px);
  font-weight: 850;
  line-height: 1.13;
  letter-spacing: -0.055em;
}
.compass-paper__header p {
  font-size: clamp(11px, 1.3vw, 15px);
  margin-top: 6px;
}
.compass-paper__brand {
  display: flex;
  align-items: center;
  gap: 7px;
  flex-shrink: 0;
}
.compass-paper__brand strong {
  font-size: clamp(12px, 1.8vw, 20px);
  letter-spacing: -0.06em;
  font-weight: 850;
  white-space: nowrap;
}
.compass-paper__brand strong span {
  color: #0057b8;
}
.compass-paper__accent {
  display: block;
  width: 43px;
  height: 4px;
  background: #e1062e;
  border-radius: 5px;
  margin: 13px 0 17px;
}
.compass-paper__header .compass-paper__accent {
  margin: 13px 0 0;
}
.compass-paper__flag {
  position: absolute;
  right: -56px;
  top: -40px;
  width: 100px;
  opacity: 0.95;
  pointer-events: none;
}
.compass-paper__hero {
  display: flex;
  align-items: center;
  position: relative;
  gap: 20px;
  padding: 26px 24px;
  border: 1px solid #d5eae7;
  border-radius: 18px;
  background: linear-gradient(112deg, #f0faf9, #e4f4f2);
  margin-bottom: 20px;
  isolation: isolate;
}
.compass-paper__hero-icon {
  display: grid;
  place-items: center;
  width: 98px;
  height: 108px;
  flex-shrink: 0;
  border-radius: 23px;
  background: linear-gradient(135deg, #08aea2, #007f78);
  box-shadow: inset 0 0 0 1px #ffffff55;
}
.compass-paper__hero-copy {
  flex: 1;
  min-width: 0;
}
.compass-paper__hero-copy h3 {
  font-size: clamp(24px, 3.4vw, 37px);
  line-height: 1.1;
  margin-bottom: 10px;
}
.compass-paper__hero-copy p {
  font-size: 14px;
  line-height: 1.6;
}
.compass-paper__traits {
  border-left: 1px solid #9bcfca;
  padding-left: 20px;
  flex: 0 0 215px;
}
.compass-paper__traits h4 {
  background: #00887f;
  color: white;
  border-radius: 24px;
  text-align: center;
  padding: 7px 9px;
  font-size: 14px;
  font-weight: 800;
  margin-bottom: 12px;
}
.compass-paper__traits ul {
  display: grid;
  gap: 10px;
  list-style: none;
  padding: 0;
  margin: 0;
}
.compass-paper__traits li {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 12px;
}
.compass-paper__wave {
  position: absolute;
  bottom: 0;
  left: 0;
  width: 38%;
  z-index: -1;
  border-bottom-left-radius: 18px;
  pointer-events: none;
}
.compass-paper__grid {
  display: grid;
  grid-template-columns: 1fr 1.12fr;
  gap: 18px;
  align-items: start;
}
.compass-card {
  border: 1px solid var(--line);
  border-radius: 15px;
  padding: 20px;
  min-width: 0;
  background: linear-gradient(145deg, #fff, #fbfcfd);
}
.compass-paper__scores,
.compass-paper__insights {
  display: grid;
  gap: 16px;
  min-width: 0;
}
.compass-paper__wheel {
  display: block;
  width: 100%;
  height: auto;
  margin: -5px auto -2px;
}
.compass-paper .compass-paper__caption {
  font-size: 10px;
  line-height: 1.6;
  color: #52677b;
  margin-top: 5px;
}
.compass-paper__spectrum {
  height: 19px;
  border-radius: 20px;
  background: linear-gradient(90deg, #0057b8, #aeb9ce, #e1062e);
  position: relative;
  margin: 44px 10px 10px;
}
.compass-paper__dot {
  position: absolute;
  width: 25px;
  height: 25px;
  background: #008c83;
  border: 4px solid white;
  border-radius: 50%;
  top: -3px;
  transform: translateX(-50%);
  box-shadow: 0 2px 5px #001f4d25;
}
.compass-paper__you {
  position: absolute;
  top: -39px;
  border-radius: 15px;
  background: #008b82;
  color: white;
  padding: 3px 13px;
  font-weight: 800;
  font-size: 12px;
  transform: translateX(-50%);
}
.compass-paper__you:after {
  content: "";
  position: absolute;
  top: 100%;
  left: calc(50% - 5px);
  border-top: 7px solid #008b82;
  border-left: 5px solid transparent;
  border-right: 5px solid transparent;
}
.compass-paper__axis-labels {
  display: flex;
  justify-content: space-between;
  font-size: 13px;
}
.compass-theme {
  display: flex;
  gap: 12px;
  padding: 13px 0;
  border-bottom: 1px solid #e6ebf1;
}
.compass-theme:last-child {
  padding-bottom: 0;
  border: 0;
}
.compass-theme__icon {
  display: grid;
  place-items: center;
  flex: 0 0 36px;
  height: 38px;
  background: #eaf4f3;
  border-radius: 10px;
}
.compass-theme__body {
  min-width: 0;
  flex: 1;
}
.compass-theme__labels {
  display: flex;
  justify-content: space-between;
  gap: 10px;
  font-size: 12px;
  font-weight: 650;
}
.compass-theme__labels span:last-child {
  text-align: right;
}
.compass-theme__track {
  height: 10px;
  background: #dce3e9;
  border-radius: 15px;
  position: relative;
  margin: 10px 0 6px;
}
.compass-theme__track > span {
  display: block;
  height: 100%;
  background: linear-gradient(90deg, #009b8f, #00877f);
  border-radius: inherit;
}
.compass-theme__track i {
  position: absolute;
  width: 19px;
  height: 19px;
  top: -4px;
  background: #00877f;
  border: 4px solid white;
  box-shadow: 0 1px 5px #001f4d30;
  border-radius: 50%;
  transform: translateX(-50%);
}
.compass-theme__track--empty {
  background: #edf1f5;
}
.compass-theme__percentages {
  display: flex;
  justify-content: space-between;
  font-size: 12px;
}
.compass-paper .compass-paper__empty {
  background: #f2f7f9;
  border: 1px solid #e4ecef;
  border-radius: 10px;
  padding: 14px;
  color: #52677b;
  font-size: 12px;
  line-height: 1.75;
}
.compass-paper__subheading {
  font-size: 12px;
  color: #52677b;
  margin-bottom: 14px !important;
}
.compass-parties {
  padding: 0;
  margin: 0;
  list-style: none;
  display: grid;
  gap: 9px;
}
.compass-parties li {
  display: flex;
  align-items: center;
  gap: 14px;
  border: 1px solid #e5ebf1;
  border-radius: 12px;
  padding: 12px;
  background: white;
}
.compass-parties li > div {
  flex: 1;
  min-width: 0;
}
.compass-parties strong {
  font-size: 12px;
  overflow-wrap: anywhere;
}
.compass-parties b {
  font-size: 23px;
  letter-spacing: -0.06em;
  white-space: nowrap;
}
.compass-parties small {
  font-size: 10px;
  color: #52677b;
}
.compass-parties .compass-theme__track {
  margin: 8px 0 2px;
  height: 9px;
}
.compass-highlights {
  padding: 0;
  margin: 0;
  list-style: none;
  display: grid;
  gap: 14px;
}
.compass-highlights li {
  display: flex;
  align-items: flex-start;
  gap: 12px;
}
.compass-highlights li > span {
  flex: 0 0 23px;
  line-height: 23px;
  text-align: center;
  border-radius: 50%;
  background: #008b82;
  color: white;
  font-size: 15px;
  font-weight: 800;
}
.compass-highlights p {
  font-size: 13px;
  line-height: 1.6;
}
.compass-paper .compass-paper__reflection {
  font-size: 11px;
  line-height: 1.7;
  color: #52677b;
  margin-top: 17px;
}
.compass-card--method {
  display: flex;
  gap: 13px;
  background: #f5fbfa;
}
.compass-card--method h3 {
  font-size: 18px;
}
.compass-card--method p {
  font-size: 10px;
  line-height: 1.7;
  margin-top: 9px;
}
.compass-card--method a {
  font-size: 10px;
  font-weight: 800;
  color: #00786f;
  display: inline-block;
  margin-top: 8px;
}
.compass-paper__info {
  display: grid;
  place-items: center;
  background: #d5efeb;
  border-radius: 9px;
  width: 33px;
  height: 37px;
  flex-shrink: 0;
  font-size: 24px;
  color: #00877e;
  font-family: serif;
  font-style: italic;
  font-weight: bold;
}
.compass-paper__footer {
  display: flex;
  gap: 18px;
  justify-content: space-between;
  align-items: center;
  border-top: 1px solid var(--line);
  margin-top: 24px;
  padding-top: 19px;
  font-size: 11px;
  position: relative;
}
.compass-paper__footer > span + span {
  border-left: 1px solid var(--line);
  padding-left: 18px;
}
.compass-paper__footer small {
  font-size: 9px;
  color: #52677b;
}
.compass-paper__footer > strong {
  font-size: 14px;
  letter-spacing: -0.04em;
}
.compass-paper__footer > strong > span {
  color: #0057b8;
}
.compass-paper__footer > img {
  position: absolute;
  width: 100px;
  right: -43px;
  bottom: -40px;
  opacity: 0.8;
  pointer-events: none;
}
.compass-paper__dev {
  padding: 8px 12px !important;
  margin-bottom: 17px !important;
  background: #fff5c4;
  border: 1px dashed #987300;
  border-radius: 6px;
  font-size: 10px;
  font-weight: 800;
  color: #594000;
}
@media (max-width: 1050px) {
  .compass-paper__hero {
    flex-wrap: wrap;
  }
  .compass-paper__traits {
    flex-basis: 100%;
    border-left: 0;
    padding-left: 0;
    border-top: 1px solid #b9dcd8;
    padding-top: 16px;
    display: flex;
    align-items: center;
    gap: 15px;
  }
  .compass-paper__traits h4 {
    margin-bottom: 0;
    flex-shrink: 0;
  }
  .compass-paper__traits ul {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
  }
  .compass-paper__traits li {
    font-size: 11px;
  }
  .compass-paper__header {
    gap: 17px;
  }
  .compass-paper__header > div:nth-child(2) {
    padding-left: 17px;
  }
  .compass-card {
    padding: 16px;
  }
}
@media (max-width: 740px) {
  .compass-paper__grid {
    grid-template-columns: 1fr;
  }
  .compass-paper__header {
    flex-wrap: wrap;
    gap: 16px;
  }
  .compass-paper__header > div:nth-child(2) {
    flex-basis: 100%;
    border: 0;
    padding: 0;
  }
  .compass-paper__brand strong {
    font-size: 19px;
  }
  .compass-paper__header h2 {
    font-size: 29px;
  }
  .compass-paper__header p {
    font-size: 12px;
  }
  .compass-paper__hero {
    padding: 20px;
    gap: 15px;
  }
  .compass-paper__hero-icon {
    width: 67px;
    height: 76px;
    border-radius: 17px;
  }
  .compass-paper__hero-icon img {
    width: 44px;
    height: 44px;
  }
  .compass-paper__hero-copy h3 {
    font-size: 26px;
  }
  .compass-paper__hero-copy p {
    font-size: 12px;
  }
  .compass-paper__traits {
    display: block;
  }
  .compass-paper__traits h4 {
    display: inline-block;
    font-size: 12px;
    margin-bottom: 12px;
  }
  .compass-paper__traits ul {
    display: grid;
    gap: 9px;
  }
  .compass-paper__traits li {
    font-size: 12px;
  }
  .compass-paper__wheel {
    max-width: 440px;
  }
  .compass-paper__footer {
    flex-wrap: wrap;
    gap: 15px;
  }
  .compass-card h3 {
    font-size: 21px;
  }
}
@media (max-width: 380px) {
  .compass-paper {
    padding: 16px;
  }
  .compass-paper__hero {
    padding: 16px;
  }
  .compass-paper__hero-copy {
    flex-basis: 100%;
  }
  .compass-theme {
    gap: 8px;
  }
  .compass-theme__icon {
    flex-basis: 30px;
    width: 30px;
    height: 34px;
  }
  .compass-theme__labels {
    font-size: 11px;
  }
  .compass-card {
    padding: 14px;
  }
  .compass-paper__brand strong {
    font-size: 17px;
  }
  .compass-paper__header h2 {
    font-size: 26px;
  }
  .compass-paper__footer > span + span {
    border: 0;
    padding: 0;
  }
}
@media print {
  .compass-paper {
    box-shadow: none;
    border: 0;
  }
  .compass-paper__dev {
    display: none;
  }
}
</style>
