<template>
  <article class="issue-card">
    <header class="issue-card__header">
      <p v-if="remaining" class="issue-card__counter">
        Nog {{ remaining }} vraag{{ remaining === 1 ? "" : "en" }} te gaan
      </p>
      <h2 class="issue-card__title">{{ issue.title }}</h2>
    </header>
    <p v-if="issue.description" class="issue-card__description">
      {{ issue.description }}
    </p>
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
              {{ votePercentages.agree }}%
              ·
              {{ voteCounts.agree }} stem{{ voteCounts.agree === 1 ? "" : "men" }}
            </span>
          </li>
          <li class="issue-card__results-item">
            <span class="issue-card__results-label">Neutraal</span>
            <span class="issue-card__results-value">
              {{ votePercentages.neutral }}%
              ·
              {{ voteCounts.neutral }} stem{{ voteCounts.neutral === 1 ? "" : "men" }}
            </span>
          </li>
          <li class="issue-card__results-item">
            <span class="issue-card__results-label">Tegen</span>
            <span class="issue-card__results-value">
              {{ votePercentages.disagree }}%
              ·
              {{ voteCounts.disagree }} stem{{ voteCounts.disagree === 1 ? "" : "men" }}
            </span>
          </li>
        </ul>
      </template>
    </section>
    <IssuePartyStances :stances="issue.party_stances" />
    <IssueArgumentsList :groups="issue.arguments" />
    <footer class="issue-card__footer">
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
        type="button"
        class="issue-card__button issue-card__button--skip"
        :disabled="disabled"
        @click="emit('skip')"
      >
        Overslaan
      </button>
    </footer>
  </article>
</template>

<script setup lang="ts">
import { computed } from "vue";
import IssueArgumentsList from "~/components/issues/IssueArgumentsList.vue";
import IssuePartyStances from "~/components/issues/IssuePartyStances.vue";
import type { IssueVoteOption, IssueWithArguments } from "~/types/issues";

const props = defineProps<{
  issue: IssueWithArguments;
  remaining: number;
  disabled?: boolean;
}>();

const emit = defineEmits<{
  (e: "vote", vote: IssueVoteOption): void;
  (e: "skip"): void;
}>();

const voteCounts = computed(() => {
  const votes = props.issue.votes ?? { agree: [], neutral: [], disagree: [] };

  return {
    agree: votes.agree?.length ?? 0,
    neutral: votes.neutral?.length ?? 0,
    disagree: votes.disagree?.length ?? 0,
  };
});

const totalVotes = computed(
  () => voteCounts.value.agree + voteCounts.value.neutral + voteCounts.value.disagree,
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
  emit("vote", vote);
}
</script>

<style scoped>
.issue-card {
  display: grid;
  gap: 1.5rem;
  padding: 1.5rem;
  border-radius: 1rem;
  background: #ffffff;
  box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
}

.issue-card__header {
  display: grid;
  gap: 0.5rem;
}

.issue-card__counter {
  margin: 0;
  font-size: 0.9rem;
  color: #2563eb;
  font-weight: 600;
  text-transform: uppercase;
}

.issue-card__title {
  margin: 0;
  font-size: 1.5rem;
}

.issue-card__description {
  margin: 0;
  line-height: 1.6;
  color: #374151;
}

.issue-card__results {
  display: grid;
  gap: 0.75rem;
  padding: 1rem;
  border-radius: 0.75rem;
  background-color: #f8fafc;
}

.issue-card__results-header {
  display: flex;
  justify-content: space-between;
  align-items: baseline;
  font-weight: 600;
  color: #0f172a;
}

.issue-card__results-title {
  margin: 0;
  font-size: 1rem;
}

.issue-card__results-total {
  font-size: 0.9rem;
  color: #64748b;
}

.issue-card__results-empty {
  margin: 0;
  font-size: 0.95rem;
  color: #64748b;
}

.issue-card__results-bar {
  display: flex;
  width: 100%;
  height: 0.75rem;
  border-radius: 9999px;
  overflow: hidden;
  background-color: #e2e8f0;
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
  color: #0f172a;
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
  gap: 0.75rem;
}

.issue-card__button {
  flex: 1 1 160px;
  padding: 0.75rem 1rem;
  border-radius: 0.75rem;
  border: none;
  font-weight: 600;
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

.issue-card__button:not(:disabled):hover {
  transform: translateY(-2px);
  box-shadow: 0 12px 24px rgba(15, 23, 42, 0.15);
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
  background: linear-gradient(135deg, #93c5fd, #60a5fa);
  color: #1e3a8a;
}
</style>
