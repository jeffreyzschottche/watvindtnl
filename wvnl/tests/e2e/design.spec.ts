import { test, expect, type Page } from "@playwright/test";
const fixtures = [
  {
    id: 301,
    title: "Hoe maken we wonen betaalbaar?",
    slug: "wonen-betaalbaar",
    description: "Een expliciete testfixture voor de browsercontrole.",
  },
  {
    id: 302,
    title: "Meer ruimte voor openbaar vervoer?",
    slug: "openbaar-vervoer",
    description: "Een tweede expliciete testfixture.",
  },
].map((item) => ({
  ...item,
  votes: null,
  user_vote: null,
  url: null,
  more_info: null,
  arguments: { pro: [], con: [] },
  party_stances: { agree: [], disagree: [], neutral: [] },
  reports: {},
}));
async function mockApi(page: Page, login = false) {
  if (login)
    await page.context().addCookies([
      {
        name: "token",
        value: "design-test-fixture",
        url: "http://localhost:3010",
      },
    ]);
  await page.route("**/api/**", async (route) => {
    const url = new URL(route.request().url());
    const headers = {
      "Access-Control-Allow-Origin": "http://localhost:3010",
      "Access-Control-Allow-Headers": "*",
      "Access-Control-Allow-Methods": "*",
    };
    if (route.request().method() === "OPTIONS")
      return route.fulfill({ status: 204, headers });
    if (url.pathname === "/api/me")
      return route.fulfill({
        headers,
        json: {
          id: 90001,
          name: "Demo",
          username: "Demo",
          email: "fixture@example.test",
          voted_issue_ids: [1, 2],
          created_at: "2025-01-12T12:00:00Z",
        },
      });
    if (url.pathname === "/api/issues-args") {
      const term = (url.searchParams.get("search") ?? "").toLowerCase();
      return route.fulfill({
        headers,
        json: fixtures.filter((item) =>
          item.title.toLowerCase().includes(term),
        ),
      });
    }
    if (url.pathname.startsWith("/api/statements/"))
      return route.fulfill({
        headers,
        json: fixtures.find(
          (item) => item.slug === url.pathname.split("/").pop(),
        ),
      });
    if (url.pathname === "/api/me/votes")
      return route.fulfill({ headers, json: [] });
    return route.fulfill({
      headers,
      status: 404,
      json: { message: "No explicit fixture" },
    });
  });
}
async function noOverflow(page: Page) {
  expect(
    await page.evaluate(
      () => document.documentElement.scrollWidth <= window.innerWidth + 1,
    ),
  ).toBe(true);
}

test("homepage has the new identity, clear navigation and real content slots", async ({
  page,
}, info) => {
  await mockApi(page);
  await page.goto("/");
  await expect(page.getByRole("heading", { level: 1 })).toHaveText(
    "Nederland heefteen mening.Jij ook.",
  );
  await expect(
    page.getByRole("link", { name: "WatDenktNederland — home" }).first(),
  ).toBeVisible();
  await expect(
    page.getByRole("heading", { name: fixtures[0]!.title }),
  ).toBeVisible();
  expect(
    await page
      .locator(".site-header")
      .evaluate((el) => getComputedStyle(el).backgroundColor),
  ).toBe("rgb(255, 255, 255)");
  expect(
    await page.evaluate(() =>
      getComputedStyle(document.documentElement)
        .getPropertyValue("--wdnl-red")
        .trim(),
    ),
  ).toBe("#e1062e");
  await noOverflow(page);
  await page.screenshot({
    path: info.outputPath("homepage.png"),
    fullPage: true,
  });
});

test("login is compact and account actions are clearly available", async ({
  page,
}, info) => {
  await page.goto("/inloggen");
  await expect(
    page.getByRole("heading", { name: "Welkom terug." }),
  ).toBeVisible();
  await expect(page.getByLabel("E-mailadres")).toBeVisible();
  await expect(page.getByLabel("Wachtwoord", { exact: true })).toBeVisible();
  await expect(page.locator(".page-hero")).toHaveCount(0);
  await noOverflow(page);
  await page.screenshot({ path: info.outputPath("login.png"), fullPage: true });
});

test("catalogue searches and opens a dedicated statement page", async ({
  page,
}) => {
  await mockApi(page);
  await page.goto("/stellingen");
  await expect(
    page.getByRole("heading", { name: fixtures[0]!.title }),
  ).toBeVisible();
  await page
    .getByRole("searchbox", { name: "Zoek een stelling" })
    .fill("wonen");
  await expect(
    page.getByRole("heading", { name: fixtures[1]!.title }),
  ).toBeHidden();
  await page
    .getByRole("link", { name: /Hoe maken we wonen betaalbaar/ })
    .click();
  await expect(page).toHaveURL(/\/stellingen\/wonen-betaalbaar$/);
  await expect(page.locator(".vote-card-v2 h2")).toHaveText(fixtures[0]!.title);
  await noOverflow(page);
});

test("profile groups personal history, compass and settings", async ({
  page,
}, info) => {
  await mockApi(page, true);
  await page.goto("/profiel");
  await expect(
    page.getByRole("heading", { level: 1, name: "Mijn profiel" }),
  ).toBeVisible();
  await expect(
    page.getByRole("heading", { name: "Mijn stemmen" }),
  ).toBeVisible();
  await expect(
    page.getByRole("heading", { name: "Mijn kompas" }),
  ).toBeVisible();
  await expect(
    page.getByText("Beantwoorde stellingen", { exact: true }),
  ).toBeVisible();
  if ((page.viewportSize()?.width ?? 1000) <= 720)
    await expect(
      page.getByRole("navigation", { name: "Appnavigatie" }).getByRole("link"),
    ).toHaveCount(4);
  await noOverflow(page);
  await page.screenshot({
    path: info.outputPath("profile.png"),
    fullPage: true,
  });
});

test("account security stays inside the profile navigation", async ({
  page,
}) => {
  await mockApi(page, true);
  await page.goto("/profiel");
  await page
    .locator(".profile-home__settings")
    .getByRole("link", { name: /Beveiliging/ })
    .click();
  await expect(page.getByLabel("Huidig wachtwoord")).toBeVisible();
  if ((page.viewportSize()?.width ?? 1000) <= 720) {
    await expect(
      page
        .getByRole("navigation", { name: "Appnavigatie" })
        .getByRole("link", { name: "Profiel" }),
    ).toHaveClass(/tab-active/);
  }
  await noOverflow(page);
});
