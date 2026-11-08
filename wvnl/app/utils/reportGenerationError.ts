type ApiFailure = Error & { statusCode?: number; rawMessage?: unknown };
/** Only the report service's controlled, credential-free messages bypass generic form translations. */
export function reportGenerationError(cause: unknown): string {
  if (!(cause instanceof Error))
    return "Het devrapport kon niet worden gemaakt.";
  const failure = cause as ApiFailure;
  if (
    failure.statusCode === 422 &&
    typeof failure.rawMessage === "string" &&
    /^(OpenAI\b|OPENAI_(API_KEY|MODEL)\b|Dit model\b|De rapportinvoer\b|Deze generatie\b)/.test(
      failure.rawMessage,
    )
  ) {
    return failure.rawMessage;
  }
  if (cause.name === "TimeoutError" || cause.name === "AbortError")
    return "De generatie duurt te lang. De API kan al tokens hebben verbruikt. Controleer de API en probeer daarna opnieuw.";
  return cause.message || "Het devrapport kon niet worden gemaakt.";
}
