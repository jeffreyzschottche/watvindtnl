<script setup lang="ts">
import GoogleTestAd from "~/components/common/GoogleTestAd.vue";

const props = defineProps<{ open: boolean }>();
const emit = defineEmits<{ close: [] }>();
const dialog = ref<HTMLDialogElement | null>(null);
const titleId = useId();
const googleTest = useGoogleTestAds();
async function sync() {
  await nextTick();
  if (props.open && dialog.value && !dialog.value.open)
    dialog.value.showModal();
  else if (!props.open && dialog.value?.open) dialog.value.close();
}
watch(() => props.open, sync);
onMounted(sync);
onBeforeUnmount(() => dialog.value?.close());
</script>
<template>
  <Teleport to="body"
    ><dialog
      ref="dialog"
      class="demo-ad-dialog"
      :aria-labelledby="titleId"
      @close="emit('close')"
    >
      <header>
        <span>{{
          googleTest ? "Advertentie · Google-test" : "Advertentie · demo"
        }}</span
        ><button
          type="button"
          aria-label="Demo-advertentie sluiten"
          autofocus
          @click="dialog?.close()"
        >
          ✕
        </button>
      </header>
      <template v-if="googleTest">
        <h2 :id="titleId" class="sr-only">
          Pauze met een Google-testadvertentie
        </h2>
        <GoogleTestAd v-if="open" />
      </template>
      <template v-else>
        <div class="demo-ad-dialog__art" aria-hidden="true">✦</div>
        <h2 :id="titleId">Een kleine pauze.<br />Daarna weer jouw mening.</h2>
        <p>
          Dit is een voorbeeld van een advertentieplek. Er wordt geen echte
          advertentie geladen en niets bijgehouden.
        </p>
      </template>
      <button
        class="button button-secondary"
        type="button"
        @click="dialog?.close()"
      >
        Verder met WDNL →
      </button>
    </dialog></Teleport
  >
</template>
<style scoped>
.demo-ad-dialog {
  width: min(440px, calc(100vw - 32px));
  max-height: calc(100dvh - 48px);
  overflow: auto;
  border: 1px solid #d6e1f0;
  border-radius: 24px;
  padding: 1.4rem;
  background: #f7faff;
  color: #001f4d;
  box-shadow: 0 30px 100px #001f4d33;
  text-align: center;
}
.demo-ad-dialog::backdrop {
  background: #00172f8f;
}
.demo-ad-dialog header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  margin-bottom: 0.5rem;
  color: #526277;
  font-size: 0.7rem;
}
.demo-ad-dialog header button {
  width: 44px;
  height: 44px;
  border: 1px solid #d6e1f0;
  border-radius: 50%;
  color: #001f4d;
  background: white;
}
.demo-ad-dialog__art {
  margin: 1.2rem auto;
  background: #e5efff;
  border-radius: 24px;
  width: 100px;
  height: 100px;
  display: grid;
  place-items: center;
  color: #527caf;
  font-size: 65px;
  box-shadow: 0 6px 0 #d5e3f8;
}
.demo-ad-dialog h2 {
  font-size: 1.65rem;
  margin: 1.8rem 0 1rem;
}
.demo-ad-dialog p {
  color: #526277;
  font-size: 0.85rem;
}
.demo-ad-dialog > .button {
  width: 100%;
  margin: 0.5rem 0;
}
</style>
