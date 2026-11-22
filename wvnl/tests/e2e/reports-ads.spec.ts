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
test("the compass shows saved votes and opens checkout only when the report is ready", async ({
  page,
}) => {
  await mock(page);
  let reportReady = false;
  await page.route("**/api/me/compass-progress", (route) =>
    route.fulfill({
      headers: {
        "Access-Control-Allow-Origin": "http://localhost:3010",
        "Access-Control-Allow-Headers": "*",
      },
      json: {
        done: reportReady ? 25 : 0,
        target: 25,
        needed: reportReady ? 0 : 25,
        vote_count: 68,
        used_votes: reportReady ? 12 : 0,
        remaining_votes: 0,
        remaining_weighted_votes: reportReady ? 0 : 10,
        report_ready: reportReady,
        checkout_available: true,
        themes: ["Sociaal", "Progressief", "Internationaal", "Groen", "Links"].map(
          (left) => ({ id: left, left, minimum: 5, done: reportReady ? 5 : 0 }),
        ),
      },
    }),
  );

  await page.goto("/kompas");
  await expect(page.getByRole("heading", { name: "Nog 5 thema’s aanvullen" })).toBeVisible();
  await expect(page.getByText("68 opgeslagen stemmen · 0 meegeteld voor je kompas")).toBeVisible();
  await expect(page.getByRole("link", { name: /Koop mijn rapport/ })).toHaveCount(0);

  reportReady = true;
  await page.reload();
  await expect(page.getByRole("link", { name: "Koop mijn rapport · €1,99" })).toHaveAttribute("href", "/kompas/rapport");
});
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
    name: /Ik wil een inhoudelijke AI-toelichting/,
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

test("historical reviewed answers show a provisional profile and real topics without invented missing scores", async ({
  page,
}) => {
  const compass = {
    ...compassFixture,
    version: "compass-v2.1",
    status: "provisional",
    used_votes: 9,
    historical_votes: 9,
    duplicate_answers: 4,
    conflicting_groups: 1,
    type: {
      ...compassFixture.type,
      provisional: true,
      based_on_dimensions: ["social", "international", "green"],
    },
    dimensions: compassFixture.dimensions.map((d) =>
      d.id === "progressive"
        ? {
            ...d,
            percentage: null,
            opposite_percentage: null,
            compared: 1,
            early_minimum: 2,
            sensitivity: null,
          }
        : { ...d, provisional: true, historical: 2 },
    ),
    topics: [
      {
        name: "Onderwijs & ontwikkeling",
        count: 3,
        agree: 2,
        disagree: 1,
        neutral: 0,
      },
    ],
  };
  await mock(page, { ...saved, snapshot: { ...saved.snapshot, compass } });
  await page.goto(`/kompas/rapport?order=${reportId}`);
  const paper = page.getByRole("article", {
    name: "Jouw persoonlijke stemrapport",
  });
  await expect(
    paper.getByRole("heading", {
      name: "Voorlopig politiek type",
      exact: true,
    }),
  ).toBeVisible();
  await expect(
    paper.getByRole("heading", { name: "De Sociale Vernieuwer", exact: true }),
  ).toBeVisible();
  await expect(
    paper.getByRole("heading", { name: "Jouw onderwerpen", exact: true }),
  ).toBeVisible();
  await expect(
    paper.getByText("Onderwijs & ontwikkeling", { exact: true }),
  ).toBeVisible();
  await expect(
    paper.getByText(/Nog niet genoeg gegevens · 1\/2/),
  ).toBeVisible();
  await expect(paper.getByRole("img", { name: /80% Progressief/ })).toHaveCount(
    0,
  );
  await expect(
    paper.getByText(/4 dubbele antwoorden eenmaal geteld/),
  ).toBeVisible();
  for (const width of [1440, 390, 320]) {
    await page.setViewportSize({ width, height: 1000 });
    expect(
      await page.evaluate(() => document.documentElement.scrollWidth),
    ).toBeLessThanOrEqual(width + 1);
  }
});

