<script setup lang="ts">
import AppIcon from "~/components/ui/AppIcon.vue";
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
    /* The landing page remains useful when content is temporarily unavailable. */
  }
});
const steps = [
  {
    icon: "chat",
    title: "Lees de stelling",
    text: "Een duidelijke vraag, met context en argumenten van beide kanten.",
  },
  {
    icon: "check",
    title: "Kies jouw kant",
    text: "Tegen of voor. Jij bepaalt wat je vindt, op je eigen tempo.",
  },
  {
    icon: "compass",
    title: "Ontdek de uitkomst",
    text: "Bekijk de mening van deelnemers en leer je eigen standpunten kennen.",
  },
];
</script>
<template>
  <div class="home-v2">
    <section class="home-hero">
      <img
        class="home-hero__image"
        src="/images/martijn-baudoin-C4rDHqqmu4w-unsplash.webp"
        alt="Rode tulpenvelden en windmolens in het Nederlandse landschap"
        fetchpriority="high"
        width="1920"
        height="1280"
      />
      <div class="home-hero__shade" />
      <div class="container home-hero__content">
        <div class="home-hero__copy" data-reveal>
          <p class="home-hero__eyebrow"><span /> Wat denkt Nederland?</p>
          <h1>Nederland heeft<br />een mening.<br /><span>Jij ook.</span></h1>
          <p class="home-hero__lead">
            Stem op wat jou bezighoudt. Ontdek beide kanten.<br
              class="desktop-break"
            />
            En leer jezelf een beetje beter kennen.
          </p>
          <div class="home-hero__actions">
            <NuxtLink class="button button-red" to="/spel"
              >Begin met stemmen <AppIcon name="arrow" :size="18" /></NuxtLink
            ><NuxtLink class="home-hero__secondary" to="/kompas"
              >Ontdek jouw kompas <AppIcon name="arrow" :size="16"
            /></NuxtLink>
          </div>
          <p class="home-hero__note">
            <AppIcon name="check" :size="15" /> Gratis stemmen
            <span>·</span> Geen politieke voorkennis nodig
          </p>
        </div>
        <aside v-if="preview[0]" class="hero-question" data-reveal>
          <span class="hero-question__badge"
            ><AppIcon name="chat" :size="15" /> Een vraag om over na te
            denken</span
          >
          <p>{{ preview[0].title }}</p>
          <NuxtLink :to="`/stellingen/${preview[0].slug}`"
            >Wat vind jij? <AppIcon name="arrow" :size="18"
          /></NuxtLink>
          <span class="hero-question__footnote"
            >Eerst jouw mening. Daarna de uitslag.</span
          >
        </aside>
      </div>
      <span class="home-hero__edition">Jouw stem. Jouw perspectief.</span>
    </section>

    <section class="home-benefits" aria-label="Wat je kunt verwachten">
      <div class="container home-benefits__grid">
        <div>
          <AppIcon name="chat" />
          <p>
            <strong>Ruimte voor jouw mening</strong
            ><span>Eén stelling tegelijk</span>
          </p>
        </div>
        <div>
          <AppIcon name="grid" />
          <p>
            <strong>Bekijk beide kanten</strong
            ><span>Argumenten vóór en tegen</span>
          </p>
        </div>
        <div>
          <AppIcon name="shield" />
          <p>
            <strong>Jouw stem is persoonlijk</strong
            ><span>Anderen zien alleen de uitslag</span>
          </p>
        </div>
      </div>
    </section>

    <section class="container home-section" data-reveal>
      <header class="home-section__heading">
        <div>
          <p class="eyebrow">Van mening verschillen mag</p>
          <h2>Wat vind jij?</h2>
          <p>Over politiek, het nieuws en alles wat ons samen raakt.</p>
        </div>
        <NuxtLink to="/stellingen" class="home-text-link"
          >Bekijk alle stellingen <AppIcon name="arrow" :size="18"
        /></NuxtLink>
      </header>
      <nav class="home-topics" aria-label="Ontdek een onderwerp">
        <NuxtLink
          v-for="topic in ['Wonen', 'Zorg', 'Klimaat', 'Onderwijs']"
          :key="topic"
          :to="{ path: '/stellingen', query: { zoeken: topic.toLowerCase() } }"
          >{{ topic }} <span aria-hidden="true">↗</span></NuxtLink
        >
      </nav>
      <div v-if="preview.length" class="home-statements">
        <NuxtLink
          v-for="item in preview"
          :key="item.id"
          :to="`/stellingen/${item.slug}`"
          class="home-statement"
          data-lift
          ><span class="home-statement__label">{{
            item.source?.kind === "news"
              ? "In het nieuws"
              : item.source?.kind === "editorial"
                ? "Samenleving"
                : item.source?.kind === "motion"
                  ? "Motie"
                  : "Stelling"
          }}</span>
          <h3>{{ item.title }}</h3>
          <p>{{ item.description }}</p>
          <span class="home-statement__action"
            >Wat denk jij? <AppIcon name="arrow" :size="17" /></span
        ></NuxtLink>
      </div>
      <div v-else class="home-content-fallback">
        <p>Ontdek de stellingen, lees de argumenten en laat je stem horen.</p>
        <NuxtLink class="button button-secondary" to="/stellingen"
          >Naar de stellingen <AppIcon name="arrow" :size="17"
        /></NuxtLink>
      </div>
    </section>

    <section class="home-how" data-reveal>
      <div class="container">
        <header class="home-section__heading home-section__heading--center">
          <p class="eyebrow">Zo eenvoudig werkt het</p>
          <h2>Eén vraag. Twee kanten. Jouw mening.</h2>
          <p>Je hoeft geen expert te zijn om mee te praten.</p>
        </header>
        <div class="home-steps">
          <article v-for="(step, index) in steps" :key="step.title" data-lift>
            <div class="home-steps__icon">
              <AppIcon :name="step.icon" :size="25" /><span
                >0{{ index + 1 }}</span
              >
            </div>
            <h3>{{ step.title }}</h3>
            <p>{{ step.text }}</p>
          </article>
        </div>
      </div>
    </section>

    <section class="home-conversation" data-reveal>
      <div class="container home-conversation__grid">
        <div class="home-conversation__art">
          <img
            src="/illustrations/dutch-neighbours-v1.jpg"
            width="1536"
            height="1024"
            alt=""
            loading="lazy"
          />
          <span class="home-conversation__bubble"
            >Een ander perspectief begint bij luisteren.</span
          >
        </div>
        <div class="home-conversation__copy">
          <p class="eyebrow">Van het Binnenhof tot jouw buurt</p>
          <h2>Niet alleen politiek.<br />Ook wat jou bezighoudt.</h2>
          <p>
            Wonen, zorg, het nieuws of een vraag uit het dagelijks leven. Er is
            altijd meer dan één kant aan een verhaal.
          </p>
          <p class="home-conversation__sources">
            Onze redactie maakt stellingen bij officiële moties, actuele
            gebeurtenissen en maatschappelijke vragen. Bij de stelling zie je de
            herkomst en beschikbare bron.
          </p>
          <NuxtLink to="/methodologie" class="home-text-link"
            >Zo maken we de stellingen <AppIcon name="arrow" :size="17"
          /></NuxtLink>
        </div>
      </div>
    </section>

    <section class="container home-section" data-reveal>
      <div class="home-compass">
        <div class="home-compass__copy">
          <p class="eyebrow">Een beter beeld van jezelf</p>
          <h2>Jouw keuzes vertellen<br />een verhaal.</h2>
          <p>
            Ontdek welke politieke waarden terugkomen in je antwoorden. Het
            vernieuwde politieke kompas helpt je straks de samenhang te zien.
          </p>
          <NuxtLink class="button" to="/kompas"
            >Ontdek het kompas <AppIcon name="arrow" :size="17" /></NuxtLink
          ><span class="home-compass__soon"
            >Het nieuwe rapport is binnenkort beschikbaar</span
          >
        </div>
        <div class="home-compass__visual" aria-hidden="true">
          <div class="compass-orbit orbit-one" />
          <div class="compass-orbit orbit-two" />
          <div class="compass-disc">
            <span class="compass-n">N</span><span class="compass-s">Z</span
            ><span class="compass-w">W</span><span class="compass-e">O</span>
            <div class="compass-needle" />
            <i />
          </div>
          <span class="compass-pill compass-pill--one"
            ><AppIcon name="check" :size="15" /> Jouw standpunten</span
          ><span class="compass-pill compass-pill--two"
            ><AppIcon name="compass" :size="16" /> Jouw richting</span
          >
        </div>
      </div>
    </section>

    <section class="container home-faq" data-reveal>
      <div>
        <p class="eyebrow">Even helder</p>
        <h2>Misschien vraag<br />je je af…</h2>
        <p>Dit wil je weten voordat je begint.</p>
        <NuxtLink class="home-text-link" to="/faq"
          >Alle vragen en antwoorden <AppIcon name="arrow" :size="17"
        /></NuxtLink>
      </div>
      <div class="faq-list">
        <details v-for="item in faqItems.slice(0, 3)" :key="item.question">
          <summary>{{ item.question }}</summary>
          <p>{{ item.answer }}</p>
        </details>
      </div>
    </section>
    <section class="container home-final" data-reveal>
      <div>
        <h2>En, wat denk jij?</h2>
        <p>Er is ruimte voor jouw mening.</p>
      </div>
      <NuxtLink class="button button-red" to="/spel"
        >Begin met stemmen <AppIcon name="arrow" :size="18"
      /></NuxtLink>
    </section>
  </div>
