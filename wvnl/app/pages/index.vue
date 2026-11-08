<script setup lang="ts">
import AppIcon from "~/components/ui/AppIcon.vue";
import MotionScene from "~/components/art/MotionScene.vue";
import { faqItems } from "~/data/faq";
import { fetchIssuesWithArguments } from "~/services/issues";
import type { IssueWithArguments } from "~/types/issues";
useSeoMeta({
  title: "WatDenktNederland — Nederland heeft een mening. Jij ook.",
  description:
    "Stem op politieke en maatschappelijke stellingen, ontdek beide kanten en bekijk hoe andere WDNL-deelnemers denken. Eén vraag. Twee kanten.",
});
const preview = ref<IssueWithArguments[]>([]);
onMounted(async () => {
  try {
    preview.value = await fetchIssuesWithArguments({ limit: 3 });
  } catch {
    /* Exploration and navigation remain available offline. */
  }
});
const topics = [
  { name: "Wonen", icon: "home" },
  { name: "Zorg", icon: "shield" },
  { name: "Klimaat", icon: "compass" },
  { name: "Onderwijs", icon: "grid" },
];
const steps = [
  {
    number: "01",
    icon: "chat",
    title: "Lees. Even stilstaan.",
    text: "Eén duidelijke stelling. Met context en argumenten van beide kanten.",
    accent: "blue",
  },
  {
    number: "02",
    icon: "check",
    title: "Kies. Op jouw gevoel.",
    text: "Tegen of voor. Geen goedgekeurd antwoord, wel jouw eigen afweging.",
    accent: "red",
  },
  {
    number: "03",
    icon: "compass",
    title: "Ontdek. Kijk verder.",
    text: "Bekijk de uitslag van deelnemers. En bepaal zelf wanneer je doorgaat.",
    accent: "navy",
  },
];
function origin(item: IssueWithArguments) {
  return {
    news: "In het nieuws",
    editorial: "Samenleving",
    motion: "Politieke motie",
    legacy: "Stelling",
  }[item.source?.kind ?? "legacy"];
}
</script>

