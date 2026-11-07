<template>
  <section class="auth-shell">
    <div v-if="banner" class="auth-banner" role="status">
      <strong>{{ banner.title }}</strong>
      <p>{{ banner.message }}</p>
      <button type="button" aria-label="Melding sluiten" @click="dismissBanner">
        ×
      </button>
    </div>
    <loginScreen />
  </section>
</template>
<script setup lang="ts">
definePageMeta({ alias: "/inloggen" });
import loginScreen from "~/components/loginScreen.vue";
const { isLoggedIn } = storeToRefs(useAuthStore());
const router = useRouter();
const route = useRoute();

const title = "Inloggen | Wat Denkt Nederland";
const description =
  "Log in bij Wat Denkt Nederland om verder te gaan met stemmen op actuele politieke moties en het beheren van je profiel.";

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
            message:
              "Je e-mailadres was al bevestigd. Log gerust in om verder te gaan.",
            variant: "info",
          };
          return;
        case "invalid":
          banner.value = {
            title: "Verificatielink verlopen",
            message:
              "Vraag een nieuwe bevestigingsmail aan als je nog niet kunt inloggen.",
            variant: "error",
          };
          return;
        default:
          banner.value = {
            title: "Verificatie bijgewerkt",
            message:
              "Controleer je inbox. Als het niet lukt, kun je hieronder opnieuw een mail laten sturen.",
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
            message:
              "Volg de instructies in je e-mail om je wachtwoord opnieuw in te stellen.",
            variant: "info",
          };
          return;
      }
    }

    banner.value = null;
  },
  { immediate: true },
);

watchEffect(() => {
  if (isLoggedIn.value) router.replace("/spel");
});

function dismissBanner() {
  banner.value = null;
}
</script>
<style scoped>
.auth-banner {
  position: relative;
  background: #edf3ff;
  border: 1px solid #d3e1f6;
  border-radius: 12px;
  padding: 1.2rem 2.5rem 1.2rem 1.2rem;
  margin-bottom: 1.5rem;
  font-size: 0.83rem;
}
.auth-banner p {
  margin: 0.4rem 0 0;
  color: #56657b;
}
.auth-banner button {
  position: absolute;
  top: 0.5rem;
  right: 0.5rem;
  border: 0;
  background: none;
  color: #001f4d;
  font-size: 1.4rem;
}
</style>
