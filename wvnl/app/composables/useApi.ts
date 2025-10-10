import { apiFetch } from "~/services/apiFetch";
import { useAuthStore } from "~/stores/auth";

export function useApi() {
  const auth = useAuthStore();

  async function request<T>(
    method: "GET" | "POST" | "PATCH" | "DELETE",
    endpoint: string,
    body?: any
  ): Promise<T> {
    const headers: HeadersInit = {
      Accept: "application/json",
      "X-Requested-With": "XMLHttpRequest",
    };
    if (auth.token) headers.Authorization = `Bearer ${auth.token}`;

    const isFormData = typeof FormData !== "undefined" && body instanceof FormData;

    if (body !== undefined && !isFormData) {
      headers["Content-Type"] = "application/json";
    }

    return apiFetch<T>(endpoint, {
      method,
      headers,
      body:
        body !== undefined
          ? (isFormData ? body : JSON.stringify(body))
          : undefined,
    });
  }

  return {
    get: <T>(e: string) => request<T>("GET", e),
    post: <T>(e: string, b?: any) => request<T>("POST", e, b),
    patch: <T>(e: string, b?: any) => request<T>("PATCH", e, b),
    del: <T>(e: string) => request<T>("DELETE", e),
  };
}