<template>
  <div class="home-v3">
    <section class="hero">
      <div class="hero__grid-paper" aria-hidden="true" />
      <div class="container hero__layout">
        <div class="hero__copy" data-reveal>
          <p class="hero__eyebrow">
            <span class="hero__eyebrow-dot" /> Eén vraag. Twee kanten.
          </p>
          <h1>Nederland heeft<br />een mening.<br /><span>Jij ook.</span></h1>
          <p class="hero__lead">
            Over wat er speelt. Wat je raakt.<br />En hoe jij de wereld een
            beetje anders ziet.
          </p>
          <div class="hero__actions">
            <NuxtLink to="/spel" class="button button-red"
              >Begin met stemmen <AppIcon name="arrow" :size="19" /></NuxtLink
            ><NuxtLink to="/stellingen" class="hero__secondary"
              >Eerst even rondkijken <span aria-hidden="true">↗</span></NuxtLink
            >
          </div>
          <div class="hero__reassurance">
            <span><AppIcon name="check" :size="15" /> Gratis meedoen</span
            ><span><AppIcon name="check" :size="15" /> Op jouw tempo</span>
          </div>
        </div>
        <div class="hero__art" data-reveal><MotionScene priority /></div>
      </div>
      <div class="container hero__bottom">
        <a href="#ontdek" class="hero__scroll"
          ><span aria-hidden="true">↓</span> Er valt meer te ontdekken</a
        ><span>Een open blik begint bij nieuwsgierigheid.</span>
      </div>
    </section>

    <section class="promise-strip" aria-label="Wat je kunt verwachten">
      <div class="container promise-strip__grid">
        <p>
          <span class="promise-strip__icon"
            ><AppIcon name="chat" :size="21" /></span
          ><span
            ><strong>Elke mening begint met een vraag</strong
            ><small
              >Eén stelling tegelijk. Alle ruimte om na te denken.</small
            ></span
          >
        </p>
        <p>
          <span class="promise-strip__icon promise-strip__icon--red"
            ><AppIcon name="grid" :size="21" /></span
          ><span
            ><strong>Bekijk ook de andere kant</strong
            ><small
              >Argumenten vóór én tegen. Jij maakt de afweging.</small
            ></span
          >
        </p>
        <p>
          <span class="promise-strip__icon promise-strip__icon--navy"
            ><AppIcon name="shield" :size="21" /></span
          ><span
            ><strong>Jouw keuze blijft persoonlijk</strong
            ><small
              >Andere deelnemers zien alleen de gezamenlijke uitslag.</small
            ></span
          >
        </p>
      </div>
    </section>

    <section id="ontdek" class="container discover-section" data-reveal>
      <header class="section-heading">
        <div>
          <p class="eyebrow">Er speelt genoeg</p>
          <h2>Waar denk jij over na<span class="red-dot">?</span></h2>
          <p>Een andere blik op onderwerpen die ons allemaal raken.</p>
        </div>
        <NuxtLink to="/stellingen" class="text-link"
          >Alle stellingen <AppIcon name="arrow" :size="18"
        /></NuxtLink>
      </header>
      <nav class="topic-pills" aria-label="Ontdek een onderwerp">
        <NuxtLink
          v-for="topic in topics"
          :key="topic.name"
          :to="{
            path: '/stellingen',
            query: { zoeken: topic.name.toLowerCase() },
          }"
          ><AppIcon :name="topic.icon" :size="16" />{{ topic.name
          }}<span aria-hidden="true">↗</span></NuxtLink
        >
      </nav>
      <div v-if="preview.length" class="statement-grid">
        <NuxtLink
          v-for="(item, index) in preview"
          :key="item.id"
          :to="`/stellingen/${item.slug}`"
          class="statement-tile"
          :class="`statement-tile--${index}`"
          data-lift
          ><div class="statement-tile__top">
            <span class="statement-tile__category">{{ origin(item) }}</span
            ><span class="statement-tile__symbol" aria-hidden="true"
              ><AppIcon :name="['chat', 'grid', 'compass'][index]!" :size="29"
            /></span>
          </div>
          <h3>{{ item.title }}</h3>
          <p>{{ item.description }}</p>
          <span class="statement-tile__bottom"
            >Wat vind jij?<span class="statement-tile__arrow"
              ><AppIcon name="arrow" :size="19" /></span></span
        ></NuxtLink>
      </div>
      <div v-else class="discover-fallback">
        <AppIcon name="chat" :size="28" />
        <p>Er is altijd ruimte voor een ander perspectief.</p>
        <NuxtLink to="/stellingen" class="button button-secondary"
          >Ontdek de stellingen →</NuxtLink
        >
      </div>
    </section>

    <section class="how-section">
      <div class="container">
        <header class="section-heading section-heading--center" data-reveal>
          <p class="eyebrow">Minder ingewikkeld. Meer jij.</p>
          <h2>Eén vraag. Twee kanten.<br /><span>Jouw mening.</span></h2>
          <p>Je hoeft geen expert te zijn om mee te praten.</p>
        </header>
        <div class="how-grid">
          <article
            v-for="step in steps"
            :key="step.number"
            class="how-card"
            :class="`how-card--${step.accent}`"
            data-reveal
            data-lift
          >
            <div class="how-card__number">{{ step.number }}</div>
            <div class="how-card__visual" aria-hidden="true">
              <span class="how-card__back" /><span class="how-card__front"
                ><AppIcon :name="step.icon" :size="35" /></span
              ><span class="how-card__spark">✦</span>
            </div>
            <h3>{{ step.title }}</h3>
            <p>{{ step.text }}</p>
          </article>
        </div>
      </div>
    </section>

    <section class="conversation-section">
      <div class="container conversation-grid">
        <div class="conversation-art" data-reveal>
          <div class="conversation-art__backdrop" aria-hidden="true" />
          <img
            src="/illustrations/dutch-neighbours-v1.jpg"
            alt=""
            width="1536"
            height="1024"
            loading="lazy"
          /><span class="conversation-art__caption"
            ><AppIcon name="chat" :size="17" /> Er is meer dan één kant aan een
            verhaal.</span
          >
        </div>
        <div class="conversation-copy" data-reveal>
          <p class="eyebrow">Van het Binnenhof tot jouw buurt</p>
          <h2>
            Grote vragen.<br />Gewoon aan de<br /><span>keukentafel.</span>
          </h2>
          <p>
            Wonen, zorg, het nieuws of een vraag uit het dagelijks leven. Wat er
            in Nederland gebeurt, raakt ons allemaal.
          </p>
          <p class="conversation-copy__sources">
            Onze redactie maakt stellingen bij officiële moties, actuele
            gebeurtenissen en maatschappelijke vragen. Bij een stelling vind je
            de herkomst en beschikbare bron.
          </p>
          <NuxtLink to="/methodologie" class="text-link"
            >Zo maken we de stellingen <AppIcon name="arrow" :size="18"
          /></NuxtLink>
        </div>
      </div>
    </section>

    <section class="container compass-section">
      <div class="compass-feature" data-reveal>
        <div class="compass-feature__copy">
          <span class="compass-feature__badge">Voor de nieuwsgierige jij</span>
          <h2>
            Jouw keuzes.<br />Jouw verhaal.<br /><span>Jouw kompas.</span>
          </h2>
          <p>
            Losse antwoorden vertellen samen meer. Ontdek straks welke waarden
            terugkomen in jouw standpunten.
          </p>
          <NuxtLink to="/kompas" class="button button-white"
            >Ontdek het kompas <AppIcon name="arrow" :size="18" /></NuxtLink
          ><small>Het vernieuwde rapport is in ontwikkeling.</small>
        </div>
        <div class="compass-feature__art"><MotionScene kind="compass" /></div>
      </div>
    </section>

    <section class="container home-faq">
      <div data-reveal>
        <p class="eyebrow">Even helder</p>
        <h2>Goede vraag,<br /><span>eigenlijk.</span></h2>
        <p>Dit wil je weten voordat je begint.</p>
        <NuxtLink to="/faq" class="text-link"
          >Alle vragen en antwoorden <AppIcon name="arrow" :size="18"
        /></NuxtLink>
      </div>
      <div class="faq-list" data-reveal>
        <details v-for="item in faqItems.slice(0, 3)" :key="item.question">
          <summary>{{ item.question }}</summary>
          <p>{{ item.answer }}</p>
        </details>
      </div>
    </section>

    <section class="container final-section">
      <div class="final-card" data-reveal>
        <span class="final-card__spark" aria-hidden="true">✦</span>
        <div>
          <p class="eyebrow">Er is ruimte voor jouw perspectief</p>
          <h2>En, wat denk jij?</h2>
          <p>Een kleine keuze. Een nieuw inzicht.</p>
        </div>
        <NuxtLink to="/spel" class="button button-red"
          >Laat je mening horen <AppIcon name="arrow" :size="19"
        /></NuxtLink>
      </div>
    </section>
  </div>
