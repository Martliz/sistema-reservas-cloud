// db/pool.js
// Pool de conexiones a PostgreSQL.
// Un único pool compartido por toda la aplicación (patrón Singleton).
const { Pool } = require("pg");
const config = require("../config");

const pool = new Pool(config.DB);

pool.on("error", (err) => {
  console.error("Error inesperado en el pool de PostgreSQL:", err.message);
});

/**
 * Ejecuta una query con parámetros.
 * @param {string} text  - SQL con placeholders ($1, $2...)
 * @param {Array}  params - Valores para los placeholders
 * @returns {Promise<pg.QueryResult>}
 */
const query = (text, params) => pool.query(text, params);

/**
 * Obtiene un cliente del pool para transacciones manuales.
 * Recordar llamar client.release() al terminar.
 */
const getClient = () => pool.connect();

module.exports = { query, getClient, pool };
