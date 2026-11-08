<template>
  <section class="issues-page">
    <div class="issues-page__container">
      <header class="issues-page__header">
        <h1>Wat denk jij?</h1>
        <p>Tegen, voor of neutraal. Neem de tijd voor jouw mening.</p>
        <span v-if="auth.isLoggedIn" class="issues-page__personal-count"
          ><span aria-hidden="true">✦</span>
          {{ auth.user?.voted_issue_ids?.length ?? 0 }} stellingen beantwoord
          door jou</span
        >
      </header>

      <DevReportButton floating />

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
        v-else-if="!activeIssue && !loading && !error"
        class="issues-page__state issues-page__state--success"
      >
        <img
          class="issues-page__empty-art"
          src="/illustrations/dutch-neighbours-v1.jpg"
          alt=""
          width="1536"
          height="1024"
        />
        <h2>Je bent helemaal bij!</h2>
        <p>Er staan momenteel geen onbeantwoorde kwesties voor je klaar.</p>
        <button type="button" class="issues-page__retry" @click="loadPending">
          Vernieuw
        </button>
      </div>
      <AdSlot v-if="!loading && activeIssue" placement="spel-onder-stelling" />
      <DemoAdDialog :open="adBreak.open.value" @close="adBreak.close" />
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
definePageMeta({ alias: "/spel" });
import { computed, onMounted, ref, watch } from "vue";
import { storeToRefs } from "pinia";
import DevReportButton from "~/components/reports/DevReportButton.vue";
import DemoAdDialog from "~/components/common/DemoAdDialog.vue";
import AdSlot from "~/components/common/AdSlot.vue";
import IssueDetailModal from "~/components/issues/IssueDetailModal.vue";
import IssueVoteCard from "~/components/issues/IssueVoteCard.vue";
import { useIssues } from "~/composables/useIssues";
import { useAuthStore } from "~/stores/auth";
import { useNotificationStore } from "~/stores/notifications";
import { useIssueSharing } from "~/composables/useIssueSharing";
import type { SharePlatform } from "~/composables/useIssueSharing";
import { fetchIssueWithArguments, voteOnIssue } from "~/services/issues";
import type { IssueVoteOption, IssueWithArguments } from "~/types/issues";
import { translateErrorMessage } from "~/utils/translateErrorMessage";

const siteName = "Wat Denkt Nederland";
const title = "Openstaande moties | Wat Denkt Nederland";
const description =
  "Bekijk openstaande politieke moties, lees de argumenten en geef je stem op het platform van Wat Denkt Nederland.";
const url = "https://watvindtnl.nl/issues";

const jsonLd = {
  "@context": "https://schema.org",
  "@type": "WebPage",
  name: title,
  description,
  url,
  inLanguage: "nl-NL",
  isPartOf: {
    "@type": "WebSite",
    name: siteName,
    url: "https://watvindtnl.nl",
  },
};

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
const adBreak = useAdBreak();
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
  { immediate: true },
);

onMounted(() => {
  loadPending();
});

function notifyLoginRequired() {
  notifications.addNotification({
    message: "Log in of registreer om te stemmen.",
    html: 'Je kan pas stemmen als je een account hebt aangemaakt. <a class="issues-page__auth-link" href="/login">Log in</a> of <a class="issues-page__auth-link" href="/register">registreer</a> om mee te doen.',
    type: "info",
    timeout: 6000,
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
  adBreak.next(Boolean(activeIssue.value?.user_vote), skip);
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
    const response = await voteOnIssue(
      issue.id,
      choice,
      token.value ?? undefined,
    );
    if (user.value)
      auth.updateUser({
        ...user.value,
        voted_issue_ids: response.voted_issue_ids,
      });
    const updated = {
      ...issue,
      votes: response.votes,
      user_vote: response.user_vote,
    };
    modalIssue.value = updated;
    const index = issues.value.findIndex((item) => item.id === issue.id);
    if (index >= 0) issues.value[index] = updated;
  } catch (err: unknown) {
    modalError.value = translateErrorMessage(err, {
      fallback: "Stemmen is niet gelukt.",
    });
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
    modalError.value = translateErrorMessage(err, {
      fallback: "De kwestie kon niet geladen worden.",
    });
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
  platform: SharePlatform,
) {
  if (!issue) return;

  await shareIssue(issue, { path: route.path, platform });
}
</script>

<style scoped>
.issues-page {
  display: flex;
  justify-content: center;
  padding: 2rem clamp(1rem, 4vw, 3rem) 4rem;
  background: #f8fafc;
  min-height: 70vh;
}

.issues-page__container {
  width: min(720px, 100%);
  display: grid;
  gap: 2rem;
}

.issues-page__header h1 {
  margin: 0 0 0.5rem;
  font-size: 1.65rem;
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
  box-shadow: var(--shadow-soft);
  color: #101828;
  border: 1px solid rgba(0, 51, 102, 0.12);
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
    var(--color-orange),
    var(--color-orange-hover)
  );
  color: var(--color-midnight);
  font-weight: 700;
  cursor: pointer;
  transition:
    transform 0.15s ease,
    box-shadow 0.15s ease;
}

.issues-page__retry:hover,
.issues-page__retry:focus-visible {
  transform: translateY(-2px);
  box-shadow: var(--shadow-soft);
}

.issues-page__auth-link {
  color: inherit;
  font-weight: 700;
  text-decoration: underline;
}
.issues-page__personal-count {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  border: 1px solid #dae5f5;
  border-radius: 20px;
  background: #eef4ff;
  color: #0057b8;
  font-size: 0.72rem;
  padding: 0.45rem 0.85rem;
  margin-top: 0.75rem;
}
.issues-page__empty-art {
  display: block;
  width: min(320px, 100%);
  height: auto;
  margin: 0 auto 1.3rem;
  border-radius: 18px;
}
</style>
