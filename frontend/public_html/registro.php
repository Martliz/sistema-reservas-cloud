<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

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

    if (empty($nombre))                              $errores[] = 'El nombre es requerido.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))  $errores[] = 'Email inválido.';
    if (strlen($password) < 6)                       $errores[] = 'La contraseña debe tener al menos 6 caracteres.';
    if ($password !== $confirm)                      $errores[] = 'Las contraseñas no coinciden.';

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
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Crear cuenta — ProWorkspace</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --red:    #EC0000;
      --red-dk: #C40000;
      --black:  #111111;
      --white:  #FFFFFF;
      --text:   #222222;
      --muted:  #888888;
      --border: #E5E7EB;
    }

    body {
      font-family: 'Inter', system-ui, sans-serif;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      background: #f3f4f6;
    }

    /* ── NAVBAR ───────────────────────────────────────────────── */
    .navbar {
      background: var(--black);
      height: 60px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0 2rem;
      flex-shrink: 0;
    }
    .nav-logo {
      display: flex; align-items: center; gap: .75rem;
      text-decoration: none;
    }
    .nav-logo-mark {
      width: 34px; height: 34px;
      background: var(--red);
      border-radius: 8px;
      display: flex; align-items: center; justify-content: center;
      flex-shrink: 0;
    }
    .nav-logo-name { font-size: .95rem; font-weight: 800; color: var(--white); letter-spacing: -.01em; }
    .nav-logo-tagline { font-size: .65rem; color: rgba(255,255,255,.4); font-weight: 400; letter-spacing: .04em; text-transform: uppercase; }
    .nav-right { display: flex; align-items: center; gap: 1rem; }
    .nav-login { font-size: .82rem; color: rgba(255,255,255,.6); text-decoration: none; transition: color .15s; }
    .nav-login:hover { color: var(--white); }
    .nav-login strong { color: var(--white); }
    .nav-divider-v { width: 1px; height: 18px; background: rgba(255,255,255,.15); }
    .nav-cta {
      font-size: .82rem; font-weight: 700;
      color: var(--white); background: var(--red);
      padding: .4rem 1.1rem; border-radius: 99px;
      text-decoration: none; transition: background .15s;
    }
    .nav-cta:hover { background: var(--red-dk); }

    /* ── HERO WRAPPER ─────────────────────────────────────────── */
    .page-hero {
      flex: 1;
      position: relative;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 3rem 1rem;
      background:
        linear-gradient(135deg,
          rgba(30,0,0,.75) 0%,
          rgba(10,10,30,.8) 100%),
        url('https://images.unsplash.com/photo-1497366216548-37526070297c?w=1600&q=85')
        center/cover no-repeat;
      overflow: hidden;
    }
    .page-hero::before {
      content: 'ProWorkspace';
      position: absolute;
      bottom: -20px; right: -10px;
      font-size: 9rem; font-weight: 900;
      color: rgba(255,255,255,.03);
      white-space: nowrap;
      pointer-events: none;
      letter-spacing: -.04em;
    }

    .hero-split {
      display: grid;
      grid-template-columns: 1fr 440px;
      gap: 4rem;
      max-width: 960px;
      width: 100%;
      align-items: center;
      position: relative;
      z-index: 1;
    }

    /* ── LADO IZQUIERDO ───────────────────────────────────────── */
    .hero-badge {
      display: inline-flex; align-items: center; gap: 6px;
      background: rgba(236,0,0,.2);
      border: 1px solid rgba(236,0,0,.4);
      color: #fca5a5;
      font-size: .7rem; font-weight: 700;
      padding: .3rem .9rem; border-radius: 99px;
      letter-spacing: .08em; text-transform: uppercase;
      margin-bottom: 1.25rem;
    }
    .hero-badge::before {
      content: '';
      width: 5px; height: 5px;
      background: var(--red); border-radius: 50%;
      animation: pulse 2s ease-in-out infinite;
    }
    @keyframes pulse { 0%,100%{opacity:1;transform:scale(1)} 50%{opacity:.4;transform:scale(1.5)} }

    .hero-left h1 {
      font-size: clamp(1.9rem, 3.5vw, 2.8rem);
      font-weight: 900; color: var(--white);
      line-height: 1.1; letter-spacing: -.03em;
      margin-bottom: 1rem;
    }
    .hero-left h1 em { font-style: normal; color: var(--red); }
    .hero-left p {
      font-size: .95rem; color: rgba(255,255,255,.65);
      line-height: 1.7; margin-bottom: 2rem; max-width: 380px;
    }
    .hero-feature {
      display: flex; align-items: center; gap: .6rem;
      font-size: .82rem; color: rgba(255,255,255,.7);
      margin-bottom: .6rem;
    }
    .hero-feature::before {
      content: '';
      width: 16px; height: 16px;
      background: rgba(236,0,0,.15);
      border: 1px solid rgba(236,0,0,.5);
      border-radius: 50%;
      flex-shrink: 0;
      background-image: url("data:image/svg+xml,%3Csvg width='10' height='10' viewBox='0 0 10 10' fill='none'%3E%3Cpath d='M2 5l2 2 4-4' stroke='%23EC0000' stroke-width='1.5' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: center;
    }

    /* ── CARD DE REGISTRO ─────────────────────────────────────── */
    .register-card {
      background: var(--white);
      border-radius: 16px;
      padding: 2.5rem;
      box-shadow: 0 24px 64px rgba(0,0,0,.25);
    }
    .card-logo {
      display: flex; align-items: center; gap: .7rem;
      margin-bottom: 1.5rem;
    }
    .card-logo-mark {
      width: 40px; height: 40px;
      background: var(--red); border-radius: 10px;
      display: flex; align-items: center; justify-content: center;
    }
    .card-logo-name { font-size: 1.1rem; font-weight: 800; color: var(--text); letter-spacing: -.01em; }
    .card-logo-sub  { font-size: .68rem; color: var(--muted); text-transform: uppercase; letter-spacing: .06em; }

    .card-title    { font-size: 1.3rem; font-weight: 800; color: var(--text); margin-bottom: .3rem; }
    .card-subtitle { font-size: .85rem; color: var(--muted); margin-bottom: 1.5rem; }

    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: .75rem; }

    .form-group { margin-bottom: .9rem; }
    .form-group label {
      display: block; font-size: .75rem; font-weight: 700;
      color: #555; margin-bottom: .35rem;
      text-transform: uppercase; letter-spacing: .05em;
    }
    .form-group input {
      width: 100%;
      padding: .65rem .9rem;
      border: 1.5px solid var(--border);
      border-radius: 8px;
      font-size: .9rem; color: var(--text);
      font-family: inherit;
      transition: border-color .15s, box-shadow .15s;
      background: #fafafa;
    }
    .form-group input:focus {
      outline: none;
      border-color: var(--red);
      box-shadow: 0 0 0 3px rgba(236,0,0,.1);
      background: var(--white);
    }
    .form-hint { font-size: .72rem; color: var(--muted); margin-top: .25rem; }

    /* Barra de fortaleza de contraseña */
    .strength-bar { display: flex; gap: 3px; margin-top: .4rem; height: 3px; }
    .strength-bar span {
      flex: 1; border-radius: 99px;
      background: #e5e7eb;
      transition: background .3s;
    }

    .btn-register {
      width: 100%;
      padding: .8rem;
      background: var(--red);
      color: var(--white);
      font-size: .95rem; font-weight: 700;
      border: none; border-radius: 8px;
      cursor: pointer; font-family: inherit;
      margin-top: .5rem;
      transition: background .15s, transform .1s;
    }
    .btn-register:hover { background: var(--red-dk); transform: translateY(-1px); }

    .terms {
      font-size: .75rem; color: var(--muted);
      text-align: center; margin-top: .75rem; line-height: 1.5;
    }
    .terms a { color: var(--red); text-decoration: none; }

    .card-footer {
      text-align: center; margin-top: 1rem;
      font-size: .82rem; color: var(--muted);
    }
    .card-footer a { color: var(--red); font-weight: 600; text-decoration: none; }
    .card-footer a:hover { text-decoration: underline; }

    .alert-error {
      background: #fef2f2; color: #7f1d1d;
      border: 1px solid #fca5a5;
      border-radius: 8px; padding: .75rem 1rem;
      font-size: .85rem; margin-bottom: 1.25rem;
      display: flex; gap: .5rem; align-items: flex-start;
    }
    .alert-error::before { content: '!'; font-weight: 900; color: var(--red); flex-shrink: 0; }

    /* ── FOOTER ───────────────────────────────────────────────── */
    .site-footer {
      background: var(--black);
      color: rgba(255,255,255,.3);
      text-align: center;
      padding: 1rem 2rem;
      font-size: .72rem;
      flex-shrink: 0;
    }

    /* ── RESPONSIVE ───────────────────────────────────────────── */
    @media(max-width: 768px){
      .hero-split { grid-template-columns: 1fr; gap: 2rem; }
      .hero-left  { display: none; }
      .register-card { padding: 2rem 1.5rem; }
      .form-row { grid-template-columns: 1fr; }
      .page-hero::before { display: none; }
    }
  </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar">
  <a href="/login.php" class="nav-logo">
    <div class="nav-logo-mark">
      <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
        <rect x="2" y="2" width="7" height="7" rx="1.5" fill="white"/>
        <rect x="11" y="2" width="7" height="7" rx="1.5" fill="white" opacity=".6"/>
        <rect x="2" y="11" width="7" height="7" rx="1.5" fill="white" opacity=".6"/>
        <rect x="11" y="11" width="7" height="7" rx="1.5" fill="white"/>
      </svg>
    </div>
    <div>
      <div class="nav-logo-name">ProWorkspace</div>
      <div class="nav-logo-tagline">Reservas en la nube</div>
    </div>
  </a>
  <div class="nav-right">
    <a href="/login.php" class="nav-login">
      ¿Ya tienes cuenta? <strong>Inicia sesión</strong>
    </a>
    <div class="nav-divider-v"></div>
    <a href="/login.php" class="nav-cta">Iniciar sesión</a>
  </div>
