<?php
// recursos.php — Listado de recursos disponibles
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';

session_init();
redirigir_si_no_autenticado();

$db = getDB();

$recursos = $db->query("
    SELECT r.id, r.nombre, r.descripcion, r.capacidad,
           COUNT(rv.id) FILTER (WHERE rv.estado = 'CONFIRMADA' AND rv.fecha_fin > NOW()) AS reservas_activas
    FROM recursos r
    LEFT JOIN reservas rv ON rv.recurso_id = r.id
    WHERE r.activo = TRUE
    GROUP BY r.id
    ORDER BY r.nombre
")->fetchAll();

$pageTitle = 'Recursos — ' . APP_NAME;
include 'includes/header.php';
?>

<div class="page-header">
  <h1>🏢 Recursos disponibles</h1>
  <a href="/nueva-reserva.php" class="btn btn-primary">+ Reservar</a>
</div>

<div class="card-grid">
  <?php foreach ($recursos as $rc): ?>
    <div class="card">
      <div class="card-title"><?= htmlspecialchars($rc['nombre']) ?></div>
      <p style="color:var(--gray-400); font-size:.9rem; margin-bottom:1rem">
        <?= htmlspecialchars($rc['descripcion'] ?? 'Sin descripción') ?>
      </p>
      <div style="display:flex; gap:.5rem; flex-wrap:wrap; margin-bottom:1rem">
        <span class="badge badge-blue">👥 Capacidad: <?= $rc['capacidad'] ?></span>
        <?php if ((int)$rc['reservas_activas'] > 0): ?>
          <span class="badge badge-amber">⏳ <?= $rc['reservas_activas'] ?> reserva(s) activa(s)</span>
        <?php else: ?>
          <span class="badge badge-green">✅ Disponible ahora</span>
        <?php endif; ?>
      </div>
      <a href="/nueva-reserva.php?recurso_id=<?= $rc['id'] ?>" class="btn btn-outline btn-sm">
        Reservar este espacio
      </a>
    </div>
  <?php endforeach; ?>
</div>

<?php include 'includes/footer.php'; ?>
