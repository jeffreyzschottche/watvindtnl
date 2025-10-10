<template>
  <footer class="site-footer">
    <div class="container footer-container">
      <div>
        <strong>WVNL</strong>
        <p class="footer-text">
          Samen bouwen we aan transparante besluitvorming en heldere
          burgerparticipatie.
        </p>
      </div>
      <div class="footer-links">
        <NuxtLink to="/privacy">Privacy</NuxtLink>
        <NuxtLink to="/algemene-voorwaarden">Algemene voorwaarden</NuxtLink>
        <NuxtLink to="/contact">Contact</NuxtLink>
      </div>
      <p class="footer-copy">© {{ new Date().getFullYear() }} Wat Vindt NL</p>
    </div>
  </footer>
</template>
