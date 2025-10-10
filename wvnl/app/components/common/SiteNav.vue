<template>
  <header class="site-header">
    <div class="container nav-container">
      <NuxtLink to="/" class="brand">WVNL</NuxtLink>

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
          <NuxtLink to="/" exact-active-class="link-active" @click="closeMobile">Home</NuxtLink>
          <NuxtLink to="/over-wvnl" active-class="link-active" @click="closeMobile"
            >Over WVNL</NuxtLink
          >
          <NuxtLink to="/issues" active-class="link-active" @click="closeMobile"
            >Kwesties</NuxtLink
          >
          <NuxtLink to="/privacy" active-class="link-active" @click="closeMobile"
            >Privacy</NuxtLink
          >
          <NuxtLink
            to="/algemene-voorwaarden"
            active-class="link-active"
            @click="closeMobile"
          >
            Voorwaarden
          </NuxtLink>
          <NuxtLink to="/contact" active-class="link-active" @click="closeMobile"
            >Contact</NuxtLink
          >
        </nav>
        <div v-if="!isLoggedIn" class="auth-links">
          <NuxtLink to="/login" class="button button-secondary" @click="closeMobile"
            >Inloggen</NuxtLink
          >
          <NuxtLink to="/register" class="button" @click="closeMobile"
            >Registreren</NuxtLink
          >
        </div>
      </div>
    </div>
  </header>
</template>

<script setup>
import { ref, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { useRoute } from '#imports'
import { useAuthStore } from '~/stores/auth'

const mobileOpen = ref(false)

const toggleMobile = () => {
  mobileOpen.value = !mobileOpen.value
}

const closeMobile = () => {
  mobileOpen.value = false
}

const route = useRoute()
const { isLoggedIn } = storeToRefs(useAuthStore())

watch(
  () => route.fullPath,
  () => {
    mobileOpen.value = false
  }
)
</script>
