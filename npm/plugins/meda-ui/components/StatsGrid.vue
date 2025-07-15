<template>
  <div class="stats-adaptive-grid">
    <slot />
  </div>
</template>

<script>
export default {
  name: 'StatsGrid'
}
</script>

<style scoped>
.stats-adaptive-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
  gap: 1rem;
  width: 100%;
}

/* En móvil, columna */
@media (max-width: 640px) {
  .stats-adaptive-grid {
    grid-template-columns: 1fr;
  }
}
</style> 