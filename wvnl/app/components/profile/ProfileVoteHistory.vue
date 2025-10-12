<template>
  <section class="vote-history">
    <header class="vote-history__header">
      <div>
        <h2>Stemgeschiedenis</h2>
        <p>Bekijk op welke issues je hebt gestemd.</p>
      </div>
      <div class="vote-history__search">
        <label class="sr-only" for="vote-history-search"
          >Zoek in stemgeschiedenis</label
        >
        <input
          id="vote-history-search"
          v-model="searchQuery"
          type="search"
          placeholder="Zoek op titel of beschrijving"
        />
        <svg
          aria-hidden="true"
          xmlns="http://www.w3.org/2000/svg"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="m21 21-4.35-4.35m0 0A7.5 7.5 0 1 0 6.65 6.65a7.5 7.5 0 0 0 9.9 9.9Z"
          />
        </svg>
      </div>
    </header>

    <p v-if="loading" class="vote-history__status">Bezig met laden...</p>
    <p
      v-else-if="error"
      class="vote-history__status vote-history__status--error"
    >
      {{ error }}
    </p>
    <p v-else-if="filteredItems.length === 0" class="vote-history__status">
      <template v-if="searchQuery.trim().length">
        Geen stemmen gevonden voor "{{ searchQuery }}".
      </template>
      <template v-else>Je hebt nog niet gestemd op issues.</template>
    </p>

    <template v-else>
      <ul class="vote-history__list">
        <li v-for="item in paginatedItems" :key="item.id" class="vote-card">
          <div class="vote-card__details">
            <p class="vote-card__title">{{ item.title }}</p>
            <p v-if="item.description" class="vote-card__description">
              {{ item.description }}
            </p>
          </div>
          <div class="vote-card__actions">
            <span class="vote-chip" :class="voteClass(item.vote)">
              {{ voteLabel(item.vote) }}
            </span>
            <ShareDropdown
              trigger-class="share-button"
              @select="(platform) => share(item, platform)"
            />
          </div>
        </li>
      </ul>

      <div v-if="totalPages > 1" class="vote-pagination">
        <span>Pagina {{ currentPage }} van {{ totalPages }}</span>
        <div class="vote-pagination__controls">
          <button
            type="button"
            class="button button--outline"
            :disabled="currentPage === 1"
            @click="goToPreviousPage"
          >
            Vorige
          </button>
          <button
            type="button"
            class="button button--outline"
            :disabled="currentPage === totalPages"
            @click="goToNextPage"
          >
            Volgende
          </button>
        </div>
      </div>
    </template>
  </section>
</template>

<script setup lang="ts">
import { storeToRefs } from "pinia";
import ShareDropdown from "~/components/common/ShareDropdown.vue";
import { useIssueSharing } from "~/composables/useIssueSharing";
import type { SharePlatform } from "~/composables/useIssueSharing";
import type { IssueVoteOption, UserVoteHistoryItem } from "~/types/issues";
import { useAuthStore } from "~/stores/auth";

const api = useApi();
const auth = useAuthStore();
const { isLoggedIn } = storeToRefs(auth);
const { shareIssue } = useIssueSharing();

const loading = ref(false);
const error = ref<string | null>(null);
const items = ref<UserVoteHistoryItem[]>([]);
const searchQuery = ref("");
const currentPage = ref(1);
const pageSize = 5;

const filteredItems = computed(() => {
  const query = searchQuery.value.trim().toLowerCase();

  if (!query) {
    return items.value;
  }

  return items.value.filter((item) => {
    const haystack = `${item.title} ${item.description ?? ""}`.toLowerCase();
    return haystack.includes(query);
  });
});

const totalPages = computed(() => {
  const length = filteredItems.value.length;
  if (length === 0) {
    return 1;
  }
  return Math.ceil(length / pageSize);
});

const paginatedItems = computed(() => {
  if (filteredItems.value.length === 0) {
    return [];
  }

  const start = (currentPage.value - 1) * pageSize;
  return filteredItems.value.slice(start, start + pageSize);
});

watch(
  () => isLoggedIn.value,
  (loggedIn) => {
    if (loggedIn) {
      loadHistory();
    } else {
      items.value = [];
      currentPage.value = 1;
      searchQuery.value = "";
    }
  },
  { immediate: true }
);

watch(items, () => {
  currentPage.value = 1;
});

watch(searchQuery, () => {
  currentPage.value = 1;
});

watch(totalPages, (value) => {
  if (currentPage.value > value) {
    currentPage.value = value;
  }
});

async function loadHistory() {
  if (!isLoggedIn.value || loading.value) return;

  loading.value = true;
  error.value = null;

  try {
    items.value = await api.get<UserVoteHistoryItem[]>("/me/votes");
  } catch (err: unknown) {
    error.value =
      err instanceof Error ? err.message : "Kon stemgeschiedenis niet laden.";
  } finally {
    loading.value = false;
  }
}

function voteLabel(vote: IssueVoteOption) {
  switch (vote) {
    case "agree":
      return "Voor";
    case "disagree":
      return "Tegen";
    default:
      return "Neutraal";
  }
}

