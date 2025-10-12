<template>
  <div class="page">
    <section class="page-hero">
      <div class="container narrow">
        <h1>Inloggen</h1>
        <p>Log in en ga direct verder met het delen van jouw mening.</p>
      </div>
    </section>

    <section class="container narrow">
      <loginScreen />
    </section>
  </div>
</template>

<script setup lang="ts">
import loginScreen from "~/components/loginScreen.vue";
const { isLoggedIn } = storeToRefs(useAuthStore());
const router = useRouter();

watchEffect(() => {
  if (isLoggedIn.value) router.replace("/");
});
</script>
