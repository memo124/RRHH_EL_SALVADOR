<template>
  <DashboardLayout>
    <div class="space-y-6">
      <div class="page-header">
        <div>
          <h1 class="page-title">Mantenimiento de Seguridad</h1>
          <p class="page-subtitle">Administre los accesos, usuarios, roles y permisos.</p>
        </div>
      </div>

      <!-- Tabs Navigation -->
      <div class="flex border-b border-slate-200 dark:border-slate-700 overflow-x-auto">
        <button v-for="t in tabs" :key="t.key" @click="activeTab = t.key"
          :class="activeTab === t.key ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400 font-bold' : 'border-transparent text-slate-500 hover:text-slate-700'"
          class="py-3 px-5 border-b-2 text-sm font-medium transition-all whitespace-nowrap">
          {{ t.label }}
        </button>
      </div>

      <!-- ══ TAB: USUARIOS ══════════════════════════════════════════════════════ -->
      <div v-if="activeTab === 'usuarios'" class="space-y-4">
        <div class="flex justify-end">
          <button @click="openCreateUserModal" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-semibold transition-colors">
            + Nuevo Usuario
          </button>
        </div>
        <SkeletonTable v-if="loading" />
        <div v-else class="table-shell table-scroll">
          <table v-table-cards class="table-cards w-full text-left border-collapse">
            <thead>
              <tr class="bg-slate-50 dark:bg-slate-700/50 text-slate-600 dark:text-slate-300 text-xs font-semibold uppercase border-b border-slate-200">
                <th class="px-6 py-4">ID</th>
                <th class="px-6 py-4">Usuario</th>
                <th class="px-6 py-4">Email</th>
                <th class="px-6 py-4">Estado</th>
                <th class="px-6 py-4 text-right">Acciones</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-700 text-sm">
              <tr v-for="u in usuarios" :key="u.ID_USUARIO" class="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors">
                <td class="px-6 py-4 text-slate-500">{{ u.ID_USUARIO }}</td>
                <td class="px-6 py-4 font-semibold text-slate-900 dark:text-white">{{ u.USUARIO }}</td>
                <td class="px-6 py-4">{{ u.EMAIL }}</td>
                <td class="px-6 py-4">
                  <span :class="u.ESACTIVO ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700'" class="px-2 py-0.5 rounded text-xs font-semibold mr-2">
                    {{ u.ESACTIVO ? 'Activo' : 'Inactivo' }}
                  </span>
                  <span v-if="u.BLOQUEADO" class="bg-amber-50 text-amber-700 px-2 py-0.5 rounded text-xs font-semibold">Bloqueado</span>
                </td>
                <td class="px-6 py-4 text-right space-x-3">
                  <IconActionButton variant="edit" @click="editUsuario(u)" />
                  <IconActionButton variant="permissions" title="Excepciones" @click="openPermissionsModal(u)" />
                </td>
              </tr>
            </tbody>
          </table>
          <PaginationBar
            v-if="activeTab === 'usuarios'"
            :page="usuariosPage"
            :last-page="usuariosLastPage"
            :per-page="usuariosPerPage"
            :total="usuariosTotal"
            :loading="loadingUsuarios"
            @update:page="setUsuariosPage"
            @update:per-page="setUsuariosPerPage"
          />
        </div>
      </div>

      <!-- ══ TAB: ROLES ═════════════════════════════════════════════════════════ -->
      <div v-if="activeTab === 'roles'" class="space-y-4">
        <div class="flex justify-end">
          <button @click="openCreateRolModal" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-semibold transition-colors">
            + Nuevo Rol
          </button>
        </div>
        <SkeletonTable v-if="loading" />
        <div v-else class="table-shell table-scroll">
          <table v-table-cards class="table-cards w-full text-left border-collapse">
            <thead>
              <tr class="bg-slate-50 dark:bg-slate-700/50 text-slate-600 dark:text-slate-300 text-xs font-semibold uppercase border-b border-slate-200">
                <th class="px-6 py-4">ID</th>
                <th class="px-6 py-4">Rol</th>
                <th class="px-6 py-4">Descripción</th>
                <th class="px-6 py-4">Estado</th>
                <th class="px-6 py-4 text-right">Acciones</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-700 text-sm">
              <tr v-for="r in roles" :key="r.ID_ROL" class="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors">
                <td class="px-6 py-4 text-slate-500">{{ r.ID_ROL }}</td>
                <td class="px-6 py-4 font-semibold text-slate-900 dark:text-white">{{ r.NOMBREROL }}</td>
                <td class="px-6 py-4 text-slate-500 dark:text-slate-400">{{ r.DESCRIPCION || 'N/A' }}</td>
                <td class="px-6 py-4">
                  <span :class="r.ESACTIVO ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700'" class="px-2 py-0.5 rounded text-xs font-semibold">
                    {{ r.ESACTIVO ? 'Activo' : 'Inactivo' }}
                  </span>
                </td>
                <td class="px-6 py-4 text-right space-x-3">
                  <IconActionButton variant="edit" @click="editRol(r)" />
                  <IconActionButton variant="permissions" title="Permisos del rol" @click="openRolPermisosModal(r)" />
                  <IconActionButton v-if="r.ESACTIVO" variant="inactivate" @click="inactivateRol(r)" />
                </td>
              </tr>
            </tbody>
          </table>
          <PaginationBar
            v-if="activeTab === 'roles'"
            :page="rolesPage"
            :last-page="rolesLastPage"
            :per-page="rolesPerPage"
            :total="rolesTotal"
            :loading="loadingRoles"
            @update:page="setRolesPage"
            @update:per-page="setRolesPerPage"
          />
        </div>
      </div>

      <!-- ══ TAB: PERMISOS ══════════════════════════════════════════════════════ -->
      <div v-if="activeTab === 'permisos'" class="space-y-4">
        <SkeletonTable v-if="loading" />
        <div v-else class="table-shell table-scroll">
          <table v-table-cards class="table-cards w-full text-left border-collapse">
            <thead>
              <tr class="bg-slate-50 dark:bg-slate-700/50 text-slate-600 dark:text-slate-300 text-xs font-semibold uppercase border-b border-slate-200">
                <th class="px-6 py-4">ID</th>
                <th class="px-6 py-4">Código</th>
                <th class="px-6 py-4">Nombre</th>
                <th class="px-6 py-4">Descripción</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-700 text-sm">
              <tr v-for="p in permisosCatalogo" :key="p.ID_PERMISO" class="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors">
                <td class="px-6 py-4 text-slate-500">{{ p.ID_PERMISO }}</td>
                <td class="px-6 py-4">
                  <span class="bg-indigo-50 text-indigo-700 px-2 py-0.5 rounded text-xs font-mono font-bold">{{ p.CODIGO_PERMISO }}</span>
                </td>
                <td class="px-6 py-4 font-semibold text-slate-900 dark:text-white">{{ p.NOMBRE_PERMISO }}</td>
                <td class="px-6 py-4 text-slate-500 dark:text-slate-400">{{ p.DESCRIPCION || '—' }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- ══ TAB: ERRORES DEL SISTEMA ═════════════════════════════════════════ -->
      <div v-if="activeTab === 'errores'" class="space-y-4">
        <div class="rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/40 p-4 text-sm text-slate-600 dark:text-slate-300">
          Se conservan hasta <strong>3 archivos</strong> (rotación cada <strong>3 días</strong>).
          Los detalles técnicos no se muestran al usuario final; aquí puede consultarlos con referencia legible.
        </div>

        <SkeletonTable v-if="loadingErrores" />
        <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-4">
          <div class="table-shell overflow-hidden">
            <div class="px-4 py-3 border-b border-slate-200 dark:border-slate-700 font-semibold text-sm">Archivos</div>
            <div class="divide-y divide-slate-200 dark:divide-slate-700">
              <button
                v-for="file in errorFiles"
                :key="file.name"
                type="button"
                @click="loadErrorJournal(file.name)"
                :class="selectedErrorFile === file.name ? 'bg-indigo-50 dark:bg-indigo-900/20' : 'hover:bg-slate-50 dark:hover:bg-slate-800'"
                class="w-full text-left px-4 py-3 text-sm transition-colors"
              >
                <div class="font-semibold text-slate-900 dark:text-white">{{ file.name }}</div>
                <div class="text-xs text-slate-500 mt-1">{{ file.entries_count }} eventos</div>
              </button>
              <div v-if="!errorFiles.length" class="px-4 py-6 text-sm text-slate-500">Sin errores registrados.</div>
            </div>
          </div>

          <div class="lg:col-span-2 table-shell overflow-hidden">
            <div class="px-4 py-3 border-b border-slate-200 dark:border-slate-700 font-semibold text-sm">
              Detalle {{ selectedErrorFile ? `— ${selectedErrorFile}` : '' }}
            </div>
            <div v-if="!selectedErrorFile" class="p-6 text-sm text-slate-500">Seleccione un archivo para ver los errores.</div>
            <div v-else-if="loadingErrorDetail" class="p-6 text-sm text-slate-500 animate-pulse">Cargando…</div>
            <div v-else class="divide-y divide-slate-200 dark:divide-slate-700 max-h-[70vh] overflow-y-auto">
              <div v-for="entry in errorEntries" :key="entry.referencia + entry.fecha" class="p-4 space-y-2">
                <div class="flex flex-wrap gap-2 items-center">
                  <span class="font-mono text-xs bg-rose-50 text-rose-700 px-2 py-0.5 rounded">{{ entry.referencia }}</span>
                  <span class="text-xs text-slate-500">{{ entry.fecha }}</span>
                </div>
                <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ entry.resumen }}</p>
                <p class="text-xs text-slate-500">{{ entry.tipo }} · {{ entry.ubicacion }}</p>
                <p class="text-xs text-slate-600 dark:text-slate-300"><strong>Petición:</strong> {{ entry.peticion }} · Usuario #{{ entry.usuario_id ?? '—' }} · {{ entry.ip }}</p>
                <ul v-if="entry.trace?.length" class="text-xs font-mono text-slate-500 space-y-1 mt-2">
                  <li v-for="line in entry.trace" :key="line">{{ line }}</li>
                </ul>
              </div>
              <div v-if="!errorEntries.length" class="p-6 text-sm text-slate-500">Este archivo no tiene eventos.</div>
            </div>
          </div>
        </div>
      </div>

      <!-- ══ TAB: AUDITORÍA ═════════════════════════════════════════════════════ -->
      <div v-if="activeTab === 'auditoria'" class="space-y-4">
        <div class="flex flex-wrap gap-3 items-end table-shell p-4">
          <div>
            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Tabla</label>
            <select v-model="auditoriaFiltros.TABLA" class="px-3 py-2 border rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
              <option value="">Todas</option>
              <option v-for="t in auditoriaTablas" :key="t" :value="t">{{ t }}</option>
            </select>
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Acción</label>
            <select v-model="auditoriaFiltros.ACCION" class="px-3 py-2 border rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
              <option value="">Todas</option>
              <option value="create">Creación</option>
              <option value="update">Actualización</option>
              <option value="delete">Eliminación</option>
            </select>
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Desde</label>
            <input v-model="auditoriaFiltros.fecha_inicio" type="date" class="px-3 py-2 border rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white" />
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Hasta</label>
            <input v-model="auditoriaFiltros.fecha_fin" type="date" class="px-3 py-2 border rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white" />
          </div>
          <button @click="applyAuditoriaFiltros" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-semibold transition-colors">
            Filtrar
          </button>
          <button @click="clearAuditoriaFiltros" class="px-4 py-2 border rounded-lg text-sm hover:bg-slate-50">
            Limpiar
          </button>
        </div>

        <SkeletonTable v-if="loadingAuditoria" />
        <div v-else class="table-shell table-scroll">
          <table v-table-cards class="table-cards w-full text-left border-collapse">
            <thead>
              <tr class="bg-slate-50 dark:bg-slate-700/50 text-slate-600 dark:text-slate-300 text-xs font-semibold uppercase border-b border-slate-200">
                <th class="px-6 py-4">Fecha</th>
                <th class="px-6 py-4">Usuario</th>
                <th class="px-6 py-4">Tabla</th>
                <th class="px-6 py-4">Registro</th>
                <th class="px-6 py-4">Acción</th>
                <th class="px-6 py-4">IP</th>
                <th class="px-6 py-4 text-right">Detalle</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-700 text-sm">
              <tr v-for="a in auditoria" :key="a.ID_AUDITORIA" class="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors">
                <td class="px-6 py-4 text-slate-500 whitespace-nowrap">{{ formatFecha(a.FECHA) }}</td>
                <td class="px-6 py-4 font-semibold text-slate-900 dark:text-white">{{ a.USUARIO_NOMBRE || '—' }}</td>
                <td class="px-6 py-4"><span class="bg-slate-100 text-slate-700 px-2 py-0.5 rounded text-xs font-mono">{{ a.TABLA }}</span></td>
                <td class="px-6 py-4 text-slate-500">{{ a.ID_REGISTRO || '—' }}</td>
                <td class="px-6 py-4">
                  <span :class="accionBadgeClass(a.ACCION)" class="px-2 py-0.5 rounded text-xs font-semibold">{{ accionLabel(a.ACCION) }}</span>
                </td>
                <td class="px-6 py-4 text-slate-500">{{ a.IP || '—' }}</td>
                <td class="px-6 py-4 text-right">
                  <button @click="viewAuditoriaDetalle(a)" class="text-indigo-600 hover:underline text-xs font-semibold">Ver</button>
                </td>
              </tr>
              <tr v-if="!auditoria.length">
                <td colspan="7" class="px-6 py-8 text-center text-slate-500">Sin registros de auditoría.</td>
              </tr>
            </tbody>
          </table>
          <PaginationBar
            v-if="activeTab === 'auditoria'"
            :page="auditoriaPage"
            :last-page="auditoriaLastPage"
            :per-page="auditoriaPerPage"
            :total="auditoriaTotal"
            :loading="loadingAuditoria"
            @update:page="setAuditoriaPage"
            @update:per-page="setAuditoriaPerPage"
          />
        </div>
      </div>

      <!-- ══ MODAL: DETALLE AUDITORÍA ═══════════════════════════════════════════ -->
      <AppModalShell :open="showAuditoriaDetalle" @close="showAuditoriaDetalle = false">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-xl max-w-2xl w-full mx-auto overflow-hidden border border-slate-200 dark:border-slate-700">
          <div class="px-6 py-4 bg-slate-50 dark:bg-slate-700/50 border-b border-slate-200 flex justify-between items-center">
            <h3 class="text-base font-bold text-slate-950 dark:text-white">Detalle de Auditoría #{{ auditoriaSeleccionada?.ID_AUDITORIA }}</h3>
            <button @click="showAuditoriaDetalle = false" class="text-slate-400 hover:text-slate-600 font-bold text-lg" aria-label="Cerrar"><AppIcon name="x" size="md" /></button>
          </div>
          <div class="p-6 space-y-4 max-h-[70vh] overflow-y-auto">
            <div class="grid grid-cols-2 gap-4 text-sm">
              <div><span class="font-semibold text-slate-500">Tabla:</span> {{ auditoriaSeleccionada?.TABLA }}</div>
              <div><span class="font-semibold text-slate-500">Registro:</span> {{ auditoriaSeleccionada?.ID_REGISTRO || '—' }}</div>
              <div><span class="font-semibold text-slate-500">Acción:</span> {{ accionLabel(auditoriaSeleccionada?.ACCION) }}</div>
              <div><span class="font-semibold text-slate-500">Fecha:</span> {{ formatFecha(auditoriaSeleccionada?.FECHA) }}</div>
            </div>
            <div>
              <p class="text-xs font-semibold text-slate-500 uppercase mb-1">Antes</p>
              <pre class="bg-slate-50 dark:bg-slate-900/40 p-3 rounded-lg text-xs overflow-x-auto">{{ formatJson(auditoriaSeleccionada?.BEFORE_JSON) }}</pre>
            </div>
            <div>
              <p class="text-xs font-semibold text-slate-500 uppercase mb-1">Después</p>
              <pre class="bg-slate-50 dark:bg-slate-900/40 p-3 rounded-lg text-xs overflow-x-auto">{{ formatJson(auditoriaSeleccionada?.AFTER_JSON) }}</pre>
            </div>
          </div>
        </div>
      </AppModalShell>

      <!-- ══ MODAL: USUARIO CRUD ════════════════════════════════════════════════ -->
      <AppModalShell :open="showUserModal" @close="showUserModal = false">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-xl max-w-md w-full mx-auto overflow-hidden border border-slate-200 dark:border-slate-700">
          <div class="px-6 py-4 bg-slate-50 dark:bg-slate-700/50 border-b border-slate-200 flex justify-between items-center">
            <h3 class="text-base font-bold text-slate-950 dark:text-white">{{ isEditingUser ? 'Editar Usuario' : 'Nuevo Usuario' }}</h3>
            <button @click="showUserModal = false" class="text-slate-400 hover:text-slate-600 font-bold text-lg" aria-label="Cerrar"><AppIcon name="x" size="md" /></button>
          </div>
          <form v-submit-lock="saveUser" class="p-6 space-y-4 max-h-[80vh] overflow-y-auto">
            <div>
              <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Nombre de Usuario *</label>
              <input v-model="userForm.USUARIO" type="text" required class="w-full px-3 py-2 border rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500" />
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Email *</label>
              <input v-model="userForm.EMAIL" type="email" required class="w-full px-3 py-2 border rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500" />
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">
                Contraseña {{ isEditingUser ? '(Dejar vacío para mantener)' : '*' }}
              </label>
              <input v-model="userForm.CONTRASENA" type="password" :required="!isEditingUser" class="w-full px-3 py-2 border rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500" />
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Asociar Empleado</label>
              <AsyncSelect
                v-model="userForm.ID_EMPLEADO"
                endpoint="/empleados/select"
                placeholder="Ninguno"
                search-placeholder="Buscar empleado…"
                nullable
              />
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Roles *</label>
              <div class="space-y-2 max-h-[140px] overflow-y-auto border p-2 rounded-lg">
                <label v-for="r in rolesCatalog" :key="r.ID_ROL" class="flex items-center space-x-2 text-sm text-slate-700 dark:text-slate-300 cursor-pointer">
                  <input type="checkbox" :value="r.ID_ROL" v-model="userForm.ROLES" class="rounded text-indigo-600 focus:ring-indigo-500" />
                  <span>{{ r.NOMBREROL }}</span>
                </label>
              </div>
            </div>
            <div v-if="isEditingUser" class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Activo</label>
                <AsyncSelect
                  v-model="userForm.ESACTIVO"
                  :options="SI_NO_BOOL_OPTIONS"
                  :searchable="false"
                  placeholder="Activo"
                />
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Bloqueado</label>
                <AsyncSelect
                  v-model="userForm.BLOQUEADO"
                  :options="SI_NO_BOOL_OPTIONS"
                  :searchable="false"
                  placeholder="Bloqueado"
                />
              </div>
            </div>
            <div v-if="userModalError" class="text-xs text-red-500 font-semibold bg-red-50 p-2 rounded">{{ userModalError }}</div>
            <div class="flex justify-end space-x-3 pt-4 border-t">
              <button data-no-lock type="button" @click="showUserModal = false" class="px-4 py-2 border rounded-lg text-sm hover:bg-slate-50">Cancelar</button>
              <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-semibold hover:bg-indigo-700">Guardar</button>
            </div>
          </form>
        </div>
      </AppModalShell>

      <!-- ══ MODAL: EXCEPCIONES USUARIO ═════════════════════════════════════════ -->
      <AppModalShell :open="showPermissionsModal" @close="showPermissionsModal = false">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-xl max-w-2xl w-full mx-auto overflow-hidden border border-slate-200 dark:border-slate-700">
          <div class="px-6 py-4 bg-slate-50 dark:bg-slate-700/50 border-b border-slate-200 flex justify-between items-center">
            <h3 class="text-base font-bold text-slate-950 dark:text-white">Excepciones de Permisos: {{ selectedUser?.USUARIO }}</h3>
            <button @click="showPermissionsModal = false" class="text-slate-400 hover:text-slate-600 font-bold text-lg" aria-label="Cerrar"><AppIcon name="x" size="md" /></button>
          </div>
          <form v-submit-lock="savePermissions" class="p-6 space-y-4">
            <p class="text-xs text-slate-500">Seleccione permisos directos adicionales para este usuario (complementan los del rol).</p>
            <div class="grid grid-cols-2 gap-3 max-h-[45vh] overflow-y-auto p-1">
              <label v-for="perm in permisosCatalogo" :key="perm.ID_PERMISO" class="flex items-start space-x-3 text-sm text-slate-700 dark:text-slate-300 cursor-pointer p-2 rounded hover:bg-slate-50 dark:hover:bg-slate-700/30">
                <input type="checkbox" :value="perm.ID_PERMISO" v-model="selectedUserPermissions" class="rounded text-indigo-600 focus:ring-indigo-500 mt-0.5" />
                <div>
                  <span class="font-mono font-bold text-xs text-indigo-600 block">{{ perm.CODIGO_PERMISO }}</span>
                  <span class="text-xs text-slate-400 block">{{ perm.NOMBRE_PERMISO }}</span>
                </div>
              </label>
            </div>
            <div class="flex justify-end space-x-3 pt-4 border-t">
              <button data-no-lock type="button" @click="showPermissionsModal = false" class="px-4 py-2 border rounded-lg text-sm hover:bg-slate-50">Cancelar</button>
              <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-semibold hover:bg-indigo-700">Guardar Cambios</button>
            </div>
          </form>
        </div>
      </AppModalShell>

      <!-- ══ MODAL: ROL CRUD ════════════════════════════════════════════════════ -->
      <AppModalShell :open="showRolModal" @close="showRolModal = false">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-xl max-w-md w-full mx-auto overflow-hidden border border-slate-200 dark:border-slate-700">
          <div class="px-6 py-4 bg-slate-50 dark:bg-slate-700/50 border-b border-slate-200 flex justify-between items-center">
            <h3 class="text-base font-bold text-slate-950 dark:text-white">{{ isEditingRol ? 'Editar Rol' : 'Nuevo Rol' }}</h3>
            <button @click="showRolModal = false" class="text-slate-400 hover:text-slate-600 font-bold text-lg" aria-label="Cerrar"><AppIcon name="x" size="md" /></button>
          </div>
          <form v-submit-lock="saveRol" class="p-6 space-y-4">
            <div>
              <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Nombre del Rol *</label>
              <input v-model="rolForm.NOMBREROL" type="text" required class="w-full px-3 py-2 border rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500" />
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Descripción</label>
              <textarea v-model="rolForm.DESCRIPCION" rows="2" class="w-full px-3 py-2 border rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500"></textarea>
            </div>
            <div v-if="isEditingRol">
              <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Estado</label>
              <AsyncSelect
                v-model="rolForm.ESACTIVO"
                :options="ACTIVO_BOOL_OPTIONS"
                :searchable="false"
                placeholder="Estado"
              />
            </div>
            <div v-if="rolModalError" class="text-xs text-red-500 font-semibold bg-red-50 p-2 rounded">{{ rolModalError }}</div>
            <div class="flex justify-end space-x-3 pt-4 border-t">
              <button data-no-lock type="button" @click="showRolModal = false" class="px-4 py-2 border rounded-lg text-sm hover:bg-slate-50">Cancelar</button>
              <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-semibold hover:bg-indigo-700">Guardar</button>
            </div>
          </form>
        </div>
      </AppModalShell>

      <!-- ══ MODAL: PERMISOS DEL ROL ════════════════════════════════════════════ -->
      <AppModalShell :open="showRolPermisosModal" @close="showRolPermisosModal = false">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-xl max-w-2xl w-full mx-auto overflow-hidden border border-slate-200 dark:border-slate-700">
          <div class="px-6 py-4 bg-slate-50 dark:bg-slate-700/50 border-b border-slate-200 flex justify-between items-center">
            <h3 class="text-base font-bold text-slate-950 dark:text-white">Permisos del Rol: {{ selectedRol?.NOMBREROL }}</h3>
            <button @click="showRolPermisosModal = false" class="text-slate-400 hover:text-slate-600 font-bold text-lg" aria-label="Cerrar"><AppIcon name="x" size="md" /></button>
          </div>
          <form v-submit-lock="saveRolPermisos" class="p-6 space-y-4">
            <div class="flex justify-between items-center">
              <p class="text-xs text-slate-500">Marque todos los permisos que tendrá este rol.</p>
              <div class="space-x-2">
                <button type="button" @click="selectAllRolPermisos" class="text-xs text-indigo-600 hover:underline font-semibold">Seleccionar todo</button>
                <span class="text-slate-300">|</span>
                <button type="button" @click="clearAllRolPermisos" class="text-xs text-rose-600 hover:underline font-semibold">Limpiar</button>
              </div>
            </div>
            <div class="grid grid-cols-2 gap-3 max-h-[45vh] overflow-y-auto p-1">
              <label v-for="perm in permisosCatalogo" :key="perm.ID_PERMISO" class="flex items-start space-x-3 text-sm text-slate-700 dark:text-slate-300 cursor-pointer p-2 rounded hover:bg-slate-50 dark:hover:bg-slate-700/30">
                <input type="checkbox" :value="perm.ID_PERMISO" v-model="selectedRolPermissions" class="rounded text-indigo-600 focus:ring-indigo-500 mt-0.5" />
                <div>
                  <span class="font-mono font-bold text-xs text-indigo-600 block">{{ perm.CODIGO_PERMISO }}</span>
                  <span class="text-xs text-slate-400 block">{{ perm.NOMBRE_PERMISO }}</span>
                </div>
              </label>
            </div>
            <div class="flex justify-end space-x-3 pt-4 border-t">
              <button data-no-lock type="button" @click="showRolPermisosModal = false" class="px-4 py-2 border rounded-lg text-sm hover:bg-slate-50">Cancelar</button>
              <button type="submit" class="px-4 py-2 bg-violet-600 text-white rounded-lg text-sm font-semibold hover:bg-violet-700">Guardar Permisos</button>
            </div>
          </form>
        </div>
      </AppModalShell>
    </div>
  </DashboardLayout>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import DashboardLayout from '../Dashboard.vue';