test("rich report ties AI insights to actual choices and remains readable on small screens", async ({
  page,
}) => {
  const entries = [
    {
      id: 1,
      slug: "schoolklassen",
      title: "Meer investeren in kleinere schoolklassen.",
      choice: "agree",
      description: "Een concreet voorstel over onderwijs.",
    },
    {
      id: 2,
      slug: "woningbouw",
      title: "Strengere eisen aan duurzame woningbouw.",
      choice: "agree",
    },
    {
      id: 3,
      slug: "vervoer",
      title: "Openbaar vervoer betaalbaarder maken.",
      choice: "neutral",
    },
    {
      id: 4,
      slug: "europa",
      title: "Meer Europese samenwerking.",
      choice: "disagree",
    },
  ];
  await mock(page, {
    ...saved,
    snapshot: {
      ...saved.snapshot,
      entries,
      compass: compassFixture,
      analysis_context: {
        version: 3,
        votes: entries,
        eligible_count: 4,
        included_count: 4,
        omitted_count: 0,
        excluded_conflicts: 0,
      },
    },
    narrative: {
      analysis_version: 3,
      summary:
        "Je steunt kleinere schoolklassen en strengere eisen aan duurzame woningbouw.",
      reflection: "Je keuzes hebben ieder hun eigen context.",
      insights: [
        {
          heading: "Toegankelijk onderwijs",
          body: "Je steunt het voorstel voor kleinere klassen.",
          evidence_ids: [1],
        },
        {
          heading: "Duurzaamheid bij wonen",
          body: "Je kiest voor strengere eisen bij woningbouw.",
          evidence_ids: [2],
        },
        {
          heading: "Geen richting bij vervoer",
          body: "Bij dit voorstel heb je neutraal geantwoord.",
          evidence_ids: [3],
        },
      ],
      positions: entries.map((e) => ({
        statement_id: e.id,
        interpretation: "Je vastgelegde keuze bij deze concrete stelling.",
      })),
      nuances: [
        {
          heading: "Afwegingen verschillen per onderwerp",
          body: "Je steunt onderwijsinvesteringen en stemt tegen meer Europese samenwerking.",
          evidence_ids: [1, 4],
        },
      ],
      questions: [
        {
          text: "Welke afweging weegt voor jou bij Europese samenwerking?",
          evidence_ids: [4],
        },
      ],
    },
    narrative_source: "openai",
  });
  await page.goto(`/kompas/rapport?order=${reportId}`);
  const paper = page.getByRole("article", {
    name: "Jouw persoonlijke stemrapport",
  });
  await expect(
    paper.getByRole("heading", { name: "Dit valt op bij jou" }),
  ).toBeVisible();
  await expect(
    paper.getByText("Je steunt het voorstel voor kleinere klassen.", {
      exact: true,
    }),
  ).toBeVisible();
  await expect(
    paper.getByRole("heading", { name: "Dit zijn jouw standpunten" }),
  ).toBeVisible();
  await expect(paper.getByText("Neutraal", { exact: true })).toHaveCount(3);
  await expect(
    paper.getByRole("heading", {
      name: "Welke afweging weegt voor jou bij Europese samenwerking?",
    }),
  ).toBeVisible();
  await paper
    .getByText("Hoe dit rapport tot stand komt", { exact: true })
    .click();
  await expect(
    paper.getByText(/De AI-toelichting gebruikt 4 van 4 unieke/),
  ).toBeVisible();
  await expect(paper.locator('a[href="/stellingen/vervoer"]')).toHaveCount(2);
  const art = paper.locator('img[src*="compass-sculpture-v3"]');
  expect(
    await art.evaluate(
      (img: HTMLImageElement) => img.complete && img.naturalWidth > 0,
    ),
  ).toBe(true);
  for (const width of [1440, 390, 320]) {
    await page.setViewportSize({ width, height: 1000 });
    expect(
      await page.evaluate(() => document.documentElement.scrollWidth),
    ).toBeLessThanOrEqual(width + 1);
  }
});

