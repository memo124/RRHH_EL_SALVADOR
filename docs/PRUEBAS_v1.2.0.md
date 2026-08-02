# Plan de pruebas — v1.2.0

Fecha de referencia: 2026-08-02  
Entorno: Laravel 11 + Vue 3 + PostgreSQL  
Usuario demo: `admin@rrhh.sv` / `Admin123!`

---

## 1. Reportes de planilla y boletas

| # | Caso | Pasos | Resultado esperado |
|---|------|-------|-------------------|
| 1.1 | PDF planilla con grupos | Planilla → Imprimir planilla (PDF) | Encabezado sin bordes grises duplicados; grupos por área y departamento con subtotales |
| 1.2 | Excel planilla | Exportar Excel | Misma agrupación área/departamento que el PDF |
| 1.3 | Boletas PDF | Planilla calculada → Imprimir boletas | PDF directo sin toolbar HTML; sin encabezado duplicado |

---

## 2. Errores del sistema (API)

| # | Caso | Pasos | Resultado esperado |
|---|------|-------|-------------------|
| 2.1 | Error 500 JSON | Provocar error en endpoint API | JSON con `message` amigable y `reference`; sin stack trace |
| 2.2 | Bitácora | Seguridad → Errores del sistema | Lista de archivos rotados (máx. 3, cada 3 días) |
| 2.3 | Permiso | Usuario sin `ERROR_JOURNAL_VIEW` | 403 al consultar bitácora |
| 2.4 | Toast en listados | Error al cargar tabla paginada | Toast «Error al cargar» vía interceptor |

**Post-migración:** ejecutar `php artisan migrate` y re-login para permiso ID 33.

---

## 3. Seguridad y permisos

| # | Caso | Pasos | Resultado esperado |
|---|------|-------|-------------------|
| 3.1 | Listado permisos | Seguridad → Permisos | Sin error JS; catálogo completo usable en modales |
| 3.2 | Guardar rol/usuario | Crear o editar rol con permisos | Modal cierra; toast de éxito |

---

## 4. Incapacidades — subsidios ISSS

| # | Caso | Pasos | Resultado esperado |
|---|------|-------|-------------------|
| 4.1 | Pestaña subsidios | Incapacidades → Cobros ISSS | Carga sin error PostgreSQL GROUP BY |
| 4.2 | Error de red | Simular fallo API | Toast de error visible |

---

## 5. Frontend — cancelación y feedback

| # | Caso | Pasos | Resultado esperado |
|---|------|-------|-------------------|
| 5.1 | Cancelar al navegar | Abrir pantalla lenta → cambiar ruta | Peticiones anteriores «canceled» en Network; sin toasts de error |
| 5.2 | Tabs rápidos | Catálogos RRHH: cambiar pestañas seguidas | Loader y datos corresponden a la pestaña activa |
| 5.3 | Toast éxito | Crear/editar/eliminar registro | Toast verde con mensaje del backend o por defecto |
| 5.4 | Login | Iniciar sesión | Toast «Sesión iniciada» |

---

## 6. Recálculo ISR quincenal (seeder)

```bash
php artisan db:seed --class=DemoQuincenalJunioRecalculoSeeder
```

| # | Caso | Verificación |
|---|------|--------------|
| 6.1 | Planillas 20–25 | Ene–Jun 1ra quincena calculadas en orden |
| 6.2 | Empleado 402 junio | Renta ordinaria junio **$519.51** (con vacaciones mar en acumulado) |
| 6.3 | Planilla vacaciones 26 | Renta **$49.38** (ISR normal, sin ajuste semestral) |
| 6.4 | Consola seeder | Reporte manual vs sistema con ✓ |

Documentación: `database/seeders/EJERCICIO_QUINCENAL_JUNIO_2026.md`

---

## 7. Dashboard

| # | Caso | Pasos | Resultado esperado |
|---|------|-------|-------------------|
| 7.1 | KPIs | Inicio (permiso `SALARIAL_VIEW`) | 8 tarjetas con métricas |
| 7.2 | Gráficas | Revisar 5 charts | Render en claro/oscuro; datos coherentes con BD |
| 7.3 | Alertas | Panel lateral | Contratos por vencer, marcaciones, última planilla |
| 7.4 | Accesos rápidos | Clic en enlaces | Navegación a módulos |

**API:** `GET /api/dashboard/stats`

---

## 8. Regresión rápida

- [ ] Login / logout
- [ ] Listado empleados paginado
- [ ] Calcular planilla existente
- [ ] Cerrar planilla (si aplica)
- [ ] Cambio de tema claro/oscuro en dashboard

---

## Comandos útiles

```bash
php artisan migrate
php artisan db:seed --class=DemoQuincenalJunioRecalculoSeeder
npm run build
php artisan tinker --execute="echo json_encode(app(App\Services\DashboardService::class)->getStats());"
```
