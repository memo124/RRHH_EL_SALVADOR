<template>
  <div class="h-full relative">
    <div v-if="isLoading" class="app-loader-track">
      <div class="app-loader-bar"></div>
    </div>

    <AppToast />
    <AppDialog />
    <router-view></router-view>
  </div>
</template>

<script setup>
import { onMounted } from 'vue';
import { isLoading, registerApiToast } from './services/api';
import { loadUserTheme, getCurrentUserId } from './utils/theme';
import { useToast } from './composables/useToast';
import AppToast from './components/AppToast.vue';
import AppDialog from './components/AppDialog.vue';

const toast = useToast();

onMounted(() => {
  registerApiToast(toast);
  if (getCurrentUserId()) {
    loadUserTheme();
  }
});
</script>
