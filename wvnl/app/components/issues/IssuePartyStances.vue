<template>
  <section class="issue-party-stances">
    <h3 class="issue-party-stances__title">Standpunten van partijen</h3>
    <div class="issue-party-stances__grid">
      <div
        v-for="column in columns"
        :key="column.key"
        class="issue-party-stances__column"
        :class="`issue-party-stances__column--${column.key}`"
      >
        <header class="issue-party-stances__column-header">
          <span class="issue-party-stances__badge">{{ column.label }}</span>
          <p class="issue-party-stances__hint">{{ column.hint }}</p>
        </header>
        <ul
          v-if="stances[column.key].length"
          class="issue-party-stances__list"
        >
          <li
            v-for="party in stances[column.key]"
            :key="party.id"
            class="issue-party-stances__party"
          >
            <img
              v-if="party.logo_url"
              :src="party.logo_url"
              :alt="party.abbreviation || party.name"
              class="issue-party-stances__logo"
              loading="lazy"
            />
            <div class="issue-party-stances__party-details">
              <span class="issue-party-stances__party-abbr">
                {{ party.abbreviation || party.name }}
              </span>
              <span class="issue-party-stances__party-name">{{ party.name }}</span>
            </div>
          </li>
        </ul>
        <p v-else class="issue-party-stances__empty">Nog geen standpunten.</p>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { computed } from "vue";
import type { IssuePartyStances } from "~/types/issues";

type ColumnKey = keyof IssuePartyStances;

const props = defineProps<{ stances: IssuePartyStances }>();

const columns = computed(() => [
  {
    key: "agree" as ColumnKey,
    label: "Voor",
    hint: "Deze partijen zijn voor deze kwestie.",
  },
  {
    key: "neutral" as ColumnKey,
    label: "Neutraal",
    hint: "Deze partijen houden zich (nog) op de vlakte.",
  },
  {
    key: "disagree" as ColumnKey,
    label: "Tegen",
    hint: "Deze partijen zijn tegen deze kwestie.",
  },
]);

const stances = computed(() => props.stances);
</script>

<style scoped>
.issue-party-stances {
  display: grid;
  gap: 1rem;
  padding: 1.25rem;
  border-radius: 1rem;
  background: #f1f5f9;
}

.issue-party-stances__title {
  margin: 0;
  font-size: 1.1rem;
  font-weight: 700;
  color: #0f172a;
}

.issue-party-stances__grid {
  display: grid;
  gap: 1rem;
}

@media (min-width: 768px) {
  .issue-party-stances__grid {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }
}

.issue-party-stances__column {
  display: grid;
  gap: 0.75rem;
  padding: 1rem;
  border-radius: 0.75rem;
  background: #ffffff;
  box-shadow: 0 6px 16px rgba(15, 23, 42, 0.05);
}

.issue-party-stances__column-header {
  display: grid;
  gap: 0.25rem;
}

.issue-party-stances__badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: fit-content;
  padding: 0.2rem 0.75rem;
  border-radius: 999px;
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: #0f172a;
}

.issue-party-stances__column--agree .issue-party-stances__badge {
  background: rgba(34, 197, 94, 0.15);
  color: #15803d;
}

.issue-party-stances__column--neutral .issue-party-stances__badge {
  background: rgba(107, 114, 128, 0.15);
  color: #374151;
}

.issue-party-stances__column--disagree .issue-party-stances__badge {
  background: rgba(239, 68, 68, 0.15);
  color: #b91c1c;
}

.issue-party-stances__hint {
  margin: 0;
  font-size: 0.8rem;
  color: #64748b;
}

.issue-party-stances__list {
  margin: 0;
  padding: 0;
  list-style: none;
  display: grid;
  gap: 0.75rem;
}

.issue-party-stances__party {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.issue-party-stances__logo {
  width: 36px;
  height: 36px;
  object-fit: contain;
  border-radius: 50%;
  background: #e2e8f0;
  padding: 0.25rem;
}

.issue-party-stances__party-details {
  display: flex;
  flex-direction: column;
  gap: 0.1rem;
}

.issue-party-stances__party-abbr {
  font-weight: 700;
  color: #0f172a;
}

.issue-party-stances__party-name {
  font-size: 0.85rem;
  color: #475569;
}

.issue-party-stances__empty {
  margin: 0;
  padding: 0.75rem;
  border-radius: 0.75rem;
  background: #f8fafc;
  color: #64748b;
  text-align: center;
  font-size: 0.85rem;
}
</style>
