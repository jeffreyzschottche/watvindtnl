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
    if (url.pathname === "/api/me" && route.request().method() === "DELETE")
      return route.fulfill({
        headers,
        status:
          route.request().postDataJSON().current_password === "fixture-password"
            ? 200
            : 422,
        json: { message: "Je account is verwijderd." },
      });
    if (url.pathname === "/api/logout")
      return route.fulfill({ headers, json: { message: "Uitgelogd." } });
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
    if (url.pathname === "/api/me/compass-progress")
      return route.fulfill({
        headers,
        json: {
          done: 2,
          target: 25,
          needed: 23,
          used_votes: 2,
          status: "insufficient_data",
          type: null,
          next_theme: null,
          themes: [],
        },
      });
    if (url.pathname === "/api/reports/examples")
      return route.fulfill({
        headers,
        json: [
          {
            id: "groene-vernieuwer",
            name: "De Groene Vernieuwer",
            icon: "leaf",
            color: "#237b55",
            description: "Duurzaamheid en vernieuwing.",
            dimensions: [
              { left: "Groen", right: "Economische groei", percentage: 77 },
            ],
          },
          {
            id: "internationale-verbinder",
            name: "De Internationale Verbinder",
            icon: "globe",
            color: "#087d92",
            description: "Internationale samenwerking.",
            dimensions: [
              { left: "Internationaal", right: "Nationaal", percentage: 77 },
            ],
          },
        ],
      });
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

test("homepage gives voting priority and groups the result, topics and compass links", async ({
  page,
}, info) => {
  await mockApi(page);
  await page.emulateMedia({ reducedMotion: "reduce" });
  await page.goto("/");
  await expect(page.getByRole("heading", { level: 1 })).toHaveText(
    "Wat zou jij stemmen?",
  );
  await expect(
    page.getByRole("link", { name: "WatDenktNederland — home" }).first(),
  ).toBeVisible();
  await expect(
    page.getByRole("heading", { name: fixtures[0]!.title }),
  ).toBeVisible();
  await expect(
    page.locator('.hero [data-scene="neighbourhood"] img'),
  ).toHaveAttribute("src", "/illustrations/dutch-island-v2.webp");
  await expect(
    page.locator('.compass [data-scene="compass"] img'),
  ).toHaveAttribute("src", "/illustrations/compass-v2.webp");
  await expect(page.locator(".share__art")).toHaveAttribute(
    "src",
    "/illustrations/dutch-neighbours-v1.jpg",
  );
  const hero = page.getByRole("region", { name: "Wat zou jij stemmen?" });
  await expect(
    hero.getByRole("link", { name: "Begin met stemmen" }),
  ).toHaveAttribute("href", "/spel");
  await expect(
    hero.getByRole("link", { name: "Hoe werkt het?" }),
  ).toHaveAttribute("href", "#hoe-het-werkt");
  const results = page.getByRole("region", {
    name: "Van motie naar uitslag",
  });
  await expect(
    results.getByRole("heading", {
      name: "Bekijk de stemmen in de Tweede Kamer",
    }),
  ).toBeVisible();
  await expect(results.getByText("52%", { exact: true })).toBeVisible();
  await expect(results.getByText("Fictieve voorbeelduitslag")).toBeVisible();
  await expect(
    page.getByRole("link", { name: "Zoek een stelling om te delen" }),
  ).toHaveAttribute("href", "/stellingen");
  await expect(
    page.getByRole("link", { name: "Bekijk het politieke kompas" }),
  ).toHaveAttribute("href", "/kompas");
  await expect(
    page
      .getByRole("navigation", { name: "Kies een onderwerp" })
      .getByRole("link", { name: "Klimaat" }),
  ).toHaveAttribute("href", "/stellingen?zoeken=klimaat");
  for (const image of await page
    .locator(
      'img[src^="/images/home/"], [data-scene] img, .share__art',
    )
    .all()) {
    await image.scrollIntoViewIfNeeded();
    await expect
      .poll(() =>
        image.evaluate((element: HTMLImageElement) => element.naturalWidth),
      )
      .toBeGreaterThan(0);
  }
  await expect(page.locator('img[src^="/images/home/"]')).toHaveCount(6);
  await expect(
    page.locator('.topics img[src^="/images/home/"]'),
  ).toHaveCount(4);
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
  await expect(page.locator(".catalogue__feedback")).toHaveText(
    "1 stelling in beeld",
  );
  const clear = page.getByRole("button", { name: "Zoekopdracht wissen" });
  expect((await clear.boundingBox())!.height).toBeGreaterThanOrEqual(44);
  await clear.click();
  await expect(
    page.getByRole("searchbox", { name: "Zoek een stelling" }),
  ).toBeFocused();
  await expect(page.locator(".catalogue__feedback")).toHaveText(
    "2 stellingen in beeld",
  );
  await page
    .getByRole("searchbox", { name: "Zoek een stelling" })
    .fill("wonen");
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
    await expect(
      page
        .getByRole("navigation", { name: "Appnavigatie" })
        .getByRole("link", { name: "Profiel" }),
    ).toHaveAttribute("aria-current", "location");
  }
  await noOverflow(page);
});

