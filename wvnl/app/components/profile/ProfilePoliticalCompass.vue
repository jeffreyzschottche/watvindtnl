<template>
  <section class="compass">
    <header class="compass__header">
      <div>
        <h2>Politiek kompas</h2>
        <p>
          Analyseer je stemgedrag en ontdek welke politieke richting en partij
          bij je passen.
        </p>
      </div>
      <button
        v-if="overview && overview.can_generate"
        type="button"
        class="button"
        :disabled="generating"
        @click="generateCompass"
      >
        {{ generating ? "Bezig..." : "Genereer nieuw kompas" }}
      </button>
    </header>

    <p v-if="loading" class="compass__status">Bezig met laden...</p>
    <p v-else-if="error" class="compass__status compass__status--error">
      {{ error }}
    </p>
    <template v-else>
      <p v-if="successMessage" class="compass__status compass__status--success">
        {{ successMessage }}
      </p>

      <p v-if="overview && !overview.can_generate" class="compass__hint">
        <template v-if="overview.vote_count < overview.minimum_votes">
          Je hebt nog {{ votesNeeded }}
          {{ votesNeeded === 1 ? "stem" : "stemmen" }} nodig om een kompas te
          genereren. Stem op een issue om te starten.
        </template>
        <template v-else>
          Je kunt één keer per dag een nieuw kompas genereren.
          <template v-if="overview.next_available_at">
            Volgende mogelijkheid:
            {{ formatDateTime(overview.next_available_at) }}.
          </template>
        </template>
      </p>

      <div v-if="overview && overview.latest" class="compass-card">
        <div class="compass-card__header">
          <div>
            <h3>Jouw huidige profiel</h3>
            <p v-if="overview.latest.created_at" class="compass-card__date">
              Bijgewerkt op {{ formatDateTime(overview.latest.created_at) }}
            </p>
          </div>
          <span class="compass-score__value">
            {{ overview.latest.stemgedrag_score }} / 10
          </span>
        </div>

        <div class="compass-card__body">
          <div class="compass-score">
            <div class="compass-score__scale">
              <span>Links</span>
              <div class="compass-score__bar">
                <div
                  class="compass-score__indicator"
                  :style="{
                    left: `${scorePosition(overview.latest.stemgedrag_score)}%`,
                  }"
                  aria-hidden="true"
                ></div>
              </div>
              <span>Rechts</span>
            </div>
            <p class="compass-score__explain">
              1 = sterk links, 10 = sterk rechts, 5 = midden.
            </p>
          </div>

          <div class="compass-detail">
            <h4>Politieke stroming</h4>
            <p class="compass-detail__term">
              {{ overview.latest.politieke_label.term }}
            </p>
            <p v-if="overview.latest.politieke_label.hoofdkenmerk">
              {{ overview.latest.politieke_label.hoofdkenmerk }}
            </p>
            <p
              v-if="overview.latest.politieke_label.spectrum"
              class="compass-detail__spectrum"
            >
              Spectrum: {{ overview.latest.politieke_label.spectrum }}
            </p>
          </div>

          <div v-if="overview.latest.aanbevolen_partij" class="compass-detail">
            <h4>Aanbevolen partij</h4>
            <div class="compass-party">
              <div class="compass-party__meta">
                <p class="compass-party__name">
                  {{ overview.latest.aanbevolen_partij.name }}
                </p>
                <span class="compass-party__tag">
                  {{ overview.latest.aanbevolen_partij.abbreviation }}
                </span>
              </div>
              <p class="compass-party__motivation">
                {{ overview.latest.aanbevolen_partij.motivatie }}
              </p>
              <a
                v-if="overview.latest.aanbevolen_partij.website_url"
                :href="overview.latest.aanbevolen_partij.website_url"
                target="_blank"
                rel="noopener"
              >
                Bezoek partijwebsite
              </a>
            </div>
          </div>
        </div>
      </div>

      <div v-else class="compass-empty">
        <h3>Nog geen politieke analyse</h3>
        <p>
          Zodra je voldoende hebt gestemd kun je hier een politiek kompas
          genereren en jouw profiel bekijken.
        </p>
      </div>

      <div v-if="previousEntries.length" class="compass-history">
        <h4>Eerdere kompassen</h4>
        <ul>
          <li v-for="item in previousEntries" :key="item.id">
            <div>
              <p class="compass-history__date">
                {{
                  item.created_at ? formatDateTime(item.created_at) : "Onbekend"
                }}
              </p>
              <p class="compass-history__label">
                {{ item.politieke_label.term }} — score
                {{ item.stemgedrag_score }}
              </p>
            </div>
            <span v-if="item.aanbevolen_partij" class="compass-history__party">
              {{ item.aanbevolen_partij.abbreviation }}
            </span>
          </li>
        </ul>
      </div>
    </template>
  </section>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from "vue";
