<template>
  <div class="site-wrapper">
    <a href="#main-content" class="skip-link">Naar inhoud</a>
    <SiteNav />
    <main
      ref="main"
      :data-motion="enabled ? 'on' : 'off'"
      id="main-content"
      class="site-main"
      tabindex="-1"
    >
      <slot />
    </main>
    <SiteFooter />
  </div>
</template>
<script setup lang="ts">
const main = ref<HTMLElement | null>(null);
const { enabled } = usePageMotion(main);
import SiteNav from "~/components/common/SiteNav.vue";
import SiteFooter from "~/components/common/SiteFooter.vue";
</script>
