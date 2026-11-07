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
import { applyVoteResult } from "~/utils/voting";
import { translateErrorMessage } from "~/utils/translateErrorMessage";

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

  const INITIAL_BATCH_SIZE = 10;
  const BACKGROUND_LIMIT = 200;

  async function fetchBatch(limit?: number, offset?: number) {
    if (isLoggedIn.value) {
      return fetchPendingIssues(token.value ?? undefined, { limit, offset });
    }

    return fetchIssuesWithArguments({ limit, offset });
  }

  async function loadPending() {
    const tokenId = ++requestToken;
    loading.value = true;
    error.value = null;
    issues.value = [];
    activeIndex.value = 0;

    try {
      const initialIssues = await fetchBatch(INITIAL_BATCH_SIZE, 0);
      if (tokenId !== requestToken) {
        return;
      }

      issues.value = shuffleIssues(initialIssues);
      activeIndex.value = 0;
    } catch (err: unknown) {
      if (tokenId !== requestToken) {
        return;
      }
      issues.value = [];
      activeIndex.value = 0;
      error.value = translateErrorMessage(err, {
        fallback: "Er is iets misgegaan.",
      });
      return;
    } finally {
      if (tokenId === requestToken) {
        loading.value = false;
      }
    }

    if (tokenId !== requestToken) {
      return;
    }

    let offset = issues.value.length;

    while (tokenId === requestToken) {
      try {
        const chunk = await fetchBatch(BACKGROUND_LIMIT, offset);
        if (tokenId !== requestToken) {
          return;
        }

        if (!chunk.length) {
          break;
        }

        const existingIds = new Set(issues.value.map((issue) => issue.id));
        const nextIssues = shuffleIssues(chunk).filter(
          (issue) => !existingIds.has(issue.id)
        );

        if (nextIssues.length) {
          issues.value = [...issues.value, ...nextIssues];
        }

        offset += chunk.length;

        if (chunk.length < BACKGROUND_LIMIT) {
          break;
        }
      } catch (err: unknown) {
        if (tokenId !== requestToken) {
          return;
        }

        error.value = issues.value.length
          ? "Sommige moties konden niet worden geladen. Vernieuw om het opnieuw te proberen."
          : translateErrorMessage(err, {
              fallback: "Er is iets misgegaan.",
            });
        break;
      }
    }
  }

  async function vote(vote: IssueVoteOption) {
    const issue = activeIssue.value;
    if (!issue || issue.user_vote || actionPending.value) return false;

    actionPending.value = true;
    error.value = null;
    try {
      const response = await api.post<IssueVoteResponse>(
        `/issues/${issue.id}/vote`,
        { vote }
      );
      if (auth.user) {
        auth.updateUser({
          ...auth.user,
          voted_issue_ids: response.voted_issue_ids,
        });
      }
      issues.value[activeIndex.value] = applyVoteResult(issue, response);
      return true;
  } catch (err: unknown) {
    error.value = translateErrorMessage(err, {
      fallback: "Stemmen is niet gelukt.",
    });
    } finally {
      actionPending.value = false;
    }
  }

  function skip() {
    if (actionPending.value) return;
    if (activeIssue.value?.user_vote) {
      issues.value.splice(activeIndex.value, 1);
      if (activeIndex.value >= issues.value.length) activeIndex.value = 0;
      if (!issues.value.length) void loadPending();
      return;
    }
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

function shuffleIssues<T>(items: readonly T[]): T[] {
  const shuffled = items.slice();
  for (let index = shuffled.length - 1; index > 0; index -= 1) {
    const swapIndex = Math.floor(Math.random() * (index + 1));
    const current = shuffled[index] as T;
    shuffled[index] = shuffled[swapIndex] as T;
    shuffled[swapIndex] = current;
  }
  return shuffled;
}
