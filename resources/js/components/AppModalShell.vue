<template>
  <Teleport to="body">
    <div v-if="open" class="modal-root" role="dialog" aria-modal="true">
      <div class="modal-backdrop" aria-hidden="true" @click="onBackdropClick"></div>
      <div class="modal-scroll">
        <slot />
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { watch, onUnmounted } from 'vue';

const props = defineProps({
  open: { type: Boolean, default: false },
  closeOnBackdrop: { type: Boolean, default: true },
});

const emit = defineEmits(['close']);

let scrollLockCount = 0;

const lockScroll = () => {
  scrollLockCount += 1;
  if (scrollLockCount === 1) {
    document.body.classList.add('modal-open');
  }
};

const unlockScroll = () => {
  scrollLockCount = Math.max(0, scrollLockCount - 1);
  if (scrollLockCount === 0) {
    document.body.classList.remove('modal-open');
  }
};

watch(
  () => props.open,
  (isOpen) => {
    if (isOpen) lockScroll();
    else unlockScroll();
  },
  { immediate: true },
);

onUnmounted(() => {
  if (props.open) unlockScroll();
});

const onBackdropClick = () => {
  if (props.closeOnBackdrop) {
    emit('close');
  }
};
</script>
