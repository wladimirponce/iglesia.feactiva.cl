# FEACTIVA IGLESIA SAAS — INFRAESTRUCTURA Y DEPLOY

## 1. Objetivo

Definir la infraestructura mínima para desplegar FeActiva Iglesia SaaS de forma segura y mantenible.

---

# 2. Entornos

El sistema debe manejar al menos:

```text
local
staging
production
3. Variables de entorno

Nunca guardar credenciales en código.

Archivo sugerido:

.env

Variables mínimas:

APP_ENV=production
APP_DEBUG=false
APP_URL=https://feactiva.cl

DB_HOST=localhost
DB_NAME=feactiva
DB_USER=usuario
DB_PASS=********
DB_CHARSET=utf8mb4

JWT_SECRET=********
SESSION_SECURE=true

MAIL_HOST=
MAIL_USER=
MAIL_PASS=

WHATSAPP_API_URL=
WHATSAPP_API_TOKEN=

STORAGE_PATH=/storage
LOG_PATH=/logs
4. Seguridad producción

En producción:

APP_DEBUG=false
HTTPS obligatorio
Cookies seguras
Errores técnicos ocultos
Logs internos activos
Backups activos
5. Base de datos

Motor recomendado:

MySQL 8+
InnoDB
utf8mb4_unicode_ci

Reglas:

[ ] Migraciones versionadas
[ ] Seeds separados
[ ] Backups diarios
[ ] Usuario DB con permisos mínimos necesarios
[ ] No usar root en producción
6. Estructura deploy sugerida
/app
  /backend
  /frontend
  /database
  /docs
  /storage
  /logs
  .env
7. Permisos de carpetas
/storage → escritura controlada
/logs → escritura controlada
/docs → solo lectura
/database → solo lectura en producción
8. Logs

Registrar:

errores backend
errores de autenticación
acciones críticas
fallos de integración
errores de envío comunicación

No registrar:

contraseñas
tokens
datos pastorales sensibles completos
datos financieros sensibles completos
9. Backups

Mínimo:

Backup diario de base de datos
Backup semanal completo
Retención mínima 30 días
Prueba mensual de restauración
10. HTTPS

Todo entorno productivo debe usar HTTPS.

No permitir:

login por HTTP
tokens por HTTP
descarga de reportes por HTTP
11. Deploy básico

Flujo sugerido:

1. Subir código
2. Instalar dependencias
3. Configurar .env
4. Ejecutar migraciones
5. Ejecutar seeds base
6. Verificar permisos carpetas
7. Limpiar cache si aplica
8. Probar login
9. Probar dashboard
10. Revisar logs
12. Rollback

Cada deploy debe permitir volver atrás.

Reglas:

[ ] Respaldar base antes de migrar
[ ] Mantener versión anterior del código
[ ] No ejecutar migraciones destructivas sin respaldo
13. Integraciones externas

Toda integración debe manejar:

timeout
reintentos controlados
logs de fallo
tokens en .env
no bloquear sistema principal

Aplica a:

WhatsApp
Email
Pagos
Storage
IA
14. Monitoreo mínimo

Verificar:

disponibilidad web
errores 500
uso de disco
estado de base de datos
backups realizados
fallos de login repetidos
15. Checklist producción

Antes de lanzar:

[ ] APP_DEBUG=false
[ ] HTTPS activo
[ ] Base de datos respaldada
[ ] Migraciones ejecutadas
[ ] Seeds cargados
[ ] Usuario admin creado
[ ] Logs activos
[ ] Backups activos
[ ] Permisos de carpetas correctos
[ ] Login probado
[ ] Multi-tenant probado
[ ] Permisos probados
[ ] Reportes probados
16. Criterio de éxito

Deploy aprobado cuando:

[ ] Sistema inicia sin errores
[ ] Login funciona
[ ] Dashboard funciona
[ ] CRUD CRM funciona
[ ] Finanzas funciona
[ ] Permisos funcionan
[ ] Logs registran errores
[ ] Backups están configurados