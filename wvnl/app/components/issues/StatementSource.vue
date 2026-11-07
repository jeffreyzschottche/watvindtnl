<script setup lang="ts">
import type { IssueSource } from "~/types/issues";
const props = defineProps<{
  source?: IssueSource;
  fallbackUrl?: string | null;
}>();
const url = computed(() => props.source?.url || props.fallbackUrl || null);
const safeUrl = computed(() =>
  url.value && /^https?:\/\//i.test(url.value) ? url.value : null,
);
const labels = {
  editorial: "Eigen stelling van WDNL",
  news: "Naar aanleiding van nieuws",
  motion: "Gebaseerd op een motie",
  legacy: "Achtergrond bij deze stelling",
};
const date = computed(() => {
  const value = props.source?.published_at;
  if (!value || !/^\d{4}-\d{2}-\d{2}$/.test(value)) return null;
  const parsed = new Date(`${value}T12:00:00`);
  return Number.isNaN(parsed.getTime())
    ? null
    : new Intl.DateTimeFormat("nl-NL", {
        day: "numeric",
        month: "long",
        year: "numeric",
      }).format(parsed);
});
</script>
<template>
  <aside class="statement-source" aria-label="Herkomst van de stelling">
    <span class="statement-source__label">{{
      labels[source?.kind ?? "legacy"]
    }}</span>
    <p v-if="source?.title" class="statement-source__title">
      {{ source.title }}
    </p>
    <p v-if="source?.name || date" class="statement-source__meta">
      {{ [source?.name, date].filter(Boolean).join(" · ") }}
    </p>
    <a v-if="safeUrl" :href="safeUrl" target="_blank" rel="noopener noreferrer"
      >{{
        source?.name
          ? `Lees de bron bij ${source.name}`
          : "Bekijk de oorspronkelijke bron"
      }}
      ↗</a
    >
    <p v-else class="statement-source__meta">
      {{
        source?.kind === "editorial"
          ? "Een maatschappelijke vraag, geformuleerd door WDNL."
          : "Bij deze oudere stelling is nog geen directe bronlink vastgelegd."
      }}
    </p>
    <p v-if="source?.kind === 'news'" class="statement-source__note">
      De vraag en argumenten zijn van WDNL. De nieuwsbron onderschrijft deze
      niet automatisch.
    </p>
  </aside>
</template>
<style scoped>
.statement-source {
  padding: 1rem 1.1rem;
  margin-top: 1.3rem;
  border: 1px solid #dce5f0;
  border-radius: 12px;
  background: #f7f9fc;
}
.statement-source__label {
  color: #0057b8;
  font-size: 0.69rem;
  font-weight: 750;
}
.statement-source__title {
  color: #001f4d;
  font-size: 0.85rem;
  font-weight: 650;
  margin: 0.5rem 0;
  overflow-wrap: anywhere;
}
.statement-source__meta,
.statement-source__note {
  color: #63738a;
  font-size: 0.73rem;
  line-height: 1.65;
  margin: 0.35rem 0;
}
.statement-source a {
  display: inline-block;
  font-size: 0.78rem;
  margin-block: 0.4rem;
  overflow-wrap: anywhere;
}
</style>
