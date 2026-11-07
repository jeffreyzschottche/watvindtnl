<script setup lang="ts">
import AppIcon from "~/components/ui/AppIcon.vue";
import { fetchIssuesWithArguments } from "~/services/issues";
import type { IssueWithArguments } from "~/types/issues";
const auth = useAuthStore();
const search = ref("");
const filter = ref<"all" | "yes" | "no">("all");
const items = ref<IssueWithArguments[]>([]);
const loading = ref(true);
const error = ref("");
const more = ref(false);
let generation = 0;
let timer: ReturnType<typeof setTimeout> | undefined;
async function load(append = false) {
  const request = ++generation;
  loading.value = true;
  error.value = "";
  const offset = append ? items.value.length : 0;
  try {
    const batch = await fetchIssuesWithArguments(
      {
        limit: 24,
        offset,
        search: search.value.trim(),
        answered: auth.isLoggedIn ? filter.value : "all",
      },
      {
        headers: auth.token
          ? { Authorization: `Bearer ${auth.token}` }
          : undefined,
      },
    );
    if (request !== generation) return;
    items.value = append ? [...items.value, ...batch] : batch;
    more.value = batch.length === 24;
  } catch {
    if (request === generation)
      error.value =
        "De stellingen konden niet worden geladen. Probeer het opnieuw.";
  } finally {
    if (request === generation) loading.value = false;
  }
}
watch([search, filter], () => {
  clearTimeout(timer);
  timer = setTimeout(() => {
    void load();
  }, 300);
});
watch(
  () => auth.token,
  () => {
    filter.value = "all";
    void load();
  },
);
onMounted(() => {
  void load();
});
onBeforeUnmount(() => {
  clearTimeout(timer);
  generation++;
});
useSeoMeta({
  title: "Alle stellingen | WatDenktNederland",
  description:
    "Ontdek politieke stellingen, lees beide kanten en geef je mening.",
});
</script>
<template>
  <section class="catalogue container">
    <header class="catalogue__heading">
      <p class="eyebrow">Ontdek beide kanten</p>
      <h1>Waar denk jij over na?</h1>
      <p>Zoek een onderwerp dat je raakt. Lees, denk en stem mee.</p>
    </header>
    <div class="catalogue__controls">
      <label class="catalogue__search"
        ><AppIcon name="search" :size="20" /><span class="sr-only"
          >Zoek een stelling</span
        ><input
          v-model="search"
          type="search"
          maxlength="120"
          placeholder="Zoek een stelling…"
      /></label>
      <div
        v-if="auth.isLoggedIn"
        class="catalogue__filters"
        aria-label="Filter op eigen stemmen"
      >
        <button
          v-for="option in [
            { value: 'all', label: 'Alle stellingen' },
            { value: 'no', label: 'Nog niet gestemd' },
            { value: 'yes', label: 'Gestemd' },
          ] as const"
          :key="option.value"
          type="button"
          :aria-pressed="filter === option.value"
          @click="filter = option.value"
        >
          {{ option.label }}
        </button>
      </div>
      <NuxtLink v-else to="/spel" class="catalogue__play"
        >Liever één voor één? <span>Start het spel →</span></NuxtLink
      >
    </div>
    <p v-if="error" role="alert" class="form-error">
      {{ error }}
      <button type="button" @click="load()">Opnieuw proberen</button>
    </p>
    <p v-if="loading && !items.length" class="catalogue__state" role="status">
      Even geduld, we laden de stellingen…
    </p>
    <div v-else-if="!items.length && !error" class="catalogue__state">
      <AppIcon name="search" :size="30" />
      <h2>Geen stellingen gevonden</h2>
      <p>Probeer een ander zoekwoord of kies een ander filter.</p>
      <button
        class="button button-secondary"
        type="button"
        @click="
          search = '';
          filter = 'all';
        "
      >
        Toon alle stellingen
      </button>
    </div>
    <div v-else class="catalogue__list" :aria-busy="loading">
      <NuxtLink
        v-for="item in items"
        :key="item.id"
        :to="`/stellingen/${item.slug}`"
        class="statement-row"
        ><div>
          <span class="statement-row__topic">Politiek</span>
          <h2>{{ item.title }}</h2>
          <p>{{ item.description }}</p>
        </div>
        <span v-if="item.user_vote" class="statement-row__voted"
          ><AppIcon name="check" :size="13" /> Gestemd</span
        ><AppIcon name="chevron" :size="18"
      /></NuxtLink>
    </div>
    <button
      v-if="more && items.length"
      class="button button-secondary catalogue__more"
      :disabled="loading"
      @click="load(true)"
    >
      {{ loading ? "Laden…" : "Meer stellingen" }}
    </button>
  </section>