</nav>

<!-- HERO + CARD -->
<div class="page-hero">
  <div class="hero-split">

    <!-- Izquierda -->
    <div class="hero-left">
      <div class="hero-badge">Únete a ProWorkspace</div>
      <h1>Crea tu cuenta<br>y <em>empieza hoy</em></h1>
      <p>Miles de profesionales ya gestionan sus espacios con ProWorkspace. Registro gratuito, sin tarjeta de crédito.</p>
      <div class="hero-feature">Acceso inmediato tras el registro</div>
      <div class="hero-feature">Reserva salas en segundos</div>
      <div class="hero-feature">Cancela cuando quieras</div>
      <div class="hero-feature">Sin costo, sin compromisos</div>
    </div>

    <!-- Derecha: formulario -->
    <div class="register-card">
      <div class="card-logo">
        <div class="card-logo-mark">
          <svg width="22" height="22" viewBox="0 0 22 22" fill="none">
            <rect x="2" y="2" width="8" height="8" rx="2" fill="white"/>
            <rect x="12" y="2" width="8" height="8" rx="2" fill="white" opacity=".6"/>
            <rect x="2" y="12" width="8" height="8" rx="2" fill="white" opacity=".6"/>
            <rect x="12" y="12" width="8" height="8" rx="2" fill="white"/>
          </svg>
        </div>
        <div>
          <div class="card-logo-name">ProWorkspace</div>
          <div class="card-logo-sub">Crear cuenta gratis</div>
        </div>
      </div>

      <div class="card-title">Crear cuenta</div>
      <div class="card-subtitle">Completa tus datos para comenzar</div>

      <?php foreach ($errores as $e): ?>
        <div class="alert-error"><?= htmlspecialchars($e) ?></div>
      <?php endforeach; ?>

      <form method="POST" action="/registro.php">
        <div class="form-group">
          <label for="nombre">Nombre completo</label>
          <input type="text" id="nombre" name="nombre"
                 value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>"
                 placeholder="Tu nombre" required autofocus>
        </div>

        <div class="form-group">
          <label for="email">Correo electrónico</label>
          <input type="email" id="email" name="email"
                 value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                 placeholder="tu@email.com" required>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="password">Contraseña</label>
            <input type="password" id="password" name="password"
                   placeholder="Mín. 6 caracteres" required
                   oninput="checkStrength(this.value)">
            <div class="strength-bar">
              <span id="s1"></span><span id="s2"></span>
              <span id="s3"></span><span id="s4"></span>
            </div>
            <div class="form-hint" id="strength-text">Mínimo 6 caracteres</div>
          </div>
          <div class="form-group">
            <label for="confirmar_password">Confirmar</label>
            <input type="password" id="confirmar_password" name="confirmar_password"
                   placeholder="Repite la contraseña" required>
          </div>
        </div>

        <button type="submit" class="btn-register">Crear cuenta gratis</button>
      </form>

      <div class="terms">
        Al registrarte aceptas nuestros <a href="#">Términos de uso</a>
        y <a href="#">Política de privacidad</a>
      </div>

      <div class="card-footer">
        ¿Ya tienes cuenta? <a href="/login.php">Iniciar sesión</a>
      </div>
    </div>

  </div>
