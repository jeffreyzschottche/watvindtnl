<script setup lang="ts">
import { gsap } from "gsap";
import type { CompassAnalysis } from "~/types/voteReports";

const props = defineProps<{
  type: CompassAnalysis["type"];
  catalogue: NonNullable<CompassAnalysis["type_catalogue"]>;
  animate: boolean;
}>();
const root = ref<HTMLElement | null>(null);
const selectedId = ref(props.type?.id || props.catalogue[0]?.id || "pending");
const hoveredType = ref("");
const selected = computed(() =>
  props.catalogue.find((item) => item.id === selectedId.value),
);
const isOwn = computed(() => selectedId.value === props.type?.id);
let context: gsap.Context | undefined;
let version = 0;
let disposed = false;

function select(id: string) {
  if (props.catalogue.some((item) => item.id === id)) selectedId.value = id;
}
function step(direction: number) {
  const length = props.catalogue.length;
  if (!length) return;
  const index = props.catalogue.findIndex(
    (item) => item.id === selectedId.value,
  );
  select(props.catalogue[(index + direction + length) % length]!.id);
}
function onKey(event: KeyboardEvent, index: number) {
  const length = props.catalogue.length;
  let next = index;
  if (["ArrowRight", "ArrowDown"].includes(event.key))
    next = (index + 1) % length;
  else if (["ArrowLeft", "ArrowUp"].includes(event.key))
    next = (index - 1 + length) % length;
  else if (event.key === "Home") next = 0;
  else if (event.key === "End") next = length - 1;
  else if (["Enter", " "].includes(event.key)) {
    event.preventDefault();
    select(props.catalogue[index]!.id);
    return;
  } else return;
  event.preventDefault();
  select(props.catalogue[next]!.id);
  root.value
    ?.querySelector<SVGElement>(`[data-sector-index="${next}"]`)
    ?.focus();
}
function iconPosition(index: number) {
  const angle = ((-90 + index * 22.5) * Math.PI) / 180;
  return {
    x: 310 + 130 * Math.cos(angle) - 13.5,
    y: 300 + 130 * Math.sin(angle) - 13.5,
  };
}
function sector(index: number, inset = false) {
  const trim = inset ? 3 : 0;
  const start = ((-101.25 + index * 22.5 + trim) * Math.PI) / 180;
  const end = ((-78.75 + index * 22.5 - trim) * Math.PI) / 180;
  const inner = inset ? 75 : 69;
  const outer = inset ? 174 : 180;
  const point = (radius: number, angle: number) =>
    `${(310 + radius * Math.cos(angle)).toFixed(2)},${(300 + radius * Math.sin(angle)).toFixed(2)}`;
  return `M${point(inner, start)}L${point(outer, start)}A${outer},${outer} 0 0 1 ${point(outer, end)}L${point(inner, end)}A${inner},${inner} 0 0 0 ${point(inner, start)}Z`;
}

