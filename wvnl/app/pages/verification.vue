<template>
  <div class="verification-page">
    <section class="container narrow">
      <div class="verification-card" :class="`verification-card--${copy.variant}`">
        <h1>{{ copy.title }}</h1>
        <p>{{ copy.message }}</p>
        <p class="verification-card__hint">Je wordt zo doorgestuurd naar de loginpagina.</p>
        <NuxtLink class="verification-card__link" to="/login">
          Ga nu naar login
        </NuxtLink>
      </div>
    </section>
  </div>
</template>

<script setup lang="ts">
const route = useRoute();
const router = useRouter();

const status = computed(() => {
  const value = route.query.status;
  return typeof value === "string" ? value : "success";
});

const copy = computed(() => {
  switch (status.value) {
    case "success":
      return {
        title: "Dankjewel!",
        message: "Je e-mailadres is bevestigd. Je kunt nu inloggen met je gegevens.",
        variant: "success",
        redirect: "success",
      } as const;
    case "already-verified":
      return {
        title: "Je was al bevestigd",
        message: "We herkennen je account als geverifieerd. Log gerust in om verder te gaan.",
        variant: "info",
        redirect: "already-verified",
      } as const;
    case "invalid":
      return {
        title: "Link verlopen of ongeldig",
        message: "Het lijkt erop dat deze verificatielink niet (meer) werkt. Vraag een nieuwe bevestigingsmail aan via de loginpagina.",
        variant: "error",
        redirect: "invalid",
      } as const;
    default:
      return {
        title: "We verwerken je verificatie",
        message: "Je verzoek is ontvangen. Als er iets misgaat, kun je via de loginpagina een nieuwe e-mail laten sturen.",
        variant: "info",
        redirect: status.value,
      } as const;
  }
});

onMounted(() => {
  window.setTimeout(() => {
    router.replace({ path: "/login", query: { verification: copy.value.redirect } });
  }, 2000);
});
</script>

<style scoped>
.verification-page {
  min-height: calc(100vh - 180px);
  display: grid;
  align-items: center;
  padding: 4rem 1rem;
  background: linear-gradient(135deg, rgba(0, 61, 165, 0.12), rgba(200, 16, 46, 0.1));
}

.verification-card {
  background: #fff;
  border-radius: 1.5rem;
  padding: 2.5rem;
  text-align: center;
  box-shadow: 0 24px 60px rgba(0, 26, 77, 0.18);
  border: 1px solid rgba(0, 61, 165, 0.12);
  display: grid;
  gap: 1.25rem;
}

.verification-card h1 {
  margin: 0;
  font-size: clamp(1.8rem, 3vw, 2.4rem);
}

.verification-card p {
  margin: 0;
  font-size: 1.05rem;
  line-height: 1.6;
}

.verification-card--success {
  border-color: rgba(4, 120, 87, 0.4);
}

.verification-card--info {
  border-color: rgba(37, 99, 235, 0.35);
}

.verification-card--error {
  border-color: rgba(200, 16, 46, 0.45);
}

.verification-card__hint {
  color: rgba(15, 23, 42, 0.7);
  font-size: 0.95rem;
}

.verification-card__link {
  justify-self: center;
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  padding: 0.65rem 1.5rem;
  border-radius: 999px;
  background: linear-gradient(130deg, var(--color-accent, #c8102e), var(--color-primary, #003da5));
  color: #fff;
  font-weight: 700;
}

.verification-card__link:hover,
.verification-card__link:focus-visible {
  opacity: 0.9;
}
</style>
