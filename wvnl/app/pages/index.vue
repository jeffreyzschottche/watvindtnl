<script setup lang="ts">
import MotionScene from "~/components/art/MotionScene.vue";
import AdSlot from "~/components/common/AdSlot.vue";
import AppIcon from "~/components/ui/AppIcon.vue";
import { faqItems } from "~/data/faq";
import { fetchIssuesWithArguments } from "~/services/issues";
import type { IssueWithArguments } from "~/types/issues";

useSeoMeta({
  title: "WatDenktNederland | Stem over moties uit de Tweede Kamer",
  description:
    "Stem op stellingen over echte Tweede Kamermoties. Bekijk hoe deelnemers en politieke partijen stemden, deel stellingen met vrienden en ontdek je politieke kompas.",
});

const { data: preview, status: previewStatus } = useAsyncData(
  "home-statement-preview",
  () => fetchIssuesWithArguments({ limit: 3 }),
  { lazy: true, default: () => [] },
);

const facts = ["Echte Kamermoties", "Gratis meedoen", "Je stem blijft privé"];

const steps = [
  {
    title: "Lees de stelling",
    text: "Kort uitgelegd, met argumenten vóór en tegen.",
  },
  {
    title: "Stem voor, tegen of neutraal",
    text: "Daarna zie je direct hoe andere deelnemers kozen.",
  },
  {
    title: "Bekijk de stemmen in de Tweede Kamer",
    text: "Vergelijk jouw keuze met hoe de partijen stemden.",
  },
];

const exampleVotes = [
  { id: "for", label: "Voor", percentage: 52 },
  { id: "against", label: "Tegen", percentage: 34 },
  { id: "neutral", label: "Neutraal", percentage: 14 },
];

const topics = [
  { name: "Klimaat", image: "/images/home/klimaat.webp" },
  { name: "Wonen", image: "/images/home/wonen.webp" },
  { name: "Landbouw", image: "/images/home/landbouw.webp" },
  { name: "Verkeer", image: "/images/home/verkeer.webp" },
];

function origin(item: IssueWithArguments) {
  return {
    motion: "Tweede Kamermotie",
    news: "In het nieuws",
    editorial: "Samenleving",
    legacy: "Stelling",
  }[item.source?.kind ?? "legacy"];
}
</script>

