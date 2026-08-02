import { createRouter, createWebHistory } from 'vue-router';
import Login from '../views/Login.vue';
import Dashboard from '../views/Dashboard.vue';
import TipoContratacionIndex from '../views/TipoContratacion/Index.vue';
import GeografiaIndex from '../views/Geografia/Index.vue';
import CatalogosMhIndex from '../views/CatalogosMh/Index.vue';
import CorporativoIndex from '../views/Corporativo/Index.vue';
import DeduccionesIndex from '../views/Deducciones/Index.vue';
import SeguridadIndex from '../views/Seguridad/Index.vue';
import PlanillaIndex from '../views/Planilla/Index.vue';
import EmpleadosIndex from '../views/Empleados/Index.vue';
import CatalogoRRHHIndex from '../views/CatalogoRRHH/Index.vue';
import ConceptosEmpleadoIndex from '../views/ConceptosEmpleado/Index.vue';
import HorariosIndex from '../views/Horarios/Index.vue';
import AsistenciaIndex from '../views/Asistencia/Index.vue';
import IncapacidadIndex from '../views/Incapacidad/Index.vue';
import LiquidacionesIndex from '../views/Liquidaciones/Index.vue';
import PeriodosIndex from '../views/Periodos/Index.vue';
import ParametrosAguinaldoIndex from '../views/ParametrosAguinaldo/Index.vue';
import ContratosIndex from '../views/Contratos/Index.vue';

const routes = [
    {
        path: '/login',
        name: 'Login',
        component: Login,
        meta: { guest: true }
    },
    {
        path: '/',
        name: 'Dashboard',
        component: Dashboard,
        meta: { requiresAuth: true }
    },
    {
        path: '/tipo-contratacion',
        name: 'TipoContratacion',
        component: TipoContratacionIndex,
        meta: { requiresAuth: true }
    },
    {
        path: '/empleados',
        name: 'Empleados',
        component: EmpleadosIndex,
        meta: { requiresAuth: true }
    },
    {
        path: '/contratos',
        name: 'Contratos',
        component: ContratosIndex,
        meta: { requiresAuth: true, tab: 'contratos' }
    },
    {
        path: '/contratos/plantillas',
        name: 'ContratosPlantillas',
        component: ContratosIndex,
        meta: { requiresAuth: true, tab: 'plantillas' }
    },
    {
        path: '/planilla',
        name: 'Planilla',
        component: PlanillaIndex,
        meta: { requiresAuth: true }
    },
    {
        path: '/geografia',
        name: 'Geografia',
        component: GeografiaIndex,
        meta: { requiresAuth: true }
    },
    {
        path: '/catalogos-mh',
        name: 'CatalogosMh',
        component: CatalogosMhIndex,
        meta: { requiresAuth: true }
    },
    {
        path: '/corporativo',
        name: 'Corporativo',
        component: CorporativoIndex,
        meta: { requiresAuth: true }
    },
    {
        path: '/deducciones',
        name: 'Deducciones',
        component: DeduccionesIndex,
        meta: { requiresAuth: true }
    },
    {
        path: '/conceptos-empleado',
        name: 'ConceptosEmpleado',
        component: ConceptosEmpleadoIndex,
        meta: { requiresAuth: true }
    },
    {
        path: '/horarios',
        name: 'Horarios',
        component: HorariosIndex,
        meta: { requiresAuth: true }
    },
    {
        path: '/asistencia',
        name: 'Asistencia',
        component: AsistenciaIndex,
        meta: { requiresAuth: true }
    },
    {
        path: '/incapacidades',
        name: 'Incapacidades',
        component: IncapacidadIndex,
        meta: { requiresAuth: true }
    },
    {
        path: '/liquidaciones',
        name: 'Liquidaciones',
        component: LiquidacionesIndex,
        meta: { requiresAuth: true }
    },
    {
        path: '/periodos',
        name: 'Periodos',
        component: PeriodosIndex,
        meta: { requiresAuth: true }
    },
    {
        path: '/parametros-aguinaldo',
        name: 'ParametrosAguinaldo',
        component: ParametrosAguinaldoIndex,
        meta: { requiresAuth: true }
    },
    {
        path: '/seguridad',
        name: 'Seguridad',
        component: SeguridadIndex,
        meta: { requiresAuth: true }
    },
    {
        path: '/catalogo-rrhh',
        name: 'CatalogoRRHH',
        component: CatalogoRRHHIndex,
        meta: { requiresAuth: true }
    }
];

const router = createRouter({
    history: createWebHistory(),
    routes
});

router.beforeEach((to, from, next) => {
    const token = localStorage.getItem('token');
    if (to.meta.requiresAuth && !token) {
        next('/login');
    } else if (to.meta.guest && token) {
        next('/');
    } else {
        next();
    }
});

export default router;
