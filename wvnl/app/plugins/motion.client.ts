export default defineNuxtPlugin((app) => {
  const reduced = useState("wdnl-reduced-motion", () => false);
  const query = window.matchMedia("(prefers-reduced-motion: reduce)");
  const update = () => {
    reduced.value = query.matches;
  };
  update();
  const { enabled } = useMotionPreferences();
  const stop = watch(
    enabled,
    (value) => {
      document.documentElement.dataset.wdnlMotion = value ? "on" : "off";
    },
    { immediate: true },
  );
  query.addEventListener("change", update);
  app.vueApp.onUnmount(() => {
    stop();
    query.removeEventListener("change", update);
    delete document.documentElement.dataset.wdnlMotion;
  });
});
