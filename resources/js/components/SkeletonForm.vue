<template>
  <div class="animate-pulse space-y-6 w-full max-w-2xl bg-white dark:bg-slate-900 p-6 rounded-lg border border-slate-200 dark:border-slate-700">
    <div class="h-6 bg-slate-200 dark:bg-slate-700 rounded w-1/3 mb-4"></div>
    <div class="space-y-4">
      <div v-for="i in 3" :key="i" class="space-y-2">
        <div class="h-4 bg-slate-200 dark:bg-slate-700 rounded w-1/4"></div>
        <div class="h-10 bg-slate-200 dark:bg-slate-700 rounded w-full"></div>
      </div>
      <div class="flex justify-end space-x-3 pt-4">
        <div class="h-10 bg-slate-200 dark:bg-slate-700 rounded w-20"></div>
        <div class="h-10 bg-slate-200 dark:bg-slate-700 rounded w-28"></div>
      </div>
    </div>
  </div>
</template>
