import { computed, ref } from "vue";
import { useApi } from "~/composables/useApi";
import { useAuthStore } from "~/stores/auth";
import { fetchPendingIssues } from "~/services/issues";
import type { IssueVoteOption, IssueWithArguments } from "~/types/issues";

export function useIssues() {
  const api = useApi();
  const auth = useAuthStore();

  const issues = ref<IssueWithArguments[]>([]);
  const loading = ref(false);
  const actionPending = ref(false);
  const error = ref<string | null>(null);

  const activeIndex = ref(0);

  const activeIssue = computed(() => issues.value[activeIndex.value] ?? null);
  const remaining = computed(() => issues.value.length);

  async function loadPending() {
    loading.value = true;
    error.value = null;
    try {
      const data = await fetchPendingIssues(auth.token ?? undefined);
      issues.value = data;
      activeIndex.value = 0;
    } catch (err: unknown) {
      error.value = err instanceof Error ? err.message : "Er is iets misgegaan.";
    } finally {
      loading.value = false;
    }
  }

  async function vote(vote: IssueVoteOption) {
    const issue = activeIssue.value;
    if (!issue) return;

    actionPending.value = true;
    error.value = null;
    try {
      await api.post(`/issues/${issue.id}/vote`, { vote });
      issues.value.splice(activeIndex.value, 1);
      if (activeIndex.value >= issues.value.length) {
        activeIndex.value = Math.max(issues.value.length - 1, 0);
      }
    } catch (err: unknown) {
      error.value = err instanceof Error ? err.message : "Stemmen is niet gelukt.";
    } finally {
      actionPending.value = false;
    }
  }

  function skip() {
    if (issues.value.length <= 1) {
      error.value = null;
      return;
    }
    error.value = null;
    activeIndex.value = (activeIndex.value + 1) % issues.value.length;
  }

  return {
    issues,
    activeIssue,
    remaining,
    loading,
    actionPending,
    error,
    loadPending,
    vote,
    skip,
  };
}