import { useApi } from "~/composables/useApi";
import type {
  PoliticalCompassEntry,
  PoliticalCompassOverview,
} from "~/types/politicalCompass";

const api = useApi();

const loading = ref(true);
const error = ref<string | null>(null);
const generating = ref(false);
const successMessage = ref<string | null>(null);
const overview = ref<PoliticalCompassOverview | null>(null);

const previousEntries = computed<PoliticalCompassEntry[]>(() => {
  if (!overview.value) {
    return [];
  }

  const latestId = overview.value.latest?.id ?? null;
  return overview.value.history.filter((entry) => entry.id !== latestId);
});

const votesNeeded = computed(() => {
  if (!overview.value) return 0;

  return Math.max(0, overview.value.minimum_votes - overview.value.vote_count);
});

function formatDateTime(value: string): string {
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) {
    return value;
  }

  return new Intl.DateTimeFormat("nl-NL", {
    day: "numeric",
    month: "long",
    year: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  }).format(date);
}

function scorePosition(score: number): number {
  const clamped = Math.min(10, Math.max(1, score));
  return ((clamped - 1) / 9) * 100;
}

async function loadOverview(options: { keepSuccess?: boolean } = {}) {
  loading.value = true;
  error.value = null;
  if (!options.keepSuccess) {
    successMessage.value = null;
  }

  try {
    overview.value = await api.get<PoliticalCompassOverview>(
      "/me/political-compass"
    );
  } catch (err: unknown) {
    error.value =
      err instanceof Error
        ? err.message
        : "Kon het politieke kompas niet laden.";
  } finally {
    loading.value = false;
  }
}

async function generateCompass() {
  if (generating.value) return;
  if (!overview.value) return;

  const hasMinimumVotes =
    overview.value.vote_count >= overview.value.minimum_votes;
  if (!overview.value.can_generate || !hasMinimumVotes) {
    return;
  }

  generating.value = true;
  error.value = null;
  successMessage.value = null;

  try {
    await api.post<PoliticalCompassEntry>("/me/political-compass");
    successMessage.value = "Nieuw politiek kompas gegenereerd.";
    await loadOverview({ keepSuccess: true });
  } catch (err: unknown) {
    error.value =
      err instanceof Error
        ? err.message
        : "Kon geen nieuw politiek kompas genereren.";
  } finally {
    generating.value = false;
  }
}

onMounted(() => {
  loadOverview();
});
</script>

<style scoped>
.compass {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.compass__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
}

.compass__header h2 {
  margin: 0 0 0.35rem;
}

.compass__header p {
  margin: 0;
  color: rgba(16, 24, 40, 0.7);
}

.compass__status {
  margin: 0;
  font-weight: 600;
}

.compass__status--error {
  color: var(--color-accent);
}

.compass__status--success {
  color: #087443;
}

.compass__hint {
  margin: 0;
  padding: 0.85rem 1rem;
  border-radius: 12px;
  background: rgba(0, 61, 165, 0.07);
  border: 1px solid rgba(0, 61, 165, 0.18);
  color: rgba(16, 24, 40, 0.85);
}

