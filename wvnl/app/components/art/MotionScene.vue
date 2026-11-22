<script setup lang="ts">
import { gsap } from "gsap";
const props = withDefaults(
  defineProps<{ kind?: "neighbourhood" | "compass"; priority?: boolean }>(),
  { kind: "neighbourhood", priority: false },
);
const root = ref<HTMLElement | null>(null);
const { enabled } = useMotionPreferences();
const isCompass = computed(() => props.kind === "compass");
let context: gsap.Context | undefined;
let teardown: (() => void) | undefined;
let disposed = false;
function clean() {
  teardown?.();
  teardown = undefined;
  context?.revert();
  context = undefined;
}
async function setup() {
  await nextTick();
  if (disposed) return;
  clean();
  const element = root.value;
  if (!element || !enabled.value) return;
  let floats: gsap.core.Timeline;
  let rotateX: ReturnType<typeof gsap.quickTo>;
  let rotateY: ReturnType<typeof gsap.quickTo>;
  context = gsap.context(() => {
    floats = gsap.timeline({ repeat: -1, yoyo: true, paused: true });
    floats.to(
      "[data-float-art]",
      {
        y: -12,
        rotation: isCompass.value ? 2 : -0.7,
        duration: 3.6,
        ease: "sine.inOut",
      },
      0,
    );
    const plane = element.querySelector("[data-parallax]");
    rotateX = gsap.quickTo(plane, "rotationX", {
      duration: 0.7,
      ease: "power2.out",
    });
    rotateY = gsap.quickTo(plane, "rotationY", {
      duration: 0.7,
      ease: "power2.out",
    });
  }, element);
  let visible = true;
  const play = () => {
    if (visible && !document.hidden) floats.play();
    else floats.pause();
  };
  const observer =
    typeof IntersectionObserver !== "undefined"
      ? new IntersectionObserver((entries) => {
          visible = entries[0]?.isIntersecting ?? false;
          play();
        })
      : null;
  observer?.observe(element);
  if (!observer) play();
  const move = (event: PointerEvent) => {
    if (event.pointerType !== "mouse") return;
    const box = element.getBoundingClientRect();
    const x = Math.max(
      -0.5,
      Math.min(0.5, (event.clientX - box.left) / box.width - 0.5),
    );
    const y = Math.max(
      -0.5,
      Math.min(0.5, (event.clientY - box.top) / box.height - 0.5),
    );
    rotateX(-y * 9);
    rotateY(x * 10);
  };
  const leave = () => {
    rotateX(0);
    rotateY(0);
  };
  element.addEventListener("pointermove", move);
  element.addEventListener("pointerleave", leave);
  document.addEventListener("visibilitychange", play);
  teardown = () => {
    observer?.disconnect();
    element.removeEventListener("pointermove", move);
    element.removeEventListener("pointerleave", leave);
    document.removeEventListener("visibilitychange", play);
  };
}
onMounted(setup);
watch([enabled, () => props.kind], setup);
onBeforeUnmount(() => {
  disposed = true;
  clean();
});
</script>
<template>
  <div
    ref="root"
    class="motion-scene"
    :class="{ 'motion-scene--compass': isCompass }"
    :data-scene="kind"
    :data-animated="enabled ? 'true' : 'false'"
  >
    <div class="motion-scene__world" aria-hidden="true">
      <div class="motion-scene__plane" data-parallax>
        <img
          data-float-art
          :src="
            isCompass
              ? '/illustrations/compass-v2.webp'
              : '/illustrations/dutch-island-v2.webp'
          "
          alt=""
          :width="isCompass ? 850 : 900"
          :height="isCompass ? 850 : 1125"
          :loading="priority ? 'eager' : 'lazy'"
          :fetchpriority="priority ? 'high' : 'auto'"
          draggable="false"
        />
      </div>
    </div>
  </div>
</template>
<style scoped>
.motion-scene {
  isolation: isolate;
  min-width: 0;
}
.motion-scene__world {
  position: relative;
  aspect-ratio: 1.05;
  perspective: 1100px;
}
.motion-scene__plane {
  position: absolute;
  inset: -4%;
  transform-style: preserve-3d;
}
.motion-scene__plane img {
  display: block;
  width: 100%;
  height: 100%;
  object-fit: contain;
  user-select: none;
  filter: drop-shadow(0 20px 24px #1737691a);
}
.motion-scene--compass .motion-scene__plane {
  inset: 0;
}
</style>
