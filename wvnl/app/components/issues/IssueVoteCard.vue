<template>
  <article class="issue-card">
    <Teleport v-if="showAuthModal" to="body">
      <div class="modal" @keydown.esc.prevent="closeAuthModal">
        <div class="modal__backdrop" @click="closeAuthModal" />
        <div class="modal__dialog" role="dialog" aria-modal="true">
          <header class="modal__header">
            <h2 class="modal__title">Inloggen vereist</h2>
            <button
              type="button"
              class="modal__close"
              @click="closeAuthModal"
              aria-label="Sluiten"
            >
              ×
            </button>
          </header>
          <div class="modal__body">
            <p class="modal__intro">
              Je bent niet ingelogd en kan daarom geen stem uitbrengen. Log in om
              je mening te geven of maak een account aan.
            </p>
            <div class="modal__actions">
              <NuxtLink to="/login" class="modal__secondary" @click="closeAuthModal">
                Inloggen
              </NuxtLink>
              <NuxtLink to="/register" class="modal__primary" @click="closeAuthModal">
                Registreren
              </NuxtLink>
            </div>
          </div>
        </div>
      </div>
    </Teleport>
    <header class="issue-card__header">
      <div class="issue-card__title-group">
        <p v-if="remaining" class="issue-card__counter">
          Motie #{{ issue.id }}
        </p>
        <h2 class="issue-card__title">{{ issue.title }}</h2>
      </div>
      <ReportMenu
        class="issue-card__report"
        :submit="handleIssueReport"
        success-message="Bedankt voor je melding over deze kwestie."
      />
    </header>
    <p v-if="issue.description" class="issue-card__description">
      {{ issue.description }}
    </p>
    <p
      v-else
      class="issue-card__description issue-card__description--empty"
      aria-hidden="true"
    ></p>
    <section class="issue-card__footer">
      <button
        type="button"
        class="issue-card__button issue-card__button--agree"
        :disabled="disabled"
        @click="emitVote('agree')"
      >
        Voor
      </button>
      <button
        type="button"
        class="issue-card__button issue-card__button--neutral"
        :disabled="disabled"
        @click="emitVote('neutral')"
      >
        Neutraal
      </button>
      <button
        type="button"
        class="issue-card__button issue-card__button--disagree"
        :disabled="disabled"
        @click="emitVote('disagree')"
      >
        Tegen
      </button>
      <button
        v-if="showSkip"
        type="button"
        class="issue-card__button issue-card__button--skip"
        :disabled="disabled"
        @click="emit('skip')"
      >
        Overslaan
      </button>
      <div v-if="showShare" class="issue-card__share">
        <ShareDropdown
          class="issue-card__share-trigger"
          @select="handleShare"
        />
      </div>
    </section>
    <section class="issue-card__results">
      <header class="issue-card__results-header">
        <h3 class="issue-card__results-title">Wat anderen vinden</h3>
        <span class="issue-card__results-total">
          {{ totalVotes }} stem{{ totalVotes === 1 ? "" : "men" }}
        </span>
      </header>
      <p v-if="totalVotes === 0" class="issue-card__results-empty">
        Nog geen stemmen uitgebracht.
      </p>
      <template v-else>
        <div class="issue-card__results-bar">
          <span
            class="issue-card__results-segment issue-card__results-segment--agree"
            :style="{ width: `${votePercentages.agree}%` }"
          />
          <span
            class="issue-card__results-segment issue-card__results-segment--neutral"
            :style="{ width: `${votePercentages.neutral}%` }"
          />
          <span
            class="issue-card__results-segment issue-card__results-segment--disagree"
            :style="{ width: `${votePercentages.disagree}%` }"
          />
        </div>
        <ul class="issue-card__results-list">
          <li class="issue-card__results-item">
            <span class="issue-card__results-label">Voor</span>
            <span class="issue-card__results-value">
              {{ votePercentages.agree }}% · {{ voteCounts.agree }} stem{{
                voteCounts.agree === 1 ? "" : "men"
              }}
            </span>
          </li>
          <li class="issue-card__results-item">
            <span class="issue-card__results-label">Neutraal</span>
            <span class="issue-card__results-value">
              {{ votePercentages.neutral }}% · {{ voteCounts.neutral }} stem{{
                voteCounts.neutral === 1 ? "" : "men"
              }}
            </span>
          </li>
          <li class="issue-card__results-item">
            <span class="issue-card__results-label">Tegen</span>
            <span class="issue-card__results-value">
              {{ votePercentages.disagree }}% · {{ voteCounts.disagree }} stem{{
                voteCounts.disagree === 1 ? "" : "men"
              }}
            </span>
          </li>
        </ul>
      </template>
    </section>
    <IssueArgumentsList :groups="issue.arguments" />
    <IssuePartyStances :stances="issue.party_stances" />
  </article>
</template>

