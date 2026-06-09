<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

session_init();
redirigir_si_no_autenticado();

$usuario = usuario_actual();
$db      = getDB();

$stmt = $db->prepare("
    SELECT
      COUNT(*) FILTER (WHERE estado = 'CONFIRMADA') AS activas,
      COUNT(*) FILTER (WHERE estado = 'CANCELADA')  AS canceladas,
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
    ORDER BY r.fecha_inicio ASC LIMIT 5
");
$stmt->execute([$usuario['id']]);
$proximas = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sistema de Reservas</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --red:     #EC0000;
      --red-dk:  #C40000;
      --dark:    #333333;
      --black:   #111111;
      --white:   #FFFFFF;
      --text:    #222222;
      --muted:   #888888;
      --bg:      #F5F5F5;
    }

    body { font-family: 'Inter', system-ui, sans-serif; color: var(--text); background: var(--bg); }

    /* ── NAVBAR ───────────────────────────────────────────────── */
    .navbar {
      background: var(--black);
      height: 60px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0 2rem;
      position: sticky;
      top: 0;
      z-index: 200;
    }
    .nav-left { display: flex; align-items: center; gap: 1rem; }
    .nav-logo-box {
      display: flex; align-items: center; gap: .6rem;
      text-decoration: none;
    }
    .nav-logo-icon {
      width: 32px; height: 32px;
      background: var(--red);
      border-radius: 6px;
      display: flex; align-items: center; justify-content: center;
    }
    .nav-divider { width: 1px; height: 24px; background: rgba(255,255,255,.2); }
    .nav-brand { font-size: .92rem; font-weight: 700; color: var(--white); line-height: 1.2; }
    .nav-brand span { display: block; font-size: .7rem; font-weight: 400; color: rgba(255,255,255,.5); }
    .nav-right { display: flex; align-items: center; gap: 1.25rem; }
    .nav-user {
      display: flex; align-items: center; gap: .5rem;
      color: rgba(255,255,255,.8); font-size: .82rem;
      text-decoration: none; cursor: pointer;
    }
    .nav-user svg { opacity: .7; }
    .nav-links { display: flex; align-items: center; gap: 1rem; }
    .nav-link { color: rgba(255,255,255,.6); text-decoration: none; font-size: .82rem; transition: color .15s; }
    .nav-link:hover { color: var(--white); }
    .nav-link.active { color: var(--white); font-weight: 600; }
    .nav-reservar {
      background: var(--red); color: var(--white);
      font-size: .82rem; font-weight: 700;
      padding: .45rem 1.2rem; border-radius: 99px;
      text-decoration: none; transition: background .15s;
    }
    .nav-reservar:hover { background: var(--red-dk); }
    .nav-hamburger { color: var(--white); cursor: pointer; opacity: .7; }

    /* ── BREADCRUMB ───────────────────────────────────────────── */
    .breadcrumb {
      background: rgba(0,0,0,.75);
      backdrop-filter: blur(8px);
      padding: .5rem 2rem;
      border-bottom: 1px solid rgba(255,255,255,.06);
    }
    .breadcrumb a, .breadcrumb span {
      font-size: .78rem; color: rgba(255,255,255,.55);
      text-decoration: none;
    }
    .breadcrumb a:hover { color: rgba(255,255,255,.9); }
    .breadcrumb .sep { margin: 0 .4rem; color: rgba(255,255,255,.3); }
    .breadcrumb .current { color: rgba(255,255,255,.85); }

    /* ── HERO ─────────────────────────────────────────────────── */
    .hero {
      position: relative;
      height: 500px;
      display: flex;
      align-items: center;
      justify-content: center;
      background:
        linear-gradient(to bottom, rgba(0,0,0,.35) 0%, rgba(0,0,0,.65) 100%),
        url('https://images.unsplash.com/photo-1497366216548-37526070297c?w=1600&q=85') center/cover no-repeat;
      text-align: center;
      padding: 2rem;
    }
    .hero-content { max-width: 700px; }
    .hero h1 {
      font-size: clamp(1.8rem, 4vw, 2.9rem);
      font-weight: 900;
      color: var(--white);
      line-height: 1.15;
      margin-bottom: 2rem;
      letter-spacing: -.02em;
      text-shadow: 0 2px 20px rgba(0,0,0,.4);
    }
    .hero h1 .slash { color: var(--red); }
    .hero-cta {
      display: inline-block;
      background: var(--red);
      color: var(--white);
      font-size: .95rem;
      font-weight: 700;
      padding: .85rem 2.5rem;
      border-radius: 99px;
      text-decoration: none;
      letter-spacing: .02em;
      transition: background .15s, transform .15s, box-shadow .15s;
      box-shadow: 0 4px 20px rgba(236,0,0,.4);
    }
    .hero-cta:hover {
      background: var(--red-dk);
      transform: translateY(-2px);
      box-shadow: 0 8px 28px rgba(236,0,0,.5);
    }

    /* ── BIENVENIDA ───────────────────────────────────────────── */
    .bienvenida {
      background: var(--white);
      padding: 4rem 2rem;
      text-align: center;
    }
    .bienvenida h2 {
      font-size: clamp(1.2rem, 2.5vw, 1.7rem);
      font-weight: 700;
      color: var(--text);
      max-width: 680px;
      margin: 0 auto;
      line-height: 1.4;
    }
    .bienvenida h2 .slash { color: var(--red); }

    /* ── STATS (entre bienvenida y servicios) ─────────────────── */
    .stats-section {
      background: var(--white);
      padding: 0 2rem 3rem;
      display: flex;
      justify-content: center;
      gap: 1.5rem;
      flex-wrap: wrap;
    }
    .stat-pill {
      display: flex; align-items: center; gap: .75rem;
      background: #f8f8f8; border: 1px solid #eee;
      border-radius: 12px; padding: 1rem 1.5rem;
      min-width: 170px;
      transition: box-shadow .2s;
    }
    .stat-pill:hover { box-shadow: 0 4px 16px rgba(0,0,0,.08); }
    .stat-pill-icon {
      width: 42px; height: 42px; border-radius: 10px;
      display: flex; align-items: center; justify-content: center;
      background: #fff0f0;
      flex-shrink: 0;
    }
    .stat-pill-num { font-size: 1.6rem; font-weight: 900; color: var(--red); line-height: 1; }
    .stat-pill-lbl { font-size: .72rem; color: var(--muted); font-weight: 600; text-transform: uppercase; letter-spacing: .05em; margin-top: 2px; }

    /* ── SERVICIOS ────────────────────────────────────────────── */
    .servicios {
      background: var(--dark);
      padding: 4rem 2rem;
      text-align: center;
    }
    .servicios h3 {
      font-size: 1.2rem;
      font-weight: 700;
      color: var(--white);
      margin-bottom: 2.5rem;
    }
    .servicios-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 2rem;
      max-width: 900px;
      margin: 0 auto;
    }
    .servicio-item { display: flex; flex-direction: column; align-items: center; gap: .75rem; }
    .servicio-icon {
      width: 56px; height: 56px;
      border: 1.5px solid rgba(255,255,255,.35);
      border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      transition: border-color .2s, background .2s;
    }
    .servicio-item:hover .servicio-icon {
      border-color: var(--red);
      background: rgba(236,0,0,.1);
    }
    .servicio-item p { font-size: .8rem; color: rgba(255,255,255,.8); font-weight: 500; line-height: 1.4; }

    /* ── MIS RESERVAS ─────────────────────────────────────────── */
    .reservas-section {
      background: var(--white);
      padding: 3rem 2rem;
      max-width: 960px;
      margin: 0 auto;
    }
    .sec-header {
      display: flex; justify-content: space-between; align-items: center;
      margin-bottom: 1.25rem;
    }
    .sec-title {
      font-size: 1.1rem; font-weight: 800; color: var(--text);
      display: flex; align-items: center; gap: .5rem;
    }
    .sec-title::before {
      content: ''; width: 4px; height: 1.1em;
      background: var(--red); border-radius: 99px; display: inline-block;
    }
    .ver-todas { font-size: .82rem; color: var(--red); font-weight: 600; text-decoration: none; }
    .ver-todas:hover { text-decoration: underline; }

    .tabla-card {
      border-radius: 12px; overflow: hidden;
      border: 1px solid #eee;
      box-shadow: 0 2px 12px rgba(0,0,0,.05);
    }
    .tabla-card table { width: 100%; border-collapse: collapse; font-size: .875rem; }
    .tabla-card thead tr { background: #fafafa; }
    .tabla-card th {
      padding: .8rem 1.1rem; text-align: left;
      font-size: .7rem; font-weight: 700; color: #999;
      text-transform: uppercase; letter-spacing: .07em;
      border-bottom: 1px solid #f0f0f0;
    }
    .tabla-card td {
      padding: .9rem 1.1rem;
      border-bottom: 1px solid #f7f7f7;
      color: #334155;
    }
    .tabla-card tbody tr:last-child td { border-bottom: none; }
    .tabla-card tbody tr:hover { background: #fff5f5; }
    .badge-ok {
      display: inline-flex; align-items: center; gap: 4px;
      background: #dcfce7; color: #14532d;
      font-size: .68rem; font-weight: 700;
      padding: .2rem .75rem; border-radius: 99px;
      text-transform: uppercase; letter-spacing: .04em;
    }
    .badge-ok::before { content:''; width:5px;height:5px;background:#16a34a;border-radius:50%; }
    .btn-cancel {
      font-size: .76rem; font-weight: 600; color: var(--red);
      background: #fff0f0; border: 1px solid #ffd0d0;
      padding: .3rem .85rem; border-radius: 6px;
      cursor: pointer; transition: background .15s;
    }
    .btn-cancel:hover { background: #ffe0e0; }

    .empty-state { text-align: center; padding: 3rem; color: var(--muted); }
    .empty-state a { color: var(--red); font-weight: 600; text-decoration: none; }

    /* ── FAB ──────────────────────────────────────────────────── */
    .fab {
      position: fixed; bottom: 2rem; right: 2rem;
      width: 54px; height: 54px;
      background: #0891b2;
      border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      box-shadow: 0 4px 20px rgba(8,145,178,.45);
      cursor: pointer; z-index: 999;
      transition: transform .2s, box-shadow .2s;
      text-decoration: none;
    }
    .fab:hover { transform: scale(1.08); box-shadow: 0 8px 28px rgba(8,145,178,.55); }

    /* ── FOOTER ───────────────────────────────────────────────── */
    .site-footer {
      background: var(--black);
      color: rgba(255,255,255,.4);
      text-align: center;
      padding: 1.5rem;
      font-size: .78rem;
    }

    /* ── ALERT ────────────────────────────────────────────────── */
    .alert { padding: .85rem 2rem; font-size: .875rem; }
    .alert-success { background: #f0fdf4; color: #14532d; border-bottom: 1px solid #86efac; }
    .alert-error   { background: #fef2f2; color: #7f1d1d; border-bottom: 1px solid #fca5a5; }

    /* ── RESPONSIVE ───────────────────────────────────────────── */
    @media(max-width:768px){
      .servicios-grid { grid-template-columns: repeat(2,1fr); }
      .nav-links { display: none; }
      .hero { height: 420px; }
      .reservas-section { padding: 2rem 1rem; }
    }
    @media(max-width:480px){
      .servicios-grid { grid-template-columns: repeat(2,1fr); gap: 1.25rem; }
      .stats-section { flex-direction: column; align-items: center; }
      .navbar { padding: 0 1rem; }
    }
  </style>
</head>
<body>

<?php if (isset($_SESSION['flash'])): ?>
  <div class="alert alert-<?= $_SESSION['flash']['tipo'] ?>">
    <?= htmlspecialchars($_SESSION['flash']['mensaje']) ?>
  </div>
  <?php unset($_SESSION['flash']); ?>
<?php endif; ?>

<!-- NAVBAR -->
<nav class="navbar">
  <div class="nav-left">
    <a href="/index.php" class="nav-logo-box">
      <div class="nav-logo-icon">
        <svg width="18" height="18" viewBox="0 0 18 18" fill="none">
          <rect x="2" y="2" width="6" height="6" rx="1.5" fill="white"/>
          <rect x="10" y="2" width="6" height="6" rx="1.5" fill="white" opacity=".6"/>
          <rect x="2" y="10" width="6" height="6" rx="1.5" fill="white" opacity=".6"/>
          <rect x="10" y="10" width="6" height="6" rx="1.5" fill="white"/>
        </svg>
      </div>
      <div class="nav-brand">
        Sistema de Reservas
        <span>Grupo 2 - Cloud · Módulo 2</span>
      </div>
    </a>
  </div>
  <div class="nav-right">
    <div class="nav-links">
      <a href="/index.php" class="nav-link active">Inicio</a>
      <a href="/reservas.php" class="nav-link">Mis Reservas</a>
      <a href="/recursos.php" class="nav-link">Recursos</a>
    </div>
    <div class="nav-user">
      <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
        <circle cx="8" cy="5" r="3" stroke="white" stroke-width="1.5"/>
        <path d="M2 14c0-3.314 2.686-5 6-5s6 1.686 6 5" stroke="white" stroke-width="1.5" stroke-linecap="round"/>
      </svg>
      <span style="color:rgba(255,255,255,.7);font-size:.82rem"><?= htmlspecialchars($usuario['nombre']) ?></span>
    </div>
    <a href="/nueva-reserva.php" class="nav-reservar">Reservar Sala</a>
    <a href="/logout.php" class="nav-link">
      <svg class="nav-hamburger" width="20" height="20" viewBox="0 0 20 20" fill="none">
        <path d="M3 5h14M3 10h14M3 15h14" stroke="white" stroke-width="1.5" stroke-linecap="round"/>
      </svg>
    </a>
  </div>
</nav>

<!-- BREADCRUMB -->
<div class="breadcrumb">
  <a href="/index.php">Inicio</a>
  <span class="sep">›</span>
  <span class="current">Dashboard</span>
</div>

<!-- HERO -->
<section class="hero">
  <div class="hero-content">
    <h1>Reserva tu espacio,<br>el lugar donde <span class="slash">todo</span> comienza</h1>
    <a href="/nueva-reserva.php" class="hero-cta">Reservar Sala</a>
  </div>
</section>

<!-- BIENVENIDA -->
<section class="bienvenida">
  <h2>Ven a disfrutar de las comodidades y beneficios que <span class="slash">Work/</span>Café tiene para ti, <?= htmlspecialchars($usuario['nombre']) ?></h2>
</section>

<!-- STATS -->
<div class="stats-section">
  <div class="stat-pill">
    <div class="stat-pill-icon">
      <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
        <rect x="3" y="4" width="14" height="13" rx="2" stroke="#EC0000" stroke-width="1.5"/>
        <path d="M6 2v4M14 2v4M3 8h14" stroke="#EC0000" stroke-width="1.5" stroke-linecap="round"/>
      </svg>
    </div>
    <div>
      <div class="stat-pill-num"><?= (int)$stats['activas'] ?></div>
      <div class="stat-pill-lbl">Activas</div>
    </div>
  </div>
  <div class="stat-pill">
    <div class="stat-pill-icon">
      <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
        <circle cx="10" cy="10" r="7" stroke="#EC0000" stroke-width="1.5"/>
        <path d="M6.5 10l2.5 2.5 4.5-4.5" stroke="#EC0000" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
    </div>
    <div>
      <div class="stat-pill-num"><?= (int)$stats['proximas'] ?></div>
      <div class="stat-pill-lbl">Próximas</div>
    </div>
  </div>
  <div class="stat-pill">
    <div class="stat-pill-icon">
      <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
        <circle cx="10" cy="10" r="7" stroke="#888" stroke-width="1.5"/>
        <path d="M7 7l6 6M13 7l-6 6" stroke="#888" stroke-width="1.5" stroke-linecap="round"/>
      </svg>
    </div>
    <div>
      <div class="stat-pill-num"><?= (int)$stats['canceladas'] ?></div>
      <div class="stat-pill-lbl">Canceladas</div>
    </div>
  </div>
</div>

<!-- SERVICIOS -->
<section class="servicios">
  <h3>Todas nuestras sucursales cuentan con:</h3>
  <div class="servicios-grid">
    <div class="servicio-item">
      <div class="servicio-icon">
        <svg width="26" height="26" viewBox="0 0 26 26" fill="none">
          <circle cx="9" cy="8" r="3" stroke="white" stroke-width="1.5"/>
          <circle cx="17" cy="8" r="3" stroke="white" stroke-width="1.5"/>
          <path d="M3 20c0-3 2.5-5 6-5h8c3.5 0 6 2 6 5" stroke="white" stroke-width="1.5" stroke-linecap="round"/>
        </svg>
      </div>
      <p>Espacios Cowork</p>
    </div>
    <div class="servicio-item">
      <div class="servicio-icon">
        <svg width="26" height="26" viewBox="0 0 26 26" fill="none">
          <path d="M8 10h10v6a4 4 0 01-4 4h-2a4 4 0 01-4-4v-6z" stroke="white" stroke-width="1.5"/>
          <path d="M18 12h2a2 2 0 010 4h-2" stroke="white" stroke-width="1.5"/>
          <path d="M13 6c0 0-2-2 0-4" stroke="white" stroke-width="1.5" stroke-linecap="round"/>
        </svg>
      </div>
      <p>Cafetería de especialidad</p>
    </div>
    <div class="servicio-item">
      <div class="servicio-icon">
        <svg width="26" height="26" viewBox="0 0 26 26" fill="none">
          <rect x="3" y="5" width="20" height="14" rx="2" stroke="white" stroke-width="1.5"/>
          <path d="M9 22h8M13 19v3" stroke="white" stroke-width="1.5" stroke-linecap="round"/>
        </svg>
      </div>
      <p>Salas de reuniones</p>
    </div>
    <div class="servicio-item">
      <div class="servicio-icon">
        <svg width="26" height="26" viewBox="0 0 26 26" fill="none">
          <path d="M5 12.5C5 8.36 8.69 5 13 5s8 3.36 8 7.5" stroke="white" stroke-width="1.5" stroke-linecap="round"/>
          <path d="M8 15.5c0-2.76 2.24-5 5-5s5 2.24 5 5" stroke="white" stroke-width="1.5" stroke-linecap="round"/>
          <circle cx="13" cy="19" r="1.5" fill="white"/>
        </svg>
      </div>
      <p>Wi-Fi gratuito</p>
    </div>
    <div class="servicio-item">
      <div class="servicio-icon">
        <svg width="26" height="26" viewBox="0 0 26 26" fill="none">
          <rect x="9" y="3" width="8" height="12" rx="2" stroke="white" stroke-width="1.5"/>
          <path d="M13 15v5M10 20h6" stroke="white" stroke-width="1.5" stroke-linecap="round"/>
          <path d="M11 7v4M15 7v4" stroke="white" stroke-width="1.5" stroke-linecap="round"/>
        </svg>
      </div>
      <p>Enchufes disponibles</p>
    </div>
    <div class="servicio-item">
      <div class="servicio-icon">
        <svg width="26" height="26" viewBox="0 0 26 26" fill="none">
          <rect x="3" y="7" width="20" height="13" rx="2" stroke="white" stroke-width="1.5"/>
          <path d="M3 11h20" stroke="white" stroke-width="1.5"/>
          <path d="M7 15h4" stroke="white" stroke-width="1.5" stroke-linecap="round"/>
        </svg>
      </div>
      <p>Descuentos especiales</p>
    </div>
    <div class="servicio-item">
      <div class="servicio-icon">
        <svg width="26" height="26" viewBox="0 0 26 26" fill="none">
          <circle cx="13" cy="13" r="9" stroke="white" stroke-width="1.5"/>
          <path d="M13 8v5l3 3" stroke="white" stroke-width="1.5" stroke-linecap="round"/>
        </svg>
      </div>
      <p>Horario extendido</p>
    </div>
    <div class="servicio-item">
      <div class="servicio-icon">
        <svg width="26" height="26" viewBox="0 0 26 26" fill="none">
          <rect x="5" y="3" width="16" height="20" rx="2" stroke="white" stroke-width="1.5"/>
          <path d="M9 8h8M9 12h8M9 16h5" stroke="white" stroke-width="1.5" stroke-linecap="round"/>
        </svg>
      </div>
      <p>Reserva en línea</p>
    </div>
  </div>
</section>

<!-- MIS RESERVAS -->
<section class="reservas-section">
  <div class="sec-header">
    <div class="sec-title">Mis próximas reservas</div>
    <a href="/reservas.php" class="ver-todas">Ver todas →</a>
  </div>
  <div class="tabla-card">
    <?php if (empty($proximas)): ?>
      <div class="empty-state">
        <p>No tienes reservas próximas.</p>
        <a href="/nueva-reserva.php">Reservar ahora →</a>
      </div>
    <?php else: ?>
      <table>
        <thead>
          <tr>
            <th>Recurso</th><th>Inicio</th><th>Fin</th><th>Estado</th><th>Notas</th><th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($proximas as $r): ?>
            <tr>
              <td style="font-weight:700;color:#111"><?= htmlspecialchars($r['recurso_nombre']) ?></td>
              <td><?= date('d/m/Y H:i', strtotime($r['fecha_inicio'])) ?></td>
              <td><?= date('d/m/Y H:i', strtotime($r['fecha_fin'])) ?></td>
              <td><span class="badge-ok">Confirmada</span></td>
              <td style="color:#999;font-size:.82rem"><?= htmlspecialchars($r['notas'] ?? '—') ?></td>
              <td>
                <form method="POST" action="/reservas.php" style="display:inline">
                  <input type="hidden" name="accion" value="cancelar">
                  <input type="hidden" name="id" value="<?= $r['id'] ?>">
                  <button type="submit" class="btn-cancel btn-cancelar">Cancelar</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</section>

<!-- FOOTER -->
<footer class="site-footer">
  Sistema de Reservas v1.0.0 &mdash; Grupo 2 - Cloud Architecture 2026
</footer>

<!-- FAB -->
<a href="/nueva-reserva.php" class="fab" title="Nueva reserva">
  <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
    <rect x="5" y="2" width="14" height="18" rx="2" stroke="white" stroke-width="1.5"/>
    <path d="M9 7h6M9 11h6M9 15h4" stroke="white" stroke-width="1.5" stroke-linecap="round"/>
    <circle cx="18" cy="18" r="4" fill="#0891b2"/>
    <path d="M16 18h4M18 16v4" stroke="white" stroke-width="1.5" stroke-linecap="round"/>
  </svg>
</a>

<script>
document.querySelectorAll('.btn-cancelar').forEach(btn => {
  btn.addEventListener('click', e => {
    if (!confirm('¿Confirmas que deseas cancelar esta reserva?')) e.preventDefault();
  });
});
</script>
</body>
</html>
