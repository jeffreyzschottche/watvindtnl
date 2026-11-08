<template>
  <div
    ref="root"
    class="share-dropdown"
    :class="{ 'share-dropdown--open': isOpen }"
    @keydown.esc.prevent="close"
  >
    <button
      ref="trigger"
      class="issue-card__button--share"
      type="button"
      :class="triggerClass"
      :aria-expanded="isOpen"
      :aria-controls="panelId"
      @click.stop="toggle"
    >
      {{ triggerLabel }}
    </button>
    <transition name="share-dropdown__transition">
      <ul v-if="isOpen" :id="panelId" class="share-dropdown__menu">
        <li v-for="option in computedOptions" :key="option.value">
          <button
            type="button"
            class="share-dropdown__option"
            @click="select(option.value)"
          >
            {{ option.label }}
          </button>
        </li>
      </ul>
    </transition>
  </div>
</template>

<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from "vue";
import type { SharePlatform } from "~/composables/useIssueSharing";

type ShareOption = { value: SharePlatform; label: string };

const props = withDefaults(
  defineProps<{
    triggerLabel?: string;
    triggerClass?: string;
    options?: ShareOption[];
  }>(),
  {
    triggerLabel: "Deel",
    triggerClass:
      "issue-card__button issue-card__button--share issues-page__retry",
  },
);

const emit = defineEmits<{
  (e: "select", platform: SharePlatform): void;
}>();

const DEFAULT_OPTIONS: ShareOption[] = [
  { value: "instagram", label: "Instagram" },
  { value: "facebook", label: "Facebook" },
  { value: "twitter", label: "Twitter" },
  { value: "whatsapp", label: "WhatsApp" },
  { value: "clipboard", label: "Klembord" },
];

const isOpen = ref(false);
const root = ref<HTMLElement | null>(null);
const trigger = ref<HTMLButtonElement | null>(null);
const panelId = useId();

const computedOptions = computed(() => props.options ?? DEFAULT_OPTIONS);

async function toggle() {
  isOpen.value = !isOpen.value;
  if (isOpen.value) {
    await nextTick();
    root.value
      ?.querySelector<HTMLButtonElement>(".share-dropdown__option")
      ?.focus();
  }
}

function close() {
  isOpen.value = false;
  trigger.value?.focus();
}

function select(platform: SharePlatform) {
  emit("select", platform);
  close();
}

function handleDocumentClick(event: MouseEvent) {
  if (!isOpen.value) return;
  const target = event.target as Node | null;
  if (target && root.value?.contains(target)) {
    return;
  }
  isOpen.value = false;
}

onMounted(() => {
  document.addEventListener("click", handleDocumentClick);
});

onBeforeUnmount(() => {
  document.removeEventListener("click", handleDocumentClick);
});
</script>

<style scoped>
.share-dropdown {
  position: relative;
  display: inline-flex;
}

.share-dropdown__menu li {
  list-style-type: none;
}
.share-dropdown__menu {
  position: absolute;
  right: 0;
  top: calc(100% + 0.6rem);
  display: grid;
  gap: 0.4rem;
  padding: 0.75rem;
  border-radius: 1rem;
  background: #fff;
  min-width: 12rem;
  box-shadow: 0 12px 35px #001f4d1a;
  border: 1px solid #dce3ee;
  backdrop-filter: blur(6px);
  z-index: 100;
  color: #001f4d;
}

.share-dropdown__option {
  width: 100%;
  text-align: left;
  padding: 0.6rem 0.9rem;
  border: none;
  border-radius: 0.7rem;
  background: #f5f7fa;
  color: #001f4d;
  min-height: 44px;
  font-weight: 600;
  cursor: pointer;
  transition:
    background 0.18s ease,
    color 0.18s ease,
    transform 0.18s ease,
    box-shadow 0.18s ease;
  display: flex;
  align-items: center;
  gap: 0.45rem;
}

.share-dropdown__option:hover,
.share-dropdown__option:focus {
  background: #edf3ff;
  color: var(--color-orange);
  box-shadow: inset 0 0 0 1px #a8c2e4;
}

.share-dropdown__transition-enter-active,
.share-dropdown__transition-leave-active {
  transition:
    opacity 0.15s ease,
    transform 0.15s ease;
}

.share-dropdown__transition-enter-from,
.share-dropdown__transition-leave-to {
  opacity: 0;
  transform: translateY(-4px);
}
.triggerClass {
  padding: 0.75rem 1.5rem;
  border-radius: 999px;
  border: none;
  background: linear-gradient(
    130deg,
    var(--color-accent),
    var(--color-primary)
  );
  color: white;
  font-weight: 700;
  cursor: pointer;
  transition:
    transform 0.15s ease,
    box-shadow 0.15s ease;
}

.triggerClass:hover,
.triggerClass:focus-visible {
  transform: translateY(-2px);
  box-shadow: 0 18px 32px rgba(0, 0, 0, 0.35);
}

.issue-card__button--share {
  background: #f5f7fa;
  color: #526277;
  border-radius: 12px;
  border: 1px solid #dce3ee;
  min-height: 44px;
  padding: 0.55rem 1rem;
  cursor: pointer;
  font-weight: 700;
}
</style>
