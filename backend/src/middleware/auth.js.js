// middleware/auth.js
// Middleware que verifica el JWT en el header Authorization.
// Se aplica a todas las rutas protegidas.
const jwt = require("jsonwebtoken");
const config = require("../config");

/**
 * Extrae y verifica el Bearer token.
 * Si es válido, adjunta el payload decodificado en req.usuario.
 * Si falla, responde 401 inmediatamente.
 */
function verificarToken(req, res, next) {
  const authHeader = req.headers["authorization"] || "";

  if (!authHeader.startsWith("Bearer ")) {
    return res.status(401).json({
      error: "Token requerido",
      detalle: "El header Authorization debe tener formato: Bearer <token>",
    });
  }

  const token = authHeader.slice(7).trim();

  try {
    const payload = jwt.verify(token, config.JWT_SECRET);
    req.usuario = payload; // { id, email, nombre, iat, exp }
    next();
  } catch (err) {
    const mensaje =
      err.name === "TokenExpiredError" ? "Token expirado" : "Token inválido";
    return res.status(401).json({ error: mensaje });
  }
}

module.exports = { verificarToken };
