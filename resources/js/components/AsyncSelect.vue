<template>
  <div ref="rootRef" class="relative" :class="wrapperClass">
    <button
      ref="triggerRef"
      type="button"
      :disabled="disabled"
      @click="toggleOpen"
      :class="[
        'w-full flex items-center justify-between gap-2 border border-slate-300 dark:border-slate-600 rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-left disabled:opacity-50 focus:outline-none focus:ring-2 focus:ring-indigo-500',
        compact ? 'px-2 py-2 sm:px-3' : 'px-3 py-2',
        inputClass,
      ]"
      :title="compact ? (displayLabel || placeholder) : undefined"
      :aria-label="compact ? (displayLabel || placeholder) : undefined"
    >
      <span class="flex items-center gap-2 min-w-0 flex-1 justify-center sm:justify-start">
        <AppIcon v-if="displayIcon" :name="displayIcon" size="sm" class="shrink-0 opacity-80" />
        <span
          class="truncate"
          :class="[
            displayLabel ? '' : 'text-slate-400',
            compact ? 'hidden sm:inline' : '',
          ]"
        >{{ displayLabel || placeholder }}</span>
      </span>
      <AppIcon
        :name="open ? 'chevron-up' : 'chevron-down'"
        size="xs"
        class="text-slate-400 shrink-0"
        :class="compact ? 'hidden sm:block' : ''"
      />
    </button>

    <Teleport to="body">
      <div
        v-if="open"
        ref="panelRef"
        class="fixed z-[100] rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-800 shadow-lg overflow-hidden"
        :style="panelStyle"
      >
        <div v-if="searchable" class="p-2 border-b border-slate-200 dark:border-slate-700 flex gap-2">
          <input
            ref="searchRef"
            v-model="searchText"
            type="text"
            :placeholder="searchPlaceholder"
            class="flex-1 px-2.5 py-1.5 text-sm border border-slate-300 dark:border-slate-600 rounded bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-indigo-500"
            @keydown.escape.prevent="close"
          />
          <button
            v-if="nullable && modelValue != null && modelValue !== ''"
            type="button"
            class="px-2 py-1 text-xs font-semibold text-slate-500 hover:text-rose-600 shrink-0"
            @click="clearSelection"
          >
            Limpiar
          </button>
        </div>

        <div ref="listRef" class="overflow-y-auto" :style="{ maxHeight: listMaxHeight }" @scroll="onScroll">
          <div v-if="loading && !visibleOptions.length" class="px-3 py-4 text-center text-xs text-slate-400">
            Cargando…
          </div>
          <div v-else-if="!visibleOptions.length" class="px-3 py-4 text-center text-xs text-slate-400">
            Sin resultados
          </div>
          <div v-else :style="{ height: `${totalSize}px`, position: 'relative' }">
            <button
              v-for="virtualRow in virtualRows"
              :key="String(visibleOptions[virtualRow.index].value)"
              type="button"
              :style="{
                position: 'absolute',
                top: 0,
                left: 0,
                width: '100%',
                height: `${virtualRow.size}px`,
                transform: `translateY(${virtualRow.start}px)`,
              }"
              :class="[
                'px-3 py-2 text-left text-sm hover:bg-indigo-50 dark:hover:bg-indigo-900/30 flex items-center gap-2 min-w-0',
                modelValue == visibleOptions[virtualRow.index].value
                  ? 'bg-indigo-100 dark:bg-indigo-900/40 text-indigo-800 dark:text-indigo-200 font-semibold'
                  : 'text-slate-800 dark:text-slate-200',
              ]"
              @click="selectOption(visibleOptions[virtualRow.index])"
            >
              <AppIcon
                v-if="visibleOptions[virtualRow.index].icon"
                :name="visibleOptions[virtualRow.index].icon"
                size="sm"
                class="shrink-0 opacity-80"
              />
              <span class="min-w-0 leading-snug">{{ visibleOptions[virtualRow.index].label }}</span>
            </button>
          </div>
          <div v-if="loading && visibleOptions.length" class="px-3 py-2 text-center text-[10px] text-slate-400">
            Cargando más…
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted, nextTick, toRef } from 'vue';
import { useVirtualizer } from '@tanstack/vue-virtual';
import { useAsyncSelect } from '../composables/useAsyncSelect';

