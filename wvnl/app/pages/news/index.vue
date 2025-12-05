<template>
  <div class="page page--news">
    <section class="page-hero page-hero--news">
      <div class="container narrow">
        <p class="page-hero__eyebrow">Wat Denkt Nederland</p>
        <h1 class="page-hero__title">
          <span class="page-hero__accent page-hero__accent--blue">Nieuws</span>
          <span class="page-hero__accent page-hero__accent--red"
            >&amp; moties</span
          >
        </h1>
        <p class="page-hero__subtitle">
          Voor elke motie schrijven we een artikel in heldere taal, zodat je
          binnen enkele minuten begrijpt waar je over stemt.
        </p>
      </div>
    </section>

    <section class="news-board container">
      <header class="news-board__header">
        <div>
          <p class="news-board__eyebrow">Alle moties</p>
          <h2>Maar dan in simpelere taal</h2>
        </div>
        <div class="news-board__search">
          <label class="sr-only" for="news-search">Zoek naar moties</label>
          <input
            id="news-search"
            v-model="searchQuery"
            type="search"
            placeholder="Zoek op titel of onderwerp"
          />
          <svg
            aria-hidden="true"
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="m21 21-4.35-4.35m0 0A7.5 7.5 0 1 0 6.65 6.65a7.5 7.5 0 0 0 9.9 9.9Z"
            />
          </svg>
        </div>
      </header>

      <div v-if="loading" class="news-board__state">
        Moties worden geladen...
      </div>
      <div v-else-if="error" class="news-board__state news-board__state--error">
        <p>{{ error }}</p>
        <button
          class="button button--outline"
          type="button"
          @click="loadArticles"
        >
          Probeer opnieuw
        </button>
      </div>
      <div v-else-if="filteredArticles.length === 0" class="news-board__state">
        Geen moties gevonden met "{{ searchQuery }}".
      </div>

      <ul v-else class="news-grid">
        <li v-for="article in filteredArticles" :key="article.id">
          <article class="news-card">
            <p class="news-card__issue" v-if="article.issue">
              {{ article.issue.title }}
            </p>
            <h3 class="news-card__title">
              <NuxtLink :to="`/news/${article.slug}`">{{
                article.title
              }}</NuxtLink>
            </h3>
            <p v-if="article.excerpt" class="news-card__excerpt">
              {{ previewExcerpt(article.excerpt) }}
            </p>
            <footer class="news-card__footer">
              <NuxtLink class="button" :to="`/news/${article.slug}`">
                Lees uitleg
              </NuxtLink>
              <NuxtLink
                class="button button--outline"
                :to="voteUrl(article.issue?.id ?? article.issue_id)"
              >
                Stem op deze motie
              </NuxtLink>
            </footer>
          </article>
        </li>
      </ul>
    </section>
  </div>
</template>

<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from "vue";
import { fetchNewsArticles } from "~/services/news";
import type { NewsArticleListItem } from "~/types/news";
import { translateErrorMessage } from "~/utils/translateErrorMessage";

const articles = ref<NewsArticleListItem[]>([]);
const loading = ref(true);
const error = ref<string | null>(null);
const searchQuery = ref("");
const debouncedSearch = ref("");
let searchTimer: ReturnType<typeof setTimeout> | null = null;

const filteredArticles = computed(() => {
  if (!debouncedSearch.value.trim()) {
    return articles.value;
  }
  const query = debouncedSearch.value.toLowerCase();
  return articles.value.filter((article) => {
    const haystack = [
      article.title,
      article.subtitle ?? "",
      article.excerpt ?? "",
      article.issue?.title ?? "",
    ]
      .join(" ")
      .toLowerCase();
    return haystack.includes(query);
  });
});

async function loadArticles() {
  loading.value = true;
  error.value = null;
  try {
    const response = await fetchNewsArticles();
    articles.value = response;
  } catch (err: unknown) {
    error.value = translateErrorMessage(err, {
      fallback: "Kon nieuwsartikelen niet laden.",
    });
  } finally {
    loading.value = false;
  }
}

function voteUrl(issueId: number | null | undefined) {
  if (!issueId) {
    return "/issues";
  }
  const params = new URLSearchParams({ issue_id: String(issueId) });
  return `/issues?${params.toString()}`;
}

