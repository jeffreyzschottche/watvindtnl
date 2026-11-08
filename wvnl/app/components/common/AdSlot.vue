<script setup lang="ts">
import DemoAdDialog from "~/components/common/DemoAdDialog.vue";
import { adMode } from "~/utils/ads";
withDefaults(defineProps<{ placement?: string; compact?: boolean }>(), {
  placement: "in-content",
  compact: false,
});
const config = useRuntimeConfig();
const mode = computed(() =>
  adMode(config.public.demoAds, config.public.adsEnabled, false),
);
const open = ref(false);
</script>
<template>
  <aside
    v-if="mode === 'demo'"
    class="wdnl-demo-placement"
    :class="{ 'wdnl-demo-placement--compact': compact }"
    :data-placement="placement"
    aria-label="Advertentieplaatsing (demo)"
  >
    <div class="wdnl-demo-placement__label">
      <span>Advertentie · demo</span><span aria-hidden="true">AD</span>
    </div>
    <div class="wdnl-demo-placement__content">
      <span class="wdnl-demo-placement__art" aria-hidden="true">✦</span>
      <div>
        <strong>Hier krijgt een adverteerder de ruimte.</strong>
        <p>Een voorbeeldplaatsing, zonder tracking.</p>
      </div>
      <button type="button" @click="open = true">
        Bekijk voorbeeld <span aria-hidden="true">↗</span>
      </button>
    </div>
    <DemoAdDialog :open="open" @close="open = false" />
  </aside>
</template>
<style scoped>
.wdnl-demo-placement {
  width: 100%;
  min-width: 0;
  border: 1px dashed #aebfd6;
  background: linear-gradient(115deg, #f0f5fd, #fff);
  border-radius: 18px;
  padding: 1rem 1.25rem;
  text-align: left;
  color: #526277;
}
.wdnl-demo-placement__label {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.7rem;
  font-size: 0.63rem;
  text-transform: uppercase;
  letter-spacing: 0.1em;
}
.wdnl-demo-placement__label > span:last-child {
  border: 1px solid #c6d2e2;
  border-radius: 4px;
  padding: 0 0.3rem;
}
.wdnl-demo-placement__content {
  display: flex;
  align-items: center;
  gap: 1rem;
}
.wdnl-demo-placement__content > div {
  flex: 1;
  min-width: 0;
}
.wdnl-demo-placement__art {
  font-size: 32px;
  color: #7b9ac1;
  background: #e5edfa;
  border-radius: 13px;
  width: 54px;
  height: 54px;
  flex-shrink: 0;
  display: grid;
  place-items: center;
}
.wdnl-demo-placement strong {
  display: block;
  font-size: 0.83rem;
  color: #324e73;
}
.wdnl-demo-placement p {
  font-size: 0.72rem;
  margin: 0.2rem 0 0;
}
.wdnl-demo-placement button {
  border: 1px solid #c8d6e8;
  background: white;
  color: #365c8c;
  border-radius: 10px;
  min-height: 44px;
  padding: 0.5rem 0.8rem;
  font-size: 0.71rem;
  white-space: nowrap;
}
.wdnl-demo-placement--compact .wdnl-demo-placement__art {
  display: none;
}
@media (max-width: 540px) {
  .wdnl-demo-placement {
    padding: 1rem;
  }
  .wdnl-demo-placement__content {
    flex-wrap: wrap;
    gap: 0.7rem;
  }
  .wdnl-demo-placement__art {
    display: none;
  }
  .wdnl-demo-placement__content > div {
    flex-basis: 100%;
  }
}
</style>
