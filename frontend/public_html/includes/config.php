<?php
// includes/config.php
// Configuración de conexión a PostgreSQL.
// En cPanel: edita estos valores con los datos de "Bases de datos PostgreSQL".

define('DB_HOST',     getenv('DB_HOST')     ?: 'localhost');
define('DB_PORT',     getenv('DB_PORT')     ?: '5432');
define('DB_NAME',     getenv('DB_NAME')     ?: 'reservas_db');
define('DB_USER',     getenv('DB_USER')     ?: 'reservas_user');
define('DB_PASSWORD', getenv('DB_PASSWORD') ?: '');

define('JWT_SECRET',  getenv('JWT_SECRET')  ?: 'cambia-esto-en-produccion');
define('APP_NAME',    'Sistema de Reservas');
define('APP_VERSION', '1.0.0');

// Zona horaria Chile
date_default_timezone_set('America/Santiago');
