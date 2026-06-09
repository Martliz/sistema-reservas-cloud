-- =============================================================
-- Sistema de Reservas en la Nube — Schema PostgreSQL
-- Ejecutar en cPanel > phpPgAdmin > SQL
-- =============================================================

-- Tabla de usuarios
CREATE TABLE IF NOT EXISTS usuarios (
    id          SERIAL PRIMARY KEY,
    nombre      VARCHAR(100) NOT NULL,
    email       VARCHAR(150) NOT NULL UNIQUE,
    password    VARCHAR(255) NOT NULL,
    created_at  TIMESTAMPTZ  NOT NULL DEFAULT NOW()
);

-- Tabla de recursos (espacios/salas)
CREATE TABLE IF NOT EXISTS recursos (
    id          SERIAL PRIMARY KEY,
    nombre      VARCHAR(150) NOT NULL,
    descripcion VARCHAR(500),
    capacidad   INTEGER      NOT NULL DEFAULT 1,
    activo      BOOLEAN      NOT NULL DEFAULT TRUE,
    created_at  TIMESTAMPTZ  NOT NULL DEFAULT NOW()
);

-- Tabla de reservas
CREATE TABLE IF NOT EXISTS reservas (
    id           SERIAL PRIMARY KEY,
    usuario_id   INTEGER     NOT NULL REFERENCES usuarios(id) ON DELETE CASCADE,
    recurso_id   INTEGER     NOT NULL REFERENCES recursos(id) ON DELETE CASCADE,
    fecha_inicio TIMESTAMPTZ NOT NULL,
    fecha_fin    TIMESTAMPTZ NOT NULL,
    estado       VARCHAR(20) NOT NULL DEFAULT 'CONFIRMADA'
                 CHECK (estado IN ('CONFIRMADA', 'CANCELADA', 'PENDIENTE')),
    notas        VARCHAR(500),
    created_at   TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    CONSTRAINT fechas_validas CHECK (fecha_fin > fecha_inicio)
);

-- Índice para búsquedas de disponibilidad (mejora el rendimiento)
CREATE INDEX IF NOT EXISTS idx_reservas_recurso_fechas
    ON reservas (recurso_id, fecha_inicio, fecha_fin)
    WHERE estado = 'CONFIRMADA';

-- Datos iniciales: 3 recursos de ejemplo
INSERT INTO recursos (nombre, descripcion, capacidad) VALUES
    ('Sala A',                 'Sala de reuniones principal, proyector incluido', 10),
    ('Sala B',                 'Sala de reuniones secundaria',                     6),
    ('Escritorio Compartido 1','Hot desk en zona silenciosa',                      1)
ON CONFLICT DO NOTHING;

-- =============================================================
-- Verificación (opcional)
-- SELECT * FROM recursos;
-- SELECT * FROM usuarios;
-- SELECT * FROM reservas;
-- =============================================================
