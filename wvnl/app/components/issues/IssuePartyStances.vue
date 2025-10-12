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
        <ul v-if="stances[column.key].length" class="issue-party-stances__list">
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
              <span class="issue-party-stances__party-name">{{
                party.name
              }}</span>
            </div>
          </li>
        </ul>
        <p v-else class="issue-party-stances__empty">
          Nog geen voor/tegenstanders.
        </p>
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
    hint: "Deze partijen zijn neutraal.",
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
  padding: 1.5rem;
  border-radius: 1rem;
  background: linear-gradient(
    135deg,
    rgba(0, 61, 165, 0.05),
    rgba(255, 255, 255, 0.75)
  );
  border: 1px solid rgba(0, 61, 165, 0.12);
}

.issue-party-stances__title {
  margin: 0;
  font-size: 1.15rem;
  font-weight: 700;
  color: var(--color-primary);
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
  border-radius: 0.85rem;
  background: linear-gradient(
    135deg,
    #ffffff 0%,
    rgba(238, 241, 246, 0.85) 100%
  );
  box-shadow: 0 12px 24px rgba(0, 28, 70, 0.08);
  border: 1px solid rgba(0, 61, 165, 0.08);
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
  color: #101828;
  background: rgba(0, 61, 165, 0.08);
  border: 1px solid rgba(0, 61, 165, 0.15);
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
  color: #475467;
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
  background: rgba(255, 255, 255, 0.9);
  padding: 0.25rem;
  box-shadow: inset 0 0 0 1px rgba(0, 61, 165, 0.08);
}

.issue-party-stances__party-details {
  display: flex;
  flex-direction: column;
  gap: 0.1rem;
}

.issue-party-stances__party-abbr {
  font-weight: 700;
  color: var(--color-primary);
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
