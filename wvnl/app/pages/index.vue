<script setup>
const { data, error, pending } = await useFetch(
  "http://localhost:8000/api/questions",
  {
    method: "GET",
  }
);
</script>

<template>
  <main class="p-6">
    <div v-if="pending">Laden…</div>
    <div v-else-if="error">
      Er ging iets mis: {{ error?.statusMessage || error }}
    </div>
    <ul v-else>
      <li v-for="q in data.data" :key="q.id" class="mb-2">
        <strong>{{ q.text }}</strong>
        <ul v-if="q.choices">
          <li v-for="c in q.choices" :key="c">- {{ c }}</li>
        </ul>
      </li>
    </ul>
  </main>
</template>

<script></script>
