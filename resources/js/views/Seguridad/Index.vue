<template>
  <DashboardLayout>
    <div class="space-y-6">
      <div class="flex justify-between items-center">
        <div>
          <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Mantenimiento de Seguridad</h1>
          <p class="text-sm text-slate-600 dark:text-slate-400">Administre los accesos, usuarios, roles y permisos.</p>
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
        <div v-else class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden shadow-sm">
          <table class="w-full text-left border-collapse">
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
                  <button @click="editUsuario(u)" class="text-indigo-600 font-semibold text-xs hover:underline">Editar</button>
                  <button @click="openPermissionsModal(u)" class="text-slate-600 font-semibold text-xs hover:underline">Excepciones</button>
                </td>
              </tr>
            </tbody>
          </table>
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
        <div v-else class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden shadow-sm">
          <table class="w-full text-left border-collapse">
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
                  <button @click="editRol(r)" class="text-indigo-600 font-semibold text-xs hover:underline">Editar</button>
                  <button @click="openRolPermisosModal(r)" class="text-violet-600 font-semibold text-xs hover:underline">Permisos</button>
                  <button @click="inactivateRol(r)" v-if="r.ESACTIVO" class="text-rose-600 font-semibold text-xs hover:underline">Inactivar</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- ══ TAB: PERMISOS ══════════════════════════════════════════════════════ -->
      <div v-if="activeTab === 'permisos'" class="space-y-4">
        <SkeletonTable v-if="loading" />
        <div v-else class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden shadow-sm">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="bg-slate-50 dark:bg-slate-700/50 text-slate-600 dark:text-slate-300 text-xs font-semibold uppercase border-b border-slate-200">
                <th class="px-6 py-4">ID</th>
                <th class="px-6 py-4">Código</th>
                <th class="px-6 py-4">Nombre</th>
                <th class="px-6 py-4">Descripción</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-700 text-sm">
              <tr v-for="p in permisos" :key="p.ID_PERMISO" class="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors">
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

      <!-- ══ MODAL: USUARIO CRUD ════════════════════════════════════════════════ -->
      <div v-if="showUserModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-xl max-w-md w-full overflow-hidden border border-slate-200 dark:border-slate-700">
          <div class="px-6 py-4 bg-slate-50 dark:bg-slate-700/50 border-b border-slate-200 flex justify-between items-center">
            <h3 class="text-base font-bold text-slate-950 dark:text-white">{{ isEditingUser ? 'Editar Usuario' : 'Nuevo Usuario' }}</h3>
            <button @click="showUserModal = false" class="text-slate-400 hover:text-slate-600 font-bold text-lg">✕</button>
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
                <label v-for="r in roles" :key="r.ID_ROL" class="flex items-center space-x-2 text-sm text-slate-700 dark:text-slate-300 cursor-pointer">
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
      </div>

      <!-- ══ MODAL: EXCEPCIONES USUARIO ═════════════════════════════════════════ -->
      <div v-if="showPermissionsModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-xl max-w-2xl w-full overflow-hidden border border-slate-200 dark:border-slate-700">
          <div class="px-6 py-4 bg-slate-50 dark:bg-slate-700/50 border-b border-slate-200 flex justify-between items-center">
            <h3 class="text-base font-bold text-slate-950 dark:text-white">Excepciones de Permisos: {{ selectedUser?.USUARIO }}</h3>
            <button @click="showPermissionsModal = false" class="text-slate-400 hover:text-slate-600 font-bold text-lg">✕</button>
          </div>
          <form v-submit-lock="savePermissions" class="p-6 space-y-4">
            <p class="text-xs text-slate-500">Seleccione permisos directos adicionales para este usuario (complementan los del rol).</p>
            <div class="grid grid-cols-2 gap-3 max-h-[45vh] overflow-y-auto p-1">
              <label v-for="perm in permisos" :key="perm.ID_PERMISO" class="flex items-start space-x-3 text-sm text-slate-700 dark:text-slate-300 cursor-pointer p-2 rounded hover:bg-slate-50 dark:hover:bg-slate-700/30">
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
      </div>

      <!-- ══ MODAL: ROL CRUD ════════════════════════════════════════════════════ -->
      <div v-if="showRolModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-xl max-w-md w-full overflow-hidden border border-slate-200 dark:border-slate-700">
          <div class="px-6 py-4 bg-slate-50 dark:bg-slate-700/50 border-b border-slate-200 flex justify-between items-center">
            <h3 class="text-base font-bold text-slate-950 dark:text-white">{{ isEditingRol ? 'Editar Rol' : 'Nuevo Rol' }}</h3>
            <button @click="showRolModal = false" class="text-slate-400 hover:text-slate-600 font-bold text-lg">✕</button>
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
      </div>

      <!-- ══ MODAL: PERMISOS DEL ROL ════════════════════════════════════════════ -->
      <div v-if="showRolPermisosModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-xl max-w-2xl w-full overflow-hidden border border-slate-200 dark:border-slate-700">
          <div class="px-6 py-4 bg-slate-50 dark:bg-slate-700/50 border-b border-slate-200 flex justify-between items-center">
            <h3 class="text-base font-bold text-slate-950 dark:text-white">Permisos del Rol: {{ selectedRol?.NOMBREROL }}</h3>
            <button @click="showRolPermisosModal = false" class="text-slate-400 hover:text-slate-600 font-bold text-lg">✕</button>
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
              <label v-for="perm in permisos" :key="perm.ID_PERMISO" class="flex items-start space-x-3 text-sm text-slate-700 dark:text-slate-300 cursor-pointer p-2 rounded hover:bg-slate-50 dark:hover:bg-slate-700/30">
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
      </div>
    </div>
  </DashboardLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import DashboardLayout from '../Dashboard.vue';
