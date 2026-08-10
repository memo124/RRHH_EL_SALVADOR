# Mobile API consumer notes (optional scaffold)

Base URL: `{APP_URL}/api`

## Auth
```
POST /login
{ "usuario": "empleado@rrhh.sv", "contrasena": "Empleado123!" }
→ { token, user: { id_empleado, is_employee_portal, permissions, ... } }
```
Header: `Authorization: Bearer {token}`

## Portal endpoints
| Method | Path | Notes |
|--------|------|-------|
| GET | /portal/me | Profile summary |
| GET | /portal/boletas | Paginated (`page`, `per_page`) |
| GET/POST | /portal/permisos | List / create own leave requests |
| GET | /portal/encuestas | Pending surveys |
| POST | /portal/encuestas/{id}/responder | Submit answers |
| GET | /portal/evaluaciones | Own performance reviews |

OpenAPI UI: `/docs/api` · Spec: `/docs/api.json`

Suggested screens: Login → Home → Boletas → Permisos → Encuestas.
