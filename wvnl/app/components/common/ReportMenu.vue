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
  border: none;
  background: linear-gradient(135deg, #ff8e00, #fd7702);
  color: var(--color-midnight);
  border-radius: 9999px;
  font-size: 0.85rem;
  font-weight: 600;
  padding: 0.45rem 1rem;
  cursor: pointer;
  transition: background-color 0.2s ease, color 0.2s ease, transform 0.2s ease,
    box-shadow 0.2s ease;
  box-shadow: 0 8px 20px rgba(0, 51, 102, 0.18);
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
  color: var(--color-midnight);
  transform: translateY(-1px);
  box-shadow: 0 10px 22px rgba(253, 119, 2, 0.35);
}

.report-menu__panel {
  position: absolute;
  top: 100%;
  right: 0;
  margin-top: 0.5rem;
  width: min(320px, 90vw);
  border-radius: 1rem;
  background: linear-gradient(
    145deg,
    rgba(0, 35, 71, 0.95),
    rgba(0, 51, 102, 0.85)
  );
  box-shadow: 0 18px 40px rgba(0, 0, 0, 0.35);
  padding: 1.1rem;
  border: 1px solid rgba(255, 255, 255, 0.08);
  z-index: 10;
}

.report-menu__title {
  margin: 0 0 0.75rem;
  font-size: 0.95rem;
  font-weight: 700;
  color: var(--color-orange);
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
  border: 1px solid rgba(255, 255, 255, 0.2);
  background: rgba(255, 255, 255, 0.12);
  border-radius: 0.65rem;
  padding: 0.6rem 0.85rem;
  text-align: left;
  font-size: 0.9rem;
  color: var(--color-text-light);
  cursor: pointer;
  transition: background-color 0.2s ease, border-color 0.2s ease,
    color 0.2s ease, transform 0.2s ease;
}

.report-menu__option:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.report-menu__option:not(:disabled):hover,
.report-menu__option:not(:disabled):focus-visible {
  background: rgba(255, 255, 255, 0.25);
  border-color: rgba(255, 255, 255, 0.5);
  color: var(--color-orange);
  transform: translateY(-1px);
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
