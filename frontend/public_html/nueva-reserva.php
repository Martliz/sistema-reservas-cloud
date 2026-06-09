<?php
// nueva-reserva.php — Formulario para crear una reserva
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';

session_init();
redirigir_si_no_autenticado();

$usuario = usuario_actual();
$db      = getDB();
$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $recurso_id   = (int)($_POST['recurso_id'] ?? 0);
    $fecha_inicio = trim($_POST['fecha_inicio'] ?? '');
    $fecha_fin    = trim($_POST['fecha_fin'] ?? '');
    $notas        = trim($_POST['notas'] ?? '');

    // Validaciones
    if ($recurso_id <= 0)      $errores[] = 'Selecciona un recurso.';
    if (empty($fecha_inicio))  $errores[] = 'La fecha de inicio es requerida.';
    if (empty($fecha_fin))     $errores[] = 'La fecha de fin es requerida.';

    if (empty($errores)) {
        $inicio = new DateTime($fecha_inicio);
        $fin    = new DateTime($fecha_fin);
        $ahora  = new DateTime();

        if ($inicio <= $ahora) $errores[] = 'La fecha de inicio debe ser futura.';
        if ($fin <= $inicio)   $errores[] = 'La fecha de fin debe ser posterior al inicio.';
    }

    if (empty($errores)) {
        // Verificar que el recurso existe y está activo
        $stmt = $db->prepare('SELECT id, nombre FROM recursos WHERE id = ? AND activo = TRUE');
        $stmt->execute([$recurso_id]);
        if (!$stmt->fetch()) {
            $errores[] = 'El recurso seleccionado no está disponible.';
        }
    }

    if (empty($errores)) {
        // Verificar disponibilidad: sin solapamiento con otras reservas confirmadas
        $stmt = $db->prepare("
            SELECT COUNT(*) FROM reservas
            WHERE recurso_id = ?
              AND estado = 'CONFIRMADA'
              AND fecha_inicio < ?::timestamptz
              AND fecha_fin    > ?::timestamptz
        ");
        $stmt->execute([$recurso_id, $fecha_fin, $fecha_inicio]);
        if ((int)$stmt->fetchColumn() > 0) {
            $errores[] = 'El recurso no está disponible en ese horario. Elige otro período.';
        }
    }

    if (empty($errores)) {
        $stmt = $db->prepare("
            INSERT INTO reservas (usuario_id, recurso_id, fecha_inicio, fecha_fin, notas)
            VALUES (?, ?, ?::timestamptz, ?::timestamptz, ?)
            RETURNING id
        ");
        $stmt->execute([$usuario['id'], $recurso_id, $fecha_inicio, $fecha_fin, $notas ?: null]);
        $nueva_id = $stmt->fetchColumn();

        $_SESSION['flash'] = ['tipo' => 'success', 'mensaje' => "✅ Reserva #{$nueva_id} confirmada correctamente."];
        header('Location: /reservas.php');
        exit;
    }
}

// Cargar recursos disponibles para el select
$recursos = $db->query('SELECT id, nombre, descripcion, capacidad FROM recursos WHERE activo = TRUE ORDER BY nombre')->fetchAll();

$pageTitle = 'Nueva Reserva — ' . APP_NAME;
include 'includes/header.php';
?>

<div class="page-header">
  <h1>+ Nueva Reserva</h1>
  <a href="/reservas.php" class="btn btn-outline">← Volver</a>
</div>

<?php foreach ($errores as $e): ?>
  <div class="alert alert-error"><?= htmlspecialchars($e) ?></div>
<?php endforeach; ?>

<div class="card">
  <form method="POST" action="/nueva-reserva.php" id="form-reserva">
    <div class="form-group">
      <label for="recurso_id">Recurso a reservar</label>
      <select id="recurso_id" name="recurso_id" required>
        <option value="">Selecciona un recurso...</option>
        <?php foreach ($recursos as $rc): ?>
          <option value="<?= $rc['id'] ?>"
            <?= (int)($_POST['recurso_id'] ?? 0) === (int)$rc['id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($rc['nombre']) ?>
            (capacidad: <?= $rc['capacidad'] ?>)
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem">
      <div class="form-group">
        <label for="fecha_inicio">Fecha y hora de inicio</label>
        <input type="datetime-local" id="fecha_inicio" name="fecha_inicio"
               value="<?= htmlspecialchars($_POST['fecha_inicio'] ?? '') ?>" required>
      </div>
      <div class="form-group">
        <label for="fecha_fin">Fecha y hora de fin</label>
        <input type="datetime-local" id="fecha_fin" name="fecha_fin"
               value="<?= htmlspecialchars($_POST['fecha_fin'] ?? '') ?>" required>
      </div>
    </div>

    <div class="form-group">
      <label for="notas">Notas (opcional)</label>
      <textarea id="notas" name="notas" placeholder="Ej: Reunión de equipo, presentación de cliente..."><?= htmlspecialchars($_POST['notas'] ?? '') ?></textarea>
    </div>

    <button type="submit" class="btn btn-primary">Confirmar reserva</button>
  </form>
</div>

<?php if (!empty($recursos)): ?>
<div class="card">
  <div class="card-title">📋 Recursos disponibles</div>
  <div class="card-grid">
    <?php foreach ($recursos as $rc): ?>
      <div style="border:1px solid var(--gray-200); border-radius:var(--radius); padding:1rem">
        <strong><?= htmlspecialchars($rc['nombre']) ?></strong>
        <p style="font-size:.85rem; color:var(--gray-400); margin:.25rem 0">
          <?= htmlspecialchars($rc['descripcion'] ?? '') ?>
        </p>
        <span class="badge badge-blue">Capacidad: <?= $rc['capacidad'] ?></span>
      </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
