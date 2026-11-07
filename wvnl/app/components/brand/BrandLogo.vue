<script setup lang="ts">
withDefaults(defineProps<{ compact?: boolean }>(), { compact: false });
</script>
<template>
  <span class="wdnl-logo">
    <img src="/brand/wdnl-mark-concept.svg" alt="" width="39" height="44" />
    <span v-if="!compact" class="wdnl-logo__name"
      >WatDenkt<span>Nederland</span></span
    >
  </span>
</template>
<style scoped>
.wdnl-logo {
  display: inline-flex;
  align-items: center;
  gap: 0.55rem;
  white-space: nowrap;
  color: #001f4d;
}
.wdnl-logo img {
  flex: 0 0 auto;
  object-fit: contain;
}
.wdnl-logo__name {
  font-size: 1.22rem;
  font-weight: 850;
  letter-spacing: -0.055em;
}
.wdnl-logo__name span {
  color: #0057b8;
}
@media (max-width: 380px) {
  .wdnl-logo__name {
    font-size: 1.03rem;
  }
  .wdnl-logo img {
    width: 31px;
    height: 37px;
  }
}
</style>