import SkeletonTable from '../../components/SkeletonTable.vue';
import AppModalShell from '../../components/AppModalShell.vue';
import PaginationBar from '../../components/PaginationBar.vue';
import { usePaginatedList } from '../../composables/usePaginatedList';
import api from '../../services/api';
import { dialog } from '../../composables/useDialog';
import { useToast } from '../../composables/useToast';
import { getApiErrorMessage } from '../../utils/apiError';
import { SI_NO_BOOL_OPTIONS, ACTIVO_BOOL_OPTIONS } from '../../utils/staticSelectOptions';

const toast = useToast();

const tabs = computed(() => {
  const user = JSON.parse(localStorage.getItem('user') || '{}');
  const perms = Array.isArray(user.permissions) ? user.permissions : [];
  const items = [
    { key: 'usuarios', label: 'Usuarios' },
    { key: 'roles', label: 'Roles' },
    { key: 'permisos', label: 'Catálogo de Permisos' },
  ];
  if (perms.includes('ERROR_JOURNAL_VIEW')) {
    items.push({ key: 'errores', label: 'Errores del sistema' });
  }
  if (perms.includes('SEGURIDAD_VIEW')) {
    items.push({ key: 'auditoria', label: 'Auditoría' });
  }
  return items;
});

const activeTab = ref('usuarios');
const permisos  = ref([]);

