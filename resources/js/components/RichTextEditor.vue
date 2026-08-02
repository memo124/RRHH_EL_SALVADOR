<template>
  <div class="rich-text-editor rounded-lg border border-slate-300 dark:border-slate-600 overflow-hidden bg-white dark:bg-slate-700">
    <QuillEditor
      ref="editorRef"
      v-model:content="innerContent"
      content-type="html"
      theme="snow"
      :toolbar="toolbar"
      :placeholder="placeholder"
      @ready="onReady"
    />
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import { QuillEditor } from '@vueup/vue-quill';
import '@vueup/vue-quill/dist/vue-quill.snow.css';

const props = defineProps({
  modelValue: { type: String, default: '' },
  placeholder: { type: String, default: 'Escriba el contenido del contrato…' },
});

const emit = defineEmits(['update:modelValue']);

const editorRef = ref(null);
const quillInstance = ref(null);
const innerContent = ref(props.modelValue || '');

const toolbar = [
  [{ header: [1, 2, 3, false] }],
  ['bold', 'italic', 'underline'],
  [{ align: [] }],
  [{ list: 'ordered' }, { list: 'bullet' }],
  [{ indent: '-1' }, { indent: '+1' }],
  ['clean'],
];

watch(
  () => props.modelValue,
  (val) => {
    if (val !== innerContent.value) {
      innerContent.value = val || '';
    }
  },
);

watch(innerContent, (val) => {
  emit('update:modelValue', val === '<p><br></p>' ? '' : val);
});

const onReady = (quill) => {
  quillInstance.value = quill;
};

const insertText = (text) => {
  const quill = quillInstance.value ?? editorRef.value?.getQuill?.();
  if (!quill) {
    innerContent.value = (innerContent.value || '') + text;
    return;
  }
  const range = quill.getSelection(true);
  const index = range ? range.index : Math.max(0, quill.getLength() - 1);
  quill.insertText(index, text);
  quill.setSelection(index + text.length);
};

defineExpose({ insertText });
</script>

<style scoped>
.rich-text-editor :deep(.ql-toolbar) {
  border: none;
  border-bottom: 1px solid rgb(203 213 225);
  background: rgb(248 250 252);
}

.dark .rich-text-editor :deep(.ql-toolbar) {
  border-bottom-color: rgb(71 85 105);
  background: rgb(51 65 85);
}

.rich-text-editor :deep(.ql-container) {
  border: none;
  min-height: 200px;
  font-size: 14px;
}

.rich-text-editor :deep(.ql-editor) {
  min-height: 200px;
  color: inherit;
}

.dark .rich-text-editor :deep(.ql-stroke) {
  stroke: rgb(203 213 225);
}

.dark .rich-text-editor :deep(.ql-fill) {
  fill: rgb(203 213 225);
}

.dark .rich-text-editor :deep(.ql-picker-label) {
  color: rgb(203 213 225);
}
</style>
