/** One preference for the website and its Capacitor iOS/Android clients. */
export function useMotionPreferences() {
  const reducedBySystem = useState("wdnl-reduced-motion", () => false);
  const optedIn = useCookie<boolean>("wdnl_motion", {
    default: () => true,
    sameSite: "lax",
    maxAge: 60 * 60 * 24 * 365,
  });
  const preference = useState(
    "wdnl-motion-opt-in",
    () => optedIn.value !== false,
  );
  const enabled = computed(() => !reducedBySystem.value && preference.value);
  return {
    enabled,
    reducedBySystem,
    toggle: () => {
      preference.value = !enabled.value;
      optedIn.value = preference.value;
    },
  };
}