const props = defineProps({
  modelValue: { type: [Number, String, Boolean, null], default: null },
  endpoint: { type: String, default: null },
  catalog: { type: String, default: null },
  options: { type: Array, default: null },
  params: { type: Object, default: () => ({}) },
  placeholder: { type: String, default: 'Seleccionar…' },
  searchPlaceholder: { type: String, default: 'Buscar…' },
  nullable: { type: Boolean, default: false },
  disabled: { type: Boolean, default: false },
  searchable: { type: Boolean, default: true },
  perPage: { type: Number, default: 30 },
  inputClass: { type: String, default: '' },
  wrapperClass: { type: String, default: '' },
  /** Alinea el panel al borde derecho del botón (útil en header móvil). */
  dropdownAlign: { type: String, default: 'start' },
  /** En móvil muestra solo el icono en el botón disparador. */
  compact: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue', 'change']);

const open = ref(false);
const searchText = ref('');
const rootRef = ref(null);
const triggerRef = ref(null);
const panelRef = ref(null);
const listRef = ref(null);
const searchRef = ref(null);
const selectedOption = ref(null);
const panelStyle = ref({});
const listMaxHeight = ref('14rem');

const resolvedEndpoint = computed(() => {
  if (props.catalog) return `/catalogs/${props.catalog}/select`;
  return props.endpoint;
});

const isStatic = computed(() => Array.isArray(props.options));

const asyncSelect = useAsyncSelect(resolvedEndpoint, {
  params: toRef(props, 'params'),
  perPage: props.perPage,
  enabled: computed(() => !isStatic.value && !!resolvedEndpoint.value),
});

const staticFiltered = computed(() => {
  if (!isStatic.value) return [];
  const q = searchText.value.toLowerCase().trim();
  const list = props.options || [];
  if (!q || !props.searchable) return list;
  return list.filter((o) => String(o.label ?? '').toLowerCase().includes(q));
});

const visibleOptions = computed(() => (isStatic.value ? staticFiltered.value : asyncSelect.optionsList.value));
const loading = computed(() => (isStatic.value ? false : asyncSelect.loading.value));
const hasMore = computed(() => (isStatic.value ? false : asyncSelect.hasMore.value));

const virtualizerOptions = computed(() => ({
  count: visibleOptions.value.length,
  getScrollElement: () => listRef.value,
  estimateSize: () => 40,
  overscan: 8,
}));

const virtualizer = useVirtualizer(virtualizerOptions);
const virtualRows = computed(() => virtualizer.value.getVirtualItems());
const totalSize = computed(() => virtualizer.value.getTotalSize());

const displayLabel = computed(() => {
  if (selectedOption.value) return selectedOption.value.label;
  const found = visibleOptions.value.find((o) => o.value == props.modelValue);
  if (found) return found.label;
  if (isStatic.value && props.options) {
    const staticFound = props.options.find((o) => o.value == props.modelValue);
    return staticFound?.label ?? '';
  }
  return '';
});

const displayIcon = computed(() => {
  if (selectedOption.value?.icon) return selectedOption.value.icon;
  const found = visibleOptions.value.find((o) => o.value == props.modelValue);
  if (found?.icon) return found.icon;
  if (isStatic.value && props.options) {
    const staticFound = props.options.find((o) => o.value == props.modelValue);
    return staticFound?.icon ?? null;
  }
  return null;
});

const updatePanelPosition = () => {
  if (!open.value || !triggerRef.value) return;

  const rect = triggerRef.value.getBoundingClientRect();
  const viewportPadding = 8;
  const panelWidth = Math.min(280, window.innerWidth - viewportPadding * 2);

  let left = props.dropdownAlign === 'end' ? rect.right - panelWidth : rect.left;
  left = Math.max(viewportPadding, Math.min(left, window.innerWidth - panelWidth - viewportPadding));

  const top = rect.bottom + 6;
  const searchHeight = props.searchable ? 52 : 0;
  const available = window.innerHeight - top - viewportPadding - searchHeight;
  listMaxHeight.value = `${Math.max(120, Math.min(224, available))}px`;

  panelStyle.value = {
    top: `${top}px`,
    left: `${left}px`,
    width: `${panelWidth}px`,
  };
};

const resolveSelected = async (value) => {
  if (value == null || value === '') {
    selectedOption.value = null;
    return;
  }
  if (isStatic.value) {
    selectedOption.value = (props.options || []).find((o) => o.value == value) || null;
    return;
  }
  await asyncSelect.resolveSelected(value);
  selectedOption.value = asyncSelect.selectedOption.value;
};

const close = () => {
  open.value = false;
};

const toggleOpen = async () => {
  if (props.disabled) return;
  open.value = !open.value;
  if (open.value) {
    searchText.value = '';
    if (!isStatic.value && resolvedEndpoint.value) {
      await asyncSelect.fetchOptions({ append: false, q: '' });
    }
    await nextTick();
    updatePanelPosition();
    if (props.searchable) searchRef.value?.focus();
  }
};

const selectOption = (opt) => {
  selectedOption.value = opt;
  emit('update:modelValue', opt.value);
  emit('change', opt.value);
  close();
};

const clearSelection = () => {
  selectedOption.value = null;
  emit('update:modelValue', null);
  emit('change', null);
  close();
};

const onScroll = () => {
  if (isStatic.value) return;
  const el = listRef.value;
  if (!el || loading.value || !hasMore.value) return;
  if (el.scrollTop + el.clientHeight >= el.scrollHeight - 48) {
    asyncSelect.loadMore();
  }
};

const onClickOutside = (e) => {
  const inRoot = rootRef.value?.contains(e.target);
  const inPanel = panelRef.value?.contains(e.target);
  if (!inRoot && !inPanel) close();
};

const onViewportChange = () => {
  if (open.value) updatePanelPosition();
};

watch(searchText, (q) => {
  if (!isStatic.value && props.searchable) asyncSelect.search(q);
});

watch(
  () => props.modelValue,
  (val) => resolveSelected(val),
  { immediate: true },
);

watch(
  () => props.options,
  () => resolveSelected(props.modelValue),
  { deep: true },
);

watch(
  () => props.params,
  () => {
    if (open.value && !isStatic.value) asyncSelect.fetchOptions({ append: false, q: searchText.value });
  },
  { deep: true },
);

watch(open, (isOpen) => {
  if (isOpen) {
    nextTick(updatePanelPosition);
  }
});

onMounted(() => {
  document.addEventListener('click', onClickOutside);
  window.addEventListener('resize', onViewportChange);
  window.addEventListener('scroll', onViewportChange, true);
});

onUnmounted(() => {
  document.removeEventListener('click', onClickOutside);
  window.removeEventListener('resize', onViewportChange);
  window.removeEventListener('scroll', onViewportChange, true);
});

defineExpose({ clearSelection, reset: asyncSelect.reset });
</script>
