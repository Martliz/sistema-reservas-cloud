<?php
// login.php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';

session_init();

// Si ya está autenticado, ir al inicio
if (usuario_autenticado()) {
    header('Location: /index.php');
    exit;
}

$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores[] = 'Ingresa un email válido.';
    }
    if (empty($password)) {
        $errores[] = 'La contraseña es requerida.';
    }

    if (empty($errores)) {
        $db   = getDB();
        $stmt = $db->prepare('SELECT id, nombre, email, password FROM usuarios WHERE email = ?');
        $stmt->execute([$email]);
        $usuario = $stmt->fetch();

        if (!$usuario || !password_verify($password, $usuario['password'])) {
            $errores[] = 'Credenciales incorrectas.';
        } else {
            $token = jwt_create([
                'id'     => $usuario['id'],
                'email'  => $usuario['email'],
                'nombre' => $usuario['nombre'],
            ]);
            $_SESSION['usuario_id']     = $usuario['id'];
            $_SESSION['usuario_nombre'] = $usuario['nombre'];
            $_SESSION['usuario_email']  = $usuario['email'];
            $_SESSION['token']          = $token;

            header('Location: /index.php');
            exit;
        }
    }
}

$pageTitle = 'Iniciar sesión — ' . APP_NAME;
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
    <h1>📅 Iniciar sesión</h1>
    <p>Sistema de Reservas — Alkemy</p>

    <?php foreach ($errores as $e): ?>
      <div class="alert alert-error"><?= htmlspecialchars($e) ?></div>
    <?php endforeach; ?>

    <form method="POST" action="/login.php">
      <div class="form-group">
        <label for="email">Correo electrónico</label>
        <input type="email" id="email" name="email"
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
               required autofocus>
      </div>
      <div class="form-group">
        <label for="password">Contraseña</label>
        <input type="password" id="password" name="password" required>
      </div>
      <button type="submit" class="btn btn-primary btn-block">Entrar</button>
    </form>

    <div class="auth-footer">
      ¿No tienes cuenta? <a href="/registro.php">Regístrate</a>
    </div>
  </div>
</div>
<script src="/assets/js/main.js"></script>
</body>
</html>
