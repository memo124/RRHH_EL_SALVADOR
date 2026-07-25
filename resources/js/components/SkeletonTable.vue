<template>
  <div class="animate-pulse space-y-4 w-full">
    <div v-if="!noHeader" class="flex justify-between items-center mb-4">
      <div class="h-10 bg-slate-200 dark:bg-slate-700 rounded w-1/3"></div>
      <div class="h-10 bg-slate-200 dark:bg-slate-700 rounded w-1/4"></div>
    </div>

    <div class="border border-slate-200 dark:border-slate-700 rounded-lg overflow-hidden bg-white dark:bg-slate-800">
      <div class="bg-slate-100 dark:bg-slate-800 px-6 py-4 border-b border-slate-200 dark:border-slate-700">
        <div class="grid gap-4" :style="gridStyle">
          <div v-for="i in cols" :key="`h-${i}`" class="h-5 bg-slate-200 dark:bg-slate-700 rounded" :style="{ width: barWidth(i) }"></div>
        </div>
      </div>

      <div class="divide-y divide-slate-200 dark:divide-slate-700">
        <div v-for="i in rows" :key="`r-${i}`" class="px-6 py-4">
          <div class="grid gap-4" :style="gridStyle">
            <div v-for="j in cols" :key="`c-${i}-${j}`" class="h-4 bg-slate-200 dark:bg-slate-700 rounded" :style="{ width: barWidth(j + 1) }"></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  rows: { type: Number, default: 5 },
  cols: { type: Number, default: 4 },
  noHeader: { type: Boolean, default: false },
});

const widths = ['50%', '75%', '66%', '33%', '80%', '40%', '60%', '55%'];

const gridStyle = computed(() => ({
  gridTemplateColumns: `repeat(${props.cols}, minmax(0, 1fr))`,
}));

const barWidth = (index) => widths[(index - 1) % widths.length];
</script>
