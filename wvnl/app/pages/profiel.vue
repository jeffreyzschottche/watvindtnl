<script setup lang="ts">
definePageMeta({ middleware: "auth" });
import AppIcon from "~/components/ui/AppIcon.vue";
const auth = useAuthStore();
const name = computed(() => auth.user?.username || auth.user?.name || "Welkom");
const since = computed(() => {
  const value = auth.user?.created_at;
  return value
    ? new Intl.DateTimeFormat("nl-NL", {
        month: "long",
        year: "numeric",
      }).format(new Date(value))
    : null;
});
async function logout() {
  await auth.logout();
  await navigateTo("/inloggen");
}
useSeoMeta({
  title: "Mijn profiel | WatDenktNederland",
  robots: "noindex, nofollow",
});
</script>
<template>
  <section class="profile-home container">
    <header class="profile-home__header">
      <div>
        <p class="eyebrow">Jouw plek op WDNL</p>
        <h1>Mijn profiel</h1>
        <p>Je mening, je inzichten en je account. Alles bij elkaar.</p>
      </div>
      <button type="button" class="profile-home__logout" @click="logout">
        Uitloggen →
      </button>
    </header>
    <div class="profile-home__identity" data-reveal>
      <span class="profile-home__avatar">{{
        name.slice(0, 1).toUpperCase()
      }}</span>
      <div>
        <h2>{{ name }}</h2>
        <p>{{ since ? `Lid sinds ${since}` : "Jouw WDNL-account" }}</p>
      </div>
      <div class="profile-home__count">
        <strong>{{ auth.user?.voted_issue_ids?.length ?? 0 }}</strong
        ><span>Beantwoorde stellingen</span>
      </div>
    </div>
    <div class="profile-home__grid" data-reveal>
      <NuxtLink to="/mijn-stemmen"
        ><span class="profile-home__icon"><AppIcon name="grid" /></span>
        <h2>Mijn stemmen</h2>
        <p>Bekijk je keuzes en eerdere stellingen.</p>
        <span class="profile-home__arrow"
          >Bekijk je stemgeschiedenis
          <AppIcon name="arrow" :size="16" /></span></NuxtLink
      ><NuxtLink
        :to="
          auth.user?.premium ? '/profiel/instellingen?tab=compass' : '/kompas'
        "
        ><span class="profile-home__icon"><AppIcon name="compass" /></span>
        <h2>Mijn kompas</h2>
        <p>Ontdek meer over je politieke standpunten.</p>
        <span class="profile-home__arrow"
          >{{ auth.user?.premium ? "Bekijk je kompas" : "Ontdek het kompas" }}
          <AppIcon name="arrow" :size="16" /></span
      ></NuxtLink>
    </div>
    <h2 class="profile-home__subheading">Je account beheren</h2>
    <div class="profile-home__settings" data-reveal>
      <NuxtLink to="/profiel/instellingen?tab=profile"
        ><AppIcon name="user" /><span
          ><strong>Persoonlijke gegevens</strong
          ><small>Je profiel en voorkeuren aanpassen</small></span
        ><AppIcon name="chevron" :size="17" /></NuxtLink
      ><NuxtLink to="/profiel/instellingen?tab=security"
        ><AppIcon name="lock" /><span
          ><strong>Beveiliging</strong
          ><small>Je wachtwoord wijzigen</small></span
        ><AppIcon name="chevron" :size="17" /></NuxtLink
      ><NuxtLink to="/privacy"
        ><AppIcon name="shield" /><span
          ><strong>Privacy en je gegevens</strong
          ><small>Lees hoe je gegevens worden verwerkt</small></span
        ><AppIcon name="chevron" :size="17"
      /></NuxtLink>
    </div>
  </section>