test("profile deletion can be canceled and requires a password to submit", async ({
  page,
}) => {
  await mockApi(page, true);
  await page.goto("/profiel");
  const trigger = page.getByRole("button", { name: "Account verwijderen" });
  await trigger.click();
  const dialog = page.getByRole("dialog", { name: "Je account verwijderen?" });
  await expect(dialog).toBeVisible();
  await dialog.getByRole("button", { name: "Annuleren" }).click();
  await expect(dialog).toBeHidden();
  await expect(trigger).toBeFocused();

  await trigger.click();
  await dialog.getByLabel("Huidig wachtwoord").fill("wrong-password");
  await dialog.getByRole("button", { name: "Definitief verwijderen" }).click();
  await expect(dialog.getByRole("alert")).toHaveText(
    "Controleer je huidige wachtwoord.",
  );
  await dialog.getByLabel("Huidig wachtwoord").fill("fixture-password");
  const submitted = page.waitForRequest(
    (request) =>
      request.method() === "DELETE" && request.url().endsWith("/api/me"),
  );
  await dialog.getByRole("button", { name: "Definitief verwijderen" }).click();
  expect((await submitted).postDataJSON()).toEqual({
    current_password: "fixture-password",
  });
  await expect(
    page.getByText("Je account is verwijderd", { exact: true }),
  ).toBeVisible();
});

test("logout is available beneath the desktop profile link and on the mobile profile page", async ({
  page,
}, info) => {
  await mockApi(page, true);
  await page.goto("/profiel");
  const logout = page.getByRole("button", { name: "Uitloggen", exact: true });
  if (info.project.name === "desktop") {
    const toggle = page.getByRole("button", { name: "Profielmenu" });
    await expect(logout).toBeHidden();
    await toggle.focus();
    await toggle.press("Enter");
    await expect(logout).toBeVisible();
    await toggle.press("Escape");
    await expect(logout).toBeHidden();
    await page.getByRole("link", { name: "Mijn profiel", exact: true }).hover();
  }
  await expect(logout).toBeVisible();
  await logout.click();
  await expect(page).toHaveURL(/\/inloggen$/);
});

test("homepage uses the local font and shared animation preference", async ({
  page,
}) => {
  await mockApi(page);
  await page.goto("/");
  await expect(page.locator("#main-content")).toHaveAttribute(
    "data-motion",
    "on",
  );
  const scenes = page.locator("[data-scene]");
  await expect(scenes).toHaveCount(2);
  await expect(page.locator('[data-scene][data-animated="true"]')).toHaveCount(
    2,
  );
  const fontLoaded = await page.evaluate(async () => {
    await document.fonts.ready;
    return [...document.fonts].some(
      (font) => font.family === "Manrope" && font.status === "loaded",
    );
  });
  expect(fontLoaded).toBe(true);
  await expect(
    page.getByRole("button", { name: "Animatie pauzeren" }),
  ).toHaveCount(0);
  await expect(page.getByText("Er valt meer te ontdekken")).toHaveCount(0);
  await expect(
    page.getByRole("button", { name: "Animaties uitzetten" }),
  ).toHaveCount(0);
  await expect(page.getByRole("heading", { level: 1 })).toBeVisible();
  await noOverflow(page);
});

