<template>
  <section class="space-y-4">
    <header class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
      <div>
        <h2 class="text-2xl font-semibold">Stemgeschiedenis</h2>
        <p class="text-gray-600">Bekijk op welke issues je hebt gestemd.</p>
      </div>
      <div class="relative w-full md:w-64">
        <label class="sr-only" for="vote-history-search">Zoek in stemgeschiedenis</label>
        <input
          id="vote-history-search"
          v-model="searchQuery"
          type="search"
          class="w-full rounded border border-gray-300 px-3 py-2 pl-10 text-sm focus:border-black focus:outline-none"
          placeholder="Zoek op titel of beschrijving"
        />
        <svg
          class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"
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

    <p v-if="loading" class="text-gray-500">Bezig met laden...</p>
    <p v-else-if="error" class="text-red-600">{{ error }}</p>
    <p v-else-if="filteredItems.length === 0" class="text-gray-500">
      <template v-if="searchQuery.trim().length">
        Geen stemmen gevonden voor "{{ searchQuery }}".
      </template>
      <template v-else>Je hebt nog niet gestemd op issues.</template>
    </p>

    <template v-else>
      <ul class="space-y-3">
        <li
          v-for="item in paginatedItems"
          :key="item.id"
          class="flex flex-col gap-2 rounded border border-gray-200 p-4 shadow-sm md:flex-row md:items-center md:justify-between"
        >
          <div>
            <p class="font-medium">{{ item.title }}</p>
            <p v-if="item.description" class="mt-1 text-sm text-gray-600">
              {{ item.description }}
            </p>
          </div>
          <div class="flex flex-col items-start gap-2 md:flex-row md:items-center">
            <span
              class="inline-flex w-fit items-center rounded-full px-3 py-1 text-sm font-semibold"
              :class="voteClass(item.vote)"
            >
              {{ voteLabel(item.vote) }}
            </span>
            <ShareDropdown
              trigger-class="inline-flex items-center gap-2 rounded-full border border-gray-300 px-3 py-1 text-sm font-medium text-gray-700 transition hover:border-gray-400 hover:text-gray-900"
              @select="(platform) => share(item, platform)"
            />
          </div>
        </li>
      </ul>

      <div
        v-if="totalPages > 1"
        class="flex flex-col items-center gap-3 border-t border-gray-100 pt-4 text-sm text-gray-600 md:flex-row md:justify-between"
      >
        <span>Pagina {{ currentPage }} van {{ totalPages }}</span>
        <div class="flex items-center gap-2">
          <button
            type="button"
            class="inline-flex items-center rounded border border-gray-300 px-3 py-1 font-medium text-gray-700 transition hover:border-gray-400 hover:text-gray-900 disabled:cursor-not-allowed disabled:opacity-50"
            :disabled="currentPage === 1"
            @click="goToPreviousPage"
          >
            Vorige
          </button>
          <button
            type="button"
            class="inline-flex items-center rounded border border-gray-300 px-3 py-1 font-medium text-gray-700 transition hover:border-gray-400 hover:text-gray-900 disabled:cursor-not-allowed disabled:opacity-50"
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
      err instanceof Error
        ? err.message
        : "Kon stemgeschiedenis niet laden.";
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
      return "bg-green-100 text-green-800";
    case "disagree":
      return "bg-red-100 text-red-800";
    default:
      return "bg-yellow-100 text-yellow-800";
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