</template>
<style scoped>
.catalogue {
  max-width: 900px;
  padding-block: 48px 65px;
}
.catalogue__heading h1 {
  margin: 0.5rem 0 0.85rem;
  font-size: 2.5rem;
}
.catalogue__heading > p:last-child {
  color: #63738a;
  font-size: 0.95rem;
}
.catalogue__controls {
  margin: 2rem 0 1.5rem;
}
.catalogue__search {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  background: #fff;
  border: 1px solid #d1dbe8;
  border-radius: 10px;
  padding: 0.5rem 1rem;
  color: #6d7f99;
}
.catalogue__search:focus-within {
  border-color: #0057b8;
  box-shadow: var(--focus-ring);
}
.catalogue__search input {
  background: transparent;
  width: 100%;
  border: 0;
  outline: none;
  padding: 0.35rem 0;
  font-size: 0.9rem;
  color: #001f4d;
}
.catalogue__filters {
  display: flex;
  gap: 0.5rem;
  margin-top: 1rem;
  flex-wrap: wrap;
}
.catalogue__filters button {
  padding: 0.5rem 0.9rem;
  border: 1px solid #dce3ee;
  border-radius: 8px;
  color: #63738a;
  background: #fff;
  font-size: 0.75rem;
  min-height: 40px;
}
.catalogue__filters button[aria-pressed="true"] {
  background: #001f4d;
  color: #fff;
  border-color: #001f4d;
}
.catalogue__play {
  display: block;
  text-align: right;
  font-size: 0.75rem;
  color: #63738a;
  margin-top: 0.9rem;
  font-weight: 400;
}
.catalogue__play span {
  font-weight: 650;
  color: #0057b8;
}
.catalogue__list {
  display: grid;
  gap: 0.7rem;
}
.statement-row {
  display: flex;
  align-items: center;
  gap: 1rem;
  border: 1px solid #dfe6ef;
  border-radius: 12px;
  background: #fff;
  padding: 1.2rem 1.35rem;
  color: #001f4d;
  text-decoration: none;
}
.statement-row:hover {
  border-color: #97b6df;
  text-decoration: none;
}
.statement-row > div {
  flex: 1;
  min-width: 0;
}
.statement-row__topic {
  font-size: 0.6rem;
  color: #0057b8;
  text-transform: uppercase;
  letter-spacing: 0.09em;
}
.statement-row h2 {
  font-size: 0.98rem;
  line-height: 1.45;
  margin: 0.3rem 0 0.4rem;
  letter-spacing: -0.02em;
}
.statement-row p {
  font-size: 0.78rem;
  color: #63738a;
  font-weight: 400;
  margin: 0;
  display: -webkit-box;
  -webkit-line-clamp: 1;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
.statement-row > svg {
  flex-shrink: 0;
}
.statement-row__voted {
  display: flex;
  gap: 0.3rem;
  align-items: center;
  font-size: 0.61rem;
  white-space: nowrap;
  padding: 0.3rem 0.6rem;
  border-radius: 30px;
  background: #eaf5ee;
  color: #24743b;
}
.catalogue__more {
  display: flex;
  margin: 1.5rem auto 0;
}
.catalogue__state {
  text-align: center;
  padding: 3rem 1rem;
  color: #63738a;
}
.catalogue__state > svg {
  margin-bottom: 1rem;
}
.catalogue__state h2 {
  font-size: 1.4rem;
  color: #001f4d;
}
@media (max-width: 720px) {
  .catalogue {
    padding-block: 32px 45px;
  }
  .catalogue__heading h1 {
    font-size: 2rem;
  }
  .statement-row {
    padding: 1rem;
    gap: 0.65rem;
  }
  .statement-row__voted {
    font-size: 0.55rem;
    padding: 0.25rem 0.4rem;
  }
  .statement-row h2 {
    font-size: 0.9rem;
  }
}
</style>
