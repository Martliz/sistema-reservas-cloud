# Manual de Despliegue — cPanel
## Sistema de Reservas en la Nube · Alkemy Módulo 2

---

## Requisitos previos

- Acceso a cPanel con las herramientas que tienes disponibles
- PostgreSQL habilitado (confirmado en tus capturas)
- PHP habilitado (nativo en cPanel)
- Setup Node.js App disponible (confirmado en tus capturas)

---

## Paso 1 — Crear la base de datos PostgreSQL

1. En cPanel ir a **Bases de datos > Bases de datos PostgreSQL**
2. Crear una base de datos nueva: `reservas_db`
3. Crear un usuario nuevo: `reservas_user` con una contraseña segura
4. Asignar el usuario a la base de datos con **todos los privilegios**
5. Ir a **phpPgAdmin**
6. Conectarse a `reservas_db`
7. Clic en **SQL** y pegar el contenido de `database/schema.sql`
8. Ejecutar — deberían crearse 3 tablas y 3 recursos de ejemplo

---

## Paso 2 — Subir el frontend PHP

1. En cPanel ir a **Archivos > Administrador de archivos**
2. Navegar a `public_html`
3. Subir **todo el contenido** de la carpeta `frontend/` directamente en `public_html`

La estructura en `public_html` debe quedar así:

```
public_html/
├── index.php
├── login.php
├── registro.php
├── reservas.php
├── nueva-reserva.php
├── recursos.php
├── logout.php
├── includes/
│   ├── config.php   ← editar con tus datos de DB
│   ├── db.php
│   ├── auth.php
│   ├── header.php
│   └── footer.php
└── assets/
    ├── css/main.css
    └── js/main.js
```

4. Editar `public_html/includes/config.php` con los datos reales:

```php
define('DB_HOST',     'localhost');
define('DB_PORT',     '5432');
define('DB_NAME',     'tuusuario_reservas_db');  // cPanel prefija con tu usuario
define('DB_USER',     'tuusuario_reservas_user');
define('DB_PASSWORD', 'tu-contraseña-segura');
define('JWT_SECRET',  'una-cadena-larga-y-aleatoria-aqui');
```

> ⚠️ En cPanel los nombres de BD y usuario se prefijan con tu nombre de cuenta.
> Ejemplo: si tu cuenta es `midominio`, la BD se llama `midominio_reservas_db`.

---

## Paso 3 — Desplegar el backend Node.js (API REST)

1. En cPanel ir a **Software > Setup Node.js App**
2. Clic en **Create Application**
3. Configurar:
   - **Node.js version:** 18.x o 20.x (la más reciente disponible)
   - **Application mode:** Production
   - **Application root:** `nodeapp/backend` (carpeta donde subirás el backend)
   - **Application URL:** `tudominio.com/api` o un subdominio `api.tudominio.com`
   - **Application startup file:** `src/app.js`

4. Guardar y copiar el comando que genera cPanel (algo como):
   ```
   source /home/tuusuario/nodevenv/nodeapp/backend/18/bin/activate
   ```

5. Subir el contenido de la carpeta `backend/` a `nodeapp/backend/` via Administrador de archivos

6. En Setup Node.js App, hacer clic en **Run NPM Install**

7. Configurar las **Environment Variables** en la misma pantalla:
   ```
   DB_HOST=localhost
   DB_PORT=5432
   DB_NAME=tuusuario_reservas_db
   DB_USER=tuusuario_reservas_user
   DB_PASSWORD=tu-contraseña
   JWT_SECRET=la-misma-cadena-que-en-config.php
   NODE_ENV=production
   PORT=3000
   ```

8. Clic en **Restart** para iniciar la aplicación

---

## Paso 4 — Verificar el despliegue

### Frontend PHP
Abrir en el navegador: `https://tudominio.com`
- Debería mostrar la página de login
- Registrar un usuario nuevo
- Crear una reserva de prueba

### Backend Node.js (API)
Probar desde el navegador o curl:
```
https://tudominio.com/api/health
```
Respuesta esperada: `{"status":"ok","service":"reservas-api"}`

---

## Paso 5 — Configurar SSL (recomendado)

1. En cPanel ir a **Seguridad > SSL/TLS Status**
2. Activar **AutoSSL** para tu dominio
3. Esperar 5 minutos y verificar que `https://` funciona

---

## Solución de problemas comunes

| Problema | Causa probable | Solución |
|---|---|---|
| Error 500 en PHP | Credenciales de DB incorrectas | Verificar `config.php` |
| "No se pudo conectar" | Nombre de DB con prefijo faltante | Agregar `tuusuario_` al nombre |
| Node.js no inicia | Puerto en uso | Cambiar `PORT` en env vars |
| Login falla siempre | `JWT_SECRET` diferente en PHP y Node | Usar el mismo valor en ambos |
| Sesión se pierde | PHP sin permisos de escritura | `chmod 755` en la carpeta `sessions` |

---

## Estructura del repositorio GitHub

```
sistema-reservas/
├── backend/          → API REST Node.js + Express
│   ├── src/
│   │   ├── app.js
│   │   ├── config/
│   │   ├── db/
│   │   ├── middleware/
│   │   └── routes/
│   ├── tests/
│   └── package.json
├── frontend/         → Aplicación PHP para public_html
│   ├── index.php
│   ├── includes/
│   └── assets/
├── database/
│   └── schema.sql    → Ejecutar en phpPgAdmin
├── docs/
│   └── diagrama-arquitectura.png
└── README.md         → Este archivo
```
