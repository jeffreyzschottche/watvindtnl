<template>
  <aside v-if="mode === 'demo'" class="ad-slot" aria-label="Advertentieplaatsing (demo)">
    <span>Advertentie · demo</span><p>Ruimte voor een advertentie</p>
  </aside>
</template>
<script setup lang="ts">
import { adMode } from "~/utils/ads";
const config = useRuntimeConfig();
// Live provider is deliberately closed until a certified CMP adapter exists.
const mode = computed(() => adMode(config.public.demoAds, config.public.adsEnabled, false));
</script>
<style scoped>
.ad-slot { border: 1px dashed #bac8dc; background: #f5f7fa; border-radius: 12px; padding: 1.5rem; text-align: center; color: #526277; }
.ad-slot span { font-size: .7rem; text-transform: uppercase; letter-spacing: .1em; }.ad-slot p { margin: .6rem 0 0; font-size: .85rem; }
</style>
