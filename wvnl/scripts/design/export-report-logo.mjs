import { chromium } from "@playwright/test";
import { fileURLToPath } from "node:url";
const browser = await chromium.launch({ channel: "chrome", headless: true });
try {
  const page = await browser.newPage({
    deviceScaleFactor: 4,
    viewport: { width: 1100, height: 220 },
  });
  await page.goto("http://localhost:3000");
  await page.setContent(
    `<style>@font-face{font-family:Manrope;src:url(http://localhost:3000/fonts/manrope-variable.ttf) format('truetype');font-weight:200 800}body{margin:0;background:transparent}.logo{display:inline-flex;align-items:center;gap:20px;padding:5px;font-family:Manrope,sans-serif;color:#001f4d;white-space:nowrap;font-weight:800;font-size:63px;letter-spacing:-3.4px;line-height:1.2}.logo img{width:118px;height:134px;object-fit:contain}.logo span span{color:#0057b8}</style><div class="logo"><img src="http://localhost:3000/brand/wdnl-mark-concept.svg"><span>WatDenkt<span>Nederland</span></span></div>`,
  );
  await page.evaluate(() => document.fonts.ready);
  await page.locator("img").evaluate((img) => img.decode());
  await page
    .locator(".logo")
    .screenshot({
      path: fileURLToPath(
        new URL("../../public/brand/wdnl-logo.png", import.meta.url),
      ),
      omitBackground: true,
    });
  console.log(
    "Existing WDNL brand lockup exported with the original mark and bundled Manrope font.",
  );
} finally {
  await browser.close();
}