import SkeletonTable from '../../components/SkeletonTable.vue';
import api from '../../services/api';
import { SI_NO_BOOL_OPTIONS, ACTIVO_BOOL_OPTIONS } from '../../utils/staticSelectOptions';

const tabs = [
  { key: 'usuarios', label: 'Usuarios' },
  { key: 'roles',    label: 'Roles' },
  { key: 'permisos', label: 'Catálogo de Permisos' },
];

const activeTab = ref('usuarios');
const usuarios  = ref([]);
const roles     = ref([]);
const permisos  = ref([]);
const loading   = ref(false);

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
const loadData = async () => {
  loading.value = true;
  try {
    const [usrRes, rolRes, permRes] = await Promise.all([
      api.get('/usuarios'),
      api.get('/roles'),
      api.get('/permisos-list'),
    ]);
    usuarios.value  = usrRes.data;
    roles.value     = rolRes.data;
    permisos.value  = permRes.data;
  } catch (err) {
    console.error(err);
  } finally {
    loading.value = false;
  }
};

onMounted(loadData);

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
  userForm.value = { ID_USUARIO: u.ID_USUARIO, USUARIO: u.USUARIO, EMAIL: u.EMAIL, CONTRASENA: '', ID_EMPLEADO: u.ID_EMPLEADO, ROLES: [...(u.roles || [])], ESACTIVO: !!u.ESACTIVO, BLOQUEADO: !!u.BLOQUEADO };
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

const openPermissionsModal = (user) => {
  selectedUser.value = user;
  selectedUserPermissions.value = [...(user.permissions || [])];
  showPermissionsModal.value = true;
};

const savePermissions = async () => {
  try {
    await api.put(`/usuarios/${selectedUser.value.ID_USUARIO}/permisos`, { permisos: selectedUserPermissions.value });
    showPermissionsModal.value = false;
    loadData();
  } catch (err) {
    alert('Error al guardar excepciones.');
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
  if (confirm(`¿Inactivar rol "${r.NOMBREROL}"?`)) {
    await api.delete(`/roles/${r.ID_ROL}`);
    loadData();
  }
};

const openRolPermisosModal = async (r) => {
  selectedRol.value = r;
  try {
    const res = await api.get(`/roles/${r.ID_ROL}/permisos`);
    selectedRolPermissions.value = res.data;
  } catch {
    selectedRolPermissions.value = [...(r.permisos || [])];
  }
  showRolPermisosModal.value = true;
};

const saveRolPermisos = async () => {
  try {
    await api.put(`/roles/${selectedRol.value.ID_ROL}/permisos`, { permisos: selectedRolPermissions.value });
    showRolPermisosModal.value = false;
    loadData();
  } catch (err) {
    alert('Error al guardar permisos del rol.');
  }
};

const selectAllRolPermisos = () => {
  selectedRolPermissions.value = permisos.value.map(p => p.ID_PERMISO);
};

const clearAllRolPermisos = () => {
  selectedRolPermissions.value = [];
};
</script>
