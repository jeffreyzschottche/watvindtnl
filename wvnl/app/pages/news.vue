<template>
  <div class="page page--news">
    <section class="page-hero page-hero--news">
      <div class="container narrow">
        <h1 class="page-hero__title">
          Moties &amp; updates
        </h1>
        <p class="page-hero__subtitle">
          Binnenkort vind je hier achtergrondartikelen en updates over actuele moties.
        </p>
      </div>
    </section>
    <section class="container narrow news-placeholder">
      <p>We werken aan deze pagina. Kom binnenkort terug voor meer informatie.</p>
    </section>
  </div>
</template>

<script setup lang="ts">
const title = "Moties | Wat Denkt Nederland";
const description =
  "Lees binnenkort meer over moties en nieuws vanuit Wat Denkt Nederland.";
useHead({
  title,
  meta: [
    {
      name: "description",
      content: description,
    },
  ],
});
</script>

<style scoped>
.page--news {
  min-height: 100vh;
}

.page-hero--news {
  background: linear-gradient(120deg, rgba(0, 51, 102, 0.9), rgba(255, 142, 0, 0.7));
  color: #fff;
  text-align: center;
  padding: clamp(3rem, 8vw, 6rem) 1.5rem;
}

.news-placeholder {
  padding: clamp(2rem, 6vw, 4rem) 1.5rem 4rem;
  text-align: center;
  color: #475569;
  font-size: 1.1rem;
}
</style>
