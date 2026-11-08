/** Demo pauses only follow an explicit Next after a confirmed vote. No political data is retained. */
export function useAdBreak() {
  const config = useRuntimeConfig();
  const counter = useState("wdnl-demo-ad-count", () => 0);
  const last = useState("wdnl-demo-ad-at", () => 0);
  const open = ref(false);
  let continueAction: (() => void) | undefined;
  function next(answered: boolean, action: () => void) {
    if (open.value) return;
    if (answered) counter.value++;
    if (
      config.public.demoAds &&
      answered &&
      counter.value >= 5 &&
      Date.now() - last.value >= 120000
    ) {
      counter.value = 0;
      last.value = Date.now();
      continueAction = action;
      open.value = true;
    } else action();
  }
  function close() {
    open.value = false;
    const action = continueAction;
    continueAction = undefined;
    action?.();
  }
  return { open, next, close };
}
