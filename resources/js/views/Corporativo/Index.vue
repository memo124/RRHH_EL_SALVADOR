<template>
  <DashboardLayout>
    <div class="space-y-6">
      <div>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Mantenimiento Corporativo</h1>
        <p class="text-sm text-slate-600 dark:text-slate-400">Estructura organizativa, empresas y unidades de negocio.</p>
      </div>

      <!-- Tabs -->
      <div class="flex border-b border-slate-200 dark:border-slate-700 overflow-x-auto">
        <button v-for="t in tabs" :key="t.key" @click="activeTab = t.key"
          :class="activeTab === t.key ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400 font-bold' : 'border-transparent text-slate-500 hover:text-slate-700'"
          class="py-3 px-5 border-b-2 text-sm font-medium transition-all whitespace-nowrap">
          {{ t.label }}
        </button>
      </div>

      <!-- Generic Section -->
      <div class="space-y-4">
        <div class="flex justify-between items-center">
          <input v-model="search" type="text" :placeholder="`Buscar ${currentTab?.label?.toLowerCase()}...`"
            class="w-full max-w-xs px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none" />
          <button @click="openCreate" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-semibold transition-colors">
            + {{ currentTab?.addLabel }}
          </button>
        </div>

        <SkeletonTable v-if="loading" />

        <!-- ── EMPRESAS ─────────────────────────────────────────────────── -->
        <div v-else-if="activeTab === 'empresas'" class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden shadow-sm">
          <table class="w-full text-left border-collapse">
            <thead><tr class="bg-slate-50 dark:bg-slate-700/50 text-slate-600 dark:text-slate-300 text-xs font-semibold uppercase border-b border-slate-200">
              <th class="px-6 py-4">ID</th><th class="px-6 py-4">Empresa</th><th class="px-6 py-4">NIT</th><th class="px-6 py-4">Teléfono</th><th class="px-6 py-4">Estado</th><th class="px-6 py-4 text-right">Acciones</th>
            </tr></thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-700 text-sm">
              <tr v-for="r in filtered" :key="r.ID_EMPRESA" class="hover:bg-slate-50 dark:hover:bg-slate-700/30">
                <td class="px-6 py-4 text-slate-500">{{ r.ID_EMPRESA }}</td>
                <td class="px-6 py-4 font-semibold text-slate-900 dark:text-white">{{ r.NOMBREEMPRESA }}</td>
                <td class="px-6 py-4">{{ r.NUMERONIT || 'N/A' }}</td>
                <td class="px-6 py-4">{{ r.TELEFONO || 'N/A' }}</td>
                <td class="px-6 py-4"><span :class="r.EMPRESAACTIVA ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700'" class="px-2 py-0.5 rounded text-xs font-semibold">{{ r.EMPRESAACTIVA ? 'Activa' : 'Inactiva' }}</span></td>
                <td class="px-6 py-4 text-right space-x-2">
                  <button @click="openEdit(r)" class="text-indigo-600 font-semibold text-xs hover:underline">Editar</button>
                  <button v-if="r.EMPRESAACTIVA" @click="inactivate(r, 'empresas', 'ID_EMPRESA')" class="text-rose-600 font-semibold text-xs hover:underline">Inactivar</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- ── ÁREAS ───────────────────────────────────────────────────── -->
        <div v-else-if="activeTab === 'areas'" class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden shadow-sm">
          <table class="w-full text-left border-collapse">
            <thead><tr class="bg-slate-50 dark:bg-slate-700/50 text-slate-600 dark:text-slate-300 text-xs font-semibold uppercase border-b border-slate-200">
              <th class="px-6 py-4">ID</th><th class="px-6 py-4">Área</th><th class="px-6 py-4">Empresa</th><th class="px-6 py-4">Prorrateada</th><th class="px-6 py-4">Estado</th><th class="px-6 py-4 text-right">Acciones</th>
            </tr></thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-700 text-sm">
              <tr v-for="r in filtered" :key="r.ID_AREA" class="hover:bg-slate-50 dark:hover:bg-slate-700/30">
                <td class="px-6 py-4 text-slate-500">{{ r.ID_AREA }}</td>
                <td class="px-6 py-4 font-semibold text-slate-900 dark:text-white">{{ r.NOMBREAREA }}</td>
                <td class="px-6 py-4">{{ empresaNombre(r.ID_EMPRESA) }}</td>
                <td class="px-6 py-4">{{ r.PRORRATEADA ? 'Sí' : 'No' }}</td>
                <td class="px-6 py-4"><span :class="r.ACTIVA ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700'" class="px-2 py-0.5 rounded text-xs font-semibold">{{ r.ACTIVA ? 'Activa' : 'Inactiva' }}</span></td>
                <td class="px-6 py-4 text-right space-x-2">
                  <button @click="openEdit(r)" class="text-indigo-600 font-semibold text-xs hover:underline">Editar</button>
                  <button v-if="r.ACTIVA" @click="inactivate(r, 'areas', 'ID_AREA')" class="text-rose-600 font-semibold text-xs hover:underline">Inactivar</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- ── CENTROS COSTO ───────────────────────────────────────────── -->
        <div v-else-if="activeTab === 'centros-costo'" class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden shadow-sm">
          <table class="w-full text-left border-collapse">
            <thead><tr class="bg-slate-50 dark:bg-slate-700/50 text-slate-600 dark:text-slate-300 text-xs font-semibold uppercase border-b border-slate-200">
              <th class="px-6 py-4">ID</th><th class="px-6 py-4">Código</th><th class="px-6 py-4">Nombre</th><th class="px-6 py-4">Empresa</th><th class="px-6 py-4">Estado</th><th class="px-6 py-4 text-right">Acciones</th>
            </tr></thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-700 text-sm">
              <tr v-for="r in filtered" :key="r.ID_CENTROCOSTO" class="hover:bg-slate-50 dark:hover:bg-slate-700/30">
                <td class="px-6 py-4 text-slate-500">{{ r.ID_CENTROCOSTO }}</td>
                <td class="px-6 py-4"><span class="font-mono text-xs bg-slate-100 px-2 py-0.5 rounded">{{ r.CODIGO_CENTROCOSTO }}</span></td>
                <td class="px-6 py-4 font-semibold text-slate-900 dark:text-white">{{ r.NOMBRE_CENTROCOSTO }}</td>
                <td class="px-6 py-4">{{ empresaNombre(r.ID_EMPRESA) }}</td>
                <td class="px-6 py-4"><span :class="r.ESACTIVO ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700'" class="px-2 py-0.5 rounded text-xs font-semibold">{{ r.ESACTIVO ? 'Activo' : 'Inactivo' }}</span></td>
                <td class="px-6 py-4 text-right space-x-2">
                  <button @click="openEdit(r)" class="text-indigo-600 font-semibold text-xs hover:underline">Editar</button>
                  <button v-if="r.ESACTIVO" @click="inactivate(r, 'centros-costo', 'ID_CENTROCOSTO')" class="text-rose-600 font-semibold text-xs hover:underline">Inactivar</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- ── DEPARTAMENTOS ───────────────────────────────────────────── -->
        <div v-else-if="activeTab === 'departamentos'" class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden shadow-sm">
          <table class="w-full text-left border-collapse">
            <thead><tr class="bg-slate-50 dark:bg-slate-700/50 text-slate-600 dark:text-slate-300 text-xs font-semibold uppercase border-b border-slate-200">
              <th class="px-6 py-4">ID</th><th class="px-6 py-4">Departamento</th><th class="px-6 py-4">Área</th><th class="px-6 py-4">Empresa</th><th class="px-6 py-4 text-right">Acciones</th>
            </tr></thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-700 text-sm">
              <tr v-for="r in filtered" :key="r.ID_DEPARTAMENTO" class="hover:bg-slate-50 dark:hover:bg-slate-700/30">
                <td class="px-6 py-4 text-slate-500">{{ r.ID_DEPARTAMENTO }}</td>
                <td class="px-6 py-4 font-semibold text-slate-900 dark:text-white">{{ r.NOMBREDEPARTAMENTO }}</td>
                <td class="px-6 py-4">{{ areaNombre(r.ID_AREA) }}</td>
                <td class="px-6 py-4">{{ empresaNombre(r.ID_EMPRESA) }}</td>
                <td class="px-6 py-4 text-right space-x-2">
                  <button @click="openEdit(r)" class="text-indigo-600 font-semibold text-xs hover:underline">Editar</button>
                  <button @click="deleteRecord(r, 'departamentos', 'ID_DEPARTAMENTO')" class="text-rose-600 font-semibold text-xs hover:underline">Eliminar</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- ── CARGOS ──────────────────────────────────────────────────── -->
        <div v-else-if="activeTab === 'cargos'" class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden shadow-sm">
          <table class="w-full text-left border-collapse">
            <thead><tr class="bg-slate-50 dark:bg-slate-700/50 text-slate-600 dark:text-slate-300 text-xs font-semibold uppercase border-b border-slate-200">
              <th class="px-6 py-4">ID</th><th class="px-6 py-4">Cargo</th><th class="px-6 py-4">Departamento</th><th class="px-6 py-4">Nivel</th><th class="px-6 py-4">Estado</th><th class="px-6 py-4 text-right">Acciones</th>
            </tr></thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-700 text-sm">
              <tr v-for="r in filtered" :key="r.ID_CARGO" class="hover:bg-slate-50 dark:hover:bg-slate-700/30">
                <td class="px-6 py-4 text-slate-500">{{ r.ID_CARGO }}</td>
                <td class="px-6 py-4 font-semibold text-slate-900 dark:text-white">{{ r.NOMBRECARGO }}</td>
                <td class="px-6 py-4">{{ deptoNombre(r.ID_DEPARTAMENTO) }}</td>
                <td class="px-6 py-4">{{ r.NIVEL_JERARQUICO }}</td>
                <td class="px-6 py-4"><span :class="r.CARGOESTADO ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700'" class="px-2 py-0.5 rounded text-xs font-semibold">{{ r.CARGOESTADO ? 'Activo' : 'Inactivo' }}</span></td>
                <td class="px-6 py-4 text-right space-x-2">
                  <button @click="openEdit(r)" class="text-indigo-600 font-semibold text-xs hover:underline">Editar</button>
                  <button v-if="r.CARGOESTADO" @click="inactivate(r, 'cargos', 'ID_CARGO')" class="text-rose-600 font-semibold text-xs hover:underline">Inactivar</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- ── SUCURSALES ──────────────────────────────────────────────── -->
        <div v-else-if="activeTab === 'sucursales'" class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden shadow-sm">
          <table class="w-full text-left border-collapse">
            <thead><tr class="bg-slate-50 dark:bg-slate-700/50 text-slate-600 dark:text-slate-300 text-xs font-semibold uppercase border-b border-slate-200">
              <th class="px-6 py-4">ID</th><th class="px-6 py-4">Sucursal</th><th class="px-6 py-4">Empresa</th><th class="px-6 py-4">Dirección</th><th class="px-6 py-4">Estado</th><th class="px-6 py-4 text-right">Acciones</th>
            </tr></thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-700 text-sm">
              <tr v-for="r in filtered" :key="r.ID_SUCURSAL" class="hover:bg-slate-50 dark:hover:bg-slate-700/30">
                <td class="px-6 py-4 text-slate-500">{{ r.ID_SUCURSAL }}</td>
                <td class="px-6 py-4 font-semibold text-slate-900 dark:text-white">{{ r.NOMBRESUCURSAL }}</td>
                <td class="px-6 py-4">{{ empresaNombre(r.ID_EMPRESA) }}</td>
                <td class="px-6 py-4">{{ r.DIRECCION || '—' }}</td>
                <td class="px-6 py-4"><span :class="r.ESACTIVA ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700'" class="px-2 py-0.5 rounded text-xs font-semibold">{{ r.ESACTIVA ? 'Activa' : 'Inactiva' }}</span></td>
                <td class="px-6 py-4 text-right space-x-2">
                  <button @click="openEdit(r)" class="text-indigo-600 font-semibold text-xs hover:underline">Editar</button>
                  <button v-if="r.ESACTIVA" @click="inactivate(r, 'sucursales', 'ID_SUCURSAL')" class="text-rose-600 font-semibold text-xs hover:underline">Inactivar</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- ── BODEGAS ─────────────────────────────────────────────────── -->
        <div v-else-if="activeTab === 'bodegas'" class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden shadow-sm">
          <table class="w-full text-left border-collapse">
            <thead><tr class="bg-slate-50 dark:bg-slate-700/50 text-slate-600 dark:text-slate-300 text-xs font-semibold uppercase border-b border-slate-200">
              <th class="px-6 py-4">ID</th><th class="px-6 py-4">Bodega</th><th class="px-6 py-4">Empresa</th><th class="px-6 py-4 text-right">Acciones</th>
            </tr></thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-700 text-sm">
              <tr v-for="r in filtered" :key="r.ID_BODEGA" class="hover:bg-slate-50 dark:hover:bg-slate-700/30">
                <td class="px-6 py-4 text-slate-500">{{ r.ID_BODEGA }}</td>
                <td class="px-6 py-4 font-semibold text-slate-900 dark:text-white">{{ r.NOMBREBODEGA }}</td>
                <td class="px-6 py-4">{{ empresaNombre(r.ID_EMPRESA) }}</td>
                <td class="px-6 py-4 text-right space-x-2">
                  <button @click="openEdit(r)" class="text-indigo-600 font-semibold text-xs hover:underline">Editar</button>
                  <button @click="deleteRecord(r, 'bodegas', 'ID_BODEGA')" class="text-rose-600 font-semibold text-xs hover:underline">Eliminar</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- ── RUTAS ───────────────────────────────────────────────────── -->
        <div v-else-if="activeTab === 'rutas'" class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden shadow-sm">
          <table class="w-full text-left border-collapse">
            <thead><tr class="bg-slate-50 dark:bg-slate-700/50 text-slate-600 dark:text-slate-300 text-xs font-semibold uppercase border-b border-slate-200">
              <th class="px-6 py-4">ID</th><th class="px-6 py-4">Ruta</th><th class="px-6 py-4">Empresa</th><th class="px-6 py-4">Estado</th><th class="px-6 py-4 text-right">Acciones</th>
            </tr></thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-700 text-sm">
              <tr v-for="r in filtered" :key="r.ID_RUTA" class="hover:bg-slate-50 dark:hover:bg-slate-700/30">
                <td class="px-6 py-4 text-slate-500">{{ r.ID_RUTA }}</td>
                <td class="px-6 py-4 font-semibold text-slate-900 dark:text-white">{{ r.NOMBRERUTA }}</td>
                <td class="px-6 py-4">{{ empresaNombre(r.ID_EMPRESA) }}</td>
                <td class="px-6 py-4"><span :class="r.ESACTIVA ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700'" class="px-2 py-0.5 rounded text-xs font-semibold">{{ r.ESACTIVA ? 'Activa' : 'Inactiva' }}</span></td>
                <td class="px-6 py-4 text-right space-x-2">
                  <button @click="openEdit(r)" class="text-indigo-600 font-semibold text-xs hover:underline">Editar</button>
                  <button v-if="r.ESACTIVA" @click="inactivate(r, 'rutas', 'ID_RUTA')" class="text-rose-600 font-semibold text-xs hover:underline">Inactivar</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- ══ DYNAMIC MODAL ════════════════════════════════════════════════════ -->
      <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-xl max-w-lg w-full overflow-hidden border border-slate-200 dark:border-slate-700">
          <div class="px-6 py-4 bg-slate-50 dark:bg-slate-700/50 border-b border-slate-200 flex justify-between items-center">
            <h3 class="text-base font-bold text-slate-950 dark:text-white">{{ isEditing ? 'Editar' : 'Nuevo' }} {{ currentTab?.label }}</h3>
            <button @click="showModal = false" class="text-slate-400 hover:text-slate-600 font-bold text-lg">✕</button>
          </div>
          <form v-submit-lock="save" class="p-6 space-y-4 max-h-[75vh] overflow-y-auto">

            <!-- EMPRESA FIELDS -->
            <template v-if="activeTab === 'empresas'">
              <FormField label="Nombre Empresa *" v-model="form.NOMBREEMPRESA" required />
              <FormField label="Abreviatura" v-model="form.ABREVIATURA" />
              <FormField label="NIT" v-model="form.NUMERONIT" />
              <FormField label="Reg. Patronal" v-model="form.NUMEROREGISTRO" />
              <FormField label="Teléfono" v-model="form.TELEFONO" />
              <FormField label="Giro" v-model="form.GIRO" />
              <FormField label="Dirección" v-model="form.DIRECCION" />
              <FormField label="URL Logo" v-model="form.URL_LOGO" placeholder="/images/logos/empresa-1.svg" />
              <div v-if="isEditing" class="space-y-2">
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase">Subir logo (PNG/JPG/SVG)</label>
                <input type="file" accept="image/*" @change="onLogoSelected" class="block w-full text-xs text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded file:border-0 file:bg-indigo-50 file:text-indigo-700" />
                <img v-if="logoPreview" :src="logoPreview" alt="Vista previa logo" class="h-12 object-contain border rounded p-1 bg-white" />
              </div>
            </template>

            <!-- AREA FIELDS -->
            <template v-else-if="activeTab === 'areas'">
              <FormField label="Nombre Área *" v-model="form.NOMBREAREA" required />
              <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Empresa *</label>
                <AsyncSelect
                  v-model="form.ID_EMPRESA"
                  catalog="empresas"
                  placeholder="Seleccionar empresa"
                />
              </div>
              <div class="flex items-center space-x-4 text-sm">
                <label class="flex items-center space-x-2 cursor-pointer">
                  <input type="checkbox" v-model="form.PRORRATEADA" class="rounded text-indigo-600" />
                  <span>Prorrateada</span>
                </label>
                <label class="flex items-center space-x-2 cursor-pointer">
                  <input type="checkbox" v-model="form.ACTIVA" class="rounded text-indigo-600" />
                  <span>Activa</span>
                </label>
              </div>
            </template>

            <!-- CENTRO COSTO FIELDS -->
            <template v-else-if="activeTab === 'centros-costo'">
              <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Empresa *</label>
                <AsyncSelect
                  v-model="form.ID_EMPRESA"
                  catalog="empresas"
                  placeholder="Seleccionar empresa"
                />
              </div>
              <FormField label="Código *" v-model="form.CODIGO_CENTROCOSTO" required />
              <FormField label="Nombre *" v-model="form.NOMBRE_CENTROCOSTO" required />
              <FormField label="Descripción" v-model="form.DESCRIPCION" />
            </template>

            <!-- DEPARTAMENTO FIELDS -->
            <template v-else-if="activeTab === 'departamentos'">
              <FormField label="Nombre Departamento *" v-model="form.NOMBREDEPARTAMENTO" required />
              <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Empresa *</label>
                <AsyncSelect
                  v-model="form.ID_EMPRESA"
                  catalog="empresas"
                  placeholder="Seleccionar empresa"
                />
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Área *</label>
                <AsyncSelect
                  v-model="form.ID_AREA"
                  catalog="areas"
                  :params="areaParams"
                  :disabled="!form.ID_EMPRESA"
                  placeholder="Seleccionar área"
                />
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Centro de Costo</label>
                <AsyncSelect
                  v-model="form.ID_CENTROCOSTO"
                  catalog="centros-costo"
                  :params="centroCostoParams"
                  nullable
                  placeholder="Ninguno"
                />
              </div>
              <FormField label="Cuenta Contable" v-model="form.CUENTACONTABLE" />
            </template>

            <!-- CARGO FIELDS -->
            <template v-else-if="activeTab === 'cargos'">
              <FormField label="Nombre Cargo *" v-model="form.NOMBRECARGO" required />
              <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Departamento *</label>
                <AsyncSelect
                  v-model="form.ID_DEPARTAMENTO"
                  catalog="departamentos"
                  placeholder="Seleccionar departamento"
                />
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Nivel Jerárquico</label>
                <input v-model.number="form.NIVEL_JERARQUICO" type="number" min="1" class="w-full px-3 py-2 border rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none" />
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Cargo Padre</label>
                <AsyncSelect
                  v-model="form.ID_CARGO_PADRE"
                  catalog="cargos"
                  nullable
                  placeholder="Ninguno"
                />
              </div>
            </template>

            <!-- SUCURSAL FIELDS -->
            <template v-else-if="activeTab === 'sucursales'">
              <FormField label="Nombre Sucursal *" v-model="form.NOMBRESUCURSAL" required />
              <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Empresa *</label>
                <AsyncSelect
                  v-model="form.ID_EMPRESA"
                  catalog="empresas"
                  placeholder="Seleccionar empresa"
                />
              </div>
              <FormField label="Dirección" v-model="form.DIRECCION" />
            </template>

            <!-- BODEGA FIELDS -->
            <template v-else-if="activeTab === 'bodegas'">
              <FormField label="Nombre Bodega *" v-model="form.NOMBREBODEGA" required />
              <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Empresa *</label>
                <AsyncSelect
                  v-model="form.ID_EMPRESA"
                  catalog="empresas"
                  placeholder="Seleccionar empresa"
                />
              </div>
            </template>

            <!-- RUTA FIELDS -->
            <template v-else-if="activeTab === 'rutas'">
              <FormField label="Nombre Ruta *" v-model="form.NOMBRERUTA" required />
              <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Empresa *</label>
                <AsyncSelect
                  v-model="form.ID_EMPRESA"
                  catalog="empresas"
                  placeholder="Seleccionar empresa"
                />
              </div>
            </template>

            <div v-if="modalError" class="text-xs text-red-500 bg-red-50 p-2 rounded font-semibold">{{ modalError }}</div>
            <div class="flex justify-end space-x-3 pt-4 border-t">
              <button data-no-lock type="button" @click="showModal = false" class="px-4 py-2 border rounded-lg text-sm hover:bg-slate-50">Cancelar</button>
              <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-semibold hover:bg-indigo-700">Guardar</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import DashboardLayout from '../Dashboard.vue';
