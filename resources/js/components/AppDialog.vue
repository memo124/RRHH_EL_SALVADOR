<template>
  <Teleport to="body">
    <div v-if="state" class="dialog-root" role="alertdialog" :aria-labelledby="titleId" aria-modal="true">
      <div class="dialog-backdrop" @click="onBackdrop"></div>
      <div class="dialog-scroll">
        <div class="dialog-panel" :class="variantPanelClass">
          <div class="dialog-icon" :class="variantIconClass" aria-hidden="true">
            <AppIcon :name="variantIcon" size="lg" />
          </div>

          <h2 :id="titleId" class="dialog-title">{{ state.title || defaultTitle }}</h2>
          <p v-if="state.message" class="dialog-message">{{ state.message }}</p>

          <!-- Prompt: un solo campo -->
          <div v-if="state.template === 'prompt'" class="dialog-fields">
            <label class="label-base">{{ state.inputLabel || 'Valor' }}</label>
            <input
              v-if="state.inputType !== 'textarea'"
              v-model="state.fieldValues.input"
              :type="state.inputType || 'text'"
              class="input-base"
              :placeholder="state.inputPlaceholder"
              @keyup.enter="submit"
            />
            <textarea
              v-else
              v-model="state.fieldValues.input"
              class="input-base"
              rows="3"
              :placeholder="state.inputPlaceholder"
            />
            <p v-if="state.fieldErrors.input" class="dialog-field-error">{{ state.fieldErrors.input }}</p>
          </div>

          <!-- Form: campos dinámicos -->
          <div v-if="state.template === 'form'" class="dialog-fields space-y-3">
            <div v-for="field in state.fields" :key="field.name">
              <label v-if="field.type !== 'checkbox'" class="label-base">
                {{ field.label }}{{ field.required ? ' *' : '' }}
              </label>
              <input
                v-if="field.type === 'text' || field.type === 'number' || !field.type"
                v-model="state.fieldValues[field.name]"
                :type="field.type === 'number' ? 'number' : 'text'"
                class="input-base"
                :placeholder="field.placeholder"
              />
              <textarea
                v-else-if="field.type === 'textarea'"
                v-model="state.fieldValues[field.name]"
                class="input-base"
                :rows="field.rows || 3"
                :placeholder="field.placeholder"
              />
              <AsyncSelect
                v-else-if="field.type === 'select'"
                v-model="state.fieldValues[field.name]"
                :options="field.options || []"
                :searchable="false"
                :placeholder="field.placeholder || 'Seleccionar…'"
              />
              <label v-else-if="field.type === 'checkbox'" class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-200">
                <input v-model="state.fieldValues[field.name]" type="checkbox" class="rounded border-slate-300 dark:border-slate-600" />
                <span>{{ field.label }}</span>
              </label>
              <p v-if="state.fieldErrors[field.name]" class="dialog-field-error">{{ state.fieldErrors[field.name] }}</p>
            </div>
          </div>

          <div class="dialog-actions">
            <button
              v-if="showCancel"
              type="button"
              class="btn-secondary"
              data-no-lock
              @click="cancelDialog"
            >
              {{ state.cancelText }}
            </button>
            <button
              type="button"
              class="btn-primary"
              :class="confirmBtnClass"
              data-no-lock
              @click="submit"
            >
              {{ state.confirmText }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { computed, watch, onUnmounted } from 'vue';
import AsyncSelect from './AsyncSelect.vue';
import { useDialog } from '../composables/useDialog';

const { dialogState, resolve, cancel: cancelDialog } = useDialog();

const state = dialogState;
const titleId = `dialog-title-${Math.random().toString(36).slice(2, 9)}`;

let scrollLockCount = 0;

const lockScroll = () => {
  scrollLockCount += 1;
  if (scrollLockCount === 1) document.body.classList.add('modal-open');
};

const unlockScroll = () => {
  scrollLockCount = Math.max(0, scrollLockCount - 1);
  if (scrollLockCount === 0) document.body.classList.remove('modal-open');
};

watch(
  () => state.value,
  (val) => {
    if (val) lockScroll();
    else unlockScroll();
  },
);

onUnmounted(unlockScroll);

const showCancel = computed(() => state.value?.template !== 'alert');

const defaultTitle = computed(() => ({
  confirm: 'Confirmar acción',
  alert: 'Aviso',
  prompt: 'Ingresar datos',
  form: 'Completar información',
}[state.value?.template] || 'Confirmar'));

const variantIcon = computed(() => ({
  danger: 'alert-triangle',
  warning: 'circle-alert',
  info: 'info',
  success: 'check-circle',
}[state.value?.variant] || 'circle-alert'));

const variantPanelClass = computed(() => ({
  danger: 'dialog-panel-danger',
  warning: 'dialog-panel-warning',
  info: '',
  success: 'dialog-panel-success',
}[state.value?.variant] || ''));

const variantIconClass = computed(() => ({
  danger: 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300',
  warning: 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
  info: 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300',
  success: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
}[state.value?.variant] || 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300'));

const confirmBtnClass = computed(() => ({
  danger: '!bg-rose-600 hover:!bg-rose-700',
  warning: '!bg-amber-600 hover:!bg-amber-700',
  success: '!bg-emerald-600 hover:!bg-emerald-700',
}[state.value?.variant] || ''));

function validate() {
  if (!state.value) return true;
  const errors = {};
  let ok = true;

  if (state.value.template === 'prompt') {
    if (state.value.inputRequired && !String(state.value.fieldValues.input ?? '').trim()) {
      errors.input = 'Este campo es obligatorio.';
      ok = false;
    }
  }

  if (state.value.template === 'form') {
    for (const field of state.value.fields || []) {
      const val = state.value.fieldValues[field.name];
      if (field.required) {
        if (field.type === 'checkbox' && !val) {
          errors[field.name] = 'Debe marcar esta opción.';
          ok = false;
        } else if (field.type !== 'checkbox' && !String(val ?? '').trim()) {
          errors[field.name] = 'Campo obligatorio.';
          ok = false;
        }
      }
    }
  }

  state.value.fieldErrors = errors;
  return ok;
}

function submit() {
  if (!validate()) return;
  resolve(true, { ...state.value.fieldValues });
}

function onBackdrop() {
  if (state.value?.template === 'alert') {
    resolve(true, null);
    return;
  }
  cancelDialog();
}
</script>

<style scoped>
.dialog-root {
  @apply fixed inset-0 z-[10050];
}
.dialog-backdrop {
  @apply absolute inset-0 bg-slate-900/80 dark:bg-black/75 backdrop-blur-md backdrop-saturate-150;
}
.dialog-scroll {
  @apply absolute inset-0 flex items-center justify-center p-4 pointer-events-none;
}
.dialog-panel {
  @apply pointer-events-auto w-full max-w-md rounded-xl border border-slate-200 dark:border-slate-700
    bg-white dark:bg-slate-800 shadow-2xl p-6 space-y-4;
}
.dialog-icon {
  @apply w-11 h-11 rounded-full flex items-center justify-center text-lg font-bold mx-auto;
}
.dialog-title {
  @apply text-base font-bold text-slate-900 dark:text-white text-center;
}
.dialog-message {
  @apply text-sm text-slate-600 dark:text-slate-300 text-center whitespace-pre-line;
}
.dialog-fields {
  @apply text-left;
}
.dialog-field-error {
  @apply text-xs text-rose-600 dark:text-rose-400 mt-1;
}
.dialog-actions {
  @apply flex justify-end gap-3 pt-2;
}
.dialog-panel-danger {
  @apply border-rose-200 dark:border-rose-800;
}
.dialog-panel-warning {
  @apply border-amber-200 dark:border-amber-800;
}
.dialog-panel-success {
  @apply border-emerald-200 dark:border-emerald-800;
}
</style>
