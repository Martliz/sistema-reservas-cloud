// config/index.js
// Centraliza toda la configuración desde variables de entorno.
// En cPanel se configuran en "Setup Node.js App" > Environment Variables.
require("dotenv").config();

module.exports = {
  // Servidor
  PORT: process.env.PORT || 3000,
  NODE_ENV: process.env.NODE_ENV || "development",

  // Base de datos PostgreSQL (valores de cPanel)
  DB: {
    host: process.env.DB_HOST || "localhost",
    port: parseInt(process.env.DB_PORT) || 5432,
    database: process.env.DB_NAME || "reservas_db",
    user: process.env.DB_USER || "reservas_user",
    password: process.env.DB_PASSWORD || "",
    ssl: process.env.DB_SSL === "true" ? { rejectUnauthorized: false } : false,
  },

  // JWT
  JWT_SECRET: process.env.JWT_SECRET || "cambia-esto-en-produccion",
  JWT_EXPIRES_IN: process.env.JWT_EXPIRES_IN || "8h",

  // CORS — dominio de tu hosting cPanel
  CORS_ORIGIN: process.env.CORS_ORIGIN || "*",
};