import SkeletonTable from '../../components/SkeletonTable.vue';
import api from '../../services/api';

// Inline reusable field
const FormField = {
  props: ['label', 'modelValue', 'required'],
  emits: ['update:modelValue'],
  template: `
    <div>
      <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">{{ label }}</label>
      <input :value="modelValue" @input="$emit('update:modelValue', $event.target.value)" :required="required"
        class="w-full px-3 py-2 border rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500" />
    </div>
  `
};

const tabs = [
  { key: 'empresas',      label: 'Empresas',       addLabel: 'Nueva Empresa',        api: 'empresas',      search: 'NOMBREEMPRESA' },
  { key: 'areas',         label: 'Áreas',          addLabel: 'Nueva Área',           api: 'areas',         search: 'NOMBREAREA' },
  { key: 'centros-costo', label: 'Centros de Costo',addLabel: 'Nuevo Centro de Costo',api: 'centros-costo', search: 'NOMBRE_CENTROCOSTO' },
  { key: 'departamentos', label: 'Departamentos',  addLabel: 'Nuevo Departamento',   api: 'departamentos', search: 'NOMBREDEPARTAMENTO' },
  { key: 'cargos',        label: 'Cargos',         addLabel: 'Nuevo Cargo',          api: 'cargos',        search: 'NOMBRECARGO' },
  { key: 'sucursales',    label: 'Sucursales',     addLabel: 'Nueva Sucursal',       api: 'sucursales',    search: 'NOMBRESUCURSAL' },
  { key: 'bodegas',       label: 'Bodegas',        addLabel: 'Nueva Bodega',         api: 'bodegas',       search: 'NOMBREBODEGA' },
  { key: 'rutas',         label: 'Rutas',          addLabel: 'Nueva Ruta',           api: 'rutas',         search: 'NOMBRERUTA' },
];

