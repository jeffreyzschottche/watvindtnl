import { defineNuxtConfig } from "nuxt/config";

export default defineNuxtConfig({
  runtimeConfig: {
    public: {
      demoAds: process.env.DEMO_ADS === "1", // Local labelled placeholders; independent of live ads.
      // Live AdSense defaults to on in production builds; override with NUXT_PUBLIC_ADS_ENABLED=false.
      adsEnabled: process.env.ADS_ENABLED
        ? process.env.ADS_ENABLED === "1"
        : process.env.NODE_ENV === "production",
      adsenseClient: "ca-pub-2932017576194299",
      adsenseSlot: "4921284951", // Display unit "wdnl-responsief"
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
