// routes/reservas.js
const express = require("express");
const { body, validationResult } = require("express-validator");
const { query } = require("../db/pool");
const { verificarToken } = require("../middleware/auth");

const router = express.Router();
router.use(verificarToken); // todas las rutas de reservas requieren autenticación

// GET /reservas — listar reservas del usuario autenticado
router.get("/", async (req, res, next) => {
  try {
    const resultado = await query(
      `SELECT r.id, r.fecha_inicio, r.fecha_fin, r.estado, r.notas, r.created_at,
              rc.nombre AS recurso_nombre, rc.capacidad
       FROM reservas r
       JOIN recursos rc ON rc.id = r.recurso_id
       WHERE r.usuario_id = $1
       ORDER BY r.fecha_inicio DESC`,
      [req.usuario.id]
    );
    res.json({ reservas: resultado.rows });
  } catch (err) { next(err); }
});

// POST /reservas — crear reserva
router.post("/",
  [
    body("recurso_id").isInt({ gt: 0 }).withMessage("recurso_id inválido"),
    body("fecha_inicio").isISO8601().withMessage("fecha_inicio inválida"),
    body("fecha_fin").isISO8601().withMessage("fecha_fin inválida"),
  ],
  async (req, res, next) => {
    const errores = validationResult(req);
    if (!errores.isEmpty())
      return res.status(422).json({ error: "Datos inválidos", detalle: errores.array() });

    const { recurso_id, fecha_inicio, fecha_fin, notas } = req.body;

    if (new Date(fecha_fin) <= new Date(fecha_inicio))
      return res.status(400).json({ error: "fecha_fin debe ser posterior a fecha_inicio" });

    if (new Date(fecha_inicio) <= new Date())
      return res.status(400).json({ error: "fecha_inicio debe ser una fecha futura" });

    try {
      // Verificar recurso activo
      const recurso = await query("SELECT id FROM recursos WHERE id=$1 AND activo=TRUE", [recurso_id]);
      if (recurso.rows.length === 0)
        return res.status(404).json({ error: "Recurso no encontrado o inactivo" });

      // Verificar disponibilidad (sin solapamiento)
      const solapado = await query(
        `SELECT COUNT(*) FROM reservas
         WHERE recurso_id=$1 AND estado='CONFIRMADA'
           AND fecha_inicio < $3::timestamptz AND fecha_fin > $2::timestamptz`,
        [recurso_id, fecha_inicio, fecha_fin]
      );
      if (parseInt(solapado.rows[0].count) > 0)
        return res.status(409).json({ error: "Recurso no disponible en ese período" });

      const nueva = await query(
        `INSERT INTO reservas (usuario_id, recurso_id, fecha_inicio, fecha_fin, notas)
         VALUES ($1,$2,$3::timestamptz,$4::timestamptz,$5) RETURNING *`,
        [req.usuario.id, recurso_id, fecha_inicio, fecha_fin, notas || null]
      );
      res.status(201).json({ reserva: nueva.rows[0] });
    } catch (err) { next(err); }
  }
);

// DELETE /reservas/:id — cancelar reserva
router.delete("/:id", async (req, res, next) => {
  try {
    const resultado = await query(
      `UPDATE reservas SET estado='CANCELADA'
       WHERE id=$1 AND usuario_id=$2 AND estado='CONFIRMADA' RETURNING id`,
      [req.params.id, req.usuario.id]
    );
    if (resultado.rows.length === 0)
      return res.status(404).json({ error: "Reserva no encontrada o ya cancelada" });
    res.json({ mensaje: "Reserva cancelada", id: resultado.rows[0].id });
  } catch (err) { next(err); }
});

module.exports = router;