const permisosCatalogo = computed(() =>
  (Array.isArray(permisos.value) ? permisos.value : []).filter((p) => p && p.ID_PERMISO != null)
);
const rolesCatalog = ref([]);
const loadingPermisos = ref(false);
const errorFiles = ref([]);
const errorEntries = ref([]);
const selectedErrorFile = ref('');
const loadingErrores = ref(false);
const loadingErrorDetail = ref(false);

const {
  items: usuarios,
  loading: loadingUsuarios,
  page: usuariosPage,
  perPage: usuariosPerPage,
  total: usuariosTotal,
  lastPage: usuariosLastPage,
  fetch: loadUsuarios,
  setPage: setUsuariosPage,
  setPerPage: setUsuariosPerPage,
} = usePaginatedList('/usuarios', { perPage: 25 });

const {
  items: roles,
  loading: loadingRoles,
  page: rolesPage,
  perPage: rolesPerPage,
  total: rolesTotal,
  lastPage: rolesLastPage,
  fetch: loadRoles,
  setPage: setRolesPage,
  setPerPage: setRolesPerPage,
} = usePaginatedList('/roles', { perPage: 25 });

const {
  items: auditoria,
  loading: loadingAuditoria,
  page: auditoriaPage,
  perPage: auditoriaPerPage,
  total: auditoriaTotal,
  lastPage: auditoriaLastPage,
  fetch: loadAuditoria,
  setPage: setAuditoriaPage,
  setPerPage: setAuditoriaPerPage,
} = usePaginatedList('/auditoria', { perPage: 25 });

