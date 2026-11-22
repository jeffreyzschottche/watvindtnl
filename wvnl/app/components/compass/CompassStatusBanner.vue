<script setup lang="ts">
import AppIcon from "~/components/ui/AppIcon.vue";

const props = withDefaults(defineProps<{ hideWhenComplete?: boolean }>(), {
  hideWhenComplete: false,
});
const { data, complete, percent, themesRemaining, votesRemaining, refresh } =
  useCompassProgress();
onMounted(refresh);
</script>

<template>
  <section
    v-if="data && !(complete && props.hideWhenComplete)"
    class="compass-banner"
    :class="{ 'compass-banner--complete': complete }"
    aria-labelledby="compass-banner-title"
  >
    <div v-if="themesRemaining" class="compass-banner__count" aria-hidden="true">
      <strong>{{ themesRemaining }}</strong>
      <span>{{ themesRemaining === 1 ? "thema" : "thema’s" }}</span>
    </div>
    <div v-else class="compass-banner__count compass-banner__count--done" aria-hidden="true">
      <AppIcon :name="complete ? 'check' : 'compass'" :size="44" />
    </div>

    <div class="compass-banner__copy">
      <h2 id="compass-banner-title">
        <template v-if="complete">Je politieke kompas is klaar</template>
        <template v-else-if="themesRemaining">
          Nog {{ themesRemaining }} {{ themesRemaining === 1 ? "thema" : "thema’s" }} aanvullen
        </template>
        <template v-else>Je politieke kompas is in opbouw</template>
      </h2>
      <p v-if="themesRemaining">
        Voor elk thema zijn {{ data.themes[0]?.minimum }} voor- of tegenstemmen
        op kompasstellingen nodig. Eén stem kan voor meerdere thema’s meetellen.
      </p>
      <p v-else-if="votesRemaining">
        Beantwoord nog minstens {{ votesRemaining }}
        {{ data.remaining_weighted_votes ? "kompasstellingen" : "stellingen" }}
        om je rapport vrij te spelen.
      </p>
      <p v-else-if="!complete">
        Je themascores zijn gevuld. Stem verder om een politiek type te bepalen.
      </p>
      <p v-else-if="data.checkout_available">
        Je hebt genoeg stellingen beantwoord. Koop nu je persoonlijke
        kompasrapport voor €1,99 en betaal met iDEAL | Wero.
      </p>
      <p v-else>Je rapport is tijdelijk nog niet te koop.</p>
      <div
        class="compass-banner__track"
        role="progressbar"
        :aria-valuenow="percent"
        aria-valuemin="0"
        aria-valuemax="100"
        aria-label="Dekking van de kompasthema’s"
      >
        <span :style="{ width: `${percent}%` }" />
      </div>
      <p class="compass-banner__meta">
        <template v-if="data.vote_count !== undefined">
          {{ data.vote_count }} opgeslagen stemmen ·
        </template>
        {{ data.used_votes }} meegeteld voor je kompas
      </p>
    </div>

    <NuxtLink
      :to="complete ? '/kompas/rapport' : '/spel'"
      class="compass-banner__cta"
    >
      {{ complete ? (data.checkout_available ? "Koop mijn rapport · €1,99" : "Bekijk mijn rapporten") : "Verder stemmen" }}
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
