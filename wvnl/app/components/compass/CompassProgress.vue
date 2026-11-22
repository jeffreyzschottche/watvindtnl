<script setup lang="ts">
import AppIcon from "~/components/ui/AppIcon.vue";
const props = withDefaults(defineProps<{ variant?: "compact" | "card" }>(), {
  variant: "compact",
});
const auth = useAuthStore();
const { data, complete, percent, themesRemaining, votesRemaining, refresh } =
  useCompassProgress();
const message = computed(() => {
  const d = data.value;
  if (!d) return "";
  if (complete.value)
    return "Je kompas is compleet. Elke stem maakt het scherper.";
  if (themesRemaining.value)
    return `Nog ${themesRemaining.value} ${themesRemaining.value === 1 ? "thema" : "thema’s"} aanvullen`;
  if (votesRemaining.value)
    return `Nog minstens ${votesRemaining.value} ${votesRemaining.value === 1 ? "antwoord" : "antwoorden"} nodig`;
  return "Je themascores zijn gevuld. Stem verder voor je politieke type.";
});
onMounted(refresh);
// Refresh after every saved vote; the count lives on the user record.
watch(
  () => auth.user?.voted_issue_ids?.length,
  (now, before) => {
    if (before !== undefined && now !== before) void refresh();
  },
);
</script>

<template>
  <NuxtLink
    v-if="data && props.variant === 'compact'"
    to="/kompas"
    class="compass-progress compass-progress--compact"
    :aria-label="`Dekking van de kompasthema’s ${percent}%. ${message}`"
  >
    <span class="compass-progress__icon" aria-hidden="true"
      ><AppIcon name="compass" :size="18"
    /></span>
    <span class="compass-progress__body">
      <span class="compass-progress__row"
        ><strong>Jouw kompas</strong><span>{{ percent }}%</span></span
      >
      <span class="compass-progress__track" aria-hidden="true"
        ><span :style="{ width: `${percent}%` }"
      /></span>
      <small>{{ message }}</small>
    </span>
  </NuxtLink>

  <section
    v-else-if="data && props.variant === 'card'"
    class="compass-progress compass-progress--card"
    aria-labelledby="compass-progress-title"
  >
    <p class="compass-progress__eyebrow">Jouw voortgang</p>
    <h2 id="compass-progress-title">
      {{
        complete
          ? "Je kompas is compleet"
          : data.type
            ? `Voorlopig: ${data.type.name}`
            : "Je kompas krijgt vorm"
      }}
    </h2>
    <div
      class="compass-progress__track compass-progress__track--large"
      role="progressbar"
      :aria-valuenow="percent"
      aria-valuemin="0"
      aria-valuemax="100"
      aria-label="Dekking van de kompasthema’s"
    >
      <span :style="{ width: `${percent}%` }" />
    </div>
    <p class="compass-progress__message">
      {{ message
      }}<template v-if="!complete && data.next_theme">
        · vooral <strong>{{ data.next_theme }}</strong> mist nog</template
      >
    </p>
    <ul class="compass-progress__themes">
      <li v-for="theme in data.themes" :key="theme.id">
        <span>{{ theme.left }} ↔ {{ theme.right }}</span>
        <span class="compass-progress__dots" aria-hidden="true"
          ><i
            v-for="n in theme.minimum"
            :key="n"
            :class="{ on: n <= theme.done }"
        /></span>
        <span class="sr-only"
          >{{ theme.done }} van {{ theme.minimum }} antwoorden</span
        >
      </li>
    </ul>
    <NuxtLink
      :to="complete ? '/profiel/mijn-kompas' : '/spel'"
      class="button button-red compass-progress__cta"
      >{{ complete ? "Bekijk je kompas" : "Verder stemmen" }}
      <AppIcon name="arrow" :size="16"
    /></NuxtLink>
  </section>
</template>

<style scoped>
.compass-progress {
  color: #001f4d;
  text-decoration: none;
}
.compass-progress--compact {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  min-height: 56px;
  padding: 0.6rem 0.85rem;
  background: #fff;
  border: 1px solid #dce5f3;
  border-radius: 14px;
  box-shadow: 0 3px 0 #e3ebf7;
}
.compass-progress__icon {
  display: grid;
  place-items: center;
  width: 34px;
  height: 34px;
  flex: none;
  border-radius: 10px;
  background: #edf3ff;
  color: #0057b8;
}
.compass-progress__body {
  flex: 1;
  display: grid;
  gap: 0.3rem;
  min-width: 0;
}
.compass-progress__row {
  display: flex;
  justify-content: space-between;
  font-size: 0.8rem;
}
.compass-progress__row span {
  color: #0057b8;
  font-weight: 800;
}
.compass-progress small {
  color: #586980;
  font-size: 0.7rem;
}
.compass-progress__track {
  display: block;
  height: 6px;
  border-radius: 6px;
  background: #e3eaf4;
  overflow: hidden;
}
.compass-progress__track > span {
  display: block;
  height: 100%;
  border-radius: inherit;
  background: linear-gradient(90deg, #0057b8, #2f80ed);
  transition: width 0.5s ease;
}
.compass-progress--card {
  display: grid;
  gap: 0.8rem;
  padding: 1.4rem;
  background: #fff;
  border: 1px solid #dce5f3;
  border-radius: 20px;
  box-shadow: 0 6px 0 #e3ebf7;
}
.compass-progress--card h2 {
  margin: 0;
  font-size: 1.3rem;
  letter-spacing: -0.03em;
}
.compass-progress__eyebrow {
  margin: 0;
  color: #0057b8;
  font-size: 0.68rem;
  font-weight: 750;
  letter-spacing: 0.12em;
  text-transform: uppercase;
}
.compass-progress__track--large {
  height: 10px;
}
.compass-progress__message {
  margin: 0;
  color: #526277;
  font-size: 0.85rem;
}
.compass-progress__themes {
  list-style: none;
  margin: 0;
  padding: 0;
  display: grid;
  gap: 0.45rem;
}
.compass-progress__themes li {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 0.75rem;
  font-size: 0.78rem;
  color: #3d4f68;
}
.compass-progress__dots {
  display: inline-flex;
  gap: 4px;
  flex: none;
}
.compass-progress__dots i {
  width: 9px;
  height: 9px;
  border-radius: 50%;
  background: #dfe6f0;
}
.compass-progress__dots i.on {
  background: #0057b8;
}
.compass-progress__cta {
  justify-content: center;
  min-height: 48px;
}
@media (prefers-reduced-motion: reduce) {
  .compass-progress__track > span {
    transition: none;
  }
}
</style>
