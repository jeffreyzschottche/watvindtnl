<script setup lang="ts">
import IssueVoteCard from "~/components/issues/IssueVoteCard.vue";
import { fetchIssueBySlug, voteOnIssue } from "~/services/issues";
import type { IssueWithArguments, IssueVoteOption } from "~/types/issues";
import { translateErrorMessage } from "~/utils/translateErrorMessage";
const route = useRoute();
const auth = useAuthStore();
const issue = ref<IssueWithArguments | null>(null);
const loading = ref(true);
const pending = ref(false);
const error = ref("");
async function load() {
  loading.value = true;
  error.value = "";
  try {
    issue.value = await fetchIssueBySlug(
      String(route.params.slug),
      auth.token ?? undefined,
    );
  } catch {
    error.value =
      "Deze stelling kon niet worden geladen. Controleer de link of probeer het opnieuw.";
  } finally {
    loading.value = false;
  }
}
async function vote(choice: IssueVoteOption) {
  if (!issue.value || pending.value) return;
  pending.value = true;
  error.value = "";
  try {
    const response = await voteOnIssue(
      issue.value.id,
      choice,
      auth.token ?? undefined,
    );
    issue.value = {
      ...issue.value,
      user_vote: response.user_vote,
      votes: response.votes,
    };
    if (auth.user)
      auth.updateUser({
        ...auth.user,
        voted_issue_ids: response.voted_issue_ids,
      });
  } catch (err) {
    error.value = translateErrorMessage(err, {
      fallback: "Stemmen is niet gelukt.",
    });
  } finally {
    pending.value = false;
  }
}
onMounted(load);
watch(() => route.params.slug, load);
watch(() => auth.token, load);
useSeoMeta({
  title: () => `${issue.value?.title ?? "Stelling"} | WatDenktNederland`,
  description: () =>
    issue.value?.description ?? "Lees de argumenten en geef je mening.",
});
</script>
<template>
  <section class="statement-detail container">
    <NuxtLink to="/stellingen" class="statement-detail__back"
      >← Alle stellingen</NuxtLink
    >
    <p v-if="loading" role="status">De stelling wordt geladen…</p>
    <template v-else
      ><p v-if="error" role="alert" class="form-error">{{ error }}</p>
      <IssueVoteCard
        v-if="issue"
        :issue="issue"
        :remaining="0"
        :disabled="pending"
        :show-skip="false"
        :show-share="false"
        @vote="vote"
      /><NuxtLink
        v-if="issue?.user_vote"
        class="button statement-detail__next"
        to="/spel"
        >Verder met stemmen →</NuxtLink
      ><button
        v-if="!issue && error"
        class="button button-secondary"
        @click="load"
      >
        Opnieuw proberen
      </button></template
    >
  </section>
</template>
<style scoped>
.statement-detail {
  max-width: 780px;
  padding-block: 32px 60px;
}
.statement-detail__back {
  display: inline-block;
  font-size: 0.8rem;
  margin-bottom: 1.5rem;
  color: #63738a;
}
.statement-detail__next {
  display: flex;
  margin-top: 1rem;
}
</style>