watch([() => props.type?.id, () => props.catalogue], () => {
  selectedId.value = props.type?.id || props.catalogue[0]?.id || "pending";
});
watch(
  () => props.animate,
  (enabled) => {
    if (!enabled) {
      version++;
      context?.revert();
    }
  },
);
watch(selectedId, async () => {
  const current = ++version;
  context?.revert();
  await nextTick();
  const scope = root.value;
  if (!scope || !props.animate || disposed) return;
  if (current !== version || disposed || !props.animate) return;
  context = gsap.context(() => {
    gsap.fromTo(
      ".poster-wheel",
      { opacity: 0.55 },
      {
        opacity: 1,
        duration: 0.35,
        ease: "power2.out",
        clearProps: "opacity,transform",
      },
    );
    gsap.fromTo(
      ".type-explorer__copy",
      { y: 6, opacity: 0.6 },
      {
        y: 0,
        opacity: 1,
        duration: 0.3,
        ease: "power2.out",
        clearProps: "opacity,transform",
      },
    );
  }, scope);
});
onBeforeUnmount(() => {
  disposed = true;
  version++;
  context?.revert();
});
</script>
<template>
  <div
    ref="root"
    class="type-wheel"
    :style="{ '--preview-color': selected?.color || type?.color || '#008f87' }"
  >
    <div class="type-wheel__picture" data-report-wheel>
      <svg
        v-if="catalogue.length === 16"
        class="poster-wheel"
        viewBox="90 80 440 440"
        role="group"
        aria-label="Politieke typen verkennen"
      >
        <g
          v-for="(item, index) in catalogue"
          :key="item.id"
          :style="{ '--sector-color': item.color }"
        >
          <path
            :d="sector(index)"
            class="type-wheel__sector"
            :fill="
              selectedId === item.id
                ? item.color
                : index % 2
                  ? '#c4d5df'
                  : '#d8e4eb'
            "
            role="button"
            :tabindex="selectedId === item.id ? 0 : -1"
            :aria-label="`${item.name} bekijken`"
            :aria-pressed="selectedId === item.id"
            :data-sector-index="index"
            @click="select(item.id)"
            @keydown="onKey($event, index)"
            @pointerenter="
              (event) => {
                if (event.pointerType === 'mouse') hoveredType = item.name;
              }
            "
            @pointerleave="hoveredType = ''"
          />
          <path
            :d="sector(index, true)"
            class="type-wheel__sector-focus"
            aria-hidden="true"
          />
          <image
            :href="`/report-art/${item.icon}-white.svg`"
            :x="iconPosition(index).x"
            :y="iconPosition(index).y"
            width="27"
            height="27"
            aria-hidden="true"
            class="type-wheel__icon"
          />
        </g>
        <circle cx="310" cy="300" r="67" fill="white" aria-hidden="true" />
        <image
          href="/brand/wdnl-mark-concept.svg"
          x="277"
          y="260"
          width="66"
          height="80"
          aria-hidden="true"
          class="type-wheel__icon"
        />
      </svg>
      <img
        v-else
        class="poster-wheel"
        :src="`/report-art/wheel-v4-${type?.id || 'pending'}.svg`"
        :alt="
          type
            ? `Zestien politieke typen, ${type.name} uitgelicht`
            : 'Zestien politieke typen; nog geen type uitgelicht'
        "
        width="620"
        height="600"
      />
    </div>
    <template v-if="catalogue.length">
      <p class="type-wheel__hint">
        {{ hoveredType || "Klik op het wiel of blader door de typen." }}
      </p>
      <div class="type-wheel__navigation">
        <button
          type="button"
          aria-label="Vorig type bekijken"
          :disabled="catalogue.length < 2"
          @click="step(-1)"
        >
          ←
        </button>
        <select
          :value="selectedId"
          aria-label="Bekijk een politiek type"
          @change="select(($event.target as HTMLSelectElement).value)"
        >
          <option v-for="item in catalogue" :key="item.id" :value="item.id">
            {{ item.name }}{{ item.id === type?.id ? " · jouw type" : "" }}
          </option>
        </select>
        <button
          type="button"
          aria-label="Volgend type bekijken"
          :disabled="catalogue.length < 2"
          @click="step(1)"
        >
          →
        </button>
      </div>
      <div class="type-explorer" :data-preview-type="selectedId">
        <div class="type-explorer__copy" aria-live="polite" aria-atomic="true">
          <span class="type-explorer__badge">{{
            isOwn
              ? type?.provisional
                ? "Jouw voorlopige type"
                : "Jouw type"
              : "Een ander type ontdekken"
          }}</span>
          <h4>{{ selected?.name }}</h4>
          <p>{{ selected?.description }}</p>
        </div>
        <button
          v-if="type && !isOwn"
          type="button"
          class="type-explorer__reset"
          @click="select(type.id)"
        >
          Terug naar mijn type
        </button>
      </div>
    </template>
    <p v-else class="type-wheel__caption">
      {{ type?.name || "Meer gewogen antwoorden nodig voor een type"
      }}<span v-if="type?.provisional"> · voorlopige indicatie</span>
    </p>
  </div>
