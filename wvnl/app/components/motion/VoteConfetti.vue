<script setup lang="ts">
import { gsap } from "gsap";
const props = defineProps<{ trigger: number }>();
const { enabled } = useMotionPreferences();
const root = ref<HTMLElement | null>(null);
const playing = ref(false);
const colors = ["#0057b8", "#e1062e", "#65b7ea", "#edb84f", "#001f4d"];
const pieces = Array.from({ length: 44 }, (_, i) => ({
  color: colors[i % colors.length],
  round: i % 4 === 0,
}));
let timeline: gsap.core.Timeline | undefined;
let disposed = false;
function stop() {
  timeline?.kill();
  playing.value = false;
}
watch(
  () => props.trigger,
  async (value) => {
    stop();
    if (!value || !enabled.value) return;
    playing.value = true;
    await nextTick();
    if (disposed || !enabled.value || !root.value) {
      stop();
      return;
    }
    const elements = root.value.querySelectorAll("i");
    const width = root.value.clientWidth;
    timeline = gsap.timeline({
      onComplete: () => {
        playing.value = false;
      },
    });
    elements.forEach((element, i) => {
      const left = i % 2 === 0;
      const drift =
        (((i * 31) % 145) + 25) * (left ? 1 : -1) * Math.min(width / 450, 1);
      const duration = 1.05 + (i % 5) * 0.07;
      timeline!.set(
        element,
        {
          x: width * (left ? 0.22 : 0.78),
          y: 150,
          opacity: 1,
          rotation: i * 23,
        },
        0,
      );
      timeline!.to(
        element,
        {
          x: `+=${drift}`,
          rotation: `+=${210 + i * 11}`,
          rotationY: i % 2 ? 180 : 360,
          duration,
          ease: "power1.out",
        },
        0,
      );
      timeline!.to(
        element,
        { y: 12 + ((i * 13) % 78), duration: 0.36, ease: "power2.out" },
        0,
      );
      timeline!.to(
        element,
        {
          y: 250 + (i % 6) * 12,
          opacity: 0,
          duration: duration - 0.36,
          ease: "power1.in",
        },
        0.36,
      );
    });
  },
);
watch(enabled, (value) => {
  if (!value) stop();
});
onBeforeUnmount(() => {
  disposed = true;
  stop();
});
</script>
<template>
  <div
    v-if="playing"
    ref="root"
    class="vote-confetti"
    aria-hidden="true"
    data-testid="vote-confetti"
  >
    <i
      v-for="(piece, index) in pieces"
      :key="index"
      :style="{
        backgroundColor: piece.color,
        borderRadius: piece.round ? '50%' : '1px',
      }"
    />
  </div>
</template>
<style scoped>
.vote-confetti {
  position: absolute;
  inset: -35px -8px auto;
  height: 290px;
  overflow: hidden;
  pointer-events: none;
  z-index: 2;
  contain: layout paint;
}
.vote-confetti i {
  position: absolute;
  top: 0;
  left: 0;
  width: 7px;
  height: 10px;
  will-change: transform, opacity;
}
</style>
