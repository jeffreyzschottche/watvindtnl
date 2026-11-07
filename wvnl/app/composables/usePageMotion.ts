import { gsap } from "gsap";
import type { Ref } from "vue";

/** Progressive decoration: content remains readable if animation is disabled or unavailable. */
export function usePageMotion(root: Ref<HTMLElement | null>) {
  const { enabled } = useMotionPreferences();
  const app = useNuxtApp();
  let context: gsap.Context | undefined;
  let intersection: IntersectionObserver | undefined;
  let mutations: MutationObserver | undefined;
  let detachHover: (() => void) | undefined;
  let disposed = false;

  function clean() {
    intersection?.disconnect();
    mutations?.disconnect();
    detachHover?.();
    context?.revert();
    context = undefined;
  }
  async function refresh() {
    await nextTick();
    if (disposed) return;
    clean();
    const scope = root.value;
    if (!scope || !enabled.value || !("IntersectionObserver" in window)) return;
    context = gsap.context(() => {}, scope);
    const seen = new WeakSet<Element>();
    intersection = new IntersectionObserver(
      (entries) => {
        let order = 0;
        for (const entry of entries) {
          if (!entry.isIntersecting) continue;
          intersection?.unobserve(entry.target);
          context?.add(() =>
            gsap.fromTo(
              entry.target,
              { opacity: 0, y: 18 },
              {
                opacity: 1,
                y: 0,
                duration: 0.55,
                delay: (order++ % 4) * 0.06,
                ease: "power2.out",
                clearProps: "opacity,transform",
              },
            ),
          );
        }
      },
      { threshold: 0.08, rootMargin: "0px 0px 24px 0px" },
    );
    const observe = () => {
      scope.querySelectorAll("[data-reveal]").forEach((element) => {
        if (seen.has(element)) return;
        seen.add(element);
        intersection?.observe(element);
      });
    };
    observe();
    mutations = new MutationObserver(observe);
    mutations.observe(scope, { childList: true, subtree: true });
    const hover = (event: PointerEvent, entering: boolean) => {
      if (event.pointerType !== "mouse" || !(event.target instanceof Element))
        return;
      const target = event.target.closest<HTMLElement>("[data-lift]");
      if (
        !target ||
        !scope.contains(target) ||
        (event.relatedTarget instanceof Node &&
          target.contains(event.relatedTarget))
      )
        return;
      context?.add(() =>
        gsap.to(target, {
          y: entering ? -4 : 0,
          duration: 0.2,
          ease: "power2.out",
          overwrite: "auto",
        }),
      );
    };
    const over = (e: PointerEvent) => hover(e, true);
    const out = (e: PointerEvent) => hover(e, false);
    scope.addEventListener("pointerover", over);
    scope.addEventListener("pointerout", out);
    detachHover = () => {
      scope.removeEventListener("pointerover", over);
      scope.removeEventListener("pointerout", out);
    };
  }
  const unhook = app.hook("page:finish", refresh);
  watch(enabled, refresh);
  onMounted(refresh);
  onBeforeUnmount(() => {
    disposed = true;
    unhook();
    clean();
  });
  return { enabled };
}