const auditoriaTablas = ref([]);
const auditoriaFiltros = ref({ TABLA: '', ACCION: '', fecha_inicio: '', fecha_fin: '' });
const showAuditoriaDetalle = ref(false);
const auditoriaSeleccionada = ref(null);

const loading = computed(() => {
  if (activeTab.value === 'usuarios') return loadingUsuarios.value;
  if (activeTab.value === 'roles') return loadingRoles.value;
  if (activeTab.value === 'errores') return loadingErrores.value;
  if (activeTab.value === 'auditoria') return loadingAuditoria.value;
  return loadingPermisos.value;
});

// ── Usuario ──────────────────────────────────────────────────────────────────
const showUserModal  = ref(false);
const isEditingUser  = ref(false);
const userModalError = ref('');
const userForm = ref({ ID_USUARIO: null, USUARIO: '', EMAIL: '', CONTRASENA: '', ID_EMPLEADO: null, ROLES: [], ESACTIVO: true, BLOQUEADO: false });

const showPermissionsModal    = ref(false);
const selectedUser            = ref(null);
const selectedUserPermissions = ref([]);

// ── Rol ───────────────────────────────────────────────────────────────────────
const showRolModal   = ref(false);
const isEditingRol   = ref(false);
const rolModalError  = ref('');
const rolForm = ref({ ID_ROL: null, NOMBREROL: '', DESCRIPCION: '', ESACTIVO: true });

