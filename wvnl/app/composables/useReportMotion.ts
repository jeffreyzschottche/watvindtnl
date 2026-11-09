import { gsap } from "gsap";
import type { Ref } from "vue";

/** Finite, scoped decoration. Scores remain readable without JavaScript or motion. */
export function useReportMotion(
  root: Ref<HTMLElement | null>,
  identity: () => string,
) {
  const { enabled } = useMotionPreferences();
  const mounted = ref(false);
  let context: gsap.Context | undefined;
  let observer: IntersectionObserver | undefined;
  let generation = 0;
  let disposed = false;

  function clean() {
    observer?.disconnect();
    context?.revert();
    observer = undefined;
    context = undefined;
  }

  async function setup() {
    const current = ++generation;
    clean();
    await nextTick();
    const scope = root.value;
    if (
      disposed ||
      current !== generation ||
      !mounted.value ||
      !enabled.value ||
      !scope
    )
      return;
    context = gsap.context(() => {}, scope);
    const reveal = (element: Element) => {
      if (disposed || current !== generation) return;
      context?.add(() => {
        gsap.fromTo(
          element,
          { y: 14, opacity: 0.65 },
          {
            y: 0,
            opacity: 1,
            duration: 0.5,
            ease: "power2.out",
            clearProps: "transform,opacity",
          },
        );
        const bars = element.querySelectorAll("[data-report-bar]");
        if (bars.length)
          gsap.fromTo(
            bars,
            { scaleX: 0, transformOrigin: "left center" },
            {
              scaleX: 1,
              duration: 0.9,
              stagger: 0.065,
              ease: "power3.out",
              clearProps: "transform,transformOrigin",
            },
          );
        const wheel = element.querySelector("[data-report-wheel]");
        if (wheel)
          gsap.fromTo(
            wheel,
            { scale: 0.95, rotation: -4, transformOrigin: "center" },
            {
              scale: 1,
              rotation: 0,
              duration: 0.85,
              ease: "power2.out",
              clearProps: "transform,transformOrigin",
            },
          );
        const points = element.querySelectorAll("[data-report-point]");
        if (points.length)
          gsap.fromTo(
            points,
            { x: 8, opacity: 0.5 },
            {
              x: 0,
              opacity: 1,
              duration: 0.45,
              stagger: 0.08,
              ease: "power2.out",
              clearProps: "transform,opacity",
            },
          );
      });
    };
    const elements = scope.querySelectorAll("[data-report-reveal]");
    if (!("IntersectionObserver" in window)) {
      elements.forEach(reveal);
      return;
    }
    observer = new IntersectionObserver(
      (entries) => {
        for (const entry of entries) {
          if (!entry.isIntersecting) continue;
          observer?.unobserve(entry.target);
          reveal(entry.target);
        }
      },
      { threshold: 0.12 },
    );
    elements.forEach((element) => observer?.observe(element));
  }

  onMounted(() => {
    mounted.value = true;
    setup();
  });
  watch([enabled, identity], setup);
  onBeforeUnmount(() => {
    disposed = true;
    generation++;
    clean();
  });
  return { enabled: computed(() => mounted.value && enabled.value) };
}
