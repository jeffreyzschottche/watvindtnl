<template>
  <Teleport to="body">
    <div class="modal" @keydown.esc.prevent="emitClose">
      <div class="modal__backdrop" @click="emitClose" />
      <div class="modal__dialog" role="dialog" aria-modal="true">
        <header class="modal__header">
          <h2 class="modal__title">Wachtwoord resetten</h2>
          <button type="button" class="modal__close" @click="emitClose" aria-label="Sluiten">
            ×
          </button>
        </header>
        <form class="modal__body" @submit.prevent="onSubmit">
          <p class="modal__intro">
            Vul je e-mailadres in. Als het bij ons bekend is, sturen we je een mail met een link om je wachtwoord opnieuw in te stellen.
          </p>
          <label class="modal__field">
            <span>E-mailadres</span>
            <input
              v-model="email"
              type="email"
              autocomplete="email"
              required
              :disabled="submitted"
            />
          </label>

          <p v-if="error" class="modal__error">{{ error }}</p>
          <p v-if="message" class="modal__message">{{ message }}</p>

          <footer class="modal__actions">
            <button type="button" class="modal__secondary" @click="emitClose">
              Sluiten
            </button>
            <button class="modal__primary" :disabled="loading || submitted">
              {{ loading ? "Versturen..." : submitted ? "Mail verstuurd" : "Verstuur resetlink" }}
            </button>
          </footer>
        </form>
      </div>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import { onMounted, watch } from "vue";

const props = defineProps<{ initialEmail?: string }>();
const emit = defineEmits<{ (e: "close"): void }>();

const email = ref("");
const loading = ref(false);
const submitted = ref(false);
const error = ref<string | null>(null);
const message = ref<string | null>(null);

const { requestPasswordReset } = useAuth();

onMounted(() => {
  if (props.initialEmail) {
    email.value = props.initialEmail;
  }
});

watch(
  () => props.initialEmail,
  (value) => {
    if (!submitted.value && value) {
      email.value = value;
    }
  }
);

function emitClose() {
  emit("close");
}

async function onSubmit() {
  error.value = null;
  message.value = null;
  loading.value = true;
  try {
    const { message: responseMessage } = await requestPasswordReset(email.value);
    submitted.value = true;
    message.value =
      responseMessage ||
      "Als het e-mailadres bij ons bekend is, ontvang je binnen enkele ogenblikken een e-mail met verdere instructies.";
  } catch (e: any) {
    error.value = e?.message || "Verzenden mislukt.";
  } finally {
    loading.value = false;
  }
}
</script>

<style scoped>
.modal {
  position: fixed;
  inset: 0;
  z-index: 1600;
  display: grid;
  place-items: center;
  padding: 1.5rem;
}

.modal__backdrop {
  position: absolute;
  inset: 0;
  background: rgba(15, 23, 42, 0.6);
  backdrop-filter: blur(2px);
}

.modal__dialog {
  position: relative;
  width: min(480px, 100%);
  border-radius: 18px;
  background: linear-gradient(135deg, rgba(255, 255, 255, 0.97), rgba(238, 241, 246, 0.97));
  box-shadow: 0 24px 48px rgba(0, 26, 77, 0.28);
  border: 1px solid rgba(0, 63, 125, 0.35);
  padding: 1.75rem;
  display: grid;
  gap: 1.25rem;
}

.modal__header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
}

.modal__title {
  margin: 0;
  font-size: 1.35rem;
  color: var(--color-primary, #003366);
}

.modal__close {
  border: none;
  background: rgba(255, 255, 255, 0.95);
  border-radius: 999px;
  width: 2.2rem;
  height: 2.2rem;
  font-size: 1.5rem;
  color: var(--color-accent, #ff5003);
  cursor: pointer;
  box-shadow: 0 6px 14px rgba(0, 26, 77, 0.18);
}

.modal__close:hover,
.modal__close:focus-visible {
  background: rgba(255, 80, 3, 0.2);
}

.modal__body {
  display: grid;
  gap: 1.25rem;
}

.modal__intro {
  margin: 0;
  color: #1f2937;
  line-height: 1.5;
}

.modal__field {
  display: grid;
  gap: 0.45rem;
}

.modal__field input {
  padding: 0.7rem 0.9rem;
  border-radius: 0.75rem;
  border: 1px solid rgba(0, 63, 125, 0.35);
  font-size: 1rem;
}

.modal__field input:disabled {
  background: rgba(15, 23, 42, 0.05);
  color: rgba(15, 23, 42, 0.7);
}

.modal__error {
  margin: 0;
  color: #b91c1c;
  font-weight: 600;
}

.modal__message {
  margin: 0;
  color: #047857;
  font-weight: 600;
}

.modal__actions {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
}

.modal__secondary,
.modal__primary {
  border-radius: 999px;
  padding: 0.6rem 1.4rem;
  font-weight: 700;
  cursor: pointer;
  border: none;
}

.modal__secondary {
  background: rgba(15, 23, 42, 0.05);
  color: #0f172a;
}

.modal__primary {
  background: linear-gradient(
    130deg,
    var(--color-accent, #ff5003),
    var(--color-primary, #003366)
  );
  color: #fff;
  box-shadow: 0 12px 24px rgba(0, 35, 71, 0.35);
}

.modal__primary[disabled] {
  opacity: 0.6;
  cursor: not-allowed;
  box-shadow: none;
}
</style>