function voteClass(vote: IssueVoteOption) {
  switch (vote) {
    case "agree":
      return "vote-chip--agree";
    case "disagree":
      return "vote-chip--disagree";
    default:
      return "vote-chip--neutral";
  }
}

function goToPreviousPage() {
  if (currentPage.value > 1) {
    currentPage.value -= 1;
  }
}

function goToNextPage() {
  if (currentPage.value < totalPages.value) {
    currentPage.value += 1;
  }
}

async function share(item: UserVoteHistoryItem, platform: SharePlatform) {
  await shareIssue(
    { id: item.id, title: item.title },
    { vote: item.vote, path: "/issues", platform }
  );
}

defineExpose({ refresh: loadHistory });
</script>

<style scoped>
.vote-history {
  display: grid;
  gap: 1.75rem;
}

.vote-history__header {
  display: flex;
  flex-wrap: wrap;
  justify-content: space-between;
  align-items: center;
  gap: 1.5rem;
}

.vote-history__header h2 {
  margin: 0;
  font-size: 1.85rem;
  color: var(--color-primary);
}

.vote-history__header p {
  margin: 0.25rem 0 0;
  color: var(--color-muted);
  font-weight: 500;
}

.vote-history__search {
  position: relative;
  width: min(280px, 100%);
}

.vote-history__search input {
  width: 100%;
  padding: 0.65rem 1rem 0.65rem 2.75rem;
  border-radius: 999px;
  border: 1px solid rgba(0, 61, 165, 0.25);
  background: #ffffff;
  font-size: 0.95rem;
  color: var(--color-text);
  box-shadow: inset 0 1px 4px rgba(0, 29, 72, 0.05);
}

.vote-history__search input:focus {
  outline: 3px solid rgba(0, 61, 165, 0.28);
  border-color: var(--color-primary);
}

.vote-history__search svg {
  position: absolute;
  top: 50%;
  left: 1rem;
  transform: translateY(-50%);
  width: 1.1rem;
  height: 1.1rem;
  color: rgba(16, 24, 40, 0.55);
}

.vote-history__status {
  margin: 0;
  color: var(--color-muted);
  font-weight: 500;
}

.vote-history__status--error {
  color: var(--color-accent);
}

.vote-history__list {
  list-style: none;
  margin: 0;
  padding: 0;
  display: grid;
  gap: 1rem;
}

.vote-card {
  display: flex;
  flex-wrap: wrap;
  justify-content: space-between;
  gap: 1rem;
  padding: 1.25rem 1.5rem;
  border-radius: calc(var(--border-radius) - 2px);
  background: linear-gradient(
    135deg,
    rgba(0, 61, 165, 0.04),
    rgba(200, 16, 46, 0.04)
  );
  border: 1px solid rgba(0, 61, 165, 0.12);
  box-shadow: 0 10px 25px rgba(0, 27, 70, 0.08);
}

.vote-card__details {
  flex: 1 1 220px;
}

.vote-card__title {
  margin: 0;
  font-weight: 700;
  color: var(--color-text);
  font-size: 1.05rem;
}

.vote-card__description {
  margin: 0.4rem 0 0;
  color: var(--color-muted);
  line-height: 1.5;
}

.vote-card__actions {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.75rem;
}

.vote-chip {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0.35rem 1rem;
  border-radius: 999px;
  font-weight: 700;
  font-size: 0.85rem;
}

.vote-chip--agree {
  background: rgba(6, 152, 98, 0.15);
  color: #04724d;
}

.vote-chip--disagree {
  background: rgba(200, 16, 46, 0.12);
  color: var(--color-accent);
}

.vote-chip--neutral {
  background: rgba(255, 155, 0, 0.2);
  color: #8a4b00;
}

.share-button {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  padding: 0.45rem 1.1rem;
  border-radius: 999px;
  border: none;
  background: linear-gradient(135deg, #003da5, #0a4bc9);
  color: #ffffff;
  font-weight: 600;
  font-size: 0.9rem;
  transition: transform 0.2s ease, box-shadow 0.2s ease, filter 0.2s ease;
  box-shadow: 0 12px 26px rgba(0, 61, 165, 0.22);
}

.share-button:hover,
.share-button:focus-visible {
  transform: translateY(-1px);
  box-shadow: 0 16px 30px rgba(0, 61, 165, 0.28);
  outline: none;
  filter: brightness(1.05);
}

.vote-pagination {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem 1.5rem;
  padding-top: 1.25rem;
  border-top: 1px solid rgba(0, 61, 165, 0.12);
  font-weight: 600;
  color: var(--color-muted);
}

.vote-pagination__controls {
  display: flex;
  gap: 0.75rem;
}

.button--outline {
  background: #ffffff;
  border: 2px solid rgba(0, 61, 165, 0.35);
  color: var(--color-primary);
  padding-inline: 1.4rem;
}

.button--outline:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  box-shadow: none;
}

@media (max-width: 640px) {
  .vote-history__header {
    flex-direction: column;
    align-items: flex-start;
  }

  .vote-card {
    padding: 1rem 1.25rem;
  }
}
</style>
