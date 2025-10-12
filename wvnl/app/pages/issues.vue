<template>
  <section class="issues-page">
    <div class="issues-page__container">
      <header class="issues-page__header">
        <h1>Openstaande moties</h1>
        <p>
          Geef aan hoe jij over de volgende stellingen denkt. Kies voor, tegen,
          neutraal of sla over als je het nog niet weet.
        </p>
      </header>

      <div v-if="loading" class="issues-page__state">
        Even geduld, we laden de moties...
      </div>

      <div
        v-else-if="error && !activeIssue"
        class="issues-page__state issues-page__state--error"
      >
        <p>{{ error }}</p>
        <button type="button" class="issues-page__retry" @click="loadPending">
          Probeer opnieuw
        </button>
      </div>

      <IssueVoteCard
        v-else-if="activeIssue"
        :issue="activeIssue"
        :remaining="remaining"
        :disabled="actionPending"
        @vote="handleVote"
        @skip="handleSkip"
        @share="(platform) => handleShare(activeIssue, platform)"
      />

      <p v-if="error && activeIssue" class="issues-page__inline-error">
        {{ error }}
      </p>

      <div
        v-else-if="!activeIssue"
        class="issues-page__state issues-page__state--success"
      >
        <h2>Je bent helemaal bij!</h2>
        <p>Er staan momenteel geen onbeantwoorde kwesties voor je klaar.</p>
        <button type="button" class="issues-page__retry" @click="loadPending">
          Vernieuw
        </button>
      </div>
    </div>
    <IssueDetailModal
      v-if="isModalOpen"
      :issue="modalIssue"
      :loading="modalLoading"
      :error="modalError"
      :action-pending="modalActionPending"
      :can-retry="modalIssueId !== null"
      @close="closeModal"
      @retry="retryModal"
      @vote="handleModalVote"
      @share="(platform) => handleShare(modalIssue, platform)"
    />
  </section>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from "vue";
import { storeToRefs } from "pinia";
import IssueDetailModal from "~/components/issues/IssueDetailModal.vue";
import IssueVoteCard from "~/components/issues/IssueVoteCard.vue";
import { useIssues } from "~/composables/useIssues";
import { useAuthStore } from "~/stores/auth";
import { useNotificationStore } from "~/stores/notifications";
import { useIssueSharing } from "~/composables/useIssueSharing";
import type { SharePlatform } from "~/composables/useIssueSharing";
import { fetchIssueWithArguments, voteOnIssue } from "~/services/issues";
import type { IssueVoteOption, IssueWithArguments } from "~/types/issues";

const route = useRoute();
const router = useRouter();

const issuesState = useIssues();
const {
  issues,
  activeIssue,
  remaining,
  loading,
  actionPending,
  error,
  loadPending,
  vote,
  skip,
} = issuesState;

const auth = useAuthStore();
const { isLoggedIn, user, token } = storeToRefs(auth);

const notifications = useNotificationStore();
const { shareIssue } = useIssueSharing();

const modalIssue = ref<IssueWithArguments | null>(null);
const modalLoading = ref(false);
const modalError = ref<string | null>(null);
const modalActionPending = ref(false);
const modalIssueId = ref<number | null>(null);

const isModalOpen = computed(() => route.query.issue_id !== undefined);

watch(
  () => route.query.issue_id,
  (raw) => {
    if (raw === undefined) {
      modalIssueId.value = null;
      modalIssue.value = null;
      modalError.value = null;
      modalLoading.value = false;
      modalActionPending.value = false;
      return;
    }

    const value = Array.isArray(raw) ? raw[0] : raw;
    const parsed = Number(value);
    if (!value || Number.isNaN(parsed)) {
      modalIssueId.value = null;
      modalIssue.value = null;
      modalError.value = "Deze kwestie kon niet worden gevonden.";
      modalLoading.value = false;
      return;
    }

    modalIssueId.value = parsed;
    void loadIssueForModal(parsed);
  },
  { immediate: true }
);

onMounted(() => {
  loadPending();
});

function notifyLoginRequired() {
  notifications.addNotification({
    message: "Registreer of log in om je mening te geven.",
    type: "info",
  });
}

function handleVote(choice: IssueVoteOption) {
  if (!isLoggedIn.value) {
    notifyLoginRequired();
    return;
  }
  void vote(choice);
}

function handleSkip() {
  skip();
}

