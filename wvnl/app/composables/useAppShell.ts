/** One definition of "app mode": logged in, on an app page. Drives the tab bar, compact header and hidden footer. */
export function useAppShell() {
  const auth = useAuthStore();
  const route = useRoute();
  const showTabs = computed(
    () =>
      auth.isLoggedIn &&
      route.path !== "/" &&
      !["/inloggen", "/login", "/registreren", "/register"].includes(
        route.path,
      ),
  );
  return { showTabs };
}
