<template>
  <header class="site-header">
    <div class="container nav-container">
      <NuxtLink to="/" class="brand">WVNL</NuxtLink>
      <nav class="nav-links" aria-label="Hoofdmenu">
        <NuxtLink to="/" exact-active-class="link-active">Home</NuxtLink>
        <NuxtLink to="/over-wvnl" active-class="link-active">Over WVNL</NuxtLink>
        <NuxtLink to="/privacy" active-class="link-active">Privacy</NuxtLink>
        <NuxtLink to="/algemene-voorwaarden" active-class="link-active">Voorwaarden</NuxtLink>
        <NuxtLink to="/contact" active-class="link-active">Contact</NuxtLink>
      </nav>
      <div class="auth-links">
        <NuxtLink to="/login" class="button button-secondary">Inloggen</NuxtLink>
        <NuxtLink to="/register" class="button">Registreren</NuxtLink>
      </div>
    </div>
  </header>
</template>