test("one-page compass poster uses the real logo, sixteen-type wheel and all requested report sections", async ({
  page,
}) => {
  const { readFileSync } = await import("node:fs");
  const poster = JSON.parse(
    readFileSync(
      new URL(
        "../../scripts/design/compass-poster-example.json",
        import.meta.url,
      ),
      "utf8",
    ),
  );
  await mock(page, {
    ...saved,
    snapshot: poster.snapshot,
    narrative: poster.narrative,
  });
  await page.goto(`/kompas/rapport?order=${reportId}`);
  const paper = page.getByRole("article", {
    name: "Jouw persoonlijke stemrapport",
  });
  await expect(paper.locator(".poster-header")).toHaveCount(1);
  await expect(page.locator(".insight-page")).toHaveCount(0);
  for (const heading of [
    "Jouw Politieke Kompas",
    "Jouw politieke persoonlijkheid",
    "Jouw positie op het politieke spectrum",
    "Jouw scores op belangrijke thema’s",
    "Partijen die bij je passen",
    "Wat typeert jou?",
    "Hoe dit rapport werkt",
  ]) {
    await expect(
      paper.getByRole("heading", { name: heading, exact: true }),
    ).toBeVisible();
  }
  await expect(paper.locator(".poster-highlights li")).toHaveCount(5);
  await expect(paper.locator(".poster-party")).toHaveCount(5);
  await expect(
    paper.getByText("Matchscore op 100 · voorzichtig bij weinig moties."),
  ).toBeVisible();
  await expect(paper.locator(".poster-party-percentage").first()).toHaveText(
    String(poster.snapshot.party_matches[0].balanced_score),
  );
  await expect(paper.locator(".poster-party").first()).toContainText(
    "8 van 10 moties",
  );
  await expect(paper.locator(".poster-method")).toContainText(
    "scores dichter bij het midden",
  );
  expect(poster.snapshot.compass.type_catalogue).toHaveLength(16);
  const logo = paper.getByRole("img", {
    name: "WatDenktNederland",
    exact: true,
  });
  await expect(logo).toHaveAttribute("src", "/brand/wdnl-logo.png");
  for (const selector of [".poster-logo"]) {
    expect(
      await paper
        .locator(selector)
        .evaluate(
          (img: HTMLImageElement) => img.complete && img.naturalWidth > 0,
        ),
    ).toBe(true);
  }
  await expect(paper.locator(".type-wheel__sector")).toHaveCount(16);
  await page
    .getByText("Onderbouwing van jouw rapport", { exact: true })
    .click();
  await expect(page.locator(".poster-evidence[open]")).toBeVisible();
  for (const width of [1440, 390, 320]) {
    await page.setViewportSize({ width, height: 1000 });
    expect(
      await page.evaluate(() => document.documentElement.scrollWidth),
    ).toBeLessThanOrEqual(width + 1);
  }
});

async function posterPreview(page: Page) {
  const { readFileSync } = await import("node:fs");
  const fixture = JSON.parse(
    readFileSync(
      new URL(
        "../../scripts/design/compass-poster-example.json",
        import.meta.url,
      ),
      "utf8",
    ),
  );
  await mock(page, {
    ...saved,
    snapshot: fixture.snapshot,
    narrative: fixture.narrative,
  });
  await page.goto(`/kompas/rapport?order=${reportId}`);
  return {
    fixture,
    paper: page.getByRole("article", { name: "Jouw persoonlijke stemrapport" }),
  };
}