const showRolPermisosModal  = ref(false);
const selectedRol           = ref(null);
const selectedRolPermissions= ref([]);

// ── Load Data ────────────────────────────────────────────────────────────────
const loadPermisos = async () => {
  loadingPermisos.value = true;
  try {
    const res = await api.get('/permisos-list');
    const payload = res.data;
    permisos.value = Array.isArray(payload) ? payload : (payload?.data ?? []);
  } catch (err) {
    permisos.value = [];
    toast.error('Error al cargar permisos', getApiErrorMessage(err));
  } finally {
    loadingPermisos.value = false;
  }
};

const loadErrorFiles = async () => {
  loadingErrores.value = true;
  try {
    const res = await api.get('/error-journal');
    errorFiles.value = res.data.files ?? [];
    if (!selectedErrorFile.value && errorFiles.value.length) {
      await loadErrorJournal(errorFiles.value[errorFiles.value.length - 1].name);
    }
  } catch (err) {
    toast.error('Error al cargar bitácora', getApiErrorMessage(err));
  } finally {
    loadingErrores.value = false;
  }
};

const loadErrorJournal = async (filename) => {
  selectedErrorFile.value = filename;
  loadingErrorDetail.value = true;
  try {
    const res = await api.get(`/error-journal/${filename}`);
    errorEntries.value = res.data.entries ?? [];
  } catch (err) {
    errorEntries.value = [];
    toast.error('Error al leer archivo', getApiErrorMessage(err));
  } finally {
    loadingErrorDetail.value = false;
  }
};

