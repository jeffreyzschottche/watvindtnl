<template>
  <div class="arguments">
    <section class="arguments__column">
      <h3 class="arguments__heading">Voor</h3>
      <ul v-if="groups.pro.length" class="arguments__list">
        <li v-for="argument in groups.pro" :key="argument.id" class="arguments__item">
          {{ argument.body }}
        </li>
      </ul>
      <p v-else class="arguments__empty">Nog geen argumenten voor.</p>
    </section>
    <section class="arguments__column">
      <h3 class="arguments__heading">Tegen</h3>
      <ul v-if="groups.con.length" class="arguments__list">
        <li v-for="argument in groups.con" :key="argument.id" class="arguments__item">
          {{ argument.body }}
        </li>
      </ul>
      <p v-else class="arguments__empty">Nog geen argumenten tegen.</p>
    </section>
  </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import type { IssueArgumentsGroup } from "~/types/issues";

const props = defineProps<{
  groups: IssueArgumentsGroup;
}>();

const groups = computed(() => props.groups);
</script>

<style scoped>
.arguments {
  display: grid;
  gap: 1.5rem;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
}

.arguments__heading {
  margin-bottom: 0.5rem;
  font-size: 1.1rem;
}

.arguments__list {
  display: grid;
  gap: 0.75rem;
  padding-left: 1rem;
  margin: 0;
  list-style: disc;
}

.arguments__item {
  line-height: 1.5;
}

.arguments__empty {
  margin: 0;
  font-style: italic;
  color: #6b7280;
}
</style>
