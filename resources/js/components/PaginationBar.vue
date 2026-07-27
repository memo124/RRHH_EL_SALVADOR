<template>
  <div
    v-if="total > 0"
    class="flex flex-wrap items-center justify-between gap-3 px-4 py-3 border-t border-slate-200 dark:border-slate-700 bg-slate-50/80 dark:bg-slate-800/80 text-xs text-slate-600 dark:text-slate-300"
  >
    <div class="flex items-center gap-2">
      <span>{{ from }}–{{ to }} de {{ total }}</span>
      <select
        v-if="showPerPage"
        :value="perPage"
        @change="$emit('update:perPage', Number($event.target.value))"
        class="px-2 py-1 border border-slate-300 dark:border-slate-600 rounded bg-white dark:bg-slate-700 text-slate-800 dark:text-slate-200"
      >
        <option v-for="n in perPageOptions" :key="n" :value="n">{{ n }} / pág.</option>
      </select>
    </div>
    <div class="flex items-center gap-1">
      <button
        type="button"
        :disabled="page <= 1 || loading"
        @click="$emit('update:page', page - 1)"
        class="px-2.5 py-1.5 rounded border border-slate-300 dark:border-slate-600 hover:bg-white dark:hover:bg-slate-700 disabled:opacity-40 font-semibold"
      >
        ‹
      </button>
      <span class="px-2 font-semibold">{{ page }} / {{ lastPage }}</span>
      <button
        type="button"
        :disabled="page >= lastPage || loading"
        @click="$emit('update:page', page + 1)"
        class="px-2.5 py-1.5 rounded border border-slate-300 dark:border-slate-600 hover:bg-white dark:hover:bg-slate-700 disabled:opacity-40 font-semibold"
      >
        ›
      </button>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  page: { type: Number, default: 1 },
  lastPage: { type: Number, default: 1 },
  perPage: { type: Number, default: 25 },
  total: { type: Number, default: 0 },
  loading: { type: Boolean, default: false },
  showPerPage: { type: Boolean, default: true },
  perPageOptions: { type: Array, default: () => [25, 50, 100] },
});

defineEmits(['update:page', 'update:perPage']);

const from = computed(() => (props.total === 0 ? 0 : (props.page - 1) * props.perPage + 1));
const to = computed(() => Math.min(props.page * props.perPage, props.total));
</script>
