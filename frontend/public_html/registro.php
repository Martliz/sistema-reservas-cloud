<?php
// registro.php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';

session_init();

if (usuario_autenticado()) {
    header('Location: /index.php');
    exit;
}

$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre   = trim($_POST['nombre'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirmar_password'] ?? '';

    if (empty($nombre)) $errores[] = 'El nombre es requerido.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errores[] = 'Email inválido.';
    if (strlen($password) < 6) $errores[] = 'La contraseña debe tener al menos 6 caracteres.';
    if ($password !== $confirm) $errores[] = 'Las contraseñas no coinciden.';

    if (empty($errores)) {
        $db = getDB();

        $existe = $db->prepare('SELECT id FROM usuarios WHERE email = ?');
        $existe->execute([$email]);
        if ($existe->fetch()) {
            $errores[] = 'El email ya está registrado.';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $db->prepare(
                'INSERT INTO usuarios (nombre, email, password) VALUES (?, ?, ?) RETURNING id'
            );
            $stmt->execute([$nombre, $email, $hash]);
            $_SESSION['flash'] = ['tipo' => 'success', 'mensaje' => '¡Cuenta creada! Ahora puedes iniciar sesión.'];
            header('Location: /login.php');
            exit;
        }
    }
}

$pageTitle = 'Crear cuenta — ' . APP_NAME;
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $pageTitle ?></title>
  <link rel="stylesheet" href="/assets/css/main.css">
</head>
<body>
<div class="auth-wrapper">
  <div class="auth-card">
    <h1>📅 Crear cuenta</h1>
    <p>Únete al sistema de reservas</p>

    <?php foreach ($errores as $e): ?>
      <div class="alert alert-error"><?= htmlspecialchars($e) ?></div>
    <?php endforeach; ?>

    <form method="POST" action="/registro.php">
      <div class="form-group">
        <label for="nombre">Nombre completo</label>
        <input type="text" id="nombre" name="nombre"
               value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>" required autofocus>
      </div>
      <div class="form-group">
        <label for="email">Correo electrónico</label>
        <input type="email" id="email" name="email"
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
      </div>
      <div class="form-group">
        <label for="password">Contraseña</label>
        <input type="password" id="password" name="password" required>
        <span class="form-hint">Mínimo 6 caracteres</span>
      </div>
      <div class="form-group">
        <label for="confirmar_password">Confirmar contraseña</label>
        <input type="password" id="confirmar_password" name="confirmar_password" required>
      </div>
      <button type="submit" class="btn btn-primary btn-block">Crear cuenta</button>
    </form>

    <div class="auth-footer">
      ¿Ya tienes cuenta? <a href="/login.php">Inicia sesión</a>
    </div>
  </div>
</div>
<script src="/assets/js/main.js"></script>
</body>
</html>
