const defaultApiBase =
  process.env.NUXT_PUBLIC_API_BASE_URL || "http://localhost:8000/api";

export default defineNuxtConfig({
  runtimeConfig: {
    public: {
      apiBase: defaultApiBase,
      altchaChallengeUrl:
        process.env.NUXT_PUBLIC_ALTCHA_CHALLENGE_URL ||
        `${defaultApiBase}/altcha/challenge`,
    },
  },
  ssr: false,
  app: {
    baseURL: "/",
  },
  modules: ["@pinia/nuxt", "@nuxt/devtools"],
  css: ["../assets/css/main.css"],
  devtools: { enabled: true },
  vue: {
    compilerOptions: {
      isCustomElement: (tag) => tag === "altcha-widget",
    },
  },
});
