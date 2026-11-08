<script setup lang="ts">
import { gsap } from "gsap";
import AppIcon from "~/components/ui/AppIcon.vue";
const props = withDefaults(
  defineProps<{ kind?: "neighbourhood" | "compass"; priority?: boolean }>(),
  { kind: "neighbourhood", priority: false },
);
const root = ref<HTMLElement | null>(null);
const { enabled, reducedBySystem, toggle } = useMotionPreferences();
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
    floats.to(
      "[data-float-chip]",
      {
        y: -9,
        rotation: (i: number) => (i % 2 ? 2 : -2),
        duration: 3.6,
        ease: "sine.inOut",
        stagger: 0.2,
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
      <div class="motion-scene__halo" />
      <div class="motion-scene__orbit motion-scene__orbit--one" />
      <div class="motion-scene__orbit motion-scene__orbit--two" />
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
      <span class="scene-chip scene-chip--first" data-float-chip
        ><span class="scene-chip__icon"
          ><AppIcon :name="isCompass ? 'compass' : 'chat'" :size="19" /></span
        >{{ isCompass ? "Jouw eigen richting" : "Wat denk jij?" }}</span
      >
      <span class="scene-chip scene-chip--second" data-float-chip
        ><span class="scene-chip__dot" />{{
          isCompass
            ? "Inzicht begint bij jezelf"
            : "Ruimte voor jouw perspectief"
        }}</span
      >
      <span class="scene-spark scene-spark--one">✦</span
      ><span class="scene-spark scene-spark--two">✧</span>
    </div>
    <button
      class="scene-toggle"
      type="button"
      :disabled="reducedBySystem"
      :aria-pressed="enabled"
      @click="toggle"
    >
      <span aria-hidden="true">{{ enabled ? "Ⅱ" : "▷" }}</span
      >{{
        reducedBySystem
          ? "Minder beweging actief"
          : enabled
            ? "Animatie pauzeren"
            : "Animatie afspelen"
      }}
    </button>
  </div>
</template>
<style scoped>
.motion-scene {
  position: relative;
  min-width: 0;
  isolation: isolate;
}
.motion-scene__world {
  position: relative;
  aspect-ratio: 1 / 1.13;
  perspective: 1100px;
}
.motion-scene__halo {
  position: absolute;
  width: 90%;
  aspect-ratio: 1;
  top: 7%;
  left: 5%;
  border-radius: 50%;
  background: radial-gradient(
    circle at 35% 30%,
    #e8f4ff,
    #d6e8ff 50%,
    #e2eaff66 74%
  );
  box-shadow: inset 0 0 65px #ffffffa8;
  z-index: -2;
}
.motion-scene__orbit {
  position: absolute;
  border: 1px solid #c8d9edb3;
  width: 82%;
  height: 52%;
  top: 24%;
  left: 9%;
  border-radius: 50%;
  pointer-events: none;
  z-index: -1;
}
.motion-scene__orbit--one {
  transform: rotate(-26deg);
}
.motion-scene__orbit--two {
  transform: rotate(29deg);
  width: 74%;
  left: 13%;
  height: 60%;
  top: 20%;
  border-style: dashed;
  opacity: 0.6;
}
.motion-scene__plane {
  position: absolute;
  inset: -4% -5% -2%;
  transform-style: preserve-3d;
}
.motion-scene__plane img {
  width: 100%;
  height: 100%;
  object-fit: contain;
  display: block;
  user-select: none;
  filter: drop-shadow(0 24px 22px #17376918);
}
.scene-chip {
  position: absolute;
  display: inline-flex;
  align-items: center;
  gap: 0.65rem;
  color: #001f4d;
  background: #fffffff2;
  border: 1px solid #fff;
  border-radius: 16px;
  padding: 0.7rem 1rem;
  box-shadow:
    0 12px 30px #16386d12,
    0 2px 5px #16386d06;
  font-size: 0.75rem;
  font-weight: 750;
  backdrop-filter: blur(12px);
}
.scene-chip--first {
  top: 24%;
  left: -6%;
  transform: rotate(-7deg);
}
.scene-chip--second {
  bottom: 13%;
  right: -5%;
  transform: rotate(5deg);
  font-size: 0.65rem;
  padding: 0.85rem 1rem;
}
.scene-chip__icon {
  width: 33px;
  height: 33px;
  border-radius: 11px;
  display: grid;
  place-items: center;
  background: #eaf2ff;
  color: #0057b8;
}
.scene-chip__dot {
  width: 8px;
  height: 8px;
  background: #e1062e;
  border: 3px solid #ffe0e7;
  box-sizing: content-box;
  border-radius: 50%;
}
.scene-spark {
  position: absolute;
  font-size: 30px;
  color: #0057b8;
}
.scene-spark--one {
  top: 5%;
  right: 7%;
  transform: rotate(15deg);
}
.scene-spark--two {
  bottom: 9%;
  left: 2%;
  color: #e1062e;
  font-size: 25px;
}
.scene-toggle {
  position: relative;
  z-index: 3;
  display: flex;
  align-items: center;
  gap: 0.4rem;
  margin: 0.8rem 0 0 auto;
  border: 0;
  background: transparent;
  color: #5b6f88;
  font-size: 0.62rem;
  min-height: 44px;
  padding: 0.3rem 0.5rem;
}
.scene-toggle > span {
  display: grid;
  place-items: center;
  width: 22px;
  height: 22px;
  border: 1px solid #cbd9ea;
  border-radius: 50%;
  color: #42648c;
  font-size: 10px;
}
.motion-scene--compass .motion-scene__world {
  aspect-ratio: 1;
}
.motion-scene--compass .motion-scene__plane {
  inset: -3%;
}
.motion-scene--compass .scene-chip--first {
  top: 12%;
  left: -4%;
}
.motion-scene--compass .scene-chip--second {
  bottom: 9%;
  right: -3%;
}
@media (max-width: 720px) {
  .motion-scene__plane {
    inset: -1% -2%;
  }
  .scene-chip {
    border-radius: 12px;
    padding: 0.5rem 0.65rem;
    font-size: 0.64rem;
    gap: 0.4rem;
  }
  .scene-chip__icon {
    width: 28px;
    height: 28px;
    border-radius: 9px;
  }
  .scene-chip--first {
    left: 0;
  }
  .scene-chip--second {
    right: 0;
    font-size: 0.55rem;
    padding: 0.65rem;
  }
  .scene-toggle {
    margin-top: 0;
  }
  .scene-spark {
    font-size: 22px;
  }
}

@media (max-width: 720px) {
  .motion-scene--compass .motion-scene__plane {
    inset: 0;
  }
  .motion-scene--compass .scene-chip--first {
    left: 0;
  }
  .motion-scene--compass .scene-chip--second {
    right: 0;
  }
}
</style>