async function handleModalVote(choice: IssueVoteOption) {
  if (!isLoggedIn.value) {
    notifyLoginRequired();
    return;
  }

  const issue = modalIssue.value;
  if (!issue) return;

  modalActionPending.value = true;
  modalError.value = null;

  try {
    const inPending = issues.value.some((item) => item.id === issue.id);
    if (inPending) {
      await vote(choice);
      closeModal();
      return;
    }

    const response = await voteOnIssue(
      issue.id,
      choice,
      token.value ?? undefined
    );
    if (user.value) {
      auth.updateUser({
        ...user.value,
        voted_issue_ids: response.voted_issue_ids,
      });
    }
    const updated = applyUserVote(issue, user.value?.id ?? null, choice);
    modalIssue.value = updated;
  } catch (err: unknown) {
    modalError.value =
      err instanceof Error ? err.message : "Stemmen is niet gelukt.";
  } finally {
    modalActionPending.value = false;
  }
}

async function loadIssueForModal(id: number) {
  if (modalIssue.value?.id === id && !modalError.value) {
    return;
  }

  modalLoading.value = true;
  modalError.value = null;

  const existing = issues.value.find((issue) => issue.id === id);
  if (existing) {
    modalIssue.value = existing;
    modalLoading.value = false;
    return;
  }

  try {
    const issue = await fetchIssueWithArguments(id, token.value ?? undefined);
    modalIssue.value = issue;
  } catch (err: unknown) {
    modalIssue.value = null;
    modalError.value =
      err instanceof Error
        ? err.message
        : "De kwestie kon niet geladen worden.";
  } finally {
    modalLoading.value = false;
  }
}

function closeModal() {
  modalIssue.value = null;
  modalError.value = null;
  modalActionPending.value = false;
  modalIssueId.value = null;
  const newQuery = { ...route.query } as Record<string, any>;
  delete newQuery.issue_id;
  router.replace({ query: newQuery });
}

function retryModal() {
  if (modalIssueId.value) {
    void loadIssueForModal(modalIssueId.value);
  }
}

async function handleShare(
  issue: IssueWithArguments | null,
  platform: SharePlatform
) {
  if (!issue) return;

  await shareIssue(issue, { path: route.path, platform });
}

function applyUserVote(
  issue: IssueWithArguments,
  userId: number | null,
  vote: IssueVoteOption
): IssueWithArguments {
  if (!userId) return issue;

  const updatedVotes = {
    agree: [...(issue.votes?.agree ?? [])],
    disagree: [...(issue.votes?.disagree ?? [])],
    neutral: [...(issue.votes?.neutral ?? [])],
  };

  updatedVotes.agree = updatedVotes.agree.filter((id) => id !== userId);
  updatedVotes.disagree = updatedVotes.disagree.filter((id) => id !== userId);
  updatedVotes.neutral = updatedVotes.neutral.filter((id) => id !== userId);

  updatedVotes[vote].push(userId);

  return {
    ...issue,
    votes: updatedVotes,
  };
}
</script>

<style scoped>
.issues-page {
  display: flex;
  justify-content: center;
  padding: 2rem 1rem 4rem;
  background: #f8fafc;
  min-height: 100vh;
}

.issues-page__container {
  width: min(960px, 100%);
  display: grid;
  gap: 2rem;
}

.issues-page__header h1 {
  margin: 0 0 0.5rem;
  font-size: 2rem;
  color: var(--color-primary);
}

.issues-page__header p {
  margin: 0;
  color: #475569;
  line-height: 1.6;
}

.issues-page__state {
  display: grid;
  gap: 0.75rem;
  justify-items: center;
  text-align: center;
  padding: 2rem;
  border-radius: 1rem;
  background: linear-gradient(
    135deg,
    rgba(255, 255, 255, 0.95),
    rgba(238, 241, 246, 0.9)
  );
  box-shadow: 0 18px 36px rgba(0, 28, 70, 0.12);
  color: #101828;
  border: 1px solid rgba(0, 61, 165, 0.12);
}

.issues-page__state--error {
  color: #b91c1c;
}

.issues-page__state--success h2 {
  margin: 0;
}

.issues-page__inline-error {
  margin: 0;
  padding: 0.75rem 1rem;
  border-radius: 0.75rem;
  background: rgba(239, 68, 68, 0.1);
  color: #b91c1c;
}

.issues-page__retry {
  padding: 0.75rem 1.5rem;
  border-radius: 999px;
  border: none;
  background: linear-gradient(
    130deg,
    var(--color-accent),
    var(--color-primary)
  );
  color: white;
  font-weight: 700;
  cursor: pointer;
  transition: transform 0.15s ease, box-shadow 0.15s ease;
}

.issues-page__retry:hover,
.issues-page__retry:focus-visible {
  transform: translateY(-2px);
  box-shadow: 0 14px 26px rgba(0, 61, 165, 0.28);
}
</style>
