<script setup lang="ts">
import AppIcon from "~/components/ui/AppIcon.vue";
import type { CompassExample } from "~/types/voteReports";

const props = defineProps<{ examples: CompassExample[] }>();
const selectedId = ref("groene-vernieuwer");
const selected = computed(
  () =>
    props.examples.find(({ id }) => id === selectedId.value) ??
    props.examples[0],
);

function step(direction: number) {
  const index = props.examples.findIndex(({ id }) => id === selected.value?.id);
  const count = props.examples.length;
  const next = props.examples[(index + direction + count) % count];
  if (next) selectedId.value = next.id;
}
</script>

<template>
  <section
    v-if="selected"
    class="report-examples"
    aria-labelledby="report-examples-title"
  >
    <div class="report-examples__header">
      <div>
        <h2 id="report-examples-title">
          Verken de {{ examples.length }} kompastypen
        </h2>
        <p>Bekijk hoe verschillende antwoorden eruitzien in een rapport.</p>
      </div>
      <div class="report-examples__controls">
        <button
          type="button"
          aria-label="Vorig voorbeeldtype"
          @click="step(-1)"
        >
          <AppIcon name="chevron" :size="18" />
        </button>
        <select v-model="selectedId" aria-label="Kies een voorbeeldtype">
          <option
            v-for="example in examples"
            :key="example.id"
            :value="example.id"
          >
            {{ example.name }}
          </option>
        </select>
        <button
          type="button"
          aria-label="Volgend voorbeeldtype"
          @click="step(1)"
        >
          <AppIcon name="chevron" :size="18" />
        </button>
      </div>
    </div>

    <div
      class="report-examples__body"
      :style="{ '--type-color': selected.color }"
    >
      <div class="report-examples__art" aria-hidden="true">
        <div class="report-examples__medallion">
          <img
            :src="`/report-art/${selected.icon}-white.svg`"
            alt=""
            width="144"
            height="144"
          />
        </div>
      </div>
      <article
        class="report-examples__report"
        aria-labelledby="report-example-name"
      >
        <div aria-live="polite" aria-atomic="true">
          <p class="report-examples__eyebrow">
            Voorbeeldrapport · fictieve antwoorden
          </p>
          <h3 id="report-example-name">{{ selected.name }}</h3>
          <p class="report-examples__insight">{{ selected.description }}</p>
        </div>
        <ul
          class="report-examples__themes"
          aria-label="Themascores in dit voorbeeld"
        >
          <li v-for="dimension in selected.dimensions" :key="dimension.left">
            <div class="report-examples__theme-labels">
              <span
                >{{ dimension.left }}
                <strong>{{ dimension.percentage }}%</strong></span
              >
              <span
                ><strong>{{ 100 - dimension.percentage }}%</strong>
                {{ dimension.right }}</span
              >
            </div>
            <div class="report-examples__track" aria-hidden="true">
              <span :style="{ width: `${dimension.percentage}%` }" />
            </div>
          </li>
        </ul>
        <a
          href="/examples/WDNL-kompas-voorbeeld.pdf"
          class="report-examples__pdf"
          target="_blank"
          rel="noopener"
        >
          Bekijk het volledige opmaakvoorbeeld (PDF)
          <AppIcon name="arrow" :size="16" />
        </a>
      </article>
    </div>
  </section>
</template>

