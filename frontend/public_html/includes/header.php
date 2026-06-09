<?php
// includes/header.php
// Cabecera HTML compartida por todas las páginas.
require_once __DIR__ . '/auth.php';
$usuario = usuario_actual();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle ?? APP_NAME) ?></title>
  <link rel="stylesheet" href="/assets/css/main.css">
</head>
<body>

<nav class="navbar">
  <a class="navbar-brand" href="/index.php">📅 <?= APP_NAME ?></a>
  <div class="navbar-links">
    <?php if ($usuario): ?>
      <a href="/index.php">Inicio</a>
      <a href="/reservas.php">Mis Reservas</a>
      <a href="/nueva-reserva.php">Nueva Reserva</a>
      <a href="/recursos.php">Recursos</a>
      <span class="navbar-user">👤 <?= htmlspecialchars($usuario['nombre']) ?></span>
      <a href="/logout.php" class="btn btn-outline">Salir</a>
    <?php else: ?>
      <a href="/login.php">Iniciar sesión</a>
      <a href="/registro.php" class="btn btn-primary">Registrarse</a>
    <?php endif; ?>
  </div>
</nav>

<main class="container">
<?php if (isset($_SESSION['flash'])): ?>
  <div class="alert alert-<?= $_SESSION['flash']['tipo'] ?>">
    <?= htmlspecialchars($_SESSION['flash']['mensaje']) ?>
  </div>
  <?php unset($_SESSION['flash']); ?>
<?php endif; ?>
