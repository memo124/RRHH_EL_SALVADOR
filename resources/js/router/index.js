import { createRouter, createWebHistory } from 'vue-router';
import { cancelPendingRequests } from '../services/api';
import api from '../services/api';
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
import CalendarioIndex from '../views/Calendario/Index.vue';
import EncuestasIndex from '../views/Encuestas/Index.vue';
import FormulariosEmpleadoIndex from '../views/FormulariosEmpleado/Index.vue';
import FormulariosResponder from '../views/FormulariosEmpleado/Responder.vue';
import DocumentosEmpleadoIndex from '../views/DocumentosEmpleado/Index.vue';
import VacacionesPermisosIndex from '../views/VacacionesPermisos/Index.vue';
import CapacitacionesIndex from '../views/Capacitaciones/Index.vue';
import ReclutamientoIndex from '../views/Reclutamiento/Index.vue';
import EvaluacionesIndex from '../views/Evaluaciones/Index.vue';
import ActividadesEconomicasIndex from '../views/ActividadesEconomicas/Index.vue';
import CumplimientoIsss from '../views/Cumplimiento/Isss.vue';
import CumplimientoAfp from '../views/Cumplimiento/Afp.vue';
import CumplimientoInsaforp from '../views/Cumplimiento/Insaforp.vue';
import CumplimientoRenta from '../views/Cumplimiento/Renta.vue';
import CumplimientoIsssMovimientos from '../views/Cumplimiento/IsssMovimientos.vue';
import CumplimientoAguinaldo from '../views/Cumplimiento/Aguinaldo.vue';
import CumplimientoRetencion10 from '../views/Cumplimiento/Retencion10.vue';
import Setup from '../views/Setup.vue';
import PortalHome from '../views/Portal/Home.vue';
import PortalBoletas from '../views/Portal/Boletas.vue';
import PortalPermisos from '../views/Portal/Permisos.vue';
import PortalEncuestas from '../views/Portal/Encuestas.vue';
import PortalEvaluaciones from '../views/Portal/Evaluaciones.vue';
import PortalPerfil from '../views/Portal/Perfil.vue';

const routes = [
    {
        path: '/setup',
        name: 'Setup',
        component: Setup,
        meta: { guest: true, setup: true }
    },
    {
        path: '/login',
        name: 'Login',
        component: Login,
        meta: { guest: true }
    },
    {
        path: '/actualizar-datos/:token',
        name: 'ActualizarDatos',
        component: FormulariosResponder,
        meta: { guest: true, publicForm: true }
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
    },
    {
        path: '/calendario',
        name: 'Calendario',
        component: CalendarioIndex,
        meta: { requiresAuth: true }
    },
    {
        path: '/encuestas',
        name: 'Encuestas',
        component: EncuestasIndex,
        meta: { requiresAuth: true }
    },
    {
        path: '/formularios-empleado',
        name: 'FormulariosEmpleado',
        component: FormulariosEmpleadoIndex,
        meta: { requiresAuth: true }
    },
    {
        path: '/documentos-empleado',
        name: 'DocumentosEmpleado',
        component: DocumentosEmpleadoIndex,
        meta: { requiresAuth: true }
    },
    {
        path: '/vacaciones-permisos',
        name: 'VacacionesPermisos',
        component: VacacionesPermisosIndex,
        meta: { requiresAuth: true }
    },
    {
        path: '/capacitaciones',
        name: 'Capacitaciones',
        component: CapacitacionesIndex,
        meta: { requiresAuth: true }
    },
    {
        path: '/reclutamiento',
        name: 'Reclutamiento',
        component: ReclutamientoIndex,
        meta: { requiresAuth: true }
    },
    {
        path: '/evaluaciones',
        name: 'Evaluaciones',
        component: EvaluacionesIndex,
        meta: { requiresAuth: true }
    },
    {
        path: '/actividades-economicas',
        name: 'ActividadesEconomicas',
        component: ActividadesEconomicasIndex,
        meta: { requiresAuth: true }
    },
    {
        path: '/portal',
        name: 'PortalHome',
        component: PortalHome,
        meta: { requiresAuth: true, portal: true }
    },
    {
        path: '/portal/boletas',
        name: 'PortalBoletas',
        component: PortalBoletas,
        meta: { requiresAuth: true, portal: true }
    },
    {
        path: '/portal/permisos',
        name: 'PortalPermisos',
        component: PortalPermisos,
        meta: { requiresAuth: true, portal: true }
    },
    {
        path: '/portal/encuestas',
        name: 'PortalEncuestas',
        component: PortalEncuestas,
        meta: { requiresAuth: true, portal: true }
    },
    {
        path: '/portal/evaluaciones',
        name: 'PortalEvaluaciones',
        component: PortalEvaluaciones,
        meta: { requiresAuth: true, portal: true }
    },
    {
        path: '/portal/perfil',
        name: 'PortalPerfil',
        component: PortalPerfil,
        meta: { requiresAuth: true, portal: true }
    },
    {
        path: '/cumplimiento/isss',
        name: 'CumplimientoIsss',
        component: CumplimientoIsss,
        meta: { requiresAuth: true }
    },
    {
        path: '/cumplimiento/afp',
        name: 'CumplimientoAfp',
        component: CumplimientoAfp,
        meta: { requiresAuth: true }
    },
    {
        path: '/cumplimiento/insaforp',
        name: 'CumplimientoInsaforp',
        component: CumplimientoInsaforp,
        meta: { requiresAuth: true }
    },
    {
        path: '/cumplimiento/renta',
        name: 'CumplimientoRenta',
        component: CumplimientoRenta,
        meta: { requiresAuth: true }
    },
    {
        path: '/cumplimiento/isss-movimientos',
        name: 'CumplimientoIsssMovimientos',
        component: CumplimientoIsssMovimientos,
        meta: { requiresAuth: true }
    },
    {
        path: '/aguinaldo',
        name: 'CumplimientoAguinaldo',
        component: CumplimientoAguinaldo,
        meta: { requiresAuth: true }
    },
    {
        path: '/cumplimiento/retencion10',
        name: 'CumplimientoRetencion10',
        component: CumplimientoRetencion10,
        meta: { requiresAuth: true }
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes
});

router.beforeEach(async (to, from, next) => {
    if (from.name && to.path !== from.path) {
        cancelPendingRequests();
    }

    const token = localStorage.getItem('token');

    let setupRequired = false;
    if (!to.meta.publicForm) {
        try {
            const { data } = await api.get('/setup/status');
            setupRequired = !!data.setup_required;
        } catch {
            setupRequired = false;
        }
    }

    if (setupRequired && to.name !== 'Setup') {
        next('/setup');
        return;
    }

    let isEmployeePortalUser = false;
    if (token) {
        try {
            const user = JSON.parse(localStorage.getItem('user'));
            isEmployeePortalUser = !!user?.is_employee_portal;
        } catch {
            isEmployeePortalUser = false;
        }
    }

    if (to.meta.requiresAuth && !token) {
        next('/login');
    } else if (to.meta.guest && token && !to.meta.publicForm && !to.meta.setup) {
        next(isEmployeePortalUser ? '/portal' : '/');
    } else if (to.meta.requiresAuth && isEmployeePortalUser && !to.meta.portal) {
        next('/portal');
    } else {
        next();
    }
});

export default router;