.compass-card {
  border-radius: 18px;
  border: 1px solid rgba(16, 24, 40, 0.08);
  background: linear-gradient(
    135deg,
    rgba(0, 61, 165, 0.06),
    rgba(200, 16, 46, 0.05)
  );
  padding: clamp(1.5rem, 3vw, 2.25rem);
  display: flex;
  flex-direction: column;
  gap: 1.75rem;
}

.compass-card__header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 1rem;
}

.compass-card__header h3 {
  margin: 0 0 0.35rem;
}

.compass-card__date {
  margin: 0;
  color: rgba(16, 24, 40, 0.65);
  font-weight: 500;
}

.compass-card__body {
  display: grid;
  gap: 1.5rem;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
}

.compass-score {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.compass-score__value {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0.4rem 0.9rem;
  border-radius: 999px;
  background: rgba(0, 61, 165, 0.15);
  color: rgba(0, 61, 165, 0.95);
  font-weight: 700;
  font-size: 0.95rem;
}

.compass-score__scale {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  font-weight: 600;
}

.compass-score__scale span:first-child,
.compass-score__scale span:last-child {
  color: rgba(16, 24, 40, 0.6);
  font-size: 0.9rem;
}

.compass-score__bar {
  position: relative;
  flex: 1;
  height: 6px;
  border-radius: 999px;
  background: rgba(16, 24, 40, 0.15);
}

.compass-score__indicator {
  position: absolute;
  top: 50%;
  transform: translate(-50%, -50%);
  width: 14px;
  height: 14px;
  border-radius: 50%;
  background: linear-gradient(
    135deg,
    rgba(0, 61, 165, 0.95),
    rgba(200, 16, 46, 0.95)
  );
  box-shadow: 0 0 10px rgba(0, 61, 165, 0.35);
}

.compass-score__explain {
  margin: 0;
  font-size: 0.85rem;
  color: rgba(16, 24, 40, 0.65);
}

.compass-detail {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.compass-detail h4 {
  margin: 0;
}

.compass-detail__term {
  margin: 0;
  font-weight: 700;
  font-size: 1.05rem;
}

.compass-detail__spectrum {
  margin: 0;
  font-style: italic;
  color: rgba(16, 24, 40, 0.65);
}

.compass-party {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.compass-party__meta {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.compass-party__name {
  margin: 0;
  font-weight: 700;
}

.compass-party__tag {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0.25rem 0.65rem;
  border-radius: 999px;
  background: rgba(200, 16, 46, 0.12);
  color: rgba(200, 16, 46, 0.9);
  font-weight: 700;
  font-size: 0.85rem;
}

.compass-party__motivation {
  margin: 0;
}

.compass-party a {
  align-self: flex-start;
  font-weight: 600;
  color: rgba(0, 61, 165, 0.95);
}

.compass-empty {
  padding: 1.5rem;
  border-radius: 16px;
  border: 1px dashed rgba(16, 24, 40, 0.2);
  background: rgba(0, 61, 165, 0.05);
}

.compass-empty h3 {
  margin: 0 0 0.5rem;
}

.compass-empty p {
  margin: 0;
}

.compass-history {
  margin-top: 0.5rem;
}

.compass-history h4 {
  margin: 0 0 0.75rem;
}

.compass-history ul {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 0.85rem;
}

.compass-history li {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
  padding: 0.75rem 1rem;
  border-radius: 12px;
  background: rgba(16, 24, 40, 0.04);
}

.compass-history__date {
  margin: 0 0 0.25rem;
  font-weight: 600;
  color: rgba(16, 24, 40, 0.65);
}

.compass-history__label {
  margin: 0;
  font-weight: 600;
}

.compass-history__party {
  font-weight: 700;
  color: rgba(0, 61, 165, 0.9);
}

@media (max-width: 768px) {
  .compass__header {
    flex-direction: column;
    align-items: stretch;
  }

  .compass__header button {
    align-self: flex-start;
  }

  .compass-card__body {
    grid-template-columns: 1fr;
  }
}
</style>
