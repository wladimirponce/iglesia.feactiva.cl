# 🚀 Guía de Despliegue en cPanel (Git Pull)

Esta guía detalla el proceso para desplegar y actualizar **FeActiva Iglesia SaaS** utilizando la herramienta "Git™ Version Control" de cPanel.

## 📋 Requisitos Previos
- Acceso al cPanel del dominio (p.ej., `iglesia.feactiva.cl`).
- Repositorio de Git configurado y con acceso (SSH Key configurada en cPanel si el repo es privado).
- Base de Datos MySQL creada en cPanel.

---

## 🛠️ Paso 1: Configuración Inicial de Git en cPanel

1. Inicia sesión en **cPanel**.
2. Ve a la sección **Archivos** > **Git™ Version Control**.
3. Haz clic en **Create**.
4. Configura el repositorio:
   - **Clone URL**: La URL SSH de tu repositorio (ej: `git@github.com:usuario/repo.git`).
   - **File Root**: La ruta donde se clonará. Se recomienda una carpeta fuera de `public_html` si es posible, o directamente en el root del subdominio.
   - **Repository Name**: `feactiva-saas`.
5. Haz clic en **Create**.

---

## ⚙️ Paso 2: Configuración del Archivo `.env`

Dado que el archivo `.env` no se sube al repositorio por seguridad, debes crearlo manualmente:

1. Ve al **Administrador de Archivos** en cPanel.
2. Entra en la carpeta donde clonaste el proyecto.
3. Crea un nuevo archivo llamado `.env`.
4. Copia el contenido de `.env.example` y actualiza los valores de producción:
   ```env
   DB_HOST=localhost
   DB_NAME=tu_usuario_dbname
   DB_USER=tu_usuario_dbuser
   DB_PASS=tu_password_seguro
   APP_ENV=production
   APP_URL=https://iglesia.feactiva.cl
   JWT_SECRET=un_token_muy_largo_y_aleatorio
   ```

---

## 🗄️ Paso 3: Base de Datos

1. En cPanel, ve a **Bases de Datos MySQL®**.
2. Crea la base de datos y el usuario (asegúrate de darle todos los privilegios).
3. Ve a **phpMyAdmin**.
4. Selecciona la base de datos recién creada.
5. Importa los archivos SQL que se encuentran en `database/migrations/` (en orden cronológico).

---

## 🌐 Paso 4: Configuración del Servidor Web (Apache)

Para que el dominio apunte correctamente a la aplicación, asegúrate de:

1. **Frontend**: Si el frontend es estático, el `DocumentRoot` del dominio debe apuntar a la carpeta `frontend/`.
2. **Backend**: El backend debe ser accesible (generalmente a través de un subdominio como `api.iglesia.feactiva.cl` apuntando a `backend/public/`).
3. **.htaccess**: Si usas una estructura combinada, asegúrate de tener un `.htaccess` en el root que redirija las peticiones según corresponda.

---

## 🔄 Paso 5: Proceso de Actualización (Pull)

Cada vez que quieras subir cambios nuevos:

1. Haz **Push** de tus cambios locales a la rama principal (`main` o `master`).
2. En cPanel, ve a **Git™ Version Control**.
3. Busca tu repositorio y haz clic en **Manage**.
4. Ve a la pestaña **Pull or Deploy**.
5. Haz clic en **Update from Remote**.
6. (Opcional) Si configuraste un archivo `deploy.sh` o `cpanel.yml`, los cambios se aplicarán automáticamente. Si no, verifica si hay cambios en la base de datos que debas aplicar manualmente en phpMyAdmin.

---

## 📂 Permisos de Carpetas
Asegúrate de que las siguientes carpetas tengan permisos de escritura (`755` o `775` según el servidor):
- `/backend/logs/`
- `/backend/storage/` (si existe)

---

> [!TIP]
> **Automatización**: Considera crear un archivo `.cpanel.yml` en la raíz para automatizar la copia de archivos tras cada pull.
