import { test, expect, type Page } from "@playwright/test";
const fixture = (id: number) => ({
  id,
  slug: `fixture-${id}`,
  title: `Expliciete stelling ${id}`,
  description: "Browserfixture zonder echte stemmen.",
  votes: null,
  user_vote: null,
  more_info: null,
  url: null,
  arguments: { pro: [], con: [] },
  party_stances: { agree: [], disagree: [], neutral: [] },
  reports: {},
});
const reportId = "ce0a1a51-5076-4d60-b0d8-8af359b77538";
const product = {
  price_cents: 199,
  currency: "eur",
  purchase_type: "one_report_snapshot",
  minimum_votes: 10,
  checkout_available: true,
  native_purchase_available: false,
  ai_available: true,
  ai_provider: "openai",
  methodology_version: "vote-overview-v1",
};
const saved = {
  id: reportId,
  status: "ready",
  amount_cents: 199,
  currency: "eur",
  created_at: "2026-10-01T12:00:00Z",
  ready_at: "2026-10-01T12:00:00Z",
  vote_count: 12,
  checkout_url: null,
  download_available: true,
  snapshot: {
    total: 12,
    counts: { agree: 7, disagree: 5, neutral: 0 },
    entries: [
      {
        id: 1,
        slug: "fixture-1",
        title: "Bestaande teststelling",
        choice: "agree",
      },
    ],
    party_matches: [],
    methodology_version: "vote-overview-v1",
    generated_at: "2026-10-01T12:00:00Z",
    limits: [],
  },
  narrative: {
    summary: "Een expliciet testrapport.",
    reflection: "Een testreflectie zonder echte persoonsgegevens.",
  },
  narrative_source: "code",
};
async function mock(page: Page, selected: Record<string, unknown> = saved) {
  await page.context().addCookies([
    {
      name: "token",
      value: "isolated-reports-fixture",
      url: "http://localhost:3010",
    },
  ]);
  const votes: number[] = [];
  const purchases: unknown[] = [];
  const trackers: string[] = [];
  page.on("request", (r) => {
    if (
      /googlesyndication|doubleclick|google-analytics|googletagmanager/.test(
        r.url(),
      )
    )
      trackers.push(r.url());
  });
  await page.route("**/api/**", async (route) => {
    const url = new URL(route.request().url());
    const headers = {
      "Access-Control-Allow-Origin": "http://localhost:3010",
      "Access-Control-Allow-Headers": "*",
      "Access-Control-Allow-Methods": "GET,POST,OPTIONS",
    };
    if (route.request().method() === "OPTIONS")
      return route.fulfill({ status: 204, headers });
    if (url.pathname === "/api/me")
      return route.fulfill({
        headers,
        json: {
          id: 90001,
          username: "Browserfixture",
          email: "fixture@example.test",
          voted_issue_ids: Array.from({ length: 32 }, (_, i) => i + 1),
        },
      });
    if (url.pathname === "/api/issues/pending")
      return route.fulfill({
        headers,
        json:
          Number(url.searchParams.get("offset")) > 0
            ? []
            : [101, 102, 103, 104, 105, 106]
                .filter((id) => !votes.includes(id))
                .map(fixture),
      });
    const vote = url.pathname.match(/\/issues\/(\d+)\/vote$/);
    if (vote) {
      votes.push(Number(vote[1]));
      return route.fulfill({
        headers,
        json: {
          vote: route.request().postDataJSON().vote,
          user_vote: route.request().postDataJSON().vote,
          voted_issue_ids: votes,
          votes: { agree: 2, disagree: 1, neutral: 0 },
        },
      });
    }
    if (url.pathname === "/api/reports/product")
      return route.fulfill({ headers, json: product });
    if (url.pathname === "/api/me/reports")
      return route.fulfill({
        headers,
        json: {
          product,
          vote_count: 32,
          comparison: {
            previous_report_at: saved.ready_at,
            new_votes: 20,
            suggest_update: true,
            message:
              "Je hebt 20 nieuwe stellingen beantwoord sinds je vorige rapport.",
          },
          orders: [saved],
        },
      });
    if (url.pathname === `/api/me/reports/${reportId}`)
      return route.fulfill({ headers, json: selected });
    if (url.pathname === `/api/me/reports/${reportId}/pdf`)
      return route.fulfill({
        headers: { ...headers, "Content-Type": "application/pdf" },
        body: "%PDF-1.4\nExplicit browser download fixture only.",
      });
    if (url.pathname === "/api/me/reports/orders") {
      purchases.push(route.request().postDataJSON());
      return route.fulfill({
        headers,
        status: 503,
        json: {
          message: "Expliciete betaalfixture: geen echte betaling gestart.",
        },
      });
    }
    return route.fulfill({
      headers,
      status: 404,
      json: { message: "No explicit fixture" },
    });
  });
  return { votes, purchases, trackers };
}
test("demo placement is visible before voting, clickable and immediately closable", async ({
  page,
}) => {
  const { trackers, votes } = await mock(page);
  await page.goto("/spel");
  const ad = page.getByRole("complementary", {
    name: "Advertentieplaatsing (demo)",
  });
  await expect(ad).toBeVisible();
  await ad.getByRole("button", { name: "Bekijk voorbeeld" }).click();
  const dialog = page.getByRole("dialog", {
    name: "Een kleine pauze.",
    exact: false,
  });
  await expect(dialog).toBeVisible();
  await page.keyboard.press("Escape");
  await expect(dialog).toBeHidden();
  await expect(page.locator(".vote-card-v2 h2")).toBeVisible();
  expect(votes).toHaveLength(0);
  expect(trackers).toEqual([]);
});
test("demo break follows the fifth manual next and never a vote or skipped question", async ({
  page,
}) => {
  const { votes, trackers } = await mock(page);
  await page.goto("/spel");
  await page.getByRole("button", { name: "Deze stelling overslaan" }).click();
  for (let i = 0; i < 5; i++) {
    const title = await page.locator(".vote-card-v2 h2").textContent();
    await page.getByRole("button", { name: "Voor Ik ben het eens" }).click();
    await expect(
      page.getByRole("heading", { name: "Zo denken de deelnemers" }),
    ).toBeVisible();
    await expect(page.getByRole("dialog")).toBeHidden();
    await page.getByRole("button", { name: "Volgende stelling" }).click();
    if (i === 4) {
      const dialog = page.getByRole("dialog", { name: /Een kleine pauze/ });
      await expect(dialog).toBeVisible();
      await expect(page.locator(".vote-card-v2 h2")).toHaveText(title!);
      await dialog.getByRole("button", { name: "Verder met WDNL" }).click();
    }
    await expect(page.locator(".vote-card-v2 h2")).not.toHaveText(title!);
  }
  expect(votes).toHaveLength(5);
  expect(trackers).toEqual([]);
});
test("report purchase is separate, consent is explicit and existing PDF downloads cost no generation", async ({
  page,
}) => {
  const { purchases, trackers } = await mock(page);
  await page.goto("/profiel/mijn-kompas");
  await expect(
    page.getByRole("heading", { name: "Mijn stemrapporten." }),
  ).toBeVisible();
  await expect(
    page.getByText(
      "Je hebt 20 nieuwe stellingen beantwoord sinds je vorige rapport.",
    ),
  ).toBeVisible();
  await expect(page.getByText(/verschoven/i)).toHaveCount(0);
  const buy = page.getByRole("button", { name: "Nieuw rapport kopen" });
  await expect(buy).toBeDisabled();
  const ai = page.getByRole("checkbox", {
    name: /Ik wil ook een korte AI-toelichting/,
  });
  await expect(ai).not.toBeChecked();
  await page
    .getByRole("checkbox", { name: /Ik geef uitdrukkelijk toestemming/ })
    .check();
  await expect(buy).toBeEnabled();
  const downloadPromise = page.waitForEvent("download");
  await page.getByRole("button", { name: "Download PDF" }).click();
  const download = await downloadPromise;
  expect(download.suggestedFilename()).toBe("WDNL-stemrapport.pdf");
  expect(purchases).toHaveLength(0);
  await buy.click();
  await expect(page.getByRole("alert")).toContainText(
    "Probeer het later opnieuw",
  );
  expect(purchases).toHaveLength(1);
  expect(purchases[0]).toMatchObject({
    political_consent: true,
    ai_consent: false,
    platform: "web",
  });
  await expect(
    page.getByRole("complementary", { name: "Advertentieplaatsing (demo)" }),
  ).toHaveCount(0);
  expect(trackers).toEqual([]);
});

