<template>
  <div
    class="border-2 border-dashed rounded-lg p-6 text-center transition-colors"
    :class="dragOver
      ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20'
      : 'border-slate-300 dark:border-slate-600 hover:border-indigo-400'"
    @dragover.prevent="dragOver = true"
    @dragleave.prevent="dragOver = false"
    @drop.prevent="onDrop"
  >
    <input
      ref="inputRef"
      type="file"
      class="hidden"
      :accept="accept"
      :disabled="disabled || uploading"
      @change="onSelect"
    />
    <div v-if="uploading" class="text-sm text-slate-500">Subiendo archivo...</div>
    <div v-else-if="previewName" class="space-y-2">
      <p class="text-sm font-medium text-slate-700 dark:text-slate-200">{{ previewName }}</p>
      <button type="button" class="text-xs text-red-600 hover:underline" @click="clear">Quitar</button>
    </div>
    <div v-else class="space-y-2">
      <p class="text-sm text-slate-600 dark:text-slate-400">
        Arrastre un archivo aquí o
        <button type="button" class="text-indigo-600 hover:underline font-medium" @click="inputRef?.click()">
          seleccione
        </button>
      </p>
      <p class="text-xs text-slate-400">PDF, JPG, PNG o DOCX — máx. 10 MB</p>
    </div>
    <p v-if="error" class="mt-2 text-xs text-red-600">{{ error }}</p>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
  modelValue: { type: [Number, String, null], default: null },
  accept: { type: String, default: '.pdf,.jpg,.jpeg,.png,.docx' },
  disabled: { type: Boolean, default: false },
  uploading: { type: Boolean, default: false },
  fileName: { type: String, default: '' },
});

const emit = defineEmits(['update:modelValue', 'upload', 'clear']);

const inputRef = ref(null);
const dragOver = ref(false);
const previewName = ref(props.fileName || '');
const error = ref('');

watch(() => props.fileName, (v) => { previewName.value = v || ''; });

const MAX_BYTES = 10 * 1024 * 1024;
const ALLOWED = ['pdf', 'jpg', 'jpeg', 'png', 'docx'];

function validate(file) {
  error.value = '';
  const ext = file.name.split('.').pop()?.toLowerCase();
  if (!ext || !ALLOWED.includes(ext)) {
    error.value = 'Extensión no permitida.';
    return false;
  }
  if (file.size > MAX_BYTES) {
    error.value = 'El archivo excede 10 MB.';
    return false;
  }
  return true;
}

function handleFile(file) {
  if (!file || !validate(file)) return;
  previewName.value = file.name;
  emit('upload', file);
}

function onSelect(e) {
  handleFile(e.target.files?.[0]);
}

function onDrop(e) {
  dragOver.value = false;
  handleFile(e.dataTransfer.files?.[0]);
}

function clear() {
  previewName.value = '';
  error.value = '';
  if (inputRef.value) inputRef.value.value = '';
  emit('update:modelValue', null);
  emit('clear');
}
</script>
