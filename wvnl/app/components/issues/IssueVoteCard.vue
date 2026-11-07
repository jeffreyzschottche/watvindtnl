<script setup lang="ts">
import { computed } from "vue";
import { storeToRefs } from "pinia";
import type { IssueVoteOption, IssueWithArguments } from "~/types/issues";
import type { SharePlatform } from "~/composables/useIssueSharing";
import type { ReportReason } from "~/types/reports";
import { reportIssue } from "~/services/issues";
import { useAuthStore } from "~/stores/auth";
import { votePercentages } from "~/utils/voting";
import IssueArgumentsList from "~/components/issues/IssueArgumentsList.vue";
import IssuePartyStances from "~/components/issues/IssuePartyStances.vue";
import ReportMenu from "~/components/common/ReportMenu.vue";
import ShareDropdown from "~/components/common/ShareDropdown.vue";

const props = withDefaults(defineProps<{
  issue: IssueWithArguments;
  remaining: number;
  disabled?: boolean;
  showSkip?: boolean;
  showShare?: boolean;
}>(), { showSkip: true, showShare: true });
const emit = defineEmits<{
  vote: [vote: IssueVoteOption];
  skip: [];
  share: [platform: SharePlatform];
}>();
const auth = useAuthStore();
const { token } = storeToRefs(auth);
const counts = computed(() => props.issue.votes ?? { agree: 0, disagree: 0, neutral: 0 });
const percentages = computed(() => votePercentages(counts.value));
const total = computed(() => counts.value.agree + counts.value.disagree + counts.value.neutral);
const answered = computed(() => props.issue.user_vote != null);
const choiceLabel = computed(() => ({ agree: "voor", disagree: "tegen", neutral: "neutraal" })[props.issue.user_vote ?? "neutral"]);
async function handleReport(reason: ReportReason) {
  await reportIssue(props.issue.id, reason, token.value ?? undefined);
}
</script>

<template>
  <article class="vote-card-v2" :aria-busy="disabled">
    <header class="vote-card-v2__meta">
      <span class="vote-card-v2__badge">Stelling #{{ issue.id }}</span>
      <ReportMenu :key="issue.id" :submit="handleReport" success-message="Bedankt voor je melding." />
    </header>
    <div class="vote-card-v2__question">
      <p class="vote-card-v2__eyebrow">Eén vraag. Twee kanten.</p>
      <h2>{{ issue.title }}</h2>
      <p v-if="issue.description" class="vote-card-v2__description">{{ issue.description }}</p>
    </div>

    <template v-if="!answered">
      <div v-if="auth.isLoggedIn" class="vote-card-v2__choices">
        <button class="vote-choice vote-choice--against" type="button" :disabled="disabled" @click="emit('vote', 'disagree')">
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 13V3H3v10h4Zm0-9h9a2 2 0 0 1 2 1.7l1 7a2 2 0 0 1-2 2.3h-4l1 5-1 1-6-8" /></svg>
          <strong>Tegen</strong><span>Ik ben het oneens</span>
        </button>
        <button class="vote-choice vote-choice--for" type="button" :disabled="disabled" @click="emit('vote', 'agree')">
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 11v10H3V11h4Zm0 9h9a2 2 0 0 0 2-1.7l1-7a2 2 0 0 0-2-2.3h-4l1-5-1-1-6 8" /></svg>
          <strong>Voor</strong><span>Ik ben het eens</span>
        </button>
      </div>
      <div v-else class="vote-card-v2__login">
        <p>Log in om je mening te geven en de uitslag te bekijken.</p>
        <NuxtLink class="vote-card-v2__primary" to="/inloggen">Inloggen om te stemmen →</NuxtLink>
        <NuxtLink to="/registreren">Nog geen account? Registreren</NuxtLink>
      </div>
      <p v-if="disabled" class="vote-card-v2__status" role="status">Je stem wordt opgeslagen…</p>
      <button v-if="showSkip" class="vote-card-v2__text-button" type="button" :disabled="disabled" @click="emit('skip')">Deze stelling overslaan →</button>
    </template>

    <section v-else class="vote-card-v2__results" aria-live="polite" aria-atomic="true">
      <p class="vote-card-v2__confirmation">✓ Je hebt {{ choiceLabel }} gestemd</p>
      <h3>Zo denken de deelnemers</h3>
      <div class="vote-card-v2__bar" role="img" :aria-label="`${percentages.disagree}% tegen, ${percentages.agree}% voor, ${percentages.neutral}% historisch neutraal`">
        <span class="against" :style="{ width: `${percentages.disagree}%` }" />
        <span class="neutral" :style="{ width: `${percentages.neutral}%` }" />
        <span class="for" :style="{ width: `${percentages.agree}%` }" />
      </div>
      <div class="vote-card-v2__numbers">
        <p class="against-text"><strong>{{ percentages.disagree }}%</strong>Tegen · {{ counts.disagree }}</p>
        <p class="for-text"><strong>{{ percentages.agree }}%</strong>Voor · {{ counts.agree }}</p>
      </div>
      <p v-if="counts.neutral" class="vote-card-v2__note">{{ counts.neutral }} historische neutrale stemmen ({{ percentages.neutral }}%) blijven meegeteld.</p>
      <p class="vote-card-v2__note">{{ total }} {{ total === 1 ? 'stem' : 'stemmen' }} van WDNL-deelnemers. Deze uitslag is geen representatieve peiling van Nederland.</p>
      <button v-if="showSkip" class="vote-card-v2__primary" type="button" :disabled="disabled" @click="emit('skip')">Volgende stelling →</button>
    </section>

    <details class="vote-card-v2__background" :open="answered">
      <summary>Argumenten en achtergrond <span aria-hidden="true">＋</span></summary>
      <IssueArgumentsList :groups="issue.arguments" />
      <div v-if="issue.more_info" class="vote-card-v2__context"><h3>De context</h3><p>{{ issue.more_info }}</p></div>
      <IssuePartyStances :stances="issue.party_stances" />
      <a v-if="issue.url && /^https?:\/\//.test(issue.url)" :href="issue.url" target="_blank" rel="noopener noreferrer">Bekijk de oorspronkelijke bron ↗</a>
      <p v-else class="vote-card-v2__note">Bij deze oudere stelling is nog geen directe bronlink vastgelegd.</p>
    </details>
    <div v-if="showShare" class="vote-card-v2__share"><ShareDropdown @select="emit('share', $event)" /></div>
  </article>