const loadRolesCatalog = async () => {
  try {
    const res = await api.get('/roles', { params: { per_page: 200 } });
    const payload = res.data;
    rolesCatalog.value = (Array.isArray(payload) ? payload : (payload?.data ?? [])).filter((r) => r && r.ID_ROL != null);
  } catch (err) {
    rolesCatalog.value = [];
    console.error(err);
  }
};

const loadAuditoriaTablas = async () => {
  try {
    const res = await api.get('/auditoria/tablas');
    auditoriaTablas.value = Array.isArray(res.data) ? res.data : [];
  } catch (err) {
    auditoriaTablas.value = [];
  }
};

const applyAuditoriaFiltros = () => {
  const filtros = {};
  if (auditoriaFiltros.value.TABLA) filtros.TABLA = auditoriaFiltros.value.TABLA;
  if (auditoriaFiltros.value.ACCION) filtros.ACCION = auditoriaFiltros.value.ACCION;
  if (auditoriaFiltros.value.fecha_inicio) filtros.fecha_inicio = auditoriaFiltros.value.fecha_inicio;
  if (auditoriaFiltros.value.fecha_fin) filtros.fecha_fin = auditoriaFiltros.value.fecha_fin;
  setAuditoriaPage(1);
  loadAuditoria(filtros);
};

