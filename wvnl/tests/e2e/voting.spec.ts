import { test, expect, type Page } from "@playwright/test";

// Explicit contract fixtures: browser tests never alter real political data.
const statements = [42, 43].map((id) => ({
  id,
  title: `Teststelling ${id}`,
  slug: `test-${id}`,
  description: "Een stelling voor de browserregressietest.",
  more_info: null,
  url: null,
  news_article: null,
  news_article_slug: null,
  votes: null,
  user_vote: null,
  party_stances: { agree: [], disagree: [], neutral: [] },
  arguments: { pro: [], con: [] },
  reports: {},
  created_at: null,
  updated_at: null,
}));
async function setup(page: Page, fail = false) {
  const votes: number[] = [];
  const trackers: string[] = [];
  page.on("request", (req) => {
    if (/googlesyndication|googletagmanager|google-analytics/.test(req.url()))
      trackers.push(req.url());
  });
  await page.context().addCookies([
    {
      name: "token",
      value: "explicit-e2e-fixture",
      url: "http://localhost:3010",
    },
  ]);
  await page.route("**/api/**", async (route) => {
    const url = new URL(route.request().url());
    const headers = {
      "Access-Control-Allow-Origin": "http://localhost:3010",
      "Access-Control-Allow-Headers": "*",
      "Access-Control-Allow-Methods": "GET, POST, OPTIONS",
    };
    if (route.request().method() === "OPTIONS")
      return route.fulfill({ status: 204, headers });
    if (url.pathname === "/api/me")
      return route.fulfill({
        json: {
          id: 90001,
          name: "Browserfixture",
          email: "fixture@example.test",
          premium: false,
          voted_issue_ids: [],
        },
        headers,
      });
    if (url.pathname === "/api/issues/pending")
      return route.fulfill({
        json:
          Number(url.searchParams.get("offset")) > 0
            ? []
            : statements.filter((item) => !votes.includes(item.id)),
        headers,
      });
    const vote = url.pathname.match(/\/issues\/(\d+)\/vote$/);
    if (vote) {
      if (fail)
        return route.fulfill({
          status: 503,
          json: { message: "Stemmen tijdelijk niet beschikbaar." },
          headers,
        });
      const id = Number(vote[1]);
      votes.push(id);
      const choice = route.request().postDataJSON().vote;
      return route.fulfill({
        json: {
          status: "ok",
          vote: choice,
          user_vote: choice,
          voted_issue_ids: [...votes],
          votes: { agree: 64, disagree: 36, neutral: 0 },
        },
        headers,
      });
    }
    return route.fulfill({
      status: 404,
      json: { message: "Unknown explicit test fixture" },
      headers,
    });
  });
  return { votes, trackers };
}

test("result stays on the same statement until the user clicks next", async ({
  page,
}) => {
  const { votes, trackers } = await setup(page);
  await page.goto("/spel");
  const title = page.locator(".vote-card-v2 h2");
  await expect(title).toBeVisible();
  const original = await title.textContent();
  await expect(
    page.getByRole("heading", { name: "Zo denken de deelnemers" }),
  ).toBeHidden();
  const against = page.getByRole("button", { name: "Tegen Ik ben het oneens" });
  const support = page.getByRole("button", { name: "Voor Ik ben het eens" });
  const background = page.locator(".vote-card-v2__background");
  await expect(background).not.toHaveAttribute("open");
  const report = page
    .locator(".vote-card-v2__meta")
    .getByRole("button", { name: "Rapporteer" });
  expect((await report.boundingBox())!.height).toBeGreaterThanOrEqual(44);
  await report.click();
  await expect(report).toHaveAttribute("aria-expanded", "true");
  await expect(page.locator(".report-menu__option").first()).toBeFocused();
  await page.keyboard.press("Escape");
  await expect(report).toBeFocused();
  await expect(report).toHaveAttribute("aria-expanded", "false");
  expect((await against.boundingBox())!.x).toBeLessThan(
    (await support.boundingBox())!.x,
  );
  await support.click();
  await expect(
    page.getByRole("heading", { name: "Zo denken de deelnemers" }),
  ).toBeVisible();
  await expect(title).toHaveText(original!);
  await expect(background).not.toHaveAttribute("open");
  await expect(support).toBeHidden();
  await expect(page.getByText("64%", { exact: true })).toBeVisible();
  expect(votes).toHaveLength(1);
  await expect(page.getByTestId("vote-confetti")).toBeVisible();
  await expect(page.getByTestId("vote-confetti")).toBeHidden();
  await expect(page.getByLabel("Advertentieplaatsing (demo)")).toBeVisible();
  await page.getByRole("button", { name: "Volgende stelling" }).click();
  await expect(title).not.toHaveText(original!);
  expect(votes).toHaveLength(1);
  expect(trackers).toEqual([]);
});

test("failed vote keeps the question available and does not show a result", async ({
  page,
}) => {
  const { votes } = await setup(page, true);
  await page.goto("/spel");
  const title = page.locator(".vote-card-v2 h2");
  await expect(title).toBeVisible();
  const original = await title.textContent();
  await page.getByRole("button", { name: "Tegen Ik ben het oneens" }).click();
  await expect(page.locator(".issues-page__inline-error")).toBeVisible();
  await expect(title).toHaveText(original!);
  await expect(
    page.getByRole("button", { name: "Volgende stelling" }),
  ).toBeHidden();
  expect(votes).toHaveLength(0);
});

test("against receives the same celebration and does not move on automatically", async ({
  page,
}) => {
  await setup(page);
  await page.goto("/spel");
  const title = page.locator(".vote-card-v2 h2");
  await expect(title).toBeVisible();
  const original = await title.textContent();
  await page.getByRole("button", { name: "Tegen Ik ben het oneens" }).click();
  await expect(page.getByTestId("vote-confetti")).toBeVisible();
  await expect(
    page.getByText("Je hebt tegen gestemd", { exact: false }),
  ).toBeVisible();
  await expect(title).toHaveText(original!);
  await expect(
    page.getByRole("button", { name: "Volgende stelling" }),
  ).toBeVisible();
});

test("system reduced motion suppresses confetti while voting remains functional", async ({
  page,
}) => {
  await page.emulateMedia({ reducedMotion: "reduce" });
  await setup(page);
  await page.goto("/spel");
  await expect(page.locator("#main-content")).toHaveAttribute(
    "data-motion",
    "off",
  );
  await page.getByRole("button", { name: "Voor Ik ben het eens" }).click();
  await expect(
    page.getByRole("heading", { name: "Zo denken de deelnemers" }),
  ).toBeVisible();
  await expect(page.getByTestId("vote-confetti")).toHaveCount(0);
  await expect(
    page.getByRole("button", { name: "Minder beweging actief" }),
  ).toBeDisabled();
});

test("motion opt out is shared with the vote card and survives a reload", async ({
  page,
}) => {
  await setup(page);
  await page.goto("/spel");
  await page.getByRole("button", { name: "Animaties uitzetten" }).click();
  await expect(page.locator("#main-content")).toHaveAttribute(
    "data-motion",
    "off",
  );
  await page.reload();
  await expect(page.locator("#main-content")).toHaveAttribute(
    "data-motion",
    "off",
  );
  await page.getByRole("button", { name: "Voor Ik ben het eens" }).click();
  await expect(
    page.getByRole("heading", { name: "Zo denken de deelnemers" }),
  ).toBeVisible();
  await expect(page.getByTestId("vote-confetti")).toHaveCount(0);
});