const activeTab = ref('empresas');
const currentTab = computed(() => tabs.find(t => t.key === activeTab.value));
const loading = ref(false);
const search  = ref('');
const showModal  = ref(false);
const logoFile   = ref(null);
const logoPreview = ref('');
const isEditing  = ref(false);
const modalError = ref('');
const form = ref({});

const data = ref({
  empresas: [], areas: [], 'centros-costo': [], departamentos: [],
  cargos: [], sucursales: [], bodegas: [], rutas: []
});

const catalogos = ref({ empresas: [], areas: [], centrosCosto: [], departamentos: [], cargos: [] });

const areaParams = computed(() => (form.value.ID_EMPRESA ? { ID_EMPRESA: form.value.ID_EMPRESA } : {}));
const centroCostoParams = computed(() => (form.value.ID_EMPRESA ? { ID_EMPRESA: form.value.ID_EMPRESA } : {}));

const filtered = computed(() => {
  const key = currentTab.value?.search;
  const list = data.value[activeTab.value] || [];
  if (!search.value || !key) return list;
  return list.filter(r => (r[key] || '').toLowerCase().includes(search.value.toLowerCase()));
});

// Lookup helpers
const empresaNombre = (id) => catalogos.value.empresas.find(e => e.ID_EMPRESA === id)?.NOMBREEMPRESA || id;
const areaNombre    = (id) => catalogos.value.areas.find(a => a.ID_AREA === id)?.NOMBREAREA || id;
const deptoNombre   = (id) => catalogos.value.departamentos.find(d => d.ID_DEPARTAMENTO === id)?.NOMBREDEPARTAMENTO || id;

