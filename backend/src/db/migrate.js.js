// db/migrate.js
// Crea las tablas si no existen al iniciar la aplicación.
// Equivalente al lifespan de FastAPI con Base.metadata.create_all.
const { query } = require("./pool");

const SQL_USUARIOS = `
  CREATE TABLE IF NOT EXISTS usuarios (
    id          SERIAL PRIMARY KEY,
    nombre      VARCHAR(100) NOT NULL,
    email       VARCHAR(150) NOT NULL UNIQUE,
    password    VARCHAR(255) NOT NULL,
    created_at  TIMESTAMPTZ  NOT NULL DEFAULT NOW()
  );
`;

const SQL_RECURSOS = `
  CREATE TABLE IF NOT EXISTS recursos (
    id          SERIAL PRIMARY KEY,
    nombre      VARCHAR(150) NOT NULL,
    descripcion VARCHAR(500),
    capacidad   INTEGER NOT NULL DEFAULT 1,
    activo      BOOLEAN NOT NULL DEFAULT TRUE,
    created_at  TIMESTAMPTZ NOT NULL DEFAULT NOW()
  );
`;

const SQL_RESERVAS = `
  CREATE TABLE IF NOT EXISTS reservas (
    id           SERIAL PRIMARY KEY,
    usuario_id   INTEGER NOT NULL REFERENCES usuarios(id),
    recurso_id   INTEGER NOT NULL REFERENCES recursos(id),
    fecha_inicio TIMESTAMPTZ NOT NULL,
    fecha_fin    TIMESTAMPTZ NOT NULL,
    estado       VARCHAR(20)  NOT NULL DEFAULT 'CONFIRMADA',
    notas        VARCHAR(500),
    created_at   TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    CONSTRAINT fechas_validas CHECK (fecha_fin > fecha_inicio)
  );
`;

// Índice para acelerar búsquedas de disponibilidad
const SQL_INDICES = `
  CREATE INDEX IF NOT EXISTS idx_reservas_recurso_fechas
    ON reservas (recurso_id, fecha_inicio, fecha_fin)
    WHERE estado = 'CONFIRMADA';
`;

// Datos de prueba: 3 recursos iniciales
const SQL_SEED_RECURSOS = `
  INSERT INTO recursos (nombre, descripcion, capacidad)
  SELECT 'Sala A', 'Sala de reuniones principal', 10
  WHERE NOT EXISTS (SELECT 1 FROM recursos WHERE nombre = 'Sala A');

  INSERT INTO recursos (nombre, descripcion, capacidad)
  SELECT 'Sala B', 'Sala de reuniones secundaria', 6
  WHERE NOT EXISTS (SELECT 1 FROM recursos WHERE nombre = 'Sala B');

  INSERT INTO recursos (nombre, descripcion, capacidad)
  SELECT 'Escritorio Compartido 1', 'Hot desk zona silenciosa', 1
  WHERE NOT EXISTS (SELECT 1 FROM recursos WHERE nombre = 'Escritorio Compartido 1');
`;

async function migrate() {
  console.log("🗄️  Ejecutando migraciones...");
  await query(SQL_USUARIOS);
  await query(SQL_RECURSOS);
  await query(SQL_RESERVAS);
  await query(SQL_INDICES);
  await query(SQL_SEED_RECURSOS);
  console.log("✅ Base de datos lista.");
}

module.exports = { migrate };