test("production build hides development generation even if the API claims access", async ({
  page,
}) => {
  await mock(page);
  let devRequests = 0;
  await page.route("**/api/me/reports/dev-access", async (route) => {
    devRequests++;
    await route.fulfill({ json: { available: true } });
  });
  await page.goto("/spel");
  await expect(page.locator(".vote-card-v2 h2")).toBeVisible();
  await expect(
    page.getByRole("button", { name: /BROUW MIJN DEV-RAPPORT/ }),
  ).toHaveCount(0);
  await page.goto("/profiel/mijn-kompas");
  await expect(
    page.getByRole("heading", { name: "Mijn stemrapporten." }),
  ).toBeVisible();
  await expect(
    page.getByRole("button", { name: /BROUW MIJN DEV-RAPPORT/ }),
  ).toHaveCount(0);
  expect(devRequests).toBe(0);
});

const compassFixture = {
  version: "compass-v2.0",
  status: "ready",
  used_votes: 12,
  minimum_per_dimension: 5,
  type: {
    id: "sociale-vernieuwer",
    name: "De Sociale Vernieuwer",
    icon: "people",
    traits: ["Sociaal betrokken", "Toekomstgericht", "Gericht op duurzaamheid"],
  },
  dimensions: [
    { id: "social", left: "Sociaal", right: "Individueel", icon: "people" },
    {
      id: "progressive",
      left: "Progressief",
      right: "Behoudend",
      icon: "spark",
    },
    {
      id: "international",
      left: "Internationaal",
      right: "Nationaal",
      icon: "globe",
    },
    { id: "green", left: "Groen", right: "Economische groei", icon: "leaf" },
  ].map((d) => ({
    ...d,
    percentage: 80,
    opposite_percentage: 20,
    compared: 10,
    neutral: 2,
    minimum: 5,
    sensitivity: { min: 78, max: 89 },
  })),
  spectrum: {
    id: "economic",
    left: "Links",
    right: "Rechts",
    icon: "balance",
    percentage: 60,
    opposite_percentage: 40,
    compared: 10,
    neutral: 2,
    minimum: 5,
    sensitivity: { min: 55, max: 65 },
  },
  highlights: [
    "Je antwoorden leggen vaker de nadruk op gezamenlijke voorzieningen.",
    "Je antwoorden leggen vaker de nadruk op vernieuwing.",
  ],
};
test("compass report has the reference layout, real score slots and a PDF download", async ({
  page,
}) => {
  const selected = {
    ...saved,
    snapshot: {
      ...saved.snapshot,
      compass: compassFixture,
      party_matches: [
        {
          name: "Volledige voorbeeldpartijnaam",
          percentage: 80,
          matched: 8,
          compared: 10,
        },
      ],
    },
  };
  await mock(page, selected);
  await page.goto(`/kompas/rapport?order=${reportId}`);
  const paper = page.getByRole("article", {
    name: "Jouw persoonlijke stemrapport",
  });
  await expect(
    paper.getByRole("heading", { name: "Jouw Politieke Kompas", exact: true }),
  ).toBeVisible();
  await expect(
    paper.getByRole("heading", { name: "De Sociale Vernieuwer", exact: true }),
  ).toBeVisible();
  await expect(
    paper.getByRole("img", { name: /Politieke types, De Sociale Vernieuwer/ }),
  ).toBeVisible();
  await expect(
    paper.getByRole("img", { name: /80% Sociaal, 20% Individueel/ }),
  ).toBeVisible();
  await expect(
    paper.getByRole("img", { name: /Economische as: 60% links, 40% rechts/ }),
  ).toBeVisible();
  await expect(paper.getByText("Volledige voorbeeldpartijnaam")).toBeVisible();
  await expect(paper.locator("img[src*=logo_url]")).toHaveCount(0);
  await expect(
    page.getByRole("button", { name: "Download PDF" }),
  ).toBeEnabled();
  await page.getByRole("button", { name: "Download PDF" }).click();
  for (const width of [1440, 390, 320]) {
    await page.setViewportSize({ width, height: 1000 });
    expect(
      await page.evaluate(() => document.documentElement.scrollWidth),
    ).toBeLessThanOrEqual(width + 1);
  }
});
test("missing reviewed mappings show no invented political type or percentages", async ({
  page,
}) => {
  const compass = {
    ...compassFixture,
    type: null,
    status: "insufficient_data",
    highlights: [],
    used_votes: 0,
    dimensions: compassFixture.dimensions.map((d) => ({
      ...d,
      percentage: null,
      opposite_percentage: null,
      compared: 0,
      sensitivity: null,
    })),
    spectrum: {
      ...compassFixture.spectrum,
      percentage: null,
      opposite_percentage: null,
      compared: 0,
      sensitivity: null,
    },
  };
  await mock(page, { ...saved, snapshot: { ...saved.snapshot, compass } });
  await page.goto(`/kompas/rapport?order=${reportId}`);
  const paper = page.getByRole("article", {
    name: "Jouw persoonlijke stemrapport",
  });
  await expect(
    paper.getByRole("heading", { name: "Jouw kompas in opbouw" }),
  ).toBeVisible();
  await expect(paper.getByText(/Nog geen economische aspositie/)).toBeVisible();
  await expect(paper.getByText(/Nog niet genoeg gegevens/)).toHaveCount(4);
  await expect(paper.getByRole("img", { name: /80% Sociaal/ })).toHaveCount(0);
});