const loadAll = async () => {
  loading.value = true;
  try {
    const [emp, area, cc, dep, car, suc, bod, rut] = await Promise.all([
      api.get('/empresas'), api.get('/areas'), api.get('/centros-costo'),
      api.get('/departamentos'), api.get('/cargos'), api.get('/sucursales'),
      api.get('/bodegas'), api.get('/rutas'),
    ]);
    data.value['empresas']      = emp.data;
    data.value['areas']         = area.data;
    data.value['centros-costo'] = cc.data;
    data.value['departamentos'] = dep.data;
    data.value['cargos']        = car.data;
    data.value['sucursales']    = suc.data;
    data.value['bodegas']       = bod.data;
    data.value['rutas']         = rut.data;

    catalogos.value.empresas     = emp.data;
    catalogos.value.areas        = area.data;
    catalogos.value.centrosCosto = cc.data;
    catalogos.value.departamentos= dep.data;
    catalogos.value.cargos       = car.data;
  } catch (err) { console.error(err); }
  finally { loading.value = false; }
};

onMounted(loadAll);
watch(activeTab, () => { search.value = ''; });
watch(() => form.value.ID_EMPRESA, (next, prev) => {
  if (prev != null && next !== prev && activeTab.value === 'departamentos') {
    form.value.ID_AREA = null;
    form.value.ID_CENTROCOSTO = null;
  }
});