</template>

<style scoped>
.vote-card-v2 { color: #001f4d; background: #fff; width: 100%; max-width: 720px; padding: clamp(1.25rem, 4vw, 2.5rem); border: 1px solid #dce3ee; border-radius: 20px; box-shadow: 0 6px 28px #001f4d06; display: grid; gap: 1.5rem; box-sizing: border-box; }
.vote-card-v2__meta { display: flex; justify-content: space-between; align-items: center; }.vote-card-v2__badge { color: #0057b8; background: #eaf1ff; font-size: .75rem; font-weight: 700; border-radius: 30px; padding: .45rem .8rem; }
.vote-card-v2__question { text-align: center; }.vote-card-v2__eyebrow { color: #586980; font-size: .75rem; letter-spacing: .12em; text-transform: uppercase; }.vote-card-v2 h2 { color: #001f4d; font-size: clamp(1.5rem, 4vw, 2.1rem); line-height: 1.22; letter-spacing: -.035em; margin: .9rem 0; }.vote-card-v2__description { color: #526277; font-size: .95rem; line-height: 1.65; max-width: 55ch; margin: auto; }
.vote-card-v2__choices { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }.vote-choice { display: grid; justify-items: center; gap: .5rem; border: 1px solid transparent; border-radius: 15px; padding: 1.5rem .8rem; cursor: pointer; min-height: 150px; font: inherit; transition: border-color .15s, transform .15s; }.vote-choice svg { width: 32px; height: 32px; stroke: currentColor; stroke-width: 1.8; fill: none; stroke-linejoin: round; }.vote-choice strong { font-size: 1.35rem; }.vote-choice span { font-size: .8rem; }.vote-choice--against { background: #fff0f3; color: #be0025; }.vote-choice--for { background: #edf3ff; color: #0057b8; }.vote-choice:hover:not(:disabled) { border-color: currentColor; transform: translateY(-2px); }
.vote-card-v2__primary { display: flex; justify-content: center; align-items: center; gap: .5rem; width: 100%; background: #001f4d; color: white; min-height: 48px; padding: .8rem 1rem; border: 0; border-radius: 10px; font: inherit; font-weight: 700; cursor: pointer; box-sizing: border-box; text-decoration: none; }.vote-card-v2__primary:hover { background: #0057b8; text-decoration: none; }.vote-card-v2__text-button { min-height: 44px; font: inherit; font-size: .85rem; font-weight: 600; border: 0; background: transparent; color: #526277; cursor: pointer; }.vote-card-v2 button:disabled { opacity: .55; cursor: wait; }.vote-card-v2 :focus-visible { outline: 3px solid #0057b8; outline-offset: 4px; }
.vote-card-v2__results { text-align: center; }.vote-card-v2__confirmation { color: #24743b; font-size: .85rem; font-weight: 600; }.vote-card-v2 h3 { color: #001f4d; margin: 1.1rem 0; }.vote-card-v2__bar { display: flex; height: 22px; background: #edf0f5; border-radius: 20px; overflow: hidden; }.against { background: #e1062e; }.for { background: #0057b8; }.neutral { background: #8794a5; }.vote-card-v2__numbers { display: flex; justify-content: space-between; }.vote-card-v2__numbers p { display: grid; text-align: left; font-size: .8rem; }.vote-card-v2__numbers p:last-child { text-align: right; }.vote-card-v2__numbers strong { font-size: 1.8rem; letter-spacing: -.04em; }.against-text { color: #c50025; }.for-text { color: #0057b8; }.vote-card-v2__note { font-size: .78rem; line-height: 1.6; color: #586980; margin: .5rem 0 1rem; }
.vote-card-v2__background { border-top: 1px solid #e3e9f1; padding-top: 1rem; }.vote-card-v2__background summary { cursor: pointer; font-weight: 700; font-size: .9rem; min-height: 44px; display: flex; align-items: center; justify-content: space-between; }.vote-card-v2__background[open] summary { margin-bottom: 1rem; }.vote-card-v2__context p { color: #526277; line-height: 1.7; white-space: pre-line; }.vote-card-v2__share { display: flex; justify-content: flex-end; }.vote-card-v2__login { display: grid; gap: 1rem; text-align: center; font-size: .9rem; }.vote-card-v2__status { text-align: center; color: #526277; }
@media (prefers-reduced-motion: reduce) { .vote-choice { transition: none; }.vote-choice:hover:not(:disabled) { transform: none; } }
</style>
