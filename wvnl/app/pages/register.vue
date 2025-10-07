<template>
  <div class="p-6">
    <registerScreen />
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
