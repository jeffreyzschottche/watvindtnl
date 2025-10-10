<template>
  <div class="site-wrapper">
    <SiteNav />
    <main class="site-main">
      <slot />
    </main>
    <SiteFooter />
  </div>
</template>

<script setup lang="ts">
import SiteNav from "~/components/common/SiteNav.vue";
import SiteFooter from "~/components/common/SiteFooter.vue";
</script>
