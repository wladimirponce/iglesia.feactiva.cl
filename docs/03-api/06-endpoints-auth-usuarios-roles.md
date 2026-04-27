# FEACTIVA IGLESIA SAAS — API AUTH, USUARIOS Y ROLES

## Objetivo

Definir endpoints para autenticación, usuarios, roles y permisos.

## Endpoints Auth

```http
POST /api/v1/auth/login
POST /api/v1/auth/logout
POST /api/v1/auth/refresh
GET  /api/v1/auth/me
GET  /api/v1/auth/tenants
POST /api/v1/auth/select-tenant
Login
{
  "email": "admin@iglesia.cl",
  "password": "********"
}

Respuesta:

{
  "success": true,
  "data": {
    "token": "...",
    "user": {
      "id": 1,
      "name": "Administrador",
      "email": "admin@iglesia.cl"
    },
    "tenants": []
  }
}
Usuarios
GET    /api/v1/auth/usuarios
GET    /api/v1/auth/usuarios/{id}
POST   /api/v1/auth/usuarios
PATCH  /api/v1/auth/usuarios/{id}
DELETE /api/v1/auth/usuarios/{id}

Permisos:

auth.usuarios.ver
auth.usuarios.crear
auth.usuarios.editar
auth.usuarios.desactivar
Roles
GET    /api/v1/auth/roles
GET    /api/v1/auth/roles/{id}
POST   /api/v1/auth/roles
PATCH  /api/v1/auth/roles/{id}
DELETE /api/v1/auth/roles/{id}
POST   /api/v1/auth/roles/{id}/permisos

Permisos:

auth.roles.ver
auth.roles.crear
auth.roles.editar
auth.roles.eliminar
auth.permisos.asignar
Asignar rol a usuario
POST /api/v1/auth/usuarios/{id}/roles

Body:

{
  "role_id": 2
}
Reglas
El usuario puede tener roles distintos por tenant.
El tenant se obtiene desde sesión/token.
Nunca confiar en tenant_id enviado desde frontend.
Password siempre con password_hash.
Login debe regenerar sesión.
Logout invalida token o sesión.
Toda modificación de usuario/rol debe auditarse.
Auditoría
auth.login.success
auth.login.failed
auth.logout
auth.usuario.created
auth.usuario.updated
auth.usuario.disabled
auth.role.created
auth.role.updated
auth.permission.assigned