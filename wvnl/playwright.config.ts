import { defineConfig, devices } from "@playwright/test";
export default defineConfig({
  testDir: "./tests/e2e",
  fullyParallel: true,
  retries: process.env.CI ? 1 : 0,
  reporter: "list",
  use: { baseURL: "http://localhost:3010", trace: "retain-on-failure" },
  projects: [
    { name: "desktop", use: { ...devices["Desktop Chrome"], channel: process.env.CI ? undefined : "chrome" } },
    { name: "mobile", use: { ...devices["Pixel 7"], channel: process.env.CI ? undefined : "chrome" } },
  ],
  webServer: {
    command: "node .output/server/index.mjs",
    url: "http://localhost:3010",
    env: { PORT: "3010", HOST: "127.0.0.1", NUXT_PUBLIC_API_BASE: "http://localhost:8000/api", NUXT_PUBLIC_DEMO_ADS: "true", NUXT_PUBLIC_ADS_ENABLED: "false" },
    reuseExistingServer: false,
    timeout: 30000,
  },
});
