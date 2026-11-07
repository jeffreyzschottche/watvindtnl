<script setup lang="ts">
withDefaults(defineProps<{ name: string; size?: number }>(), { size: 22 });
const paths: Record<string, string> = {
  arrow: "M5 12h14m-6-6 6 6-6 6",
  play: "m9 5 10 7-10 7V5Z",
  grid: "M3 3h7v7H3zm11 0h7v7h-7zM3 14h7v7H3zm11 0h7v7h-7z",
  compass: "m16 8-3 5-5 3 3-5 5-3Z M22 12a10 10 0 1 1-20 0 10 10 0 0 1 20 0Z",
  user: "M20 21v-2a6 6 0 0 0-6-6h-4a6 6 0 0 0-6 6v2 M16 6a4 4 0 1 1-8 0 4 4 0 0 1 8 0Z",
  check: "m5 12 4 4L19 6",
  shield: "m12 3 8 3v6c0 5-8 9-8 9s-8-4-8-9V6l8-3Z m-4 9 3 3 5-5",
  chat: "M21 11a8 8 0 0 1-8 8H6l-4 3 1-6a8 8 0 0 1-1-5 9 9 0 0 1 19 0Z M7 10h10m-10 4h7",
  search: "m21 21-5-5 M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0Z",
  menu: "M4 6h16M4 12h16M4 18h16",
  close: "m6 6 12 12M6 18 18 6",
  lock: "M5 10h14v11H5z M8 10V6a4 4 0 1 1 8 0v4",
  mail: "M3 5h18v14H3z m0 1 9 7 9-7",
  chevron: "m9 5 7 7-7 7",
};
</script>
<template>
  <svg
    :width="size"
    :height="size"
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    stroke-width="1.7"
    stroke-linecap="round"
    stroke-linejoin="round"
    aria-hidden="true"
  >
    <path :d="paths[name] ?? paths.arrow" />
  </svg>
</template>
