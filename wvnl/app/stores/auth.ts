// ~/stores/auth.ts
import { defineStore } from "pinia";
import { useCookie } from "#imports";
import type { User } from "~/types/User";
import { apiFetch } from "~/services/apiFetch";
import { useApi } from "~/composables/useApi";

export const useAuthStore = defineStore("auth", () => {
  const token = ref<string | null>(null);
  const user = ref<User | null>(null);

  const isLoggedIn = computed(() => !!token.value);
  const isPremium = computed(() => !!user.value?.premium);

  function setSession(
    newToken: string,
    newUser: User,
    maxAge = 60 * 60 * 24 * 14
  ) {
    token.value = newToken;
    user.value = newUser;
    useCookie("token", { maxAge, sameSite: "strict", secure: !import.meta.dev }).value = newToken; // client cookie (niet HttpOnly)
  }

  function clearSession() {
    token.value = null;
    user.value = null;
    useCookie("token").value = null;
  }

  async function logout() {
    const savedToken = token.value;
    clearSession();
    if (!savedToken) return;
    try {
      await apiFetch("/logout", { method: "POST", headers: { Accept: "application/json", Authorization: `Bearer ${savedToken}` } });
    } catch {
      // Local session is cleared even when revocation cannot reach the server.
    }
  }

  /** Herstel sessie uit cookie → haalt /me op als token bestaat */
  async function restore(api = useApi()) {
    const saved = useCookie<string>("token").value;
    if (!saved) return;
    try {
      token.value = saved;
      user.value = await api.get<User>("/me");
    } catch {
      clearSession();
    }
  }

  function updateUser(newUser: User) {
    user.value = newUser;
  }

  return {
    token,
    user,
    isLoggedIn,
    isPremium,
    setSession,
    logout,
    restore,
    updateUser,
  };
});
