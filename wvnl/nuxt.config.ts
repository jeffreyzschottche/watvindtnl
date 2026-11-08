import { defineNuxtConfig } from "nuxt/config";

export default defineNuxtConfig({
  runtimeConfig: {
    public: {
      demoAds: process.env.DEMO_ADS === "1",
      adsEnabled: process.env.ADS_ENABLED === "1",
      apiBase:
        process.env.NUXT_PUBLIC_API_BASE_URL || "http://localhost:8000/api",
    },
  },
  app: {
    baseURL: "/",
    head: {
      link: [
        {
          rel: "preload",
          href: "/fonts/manrope-variable.ttf",
          as: "font",
          type: "font/ttf",
          crossorigin: "anonymous",
        },
      ],
      meta: [
        {
          name: "viewport",
          content: "width=device-width, initial-scale=1.0, viewport-fit=cover",
        },
      ],
    },
  },
  ssr: false,
  modules: ["@pinia/nuxt", "@nuxt/devtools"],
  css: ["~~/assets/css/main.css"],
  devtools: { enabled: true },
});