</template>
<style scoped>
.home-v2 {
  background: #fff;
}
.home-hero {
  min-height: 570px;
  position: relative;
  overflow: hidden;
  background: #001f4d;
  isolation: isolate;
}
.home-hero__image {
  width: 100%;
  height: 100%;
  object-fit: cover;
  position: absolute;
  inset: 0;
  object-position: center 58%;
  z-index: -3;
}
.home-hero__shade {
  position: absolute;
  inset: 0;
  background: linear-gradient(
    90deg,
    #001b43 2%,
    #001e49f2 27%,
    #001f4d99 52%,
    #001f4d08 100%
  );
  z-index: -2;
}
.home-hero__content {
  display: grid;
  grid-template-columns: 1.3fr 0.7fr;
  gap: 3rem;
  align-items: center;
  padding-block: 65px 58px;
}
.home-hero__eyebrow {
  color: #fff;
  display: flex;
  align-items: center;
  gap: 0.65rem;
  font-size: 0.65rem;
  letter-spacing: 0.15em;
  font-weight: 700;
  text-transform: uppercase;
  margin-bottom: 1.4rem;
}
.home-hero__eyebrow > span {
  height: 7px;
  width: 7px;
  border-radius: 50%;
  background: #fa3153;
}
.home-hero h1 {
  color: #fff;
  font-size: clamp(3rem, 5.3vw, 4.3rem);
  line-height: 1.06;
  letter-spacing: -0.055em;
  margin: 0 0 1.35rem;
  font-weight: 820;
}
.home-hero h1 > span {
  color: #94bfff;
}
.home-hero__lead {
  color: #e4eaf2;
  font-size: 0.98rem;
  line-height: 1.8;
  margin-bottom: 1.7rem;
}
.home-hero__actions {
  display: flex;
  gap: 1.4rem;
  align-items: center;
  flex-wrap: wrap;
}
.home-hero__secondary {
  display: inline-flex;
  align-items: center;
  gap: 0.7rem;
  color: #fff;
  font-size: 0.82rem;
  min-height: 44px;
}
.home-hero__note {
  color: #bacbdd;
  display: flex;
  gap: 0.5rem;
  align-items: center;
  font-size: 0.68rem;
  margin: 1.2rem 0 0;
}
.home-hero__note svg {
  color: #a8c8f5;
}
.home-hero__note > span {
  margin-inline: 0.2rem;
}
.home-hero__edition {
  position: absolute;
  right: 35px;
  bottom: 28px;
  color: #fff;
  font-size: 0.62rem;
  letter-spacing: 0.14em;
  text-transform: uppercase;
  text-shadow: 0 1px 4px #000;
}
.home-benefits {
  background: #fff;
  border-bottom: 1px solid #e8edf4;
}
.home-benefits__grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  padding-block: 26px;
}
.home-benefits__grid > div {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 0.9rem;
}
.home-benefits__grid > div + div {
  border-left: 1px solid #e3e9f2;
}
.home-benefits__grid svg {
  color: #0057b8;
}
.home-benefits p {
  display: grid;
  gap: 0.1rem;
  margin: 0;
}
.home-benefits strong {
  font-size: 0.77rem;
  font-weight: 700;
}
.home-benefits span {
  font-size: 0.71rem;
  color: #68758a;
}
.home-section {
  padding-block: 68px;
}
.home-section__heading {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  gap: 1rem;
  margin-bottom: 1.7rem;
}
.home-section__heading h2 {
  margin: 0 0 0.6rem;
  font-size: 2rem;
}
.home-section__heading p:not(.eyebrow) {
  font-size: 0.88rem;
  color: #68758a;
  margin: 0;
}
.home-section__heading .eyebrow {
  margin-bottom: 0.6rem;
}
.home-text-link {
  display: inline-flex;
  align-items: center;
  gap: 0.6rem;
  font-size: 0.78rem;
  white-space: nowrap;
}
.home-statements {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 18px;
}
.home-statement {
  display: flex;
  flex-direction: column;
  border: 1px solid #dfe6f0;
  border-radius: 13px;
  background: #fff;
  padding: 1.5rem;
  color: #001f4d;
  transition:
    border-color 0.2s,
    transform 0.2s;
  text-decoration: none;
}
.home-statement:hover {
  border-color: #9abce7;
  text-decoration: none;
}
.home-statement__label {
  font-size: 0.61rem;
  color: #0057b8;
  border-radius: 5px;
  background: #edf3ff;
  padding: 0.2rem 0.55rem;
  align-self: flex-start;
}
.home-statement h3 {
  font-size: 1.05rem;
  line-height: 1.4;
  margin: 1rem 0 0.8rem;
}
.home-statement p {
  color: #68758a;
  font-size: 0.8rem;
  line-height: 1.75;
  font-weight: 400;
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
  margin-bottom: 1.3rem;
}
.home-statement__action {
  margin-top: auto;
  padding-top: 1rem;
  border-top: 1px solid #edf0f6;
  display: flex;
  align-items: center;
  justify-content: space-between;
  color: #0057b8;
  font-size: 0.75rem;
}
.home-content-fallback {
  background: #f5f7fa;
  padding: 2rem;
  border-radius: 14px;
  color: #56657b;
}
.home-how {
  background: #f5f7fa;
  border-block: 1px solid #edf0f6;
  padding-block: 54px;
}
.home-section__heading--center {
  display: grid;
  justify-content: center;
  justify-items: center;
  text-align: center;
  gap: 0;
  margin-bottom: 2.5rem;
}
.home-section__heading--center h2 {
  font-size: 1.85rem;
}
.home-steps {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 3rem;
  text-align: center;
}
.home-steps__icon {
  position: relative;
  border: 1px solid #dce5f3;
  background: #fff;
  border-radius: 16px;
  width: 60px;
  height: 60px;
  margin: 0 auto 1.2rem;
  display: grid;
  place-items: center;
  color: #0057b8;
}
.home-steps__icon > span {
  position: absolute;
  top: -7px;
  right: -7px;
  border: 3px solid #f5f7fa;
  border-radius: 50%;
  background: #001f4d;
  color: #fff;
  width: 23px;
  height: 23px;
  font-size: 0.5rem;
  display: grid;
  place-items: center;
}
.home-steps h3 {
  font-size: 1rem;
  margin: 0 0 0.6rem;
}
.home-steps p {
  font-size: 0.82rem;
  color: #68758a;
  max-width: 30ch;
  margin: auto;
}
.home-compass {
  background: #eef4ff;
  border: 1px solid #e1eaf9;
  border-radius: 20px;
  display: grid;
  grid-template-columns: 1fr 1fr;
  align-items: center;
  padding: 3.2rem;
  gap: 2rem;
  overflow: hidden;
}
.home-compass h2 {
  font-size: 2.4rem;
  margin: 0 0 1rem;
}
.home-compass__copy > p:not(.eyebrow) {
  color: #62728a;
  font-size: 0.87rem;
  max-width: 42ch;
  line-height: 1.8;
}
.home-compass__soon {
  display: block;
  font-size: 0.68rem;
  color: #63738a;
  margin-top: 0.85rem;
}
.home-compass__visual {
  height: 290px;
  position: relative;
  display: grid;
  place-items: center;
}
.compass-orbit {
  position: absolute;
  border: 1px solid #cfddf4;
  border-radius: 50%;
  width: 280px;
  height: 280px;
}
.orbit-two {
  width: 350px;
  height: 350px;
  border-style: dashed;
}
.compass-disc {
  position: relative;
  width: 205px;
  height: 205px;
  background: #fff;
  border: 11px solid #f8faff;
  border-radius: 50%;
  outline: 1px solid #d0def1;
  box-shadow: 0 10px 40px #0057b815;
  transform: rotate(-15deg);
}
.compass-disc > span {
  position: absolute;
  color: #7285a4;
  font-size: 0.7rem;
  font-weight: 700;
}
.compass-n {
  top: 9px;
  left: calc(50% - 4px);
}
.compass-s {
  bottom: 9px;
  left: calc(50% - 4px);
}
.compass-w {
  left: 10px;
  top: calc(50% - 8px);
}
.compass-e {
  right: 10px;
  top: calc(50% - 8px);
}
.compass-needle {
  position: absolute;
  top: 26px;
  bottom: 26px;
  left: 67px;
  width: 48px;
  background: linear-gradient(135deg, #e1062e 50%, #dce7f6 50%);
  clip-path: polygon(50% 0, 100% 50%, 50% 100%, 0 50%);
  transform: rotate(42deg);
}
.compass-disc i {
  position: absolute;
  top: calc(50% - 6px);
  left: calc(50% - 6px);
  width: 12px;
  height: 12px;
  border: 3px solid #fff;
  border-radius: 50%;
  background: #001f4d;
}
.compass-pill {
  display: flex;
  gap: 0.5rem;
  align-items: center;
  font-size: 0.7rem;
  padding: 0.7rem 1rem;
  background: #fff;
  border: 1px solid #dce6f5;
  border-radius: 9px;
  box-shadow: 0 4px 20px #0057b80a;
  position: absolute;
  font-weight: 600;
}
.compass-pill--one {
  top: 14px;
  left: 5px;
  transform: rotate(-5deg);
}
.compass-pill--one svg {
  color: #24743b;
}
.compass-pill--two {
  bottom: 12px;
  right: 0;
  transform: rotate(4deg);
  color: #0057b8;
}
.home-faq {
  display: grid;
  grid-template-columns: 1fr 1.45fr;
  gap: 5rem;
  padding-bottom: 65px;
}
.home-faq h2 {
  font-size: 2rem;
  margin: 0 0 1rem;
}
.home-faq > div > p:not(.eyebrow) {
  font-size: 0.85rem;
  color: #68758a;
}
.home-faq .home-text-link {
  margin-top: 0.5rem;
}
.home-final {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-block: 1.8rem;
  border-top: 1px solid #e7ecf4;
  margin-bottom: 2.5rem;
}
.home-final h2 {
  font-size: 1.65rem;
  margin: 0 0 0.4rem;
}
.home-final p {
  font-size: 0.85rem;
  color: #68758a;
  margin: 0;
}
@media (max-width: 720px) {
  .home-hero {
    min-height: 570px;
  }
  .home-hero__content {
    padding-block: 50px;
  }
  .home-hero__image {
    object-position: 65% center;
  }
  .home-hero__shade {
    background:
      linear-gradient(90deg, #001c45f5, #001f4d8a),
      linear-gradient(0deg, #001f4d75, transparent);
  }
  .home-hero h1 {
    font-size: 3.45rem;
  }
  .home-hero__lead {
    font-size: 0.88rem;
  }
  .home-hero__edition {
    display: none;
  }
  .home-hero__actions {
    gap: 0.7rem 1.4rem;
  }
  .home-hero__note {
    font-size: 0.6rem;
    gap: 0.3rem;
  }
  .home-benefits__grid {
    grid-template-columns: 1fr;
    padding-block: 22px;
    gap: 18px;
  }
  .home-benefits__grid > div {
    justify-content: flex-start;
    padding-left: 0.5rem;
  }
  .home-benefits__grid > div + div {
    border: 0;
  }
  .home-section {
    padding-block: 42px;
  }
  .home-section__heading {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }
  .home-section__heading h2 {
    font-size: 1.8rem;
  }
  .home-statements {
    grid-template-columns: 1fr;
  }
  .home-how {
    padding-block: 40px;
  }
  .home-section__heading--center {
    align-items: center;
  }
  .home-section__heading--center h2 {
    font-size: 1.7rem;
  }
  .home-steps {
    grid-template-columns: 1fr;
    gap: 2rem;
  }
  .home-steps p {
    max-width: 36ch;
  }
  .home-compass {
    grid-template-columns: 1fr;
    padding: 1.7rem;
    gap: 1.5rem;
  }
  .home-compass h2 {
    font-size: 2rem;
  }
  .home-compass__visual {
    height: 270px;
  }
  .compass-pill--one {
    left: -10px;
  }
  .compass-pill--two {
    right: -10px;
  }
  .home-faq {
    grid-template-columns: 1fr;
    gap: 1.5rem;
    padding-bottom: 40px;
  }
  .home-faq h2 br {
    display: none;
  }
  .home-final {
    flex-direction: column;
    align-items: flex-start;
    gap: 1.25rem;
  }
  .home-final .button {
    width: 100%;
  }
}

.hero-question {
  padding: 1.6rem;
  border: 1px solid #ffffffb3;
  background: #ffffffee;
  color: #001f4d;
  border-radius: 22px 22px 22px 5px;
  box-shadow: 0 15px 45px #001f4d20;
  backdrop-filter: blur(10px);
}
.hero-question__badge {
  display: flex;
  align-items: center;
  gap: 0.45rem;
  font-size: 0.64rem;
  color: #0057b8;
}
.hero-question > p {
  font-size: 1.18rem;
  font-weight: 750;
  line-height: 1.45;
  margin: 1.2rem 0;
  letter-spacing: -0.025em;
}
.hero-question > a {
  display: flex;
  align-items: center;
  justify-content: space-between;
  border-radius: 9px;
  padding: 0.8rem 1rem;
  background: #0057b8;
  color: #fff;
  font-size: 0.78rem;
  text-decoration: none;
}
.hero-question__footnote {
  display: block;
  font-size: 0.62rem;
  color: #63738a;
  margin-top: 0.85rem;
}
.home-topics {
  display: flex;
  flex-wrap: wrap;
  gap: 0.6rem;
  margin: -0.25rem 0 1.7rem;
}
.home-topics a {
  font-size: 0.74rem;
  background: #f5f7fa;
  border: 1px solid #e1e7f0;
  border-radius: 30px;
  padding: 0.55rem 0.9rem;
  color: #435570;
  text-decoration: none;
}
.home-topics a span {
  color: #0057b8;
  margin-left: 0.55rem;
}
.home-topics a:hover {
  border-color: #0057b8;
  background: #edf4ff;
}
.home-conversation {
  background: #f5f7fa;
  border-block: 1px solid #e9eef5;
  padding-block: 40px;
}
.home-conversation__grid {
  display: grid;
  grid-template-columns: 1.15fr 1fr;
  gap: 3rem;
  align-items: center;
}
.home-conversation__art {
  position: relative;
  min-width: 0;
}
.home-conversation__art img {
  display: block;
  width: 100%;
  height: auto;
  border-radius: 22px;
}
.home-conversation__bubble {
  display: block;
  margin: -0.5rem auto 0;
  position: relative;
  width: fit-content;
  max-width: 90%;
  background: white;
  border: 1px solid #e0e7f1;
  border-radius: 20px 20px 20px 4px;
  padding: 0.7rem 1rem;
  font-size: 0.69rem;
  font-weight: 650;
  color: #0057b8;
}
.home-conversation__copy h2 {
  font-size: clamp(1.8rem, 3.6vw, 2.5rem);
  line-height: 1.15;
  margin: 0.7rem 0 1.1rem;
}
.home-conversation__copy > p:not(.eyebrow) {
  color: #526277;
  line-height: 1.8;
  font-size: 0.9rem;
}
.home-conversation__copy .home-conversation__sources {
  font-size: 0.78rem !important;
  color: #63738a !important;
}
@media (max-width: 900px) {
  .hero-question {
    display: none;
  }
  .home-hero__content {
    grid-template-columns: 1fr;
  }
}
@media (max-width: 720px) {
  .home-conversation__grid {
    grid-template-columns: 1fr;
    gap: 1.8rem;
  }
  .home-conversation {
    padding-block: 25px 40px;
  }
  .home-conversation__copy h2 br {
    display: none;
  }
}
</style>
