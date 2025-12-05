import { translateErrorMessage } from "~/utils/translateErrorMessage";

function extractValidationMessage(payload: unknown): string | null {
  if (
    payload &&
    typeof payload === "object" &&
    "errors" in (payload as Record<string, unknown>)
  ) {
    const errors = (payload as Record<string, unknown>).errors;
    if (errors && typeof errors === "object") {
      for (const value of Object.values(errors)) {
        if (Array.isArray(value) && value.length) {
          const first = value.find((item) => typeof item === "string");
          if (typeof first === "string") {
            return first;
          }
        } else if (typeof value === "string") {
          return value;
        }
      }
    }
  }

  return null;
}

export async function apiFetch<T>(
  endpoint: string,
  init?: RequestInit
): Promise<T> {
  const base = useRuntimeConfig().public.apiBase || "http://localhost:8000/api";
  const res = await fetch(`${base}${endpoint}`, init);

  const isJson = res.headers.get("content-type")?.includes("application/json");
  const payload = isJson ? await res.json() : await res.text();

  if (!res.ok) {
    const validationError = extractValidationMessage(payload);
    const rawMessage =
      validationError ||
      (typeof payload === "string"
        ? payload
        : typeof payload?.message === "string"
        ? payload.message
        : null);

    const message = translateErrorMessage(rawMessage, {
      fallback: `Er ging iets mis (code ${res.status}).`,
      statusCode: res.status,
    });

    const error = new Error(message);
    (error as any).statusCode = res.status;
    (error as any).rawMessage = rawMessage;
    throw error;
  }
  return payload as T;
}
