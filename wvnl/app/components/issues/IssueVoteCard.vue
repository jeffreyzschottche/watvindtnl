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
import IssueArgumentsList from "~/components/issues/IssueArgumentsList.vue";
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
