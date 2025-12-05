<template>
  <div class="page page--news-single">
    <section class="page-hero page-hero--news page-hero--news-detail">
      <div class="container narrow">
        <p class="page-hero__eyebrow">
          {{ article ? `Motie ${article.issue?.title ?? ""}` : "Motie-uitleg" }}
        </p>
        <h1 class="page-hero__title">
          <span class="page-hero__accent page-hero__accent--blue">Nieuws</span>
          <span class="page-hero__accent page-hero__accent--red">update</span>
        </h1>
        <p class="page-hero__headline">
          <template v-if="article">{{ article.title }}</template>
          <template v-else-if="loading">Artikel wordt geladen...</template>
          <template v-else-if="error">Kon deze motie niet laden.</template>
          <template v-else>Geen gegevens voor deze motie.</template>
        </p>
        <p v-if="article?.excerpt" class="page-hero__subtitle">
          {{ article.excerpt }}
        </p>
        <p v-else-if="!loading && !error" class="page-hero__subtitle">
          Zodra er voldoende informatie beschikbaar is tonen we hier de
          volledige uitleg.
        </p>
        <div v-if="article" class="news-single__hero-actions">
          <NuxtLink
            class="button"
            :to="voteUrl(article.issue?.id ?? article.issue_id)"
          >
            Stem op deze motie
          </NuxtLink>
          <NuxtLink class="button button--outline" to="/news">
            Terug naar overzicht
          </NuxtLink>
        </div>
        <div
          v-else-if="error"
          class="news-single__state news-single__state--error"
        >
          <p>{{ error }}</p>
          <button
            class="button button--outline"
            type="button"
            @click="loadArticle"
          >
            Probeer opnieuw
          </button>
        </div>
      </div>
    </section>

    <section v-if="article" class="news-single__content container">
      <div class="news-single__body">
        <article>
          <section
            v-for="section in article.sections"
            :key="section.heading + section.content"
            class="news-section"
          >
            <h2>{{ section.heading }}</h2>
            <p>{{ section.content }}</p>
          </section>
          <!-- <p v-if="article.call_to_action" class="news-section__cta">
            {{ article.call_to_action }}
          </p> -->
        </article>
      </div>
      <aside class="news-single__sidebar">
        <div class="news-sidebar">
          <h3>Meer weten?</h3>
          <p>
            Open de motie in de stem-omgeving en bekijk het volledige dossier.
          </p>
          <NuxtLink
            class="button"
            :to="voteUrl(article.issue?.id ?? article.issue_id)"
          >
            Stem op deze motie
          </NuxtLink>
        </div>
      </aside>
    </section>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, watch } from "vue";
import { useRoute } from "vue-router";
import { fetchNewsArticle } from "~/services/news";
import type { NewsArticleSummary } from "~/types/news";

const route = useRoute();
const article = ref<NewsArticleSummary | null>(null);
const loading = ref(true);
const error = ref<string | null>(null);

const slug = computed(() => (route.params.slug as string) ?? "");

async function loadArticle() {
  if (!slug.value) return;
  loading.value = true;
  error.value = null;
  try {
    article.value = await fetchNewsArticle(slug.value);
  } catch (err: unknown) {
    error.value =
      err instanceof Error ? err.message : "Kon artikel niet laden.";
    article.value = null;
  } finally {
    loading.value = false;
  }
}

watch(
  () => slug.value,
  () => {
    void loadArticle();
  },
  { immediate: true }
);

function voteUrl(issueId: number | null | undefined) {
  if (!issueId) {
    return "/issues";
  }
  const params = new URLSearchParams({ issue_id: String(issueId) });
  return `/issues?${params.toString()}`;
}

watch(
  () => article.value,
  (value) => {
    if (!value) {
      return;
    }
    useHead({
      title: `${value.title} | Wat Denkt Nederland`,
      meta: [
        {
          name: "description",
          content: value.excerpt ?? "Lees de uitleg bij deze motie.",
        },
      ],
    });
  },
  { immediate: true }
);
</script>

