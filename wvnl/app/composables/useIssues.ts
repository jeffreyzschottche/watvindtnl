import { computed, ref, watch } from "vue";
import { storeToRefs } from "pinia";
import { useApi } from "~/composables/useApi";
import { useAuthStore } from "~/stores/auth";
import { fetchIssuesWithArguments, fetchPendingIssues } from "~/services/issues";
import type {
  IssueVoteOption,
  IssueVoteResponse,
  IssueWithArguments,
} from "~/types/issues";

export function useIssues() {
  const api = useApi();
  const auth = useAuthStore();
  const { token, isLoggedIn } = storeToRefs(auth);

  const issues = ref<IssueWithArguments[]>([]);
  const loading = ref(false);
  const actionPending = ref(false);
  const error = ref<string | null>(null);

  const activeIndex = ref(0);
  let requestToken = 0;

  const activeIssue = computed(() => issues.value[activeIndex.value] ?? null);
  const remaining = computed(() => issues.value.length);

  async function loadPending() {
    const tokenId = ++requestToken;
    loading.value = true;
    error.value = null;
    try {
      const data = isLoggedIn.value
        ? await fetchPendingIssues(token.value ?? undefined)
        : await fetchIssuesWithArguments();
      if (tokenId !== requestToken) {
        return;
      }
      issues.value = data;
      activeIndex.value = 0;
    } catch (err: unknown) {
      if (tokenId !== requestToken) {
        return;
      }
      error.value = err instanceof Error ? err.message : "Er is iets misgegaan.";
    } finally {
      if (tokenId !== requestToken) {
        return;
      }
      loading.value = false;
    }
  }

  async function vote(vote: IssueVoteOption) {
    const issue = activeIssue.value;
    if (!issue) return;

    actionPending.value = true;
    error.value = null;
    try {
      const response = await api.post<IssueVoteResponse>(
        `/issues/${issue.id}/vote`,
        { vote }
      );
      if (auth.user.value) {
        auth.updateUser({
          ...auth.user.value,
          voted_issue_ids: response.voted_issue_ids,
        });
      }
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

  watch(
    isLoggedIn,
    () => {
      void loadPending();
    }
  );

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
