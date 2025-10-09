// ~/composables/useAuth.ts
import { storeToRefs } from "pinia";
import { useAuthStore } from "~/stores/auth";
import { useApi } from "~/composables/useApi";
import type { AgeCategory, User } from "~/types/User";

export function useAuth() {
  const store = useAuthStore();
  const api = useApi();

  async function register(payload: {
    name: string;
    username: string;
    email: string;
    password: string;
    password_confirmation: string;
    language: "nl" | "en";
    age_category: AgeCategory;
    province: string;
    gender: "male" | "female" | "unspecified";
    education_level: "mbo" | "hbo" | "universiteit" | "overig";
    political_preference: "links" | "midden" | "rechts" | "geen";
    notification_prefs: { email?: boolean };
    cookie_prefs: { accepted: boolean; accepted_at?: string };
  }) {
    const { token, user } = await api.post<{ token: string; user: User }>(
      "/register",
      payload
    );
    store.setSession(token, user);
  }

  async function login(email: string, password: string) {
    const { token, user } = await api.post<{ token: string; user: User }>(
      "/login",
      { email, password }
    );
    store.setSession(token, user);
  }

  return {
    ...storeToRefs(store), // token, user, isLoggedIn, isPremium
    register,
    login,
    logout: store.logout,
    restore: store.restore,
    updateUser: store.updateUser,
  };
}