const clearAuditoriaFiltros = () => {
  auditoriaFiltros.value = { TABLA: '', ACCION: '', fecha_inicio: '', fecha_fin: '' };
  loadAuditoria();
};

const viewAuditoriaDetalle = (a) => {
  auditoriaSeleccionada.value = a;
  showAuditoriaDetalle.value = true;
};

const accionLabel = (accion) => ({ create: 'Creación', update: 'Actualización', delete: 'Eliminación' }[accion] || accion || '—');

const accionBadgeClass = (accion) => ({
  create: 'bg-emerald-50 text-emerald-700',
  update: 'bg-amber-50 text-amber-700',
  delete: 'bg-rose-50 text-rose-700',
}[accion] || 'bg-slate-100 text-slate-700');

const formatFecha = (fecha) => (fecha ? new Date(fecha).toLocaleString('es-SV') : '—');

const formatJson = (json) => {
  if (!json) return '—';
  try {
    return JSON.stringify(typeof json === 'string' ? JSON.parse(json) : json, null, 2);
  } catch {
    return String(json);
  }
};

const loadData = async () => {
  await Promise.all([loadUsuarios(), loadRoles(), loadPermisos(), loadRolesCatalog()]);
};

onMounted(loadData);

watch(activeTab, (tab) => {
  if (tab === 'errores') loadErrorFiles();
  if (tab === 'auditoria' && !auditoria.value.length) {
    loadAuditoria();
    loadAuditoriaTablas();
  }
});

