import { apiFetch } from "~/services/apiFetch";

export type ContactFormPayload = {
  name: string;
  email: string;
  message: string;
  altcha: Record<string, unknown>;
};

export function submitContactForm(payload: ContactFormPayload) {
  return apiFetch<{ success: boolean; message: string }>("/contact", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify(payload),
  });
}
