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
            >Stem nu</NuxtLink
          >
          <NuxtLink
            to="/news"
            class="nav-link"
            active-class="link-active"
            @click="closeMobile"
            >Moties</NuxtLink
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
            <button
              type="button"
              class="button button-secondary"
              :class="{ 'link-active': isActive('/login') }"
              @click="navigateTo('/login')"
            >
              Inloggen
            </button>
            <button
              type="button"
              class="button"
              :class="{ 'link-active': isActive('/register') }"
              @click="navigateTo('/register')"
            >
              Registreren
            </button>
          </template>
          <div class="auth-links" v-else>
            <button
              type="button"
              class="button button-secondary"
              :class="{ 'link-active': isActive('/profile') }"
              @click="navigateTo('/profile')"
            >
              Mijn profiel
            </button>
            <button
              type="button"
              class="button button-danger"
              @click="handleLogout"
            >
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

const navigateTo = (path) => {
  closeMobile();
  router.push(path);
};

const isActive = (path) => route.path === path;

const handleLogout = () => {
  authStore.logout();
  navigateTo("/");
};

watch(
  () => route.fullPath,
  () => {
    mobileOpen.value = false;
  }
);
</script>
