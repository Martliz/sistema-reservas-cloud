<?php
// index.php — Dashboard principal con banner estilo Work Café
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

session_init();
redirigir_si_no_autenticado();

$usuario = usuario_actual();
$db      = getDB();

$stmt = $db->prepare("
    SELECT
      COUNT(*) FILTER (WHERE estado = 'CONFIRMADA')                          AS activas,
      COUNT(*) FILTER (WHERE estado = 'CANCELADA')                           AS canceladas,
      COUNT(*) FILTER (WHERE estado = 'CONFIRMADA' AND fecha_inicio > NOW()) AS proximas
    FROM reservas WHERE usuario_id = ?
");
$stmt->execute([$usuario['id']]);
$stats = $stmt->fetch();

$stmt = $db->prepare("
    SELECT r.id, r.fecha_inicio, r.fecha_fin, r.estado, r.notas,
           rc.nombre AS recurso_nombre
    FROM reservas r
    JOIN recursos rc ON rc.id = r.recurso_id
    WHERE r.usuario_id = ? AND r.estado = 'CONFIRMADA' AND r.fecha_inicio > NOW()
    ORDER BY r.fecha_inicio ASC
    LIMIT 5
");
$stmt->execute([$usuario['id']]);
$proximas = $stmt->fetchAll();

$pageTitle = 'Dashboard — ' . APP_NAME;
include 'includes/header.php';
?>

<!-- Banner de bienvenida estilo Work Café -->
<div class="welcome-banner">
  <div>
    <h1>Bienvenido, <?= htmlspecialchars($usuario['nombre']) ?></h1>
    <p>Gestiona tus reservas de espacios y recursos en tiempo real.</p>
  </div>
  <a href="/nueva-reserva.php" class="btn btn-outline">+ Reservar ahora</a>
</div>

<!-- Stats -->
<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-number"><?= (int)$stats['activas'] ?></div>
    <div class="stat-label">Reservas activas</div>
  </div>
  <div class="stat-card">
    <div class="stat-number"><?= (int)$stats['proximas'] ?></div>
    <div class="stat-label">Próximas</div>
  </div>
  <div class="stat-card">
    <div class="stat-number"><?= (int)$stats['canceladas'] ?></div>
    <div class="stat-label">Canceladas</div>
  </div>
</div>

<!-- Próximas reservas -->
<div class="card">
  <div class="card-title">Próximas reservas</div>
  <?php if (empty($proximas)): ?>
    <p style="color:var(--gris-400);text-align:center;padding:2rem 0">
      No tienes reservas próximas.
      <a href="/nueva-reserva.php" style="color:var(--azul);font-weight:600">Reservar ahora →</a>
    </p>
  <?php else: ?>
    <div class="table-wrapper">
      <table>
        <thead>
          <tr>
            <th>Recurso</th>
            <th>Inicio</th>
            <th>Fin</th>
            <th>Estado</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($proximas as $r): ?>
            <tr>
              <td><?= htmlspecialchars($r['recurso_nombre']) ?></td>
              <td><?= date('d/m/Y H:i', strtotime($r['fecha_inicio'])) ?></td>
              <td><?= date('d/m/Y H:i', strtotime($r['fecha_fin'])) ?></td>
              <td><span class="badge badge-green"><?= $r['estado'] ?></span></td>
              <td>
                <form method="POST" action="/reservas.php" style="display:inline">
                  <input type="hidden" name="accion" value="cancelar">
                  <input type="hidden" name="id" value="<?= $r['id'] ?>">
                  <button type="submit" class="btn btn-sm btn-danger btn-cancelar">Cancelar</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div style="margin-top:1rem">
      <a href="/reservas.php" class="btn-outline-dark btn btn-sm">Ver todas mis reservas →</a>
    </div>
  <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
