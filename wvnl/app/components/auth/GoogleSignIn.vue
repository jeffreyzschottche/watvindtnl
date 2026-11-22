<script setup lang="ts">
/** "Doorgaan met Google": Google's own button; the backend verifies the ID token. */
import { translateErrorMessage } from "~/utils/translateErrorMessage";

type GoogleId = {
  initialize: (options: Record<string, unknown>) => void;
  renderButton: (element: HTMLElement, options: Record<string, unknown>) => void;
};
declare global {
  interface Window {
    google?: { accounts?: { id?: GoogleId } };
  }
}

const config = useRuntimeConfig();
const clientId = String(config.public.googleClientId || "");
const { loginWithGoogle } = useAuth();
const router = useRouter();
const button = ref<HTMLElement | null>(null);
const error = ref("");
const loading = ref(false);

function loadScript(): Promise<void> {
  if (window.google?.accounts?.id) return Promise.resolve();
  return new Promise((resolve, reject) => {
    const existing = document.getElementById("google-gsi");
    const script = (existing as HTMLScriptElement | null) ?? document.createElement("script");
    script.addEventListener("load", () => resolve(), { once: true });
    script.addEventListener("error", () => reject(new Error("Google is niet bereikbaar.")), { once: true });
    if (!existing) {
      script.id = "google-gsi";
      script.src = "https://accounts.google.com/gsi/client";
      script.async = true;
      document.head.appendChild(script);
    }
  });
}

async function onCredential(response: { credential?: string }) {
  if (!response.credential) return;
  error.value = "";
  loading.value = true;
  try {
    await loginWithGoogle(response.credential);
    router.push("/spel");
  } catch (e) {
    error.value = translateErrorMessage(e, {
      fallback: "Inloggen met Google is niet gelukt.",
    });
  } finally {
    loading.value = false;
  }
}

onMounted(async () => {
  if (!clientId || !button.value) return;
  try {
    await loadScript();
    const id = window.google?.accounts?.id;
    if (!id || !button.value) return;
    id.initialize({
      client_id: clientId,
      callback: onCredential,
      ux_mode: "popup",
      context: "signin",
      itp_support: true,
    });
    id.renderButton(button.value, {
      type: "standard",
      theme: "outline",
      size: "large",
      text: "continue_with",
      shape: "pill",
      logo_alignment: "center",
      locale: "nl",
      width: Math.min(400, button.value.clientWidth || 320),
    });
  } catch (e) {
    error.value = e instanceof Error ? e.message : "Google is niet bereikbaar.";
  }
});
</script>

<template>
  <div v-if="clientId" class="google-signin">
    <div ref="button" class="google-signin__button" :aria-busy="loading" />
    <p v-if="error" class="form-error" role="alert">{{ error }}</p>
    <p class="google-signin__note">
      Geen wachtwoord nodig. Door verder te gaan met Google ga je akkoord met de
      <NuxtLink to="/algemene-voorwaarden">algemene voorwaarden</NuxtLink>.
    </p>
    <div class="google-signin__divider" aria-hidden="true"><span>of met e-mail</span></div>
  </div>
</template>

<style scoped>
.google-signin {
  display: grid;
  gap: 0.6rem;
  margin-bottom: 0.4rem;
}
.google-signin__button {
  display: flex;
  justify-content: center;
  min-height: 44px;
  width: 100%;
}
.google-signin__note {
  margin: 0;
  color: var(--wdnl-muted);
  font-size: 0.76rem;
  line-height: 1.5;
  text-align: center;
}
.google-signin__divider {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin-top: 0.4rem;
  color: #8593a6;
  font-size: 0.75rem;
}
.google-signin__divider::before,
.google-signin__divider::after {
  content: "";
  flex: 1;
  height: 1px;
  background: var(--wdnl-border);
}
</style>