<template>
  <div class="home">
    <!-- 1. Hero -->
    <section class="hero" aria-labelledby="home-title">
      <div class="container hero__layout">
        <div class="hero__copy" data-reveal>
          <h1 id="home-title">Wat zou jij <span>stemmen?</span></h1>
          <p class="hero__lead">
            Echte moties uit de Tweede Kamer, in gewone taal. Kies je kant en
            zie hoe Nederland en de politiek stemden.
          </p>
          <div class="hero__actions">
            <NuxtLink to="/spel" class="button button-red hero__primary">
              Begin met stemmen <AppIcon name="arrow" :size="19" />
            </NuxtLink>
            <a href="#hoe-het-werkt" class="home-link hero__secondary">
              Hoe werkt het?
            </a>
          </div>
          <ul class="hero__facts">
            <li v-for="fact in facts" :key="fact">
              <AppIcon name="check" :size="16" /> {{ fact }}
            </li>
          </ul>
        </div>
        <div class="hero__art"><MotionScene priority /></div>
      </div>
    </section>

    <!-- 2. Photo statement -->
    <section class="container band" aria-labelledby="band-title">
      <figure class="band__figure" data-reveal>
        <img
          src="/images/home/grachten-fietsen.webp"
          alt="Fietsen tegen een brugleuning aan een Amsterdamse gracht"
          width="1800"
          height="1201"
          loading="lazy"
          decoding="async"
        />
        <figcaption>
          <h2 id="band-title">
            Elke week stemt de Kamer over moties.
            <span>Nu stem jij mee.</span>
          </h2>
        </figcaption>
      </figure>
    </section>

    <!-- 3. How it works -->
    <section
      id="hoe-het-werkt"
      class="container section how"
      aria-labelledby="how-title"
    >
      <div class="how__copy" data-reveal>
        <h2 id="how-title">Van motie naar uitslag</h2>
        <ol class="steps">
          <li v-for="(step, index) in steps" :key="step.title">
            <span class="steps__number" aria-hidden="true">
              {{ String(index + 1).padStart(2, "0") }}
            </span>
            <div>
              <h3>{{ step.title }}</h3>
              <p>{{ step.text }}</p>
            </div>
          </li>
        </ol>
        <NuxtLink to="/methodologie" class="home-link">
          Over bronnen en uitslagen <AppIcon name="arrow" :size="17" />
        </NuxtLink>
      </div>

      <figure class="example" aria-labelledby="example-title" data-reveal>
        <p class="example__source">Tweede Kamermotie</p>
        <h3 id="example-title">Verlaag de btw op groente en fruit naar 0%.</h3>

        <p class="example__label">Deelnemers</p>
        <div class="example__bar" aria-hidden="true">
          <span
            v-for="vote in exampleVotes"
            :key="vote.id"
            :class="`vote--${vote.id}`"
            :style="{ flexGrow: vote.percentage }"
          />
        </div>
        <dl class="example__scores">
          <div
            v-for="vote in exampleVotes"
            :key="vote.id"
            :class="`vote--${vote.id}`"
          >
            <dt>{{ vote.label }}</dt>
            <dd>{{ vote.percentage }}%</dd>
          </div>
        </dl>

        <div class="example__kamer">
          <p class="example__label">Tweede Kamer</p>
          <div class="example__bar example__bar--thin" aria-hidden="true">
            <span class="vote--for" :style="{ flexGrow: 87 }" />
            <span class="vote--against" :style="{ flexGrow: 63 }" />
          </div>
          <p class="example__kamer-result">
            <strong>Aangenomen</strong> · 87 voor, 63 tegen
          </p>
        </div>
        <figcaption>Fictieve voorbeelduitslag</figcaption>
      </figure>
    </section>

    <!-- 4. Statements + topics -->
    <section id="ontdek" class="discover" aria-labelledby="discover-title">
      <div class="container">
        <header class="section-header" data-reveal>
          <h2 id="discover-title">Kies een stelling</h2>
          <NuxtLink to="/stellingen" class="home-link">
            Alle stellingen <AppIcon name="arrow" :size="17" />
          </NuxtLink>
        </header>

        <ul v-if="preview.length" class="statements" data-reveal>
          <li v-for="item in preview" :key="item.id">
            <NuxtLink :to="`/stellingen/${item.slug}`" class="statement">
              <div>
                <p class="statement__source">{{ origin(item) }}</p>
                <h3>{{ item.title }}</h3>
                <p v-if="item.description" class="statement__description">
                  {{ item.description }}
                </p>
              </div>
              <span class="statement__go" aria-hidden="true">
                <AppIcon name="arrow" :size="18" />
              </span>
            </NuxtLink>
          </li>
        </ul>
        <p
          v-else-if="previewStatus === 'pending' || previewStatus === 'idle'"
          class="discover__status"
          role="status"
        >
          Stellingen laden…
        </p>
        <p v-else class="discover__status">
          De stellingen konden hier niet worden getoond.
          <NuxtLink to="/stellingen">Naar het overzicht</NuxtLink>
        </p>

        <nav class="topics" aria-labelledby="topics-title">
          <h3 id="topics-title" data-reveal>Kies een onderwerp</h3>
          <ul>
            <li v-for="topic in topics" :key="topic.name" data-reveal>
              <NuxtLink
                class="topic"
                data-lift
                :to="{
                  path: '/stellingen',
                  query: { zoeken: topic.name.toLowerCase() },
                }"
              >
                <img
                  :src="topic.image"
                  alt=""
                  width="900"
                  height="600"
                  loading="lazy"
                  decoding="async"
                />
                <span>{{ topic.name }}</span>
              </NuxtLink>
            </li>
          </ul>
        </nav>
      </div>
    </section>

    <div class="container home-ad">
      <AdSlot placement="home-tussen-secties" compact />
    </div>

    <!-- 5. Share -->
    <section class="container section share" aria-labelledby="share-title">
      <img
        class="share__art"
        src="/illustrations/dutch-neighbours-v1.jpg"
        alt=""
        width="1536"
        height="1024"
        loading="lazy"
        decoding="async"
        data-reveal
      />
      <div class="share__copy" data-reveal>
        <h2 id="share-title">Leg het voor aan je vrienden</h2>
        <p>
          Deel een stelling via WhatsApp. Iedereen leest dezelfde argumenten en
          stemt zelf.
        </p>
        <NuxtLink to="/stellingen" class="home-link">
          Zoek een stelling om te delen <AppIcon name="arrow" :size="17" />
        </NuxtLink>
      </div>
    </section>

    <!-- 6. Compass -->
    <section class="container compass" aria-labelledby="home-compass-title">
      <div class="compass__panel" data-reveal>
        <div class="compass__copy">
          <h2 id="home-compass-title">Jouw politieke kompas</h2>
          <p>
            Na genoeg stemmen zie je waar je staat op thema’s als klimaat en
            economie, en welke partijen het vaakst stemden zoals jij.
          </p>
          <NuxtLink to="/kompas" class="button compass__link">
            Bekijk het politieke kompas <AppIcon name="arrow" :size="18" />
          </NuxtLink>
          <p class="compass__price">€1,99 per nieuw persoonlijk rapport</p>
        </div>
        <div class="compass__art"><MotionScene kind="compass" /></div>
      </div>
    </section>

    <!-- 7. FAQ -->
    <section class="container section faq" aria-labelledby="home-faq-title">
      <div data-reveal>
        <h2 id="home-faq-title">Veelgestelde vragen</h2>
        <NuxtLink to="/faq" class="home-link">
          Alle vragen <AppIcon name="arrow" :size="17" />
        </NuxtLink>
      </div>
      <div class="faq-list" data-reveal>
        <details v-for="item in faqItems.slice(0, 3)" :key="item.question">
          <summary>{{ item.question }}</summary>
          <p>{{ item.answer }}</p>
        </details>
      </div>
    </section>

    <!-- 8. Closing call to action -->
    <section class="container closing" aria-labelledby="closing-title">
      <div class="closing__inner" data-reveal>
        <img
          class="closing__bg"
          src="/images/home/molens.webp"
          alt=""
          width="1800"
          height="1200"
          loading="lazy"
          decoding="async"
        />
        <h2 id="closing-title">Klaar voor je eerste stelling?</h2>
        <p>Het duurt nog geen minuut.</p>
        <NuxtLink to="/spel" class="button button-red closing__button">
          Begin met stemmen <AppIcon name="arrow" :size="19" />
        </NuxtLink>
      </div>
    </section>

  </div>
