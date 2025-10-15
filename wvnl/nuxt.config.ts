export default defineNuxtConfig({
  runtimeConfig: {
    public: {
      apiBase:
        process.env.NUXT_PUBLIC_API_BASE_URL || "https://api.watdenktnl.nl/api",
    },
  },
  app: {
    baseURL: "/",
    head: {
      meta: [
        {
          name: "viewport",
          content:
            "width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no",
        },
      ],
    },
  },
  ssr: false,
  modules: ["@pinia/nuxt", "@nuxt/devtools"],
  css: ["../assets/css/main.css"],
  devtools: { enabled: true },
});
