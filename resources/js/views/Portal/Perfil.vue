<template>
  <PortalLayout>
    <div class="space-y-6">
      <div class="page-header">
        <div>
          <h1 class="page-title">Mi perfil</h1>
          <p class="page-subtitle mt-1">Datos personales y laborales (solo lectura). Contacte a Recursos Humanos para actualizarlos.</p>
        </div>
      </div>

      <div v-if="loading" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div v-for="i in 4" :key="i" class="card-panel animate-pulse h-32"></div>
      </div>

      <div v-else-if="!perfil" class="card-panel text-center text-sm text-slate-500 dark:text-slate-400 py-10">
        No se pudo cargar su información de perfil.
      </div>

      <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <section class="card-panel">
          <h2 class="text-sm font-bold text-slate-700 dark:text-slate-200 uppercase tracking-wide mb-3">Datos personales</h2>
          <dl class="space-y-2 text-sm">
            <Field label="Nombre completo" :value="nombreCompleto" />
            <Field label="Código de empleado" :value="perfil.CODIGOEMPLEADO" />
            <Field label="DUI" :value="perfil.DUI" />
            <Field label="NIT" :value="perfil.NIT" />
            <Field label="ISSS" :value="perfil.ISSS" />
            <Field label="NUP" :value="perfil.NUP" />
            <Field label="Género" :value="perfil.GENERO" />
            <Field label="Fecha de nacimiento" :value="fmtDate(perfil.FECHANACIMIENTO)" />
          </dl>
        </section>

        <section class="card-panel">
          <h2 class="text-sm font-bold text-slate-700 dark:text-slate-200 uppercase tracking-wide mb-3">Contacto</h2>
          <dl class="space-y-2 text-sm">
            <Field label="Correo personal" :value="perfil.CORREOELECTRONICO" />
            <Field label="Correo empresarial" :value="perfil.CORREOELECTRONICOEMPRESARIAL" />
            <Field label="Teléfono celular" :value="perfil.TELEFONOCELULAR" />
            <Field label="Dirección" :value="perfil.DIRECCION" />
          </dl>
        </section>

        <section class="card-panel">
          <h2 class="text-sm font-bold text-slate-700 dark:text-slate-200 uppercase tracking-wide mb-3">Información laboral</h2>
          <dl class="space-y-2 text-sm">
            <Field label="Empresa" :value="perfil.NOMBREEMPRESA" />
            <Field label="Departamento" :value="perfil.NOMBREDEPARTAMENTO" />
            <Field label="Cargo" :value="perfil.NOMBRECARGO" />
            <Field label="Tipo de contratación" :value="perfil.TIPOCONTRATACION" />
            <Field label="Fecha de ingreso" :value="fmtDate(perfil.FECHAINGRESO)" />
            <Field label="Jefe inmediato" :value="perfil.JEFE_NOMBRE" />
          </dl>
        </section>
      </div>
    </div>
  </PortalLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import PortalLayout from './PortalLayout.vue';
import api from '../../services/api';

const Field = {
  props: { label: String, value: [String, Number] },
  template: `
    <div class="flex justify-between gap-3 border-b border-slate-100 dark:border-slate-700 pb-2">
      <dt class="text-slate-500 dark:text-slate-400">{{ label }}</dt>
      <dd class="font-medium text-slate-800 dark:text-slate-100 text-right">{{ value || '—' }}</dd>
    </div>
  `,
};

const perfil = ref(null);
const loading = ref(true);

const nombreCompleto = computed(() => {
  if (!perfil.value) return '';
  return [perfil.value.NOMBRES, perfil.value.APELLIDO_1, perfil.value.APELLIDO_2].filter(Boolean).join(' ');
});

function fmtDate(d) {
  return d ? new Date(d).toLocaleDateString('es-SV') : '—';
}

async function loadPerfil() {
  loading.value = true;
  try {
    const { data } = await api.get('/portal/me');
    perfil.value = data;
  } catch {
    perfil.value = null;
  } finally {
    loading.value = false;
  }
}

onMounted(loadPerfil);
</script>