</template>

<style scoped>
.home {
  --space: clamp(4.5rem, 10vw, 8.5rem);
  --line: #e6ecf4;
  --soft: #f5f8fc;
  --radius: 22px;
  background: #fff;
}
.home h2 {
  margin: 0 0 1rem;
  font-size: clamp(2rem, 4.2vw, 3.1rem);
  line-height: 1.08;
  letter-spacing: -0.05em;
  text-wrap: balance;
}
.home p {
  text-wrap: pretty;
}
.section {
  padding-block: var(--space);
}
.home-link {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  min-height: 44px;
  font-size: 0.92rem;
  font-weight: 750;
}
.home-link svg {
  flex-shrink: 0;
  transition: transform 0.2s ease;
}
.home-link:hover {
  text-decoration: none;
}
.home-link:hover svg {
  transform: translateX(3px);
}

/* 1. Hero */
.hero__layout {
  display: grid;
  grid-template-columns: minmax(0, 1.05fr) minmax(0, 0.95fr);
  align-items: center;
  gap: clamp(2rem, 6vw, 5rem);
  padding-block: clamp(3rem, 7vw, 6rem) clamp(3rem, 6vw, 5rem);
}
.hero h1 {
  margin: 0 0 1.4rem;
  font-size: clamp(3.1rem, 7vw, 5.9rem);
  line-height: 0.96;
  letter-spacing: -0.07em;
}
.hero h1 span {
  display: block;
  color: var(--wdnl-blue);
}
.hero__lead {
  max-width: 34ch;
  margin: 0 0 2rem;
  color: var(--wdnl-muted);
  font-size: clamp(1.05rem, 1.7vw, 1.22rem);
  line-height: 1.6;
}
.hero__actions {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 0.5rem 1.4rem;
}
.hero__primary {
  min-height: 54px;
  padding-inline: 1.6rem;
  font-size: 0.95rem;
}
.hero__secondary {
  color: var(--wdnl-navy);
  text-decoration: underline;
  text-decoration-color: #b9c9df;
  text-underline-offset: 0.3rem;
}
.hero__secondary:hover {
  text-decoration: underline;
  text-decoration-color: currentColor;
}
.hero__facts {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem 1.4rem;
  margin: 2.4rem 0 0;
  padding: 0;
  list-style: none;
  color: var(--wdnl-muted);
  font-size: 0.85rem;
  font-weight: 650;
}
.hero__facts li {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
}
.hero__facts svg {
  color: var(--wdnl-blue);
}
.hero__art {
  position: relative;
  isolation: isolate;
  width: min(100%, 470px);
  justify-self: center;
}
.hero__art::before {
  content: "";
  position: absolute;
  inset: 8% 4% 2%;
  border-radius: 50%;
  background: var(--soft);
  z-index: -1;
}

