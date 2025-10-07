export default defineNuxtConfig({
  runtimeConfig: {
    public: {
      apiBase:
        process.env.NUXT_PUBLIC_API_BASE_URL || "http://localhost:8000/api",
    },
  },
  ssr: false,
  app: {
    baseURL: "/",
  },
  modules: ["@pinia/nuxt", "@nuxt/devtools"],
  css: ["../assets/css/main.css"],
  devtools: { enabled: true },
});