const defaultForms = {
  empresas:      { NOMBREEMPRESA: '', ABREVIATURA: '', NUMERONIT: '', NUMEROREGISTRO: '', TELEFONO: '', GIRO: '', DIRECCION: '', URL_LOGO: '' },
  areas:         { NOMBREAREA: '', ID_EMPRESA: null, PRORRATEADA: false, ACTIVA: true },
  'centros-costo':{ ID_EMPRESA: null, CODIGO_CENTROCOSTO: '', NOMBRE_CENTROCOSTO: '', DESCRIPCION: '' },
  departamentos: { NOMBREDEPARTAMENTO: '', ID_EMPRESA: null, ID_AREA: null, ID_CENTROCOSTO: null, CUENTACONTABLE: '' },
  cargos:        { NOMBRECARGO: '', ID_DEPARTAMENTO: null, ID_CENTROCOSTO: null, ID_CARGO_PADRE: null, NIVEL_JERARQUICO: 1, CARGOESTADO: true },
  sucursales:    { NOMBRESUCURSAL: '', ID_EMPRESA: null, DIRECCION: '', ESACTIVA: true },
  bodegas:       { NOMBREBODEGA: '', ID_EMPRESA: null },
  rutas:         { NOMBRERUTA: '', ID_EMPRESA: null, ID_CENTROCOSTO: null, ESACTIVA: true },
};

