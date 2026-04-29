# Guia de Despliegue en cPanel

Esta guia define el proceso de deploy para **FeActiva Iglesia SaaS** usando GitHub y la herramienta **Git Version Control** de cPanel.

## Regla Operativa

Codex puede preparar cambios, validar, hacer commit y hacer push al repositorio remoto.

El **pull en cPanel se hace siempre manualmente por el usuario** desde **Git Version Control**. Codex no debe asumir que puede ejecutar el pull en el hosting ni reemplazar archivos directamente en `public_html`.

## Requisitos Previos

- Acceso al cPanel del dominio, por ejemplo `iglesia.feactiva.cl`.
- Repositorio Git configurado en cPanel.
- Base de datos MySQL creada.
- Archivo `.env` creado manualmente en produccion.

## Configuracion Inicial

1. Iniciar sesion en cPanel.
2. Abrir **Git Version Control**.
3. Crear o administrar el repositorio del proyecto.
4. Verificar que el branch configurado sea el branch usado para deploy, normalmente `main`.
5. Verificar que la carpeta destino corresponda al subdominio correcto.

## Archivo `.env`

El archivo `.env` no se sube al repositorio. Debe crearse y mantenerse manualmente en cPanel.

1. Abrir **Administrador de Archivos**.
2. Entrar a la carpeta del proyecto.
3. Crear o editar `.env`.
4. Copiar valores desde `.env.example` y completar credenciales reales.

Variables criticas:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://iglesia.feactiva.cl

DB_HOST=localhost
DB_NAME=
DB_USER=
DB_PASS=

JWT_SECRET=
WHATSAPP_INTEGRATION_KEY=
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI=https://iglesia.feactiva.cl/api/v1/integrations/google/callback
GOOGLE_TOKEN_ENCRYPTION_KEY=
```

## Base de Datos

Las migraciones se aplican manualmente en phpMyAdmin o desde la herramienta SQL disponible en el hosting.

1. Abrir phpMyAdmin.
2. Seleccionar la base de datos del SaaS.
3. Ejecutar migraciones nuevas en orden.
4. Ejecutar seeds solo si corresponde al entorno.

No ejecutar seeds marcados como desarrollo en produccion salvo decision explicita.

## Proceso de Deploy

Cuando Codex termine cambios:

1. Codex valida el codigo localmente.
2. Codex hace commit.
3. Codex hace push a GitHub.
4. El usuario entra a cPanel.
5. El usuario abre **Git Version Control**.
6. El usuario selecciona el repositorio.
7. El usuario ejecuta manualmente **Update from Remote** o **Pull**.
8. El usuario aplica migraciones nuevas si las hay.
9. El usuario prueba endpoints o flujo real.

## Webhook FeActiva Actual

El webhook real vive en:

```text
https://feactiva.cl/whatsapp/webhook.php
```

Si los archivos del webhook pertenecen a otro repositorio o no estan conectados a Git en local, Codex solo puede modificar la copia local. El deploy a `public_html/whatsapp/` debe hacerse manualmente por cPanel, salvo que ese proyecto tenga un repositorio remoto configurado y se haya hecho push.

Archivos habituales del webhook:

```text
whatsapp/webhook.php
whatsapp/SaasClient.php
```

## Logs Temporales

Durante debug puede existir:

```text
public_html/whatsapp/whatsapp_debug.log
```

Ese log es temporal y debe eliminarse o desactivarse al terminar la investigacion.

## Checklist Despues del Pull

- Confirmar que `git` en cPanel quedo en el ultimo commit.
- Confirmar que `.env` sigue intacto.
- Confirmar que las migraciones nuevas se aplicaron.
- Probar endpoint critico.
- Revisar `error_log` y logs temporales.