<script setup lang="ts">
import { computed, ref } from "vue";
import { storeToRefs } from "pinia";
import ReportMenu from "~/components/common/ReportMenu.vue";
import ShareDropdown from "~/components/common/ShareDropdown.vue";
import IssueArgumentsList from "~/components/issues/IssueArgumentsList.vue";
import IssuePartyStances from "~/components/issues/IssuePartyStances.vue";
import type { IssueVoteOption, IssueWithArguments } from "~/types/issues";
import type { ReportReason } from "~/types/reports";
import { reportIssue } from "~/services/issues";
import { useAuthStore } from "~/stores/auth";
import type { SharePlatform } from "~/composables/useIssueSharing";

const props = withDefaults(
  defineProps<{
    issue: IssueWithArguments;
    remaining: number;
    disabled?: boolean;
    showSkip?: boolean;
    showShare?: boolean;
  }>(),
  {
    showSkip: true,
    showShare: true,
  }
);

const emit = defineEmits<{
  (e: "vote", vote: IssueVoteOption): void;
  (e: "skip"): void;
  (e: "share", platform: SharePlatform): void;
}>();

const auth = useAuthStore();
const { token } = storeToRefs(auth);

const showAuthModal = ref(false);

const voteCounts = computed(() => {
  const votes = props.issue.votes ?? { agree: [], neutral: [], disagree: [] };

  return {
    agree: votes.agree?.length ?? 0,
    neutral: votes.neutral?.length ?? 0,
    disagree: votes.disagree?.length ?? 0,
  };
});

const totalVotes = computed(
  () =>
    voteCounts.value.agree +
    voteCounts.value.neutral +
    voteCounts.value.disagree
);

const votePercentages = computed(() => {
  const total = totalVotes.value;

  if (!total) {
    return {
      agree: 0,
      neutral: 0,
      disagree: 0,
    };
  }

  const toPercentage = (count: number) => Math.round((count / total) * 100);

  return {
    agree: toPercentage(voteCounts.value.agree),
    neutral: toPercentage(voteCounts.value.neutral),
    disagree: toPercentage(voteCounts.value.disagree),
  };
});

function emitVote(vote: IssueVoteOption) {
  if (!auth.isLoggedIn) {
    showAuthModal.value = true;
    return;
  } else {
    emit("vote", vote);
  }
}

function closeAuthModal() {
  showAuthModal.value = false;
}

function handleShare(platform: SharePlatform) {
  emit("share", platform);
}

async function handleIssueReport(reason: ReportReason) {
  await reportIssue(props.issue.id, reason, token.value ?? undefined);
}
</script>

<style scoped>
.issue-card {
  display: grid;
  gap: 1.75rem;
  padding: 1.75rem;
  border-radius: 1.1rem;
  background: linear-gradient(
    135deg,
    #ffffff 0%,
    rgba(238, 241, 246, 0.85) 100%
  );
  box-shadow: 0 24px 45px rgba(0, 28, 70, 0.12);
  border: 1px solid rgba(0, 61, 165, 0.08);
  width: min(100%, 720px);
  margin: 0 auto;
  box-sizing: border-box;
}

.issue-card__header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 1rem;
}

.issue-card__title-group {
  display: grid;
  gap: 0.5rem;
}

.issue-card__counter {
  margin: 0;
  font-size: 0.9rem;
  color: var(--color-accent);
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.08em;
}

.issue-card__title {
  margin: 0;
  font-size: 1.6rem;
  color: var(--color-primary);
  word-break: break-word;
  hyphens: auto;
}

.issue-card__report {
  flex-shrink: 0;
}

.issue-card__description {
  margin: 0;
  line-height: 1.7;
  color: #344054;
  min-height: 3.2rem;
  word-break: break-word;
}

.issue-card__description--empty {
  visibility: hidden;
}

.issue-card__results {
  display: grid;
  gap: 0.75rem;
  padding: 1.1rem;
  border-radius: 0.9rem;
  background: linear-gradient(
    140deg,
    rgba(255, 255, 255, 0.95),
    rgba(238, 241, 246, 0.9)
  );
  border: 1px solid rgba(0, 61, 165, 0.12);
  min-height: 9rem;
}

.issue-card__results-header {
  display: flex;
  justify-content: space-between;
  align-items: baseline;
  font-weight: 600;
  color: var(--color-primary);
}

.issue-card__results-title {
  margin: 0;
  font-size: 1rem;
}

.issue-card__results-total {
  font-size: 0.9rem;
  color: #475467;
}

.issue-card__results-empty {
  margin: 0;
  font-size: 0.95rem;
  color: #475467;
}

.issue-card__results-bar {
  display: flex;
  width: 100%;
  height: 0.75rem;
  border-radius: 9999px;
  overflow: hidden;
  background-color: rgba(0, 61, 165, 0.12);
}

.issue-card__results-segment {
  display: inline-block;
  height: 100%;
  transition: width 0.3s ease;
}

