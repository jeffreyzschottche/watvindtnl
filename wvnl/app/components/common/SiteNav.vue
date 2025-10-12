<template>
  <header class="site-header">
    <div class="container nav-container">
      <NuxtLink to="/" class="brand nl-flag-text outline">WDNL</NuxtLink>
      <button
        class="menu-toggle"
        type="button"
        :aria-expanded="mobileOpen.toString()"
        aria-controls="primary-navigation"
        aria-label="Hoofdmenu"
        @click="toggleMobile"
      >
        <span></span>
        <span></span>
        <span></span>
        <span class="sr-only">Menu</span>
      </button>

      <div class="nav-group" :class="{ open: mobileOpen }">
        <nav id="primary-navigation" class="nav-links" aria-label="Hoofdmenu">
          <NuxtLink
            to="/over-wdnl"
            class="nav-link"
            active-class="link-active"
            @click="closeMobile"
            >Over WDNL</NuxtLink
          >
          <NuxtLink
            to="/issues"
            class="nav-link"
            active-class="link-active"
            @click="closeMobile"
            >Kwesties</NuxtLink
          >
          <NuxtLink
            to="/politiek-kompas"
            class="nav-link"
            active-class="link-active"
            @click="closeMobile"
          >
            Politiek Kompas
          </NuxtLink>
          <NuxtLink
            to="/contact"
            class="nav-link"
            active-class="link-active"
            @click="closeMobile"
            >Contact</NuxtLink
          >
        </nav>
        <div class="auth-links">
          <template v-if="!isLoggedIn">
            <button class="button my-profile">
              <NuxtLink to="/login" @click="closeMobile">Inloggen</NuxtLink>
            </button>
            <button class="button my-profile">
              <NuxtLink to="/register" @click="closeMobile"
                >Registreren</NuxtLink
              >
            </button>
          </template>
          <div v-else>
            <button type="button" class="button my-profile">
              <NuxtLink
                to="/profile"
                active-class="link-active"
                @click="closeMobile"
              >
                Mijn profiel
              </NuxtLink>
            </button>
            <button type="button" class="button" @click="handleLogout">
              Uitloggen
            </button>
          </div>
        </div>
      </div>
    </div>
  </header>
</template>

<script setup>
import { ref, watch } from "vue";
import { storeToRefs } from "pinia";
import { useRoute, useRouter } from "#imports";
import { useAuthStore } from "~/stores/auth";

const mobileOpen = ref(false);

const toggleMobile = () => {
  mobileOpen.value = !mobileOpen.value;
};

const closeMobile = () => {
  mobileOpen.value = false;
};

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();
const { isLoggedIn } = storeToRefs(authStore);

const handleLogout = () => {
  authStore.logout();
  closeMobile();
  router.push("/");
};

watch(
  () => route.fullPath,
  () => {
    mobileOpen.value = false;
  }
);
</script>
