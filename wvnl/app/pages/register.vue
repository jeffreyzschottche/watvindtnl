<template>
  <div class="page">
    <section class="page-hero">
      <div class="container narrow">
        <h1>Account aanmaken</h1>
        <p>Sluit je aan bij de community en maak in een paar stappen je profiel aan.</p>
      </div>
    </section>

    <section class="container narrow">
      <registerScreen />
    </section>
  </div>
</template>

<script setup lang="ts">
import registerScreen from "~/components/registerScreen.vue";
const { isLoggedIn } = storeToRefs(useAuthStore());
const router = useRouter();

watchEffect(() => {
  if (isLoggedIn.value) router.replace("/");
});
</script>
