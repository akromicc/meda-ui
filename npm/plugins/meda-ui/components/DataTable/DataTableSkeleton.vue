<template>
  <div class="bg-white dark:bg-gray-800 shadow-lg rounded-lg border border-gray-200 dark:border-gray-700 animate-pulse" style="overflow: visible;">
    <!-- Toolbar Skeleton -->
    <div class="p-4 border-b border-gray-200 dark:border-gray-700">
      <div class="flex flex-col sm:flex-row justify-between gap-4">
        <!-- Search Skeleton -->
        <div class="flex-1 max-w-md">
          <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <Skeleton variant="circle" width="20px" height="20px" />
            </div>
            <Skeleton variant="rect" height="40px" />
          </div>
        </div>
        <!-- Actions Skeleton -->
        <div class="flex items-center gap-2">
          <Skeleton variant="rect" width="128px" height="40px" />
          <Skeleton variant="rect" width="112px" height="40px" />
          <Skeleton variant="rect" width="40px" height="40px" />
        </div>
      </div>
    </div>

    <!-- Table Skeleton -->
    <div class="overflow-x-auto" style="overflow-y: visible;">
      <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
        <thead class="bg-gray-50 dark:bg-gray-700">
          <tr>
            <th v-for="col in 5" :key="'header-' + col" class="px-6 py-3">
              <div class="flex items-center gap-2">
                <Skeleton variant="text" width="80px" height="16px" />
                <Skeleton variant="circle" width="20px" height="20px" />
                <Skeleton variant="text" width="12px" height="12px" />
              </div>
            </th>
            <th class="px-6 py-3">
              <Skeleton variant="text" width="64px" height="16px" />
            </th>
          </tr>
        </thead>
        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
          <DataTableRowsSkeleton :rows="6" :columns="5" :has-actions="true" />
        </tbody>
      </table>
    </div>

    <!-- Pagination Skeleton -->
    <div class="bg-white dark:bg-gray-800 px-4 py-3 border-t border-gray-200 dark:border-gray-700 sm:px-6">
      <div class="flex items-center justify-between">
        <Skeleton variant="text" width="160px" height="16px" />
        <div class="flex gap-2">
          <Skeleton variant="circle" width="32px" height="32px" />
          <Skeleton variant="rect" width="32px" height="32px" />
          <Skeleton variant="rect" width="32px" height="32px" />
          <Skeleton variant="rect" width="32px" height="32px" />
          <Skeleton variant="circle" width="32px" height="32px" />
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import DataTableRowsSkeleton from './DataTableRowsSkeleton.vue'

export default {
  name: 'DataTableSkeleton',
  components: {
    DataTableRowsSkeleton
  }
}
</script> 