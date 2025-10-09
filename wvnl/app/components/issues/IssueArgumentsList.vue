<template>
  <div class="arguments">
    <section class="arguments__column">
      <h3 class="arguments__heading">Voor</h3>
      <ul v-if="groups.pro.length" class="arguments__list">
        <li
          v-for="argument in groups.pro"
          :key="argument.id"
          class="arguments__item"
        >
          <div class="arguments__item-body">
            <p class="arguments__text">{{ argument.body }}</p>
            <div v-if="argument.sources.length" class="arguments__sources">
              <span class="arguments__sources-label">Bronnen:</span>
              <ul class="arguments__sources-list">
                <li
                  v-for="(source, index) in argument.sources"
                  :key="`${argument.id}-source-${index}`"
                >
                  <a
                    :href="toAbsoluteUrl(source)"
                    class="arguments__source-link"
                    rel="noopener noreferrer"
                  >
                    {{ source }}
                  </a>
                </li>
              </ul>
            </div>
            <ReportMenu
              class="arguments__report"
              size="sm"
              :submit="(reason) => handleArgumentReport(argument.id, reason)"
              success-message="Bedankt voor je melding over dit argument."
            />
          </div>
        </li>
      </ul>
      <p v-else class="arguments__empty">Nog geen argumenten voor.</p>
    </section>
    <section class="arguments__column">
      <h3 class="arguments__heading">Tegen</h3>
      <ul v-if="groups.con.length" class="arguments__list">
        <li
          v-for="argument in groups.con"
          :key="argument.id"
          class="arguments__item"
        >
          <div class="arguments__item-body">
            <p class="arguments__text">{{ argument.body }}</p>
            <div v-if="argument.sources.length" class="arguments__sources">
              <span class="arguments__sources-label">Bronnen:</span>
              <ul class="arguments__sources-list">
                <li
                  v-for="(source, index) in argument.sources"
                  :key="`${argument.id}-source-${index}`"
                >
                  <a
                    :href="toAbsoluteUrl(source)"
                    class="arguments__source-link"
                    target="_blank"
                    rel="noopener noreferrer"
                  >
                    {{ source }}
                  </a>
                </li>
              </ul>
            </div>
            <ReportMenu
              class="arguments__report"
              size="sm"
              :submit="(reason) => handleArgumentReport(argument.id, reason)"
              success-message="Bedankt voor je melding over dit argument."
            />
          </div>
        </li>
      </ul>
      <p v-else class="arguments__empty">Nog geen argumenten tegen.</p>
    </section>
  </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { storeToRefs } from "pinia";
import ReportMenu from "~/components/common/ReportMenu.vue";
import type { IssueArgumentsGroup } from "~/types/issues";
import type { ReportReason } from "~/types/reports";
import { reportArgument } from "~/services/issues";
import { useAuthStore } from "~/stores/auth";

const toAbsoluteUrl = (url?: string) => {
  if (!url) return "#";
  const clean = url.trim();
  if (/^https?:\/\//i.test(clean)) return clean; // al goed
  if (/^\/\//.test(clean)) return "https:" + clean; // protocol-relative
  return `https://${clean.replace(/^\/+/, "")}`; // voeg https toe
};

const props = defineProps<{
  groups: IssueArgumentsGroup;
}>();

const groups = computed(() => props.groups);

const auth = useAuthStore();
const { token } = storeToRefs(auth);

function handleArgumentReport(argumentId: number, reason: ReportReason) {
  return reportArgument(argumentId, reason, token.value ?? undefined);
}
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

.arguments__item-body {
  display: grid;
  gap: 0.5rem;
}

.arguments__text {
  margin: 0;
}

.arguments__sources {
  display: grid;
  gap: 0.25rem;
  font-size: 0.85rem;
}

.arguments__sources-label {
  font-weight: 600;
}

.arguments__sources-list {
  display: grid;
  gap: 0.25rem;
  margin: 0;
  padding-left: 1rem;
}

.arguments__source-link {
  color: #1d4ed8;
  text-decoration: underline;
  word-break: break-all;
}

.arguments__empty {
  margin: 0;
  font-style: italic;
  color: #6b7280;
}

.arguments__report {
  align-self: flex-start;
}
</style>
