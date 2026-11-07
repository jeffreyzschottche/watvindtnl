<script setup lang="ts">
definePageMeta({ alias: "/registreren" });
import registerScreen from "~/components/registerScreen.vue";
const auth = useAuthStore();
watchEffect(() => {
  if (auth.isLoggedIn) void navigateTo("/spel");
});
useSeoMeta({ title: "Account aanmaken | WatDenktNederland" });
</script>
<template>
  <section class="auth-shell auth-shell--wide"><registerScreen /></section>
</template>
