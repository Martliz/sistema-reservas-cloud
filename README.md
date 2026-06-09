# Sistema de Reservas en la Nube

**Evaluación Módulo 2 — Cloud Architecture Bootcamp**

---

## Descripción

Sistema web que permite a los usuarios reservar espacios y recursos de manera eficiente. Construido con arquitectura modular desplegada en cPanel, con frontend PHP, API REST en Node.js y base de datos PostgreSQL.

## Sistema en producción

🌐 http://g11.origenet.cl

---

## Stack tecnológico

| Componente | Tecnología |
|---|---|
| Frontend | PHP 8.3 (public_html de cPanel) |
| Backend API | Node.js 18 + Express |
| Base de datos | PostgreSQL 10.23 |
| Autenticación | JWT HS256 + bcrypt factor 10 |
| Pruebas | Jest 29 + Supertest 7 |
| Servidor | Apache + cPanel |

---

## Estructura del proyecto
sistema-reservas-cloud/
├── backend/
│   ├── package.json
│   └── src/
│       ├── app.js
│       ├── config/
│       ├── db/
│       ├── middleware/
│       └── routes/
├── frontend/
│   └── public_html/
│       ├── index.php
│       ├── login.php
│       ├── registro.php
│       ├── nueva-reserva.php
│       ├── reservas.php
│       ├── recursos.php
│       ├── logout.php
│       ├── includes/
│       └── assets/
├── database/
│   └── schema.sql
├── docs/
│   ├── Documentacion_Tecnica_Sistema_Reservas.docx
│   ├── Diagrama_Arquitectura_Sistema_Reservas.pptx
│   ├── Informe_Pruebas_Sistema_Reservas.docx
│   └── Presentacion_Final_Sistema_Reservas.pptx
├── MANUAL_DESPLIEGUE.md
└── README.md

---

## Funcionalidades

- Registro e inicio de sesión con JWT
- Dashboard con estadísticas de reservas
- Crear, consultar y cancelar reservas en tiempo real
- Verificación de disponibilidad antes de confirmar
- Catálogo de recursos con capacidad y estado
- Protección de rutas con autenticación

---

## Instrucciones de despliegue local

### Base de datos
1. Crear base de datos PostgreSQL
2. Ejecutar `database/schema.sql` en phpPgAdmin o psql

### Backend Node.js
```bash
cd backend
npm install
npm start
```

### Frontend PHP
Subir contenido de `frontend/public_html/` a un servidor con PHP y editar `includes/config.php` con los datos de conexión.

---

## Pruebas unitarias

```bash
cd backend
npm install
npm test
```

Resultado esperado: **13/13 tests passing** con Jest + Supertest.

---

## Variables de entorno (backend)
DB_HOST=localhost
DB_PORT=5432
DB_NAME=nombre_base_de_datos
DB_USER=usuario_db
DB_PASSWORD=contraseña_db
JWT_SECRET=clave-secreta-jwt
NODE_ENV=production
PORT=3000

---

## Documentación

Todos los documentos están en la carpeta `/docs`:

- **Documentacion_Tecnica**: arquitectura, tecnologías y guía de despliegue
- **Diagrama_Arquitectura**: diagrama de componentes y diagrama de clases
- **Informe_Pruebas**: resultados de las 13 pruebas unitarias
- **Presentacion_Final**: resumen ejecutivo del proyecto

---

## Autor Grupo 1

