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
          votes:
            choice === "neutral"
              ? { agree: 1, disagree: 0, neutral: 1 }
              : { agree: 64, disagree: 36, neutral: 0 },
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
  if (test.info().project.name === "mobile") {
    // In the phone app the footer is hidden; the motion setting lives under Profiel.
    await page.goto("/profiel");
    await expect(
      page.getByRole("button", { name: /Animaties Minder beweging/ }),
    ).toBeDisabled();
  } else {
    await expect(page.locator("#main-content")).toHaveAttribute(
      "data-motion",
      "off",
    );
  }
});

test("motion opt out is shared with the vote card and survives a reload", async ({
  page,
}) => {
  // The motion setting only lives under Profiel on phones; desktop follows the system preference.
  test.skip(test.info().project.name !== "mobile", "Mobile-only setting");
  await setup(page);
  await page.goto("/profiel");
  await page.getByRole("button", { name: /Animaties Aan/ }).click();
  await page.goto("/spel");
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

test("neutral is saved as its own choice and the result waits for manual next", async ({
  page,
}) => {
  const { votes } = await setup(page);
  await page.goto("/spel");
  const title = page.locator(".vote-card-v2 h2");
  await expect(title).toBeVisible();
  const original = await title.textContent();
  await page
    .getByRole("button", { name: "Neutraal Maakt me niet uit / geen voorkeur" })
    .click();
  await expect(
    page.getByText("Je hebt neutraal gestemd", { exact: false }),
  ).toBeVisible();
  await expect(
    page.locator(".vote-card-v2__numbers .neutral-text"),
  ).toContainText("50%");
  await expect(
    page.locator(".vote-card-v2__numbers .neutral-text"),
  ).toContainText("Neutraal · 1");
  await expect(page.locator(".vote-card-v2")).not.toContainText("historisch");
  await expect(title).toHaveText(original!);
  expect(votes).toHaveLength(1);
  await page.getByRole("button", { name: "Volgende stelling" }).click();
  await expect(title).not.toHaveText(original!);
});

test("phone app keeps all three choices in the thumb zone, also while reading arguments", async ({
  page,
}) => {
  test.skip(test.info().project.name !== "mobile", "phone layout only");
  await setup(page);
  await page.goto("/spel");
  const tabbar = page.getByRole("navigation", { name: "Appnavigatie" });
  const against = page.getByRole("button", { name: "Tegen Ik ben het oneens" });
  const neutral = page.getByRole("button", {
    name: "Neutraal Maakt me niet uit / geen voorkeur",
  });
  const agree = page.getByRole("button", { name: "Voor Ik ben het eens" });
  await expect(agree).toBeVisible();
  const [a, n, f, tab] = await Promise.all(
    [against, neutral, agree, tabbar].map((l) => l.boundingBox()),
  );
  // One row, equal size for tegen and voor, neutraal in between, all above the tab bar.
  expect(Math.abs(a!.y - f!.y)).toBeLessThan(2);
  expect(Math.abs(a!.width - f!.width)).toBeLessThan(2);
  expect(a!.x).toBeLessThan(n!.x);
  expect(n!.x).toBeLessThan(f!.x);
  expect(a!.height).toBeGreaterThanOrEqual(56);
  expect(f!.y + f!.height).toBeLessThanOrEqual(tab!.y);
  // Apps have one menu and no web footer.
  await expect(page.getByRole("button", { name: "Menu openen" })).toBeHidden();
  await expect(page.locator(".site-footer")).toBeHidden();
  await page.locator(".vote-card-v2__background summary").click();
  await page.mouse.wheel(0, 1200);
  await expect(agree).toBeInViewport();
  const pinned = await agree.boundingBox();
  expect(pinned!.y + pinned!.height).toBeLessThanOrEqual(tab!.y);
});
