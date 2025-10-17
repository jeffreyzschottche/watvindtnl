<template>
  <div class="page">
    <section class="page-hero page-hero--login">
      <div class="container narrow">
        <h1 class="page-hero__title">
          <span class="page-hero__accent page-hero__accent--blue">Log</span>
          <span class="page-hero__accent page-hero__accent--red">in</span>
        </h1>
        <p class="page-hero__subtitle">
          Log in en ga direct verder met het geven van je mening.
        </p>
      </div>
    </section>

    <section class="container narrow">
      <div v-if="banner" class="login-banner" :class="`login-banner--${banner.variant}`">
        <div class="login-banner__content">
          <h2>{{ banner.title }}</h2>
          <p>{{ banner.message }}</p>
        </div>
        <button type="button" class="login-banner__close" @click="dismissBanner" aria-label="Melding sluiten">
          ×
        </button>
      </div>
      <loginScreen />
    </section>
  </div>
</template>

<script setup lang="ts">
import loginScreen from "~/components/loginScreen.vue";
const { isLoggedIn } = storeToRefs(useAuthStore());
const router = useRouter();
const route = useRoute();

const title = "Inloggen | Wat Denkt Nederland";
const description =
  "Log in bij Wat Denkt Nederland om verder te gaan met stemmen op actuele politieke moties en het beheren van je profiel.";

useHead({
  title,
  meta: [
    { name: "description", content: description },
    { name: "robots", content: "noindex, nofollow" },
    { property: "og:title", content: title },
    { property: "og:description", content: description },
    { property: "og:type", content: "website" },
    { property: "og:locale", content: "nl_NL" },
    { name: "twitter:card", content: "summary" },
    { name: "twitter:title", content: title },
    { name: "twitter:description", content: description },
  ],
});

type BannerVariant = "success" | "info" | "warning" | "error";
type Banner = { title: string; message: string; variant: BannerVariant };

const banner = ref<Banner | null>(null);

watch(
  () => route.query,
  (query) => {
    const verify = query.verify;
    if (verify === "sent") {
      banner.value = {
        title: "Bevestig je e-mailadres",
        message:
          "We hebben je een bevestigingsmail gestuurd. Klik op de link in je inbox om je account te activeren.",
        variant: "info",
      };
      return;
    }

    const verification = query.verification;
    if (typeof verification === "string") {
      switch (verification) {
        case "success":
          banner.value = {
            title: "E-mailadres bevestigd",
            message: "Bedankt! Je kunt nu inloggen met je gegevens.",
            variant: "success",
          };
          return;
        case "already-verified":
          banner.value = {
            title: "Welkom terug",
            message: "Je e-mailadres was al bevestigd. Log gerust in om verder te gaan.",
            variant: "info",
          };
          return;
        case "invalid":
          banner.value = {
            title: "Verificatielink verlopen",
            message: "Vraag een nieuwe bevestigingsmail aan als je nog niet kunt inloggen.",
            variant: "error",
          };
          return;
        default:
          banner.value = {
            title: "Verificatie bijgewerkt",
            message: "Controleer je inbox. Als het niet lukt, kun je hieronder opnieuw een mail laten sturen.",
            variant: "info",
          };
          return;
      }
    }

    const reset = query.reset;
    if (typeof reset === "string") {
      switch (reset) {
        case "success":
          banner.value = {
            title: "Wachtwoord gewijzigd",
            message: "Je kunt nu inloggen met je nieuwe wachtwoord.",
            variant: "success",
          };
          return;
        case "link-sent":
          banner.value = {
            title: "Controleer je inbox",
            message:
              "Als je e-mailadres bij ons bekend is, vind je binnen enkele ogenblikken een e-mail met vervolgstappen.",
            variant: "info",
          };
          return;
        default:
          banner.value = {
            title: "Wachtwoord resetten",
            message: "Volg de instructies in je e-mail om je wachtwoord opnieuw in te stellen.",
            variant: "info",
          };
          return;
      }
    }

    banner.value = null;
  },
  { immediate: true }
);

watchEffect(() => {
  if (isLoggedIn.value) router.replace("/");
});

function dismissBanner() {
  banner.value = null;
}
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
  background: rgba(0, 61, 165, 0.92);
}

.page-hero__accent--red {
  background: rgba(200, 16, 46, 0.92);
}

.page-hero__subtitle {
  margin: 0.75rem auto 0;
  max-width: 60ch;
  font-size: 1.1rem;
  font-weight: 500;
}

.page-hero--login {
  background-image: linear-gradient(
      135deg,
      rgba(0, 61, 165, 0.12),
      rgba(200, 16, 46, 0.2)
    ),
    url("/images/stockvault-cheese147191.jpg");
}

.login-banner {
  display: flex;
  align-items: start;
  justify-content: space-between;
  gap: 1.25rem;
  padding: 1.25rem 1.5rem;
  border-radius: 1.1rem;
  margin-bottom: 1.5rem;
  border: 1px solid rgba(0, 61, 165, 0.12);
  box-shadow: 0 18px 40px rgba(0, 26, 77, 0.12);
}

.login-banner__content {
  display: grid;
  gap: 0.35rem;
}

.login-banner__content h2 {
  margin: 0;
  font-size: 1.15rem;
}

.login-banner__content p {
  margin: 0;
}

.login-banner__close {
  border: none;
  background: transparent;
  color: rgba(15, 23, 42, 0.6);
  font-size: 1.5rem;
  cursor: pointer;
  line-height: 1;
}

.login-banner__close:hover,
.login-banner__close:focus-visible {
  color: rgba(15, 23, 42, 0.9);
}

.login-banner--success {
  background: rgba(16, 185, 129, 0.08);
  border-color: rgba(4, 120, 87, 0.4);
}

.login-banner--info {
  background: rgba(59, 130, 246, 0.08);
  border-color: rgba(37, 99, 235, 0.35);
}

.login-banner--warning {
  background: rgba(250, 204, 21, 0.12);
  border-color: rgba(250, 204, 21, 0.45);
}

.login-banner--error {
  background: rgba(200, 16, 46, 0.08);
  border-color: rgba(200, 16, 46, 0.45);
}
</style>