</template>

<style scoped>
.home-v3 {
  background: #fff;
  overflow: clip;
}
.hero {
  position: relative;
  isolation: isolate;
  background: linear-gradient(115deg, #fcfdff 15%, #f0f6ff 75%, #edf2ff);
  padding-top: 26px;
}
.hero__grid-paper {
  position: absolute;
  inset: 0;
  pointer-events: none;
  background-image: radial-gradient(#7992ba35 0.8px, transparent 0.8px);
  background-size: 23px 23px;
  mask-image: linear-gradient(90deg, transparent 42%, #000 85%);
  z-index: -1;
}
.hero__layout {
  display: grid;
  grid-template-columns: 1.05fr 1fr;
  gap: 3rem;
  align-items: center;
  padding-block: 28px 20px;
}
.hero__copy {
  padding-bottom: 22px;
  position: relative;
  z-index: 2;
}
.hero__eyebrow {
  display: inline-flex;
  align-items: center;
  gap: 0.6rem;
  font-size: 0.69rem;
  font-weight: 750;
  letter-spacing: 0.035em;
  color: #315a8e;
  background: #e6effc;
  border: 1px solid #dae7fa;
  border-radius: 30px;
  padding: 0.45rem 0.85rem;
  margin-bottom: 1.75rem;
}
.hero__eyebrow-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: #e1062e;
  box-shadow: 0 0 0 4px #e1062e0b;
}
.hero h1 {
  color: #001f4d;
  font-size: clamp(3rem, 5.1vw, 4.5rem);
  line-height: 1.055;
  font-weight: 800;
  letter-spacing: -0.067em;
  margin: 0 0 1.5rem;
}
.hero h1 > span {
  color: #0057b8;
  position: relative;
  display: inline-block;
}
.hero h1 > span::after {
  content: "";
  position: absolute;
  left: 2%;
  bottom: -0.1em;
  width: 96%;
  height: 0.1em;
  border-bottom: 4px solid #e1062e;
  border-radius: 50%;
  transform: rotate(-3deg);
}
.hero__lead {
  font-size: 1rem;
  line-height: 1.85;
  color: #5a6c84;
  margin: 1.9rem 0 1.8rem;
}
.hero__actions {
  display: flex;
  gap: 1.4rem;
  align-items: center;
  flex-wrap: wrap;
}
.hero__actions .button {
  min-height: 54px;
  border-radius: 13px;
  padding-inline: 1.45rem;
  font-size: 0.85rem;
  box-shadow:
    0 6px 0 #a60021,
    0 13px 24px #e1062e18;
}
.hero__actions .button:hover {
  box-shadow:
    0 4px 0 #a60021,
    0 10px 23px #e1062e25;
  transform: translateY(2px);
}
.hero__secondary {
  color: #001f4d;
  font-size: 0.76rem;
  display: flex;
  align-items: center;
  gap: 0.65rem;
  min-height: 44px;
}
.hero__secondary span {
  font-size: 1.15rem;
}
.hero__reassurance {
  display: flex;
  gap: 1.2rem;
  margin-top: 1.65rem;
  font-size: 0.68rem;
  color: #61748d;
}
.hero__reassurance > span {
  display: flex;
  align-items: center;
  gap: 0.35rem;
}
.hero__reassurance svg {
  color: #0057b8;
}
.hero__art {
  width: 100%;
  max-width: 515px;
  justify-self: end;
}
.hero__bottom {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
  padding-block: 0 25px;
  color: #5f718a;
  font-size: 0.63rem;
}
.hero__scroll {
  color: #5f7490;
  font-size: 0.65rem;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 0.65rem;
  min-height: 40px;
}
.hero__scroll > span {
  display: grid;
  place-items: center;
  border: 1px solid #ccd9eb;
  width: 27px;
  height: 32px;
  border-radius: 20px;
}
.promise-strip {
  border-block: 1px solid #e6ecf6;
  background: white;
}
.promise-strip__grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 2rem;
  padding-block: 28px;
}
.promise-strip__grid > p {
  display: flex;
  align-items: center;
  gap: 0.8rem;
  margin: 0;
}
.promise-strip__grid strong {
  display: block;
  font-size: 0.7rem;
  font-weight: 800;
  margin-bottom: 0.2rem;
}
.promise-strip__grid small {
  display: block;
  font-size: 0.61rem;
  color: #5f718a;
  line-height: 1.7;
}
.promise-strip__icon {
  display: grid;
  place-items: center;
  flex-shrink: 0;
  width: 42px;
  height: 45px;
  border-radius: 13px;
  color: #0057b8;
  background: #edf3ff;
  border: 1px solid #e1eafa;
  box-shadow: 0 3px 0 #dce7f7;
  transform: rotate(-6deg);
}
.promise-strip__icon--red {
  color: #c60028;
  background: #fff0f3;
  border-color: #ffe2e8;
  box-shadow: 0 3px 0 #f8dce3;
  transform: rotate(5deg);
}
.promise-strip__icon--navy {
  color: #001f4d;
  background: #f1f4f9;
}
.discover-section {
  padding-block: 86px 80px;
}
.section-heading {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  gap: 1.5rem;
  margin-bottom: 1.8rem;
}
.section-heading .eyebrow {
  margin-bottom: 0.8rem;
}
.section-heading h2 {
  font-size: clamp(1.85rem, 3.4vw, 2.9rem);
  line-height: 1.13;
  letter-spacing: -0.055em;
  margin: 0 0 0.85rem;
  font-weight: 800;
}
.section-heading p:not(.eyebrow) {
  font-size: 0.85rem;
  color: #687b92;
  margin: 0;
}
.red-dot {
  color: #e1062e;
}
.text-link {
  display: inline-flex;
  align-items: center;
  gap: 0.7rem;
  font-size: 0.76rem;
  min-height: 42px;
  color: #0057b8;
}
.topic-pills {
  display: flex;
  gap: 0.6rem;
  flex-wrap: wrap;
  margin-bottom: 1.7rem;
}
.topic-pills > a {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.55rem 0.8rem;
  border: 1px solid #dce6f3;
  background: #f7f9fd;
  border-radius: 30px;
  font-size: 0.73rem;
  color: #4e6785;
  text-decoration: none;
  transition:
    background 0.2s,
    border-color 0.2s;
}
.topic-pills > a > span {
  color: #607188;
  margin-left: 0.5rem;
}
.topic-pills > a:hover {
  background: #e9f1ff;
  border-color: #94b9e5;
}
.statement-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 21px;
}
.statement-tile {
  display: flex;
  flex-direction: column;
  position: relative;
  min-width: 0;
  padding: 1.6rem;
  border: 1px solid #dce6f4;
  border-radius: 22px;
  background: linear-gradient(145deg, #f3f7ff, #fff 75%);
  box-shadow:
    0 5px 0 #e6edf7,
    0 16px 30px #14396807;
  color: #001f4d;
  text-decoration: none;
}
.statement-tile--1 {
  border-color: #f0e0e5;
  background: linear-gradient(145deg, #fff3f5, #fff 75%);
  box-shadow:
    0 5px 0 #f5e8ed,
    0 16px 30px #14396807;
}
.statement-tile--2 {
  border-color: #dce9e8;
  background: linear-gradient(145deg, #f0f8f7, #fff 75%);
  box-shadow:
    0 5px 0 #e3efed,
    0 16px 30px #14396807;
}
.statement-tile:hover {
  text-decoration: none;
  border-color: #a4bbdb;
}
.statement-tile__top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.5rem;
}
.statement-tile__category {
  color: #517093;
  font-size: 0.64rem;
  letter-spacing: 0.03em;
  font-weight: 750;
}
.statement-tile__symbol {
  display: grid;
  place-items: center;
  width: 47px;
  height: 47px;
  color: #0057b8;
  border-radius: 16px;
  background: linear-gradient(145deg, #fff, #e4edff);
  border: 1px solid #fff;
  box-shadow: 3px 4px 0 #d7e5fa;
  transform: rotate(9deg);
}
.statement-tile--1 .statement-tile__symbol {
  color: #c60028;
  background: linear-gradient(145deg, #fff, #ffe9ef);
  box-shadow: 3px 4px 0 #f2dbe3;
}
.statement-tile--2 .statement-tile__symbol {
  color: #196d72;
  background: linear-gradient(145deg, #fff, #e5f2f0);
  box-shadow: 3px 4px 0 #d9eae7;
}
.statement-tile h3 {
  font-size: 1.06rem;
  font-weight: 800;
  line-height: 1.42;
  margin: 1.35rem 0 0.85rem;
  letter-spacing: -0.027em;
}
.statement-tile > p {
  font-size: 0.77rem;
  color: #6b7c91;
  line-height: 1.85;
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
  margin-bottom: 1.4rem;
  font-weight: 400;
}
.statement-tile__bottom {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-top: auto;
  padding-top: 1rem;
  border-top: 1px solid #dce5f080;
  color: #0057b8;
  font-size: 0.73rem;
  font-weight: 750;
}
.statement-tile__arrow {
  width: 33px;
  height: 33px;
  display: grid;
  place-items: center;
  background: white;
  border: 1px solid #e0e8f2;
  border-radius: 50%;
}
.discover-fallback {
  border: 1px dashed #b8cbe5;
  padding: 2rem;
  border-radius: 20px;
  color: #5b718d;
  background: #f7faff;
}
.discover-fallback p {
  margin: 0.8rem 0;
}
.how-section {
  padding-block: 70px;
  background: #f6f8fc;
  border-block: 1px solid #eaf0f7;
}
.section-heading--center {
  display: block;
  text-align: center;
  margin-bottom: 2.6rem;
}
.section-heading--center .eyebrow {
  justify-content: center;
}
.section-heading--center h2 {
  font-size: clamp(2rem, 3.6vw, 3.2rem);
}
.section-heading--center h2 > span {
  color: #0057b8;
}
.how-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 22px;
}
.how-card {
  border: 1px solid #e2e9f4;
  background: white;
  border-radius: 23px;
  padding: 1.4rem 1.6rem 1.8rem;
  position: relative;
  box-shadow: 0 5px 0 #e9eef7;
}
.how-card__number {
  font-size: 0.67rem;
  font-weight: 800;
  color: #65768d;
}
.how-card__visual {
  height: 118px;
  display: grid;
  place-items: center;
  position: relative;
  margin-bottom: 1rem;
}
.how-card__back,
.how-card__front {
  position: absolute;
  width: 68px;
  height: 75px;
  border-radius: 20px;
  border: 1px solid #c3d8f3;
  background: #d9e7fa;
  transform: rotate(-12deg) translate(-8px, 1px);
}
.how-card__front {
  display: grid;
  place-items: center;
  color: #0057b8;
  background: linear-gradient(145deg, #fff, #e9f1ff);
  transform: rotate(8deg);
  border-color: #d3e2f7;
  box-shadow: 4px 6px 0 #bfd5f1;
}
.how-card__spark {
  position: absolute;
  left: calc(50% + 42px);
  top: 7px;
  font-size: 22px;
  color: #74a2df;
}
.how-card--red .how-card__back {
  background: #ffdce5;
  border-color: #f5c4d1;
}
.how-card--red .how-card__front {
  color: #d40632;
  background: linear-gradient(145deg, #fff, #fff0f4);
  border-color: #f4d4df;
  box-shadow: 4px 6px 0 #f2ccd6;
}
.how-card--red .how-card__spark {
  color: #dc7590;
}
.how-card--navy .how-card__back {
  background: #c7d6ec;
}
.how-card--navy .how-card__front {
  color: #001f4d;
  box-shadow: 4px 6px 0 #c7d4e8;
}
.how-card h3 {
  font-size: 1rem;
  margin: 0 0 0.7rem;
  font-weight: 800;
}
.how-card > p {
  margin: 0;
  color: #697c94;
  font-size: 0.79rem;
  line-height: 1.85;
}
.conversation-section {
  padding-block: 85px;
  background: #fff;
}
.conversation-grid {
  display: grid;
  grid-template-columns: 1.12fr 1fr;
  align-items: center;
  gap: 4.5rem;
}
.conversation-art {
  position: relative;
  min-width: 0;
}
.conversation-art__backdrop {
  position: absolute;
  inset: 8% 3% 0;
  border-radius: 50%;
  background: #f2f6fc;
}
.conversation-art img {
  width: 100%;
  height: auto;
  display: block;
  position: relative;
  border-radius: 50px;
  mix-blend-mode: multiply;
}
.conversation-art__caption {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  width: fit-content;
  max-width: 95%;
  position: relative;
  margin: -5px auto 0;
  border: 1px solid #e1e8f3;
  border-radius: 14px;
  padding: 0.8rem 1rem;
  background: white;
  color: #0057b8;
  font-size: 0.65rem;
  font-weight: 700;
  box-shadow: 0 8px 25px #14396809;
  transform: rotate(-3deg);
}
.conversation-copy h2 {
  font-size: clamp(2rem, 3.7vw, 3.25rem);
  line-height: 1.13;
  letter-spacing: -0.055em;
  font-weight: 800;
  margin: 0.9rem 0 1.2rem;
}
.conversation-copy h2 span {
  color: #0057b8;
}
.conversation-copy > p:not(.eyebrow) {
  color: #62768e;
  font-size: 0.9rem;
  line-height: 1.9;
}
.conversation-copy .conversation-copy__sources {
  font-size: 0.76rem !important;
  color: #607188 !important;
}
.compass-section {
  padding-block: 0 85px;
}
.compass-feature {
  position: relative;
  display: grid;
  grid-template-columns: 1fr 1fr;
  align-items: center;
  gap: 3rem;
  padding: 3.6rem 3.8rem;
  border-radius: 32px;
  border: 1px solid #163962;
  color: #fff;
  background:
    radial-gradient(ellipse at 85% 10%, #17447e, transparent 65%), #001f4d;
  overflow: clip;
  box-shadow:
    0 10px 0 #dfe8f4,
    0 25px 45px #001f4d0b;
}
.compass-feature__badge {
  display: inline-block;
  font-size: 0.63rem;
  letter-spacing: 0.04em;
  padding: 0.4rem 0.75rem;
  border: 1px solid #ffffff38;
  color: #d1e4ff;
  background: #ffffff0c;
  border-radius: 30px;
  margin-bottom: 1.2rem;
}
.compass-feature h2 {
  font-size: clamp(2rem, 3.7vw, 3.2rem);
  line-height: 1.12;
  font-weight: 800;
  letter-spacing: -0.05em;
  margin: 0 0 1.2rem;
}
.compass-feature h2 span {
  color: #8ebcf9;
}
.compass-feature__copy > p {
  color: #b9cce5;
  max-width: 38ch;
  font-size: 0.84rem;
  line-height: 1.85;
}
.button-white {
  background: #fff;
  color: #001f4d;
  border-color: #fff;
  border-radius: 12px;
  font-size: 0.8rem;
}
.button-white:hover {
  color: #0057b8;
  background: #edf4ff;
}
.compass-feature__copy > small {
  color: #91acca;
  display: block;
  margin-top: 1rem;
  font-size: 0.63rem;
}
.compass-feature__art {
  min-width: 0;
}
.compass-feature__art :deep(.scene-toggle) {
  color: #aec7e6;
}
.compass-feature__art :deep(.scene-toggle > span) {
  color: #c4daff;
  border-color: #6682a7;
}
.home-faq {
  display: grid;
  grid-template-columns: 1fr 1.25fr;
  gap: 5rem;
  padding-bottom: 80px;
}
.home-faq h2 {
  font-size: 2.7rem;
  line-height: 1.1;
  margin: 0 0 1rem;
  font-weight: 800;
}
.home-faq h2 span {
  color: #0057b8;
}
.home-faq > div > p:not(.eyebrow) {
  color: #6c7e95;
  font-size: 0.82rem;
}
.home-faq .faq-list {
  align-self: center;
}
.home-faq .faq-list summary {
  font-size: 0.85rem;
}
.home-faq .faq-list details {
  padding-block: 1.3rem;
}
.final-section {
  padding-bottom: 70px;
}
.final-card {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 2rem;
  border: 1px solid #dce7f5;
  border-radius: 25px;
  padding: 2.2rem 2.8rem;
  background: linear-gradient(120deg, #eff5ff, #f7f9ff);
  position: relative;
}
.final-card .eyebrow {
  margin-bottom: 0.6rem;
  font-size: 0.6rem;
}
.final-card h2 {
  font-size: 2.1rem;
  margin: 0 0 0.6rem;
  font-weight: 800;
}
.final-card p:not(.eyebrow) {
  font-size: 0.84rem;
  color: #687d98;
  margin-bottom: 0;
}
.final-card__spark {
  position: absolute;
  right: 35%;
  top: -20px;
  font-size: 45px;
  color: #0057b8;
  transform: rotate(12deg);
}
.final-card .button {
  border-radius: 12px;
  min-height: 50px;
}
@media (max-width: 1050px) {
  .hero__layout {
    gap: 2rem;
  }
  .hero__actions {
    gap: 0.7rem 1.2rem;
  }
  .promise-strip__grid {
    gap: 1rem;
  }
  .compass-feature {
    padding: 2.8rem;
  }
  .conversation-grid {
    gap: 2.5rem;
  }
}
@media (max-width: 720px) {
  .hero {
    padding-top: 12px;
  }
  .hero__layout {
    grid-template-columns: 1fr;
    gap: 0;
    padding-block: 25px 0;
  }
  .hero__copy {
    padding-bottom: 0;
  }
  .hero h1 {
    font-size: clamp(2.8rem, 11.3vw, 4.5rem);
    line-height: 1.08;
  }
  .hero__eyebrow {
    font-size: 0.62rem;
    margin-bottom: 1.4rem;
  }
  .hero__lead {
    font-size: 0.9rem;
    margin-block: 1.8rem 1.4rem;
  }
  .hero__actions {
    gap: 1rem;
  }
  .hero__actions .button {
    font-size: 0.77rem;
    padding-inline: 1.1rem;
    min-height: 49px;
  }
  .hero__secondary {
    font-size: 0.68rem;
    gap: 0.35rem;
  }
  .hero__reassurance {
    margin-top: 1.4rem;
    gap: 1rem;
    font-size: 0.62rem;
  }
  .hero__art {
    width: min(100%, 380px);
    justify-self: center;
    margin-top: 1.5rem;
  }
  .hero__bottom {
    padding-bottom: 20px;
  }
  .hero__bottom > span {
    display: none;
  }
  .hero__grid-paper {
    mask-image: linear-gradient(0deg, #000 5%, transparent 80%);
  }
  .promise-strip__grid {
    grid-template-columns: 1fr;
    gap: 1.15rem;
    padding-block: 26px;
  }
  .promise-strip__grid strong {
    font-size: 0.75rem;
  }
  .promise-strip__grid small {
    font-size: 0.65rem;
  }
  .discover-section {
    padding-block: 48px;
  }
  .section-heading {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.4rem;
  }
  .section-heading h2 {
    font-size: 1.95rem;
  }
  .section-heading p:not(.eyebrow) {
    font-size: 0.79rem;
  }
  .topic-pills {
    gap: 0.45rem;
  }
  .topic-pills > a {
    font-size: 0.65rem;
    padding: 0.5rem 0.65rem;
  }
  .topic-pills > a > span {
    margin-left: 0.15rem;
  }
  .statement-grid {
    grid-template-columns: 1fr;
    gap: 19px;
  }
  .statement-tile {
    padding: 1.4rem;
    border-radius: 20px;
  }
  .statement-tile h3 {
    font-size: 1.08rem;
    margin-top: 1rem;
  }
  .statement-tile > p {
    font-size: 0.78rem;
    -webkit-line-clamp: 2;
  }
  .how-section {
    padding-block: 46px;
  }
  .section-heading--center {
    text-align: left;
  }
  .section-heading--center .eyebrow {
    justify-content: flex-start;
  }
  .how-grid {
    grid-template-columns: 1fr;
    gap: 18px;
  }
  .how-card {
    padding: 1.25rem 1.4rem 1.4rem;
    display: grid;
    grid-template-columns: 80px 1fr;
    column-gap: 1.1rem;
  }
  .how-card__number {
    position: absolute;
    top: 12px;
    right: 14px;
    font-size: 0.57rem;
  }
  .how-card__visual {
    grid-row: 1 / 3;
    height: 100%;
    min-height: 100px;
    margin: 0;
  }
  .how-card__front,
  .how-card__back {
    width: 49px;
    height: 57px;
    border-radius: 15px;
  }
  .how-card__front svg {
    width: 27px;
    height: 27px;
  }
  .how-card__spark {
    font-size: 16px;
    left: auto;
    right: -2px;
    top: 0;
  }
  .how-card h3 {
    margin-top: 0.3rem;
    font-size: 0.92rem;
  }
  .how-card > p {
    font-size: 0.76rem;
  }
  .conversation-section {
    padding-block: 45px;
  }
  .conversation-grid {
    grid-template-columns: 1fr;
    gap: 2.4rem;
  }
  .conversation-copy h2 {
    font-size: 2.35rem;
  }
  .conversation-copy > p:not(.eyebrow) {
    font-size: 0.85rem;
  }
  .conversation-art__caption {
    font-size: 0.57rem;
    padding: 0.65rem;
    gap: 0.3rem;
  }
  .compass-section {
    padding-block: 0 55px;
  }
  .compass-feature {
    grid-template-columns: 1fr;
    padding: 2rem 1.5rem 1.3rem;
    border-radius: 25px;
    gap: 0.5rem;
  }
  .compass-feature h2 {
    font-size: 2.45rem;
  }
  .compass-feature__copy > p {
    font-size: 0.82rem;
  }
  .compass-feature__art {
    margin-top: 1rem;
  }
  .compass-feature__art :deep(.scene-chip--first) {
    left: -4%;
    font-size: 0.58rem;
  }
  .compass-feature__art :deep(.scene-chip--second) {
    font-size: 0.48rem;
  }
  .home-faq {
    grid-template-columns: 1fr;
    gap: 1.5rem;
    padding-bottom: 45px;
  }
  .home-faq h2 {
    font-size: 2.2rem;
  }
  .home-faq .faq-list summary {
    font-size: 0.8rem;
  }
  .final-section {
    padding-bottom: 45px;
  }
  .final-card {
    flex-direction: column;
    align-items: flex-start;
    padding: 1.8rem;
    gap: 1.5rem;
  }
  .final-card h2 {
    font-size: 1.8rem;
  }
  .final-card .button {
    width: 100%;
  }
  .final-card__spark {
    right: 15px;
    font-size: 35px;
    top: -20px;
  }
}
@media (max-width: 360px) {
  .hero__actions {
    align-items: flex-start;
    flex-direction: column;
    gap: 0.4rem;
  }
  .hero__actions .button {
    width: 100%;
  }
  .hero h1 {
    font-size: 2.75rem;
  }
  .how-card {
    grid-template-columns: 65px 1fr;
    gap: 1rem;
  }
  .topic-pills > a {
    font-size: 0.62rem;
  }
}
</style>
