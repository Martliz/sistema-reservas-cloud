// app.js — Punto de entrada del backend monolítico
const express  = require("express");
const cors     = require("cors");
const config   = require("./config");
const { migrate } = require("./db/migrate");
const { errorHandler } = require("./middleware/errorHandler");

const authRouter     = require("./routes/auth");
const reservasRouter = require("./routes/reservas");
const recursosRouter = require("./routes/recursos");

const app = express();

app.use(cors({ origin: config.CORS_ORIGIN }));
app.use(express.json());

// Rutas agrupadas por módulo
app.use("/auth",     authRouter);
app.use("/reservas", reservasRouter);
app.use("/recursos", recursosRouter);

// Health check
app.get("/health", (req, res) =>
  res.json({ status: "ok", service: "reservas-api", version: "1.0.0" })
);

// Manejador de errores centralizado (debe ir último)
app.use(errorHandler);

// Iniciar servidor solo si no estamos en modo test
if (require.main === module) {
  migrate()
    .then(() => {
      app.listen(config.PORT, () =>
        console.log(`🚀 Reservas API corriendo en http://localhost:${config.PORT}`)
      );
    })
    .catch((err) => {
      console.error("Error al iniciar:", err.message);
      process.exit(1);
    });
}

module.exports = app;