</div>

<!-- FOOTER -->
<footer class="site-footer">
  ProWorkspace &mdash; Sistema de Reservas v1.0.0 &mdash; Alkemy Cloud Architecture 2025
</footer>

<script>
function checkStrength(val) {
  const bars  = [document.getElementById('s1'), document.getElementById('s2'),
                 document.getElementById('s3'), document.getElementById('s4')];
  const label = document.getElementById('strength-text');
  const colors = ['#e5e7eb','#e5e7eb','#e5e7eb','#e5e7eb'];
  let score = 0;

  if (val.length >= 6)                          score++;
  if (val.length >= 10)                         score++;
  if (/[A-Z]/.test(val) && /[0-9]/.test(val))  score++;
  if (/[^A-Za-z0-9]/.test(val))                score++;

  const palette = ['#EC0000','#f97316','#eab308','#16a34a'];
  const labels  = ['Muy débil','Débil','Regular','Fuerte'];

  for (let i = 0; i < 4; i++) {
    bars[i].style.background = i < score ? palette[score - 1] : '#e5e7eb';
  }
  label.textContent = score > 0 ? labels[score - 1] : 'Mínimo 6 caracteres';
  label.style.color = score > 0 ? palette[score - 1] : '#888';
}
</script>
</body>
</html>
