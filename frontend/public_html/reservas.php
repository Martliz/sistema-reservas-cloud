<?php
// reservas.php — Listado y gestión de reservas del usuario
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';

session_init();
redirigir_si_no_autenticado();

$usuario = usuario_actual();
$db      = getDB();

// Acción: cancelar reserva
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'cancelar') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        $stmt = $db->prepare("
            UPDATE reservas SET estado = 'CANCELADA'
            WHERE id = ? AND usuario_id = ? AND estado = 'CONFIRMADA'
        ");
        $stmt->execute([$id, $usuario['id']]);
        $afectadas = $stmt->rowCount();

        $_SESSION['flash'] = $afectadas > 0
            ? ['tipo' => 'success', 'mensaje' => "Reserva #{$id} cancelada."]
            : ['tipo' => 'error',   'mensaje' => 'No se pudo cancelar la reserva.'];
    }
    header('Location: /reservas.php');
    exit;
}

// Filtro por estado
$filtro_estado = $_GET['estado'] ?? 'todas';
$estados_validos = ['todas', 'CONFIRMADA', 'CANCELADA'];
if (!in_array($filtro_estado, $estados_validos)) $filtro_estado = 'todas';

$where_estado = $filtro_estado !== 'todas' ? "AND r.estado = '{$filtro_estado}'" : '';

$stmt = $db->prepare("
    SELECT r.id, r.fecha_inicio, r.fecha_fin, r.estado, r.notas, r.created_at,
           rc.nombre AS recurso_nombre, rc.capacidad
    FROM reservas r
    JOIN recursos rc ON rc.id = r.recurso_id
    WHERE r.usuario_id = ? {$where_estado}
    ORDER BY r.fecha_inicio DESC
");
$stmt->execute([$usuario['id']]);
$reservas = $stmt->fetchAll();

$pageTitle = 'Mis Reservas — ' . APP_NAME;
include 'includes/header.php';

$badge = fn($e) => match($e) {
    'CONFIRMADA' => 'badge-green',
    'CANCELADA'  => 'badge-red',
    default      => 'badge-blue',
};
?>

<div class="page-header">
  <h1>📋 Mis Reservas</h1>
  <a href="/nueva-reserva.php" class="btn btn-primary">+ Nueva reserva</a>
</div>

<!-- Filtros -->
<div style="display:flex; gap:.5rem; margin-bottom:1rem; flex-wrap:wrap">
  <?php foreach (['todas' => 'Todas', 'CONFIRMADA' => 'Activas', 'CANCELADA' => 'Canceladas'] as $val => $etiq): ?>
    <a href="?estado=<?= $val ?>"
       class="btn btn-sm <?= $filtro_estado === $val ? 'btn-primary' : 'btn-outline' ?>">
      <?= $etiq ?>
    </a>
  <?php endforeach; ?>
</div>

<div class="card">
  <?php if (empty($reservas)): ?>
    <p style="color:var(--gray-400); text-align:center; padding:2rem 0">
      No hay reservas para mostrar.
      <a href="/nueva-reserva.php">Crea tu primera reserva</a>
    </p>
  <?php else: ?>
    <div class="table-wrapper">
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>Recurso</th>
            <th>Inicio</th>
            <th>Fin</th>
            <th>Estado</th>
            <th>Notas</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($reservas as $r): ?>
            <tr>
              <td><?= $r['id'] ?></td>
              <td><?= htmlspecialchars($r['recurso_nombre']) ?></td>
              <td><?= date('d/m/Y H:i', strtotime($r['fecha_inicio'])) ?></td>
              <td><?= date('d/m/Y H:i', strtotime($r['fecha_fin'])) ?></td>
              <td><span class="badge <?= $badge($r['estado']) ?>"><?= $r['estado'] ?></span></td>
              <td style="max-width:180px; font-size:.85rem; color:var(--gray-400)">
                <?= htmlspecialchars($r['notas'] ?? '—') ?>
              </td>
              <td>
                <?php if ($r['estado'] === 'CONFIRMADA' && strtotime($r['fecha_inicio']) > time()): ?>
                  <form method="POST" action="/reservas.php" style="display:inline">
                    <input type="hidden" name="accion" value="cancelar">
                    <input type="hidden" name="id" value="<?= $r['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-danger btn-cancelar">
                      Cancelar
                    </button>
                  </form>
                <?php else: ?>
                  <span style="color:var(--gray-400); font-size:.82rem">—</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
