<template>
  <section class="space-y-4">
    <header>
      <h2 class="text-2xl font-semibold">Stemgeschiedenis</h2>
      <p class="text-gray-600">Bekijk op welke issues je hebt gestemd.</p>
    </header>

    <p v-if="loading" class="text-gray-500">Bezig met laden...</p>
    <p v-else-if="error" class="text-red-600">{{ error }}</p>
    <p v-else-if="items.length === 0" class="text-gray-500">
      Je hebt nog niet gestemd op issues.
    </p>

    <ul v-else class="space-y-3">
      <li
        v-for="item in items"
        :key="item.id"
        class="flex flex-col gap-2 rounded border border-gray-200 p-4 shadow-sm md:flex-row md:items-center md:justify-between"
      >
        <div>
          <p class="font-medium">{{ item.title }}</p>
          <p v-if="item.description" class="mt-1 text-sm text-gray-600">
            {{ item.description }}
          </p>
        </div>
        <span
          class="inline-flex w-fit items-center rounded-full px-3 py-1 text-sm font-semibold"
          :class="voteClass(item.vote)"
        >
          {{ voteLabel(item.vote) }}
        </span>
      </li>
    </ul>
  </section>
</template>

<script setup lang="ts">
import { storeToRefs } from "pinia";
import type { IssueVoteOption, UserVoteHistoryItem } from "~/types/issues";
import { useAuthStore } from "~/stores/auth";

const api = useApi();
const auth = useAuthStore();
const { isLoggedIn } = storeToRefs(auth);

const loading = ref(false);
const error = ref<string | null>(null);
const items = ref<UserVoteHistoryItem[]>([]);

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

watch(
  isLoggedIn,
  (loggedIn) => {
    if (loggedIn) {
      loadHistory();
    } else {
      items.value = [];
    }
  },
  { immediate: true }
);

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

defineExpose({ refresh: loadHistory });
</script>
