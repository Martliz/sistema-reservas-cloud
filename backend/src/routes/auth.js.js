// routes/auth.js
// Módulo de autenticación: registro, login y verificación de token.
const express = require("express");
const bcrypt = require("bcryptjs");
const jwt = require("jsonwebtoken");
const { body, validationResult } = require("express-validator");
const { query } = require("../db/pool");
const { verificarToken } = require("../middleware/auth");
const config = require("../config");

const router = express.Router();

// ── POST /auth/registro ────────────────────────────────────────────────────

router.post(
  "/registro",
  [
    body("nombre").trim().notEmpty().withMessage("El nombre es requerido"),
    body("email").isEmail().normalizeEmail().withMessage("Email inválido"),
    body("password")
      .isLength({ min: 6 })
      .withMessage("La contraseña debe tener al menos 6 caracteres"),
  ],
  async (req, res, next) => {
    const errores = validationResult(req);
    if (!errores.isEmpty()) {
      return res.status(422).json({ error: "Datos inválidos", detalle: errores.array() });
    }

    try {
      const { nombre, email, password } = req.body;

      // Verificar si el email ya existe
      const existe = await query("SELECT id FROM usuarios WHERE email = $1", [email]);
      if (existe.rows.length > 0) {
        return res.status(409).json({ error: "El email ya está registrado" });
      }

      const hash = await bcrypt.hash(password, 10);
      const resultado = await query(
        "INSERT INTO usuarios (nombre, email, password) VALUES ($1, $2, $3) RETURNING id, nombre, email, created_at",
        [nombre, email, hash]
      );

      res.status(201).json({ usuario: resultado.rows[0] });
    } catch (err) {
      next(err);
    }
  }
);

// ── POST /auth/login ───────────────────────────────────────────────────────

router.post(
  "/login",
  [
    body("email").isEmail().normalizeEmail().withMessage("Email inválido"),
    body("password").notEmpty().withMessage("La contraseña es requerida"),
  ],
  async (req, res, next) => {
    const errores = validationResult(req);
    if (!errores.isEmpty()) {
      return res.status(422).json({ error: "Datos inválidos", detalle: errores.array() });
    }

    try {
      const { email, password } = req.body;

      const resultado = await query(
        "SELECT id, nombre, email, password FROM usuarios WHERE email = $1",
        [email]
      );

      if (resultado.rows.length === 0) {
        return res.status(401).json({ error: "Credenciales incorrectas" });
      }

      const usuario = resultado.rows[0];
      const passwordValida = await bcrypt.compare(password, usuario.password);

      if (!passwordValida) {
        return res.status(401).json({ error: "Credenciales incorrectas" });
      }

      const token = jwt.sign(
        { id: usuario.id, email: usuario.email, nombre: usuario.nombre },
        config.JWT_SECRET,
        { expiresIn: config.JWT_EXPIRES_IN }
      );

      res.json({
        token,
        usuario: { id: usuario.id, nombre: usuario.nombre, email: usuario.email },
      });
    } catch (err) {
      next(err);
    }
  }
);

// ── GET /auth/me — perfil del usuario autenticado ─────────────────────────

router.get("/me", verificarToken, async (req, res, next) => {
  try {
    const resultado = await query(
      "SELECT id, nombre, email, created_at FROM usuarios WHERE id = $1",
      [req.usuario.id]
    );

    if (resultado.rows.length === 0) {
      return res.status(404).json({ error: "Usuario no encontrado" });
    }

    res.json({ usuario: resultado.rows[0] });
  } catch (err) {
    next(err);
  }
});

module.exports = router;
