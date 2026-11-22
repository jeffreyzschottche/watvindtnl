<script setup lang="ts">
/** Big, unmissable status: how many answers are still needed before the compass report can be bought. */
import AppIcon from "~/components/ui/AppIcon.vue";

const props = withDefaults(defineProps<{ hideWhenComplete?: boolean }>(), {
  hideWhenComplete: false,
});
const { data, refresh } = useCompassProgress();
onMounted(refresh);

const complete = computed(() => !!data.value && data.value.needed === 0);
const percent = computed(() =>
  data.value
    ? Math.min(100, Math.round((100 * data.value.done) / Math.max(1, data.value.target)))
    : 0,
);
</script>

<template>
  <section
    v-if="data && !(complete && props.hideWhenComplete)"
    class="compass-banner"
    :class="{ 'compass-banner--complete': complete }"
    aria-labelledby="compass-banner-title"
  >
    <div v-if="!complete" class="compass-banner__count" aria-hidden="true">
      <strong>{{ data.needed }}</strong>
      <span>{{ data.needed === 1 ? "vraag" : "vragen" }}</span>
    </div>
    <div v-else class="compass-banner__count compass-banner__count--done" aria-hidden="true">
      <AppIcon name="check" :size="44" />
    </div>

    <div class="compass-banner__copy">
      <h2 id="compass-banner-title">
        <template v-if="!complete">
          Nog {{ data.needed }} {{ data.needed === 1 ? "vraag" : "vragen" }} tot
          je politieke kompas
        </template>
        <template v-else>Je politieke kompas is klaar</template>
      </h2>
      <p v-if="!complete">
        Beantwoord nog {{ data.needed }}
        {{ data.needed === 1 ? "stelling" : "stellingen" }}. Daarna kun je je
        persoonlijke kompasrapport kopen voor €1,99.
      </p>
      <p v-else>
        Je hebt genoeg stellingen beantwoord. Koop nu je persoonlijke
        kompasrapport voor €1,99 en betaal met iDEAL | Wero.
      </p>
      <div
        class="compass-banner__track"
        role="progressbar"
        :aria-valuenow="percent"
        aria-valuemin="0"
        aria-valuemax="100"
        :aria-label="`Kompas ${percent}% compleet`"
      >
        <span :style="{ width: `${percent}%` }" />
      </div>
      <p class="compass-banner__meta">
        {{ data.done }} van {{ data.target }} antwoorden · {{ percent }}%
      </p>
    </div>

    <NuxtLink
      :to="complete ? '/kompas/rapport' : '/spel'"
      class="compass-banner__cta"
    >
      {{ complete ? "Koop mijn rapport · €1,99" : "Verder stemmen" }}
      <AppIcon name="arrow" :size="18" />
    </NuxtLink>
  </section>
</template>

<style scoped>
.compass-banner {
  display: grid;
  grid-template-columns: auto minmax(0, 1fr) auto;
  align-items: center;
  gap: clamp(1rem, 3vw, 2.25rem);
  padding: clamp(1.4rem, 3.5vw, 2.4rem);
  border-radius: 24px;
  background:
    radial-gradient(circle at 92% 10%, #ff4d6d55, transparent 45%),
    linear-gradient(120deg, #0057b8, #001f4d 70%);
  color: #fff;
  box-shadow: 0 24px 60px -30px #001f4d99;
}
.compass-banner--complete {
  background:
    radial-gradient(circle at 92% 10%, #ffd16655, transparent 45%),
    linear-gradient(120deg, #1d7a46, #0b4a2c 75%);
}
.compass-banner__count {
  display: grid;
  place-items: center;
  width: clamp(92px, 14vw, 128px);
  aspect-ratio: 1;
  border-radius: 50%;
  background: #ffffff1f;
  border: 2px solid #ffffff40;
  text-align: center;
  line-height: 1;
}
.compass-banner__count strong {
  display: block;
  font-size: clamp(2.4rem, 6vw, 3.4rem);
  font-weight: 800;
  letter-spacing: -0.05em;
  font-variant-numeric: tabular-nums;
}
.compass-banner__count span {
  margin-top: -0.6rem;
  font-size: 0.8rem;
  font-weight: 700;
  opacity: 0.85;
}
.compass-banner h2 {
  margin: 0 0 0.45rem;
  color: #fff;
  font-size: clamp(1.4rem, 3.2vw, 2.1rem);
  line-height: 1.12;
  letter-spacing: -0.04em;
}
.compass-banner__copy > p {
  margin: 0;
  max-width: 52ch;
  color: #dbe8fb;
  font-size: 1rem;
  line-height: 1.55;
}
.compass-banner__track {
  height: 10px;
  margin-top: 1.1rem;
  overflow: hidden;
  border-radius: 99px;
  background: #ffffff2b;
}
.compass-banner__track span {
  display: block;
  height: 100%;
  border-radius: inherit;
  background: linear-gradient(90deg, #ffd166, #ff4d6d);
  transition: width 0.6s ease;
}
.compass-banner--complete .compass-banner__track span {
  background: #ffd166;
}
.compass-banner .compass-banner__meta {
  margin-top: 0.5rem;
  font-size: 0.8rem;
  font-weight: 700;
  font-variant-numeric: tabular-nums;
}
.compass-banner__cta {
  display: inline-flex;
  align-items: center;
  gap: 0.55rem;
  min-height: 52px;
  padding: 0.85rem 1.4rem;
  border-radius: 14px;
  background: #fff;
  color: #001f4d;
  font-weight: 800;
  white-space: nowrap;
  box-shadow: 0 6px 0 #00000026;
}
.compass-banner__cta:hover {
  background: #fff3d6;
  color: #001f4d;
  text-decoration: none;
}
@media (max-width: 760px) {
  .compass-banner {
    grid-template-columns: auto minmax(0, 1fr);
  }
  .compass-banner__cta {
    grid-column: 1 / -1;
    justify-content: center;
  }
}
@media (prefers-reduced-motion: reduce) {
  .compass-banner__track span {
    transition: none;
  }
}
</style>