<style scoped>
.report-examples {
  --ink: #001f4d;
  --muted: #4e617b;
  color: var(--ink);
}
.report-examples__header {
  display: flex;
  align-items: end;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 1rem 2rem;
  margin-bottom: 1.25rem;
}
.report-examples__header h2 {
  margin: 0 0 0.35rem;
  font-size: clamp(1.5rem, 3vw, 2.1rem);
  letter-spacing: -0.04em;
}
.report-examples__header p {
  margin: 0;
  color: var(--muted);
}
.report-examples__controls {
  display: grid;
  grid-template-columns: 44px minmax(0, 1fr) 44px;
  width: min(100%, 350px);
  gap: 0.35rem;
}
.report-examples__controls > * {
  min-width: 0;
  min-height: 44px;
  border: 1px solid #d2ddeb;
  border-radius: 11px;
  background: #fff;
  color: var(--ink);
  font: inherit;
  font-size: 0.85rem;
  font-weight: 750;
  cursor: pointer;
}
.report-examples__controls button {
  display: grid;
  place-items: center;
}
.report-examples__controls button:hover {
  background: #edf3ff;
}
.report-examples__controls button:first-child svg {
  transform: rotate(180deg);
}
.report-examples__controls select {
  padding-inline: 0.6rem;
}
.report-examples__body {
  display: grid;
  grid-template-columns: minmax(0, 0.85fr) minmax(0, 1.15fr);
  overflow: hidden;
  border: 1px solid #dce5ef;
  border-radius: 28px;
  background: white;
  box-shadow: 0 5px 0 #e5edf8;
}
.report-examples__art {
  --wash: color-mix(in srgb, var(--type-color) 10%, white);
  --light: color-mix(in srgb, var(--type-color) 63%, white);
  --dark: color-mix(in srgb, var(--type-color) 70%, #061c36);
  position: relative;
  isolation: isolate;
  display: grid;
  place-items: center;
  min-height: 320px;
  background:
    radial-gradient(ellipse at 50% 42%, #ffffffc9 0%, transparent 64%),
    linear-gradient(
      143deg,
      var(--wash),
      color-mix(in srgb, var(--type-color) 19%, #f8fbff)
    );
}
.report-examples__art::before {
  content: "";
  position: absolute;
  inset: 12% 10%;
  border: 1px solid #ffffffb3;
  border-radius: 50%;
  box-shadow:
    0 0 0 28px #ffffff36,
    0 0 0 54px #ffffff16;
  z-index: -1;
}
.report-examples__medallion {
  display: grid;
  place-items: center;
  width: min(66%, 245px);
  aspect-ratio: 1;
  border: 1px solid #ffffff62;
  border-radius: 32%;
  background:
    radial-gradient(ellipse at 25% 9%, #ffffff45, transparent 63%),
    linear-gradient(145deg, var(--light), var(--type-color) 55%, var(--dark));
  box-sizing: border-box;
  box-shadow:
    inset 0 2px 4px #ffffff52,
    inset 0 -7px 12px #00152322,
    0 12px 0 var(--dark),
    6px 27px 38px color-mix(in srgb, var(--dark) 33%, transparent);
  transform: translateY(-7px) rotate(-9deg);
}
.report-examples__medallion img {
  width: 52%;
  height: auto;
  filter: drop-shadow(0 4px 2px #00152357);
}
.report-examples__report {
  display: grid;
  align-content: center;
  gap: 1.45rem;
  min-width: 0;
  padding: clamp(1.3rem, 4vw, 2.75rem);
}
.report-examples__eyebrow {
  margin: 0 0 0.55rem;
  color: var(--muted);
  font-size: 0.7rem;
  font-weight: 800;
  letter-spacing: 0.07em;
  text-transform: uppercase;
}
.report-examples__report h3 {
  margin: 0;
  font-size: clamp(1.65rem, 3.2vw, 2.55rem);
  line-height: 1.1;
  letter-spacing: -0.05em;
}
.report-examples__insight {
  color: var(--muted);
  line-height: 1.65;
  margin: 0.65rem 0 0;
}
.report-examples__themes {
  display: grid;
  gap: 0.75rem;
  padding: 0;
  margin: 0;
  list-style: none;
}
.report-examples__theme-labels {
  display: flex;
  justify-content: space-between;
  gap: 0.5rem;
  color: var(--muted);
  font-size: 0.78rem;
  margin-bottom: 0.35rem;
}
.report-examples__theme-labels span:last-child {
  text-align: right;
}
.report-examples__theme-labels strong {
  color: var(--ink);
  font-variant-numeric: tabular-nums;
}
.report-examples__track {
  position: relative;
  height: 8px;
  overflow: hidden;
  border-radius: 10px;
  background: #e7edf4;
}
.report-examples__track::after {
  content: "";
  position: absolute;
  inset: 0 auto 0 50%;
  width: 1px;
  background: #fff;
}
.report-examples__track span {
  display: block;
  height: 100%;
  border-radius: inherit;
  background: var(--type-color);
}
.report-examples__pdf {
  display: inline-flex;
  align-items: center;
  justify-self: start;
  gap: 0.45rem;
  color: #0057b8;
  font-size: 0.85rem;
  font-weight: 750;
  text-underline-offset: 3px;
}
.report-examples__pdf svg {
  flex-shrink: 0;
}
@media (max-width: 720px) {
  .report-examples__body {
    grid-template-columns: 1fr;
  }
  .report-examples__art {
    min-height: 225px;
  }
  .report-examples__medallion {
    width: 134px;
  }
}
</style>