.issue-card__results-segment--agree {
  background: linear-gradient(135deg, #22c55e, #16a34a);
}

.issue-card__results-segment--neutral {
  background: linear-gradient(135deg, #6b7280, #4b5563);
}

.issue-card__results-segment--disagree {
  background: linear-gradient(135deg, #ef4444, #dc2626);
}

.issue-card__results-list {
  display: grid;
  gap: 0.25rem;
  margin: 0;
  padding: 0;
  list-style: none;
}

.issue-card__results-item {
  display: flex;
  justify-content: space-between;
  font-size: 0.95rem;
  color: #101828;
}

.issue-card__results-label {
  font-weight: 600;
}

.issue-card__results-value {
  color: #475569;
}

.issue-card__footer {
  display: flex;
  flex-wrap: wrap;
  gap: 0.85rem;
}

.issue-card__button {
  flex: 1 1 160px;
  padding: 0.8rem 1.1rem;
  border-radius: 0.85rem;
  border: none;
  font-weight: 700;
  cursor: pointer;
  color: #ffffff;
  transition: transform 0.15s ease, box-shadow 0.15s ease, opacity 0.15s ease;
}

.issue-card__button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none;
  box-shadow: none;
}

.issue-card__button:not(:disabled):hover,
.issue-card__button:not(:disabled):focus-visible {
  transform: translateY(-2px);
  box-shadow: 0 16px 26px rgba(0, 28, 70, 0.18);
}

.issue-card__share {
  flex: 1 1 160px;
  display: flex;
}

.issue-card__share :deep(.share-dropdown) {
  flex: 1 1 auto;
}

.issue-card__share :deep(.issue-card__button) {
  width: 100%;
  min-height: 40px;
}

@media (max-width: 640px) {
  .issue-card {
    padding: 1.5rem;
  }

  .issue-card__footer {
    flex-direction: column;
  }

  .issue-card__button,
  .issue-card__share {
    flex: 1 1 auto;
    width: 100%;
  }
}

.issue-card__button--agree {
  background: linear-gradient(135deg, #22c55e, #16a34a);
}

.issue-card__button--neutral {
  background: linear-gradient(135deg, #6b7280, #4b5563);
}

.issue-card__button--disagree {
  background: linear-gradient(135deg, #ef4444, #dc2626);
}

.issue-card__button--skip {
  background: linear-gradient(135deg, #ffe0b2, #ff9b00);
  color: #2f1300;
}

.issue-card__button--share {
  background: linear-gradient(135deg, #003da5, #0a4bc9);
  color: #ffffff;
  box-shadow: 0 14px 28px rgba(0, 61, 165, 0.25);
  display: none !important;
}

.modal {
  position: fixed;
  inset: 0;
  z-index: 1600;
  display: grid;
  place-items: center;
  padding: 1.5rem;
}

.modal__backdrop {
  position: absolute;
  inset: 0;
  background: rgba(15, 23, 42, 0.6);
  backdrop-filter: blur(2px);
}

.modal__dialog {
  position: relative;
  width: min(420px, 100%);
  border-radius: 18px;
  background: linear-gradient(135deg, rgba(255, 255, 255, 0.97), rgba(238, 241, 246, 0.97));
  box-shadow: 0 24px 48px rgba(0, 26, 77, 0.28);
  border: 1px solid rgba(0, 61, 165, 0.12);
  padding: 1.75rem;
  display: grid;
  gap: 1.25rem;
}

.modal__header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
}

.modal__title {
  margin: 0;
  font-size: 1.35rem;
  color: var(--color-primary, #003da5);
}

.modal__close {
  border: none;
  background: rgba(255, 255, 255, 0.95);
  border-radius: 999px;
  width: 2.2rem;
  height: 2.2rem;
  font-size: 1.5rem;
  color: var(--color-accent, #c8102e);
  cursor: pointer;
  box-shadow: 0 6px 14px rgba(0, 26, 77, 0.18);
}

.modal__close:hover,
.modal__close:focus-visible {
  background: rgba(200, 16, 46, 0.12);
}

.modal__body {
  display: grid;
  gap: 1.25rem;
}

.modal__intro {
  margin: 0;
  color: #1f2937;
  line-height: 1.6;
}

.modal__actions {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
}

.modal__secondary,
.modal__primary {
  border-radius: 999px;
  padding: 0.6rem 1.4rem;
  font-weight: 700;
  cursor: pointer;
  border: none;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  transition: transform 0.15s ease, box-shadow 0.15s ease;
}

.modal__secondary {
  background: rgba(15, 23, 42, 0.05);
  color: #0f172a;
}

.modal__secondary:hover,
.modal__secondary:focus-visible {
  transform: translateY(-1px);
  box-shadow: 0 8px 16px rgba(15, 23, 42, 0.15);
}

.modal__primary {
  background: linear-gradient(130deg, var(--color-accent, #c8102e), var(--color-primary, #003da5));
  color: #fff;
  box-shadow: 0 12px 24px rgba(0, 61, 165, 0.28);
}

.modal__primary:hover,
.modal__primary:focus-visible {
  transform: translateY(-1px);
  box-shadow: 0 16px 28px rgba(0, 61, 165, 0.32);
}
</style>
