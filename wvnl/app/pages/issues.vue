<template>
  <section class="issues-page">
    <div class="issues-page__container">
      <header class="issues-page__header">
        <h1>Openstaande kwesties</h1>
        <p>
          Geef aan hoe jij over de volgende stellingen denkt. Kies voor, tegen,
          neutraal of sla over als je het nog niet weet.
        </p>
      </header>

      <div v-if="loading" class="issues-page__state">
        Even geduld, we laden je vragen...
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
      />

      <p v-if="error && activeIssue" class="issues-page__inline-error">
        {{ error }}
      </p>

      <div v-else class="issues-page__state issues-page__state--success">
        <h2>Je bent helemaal bij!</h2>
        <p>Er staan momenteel geen onbeantwoorde kwesties voor je klaar.</p>
        <button type="button" class="issues-page__retry" @click="loadPending">
          Vernieuw
        </button>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { onMounted } from "vue";
import IssueVoteCard from "~/components/issues/IssueVoteCard.vue";
import { useIssues } from "~/composables/useIssues";
import type { IssueVoteOption } from "~/types/issues";

const { activeIssue, remaining, loading, actionPending, error, loadPending, vote, skip } =
  useIssues();

onMounted(() => {
  loadPending();
});

function handleVote(choice: IssueVoteOption) {
  void vote(choice);
}

function handleSkip() {
  skip();
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
  background: #ffffff;
  box-shadow: 0 10px 30px rgba(15, 23, 42, 0.05);
  color: #0f172a;
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
  background: linear-gradient(135deg, #3b82f6, #2563eb);
  color: white;
  font-weight: 600;
  cursor: pointer;
  transition: transform 0.15s ease, box-shadow 0.15s ease;
}

.issues-page__retry:hover {
  transform: translateY(-2px);
  box-shadow: 0 12px 20px rgba(37, 99, 235, 0.25);
}
</style>