test("type wheel supports click and keyboard exploration while the personal result stays fixed", async ({
  page,
}, testInfo) => {
  const posts: string[] = [];
  page.on("request", (request) => {
    if (request.method() === "POST" && request.url().includes("/api/"))
      posts.push(request.url());
  });
  const { fixture, paper } = await posterPreview(page);
  const own = fixture.snapshot.compass.type;
  const heading = paper.locator(".poster-hero-copy h3");
  await expect(paper.locator(".poster-percentages")).toHaveCount(4);
  await expect(paper).toHaveAttribute("data-report-motion", "on");
  const personalScores = await paper
    .locator(".poster-percentages")
    .allTextContents();
  const greenSector = paper.getByRole("button", {
    name: "De Groene Vernieuwer bekijken",
    exact: true,
  });
  await expect(paper.locator("svg.poster-wheel title")).toHaveCount(0);
  if (testInfo.project.name === "desktop") {
    const restingFill = await greenSector.evaluate((element) => getComputedStyle(element).fill);
    await greenSector.hover();
    await expect(paper.locator(".type-wheel__hint")).toHaveText("De Groene Vernieuwer");
    await expect(greenSector).not.toHaveCSS("fill", restingFill);
  }
  await greenSector.click();
  await expect(paper.locator(".type-explorer h4")).toHaveText(
    "De Groene Vernieuwer",
  );
  await expect(paper.locator(".type-explorer__badge")).toHaveText(
    "Een ander type ontdekken",
  );
  await expect(heading).toHaveText(own.name);
  expect(await paper.locator(".poster-percentages").allTextContents()).toEqual(
    personalScores,
  );
  await paper.getByRole("button", { name: "Terug naar mijn type" }).click();
  await expect(paper.locator(".type-explorer h4")).toHaveText(own.name);
  const sector = paper.getByRole("button", {
    name: `${own.name} bekijken`,
    exact: true,
  });
  await sector.focus();
  await page.keyboard.press("ArrowRight");
  await expect(greenSector).toBeFocused();
  await expect(greenSector).toHaveAttribute("aria-pressed", "true");
  await expect(greenSector).toHaveCSS("outline-style", "none");
  await expect(paper.locator(".type-wheel__sector:focus-visible + .type-wheel__sector-focus")).toHaveCSS("opacity", "1");
  await paper
    .getByRole("combobox", { name: "Bekijk een politiek type" })
    .selectOption("internationale-verbinder");
  await expect(paper.locator(".type-explorer h4")).toHaveText(
    "De Internationale Verbinder",
  );
  await paper.getByRole("button", { name: "Volgend type bekijken" }).click();
  await expect(paper.locator(".type-explorer h4")).toHaveText(
    "De Praktische Hervormer",
  );
  await paper.getByRole("button", { name: "Terug naar mijn type" }).click();
  if ((page.viewportSize()?.width || 0) > 650) {
    await expect
      .poll(() =>
        paper
          .locator(".poster-personality")
          .evaluate((el) => getComputedStyle(el).transform),
      )
      .toBe("none");
    const left = (await paper.locator(".poster-personality").boundingBox())!;
    const right = (await paper.locator(".poster-scores").boundingBox())!;
    expect(
      Math.abs(left.y + left.height - right.y - right.height),
    ).toBeLessThan(2);
    await expect
      .poll(() =>
        paper
          .locator(".poster-parties")
          .evaluate((el) => getComputedStyle(el).transform),
      )
      .toBe("none");
    const lowerLeft = (await paper.locator(".poster-parties").boundingBox())!;
    const lowerRight = (await paper
      .locator(".poster-interpretation")
      .boundingBox())!;
    expect(
      Math.abs(
        lowerLeft.y + lowerLeft.height - lowerRight.y - lowerRight.height,
      ),
    ).toBeLessThan(2);
  }
  await expect(
    paper.getByRole("img", {
      name: "8 voor, 2 tegen, 0 neutraal",
      exact: true,
    }),
  ).toBeVisible();
  expect(posts).toEqual([]);
  await page.emulateMedia({ reducedMotion: "reduce" });
  await expect(paper).toHaveAttribute("data-report-motion", "off");
  await paper.screenshot({
    path: testInfo.outputPath("interactive-compass.png"),
    animations: "disabled",
  });
});

test("report exploration stays usable with reduced motion and the shared motion preference disabled", async ({
  page,
}) => {
  await page.emulateMedia({ reducedMotion: "reduce" });
  const { paper } = await posterPreview(page);
  await expect(paper).toHaveAttribute("data-report-motion", "off");
  await paper
    .getByRole("button", { name: "De Groene Vernieuwer bekijken", exact: true })
    .click();
  await expect(paper.locator(".type-explorer h4")).toHaveText(
    "De Groene Vernieuwer",
  );
  expect(
    await paper
      .locator("[data-report-bar]")
      .first()
      .evaluate((el) => (el as HTMLElement).style.transform),
  ).toBe("");
  await expect(
    paper.getByRole("heading", { name: "Wat typeert jou?" }),
  ).toBeVisible();
  await page
    .context()
    .addCookies([
      { name: "wdnl_motion", value: "false", url: "http://localhost:3010" },
    ]);
  await page.emulateMedia({ reducedMotion: "no-preference" });
  await page.reload();
  await expect(paper).toHaveAttribute("data-report-motion", "off");
  await paper
    .getByRole("combobox", { name: "Bekijk een politiek type" })
    .selectOption("groene-vernieuwer");
  await expect(paper.locator(".type-explorer h4")).toHaveText(
    "De Groene Vernieuwer",
  );
  expect(
    await paper
      .locator(".type-explorer__copy")
      .evaluate((el) => (el as HTMLElement).style.transform),
  ).toBe("");
  for (const width of [1440, 390, 320]) {
    await page.setViewportSize({ width, height: 1000 });
    expect(
      await page.evaluate(() => document.documentElement.scrollWidth),
    ).toBeLessThanOrEqual(width + 1);
  }
});
