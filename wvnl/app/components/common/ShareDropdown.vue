<template>
  <div
    ref="root"
    class="share-dropdown"
    :class="{ 'share-dropdown--open': isOpen }"
    @keydown.esc.prevent="close"
  >
    <button
      type="button"
      :class="triggerClass"
      :aria-expanded="isOpen"
      aria-haspopup="menu"
      @click.stop="toggle"
    >
      {{ triggerLabel }}
    </button>
    <transition name="share-dropdown__transition">
      <ul
        v-if="isOpen"
        class="share-dropdown__menu"
        role="menu"
      >
        <li v-for="option in computedOptions" :key="option.value" role="none">
          <button
            type="button"
            class="share-dropdown__option"
            role="menuitem"
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
    triggerClass: "issue-card__button issue-card__button--share",
  }
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

const computedOptions = computed(() => props.options ?? DEFAULT_OPTIONS);

function toggle() {
  isOpen.value = !isOpen.value;
}

function close() {
  isOpen.value = false;
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
  close();
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

.share-dropdown__menu {
  position: absolute;
  right: 0;
  top: calc(100% + 0.5rem);
  display: grid;
  gap: 0.35rem;
  padding: 0.65rem;
  border-radius: 0.9rem;
  background: linear-gradient(135deg, rgba(255, 255, 255, 0.98), rgba(238, 241, 246, 0.95));
  min-width: 12rem;
  box-shadow: 0 20px 40px rgba(0, 28, 70, 0.18);
  border: 1px solid rgba(0, 61, 165, 0.15);
  z-index: 100;
}

.share-dropdown__option {
  width: 100%;
  text-align: left;
  padding: 0.55rem 0.85rem;
  border: none;
  border-radius: 0.65rem;
  background: transparent;
  color: #101828;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.15s ease, color 0.15s ease, transform 0.15s ease;
}

.share-dropdown__option:hover,
.share-dropdown__option:focus {
  background: rgba(0, 61, 165, 0.12);
  color: var(--color-primary);
  transform: translateX(2px);
}

.share-dropdown__transition-enter-active,
.share-dropdown__transition-leave-active {
  transition: opacity 0.15s ease, transform 0.15s ease;
}

.share-dropdown__transition-enter-from,
.share-dropdown__transition-leave-to {
  opacity: 0;
  transform: translateY(-4px);
}
</style>
