// Run with the local Nuxt dev server on :3000. Every API request is intercepted;
// no real account, database, model request or purchase is used.
import { chromium } from "@playwright/test";
import assert from "node:assert/strict";
const ids = {
  fresh: "20000000-0000-4000-8000-000000000001",
  next: "20000000-0000-4000-8000-000000000002",
  old: "20000000-0000-4000-8000-000000000003",
  expired: "20000000-0000-4000-8000-000000000004",
};
const compass = {
  version: "compass-v2.0",
  status: "ready",
  used_votes: 10,
  minimum_per_dimension: 5,
  type: {
    id: "sociale-vernieuwer",
    name: "De Sociale Vernieuwer",
    icon: "people",
    traits: ["Sociaal betrokken"],
  },
  dimensions: [],
  spectrum: {
    id: "economic",
    left: "Links",
    right: "Rechts",
    percentage: null,
    compared: 0,
    minimum: 5,
  },
  highlights: [],
};
const report = (id) => ({
  id,
  is_development: true,
  layout_version: "compass-v2.0",
  generation_model: "gpt-6-luna",
  status: "ready",
  amount_cents: 0,
  currency: "eur",
  created_at: "2026-11-08T12:00:00Z",
  ready_at: "2026-11-08T12:00:00Z",
  vote_count: 10,
  download_available: true,
  snapshot: {
    total: 10,
    counts: { agree: 8, disagree: 2, neutral: 0 },
    entries: [],
    party_matches: [],
    generated_at: "2026-11-08T12:00:00Z",
    methodology_version: "compass-v2.0",
    compass,
  },
  narrative: {
    analysis_version: 4,
    highlights: [],
    summary:
      id === ids.next
        ? "Nieuw geselecteerd ontwikkelrapport."
        : "Eerder geselecteerd ontwikkelrapport.",
    reflection: "Expliciete browserfixture.",
  },
});
const overview = {
  product: { minimum_votes: 10, checkout_available: false, price_cents: 199 },
  vote_count: 10,
  orders: [],
  comparison: { suggest_update: false, new_votes: 0 },
  compass_progress: { ready: false, used_votes: 0, dimensions: [] },
};
const browser = await chromium.launch({ channel: "chrome", headless: true });
let checks = 0;
try {
  const context = await browser.newContext({ reducedMotion: "reduce" });
  await context.addCookies([
    {
      name: "token",
      value: "isolated-dev-display-fixture",
      url: "http://localhost:3000",
    },
  ]);
  const page = await context.newPage();
  const errors = [];
  page.on("pageerror", (e) => errors.push(e.message));
  let generationFailure = false;
  let overviewFails = false;
  let slowOld = false;
  let generated = ids.next;
  await page.route("**/api/**", async (route) => {
    const path = new URL(route.request().url()).pathname;
    const headers = {
      "Access-Control-Allow-Origin": "http://localhost:3000",
      "Access-Control-Allow-Headers": "*",
      "Access-Control-Allow-Methods": "GET,POST,OPTIONS",
    };
    const send = (json, status = 200) =>
      route.fulfill({ headers, json, status });
    if (route.request().method() === "OPTIONS")
      return route.fulfill({ status: 204, headers });
    if (path === "/api/me")
      return send({
        id: 90001,
        email: "fixture@example.test",
        username: "Explicit test fixture",
        voted_issue_ids: [],
      });
    if (path === "/api/me/reports/dev-access")
      return send({ available: true, model: "gpt-6-luna" });
    if (path === "/api/me/reports")
      return send(
        overviewFails
          ? { message: "Explicit overview outage fixture" }
          : overview,
        overviewFails ? 503 : 200,
      );
    if (path === "/api/me/reports/dev") {
      if (generationFailure)
        return send(
          {
            message:
              "OpenAI gaf rapporttekst die niet aan het rapportformaat voldoet. Er is geen standaardtekst gebruikt.",
          },
          422,
        );
      const result = report(generated);
      if (generated === ids.old) delete result.snapshot.compass;
      return send(result);
    }
    if (path.startsWith("/api/me/reports/dev/")) {
      const id = path.split("/").at(-1);
      if (id === ids.expired)
        return send({ message: "Preview expired fixture" }, 404);
      const result = report(id);
      if (id === ids.old) {
        if (slowOld) await new Promise((resolve) => setTimeout(resolve, 1000));
        delete result.snapshot.compass;
      }
      return send(result);
    }
    return send({ message: "No explicit fixture for this request" }, 404);
  });
  const title = () =>
    page.getByRole("heading", { name: "Jouw Politieke Kompas", exact: true });
  const button = () =>
    page.getByRole("button", { name: /BROUW MIJN DEV-RAPPORT/ });
  const until = async (predicate) => {
    for (let i = 0; i < 100; i++) {
      if (await predicate()) return;
      await page.waitForTimeout(50);
    }
    throw new Error("Expected report state did not appear");
  };
  overviewFails = true;
  await page.goto(
    `http://localhost:3000/profiel/mijn-kompas?dev_report=${ids.fresh}`,
  );
  await title().waitFor();
  await until(async () =>
    (await page.getByRole("alert").allTextContents()).some((t) =>
      t.includes("rapportoverzicht"),
    ),
  );
  assert.equal(
    await page.getByRole("button", { name: /Download PDF/ }).count(),
    1,
  );
  console.log("PASS valid dev report survives overview failure");
  checks++;
  await button().click();
  await page.waitForURL(`**dev_report=${ids.next}`);
  await until(
    async () =>
      (await page.getByText("Nieuw geselecteerd ontwikkelrapport.").count()) ===
      1,
  );
  console.log("PASS purple button opens newly selected one-page V4 report");
  checks++;
  overviewFails = false;
  slowOld = true;
  await page.goto(`http://localhost:3000/kompas/rapport?dev_report=${ids.old}`);
  await button().waitFor();
  await button().click();
  await page.waitForURL(`**dev_report=${ids.next}`);
  await title().waitFor();
  await page.waitForTimeout(1300);
  assert.equal(
    await page.getByRole("heading", { name: /Jouw stemoverzicht/ }).count(),
    0,
  );
  assert.equal(await page.getByRole("alert").count(), 0);
  console.log(
    "PASS late older request cannot overwrite new report or show its error",
  );
  checks++;
  generated = ids.expired;
  await button().click();
  await page.waitForURL(`**dev_report=${ids.expired}`);
  await until(async () =>
    (await page.getByRole("alert").allTextContents()).some((t) =>
      t.includes("verlopen"),
    ),
  );
  assert.equal(await title().count(), 0);
  assert.equal(
    await page.getByRole("button", { name: /Download PDF/ }).count(),
    0,
  );
  console.log(
    "PASS expired report has useful error and clears previous report",
  );
  checks++;
  slowOld = false;
  await page.goto(`http://localhost:3000/kompas/rapport?dev_report=${ids.old}`);
  await until(async () =>
    (await page.getByRole("alert").allTextContents()).some((t) =>
      t.includes("oude devrapportversie"),
    ),
  );
  assert.equal(
    await page.getByRole("heading", { name: /Jouw stemoverzicht/ }).count(),
    0,
  );
  console.log(
    "PASS old cached layout never silently opens as a new dev report",
  );
  checks++;
  generated = ids.old;
  await button().click();
  await until(async () =>
    (await page.getByRole("alert").allTextContents()).some((t) =>
      t.includes("Herstart de backend"),
    ),
  );
  console.log("PASS old generation response explains stale API");
  checks++;
  generationFailure = true;
  await button().click();
  await until(async () =>
    (await page.getByRole("alert").allTextContents()).some((t) =>
      t.includes("OpenAI gaf rapporttekst"),
    ),
  );
  assert.equal(
    (await page.getByRole("alert").allTextContents()).some((t) =>
      t.includes("Controleer de invoer"),
    ),
    false,
  );
  console.log(
    "PASS generation service error is visible instead of generic input validation",
  );
  checks++;
  assert.deepEqual(errors, []);
  console.log(
    `${checks} local dev regressions passed; all API traffic intercepted.`,
  );
} finally {
  await browser.close();
}
