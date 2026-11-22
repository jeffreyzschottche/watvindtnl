<script setup lang="ts">
import PosterReportSheet from "~/components/reports/PosterReportSheet.vue";
import AppIcon from "~/components/ui/AppIcon.vue";
import type { CompassExample } from "~/types/voteReports";
import { compassExampleReport } from "~/utils/compassExampleReport";

const props = defineProps<{ examples: CompassExample[] }>();
const selectedId = ref("groene-vernieuwer");
const selected = computed(
  () =>
    props.examples.find(({ id }) => id === selectedId.value) ??
    props.examples[0],
);
const report = computed(() =>
  selected.value ? compassExampleReport(selected.value, props.examples) : null,
);

function step(direction: number) {
  const count = props.examples.length;
  const index = props.examples.findIndex(({ id }) => id === selected.value?.id);
  const next = props.examples[(index + direction + count) % count];
  if (next) selectedId.value = next.id;
}
</script>

<template>
  <section
    v-if="selected && report"
    class="report-examples"
    aria-labelledby="report-examples-title"
  >
    <header class="report-examples__header">
      <h2 id="report-examples-title">
        Verken de {{ examples.length }} kompastypen
      </h2>
      <p>Kies een type om het volledige voorbeeldrapport te bekijken.</p>
      <div class="report-examples__controls">
        <button
          type="button"
          aria-label="Vorig voorbeeldtype"
          :disabled="examples.length < 2"
          @click="step(-1)"
        >
          <AppIcon name="chevron" :size="19" />
        </button>
        <div class="report-examples__select">
          <select
            :value="selected.id"
            aria-label="Kies een voorbeeldtype"
            @change="selectedId = ($event.target as HTMLSelectElement).value"
          >
            <option
              v-for="example in examples"
              :key="example.id"
              :value="example.id"
            >
              {{ example.name }}
            </option>
          </select>
          <AppIcon name="chevron" :size="17" />
        </div>
        <button
          type="button"
          aria-label="Volgend voorbeeldtype"
          :disabled="examples.length < 2"
          @click="step(1)"
        >
          <AppIcon name="chevron" :size="19" />
        </button>
      </div>
    </header>

    <PosterReportSheet
      :report="report"
      example
      @select-type="selectedId = $event"
    />
  </section>
</template>

<style scoped>
.report-examples__header {
  text-align: center;
  margin-bottom: clamp(1rem, 3vw, 1.65rem);
}
.report-examples__header h2 {
  margin: 0 0 0.4rem;
  font-size: clamp(1.5rem, 3vw, 2.1rem);
  letter-spacing: -0.04em;
}
.report-examples__header p {
  margin: 0;
  color: #4e617b;
}
.report-examples__controls {
  display: grid;
  grid-template-columns: 46px minmax(0, 1fr) 46px;
  gap: 0.45rem;
  width: min(100%, 440px);
  margin: 1.25rem auto 0;
}
.report-examples__controls button,
.report-examples__select select {
  min-width: 0;
  min-height: 46px;
  border: 1px solid #cbd9eb;
  border-radius: 12px;
  background-color: white;
  box-shadow: 0 2px 0 #e2eaf5;
  color: #001f4d;
  cursor: pointer;
}
.report-examples__controls button {
  display: grid;
  place-items: center;
}
.report-examples__controls button:not(:disabled):hover,
.report-examples__select select:hover {
  background-color: #f2f6fd;
}
.report-examples__controls button:disabled {
  opacity: 0.5;
  cursor: default;
}
.report-examples__controls button:first-child svg {
  transform: rotate(180deg);
}
.report-examples__select {
  position: relative;
  min-width: 0;
}
.report-examples__select select {
  appearance: none;
  width: 100%;
  padding-inline: clamp(1.5rem, 3vw, 2.15rem);
  font: inherit;
  font-size: clamp(0.77rem, 2.7vw, 0.94rem);
  font-weight: 800;
  text-align: center;
  text-align-last: center;
  text-overflow: ellipsis;
}
.report-examples__select svg {
  position: absolute;
  inset-inline-end: 0.65rem;
  top: 50%;
  transform: translateY(-50%) rotate(90deg);
  pointer-events: none;
}
.report-examples :deep(.compass-poster) {
  box-shadow:
    0 6px 0 #e5edf8,
    0 18px 48px #001f4d0d;
}
@media (max-width: 440px) {
  .report-examples__controls {
    grid-template-columns: 40px minmax(0, 1fr) 40px;
    gap: 0.35rem;
  }
  .report-examples__select svg {
    inset-inline-end: 0.4rem;
  }
}
</style>
