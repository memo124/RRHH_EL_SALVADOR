<template>
  <button
    :type="type"
    :disabled="disabled || loading"
    :aria-busy="loading"
    :class="[variantClass, { 'is-click-loading': loading }]"
    @click="onClick"
  >
    <span v-if="loading" class="btn-spinner" aria-hidden="true"></span>
    <span><slot /></span>
  </button>
</template>

<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
  action: { type: Function, default: null },
  type: { type: String, default: 'button' },
  variant: { type: String, default: 'primary' },
  disabled: { type: Boolean, default: false },
});

const loading = ref(false);

const variantClass = computed(() => {
  if (props.variant === 'secondary') return 'btn-secondary';
  if (props.variant === 'danger') return 'btn-secondary text-rose-600 dark:text-rose-400';
  return 'btn-primary';
});

const onClick = async (event) => {
  if (loading.value || props.disabled || !props.action) return;
  if (props.type === 'submit') return;

  loading.value = true;
  try {
    await props.action(event);
  } finally {
    loading.value = false;
  }
};

defineExpose({ loading });
</script>
