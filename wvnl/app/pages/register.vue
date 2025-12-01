<template>
  <div class="page">
    <section class="page-hero page-hero--register">
      <div class="container narrow">
        <h1 class="page-hero__title">
          <span class="page-hero__accent page-hero__accent--blue">Account</span>
          <span class="page-hero__accent page-hero__accent--red">aanmaken</span>
        </h1>
        <p class="page-hero__subtitle">
          Sluit je aan bij de community en maak in een paar stappen je profiel
          aan.
        </p>
      </div>
    </section>

    <section class="container narrow">
      <registerScreen />
    </section>
  </div>
</template>

<script setup lang="ts">
import registerScreen from "~/components/registerScreen.vue";
const { isLoggedIn } = storeToRefs(useAuthStore());
const router = useRouter();

const title = "Account aanmaken | Wat Denkt Nederland";
const description =
  "Maak een account aan bij Wat Denkt Nederland en doe mee aan discussies over actuele politieke moties.";

watchEffect(() => {
  if (isLoggedIn.value) router.replace("/");
});
</script>

<style scoped>
.page-hero {
  background-size: cover;
  background-position: center;
  background-repeat: no-repeat;
  color: #fff;
  display: grid;
  place-items: center;
  text-align: center;
  min-height: min(48vh, 420px);
  position: relative;
  isolation: isolate;
  padding: 0;
}

.page-hero::before {
  content: "";
  position: absolute;
  inset: 0;
  background: linear-gradient(
    to bottom,
    rgba(0, 0, 0, 0.6) 0%,
    rgba(0, 0, 0, 0.2) 45%,
    rgba(0, 0, 0, 0.5) 100%
  );
  z-index: -2;
}

.page-hero::after {
  content: "";
  position: absolute;
  inset: 0;
  background: radial-gradient(
    circle at center,
    rgba(0, 0, 0, 0) 0%,
    rgba(0, 0, 0, 0.35) 100%
  );
  z-index: -1;
}

.page-hero .container {
  position: relative;
  z-index: 1;
  padding-inline: 1.25rem;
}

.page-hero__title {
  margin: 0;
  font-size: clamp(2.4rem, 4vw, 3.2rem);
  display: flex;
  gap: 0.65rem;
  justify-content: center;
  flex-wrap: wrap;
}

.page-hero__accent {
  display: inline-block;
  padding: 0.1rem 0.35rem;
  border-radius: 10px;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.page-hero__accent--blue {
  background: rgba(0, 51, 102, 0.92);
}

.page-hero__accent--red {
  background: rgba(255, 80, 3, 0.92);
}

.page-hero__subtitle {
  margin: 0.75rem auto 0;
  max-width: 60ch;
  font-size: 1.1rem;
  font-weight: 500;
}

.page-hero--register {
  background-image: linear-gradient(
      135deg,
      rgba(0, 51, 102, 0.12),
      rgba(255, 80, 3, 0.2)
    ),
    url("/images/frederic-paulussen-XHk0Se67H14-unsplash.webp");
}
</style>
