import { describe, expect, it } from "vitest";
import { reportGenerationError } from "../app/utils/reportGenerationError";
describe("development report errors", () => {
  it("shows the controlled service error instead of the generic 422 form message", () => {
    const error = Object.assign(
      new Error("Controleer de invoer en probeer het opnieuw."),
      {
        statusCode: 422,
        rawMessage:
          "OpenAI gaf rapporttekst die niet aan het rapportformaat voldoet. Er is geen standaardtekst gebruikt.",
      },
    );
    expect(reportGenerationError(error)).toBe(error.rawMessage);
  });
  it("preserves sanitized provider diagnostics even if the generic translator matched model_not_found", () => {
    const error = Object.assign(new Error("We konden dit niet vinden."), {
      statusCode: 422,
      rawMessage:
        "OpenAI weigerde de aanvraag (HTTP 404, model_not_found). Controleer OPENAI_MODEL.",
    });
    expect(reportGenerationError(error)).toContain("model_not_found");
  });
  it("does not pass raw unclassified server or validation errors through", () => {
    const error = Object.assign(new Error("Er ging iets mis."), {
      statusCode: 500,
      rawMessage: "private debug details",
    });
    expect(reportGenerationError(error)).toBe("Er ging iets mis.");
    expect(
      reportGenerationError(Object.assign(error, { statusCode: 422 })),
    ).toBe("Er ging iets mis.");
  });
  it("explains timeouts without promising that no tokens were used", () => {
    expect(
      reportGenerationError(new DOMException("timeout", "TimeoutError")),
    ).toContain("tokens");
  });
});
