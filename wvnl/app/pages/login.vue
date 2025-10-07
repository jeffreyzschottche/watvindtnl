<template>
  <div class="p-6">
    <loginScreen />
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
