import { useAuthStore } from "~/stores/auth";

export default defineNuxtPlugin(async () => {
  const store = useAuthStore();
  await store.restore();
});
