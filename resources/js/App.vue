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
import { isLoading } from './services/api';
import { loadUserTheme, getCurrentUserId } from './utils/theme';
import AppToast from './components/AppToast.vue';
import AppDialog from './components/AppDialog.vue';

onMounted(() => {
  if (getCurrentUserId()) {
    loadUserTheme();
  }
});
</script>