</template>
<style scoped>
.type-wheel {
  display: flex;
  flex-direction: column;
  flex: 1;
  min-width: 0;
}
.type-wheel__picture {
  position: relative;
  width: min(100%, 365px);
  margin: -0.6rem auto -0.6rem;
  aspect-ratio: 1;
}
.poster-wheel {
  display: block;
  width: 100%;
  height: auto;
  overflow: visible;
}
.type-wheel__icon {
  pointer-events: none;
}
.type-wheel__sector {
  stroke: white;
  stroke-width: 3;
  cursor: pointer;
}
.poster-wheel .type-wheel__sector:focus {
  outline: none;
}
.type-wheel__sector-focus {
  fill: none;
  stroke: #001f4d;
  stroke-width: 2.5;
  vector-effect: non-scaling-stroke;
  opacity: 0;
  pointer-events: none;
}
.type-wheel__sector[aria-pressed="true"] + .type-wheel__sector-focus {
  stroke: white;
}
.type-wheel__sector:focus-visible + .type-wheel__sector-focus {
  opacity: 1;
}
@media (hover: hover) {
  .type-wheel__sector[aria-pressed="false"]:hover {
    fill: color-mix(in srgb, var(--sector-color) 65%, #d8e4eb);
  }
}
@media (forced-colors: active) {
  .type-wheel__sector:focus-visible + .type-wheel__sector-focus {
    stroke: Highlight;
    forced-color-adjust: none;
  }
}
.type-wheel__hint {
  text-align: center;
  font-size: 0.63rem;
  color: #53697d;
  margin: 0.2rem 0 0.7rem;
  line-height: 1.5;
}
.type-wheel__navigation {
  display: grid;
  grid-template-columns: 40px minmax(0, 1fr) 40px;
  gap: 0.4rem;
  margin-top: auto;
}
.type-wheel__navigation button,
.type-wheel__navigation select {
  font: inherit;
  color: #001f4d;
  border: 1px solid #cddce7;
  background: #f8fafc;
  border-radius: 10px;
  min-height: 44px;
}
.type-wheel__navigation button {
  font-size: 1.1rem;
  font-weight: 700;
  cursor: pointer;
}
.type-wheel__navigation button:hover {
  background: #e7f2f1;
}
.type-wheel__navigation select {
  width: 100%;
  min-width: 0;
  padding: 0.5rem 0.45rem;
  font-size: 0.72rem;
  cursor: pointer;
}
.type-explorer {
  margin-top: 0.7rem;
  padding: 0.9rem 1rem;
  border: 1px solid #cde3e1;
  border-left: 3px solid var(--preview-color);
  border-radius: 11px;
  background: linear-gradient(120deg, #f0f8f6, #f7fafc);
}
.type-explorer__badge {
  display: block;
  color: var(--preview-color);
  font-size: 0.6rem;
  font-weight: 800;
  letter-spacing: 0.07em;
  text-transform: uppercase;
}
.type-explorer h4 {
  font-size: 1rem;
  line-height: 1.3;
  letter-spacing: -0.02em;
  margin: 0.3rem 0 0.4rem;
}
.type-explorer p {
  font-size: 0.73rem;
  line-height: 1.65;
  margin: 0;
  color: #425c72;
}
.type-explorer__reset {
  font: inherit;
  font-size: 0.66rem;
  color: #0057b8;
  text-decoration: underline;
  text-underline-offset: 3px;
  padding: 0.5rem 0 0;
  background: none;
  border: 0;
  cursor: pointer;
  min-height: 32px;
}
.type-wheel__caption {
  font-size: 0.65rem;
  line-height: 1.6;
  text-align: center;
  color: var(--preview-color);
}
button:focus-visible,
select:focus-visible {
  outline: 3px solid #0057b8;
  outline-offset: 3px;
}
@media (max-width: 650px) {
  .type-wheel__picture {
    width: 100%;
    max-width: 310px;
    margin-inline: auto;
  }
  .type-wheel__navigation {
    grid-template-columns: 38px minmax(0, 1fr) 38px;
    gap: 0.3rem;
  }
  .type-wheel__navigation select {
    font-size: 0.65rem;
  }
  .type-explorer {
    padding: 0.8rem;
  }
  .type-explorer h4 {
    font-size: 0.95rem;
  }
}
@media print {
  .type-wheel__controls,
  .type-wheel__hint,
  .type-wheel__navigation,
  .type-explorer {
    display: none;
  }
}
</style>
