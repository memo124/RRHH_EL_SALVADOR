<template>
  <div class="fixed top-4 right-4 z-[100] flex flex-col gap-2 max-w-sm w-full pointer-events-none">
    <TransitionGroup name="toast">
      <div
        v-for="toast in toasts"
        :key="toast.id"
        class="pointer-events-auto rounded-xl border shadow-lg px-4 py-3 flex gap-3 items-start backdrop-blur-sm"
        :class="toastClass(toast.type)"
      >
        <span class="text-lg leading-none mt-0.5">{{ toastIcon(toast.type) }}</span>
        <div class="flex-1 min-w-0">
          <p class="font-semibold text-sm">{{ toast.title }}</p>
          <p v-if="toast.message" class="text-xs mt-0.5 opacity-90">{{ toast.message }}</p>
        </div>
        <button type="button" @click="remove(toast.id)" class="text-current opacity-60 hover:opacity-100 text-sm leading-none">✕</button>
      </div>
    </TransitionGroup>
  </div>
</template>

<script setup>
import { useToast } from '../composables/useToast';

const { toasts, remove } = useToast();

const toastClass = (type) => ({
  success: 'bg-emerald-50 border-emerald-200 text-emerald-900 dark:bg-emerald-950 dark:border-emerald-800 dark:text-emerald-100',
  error: 'bg-rose-50 border-rose-200 text-rose-900 dark:bg-rose-950 dark:border-rose-800 dark:text-rose-100',
  info: 'bg-blue-50 border-blue-200 text-blue-900 dark:bg-blue-950 dark:border-blue-800 dark:text-blue-100',
}[type] || 'bg-slate-50 border-slate-200 text-slate-900');

const toastIcon = (type) => ({ success: '✓', error: '!', info: 'ℹ' }[type] || '•');
</script>

<style scoped>
.toast-enter-active, .toast-leave-active { transition: all 0.25s ease; }
.toast-enter-from, .toast-leave-to { opacity: 0; transform: translateX(1rem); }
</style>
