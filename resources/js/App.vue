<template>
  <div class="h-full relative">
    <div
      v-if="isLoading"
      class="fixed top-0 left-0 right-0 h-1.5 bg-indigo-600 dark:bg-indigo-400 z-50 animate-pulse"
    ></div>

    <router-view></router-view>
  </div>
</template>

<script setup>
import { onMounted } from 'vue';
import { isLoading } from './services/api';
import { loadUserTheme, getCurrentUserId } from './utils/theme';

onMounted(() => {
  if (getCurrentUserId()) {
    loadUserTheme();
  }
});
</script>