<style scoped>
.page--news-single {
  background: #f5f7fb;
  min-height: 100vh;
}

.page-hero--news {
  background-image: url("/images/news.jpg");
  background-size: cover;
  background-position: center;
  color: #fff;
  text-align: center;
  min-height: min(48vh, 420px);
  display: grid;
  place-items: center;
  padding: clamp(2rem, 6vw, 4rem) 1.25rem;
  position: relative;
  isolation: isolate;
}

.page-hero--news::after {
  content: "";
  position: absolute;
  inset: 0;
  background: linear-gradient(
    180deg,
    rgba(0, 0, 0, 0.55) 0%,
    rgba(0, 0, 0, 0.3) 45%,
    rgba(0, 0, 0, 0.55) 100%
  );
  z-index: -1;
}

.page-hero__eyebrow {
  margin: 0;
  text-transform: uppercase;
  letter-spacing: 0.2rem;
  font-size: 0.85rem;
  color: rgba(255, 255, 255, 0.85);
  font-weight: 700;
}

.page-hero__title {
  margin: 0.5rem 0;
  display: flex;
  gap: 0.65rem;
  justify-content: center;
  flex-wrap: wrap;
  font-size: clamp(2.2rem, 5vw, 3.4rem);
}

.page-hero__accent {
  display: inline-block;
  padding: 0.15rem 0.45rem;
  border-radius: 12px;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.08em;
}

.page-hero__accent--blue {
  background: rgba(0, 35, 71, 0.85);
}

.page-hero__accent--red {
  background: rgba(255, 80, 3, 0.82);
}

.page-hero__headline {
  margin: 0;
  font-size: clamp(1.8rem, 4vw, 2.5rem);
  font-weight: 700;
}

.page-hero__subtitle {
  margin: 0.5rem auto 0;
  max-width: 60ch;
  color: rgba(255, 255, 255, 0.9);
  line-height: 1.6;
}

.news-single__hero-actions {
  margin-top: 1.5rem;
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 0.85rem;
}

.news-single__state {
  color: rgba(8, 18, 37, 0.7);
  font-weight: 600;
}

.news-single__state--error {
  color: var(--color-accent);
}

.news-single__content {
  display: grid;
  grid-template-columns: minmax(0, 2fr) minmax(260px, 1fr);
  gap: clamp(1.5rem, 4vw, 2.5rem);
  padding-bottom: 4rem;
  align-items: flex-start;
  margin-top: 5em;
}

.news-single__body {
  background: #fff;
  border-radius: 24px;
  padding: clamp(1.5rem, 4vw, 3rem);
  box-shadow: 0 18px 40px rgba(5, 17, 45, 0.12);
}

.news-section + .news-section {
  margin-top: 2rem;
}

.news-section h2 {
  margin: 0 0 0.75rem;
  font-size: 1.5rem;
}

.news-section p {
  margin: 0;
  line-height: 1.7;
  color: rgba(8, 18, 37, 0.8);
}

.news-section__cta {
  margin-top: 2rem;
  font-weight: 600;
  background: rgba(255, 142, 0, 0.1);
  padding: 1rem 1.25rem;
  border-radius: 16px;
  color: rgba(8, 18, 37, 0.85);
}

.news-single__sidebar {
  align-self: flex-start;
}

.news-sidebar {
  background: #fff;
  border-radius: 24px;
  padding: 1.75rem;
  box-shadow: 0 18px 40px rgba(5, 17, 45, 0.08);
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  border: 1px solid rgba(0, 35, 71, 0.08);
}

.news-sidebar h3 {
  margin: 0;
}

.news-sidebar p {
  margin: 0;
  color: rgba(8, 18, 37, 0.65);
}

@media (max-width: 960px) {
  .news-single__content {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 640px) {
  .news-single__hero-actions {
    flex-direction: column;
  }

  .news-single__hero-actions .button,
  .news-single__hero-actions .button--outline {
    width: 100%;
    text-align: center;
  }
}
</style>
