<template>
  <Teleport to="body">
    <div class="issue-modal">
      <div class="issue-modal__backdrop" @click="emit('close')" />
      <div class="issue-modal__dialog" role="dialog" aria-modal="true">
        <header class="issue-modal__header">
          <h2 class="issue-modal__title">{{ issue?.title ?? "Kwestie" }}</h2>
          <button
            type="button"
            class="issue-modal__close"
            aria-label="Sluit kwestie"
            @click="emit('close')"
          >
            ×
          </button>
        </header>
        <section v-if="loading" class="issue-modal__state">
          Bezig met laden...
        </section>
        <section v-else-if="error" class="issue-modal__state issue-modal__state--error">
          <p>{{ error }}</p>
          <button
            v-if="canRetry"
            type="button"
            class="issue-modal__retry"
            @click="emit('retry')"
          >
            Probeer opnieuw
          </button>
        </section>
        <IssueVoteCard
          v-else-if="issue"
          :issue="issue"
          :remaining="0"
          :disabled="actionPending"
          :show-skip="false"
          @vote="emit('vote', $event)"
          @skip="emit('close')"
          @share="emit('share')"
        />
      </div>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import IssueVoteCard from "~/components/issues/IssueVoteCard.vue";
import type { IssueWithArguments } from "~/types/issues";

withDefaults(
  defineProps<{
    issue: IssueWithArguments | null;
    loading: boolean;
    error: string | null;
    actionPending: boolean;
    canRetry?: boolean;
  }>(),
  {
    issue: null,
    loading: false,
    error: null,
    actionPending: false,
    canRetry: true,
  }
);

const emit = defineEmits<{
  (e: "close"): void;
  (e: "retry"): void;
  (e: "vote", vote: "agree" | "neutral" | "disagree"): void;
  (e: "share"): void;
}>();
</script>

<style scoped>
.issue-modal {
  position: fixed;
  inset: 0;
  z-index: 1500;
  display: grid;
  place-items: center;
  padding: 2rem 1rem;
}

.issue-modal__backdrop {
  position: absolute;
  inset: 0;
  background: rgba(15, 23, 42, 0.6);
  backdrop-filter: blur(2px);
}

.issue-modal__dialog {
  position: relative;
  width: min(720px, 100%);
  max-height: calc(100vh - 4rem);
  overflow-y: auto;
  border-radius: 1rem;
  background: #f8fafc;
  padding: 1.5rem;
  box-shadow: 0 20px 40px rgba(15, 23, 42, 0.3);
}

.issue-modal__header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.issue-modal__title {
  margin: 0;
  font-size: 1.25rem;
  color: #0f172a;
}

.issue-modal__close {
  border: none;
  width: 2rem;
  height: 2rem;
  border-radius: 999px;
  background: rgba(15, 23, 42, 0.1);
  color: #0f172a;
  font-size: 1.5rem;
  cursor: pointer;
}

.issue-modal__close:hover {
  background: rgba(15, 23, 42, 0.2);
}

.issue-modal__state {
  display: grid;
  gap: 0.75rem;
  justify-items: center;
  text-align: center;
  padding: 2rem 1rem;
  border-radius: 0.75rem;
  background: #ffffff;
  color: #0f172a;
}

.issue-modal__state--error {
  color: #b91c1c;
}

.issue-modal__retry {
  padding: 0.5rem 1.25rem;
  border-radius: 999px;
  border: none;
  background: linear-gradient(135deg, #3b82f6, #2563eb);
  color: #ffffff;
  font-weight: 600;
  cursor: pointer;
}

.issue-modal__retry:hover {
  transform: translateY(-1px);
  box-shadow: 0 10px 18px rgba(37, 99, 235, 0.25);
}
</style>
