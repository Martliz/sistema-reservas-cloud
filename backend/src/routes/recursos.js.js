// routes/recursos.js
const express = require("express");
const { query } = require("../db/pool");
const { verificarToken } = require("../middleware/auth");

const router = express.Router();
router.use(verificarToken);

// GET /recursos — listar recursos activos
router.get("/", async (req, res, next) => {
  try {
    const resultado = await query(
      "SELECT id, nombre, descripcion, capacidad FROM recursos WHERE activo=TRUE ORDER BY nombre"
    );
    res.json({ recursos: resultado.rows });
  } catch (err) { next(err); }
});

// GET /recursos/:id/disponibilidad
router.get("/:id/disponibilidad", async (req, res, next) => {
  const { fecha_inicio, fecha_fin } = req.query;
  if (!fecha_inicio || !fecha_fin)
    return res.status(400).json({ error: "fecha_inicio y fecha_fin son requeridos" });

  try {
    const recurso = await query(
      "SELECT id, nombre FROM recursos WHERE id=$1 AND activo=TRUE", [req.params.id]
    );
    if (recurso.rows.length === 0)
      return res.status(404).json({ error: "Recurso no encontrado" });

    const solapado = await query(
      `SELECT COUNT(*) FROM reservas
       WHERE recurso_id=$1 AND estado='CONFIRMADA'
         AND fecha_inicio < $3::timestamptz AND fecha_fin > $2::timestamptz`,
      [req.params.id, fecha_inicio, fecha_fin]
    );

    res.json({
      disponible: parseInt(solapado.rows[0].count) === 0,
      recurso_id: parseInt(req.params.id),
      nombre: recurso.rows[0].nombre,
    });
  } catch (err) { next(err); }
});

module.exports = router;
