/** Google's sample inventory is restricted to the local web preview. */
export function useGoogleTestAds() {
  return (
    import.meta.dev &&
    useRuntimeConfig().public.demoAds &&
    ["localhost", "127.0.0.1", "[::1]"].includes(useRequestURL().hostname)
  );
}