// ── Usuario handlers ──────────────────────────────────────────────────────────
const openCreateUserModal = () => {
  isEditingUser.value = false;
  userModalError.value = '';
  userForm.value = { ID_USUARIO: null, USUARIO: '', EMAIL: '', CONTRASENA: '', ID_EMPLEADO: null, ROLES: [], ESACTIVO: true, BLOQUEADO: false };
  showUserModal.value = true;
};

const editUsuario = (u) => {
  isEditingUser.value = true;
  userModalError.value = '';
  userForm.value = { ID_USUARIO: u.ID_USUARIO, USUARIO: u.USUARIO, EMAIL: u.EMAIL, CONTRASENA: '', ID_EMPLEADO: u.ID_EMPLEADO, ROLES: normalizeIdList(u.roles), ESACTIVO: !!u.ESACTIVO, BLOQUEADO: !!u.BLOQUEADO };
  showUserModal.value = true;
};

const saveUser = async () => {
  try {
    if (isEditingUser.value) {
      await api.put(`/usuarios/${userForm.value.ID_USUARIO}`, userForm.value);
    } else {
      await api.post('/usuarios', userForm.value);
    }
    showUserModal.value = false;
    loadData();
  } catch (err) {
    userModalError.value = err.response?.data?.errors
      ? Object.values(err.response.data.errors).flat().join(' ')
      : 'Error al guardar el usuario.';
  }
};

const normalizeIdList = (value) => {
  if (Array.isArray(value)) return value.filter((id) => id != null);
  if (value && typeof value === 'object') return Object.values(value).filter((id) => id != null);
  return [];
};

const openPermissionsModal = (user) => {
  selectedUser.value = user;
  selectedUserPermissions.value = normalizeIdList(user.permissions);
  showPermissionsModal.value = true;
};

const savePermissions = async () => {
  try {
    await api.put(`/usuarios/${selectedUser.value.ID_USUARIO}/permisos`, { permisos: selectedUserPermissions.value });
    showPermissionsModal.value = false;
    loadData();
  } catch (err) {
    await dialog.alert({ title: 'Error', message: 'No se pudieron guardar las excepciones.', variant: 'danger' });
  }
};

// ── Rol handlers ──────────────────────────────────────────────────────────────
const openCreateRolModal = () => {
  isEditingRol.value = false;
  rolModalError.value = '';
  rolForm.value = { ID_ROL: null, NOMBREROL: '', DESCRIPCION: '', ESACTIVO: true };
  showRolModal.value = true;
};

const editRol = (r) => {
  isEditingRol.value = true;
  rolModalError.value = '';
  rolForm.value = { ...r };
  showRolModal.value = true;
};

const saveRol = async () => {
  try {
    if (isEditingRol.value) {
      await api.put(`/roles/${rolForm.value.ID_ROL}`, rolForm.value);
    } else {
      await api.post('/roles', rolForm.value);
    }
    showRolModal.value = false;
    loadData();
  } catch (err) {
    rolModalError.value = err.response?.data?.errors
      ? Object.values(err.response.data.errors).flat().join(' ')
      : 'Error al guardar el rol.';
  }
};

const inactivateRol = async (r) => {
  if (!await dialog.confirm({
    title: 'Inactivar rol',
    message: `¿Inactivar el rol "${r.NOMBREROL}"?`,
    variant: 'warning',
    confirmText: 'Sí, inactivar',
  })) return;
  await api.delete(`/roles/${r.ID_ROL}`);
  loadData();
};

const openRolPermisosModal = async (r) => {
  selectedRol.value = r;
  try {
    const res = await api.get(`/roles/${r.ID_ROL}/permisos`);
    selectedRolPermissions.value = normalizeIdList(res.data);
  } catch {
    selectedRolPermissions.value = normalizeIdList(r.permisos);
  }
  showRolPermisosModal.value = true;
};

const saveRolPermisos = async () => {
  try {
    await api.put(`/roles/${selectedRol.value.ID_ROL}/permisos`, { permisos: selectedRolPermissions.value });
    showRolPermisosModal.value = false;
    loadData();
  } catch (err) {
    await dialog.alert({ title: 'Error', message: 'No se pudieron guardar los permisos del rol.', variant: 'danger' });
  }
};

const selectAllRolPermisos = () => {
  selectedRolPermissions.value = permisosCatalogo.value.map((p) => p.ID_PERMISO);
};

const clearAllRolPermisos = () => {
  selectedRolPermissions.value = [];
};
</script>
