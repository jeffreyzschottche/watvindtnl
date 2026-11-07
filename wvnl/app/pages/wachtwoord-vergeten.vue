<script setup lang="ts">
const email = ref("");
const pending = ref(false);
const done = ref(false);
const error = ref("");
const { requestPasswordReset } = useAuth();
async function submit() {
  if (pending.value) return;
  pending.value = true;
  error.value = "";
  try {
    await requestPasswordReset(email.value);
    done.value = true;
  } catch {
    error.value = "De aanvraag is niet gelukt. Probeer het later opnieuw.";
  } finally {
    pending.value = false;
  }
}
useSeoMeta({ title: "Wachtwoord vergeten | WatDenktNederland" });
</script>
<template>
  <section class="auth-shell">
    <form class="form" @submit.prevent="submit">
      <header class="form-header">
        <h1>Wachtwoord vergeten?</h1>
        <p>We helpen je weer op weg.</p>
      </header>
      <p v-if="done" role="status">
        Als dit e-mailadres bij ons bekend is, ontvang je een link om je
        wachtwoord te vernieuwen.
      </p>
      <template v-else
        ><label
          ><span>E-mailadres</span
          ><input
            v-model="email"
            type="email"
            autocomplete="email"
            placeholder="jij@voorbeeld.nl"
            required
        /></label>
        <p v-if="error" class="form-error" role="alert">{{ error }}</p>
        <button class="button" :disabled="pending">
          {{ pending ? "Aanvragen…" : "Verstuur resetlink" }}
        </button></template
      ><NuxtLink to="/inloggen" style="text-align: center; font-size: 0.8rem"
        >← Terug naar inloggen</NuxtLink
      >
    </form>
  </section>
</template>
