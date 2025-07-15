<template>
  <tr v-for="row in rows" :key="'loading-row-' + row" class="animate-pulse">
    <td v-for="col in columns" :key="'loading-cell-' + row + '-' + col" class="px-6 py-4">
      <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded" :style="{ width: getRandomWidth() }"></div>
    </td>
    <td v-if="hasActions" class="px-6 py-4 text-right">
      <div class="flex justify-end">
        <div class="w-6 h-6 bg-gray-200 dark:bg-gray-700 rounded-full"></div>
      </div>
    </td>
  </tr>
</template>

<script>
export default {
  name: 'DataTableRowsSkeleton',
  props: {
    rows: {
      type: Number,
      default: 6
    },
    columns: {
      type: Number,
      default: 5
    },
    hasActions: {
      type: Boolean,
      default: true
    }
  },
  methods: {
    getRandomWidth() {
      const widths = ['100%', '75%', '90%', '85%', '65%', '95%', '70%', '80%']
      return widths[Math.floor(Math.random() * widths.length)]
    }
  }
}
</script> 