</template>
<style scoped>
.profile-home {
  max-width: 900px;
  padding-block: 48px 65px;
}
.profile-home__header {
  display: flex;
  justify-content: space-between;
  gap: 1rem;
  align-items: center;
  margin-bottom: 2rem;
}
.profile-home__header h1 {
  margin: 0.5rem 0 0.75rem;
  font-size: 2.4rem;
}
.profile-home__header p:last-child {
  font-size: 0.88rem;
  color: #63738a;
}
.profile-home__logout {
  white-space: nowrap;
  flex-shrink: 0;
  border: 0;
  background: none;
  color: #63738a;
  font-size: 0.75rem;
  min-height: 44px;
}
.profile-home__identity {
  display: flex;
  align-items: center;
  gap: 1rem;
  border: 1px solid #dce3ee;
  border-radius: 14px;
  padding: 1.6rem;
  background: #fff;
}
.profile-home__avatar {
  display: grid;
  place-items: center;
  width: 58px;
  height: 58px;
  border-radius: 50%;
  color: #0057b8;
  background: #eaf1ff;
  font-size: 1.5rem;
  font-weight: 700;
}
.profile-home__identity h2 {
  font-size: 1.15rem;
  margin: 0 0 0.3rem;
}
.profile-home__identity p {
  color: #63738a;
  font-size: 0.75rem;
  margin: 0;
}
.profile-home__count {
  display: grid;
  gap: 0.2rem;
  text-align: right;
  margin-left: auto;
  padding-left: 1.5rem;
  border-left: 1px solid #e2e8f1;
}
.profile-home__count strong {
  font-size: 1.9rem;
  letter-spacing: -0.04em;
  line-height: 1;
}
.profile-home__count span {
  font-size: 0.65rem;
  color: #63738a;
}
.profile-home__grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
  margin-top: 1rem;
}
.profile-home__grid > a {
  border: 1px solid #dce3ee;
  border-radius: 14px;
  padding: 1.5rem;
  background: #fff;
  color: #001f4d;
  text-decoration: none;
}
.profile-home__grid > a:hover {
  border-color: #9ebae1;
}
.profile-home__icon {
  background: #edf3ff;
  display: grid;
  place-items: center;
  width: 43px;
  height: 43px;
  border-radius: 10px;
  color: #0057b8;
}
.profile-home__grid h2 {
  font-size: 1.2rem;
  margin: 1rem 0 0.5rem;
}
.profile-home__grid p {
  color: #63738a;
  font-size: 0.8rem;
  font-weight: 400;
}
.profile-home__arrow {
  font-size: 0.73rem;
  color: #0057b8;
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 1.2rem;
}
.profile-home__subheading {
  font-size: 1.1rem;
  margin: 2rem 0 1rem;
}
.profile-home__settings {
  background: #fff;
  border: 1px solid #dce3ee;
  border-radius: 14px;
  overflow: hidden;
}
.profile-home__settings > a {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1.1rem 1.4rem;
  color: #001f4d;
  text-decoration: none;
}
.profile-home__settings > a + a {
  border-top: 1px solid #e8edf4;
}
.profile-home__settings > a:hover {
  background: #fafcff;
}
.profile-home__settings span {
  flex: 1;
  display: grid;
}
.profile-home__settings strong {
  font-size: 0.85rem;
}
.profile-home__settings small {
  font-size: 0.73rem;
  color: #63738a;
  font-weight: 400;
}
@media (max-width: 720px) {
  .profile-home {
    padding-block: 32px 45px;
  }
  .profile-home__header {
    align-items: flex-start;
  }
  .profile-home__header h1 {
    font-size: 2rem;
  }
  .profile-home__identity {
    padding: 1rem;
    flex-wrap: wrap;
  }
  .profile-home__count {
    padding-left: 0.7rem;
  }
  .profile-home__count span {
    max-width: 75px;
  }
  .profile-home__grid {
    gap: 0.75rem;
  }
  .profile-home__grid > a {
    padding: 1rem;
  }
  .profile-home__arrow {
    font-size: 0.65rem;
  }
  .profile-home__grid h2 {
    font-size: 1.05rem;
  }
}
</style>
