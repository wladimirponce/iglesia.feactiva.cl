# FeActiva Iglesia SaaS

Proyecto nuevo e independiente para la plataforma administrativa multi-tenant de iglesias FeActiva.

## Alcance actual

- Backend PHP base sin dependencias externas.
- Configuracion por variables de entorno.
- Router HTTP con middlewares.
- Respuestas JSON con formato estandar.
- Autenticacion basica por token usando `auth_sessions`.
- API CRM inicial.
- API Finanzas inicial.
- Frontend administrativo minimo sin frameworks.
- Docker local para API, frontend y base de datos.

## Estructura

```text
backend/
  public/
    index.php
  config/
    env.php
    database.php
  core/
    Database.php
    Response.php
    Router.php
  routes/
    api.php
database/
  migrations/
    001_core_saas.sql
    002_crm_personas.sql
    003_finanzas_basicas.sql
  seeds/
    001_core_seed.sql
frontend/
  index.html
storage/
logs/
docker/
```

## Variables de entorno

Copiar `.env.example` a `.env` y completar valores locales. No usar credenciales reales en el repositorio.

## Ejecutar con Docker

Requisito: Docker Desktop abierto.

Desde la raiz del proyecto:

```bash
docker compose up -d --build
```

Abrir:

```text
Frontend: http://localhost:8080
Backend:  http://localhost:8000
MySQL:    localhost:3307
```

Credenciales de desarrollo:

```text
Email:    admin@demo.test
Password: Demo123456!
```

Ver logs:

```bash
docker compose logs -f api
docker compose logs -f frontend
docker compose logs -f db
```

Detener:

```bash
docker compose down
```

Reiniciar base desde cero, solo en desarrollo:

```bash
docker compose down -v
docker compose up -d --build
```

## Ejecutar sin Docker

Desde la raiz del proyecto:

```bash
php -S localhost:8000 -t backend/public
```

Probar:

```bash
curl http://localhost:8000/api/v1/health
```

Respuesta esperada:

```json
{
  "success": true,
  "data": {
    "status": "ok",
    "service": "feactiva-iglesia-saas",
    "environment": "local",
    "timestamp": "2026-04-26T00:00:00+00:00"
  },
  "meta": {},
  "message": null
}
```

## Base de datos

Docker carga automaticamente migraciones y seeds desde `docker/mysql/init/001_init_database.sql` cuando el volumen de base de datos esta vacio.

La base expuesta por Docker usa:

```text
Host: localhost
Port: 3307
Database: feactiva_iglesia_saas
User: feactiva
Password: feactiva
```

## Reglas vigentes

- No se conecta a ninguna base actual de FeActiva.
- No se guardan credenciales reales de produccion.
- Las credenciales deben venir de variables de entorno.