/* 2. Photo band */
.band__figure {
  position: relative;
  margin: 0;
  overflow: hidden;
  border-radius: var(--radius);
  aspect-ratio: 16 / 7;
  min-height: 340px;
}
.band__figure img {
  display: block;
  width: 100%;
  height: 100%;
  object-fit: cover;
  object-position: center 60%;
}
.band__figure figcaption {
  position: absolute;
  inset: auto 0 0;
  padding: clamp(1.5rem, 5vw, 3.5rem);
  background: linear-gradient(to top, #001a3ecc, #001a3e00);
}
.band h2 {
  max-width: 18ch;
  margin: 0;
  color: #fff;
}
.band h2 span {
  display: block;
  color: #b9d6ff;
}

/* 3. How it works */
.how {
  display: grid;
  grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
  align-items: center;
  gap: clamp(2.5rem, 7vw, 6rem);
}
.steps {
  display: grid;
  gap: 1.8rem;
  margin: 2.2rem 0 1.6rem;
  padding: 0;
  list-style: none;
}
.steps li {
  display: grid;
  grid-template-columns: 2.6rem minmax(0, 1fr);
  gap: 0.75rem;
}
.steps__number {
  padding-top: 0.15rem;
  color: var(--wdnl-blue);
  font-size: 0.85rem;
  font-weight: 800;
  font-variant-numeric: tabular-nums;
}
.steps h3 {
  margin: 0 0 0.25rem;
  font-size: 1.1rem;
  letter-spacing: -0.025em;
}
.steps p {
  margin: 0;
  color: var(--wdnl-muted);
  font-size: 0.95rem;
  line-height: 1.6;
}
.example {
  margin: 0;
  padding: clamp(1.5rem, 4vw, 2.5rem);
  border: 1px solid var(--line);
  border-radius: var(--radius);
  background: #fff;
  box-shadow: 0 24px 60px -32px #0a2a5c40;
}
.example__source {
  margin: 0 0 0.5rem;
  color: var(--wdnl-blue);
  font-size: 0.78rem;
  font-weight: 750;
}
.example h3 {
  margin: 0 0 1.8rem;
  font-size: clamp(1.25rem, 2.2vw, 1.5rem);
  line-height: 1.3;
  letter-spacing: -0.03em;
}
.example__label {
  margin: 0 0 0.55rem;
  color: var(--wdnl-muted);
  font-size: 0.8rem;
  font-weight: 700;
}
.example__bar {
  display: flex;
  gap: 3px;
  height: 12px;
  overflow: hidden;
  border-radius: 99px;
}
.example__bar--thin {
  height: 8px;
}
.example__bar span {
  flex-basis: 0;
  background: var(--vote-color);
}
.vote--for {
  --vote-color: #0057b8;
}
.vote--against {
  --vote-color: #c5002b;
}
.vote--neutral {
  --vote-color: #8593a6;
}
.example__scores {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 0.5rem;
  margin: 0.9rem 0 0;
}
.example__scores div {
  display: flex;
  flex-direction: column-reverse;
  color: var(--vote-color);
}
.example__scores dt {
  font-size: 0.8rem;
  font-weight: 700;
}
.example__scores dd {
  margin: 0;
  font-size: clamp(1.5rem, 3.4vw, 2.1rem);
  font-weight: 800;
  line-height: 1.15;
  letter-spacing: -0.03em;
  font-variant-numeric: tabular-nums;
}
.example__kamer {
  margin-top: 1.6rem;
  padding-top: 1.4rem;
  border-top: 1px solid var(--line);
}
.example__kamer-result {
  margin: 0.6rem 0 0;
  color: var(--wdnl-muted);
  font-size: 0.86rem;
}
.example__kamer-result strong {
  color: var(--wdnl-navy);
}
.example figcaption {
  margin-top: 1.4rem;
  color: #8593a6;
  font-size: 0.75rem;
}

/* 4. Statements + topics */
.discover {
  padding-block: var(--space);
  background: var(--soft);
}
.section-header {
  display: flex;
  flex-wrap: wrap;
  align-items: baseline;
  justify-content: space-between;
  gap: 0.25rem 1rem;
  margin-bottom: 1.6rem;
}
.section-header h2 {
  margin: 0;
}
.statements,
.topics ul {
  margin: 0;
  padding: 0;
  list-style: none;
}
.statements {
  display: grid;
  gap: 0.75rem;
}
.statement {
  display: grid;
  grid-template-columns: minmax(0, 1fr) auto;
  align-items: center;
  gap: 1.25rem;
  padding: 1.35rem clamp(1.1rem, 3vw, 1.75rem);
  border: 1px solid var(--line);
  border-radius: 16px;
  background: #fff;
  color: var(--wdnl-navy);
  transition:
    border-color 0.2s,
    box-shadow 0.2s;
}
.statement:hover {
  border-color: #c4d5ec;
  box-shadow: 0 14px 34px -22px #0a2a5c55;
  text-decoration: none;
}
.statement__source {
  margin: 0 0 0.3rem;
  color: var(--wdnl-blue);
  font-size: 0.76rem;
  font-weight: 750;
}
.statement h3 {
  margin: 0;
  font-size: clamp(1.02rem, 2vw, 1.2rem);
  line-height: 1.38;
  overflow-wrap: anywhere;
}
.statement__description {
  display: -webkit-box;
  margin: 0.3rem 0 0;
  overflow: hidden;
  color: var(--wdnl-muted);
  font-size: 0.88rem;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 1;
}
.statement__go {
  display: grid;
  place-items: center;
  width: 42px;
  height: 42px;
  border-radius: 50%;
  background: var(--soft);
  color: var(--wdnl-blue);
  transition:
    background 0.2s,
    color 0.2s;
}
.statement:hover .statement__go {
  background: var(--wdnl-blue);
  color: #fff;
}
.discover__status {
  padding: 1.3rem 1.5rem;
  border: 1px solid var(--line);
  border-radius: 16px;
  background: #fff;
}
.topics {
  margin-top: clamp(3.5rem, 7vw, 5rem);
}
.topics h3 {
  margin: 0 0 1.2rem;
  font-size: 1.2rem;
  letter-spacing: -0.03em;
}
.topics ul {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: clamp(0.75rem, 2vw, 1.25rem);
}
.topic {
  position: relative;
  display: block;
  overflow: hidden;
  aspect-ratio: 4 / 5;
  border-radius: 18px;
  background: #dde6f2;
  color: #fff;
}
.topic::after {
  content: "";
  position: absolute;
  inset: 45% 0 0;
  background: linear-gradient(to top, #001a3ebf, #001a3e00);
}
.topic img {
  display: block;
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.6s ease;
}
.topic:hover {
  text-decoration: none;
}
.topic:hover img {
  transform: scale(1.04);
}
.topic span {
  position: absolute;
  left: 1.1rem;
  bottom: 1rem;
  z-index: 1;
  font-size: 1.1rem;
  font-weight: 800;
  letter-spacing: -0.02em;
}

/* 5. Share */
.share {
  display: grid;
  grid-template-columns: minmax(0, 1.1fr) minmax(0, 0.9fr);
  align-items: center;
  gap: clamp(2rem, 7vw, 6rem);
}
.share__art {
  display: block;
  width: 100%;
  height: auto;
  border-radius: var(--radius);
}
.share__copy p {
  max-width: 36ch;
  margin: 0 0 1rem;
  color: var(--wdnl-muted);
  font-size: 1.05rem;
  line-height: 1.65;
}

/* 6. Compass */
.compass__panel {
  display: grid;
  grid-template-columns: minmax(0, 1.2fr) minmax(0, 0.8fr);
  align-items: center;
  gap: clamp(1rem, 4vw, 3rem);
  padding: clamp(2rem, 6vw, 4.5rem);
  border-radius: 28px;
  background: var(--wdnl-navy);
  color: #fff;
}
.compass h2 {
  color: #fff;
}
.compass__copy p {
  max-width: 42ch;
  margin: 0 0 1.8rem;
  color: #c9daf0;
  font-size: 1.02rem;
  line-height: 1.65;
}
.compass__link {
  min-height: 52px;
  background: #fff;
  color: var(--wdnl-navy);
}
.compass__link:hover {
  background: #e8f0fc;
  color: var(--wdnl-blue);
}
.compass__link:focus-visible {
  outline-color: #b2d9ff;
}
.compass__copy .compass__price {
  margin: 0.9rem 0 0;
  color: #9fb6d3;
  font-size: 0.8rem;
}
.compass__art {
  width: min(100%, 300px);
  justify-self: center;
}

/* 7. FAQ */
.faq {
  display: grid;
  grid-template-columns: minmax(0, 0.8fr) minmax(0, 1.2fr);
  gap: clamp(1rem, 6vw, 5rem);
}
.faq .faq-list summary {
  min-height: 44px;
  font-size: 1rem;
}
.faq .faq-list details {
  padding-block: 0.7rem;
}

/* 8. Closing */
.closing {
  padding-bottom: var(--space);
}
.closing__inner {
  position: relative;
  isolation: isolate;
  overflow: hidden;
  padding: clamp(5rem, 12vw, 9rem) 1.5rem;
  border-radius: 28px;
  background: var(--wdnl-navy);
  text-align: center;
}
.closing__inner::before {
  content: "";
  position: absolute;
  inset: 0;
  z-index: -1;
  background: linear-gradient(#001a3e66, #001a3ecc);
}
.closing__bg {
  position: absolute;
  inset: 0;
  z-index: -2;
  width: 100%;
  height: 100%;
  object-fit: cover;
  object-position: center 40%;
}
.closing h2 {
  color: #fff;
}
.closing__inner p {
  margin: 0 0 2rem;
  color: #dbe7f7;
  font-size: 1.1rem;
}
.closing__button {
  min-height: 54px;
  padding-inline: 1.6rem;
  font-size: 0.95rem;
}
.home-ad {
  max-width: 550px;
  padding-top: var(--space);
}
.home-ad:empty {
  display: none;
}
.home-ad:not(:empty) + .share {
  padding-top: clamp(3rem, 6vw, 5rem);
}

@media (max-width: 900px) {
  .topics ul {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
  .topic {
    aspect-ratio: 4 / 3;
  }
}
@media (max-width: 770px) {
  .hero__layout,
  .how,
  .share,
  .compass__panel,
  .faq {
    grid-template-columns: 1fr;
  }
  .hero__layout {
    gap: 1rem;
    padding-block: 2.5rem 2rem;
  }
  .hero__art {
    width: min(72%, 290px);
  }
  .hero__facts {
    margin-top: 1.8rem;
  }
  .band__figure {
    aspect-ratio: 4 / 5;
    min-height: 0;
  }
  .band__figure img {
    object-position: 35% center;
  }
  .compass__art {
    width: min(60%, 200px);
    grid-row: 1;
    justify-self: start;
  }
  .share__art {
    width: min(100%, 440px);
  }
}
@media (max-width: 480px) {
  .topics ul {
    gap: 0.6rem;
  }
  .topic span {
    left: 0.85rem;
    bottom: 0.75rem;
    font-size: 1rem;
  }
  .statement__go {
    width: 36px;
    height: 36px;
  }
}
@media (prefers-reduced-motion: reduce) {
  .topic img,
  .home-link svg {
    transition: none;
  }
}
</style>