const getIdKey = (tab) => {
  const map = { empresas: 'ID_EMPRESA', areas: 'ID_AREA', 'centros-costo': 'ID_CENTROCOSTO', departamentos: 'ID_DEPARTAMENTO', cargos: 'ID_CARGO', sucursales: 'ID_SUCURSAL', bodegas: 'ID_BODEGA', rutas: 'ID_RUTA' };
  return map[tab];
};

const openCreate = () => {
  isEditing.value = false;
  modalError.value = '';
  logoFile.value = null;
  logoPreview.value = '';
  form.value = { ...defaultForms[activeTab.value] };
  showModal.value = true;
};

const openEdit = (r) => {
  isEditing.value = true;
  modalError.value = '';
  logoFile.value = null;
  logoPreview.value = r.URL_LOGO || '';
  form.value = { ...r };
  showModal.value = true;
};

const onLogoSelected = (event) => {
  const file = event.target.files?.[0];
  logoFile.value = file || null;
  if (file) {
    logoPreview.value = URL.createObjectURL(file);
  }
};

const save = async () => {
  const tab   = currentTab.value;
  const idKey = getIdKey(activeTab.value);
  try {
    if (isEditing.value) {
      await api.put(`/${tab.api}/${form.value[idKey]}`, form.value);
      if (activeTab.value === 'empresas' && logoFile.value) {
        const fd = new FormData();
        fd.append('logo', logoFile.value);
        await api.post(`/empresas/${form.value[idKey]}/logo`, fd, {
          headers: { 'Content-Type': 'multipart/form-data' },
        });
      }
    } else {
      const res = await api.post(`/${tab.api}`, form.value);
      if (activeTab.value === 'empresas' && logoFile.value && res.data?.ID_EMPRESA) {
        const fd = new FormData();
        fd.append('logo', logoFile.value);
        await api.post(`/empresas/${res.data.ID_EMPRESA}/logo`, fd, {
          headers: { 'Content-Type': 'multipart/form-data' },
        });
      }
    }
    showModal.value = false;
    logoFile.value = null;
    logoPreview.value = '';
    loadAll();
  } catch (err) {
    modalError.value = err.response?.data?.errors
      ? Object.values(err.response.data.errors).flat().join(' ')
      : 'Error al guardar.';
  }
};

const inactivate = async (r, endpoint, idKey) => {
  if (confirm('¿Confirmar inactivación?')) {
    await api.delete(`/${endpoint}/${r[idKey]}`);
    loadAll();
  }
};

const deleteRecord = async (r, endpoint, idKey) => {
  if (confirm('¿Confirmar eliminación?')) {
    await api.delete(`/${endpoint}/${r[idKey]}`);
    loadAll();
  }
};
</script>
