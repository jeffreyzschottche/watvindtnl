<script setup lang="ts">
const width = 316;
const height = 266;
const container = ref<HTMLElement | null>(null);
const scale = ref(1);
let observer: ResizeObserver | undefined;

onMounted(() => {
  const element = container.value;
  if (!element) return;
  const resize = () => {
    scale.value = Math.min(1, element.clientWidth / width);
  };
  resize();
  observer = new ResizeObserver(resize);
  observer.observe(element);
});
onBeforeUnmount(() => observer?.disconnect());
</script>

<template>
  <div
    ref="container"
    class="google-test-ad"
    :style="{ height: `${height * scale}px` }"
  >
    <iframe
      src="https://googleads.github.io/google-publisher-tag-samples/display-test-ad/js/demo.html"
      title="Google-testadvertentie"
      :width="width"
      :height="height"
      :style="{ transform: `scale(${scale})` }"
      sandbox="allow-scripts allow-same-origin"
      referrerpolicy="no-referrer"
      loading="lazy"
    />
  </div>
</template>

<style scoped>
.google-test-ad {
  position: relative;
  width: min(100%, 316px);
  overflow: hidden;
  margin-inline: auto;
  background: #fff;
}
.google-test-ad iframe {
  position: absolute;
  inset: 0;
  border: 0;
  transform-origin: top left;
}
</style>
