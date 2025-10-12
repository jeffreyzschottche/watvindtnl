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
          @share="emit('share', $event)"
        />
      </div>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import IssueVoteCard from "~/components/issues/IssueVoteCard.vue";
import type { SharePlatform } from "~/composables/useIssueSharing";
import type { IssueVoteOption, IssueWithArguments } from "~/types/issues";

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
  (e: "vote", vote: IssueVoteOption): void;
  (e: "share", platform: SharePlatform): void;
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
  background: linear-gradient(135deg, rgba(255, 255, 255, 0.96), rgba(238, 241, 246, 0.96));
  padding: 1.75rem;
  box-shadow: 0 28px 60px rgba(0, 26, 77, 0.35);
  border: 1px solid rgba(0, 61, 165, 0.12);
}

.issue-modal__header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.issue-modal__title {
  margin: 0;
  font-size: 1.3rem;
  color: var(--color-primary);
}

.issue-modal__close {
  border: 1px solid rgba(0, 61, 165, 0.25);
  width: 2.2rem;
  height: 2.2rem;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.9);
  color: var(--color-accent);
  font-size: 1.5rem;
  cursor: pointer;
  transition: background 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease;
}

.issue-modal__close:hover,
.issue-modal__close:focus-visible {
  background: rgba(200, 16, 46, 0.12);
  transform: translateY(-1px);
  box-shadow: 0 10px 20px rgba(0, 26, 77, 0.18);
}

.issue-modal__state {
  display: grid;
  gap: 0.75rem;
  justify-items: center;
  text-align: center;
  padding: 2rem 1rem;
  border-radius: 0.85rem;
  background: rgba(255, 255, 255, 0.95);
  color: #101828;
  border: 1px solid rgba(0, 61, 165, 0.12);
  box-shadow: 0 12px 24px rgba(0, 26, 77, 0.12);
}

.issue-modal__state--error {
  color: #b91c1c;
}

.issue-modal__retry {
  padding: 0.55rem 1.35rem;
  border-radius: 999px;
  border: none;
  background: linear-gradient(130deg, var(--color-accent), var(--color-primary));
  color: #ffffff;
  font-weight: 700;
  cursor: pointer;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.issue-modal__retry:hover,
.issue-modal__retry:focus-visible {
  transform: translateY(-1px);
  box-shadow: 0 16px 30px rgba(0, 61, 165, 0.35);
}
</style>
