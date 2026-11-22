<script setup lang="ts">
/**
 * One responsive AdSense display unit. Google's published consent message (CMP)
 * loads with the same tag and decides which ads may be shown; we never pass
 * votes, statements or other political data to the ad request.
 */
import { loadAdSense } from "~/utils/adConsent";
const props = defineProps<{ client: string; adSlot: string }>();

onMounted(() => {
  loadAdSense(props.client);
  try {
    const w = window as unknown as { adsbygoogle?: unknown[] };
    (w.adsbygoogle = w.adsbygoogle || []).push({});
  } catch {
    /* An ad that cannot load must never break the page. */
  }
});
</script>

<template>
  <aside class="adsense-unit" aria-label="Advertentie">
    <span class="adsense-unit__label">Advertentie</span>
    <ins
      class="adsbygoogle"
      style="display: block"
      :data-ad-client="client"
      :data-ad-slot="adSlot"
      data-ad-format="auto"
      data-full-width-responsive="true"
    />
  </aside>
</template>

<style scoped>
.adsense-unit {
  width: 100%;
  min-width: 0;
}
.adsense-unit__label {
  display: block;
  margin-bottom: 0.4rem;
  color: #8593a6;
  font-size: 0.65rem;
  letter-spacing: 0.1em;
  text-transform: uppercase;
}
.adsense-unit:has(ins[data-ad-status="unfilled"]) {
  display: none;
}
</style>
