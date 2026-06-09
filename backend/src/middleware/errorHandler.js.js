// middleware/errorHandler.js
// Manejador de errores centralizado para Express.
// Captura cualquier error que pase por next(err).

function errorHandler(err, req, res, next) {
  console.error(`[ERROR] ${req.method} ${req.path}:`, err.message);

  // Errores de validación de express-validator (pasados como objeto)
  if (err.type === "validation") {
    return res.status(422).json({ error: "Datos inválidos", detalle: err.errors });
  }

  // Error de constraint de PostgreSQL (ej: fechas_validas)
  if (err.code === "23514") {
    return res.status(400).json({
      error: "Fechas inválidas",
      detalle: "fecha_fin debe ser posterior a fecha_inicio",
    });
  }

  // Clave foránea inexistente
  if (err.code === "23503") {
    return res.status(404).json({
      error: "Recurso o usuario no encontrado",
    });
  }

  // Error genérico — no exponer detalles internos en producción
  const status = err.status || 500;
  const mensaje =
    process.env.NODE_ENV === "production"
      ? "Error interno del servidor"
      : err.message;

  res.status(status).json({ error: mensaje });
}

module.exports = { errorHandler };
