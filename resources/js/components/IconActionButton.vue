<template>
  <button
    type="button"
    :class="['icon-action', config.className, attrs.class]"
    :title="title || config.title"
    :aria-label="title || config.title"
    v-bind="passthroughAttrs"
  >
    <AppIcon :name="config.icon" size="sm" />
  </button>
</template>

<script setup>
import { computed, useAttrs } from 'vue';

defineOptions({ inheritAttrs: false });

const props = defineProps({
  variant: {
    type: String,
    default: 'edit',
    validator: (v) => ['edit', 'delete', 'inactivate', 'view', 'print', 'cancel', 'permissions'].includes(v),
  },
  title: { type: String, default: '' },
});

const attrs = useAttrs();

const passthroughAttrs = computed(() => {
  const { class: _c, ...rest } = attrs;
  return rest;
});

const VARIANTS = {
  edit: {
    icon: 'pencil',
    title: 'Editar',
    className: 'icon-action-edit',
  },
  delete: {
    icon: 'trash',
    title: 'Eliminar',
    className: 'icon-action-delete',
  },
  inactivate: {
    icon: 'ban',
    title: 'Inactivar',
    className: 'icon-action-inactivate',
  },
  view: {
    icon: 'eye',
    title: 'Ver detalle',
    className: 'icon-action-view',
  },
  print: {
    icon: 'printer',
    title: 'Imprimir',
    className: 'icon-action-print',
  },
  cancel: {
    icon: 'x-circle',
    title: 'Cancelar',
    className: 'icon-action-delete',
  },
  permissions: {
    icon: 'key',
    title: 'Permisos',
    className: 'icon-action-view',
  },
};

const config = computed(() => VARIANTS[props.variant] || VARIANTS.edit);
</script>
