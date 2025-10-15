export async function apiFetch<T>(
  endpoint: string,
  init?: RequestInit
): Promise<T> {
  const base =
    useRuntimeConfig().public.apiBase || "https://api.watdenktnl.nl/api";
  const res = await fetch(`${base}${endpoint}`, init);

  const isJson = res.headers.get("content-type")?.includes("application/json");
  const payload = isJson ? await res.json() : await res.text();

  if (!res.ok) {
    // gooi nette fout door naar UI
    throw new Error(
      typeof payload === "string"
        ? payload
        : payload?.message || `HTTP ${res.status}`
    );
  }
  return payload as T;
}
