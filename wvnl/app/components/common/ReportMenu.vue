<template>
  <div class="report-menu" :class="[`report-menu--${size}`]">
    <button
      type="button"
      class="report-menu__trigger"
      :disabled="loading"
      @click="toggle"
    >
      {{ buttonLabel }}
    </button>
    <div v-if="open" class="report-menu__panel">
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
    <p v-if="feedback" class="report-menu__feedback" :class="feedbackClass">
      {{ feedback.message }}
    </p>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from "vue";
import {
  REPORT_REASON_OPTIONS,
  type ReportReason,
} from "~/types/reports";

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
  }
);

const open = ref(false);
const loading = ref(false);
const feedback = ref<{ type: "success" | "error"; message: string } | null>(
  null
);

const options = computed(() => REPORT_REASON_OPTIONS);

const feedbackClass = computed(() =>
  feedback.value ? `report-menu__feedback--${feedback.value.type}` : null
);

function toggle() {
  open.value = !open.value;
  if (open.value) {
    feedback.value = null;
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
    open.value = false;
  } catch (error) {
    const message =
      error instanceof Error
        ? error.message
        : props.errorMessage ?? "";

    feedback.value = {
      type: "error",
      message:
        message.includes("Unauthenticated")
          ? "Log in om te rapporteren."
          : message || props.errorMessage,
    };
  } finally {
    loading.value = false;
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
  border: 1px solid #cbd5f5;
  background: #eef2ff;
  color: #3730a3;
  border-radius: 9999px;
  font-size: 0.85rem;
  font-weight: 600;
  padding: 0.4rem 0.9rem;
  cursor: pointer;
  transition: background-color 0.2s ease, color 0.2s ease, transform 0.2s ease;
}

.report-menu--sm .report-menu__trigger {
  font-size: 0.75rem;
  padding: 0.3rem 0.75rem;
}

.report-menu__trigger:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.report-menu__trigger:not(:disabled):hover {
  background: #dbeafe;
  color: #1d4ed8;
  transform: translateY(-1px);
}

.report-menu__panel {
  position: absolute;
  top: 100%;
  right: 0;
  margin-top: 0.5rem;
  width: min(320px, 90vw);
  border-radius: 0.75rem;
  background: #ffffff;
  box-shadow: 0 12px 32px rgba(15, 23, 42, 0.15);
  padding: 1rem;
  z-index: 10;
}

.report-menu__title {
  margin: 0 0 0.75rem;
  font-size: 0.9rem;
  font-weight: 600;
  color: #1f2937;
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
  border: 1px solid #e5e7eb;
  background: #f9fafb;
  border-radius: 0.5rem;
  padding: 0.5rem 0.75rem;
  text-align: left;
  font-size: 0.85rem;
  color: #1f2937;
  cursor: pointer;
  transition: background-color 0.2s ease, border-color 0.2s ease,
    color 0.2s ease;
}

.report-menu__option:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.report-menu__option:not(:disabled):hover {
  background: #e0f2fe;
  border-color: #38bdf8;
  color: #0c4a6e;
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
