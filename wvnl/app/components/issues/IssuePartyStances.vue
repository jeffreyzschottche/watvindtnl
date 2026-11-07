<script setup lang="ts">
import { computed } from "vue";
import type { IssuePartyStances } from "~/types/issues";

const props = defineProps<{ stances: IssuePartyStances }>();
const hasParties = computed(() =>
  Object.values(props.stances).some((parties) => parties.length > 0),
);
const groups = computed(() => [
  { key: "disagree", label: "Tegen", parties: props.stances.disagree },
  { key: "agree", label: "Voor", parties: props.stances.agree },
  ...(props.stances.neutral.length
    ? [{ key: "neutral", label: "Neutraal", parties: props.stances.neutral }]
    : []),
]);
</script>

<template>
  <section class="party-stances" aria-label="Standpunten van partijen">
    <header class="party-stances__header">
      <h3>Standpunten van partijen</h3>
      <p>Deze verdeling staat los van de stemmen van WDNL-deelnemers.</p>
    </header>
    <div v-if="hasParties" class="party-stances__groups">
      <section
        v-for="group in groups"
        :key="group.key"
        class="party-group"
        :class="`party-group--${group.key}`"
        :aria-label="`Partijen ${group.label.toLowerCase()}`"
      >
        <header class="party-group__header">
          <h4>
            <span class="party-group__dot" aria-hidden="true" />{{
              group.label
            }}
          </h4>
          <span>{{
            group.parties.length
              ? `${group.parties.length} ${group.parties.length === 1 ? "partij" : "partijen"}`
              : "Niet vermeld"
          }}</span>
        </header>
        <ul v-if="group.parties.length" class="party-group__list">
          <li
            v-for="party in group.parties"
            :key="party.id"
            class="party-chip"
            :title="party.name"
          >
            <span class="party-chip__name">{{ party.name }}</span>
          </li>
        </ul>
        <p v-else class="party-group__empty">Geen partijen vermeld.</p>
      </section>
    </div>
    <p v-else class="party-stances__empty">
      Bij deze stelling zijn geen partijstandpunten beschikbaar.
    </p>
  </section>
</template>

<style scoped>
.party-stances {
  margin-top: 1.5rem;
  padding-top: 1.5rem;
  border-top: 1px solid var(--wdnl-border, #dce3ee);
  color: #001f4d;
}
.party-stances__header {
  margin-bottom: 1rem;
}
.party-stances__header h3 {
  margin: 0 0 0.4rem;
  font-size: 1rem;
  font-weight: 750;
}
.party-stances__header p {
  margin: 0;
  color: #63738a;
  font-size: 0.76rem;
  line-height: 1.6;
}
.party-stances__groups {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 0.8rem;
  align-items: start;
}
.party-group {
  border: 1px solid #d8e4f5;
  border-radius: 12px;
  background: #f1f6ff;
  padding: 1rem;
  min-width: 0;
}
.party-group--disagree {
  background: #fff5f7;
  border-color: #f1dce2;
}
.party-group--neutral {
  grid-column: 1 / -1;
  background: #f7f8fa;
  border-color: #e1e6ed;
}
.party-group__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.6rem;
  margin-bottom: 0.85rem;
}
.party-group__header h4 {
  display: flex;
  align-items: center;
  gap: 0.45rem;
  color: #0057b8;
  font-size: 0.83rem;
  margin: 0;
  letter-spacing: 0;
  font-weight: 750;
}
.party-group--disagree h4 {
  color: #be0025;
}
.party-group--neutral h4 {
  color: #586980;
}
.party-group__dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: currentColor;
  flex-shrink: 0;
}
.party-group__header > span {
  color: #63738a;
  font-size: 0.65rem;
  white-space: nowrap;
}
.party-group__list {
  display: flex;
  flex-wrap: wrap;
  gap: 0.45rem;
  list-style: none;
  margin: 0;
  padding: 0;
}
.party-chip {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  min-width: 0;
  max-width: 100%;
  padding: 0.5rem 0.65rem;
  border: 1px solid #dfe5ee;
  border-radius: 8px;
  background: #fff;
  color: #001f4d;
}
.party-chip__name {
  font-size: 0.73rem;
  line-height: 1.3;
  font-weight: 650;
  overflow-wrap: anywhere;
}
.party-group__empty,
.party-stances__empty {
  font-size: 0.77rem;
  line-height: 1.6;
  color: #63738a;
  margin: 0;
}
.party-stances__empty {
  padding: 1rem;
  background: #f5f7fa;
  border: 1px solid #e2e7ef;
  border-radius: 10px;
}
@media (max-width: 520px) {
  .party-stances__groups {
    grid-template-columns: 1fr;
    gap: 0.65rem;
  }
  .party-group {
    padding: 0.85rem;
  }
  .party-group--neutral {
    grid-column: auto;
  }
  .party-group__header {
    margin-bottom: 0.7rem;
  }
}
</style>