function previewExcerpt(text: string | null | undefined, maxLength = 160) {
  if (!text) {
    return "";
  }
  const clean = text.replace(/\s+/g, " ").trim();
  if (clean.length <= maxLength) {
    return clean;
  }
  const sliced = clean.slice(0, maxLength);
  const lastSpace = sliced.lastIndexOf(" ");
  const trimmed = (lastSpace > 0 ? sliced.slice(0, lastSpace) : sliced).trim();
  return `${trimmed}...`;
}

watch(
  () => searchQuery.value,
  (value) => {
    if (searchTimer) {
      clearTimeout(searchTimer);
    }
    searchTimer = setTimeout(() => {
      debouncedSearch.value = value;
    }, 250);
  }
);

onMounted(() => {
  void loadArticles();
});

onBeforeUnmount(() => {
  if (searchTimer) {
    clearTimeout(searchTimer);
  }
});

const title = "Nieuws & moties | Wat Denkt Nederland";
const description =
  "Lees toegankelijke uitleg bij alle moties en ga direct door naar het stemmen.";

useHead({
  title,
  meta: [
    {
      name: "description",
      content: description,
    },
  ],
});
</script>

<style scoped>
.page--news {
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
  padding: 0 1.25rem;
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

.page-hero__subtitle {
  margin: 0;
  max-width: 60ch;
  margin-inline: auto;
  font-size: 1.1rem;
  color: rgba(255, 255, 255, 0.9);
  font-weight: 500;
}

.news-board {
  padding: clamp(2rem, 5vw, 4rem) clamp(1.25rem, 4vw, 3rem) 4rem;
}

.news-board__header {
  display: flex;
  flex-wrap: wrap;
  justify-content: space-between;
  align-items: center;
  gap: 1.5rem;
  margin-bottom: 2.5rem;
}

.news-board__eyebrow {
  margin: 0;
  font-size: 0.9rem;
  letter-spacing: 0.1rem;
  text-transform: uppercase;
  color: rgba(0, 35, 71, 0.7);
}

.news-board__header h2 {
  margin: 0.35rem 0 0;
  font-size: clamp(1.9rem, 4vw, 2.4rem);
}

.news-board__search {
  position: relative;
  width: min(320px, 100%);
}

.news-board__search input {
  width: 100%;
  padding: 0.65rem 1rem 0.65rem 2.75rem;
  border-radius: 999px;
  border: 1px solid rgba(0, 35, 71, 0.25);
  background: #fff;
  font-size: 0.95rem;
  color: var(--color-midnight);
  box-shadow: inset 0 1px 4px rgba(4, 17, 36, 0.05);
}

.news-board__search svg {
  position: absolute;
  top: 50%;
  left: 1rem;
  transform: translateY(-50%);
  width: 1rem;
  height: 1rem;
  color: rgba(8, 18, 37, 0.45);
}

.news-board__state {
  text-align: center;
  font-weight: 600;
  color: rgba(8, 18, 37, 0.7);
  padding: 2rem 1rem;
}

.news-board__state--error {
  color: var(--color-accent);
}

.news-grid {
  margin: 0;
  padding: 0;
  list-style: none;
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 2.5rem;
}

.news-grid li {
  height: 100%;
  display: flex;
}

.news-card {
  background: #fff;
  border-radius: 20px;
  padding: 1.25rem;
  display: flex;
  flex-direction: column;
  gap: 0.65rem;
  box-shadow: 0 18px 40px rgba(5, 17, 45, 0.08);
  border: 1px solid rgba(0, 35, 71, 0.08);
}

.news-card__issue {
  margin: 0;
  text-transform: uppercase;
  letter-spacing: 0.1rem;
  font-size: 0.75rem;
  color: rgba(0, 35, 71, 0.7);
}

.news-card__title {
  margin: 0;
  font-size: 1.35rem;
}

.news-card__title a {
  color: inherit;
}

.news-card__excerpt {
  margin: 0;
  color: rgba(8, 18, 37, 0.75);
  line-height: 1.5;
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.news-card__footer {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
  margin-top: auto;
}

@media (max-width: 640px) {
  .news-hero {
    grid-template-columns: 1fr;
  }

  .news-board__header {
    flex-direction: column;
    align-items: flex-start;
  }

  .news-card__footer {
    flex-direction: column;
  }

  .news-card__footer .button,
  .news-card__footer .button--outline {
    width: 100%;
    text-align: center;
  }
}
</style>
