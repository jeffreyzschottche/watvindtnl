<script setup lang="ts">
import BrandLogo from "~/components/brand/BrandLogo.vue";
</script>
<template>
  <footer class="site-footer">
    <div class="container footer-main">
      <div class="footer-intro">
        <NuxtLink to="/" aria-label="WatDenktNederland — home"
          ><BrandLogo
        /></NuxtLink>
        <p>Eén vraag. Twee kanten.<br />Ontdek hoe jij erover denkt.</p>
      </div>
      <div>
        <h2>Ontdek WDNL</h2>
        <NuxtLink to="/stellingen">Alle stellingen</NuxtLink
        ><NuxtLink to="/kompas">Politiek kompas</NuxtLink
        ><NuxtLink to="/over-ons">Over ons</NuxtLink>
      </div>
      <div>
        <h2>Goed om te weten</h2>
        <NuxtLink to="/faq">Veelgestelde vragen</NuxtLink
        ><NuxtLink to="/methodologie">Hoe werken de uitslagen?</NuxtLink
        ><NuxtLink to="/contact">Contact</NuxtLink>
      </div>
    </div>
    <div class="container footer-bottom">
      <span>© {{ new Date().getFullYear() }} WatDenktNederland</span>
      <nav aria-label="Juridische informatie">
        <NuxtLink to="/privacy">Privacy</NuxtLink
        ><NuxtLink to="/voorwaarden">Voorwaarden</NuxtLink>
      </nav>
      <span class="footer-flag" aria-label="Gemaakt voor Nederland"
        ><i /><i /><i
      /></span>
    </div>
  </footer>
</template>
<style scoped>
.site-footer {
  background: #fff;
  border-top: 1px solid #e2e8f0;
  margin-top: auto;
  padding: 3.5rem 0 0;
}
.footer-main {
  display: grid;
  grid-template-columns: 2fr 1fr 1fr;
  gap: 3rem;
  padding-bottom: 3rem;
}
.footer-intro p {
  color: #68758a;
  font-size: 0.85rem;
  line-height: 1.8;
  margin: 1rem 0 0;
}
.footer-main h2 {
  font-size: 0.8rem;
  margin: 0.3rem 0 1rem;
  font-weight: 750;
}
.footer-main > div:not(:first-child) a {
  display: block;
  color: #68758a;
  font-size: 0.8rem;
  margin-top: 0.8rem;
  font-weight: 450;
}
.footer-bottom {
  display: flex;
  align-items: center;
  gap: 1.5rem;
  border-top: 1px solid #edf0f5;
  min-height: 70px;
  font-size: 0.72rem;
  color: #748096;
}
.footer-bottom nav {
  display: flex;
  gap: 1.25rem;
  margin-left: auto;
}
.footer-bottom a {
  color: #68758a;
  font-weight: 450;
}
.footer-flag {
  width: 22px;
  display: grid;
  border-radius: 2px;
  overflow: hidden;
}
.footer-flag i {
  height: 4px;
  background: #e1062e;
}
.footer-flag i:nth-child(2) {
  background: #f2f3f5;
}
.footer-flag i:last-child {
  background: #0057b8;
}
@media (max-width: 720px) {
  .footer-main {
    grid-template-columns: 1fr 1fr;
    gap: 2rem 1rem;
  }
  .footer-intro {
    grid-column: 1/-1;
  }
  .footer-bottom {
    flex-wrap: wrap;
    gap: 0.75rem;
    padding-block: 1.25rem 6.5rem;
  }
  .footer-bottom nav {
    margin-left: 0;
  }
  .footer-flag {
    margin-left: auto;
  }
}
</style>
