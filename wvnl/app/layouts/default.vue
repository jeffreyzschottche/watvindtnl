<template>
  <div class="site-wrapper">
    <SiteNav />
    <main class="site-main">
      <slot />
    </main>
    <SiteFooter />
  </div>
</template>

<script setup lang="ts">
import { onMounted } from "vue";
import { useRouter, useRuntimeConfig } from "#app";
import SiteNav from "~/components/common/SiteNav.vue";
import SiteFooter from "~/components/common/SiteFooter.vue";

/**
 * GA-ID:
 * - Pak eerst uit runtimeConfig (NUXT_PUBLIC_GTAG_ID via nuxt.config.ts)
 * - Valt terug op jouw opgegeven ID 'G-6K30LXM074'
 */
const GA_ID =
  useRuntimeConfig().public?.gtagId ||
  process.env.NUXT_PUBLIC_GTAG_ID ||
  "G-6K30LXM074";

/** Alleen in productie op de client tracken */
// const isProd =
//   process.client && process.env.NODE_ENV === "production" && !!GA_ID;

const ADS_CLIENT = "ca-pub-2932017576194299";

// Laad het AdSense script (1x)
useHead({
  script: [
    {
      id: "adsbygoogle",
      async: true,
      crossorigin: "anonymous",
      src: `https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=${ADS_CLIENT}`,
    },
  ],
});

const isProd = true;

onMounted(() => {
  if (!isProd) return;

  // Dubbele injectie voorkomen (HMR/navigaties)
  if ((window as any).__gaLoaded) return;
  (window as any).__gaLoaded = true;

  // 1) Laad gtag.js
  const gaSrc = document.createElement("script");
  gaSrc.async = true;
  gaSrc.src = `https://www.googletagmanager.com/gtag/js?id=${GA_ID}`;
  document.head.appendChild(gaSrc);

  // 2) Inline init (mag vóór/na load, dataLayer buffert)
  const gaInit = document.createElement("script");
  gaInit.type = "text/javascript";
  gaInit.text = `
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());
    gtag('config', '${GA_ID}', { 'anonymize_ip': true });
  `;
  document.head.appendChild(gaInit);
});

// SPA pageviews bij routewissel
const router = useRouter();
if (isProd) {
  router.afterEach((to) => {
    const send = () => {
      if (typeof window !== "undefined" && (window as any).gtag) {
        (window as any).gtag("config", GA_ID, {
          page_path: to.fullPath,
          page_title: document.title,
        });
      } else {
        // wacht heel even tot gtag beschikbaar is
        setTimeout(send, 200);
      }
    };
    send();
  });
}
</script>