test("compass examples change the full report with reduced motion", async ({
  page,
}) => {
  await mockApi(page);
  await page.emulateMedia({ reducedMotion: "reduce" });
  await page.goto("/kompas");
  const explainer = page.getByRole("region", {
    name: "Van je stem naar je rapport",
  });
  await expect(
    explainer.getByRole("heading", {
      name: "Vergelijk je stemmen met partijen",
    }),
  ).toBeVisible();
  const photo = explainer.getByRole("img");
  await expect
    .poll(() =>
      photo.evaluate((element: HTMLImageElement) => element.naturalWidth),
    )
    .toBeGreaterThan(0);
  const example = page.locator(".compass-page__explainer + .report-examples");
  const report = example.getByRole("article", {
    name: "Voorbeeldrapport · fictieve antwoorden",
  });
  await expect(
    report.getByRole("heading", { name: "De Groene Vernieuwer" }),
  ).toBeVisible();
  await expect(report).toHaveAttribute("data-report-motion", "off");
  await expect(
    report.getByRole("img", {
      name: "Economisch beleid: 61% links, 39% rechts",
    }),
  ).toBeVisible();
  await expect(report.locator(".poster-type-icon img")).toHaveAttribute(
    "src",
    "/report-art/leaf-white.svg",
  );
  for (const heading of [
    "Jouw politieke persoonlijkheid",
    "Partijen die bij je passen",
    "Hoe dit rapport werkt",
  ]) {
    await expect(report.getByRole("heading", { name: heading })).toBeVisible();
  }
  await expect(
    example.getByRole("link", { name: /voorbeeld.*pdf/i }),
  ).toHaveCount(0);
  const aligned = await example
    .locator(".report-examples__controls")
    .evaluate((el) => {
      const controls = el.getBoundingClientRect();
      const section = el.closest(".report-examples")!.getBoundingClientRect();
      const select = el.querySelector("select")!.getBoundingClientRect();
      const chevron = el
        .querySelector(".report-examples__select svg")!
        .getBoundingClientRect();
      return (
        Math.abs(
          controls.left +
            controls.width / 2 -
            (section.left + section.width / 2),
        ) < 1 &&
        Math.abs(
          select.top + select.height / 2 - (chevron.top + chevron.height / 2),
        ) < 1
      );
    });
  expect(aligned).toBe(true);
  await example.getByRole("button", { name: "Volgend voorbeeldtype" }).click();
  await expect(
    report.getByRole("heading", { name: "De Internationale Verbinder" }),
  ).toBeVisible();
  await expect(report.locator(".poster-type-icon img")).toHaveAttribute(
    "src",
    "/report-art/globe-white.svg",
  );
  await expect(
    report.getByRole("img", { name: /77% Internationaal/ }),
  ).toBeVisible();
  await expect(
    report.getByRole("img", {
      name: "Economisch beleid: 58% links, 42% rechts",
    }),
  ).toBeVisible();
  await example
    .getByRole("combobox", { name: "Kies een voorbeeldtype" })
    .selectOption("groene-vernieuwer");
  await expect(report.getByRole("img", { name: /77% Groen/ })).toBeVisible();
  await noOverflow(page);
});

test("signed-in compass shows personal progress without loading public examples", async ({
  page,
}) => {
  await mockApi(page, true);
  const exampleRequests: string[] = [];
  page.on("request", (request) => {
    if (request.url().endsWith("/api/reports/examples"))
      exampleRequests.push(request.url());
  });
  await page.goto("/kompas");
  await expect(
    page.getByRole("progressbar", { name: "Kompas compleet" }),
  ).toHaveAttribute("aria-valuenow", "8");
  await expect(
    page.getByRole("link", { name: "Bekijk mijn rapporten" }),
  ).toBeVisible();
  await expect(page.locator(".report-examples")).toHaveCount(0);
  await expect(
    page.getByRole("region", { name: "Van je stem naar je rapport" }),
  ).toHaveCount(0);
  await expect(
    page.getByRole("heading", { name: "Wat haal je uit je rapport?" }),
  ).toHaveCount(0);
  expect(exampleRequests).toEqual([]);
});
