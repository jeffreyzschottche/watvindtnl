// Export existing SVG artwork for Dompdf's image renderer. No image generation or report data.
import { chromium } from "@playwright/test";
import fs from "node:fs/promises";
const output = process.argv[2] || "/tmp/wdnl-v4/art";
await fs.mkdir(output, { recursive: true });
const types = JSON.parse(
  await fs.readFile(
    new URL("./compass-types-v4.json", import.meta.url),
    "utf8",
  ),
);
const browser = await chromium.launch({ channel: "chrome", headless: true });
try {
  const page = await browser.newPage({
    viewport: { width: 1240, height: 1200 },
    deviceScaleFactor: 1,
  });
  for (const type of ["pending", ...types.map((t) => t.id)]) {
    await page.goto(`http://localhost:3000/report-art/wheel-v4-${type}.svg`);
    await page
      .locator("svg")
      .screenshot({
        path: `${output}/wheel-v4-${type}.png`,
        omitBackground: true,
      });
  }
  console.log("17 existing SVG type wheels exported as print PNGs.");
} finally {
  await browser.close();
}
