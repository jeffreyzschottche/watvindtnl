<template>
  <div
    ref="root"
    class="report-menu"
    :class="[`report-menu--${size}`]"
    @keydown.esc="close"
  >
    <button
      type="button"
      ref="trigger"
      class="report-menu__trigger"
      :disabled="loading"
      :aria-expanded="open"
      :aria-controls="panelId"
      @click="toggle"
    >
      {{ buttonLabel }}
    </button>
    <div
      v-if="open"
      :id="panelId"
      class="report-menu__panel"
      :aria-busy="loading"
    >
      <p class="report-menu__title">Waarom wil je dit rapporteren?</p>
      <ul class="report-menu__options">
        <li v-for="option in options" :key="option.value">
          <button
            type="button"
            class="report-menu__option"
            :disabled="loading"
            @click="() => handleSelect(option.value)"
          >
            {{ option.label }}
          </button>
        </li>
      </ul>
    </div>
    <p
      v-if="feedback"
      role="status"
      class="report-menu__feedback"
      :class="feedbackClass"
    >
      {{ feedback.message }}
    </p>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from "vue";
import { REPORT_REASON_OPTIONS, type ReportReason } from "~/types/reports";
import { translateErrorMessage } from "~/utils/translateErrorMessage";

const props = withDefaults(
  defineProps<{
    submit: (reason: ReportReason) => Promise<unknown>;
    buttonLabel?: string;
    successMessage?: string;
    errorMessage?: string;
    size?: "sm" | "md";
  }>(),
  {
    buttonLabel: "Rapporteer",
    successMessage: "Bedankt voor je melding.",
    errorMessage: "Er ging iets mis. Probeer het later opnieuw.",
    size: "md",
  },
);

const open = ref(false);
const root = ref<HTMLElement | null>(null);
const trigger = ref<HTMLButtonElement | null>(null);
const panelId = useId();
function close() {
  if (!open.value) return;
  open.value = false;
  trigger.value?.focus();
}
function outside(event: PointerEvent) {
  if (event.target instanceof Node && !root.value?.contains(event.target))
    open.value = false;
}
onMounted(() => document.addEventListener("pointerdown", outside));
onBeforeUnmount(() => document.removeEventListener("pointerdown", outside));
const loading = ref(false);
const feedback = ref<{ type: "success" | "error"; message: string } | null>(
  null,
);

const options = computed(() => REPORT_REASON_OPTIONS);

const feedbackClass = computed(() =>
  feedback.value ? `report-menu__feedback--${feedback.value.type}` : null,
);

async function toggle() {
  open.value = !open.value;
  if (open.value) {
    feedback.value = null;
    await nextTick();
    root.value
      ?.querySelector<HTMLButtonElement>(".report-menu__option")
      ?.focus();
  }
}

async function handleSelect(reason: ReportReason) {
  loading.value = true;

  try {
    await props.submit(reason);
    feedback.value = {
      type: "success",
      message: props.successMessage,
    };
    close();
  } catch (error) {
    const message = translateErrorMessage(error, {
      fallback: props.errorMessage,
    });
    const statusCode =
      typeof error === "object" && error && "statusCode" in (error as any)
        ? Number((error as any).statusCode)
        : null;

    feedback.value = {
      type: "error",
      message:
        statusCode === 401
          ? "Log in om te rapporteren."
          : message || props.errorMessage,
    };
  } finally {
    loading.value = false;
    if (!open.value) {
      await nextTick();
      trigger.value?.focus();
    }
  }
}
</script>

<style scoped>
.report-menu {
  display: grid;
  gap: 0.5rem;
  position: relative;
}

.report-menu__trigger {
  border: 1px solid #dce3ee;
  background: #f5f7fa;
  color: #526277;
  border-radius: 9999px;
  font-size: 0.85rem;
  font-weight: 600;
  padding: 0.45rem 1rem;
  cursor: pointer;
  transition:
    background-color 0.2s ease,
    color 0.2s ease,
    transform 0.2s ease,
    box-shadow 0.2s ease;
  min-height: 44px;
  max-width: 125px;
  text-align: center;
}

.report-menu--sm .report-menu__trigger {
  font-size: 0.75rem;
  padding: 0.3rem 0.75rem;
}

.report-menu__trigger:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.report-menu__trigger:not(:disabled):hover,
.report-menu__trigger:not(:disabled):focus-visible {
  color: #0057b8;
  background: #edf3ff;
  border-color: #a8c2e4;
}

.report-menu__panel {
  position: absolute;
  top: 100%;
  right: 0;
  margin-top: 0.5rem;
  width: min(300px, calc(100vw - 64px));
  border-radius: 1rem;
  background: #fff;
  box-shadow: 0 12px 35px #001f4d1a;
  padding: 1.1rem;
  border: 1px solid #dce3ee;
  z-index: 10;
}

.report-menu__title {
  margin: 0 0 0.75rem;
  font-size: 0.95rem;
  font-weight: 700;
  color: #001f4d;
}

.report-menu__options {
  display: grid;
  gap: 0.5rem;
  margin: 0;
  padding: 0;
  list-style: none;
}

.report-menu__option {
  width: 100%;
  border: 1px solid #dce3ee;
  background: #f5f7fa;
  min-height: 44px;
  border-radius: 0.65rem;
  padding: 0.6rem 0.85rem;
  text-align: left;
  font-size: 0.9rem;
  color: #001f4d;
  cursor: pointer;
  transition:
    background-color 0.2s ease,
    border-color 0.2s ease,
    color 0.2s ease,
    transform 0.2s ease;
}

.report-menu__option:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.report-menu__option:not(:disabled):hover,
.report-menu__option:not(:disabled):focus-visible {
  background: #edf3ff;
  border-color: #a8c2e4;
  color: #0057b8;
}

.report-menu__feedback {
  margin: 0;
  font-size: 0.8rem;
}

.report-menu__feedback--success {
  color: #15803d;
}

.report-menu__feedback--error {
  color: #b91c1c;
}
</style>
