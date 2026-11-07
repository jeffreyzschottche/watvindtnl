import { test, expect } from "@playwright/test";

test("news-based statements retain independent authorship and an external source link", async ({
  page,
}) => {
  const outbound: string[] = [];
  page.on("request", (request) => {
    if (request.url().startsWith("https://example.test"))
      outbound.push(request.url());
  });
  await page.route("**/api/**", async (route) => {
    const headers = {
      "Access-Control-Allow-Origin": "http://localhost:3010",
      "Access-Control-Allow-Headers": "*",
      "Access-Control-Allow-Methods": "*",
    };
    if (route.request().method() === "OPTIONS")
      return route.fulfill({ status: 204, headers });
    if (!route.request().url().includes("/statements/test-news"))
      return route.fulfill({ status: 404, headers, json: {} });
    return route.fulfill({
      headers,
      json: {
        id: 90909,
        slug: "test-news",
        title: "Een expliciete teststelling over wonen",
        description:
          "Fictieve inhoud uitsluitend voor de browserregressietest.",
        more_info: null,
        url: "https://example.test/nieuws",
        votes: null,
        user_vote: null,
        source: {
          kind: "news",
          name: "Testredactie",
          title: "Fictieve nieuwsaanleiding voor deze test",
          url: "https://example.test/nieuws",
          published_at: "2026-01-01",
          verified_at: null,
        },
        arguments: { pro: [], con: [] },
        party_stances: { agree: [], disagree: [], neutral: [] },
        reports: {},
      },
    });
  });
  await page.goto("/stellingen/test-news");
  await page.getByText("Argumenten en achtergrond").click();
  const source = page.getByLabel("Herkomst van de stelling");
  await expect(source.getByText("Naar aanleiding van nieuws")).toBeVisible();
  await expect(
    source.getByRole("link", { name: "Lees de bron bij Testredactie" }),
  ).toHaveAttribute("href", "https://example.test/nieuws");
  await expect(source.getByText("Testredactie · 1 januari 2026")).toBeVisible();
  await expect(
    source.getByText("De vraag en argumenten zijn van WDNL.", { exact: false }),
  ).toBeVisible();
  expect(outbound).toEqual([]);
});
