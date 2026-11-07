export default defineNuxtPlugin((app) => {
  const reduced = useState("wdnl-reduced-motion", () => false);
  const query = window.matchMedia("(prefers-reduced-motion: reduce)");
  const update = () => {
    reduced.value = query.matches;
  };
  update();
  query.addEventListener("change", update);
  app.vueApp.onUnmount(() => query.removeEventListener("change", update));
});
