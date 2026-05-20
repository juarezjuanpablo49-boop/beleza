<?php
require_once 'config.php';
$user = requireLogin('cliente');
$iniciales     = strtoupper(substr($user['nombre'],0,1) . substr($user['apellido'],0,1));
$nombreDisplay = htmlspecialchars($user['nombre']);
$hoy           = date('Y-m-d');
$diaSemana     = date('N'); // 1=Lun … 7=Dom
$esPromoDay    = in_array($diaSemana, [3, 5, 7]); // Mié, Vie, Dom
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Beleza — Mi cuenta</title>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/cliente.css">
<style>
/* ============================================================
   LOGO en navbar
   ============================================================ */
.nav-logo img {
  height: 34px;
  width: auto;
  object-fit: contain;
  display: block;
}

/* ============================================================
   CARRUSEL DE PROMOCIONES
   ============================================================ */
.promo-section {
  margin-bottom: 36px;
}

.promo-label {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 14px;
}
.promo-label-text {
  font-family: 'Cormorant Garamond', serif;
  font-size: 20px;
  font-weight: 400;
  color: var(--ink);
}
.promo-badge-day {
  background: var(--rose);
  color: #fff;
  font-size: 10px;
  font-weight: 600;
  letter-spacing: 1.2px;
  text-transform: uppercase;
  padding: 3px 10px;
  border-radius: 20px;
}

.carousel-wrap {
  position: relative;
  overflow: hidden;
  border-radius: 16px;
  background: var(--dark);
  height: 340px;
  box-shadow: 0 12px 40px rgba(0,0,0,.18);
}

.carousel-track {
  display: flex;
  height: 100%;
  transition: transform .7s cubic-bezier(.77,0,.18,1);
  will-change: transform;
}

.carousel-slide {
  min-width: 100%;
  height: 100%;
  position: relative;
  overflow: hidden;
  display: flex;
  align-items: center;
}

.carousel-slide img {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
  object-position: center top;
  filter: brightness(.55) saturate(1.1);
  transition: transform 8s linear;
}

.carousel-slide.active img {
  transform: scale(1.06);
}

.carousel-overlay {
  position: relative;
  z-index: 2;
  padding: 36px 44px;
  max-width: 520px;
}

.promo-dia {
  font-size: 10px;
  letter-spacing: 3px;
  text-transform: uppercase;
  color: var(--gold);
  margin-bottom: 10px;
}

.promo-titulo {
  font-family: 'Cormorant Garamond', serif;
  font-size: 36px;
  font-weight: 400;
  color: #fff;
  line-height: 1.15;
  margin-bottom: 8px;
}

.promo-desc {
  font-size: 13px;
  color: rgba(255,255,255,.55);
  margin-bottom: 18px;
  line-height: 1.6;
}

.promo-descuento {
  display: inline-flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 20px;
}

.promo-pct {
  font-family: 'Cormorant Garamond', serif;
  font-size: 52px;
  font-weight: 600;
  color: var(--gold);
  line-height: 1;
}

.promo-pct-label {
  font-size: 12px;
  color: rgba(255,255,255,.45);
  line-height: 1.4;
}

.promo-cta {
  display: inline-block;
  background: var(--rose);
  color: #fff;
  border: none;
  border-radius: 8px;
  padding: 11px 22px;
  font-size: 13px;
  font-weight: 500;
  font-family: 'DM Sans', sans-serif;
  cursor: pointer;
  transition: background .15s, transform .12s;
}
.promo-cta:hover { background: var(--rose-dk); transform: translateY(-1px); }

/* Dots */
.carousel-dots {
  position: absolute;
  bottom: 18px;
  left: 50%;
  transform: translateX(-50%);
  display: flex;
  gap: 7px;
  z-index: 5;
}
.c-dot {
  width: 7px; height: 7px;
  border-radius: 50%;
  background: rgba(255,255,255,.3);
  cursor: pointer;
  transition: all .25s;
  border: none;
  padding: 0;
}
.c-dot.active {
  background: var(--gold);
  width: 22px;
  border-radius: 4px;
}

/* Arrows */
.carousel-arrow {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  z-index: 5;
  background: rgba(255,255,255,.12);
  border: 1px solid rgba(255,255,255,.18);
  color: #fff;
  width: 38px; height: 38px;
  border-radius: 50%;
  cursor: pointer;
  font-size: 18px;
  display: flex; align-items: center; justify-content: center;
  transition: background .15s;
  backdrop-filter: blur(4px);
}
.carousel-arrow:hover { background: rgba(255,255,255,.22); }
.carousel-arrow.prev { left: 16px; }
.carousel-arrow.next { right: 16px; }

/* Ribbon "HOY" cuando es día de promo */
.promo-hoy-ribbon {
  position: absolute;
  top: 18px; right: 18px;
  background: var(--gold);
  color: var(--dark);
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 2px;
  text-transform: uppercase;
  padding: 5px 14px;
  border-radius: 20px;
  z-index: 6;
  box-shadow: 0 2px 12px rgba(0,0,0,.25);
  animation: pulse-ribbon 2.5s ease-in-out infinite;
}
@keyframes pulse-ribbon {
  0%,100% { opacity:1; transform:scale(1); }
  50%      { opacity:.85; transform:scale(1.04); }
}

/* ============================================================
   SERVICIOS con imagen (vista inicio — galería rápida)
   ============================================================ */
.srv-preview-section {
  margin-bottom: 36px;
}
.srv-preview-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(170px, 1fr));
  gap: 12px;
}
.srv-preview-card {
  background: var(--white);
  border: 1px solid var(--line);
  border-radius: 12px;
  overflow: hidden;
  cursor: pointer;
  transition: box-shadow .15s, transform .15s;
}
.srv-preview-card:hover {
  box-shadow: 0 6px 20px rgba(0,0,0,.09);
  transform: translateY(-2px);
}
.srv-preview-img {
  width: 100%;
  height: 110px;
  object-fit: cover;
  object-position: center;
  display: block;
}
.srv-preview-body {
  padding: 10px 12px;
}
.srv-preview-name {
  font-size: 12px;
  font-weight: 500;
  color: var(--ink);
  margin-bottom: 2px;
}
.srv-preview-cat {
  font-size: 10px;
  color: var(--sub);
}

/* Inventario con imagen */
.inv-img {
  width: 44px; height: 44px;
  object-fit: cover;
  border-radius: 8px;
  margin-right: 10px;
  flex-shrink: 0;
}

@media (max-width: 680px) {
  .carousel-wrap  { height: 260px; }
  .promo-titulo   { font-size: 26px; }
  .promo-pct      { font-size: 40px; }
  .carousel-overlay { padding: 22px 22px; }
  .srv-preview-grid { grid-template-columns: repeat(auto-fill, minmax(130px,1fr)); }
}
</style>
</head>
<body>

<!-- TOP NAV -->
<header class="nav">
  <div class="nav-logo" style="margin-right:28px">
    <!-- LOGO -->
    <img src="logo.avif" alt="Beleza" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
    <!-- Fallback texto si logo no carga -->
    <span style="display:none;align-items:baseline;gap:1px">
      <span class="nav-logo-letter">B</span>
      <span class="nav-logo-text">eleza</span>
    </span>
  </div>
  <div class="nav-links" id="nav-links">
    <button class="nav-link active" onclick="showView('inicio',this)">Inicio</button>
    <button class="nav-link" onclick="showView('agendar',this)">Agendar</button>
    <button class="nav-link" onclick="showView('historial',this)">Mis citas</button>
    <button class="nav-link" onclick="showView('perfil',this)">Perfil</button>
  </div>
  <div class="nav-user">
    <div class="nav-avatar"><?= $iniciales ?></div>
    <span class="nav-name"><?= $nombreDisplay ?></span>
    <button class="nav-logout" onclick="doLogout()">Salir</button>
  </div>
</header>

<main class="main">

  <!-- ========== INICIO ========== -->
  <section class="view active" id="view-inicio">
    <div class="hero">
      <div class="hero-text">
        <div class="hero-greeting">Hola, <?= $nombreDisplay ?></div>
        <h1 class="hero-title">¿Lista para tu próxima visita?</h1>
        <p class="hero-sub">Agenda tu cita en minutos y olvídate del resto.</p>
        <button class="btn-book" onclick="showView('agendar',document.querySelectorAll('.nav-link')[1])">
          Agendar cita →
        </button>
      </div>
      <div class="hero-deco">
        <div class="hero-ring r1"></div>
        <div class="hero-ring r2"></div>
        <!-- Logo en el hero decorativo -->
        <img src="logo.avif" alt="Beleza"
             style="position:absolute;inset:0;width:100%;height:100%;object-fit:contain;opacity:.18;filter:invert(1)"
             onerror="this.style.display='none'">
        <div class="hero-letter" id="hero-letter-fallback">B</div>
      </div>
    </div>

    <!-- ===== CARRUSEL PROMOCIONES ===== -->
    <div class="promo-section">
      <div class="promo-label">
        <span class="promo-label-text">Promociones especiales</span>
        <?php if ($esPromoDay): ?>
          <span class="promo-badge-day">¡Válido hoy!</span>
        <?php else: ?>
          <span class="promo-badge-day" style="background:var(--gold);color:var(--dark)">Miér · Vier · Dom</span>
        <?php endif; ?>
      </div>

      <div class="carousel-wrap" id="promo-carousel">
        <?php if ($esPromoDay): ?>
          <div class="promo-hoy-ribbon">🌟 Promoción activa hoy</div>
        <?php endif; ?>

        <div class="carousel-track" id="carousel-track">

          <!-- Slide 1: Corte de cabello — Miércoles -->
          <div class="carousel-slide active">
            <img src="cortecabello.jpg" alt="Corte de cabello">
            <div class="carousel-overlay">
              <div class="promo-dia">Miércoles de belleza</div>
              <div class="promo-titulo">Corte de cabello<br>+ acabado</div>
              <div class="promo-desc">Renueva tu look con nuestras estilistas profesionales. Incluye lavado y secado.</div>
              <div class="promo-descuento">
                <span class="promo-pct">20%</span>
                <span class="promo-pct-label">de<br>descuento</span>
              </div>
              <button class="promo-cta" onclick="showView('agendar',document.querySelectorAll('.nav-link')[1])">Agendar ahora</button>
            </div>
          </div>

          <!-- Slide 2: Manicure semipermanente — Viernes -->
          <div class="carousel-slide">
            <img src="manicuresemiper.jfif" alt="Manicure semipermanente">
            <div class="carousel-overlay">
              <div class="promo-dia">Viernes de manos perfectas</div>
              <div class="promo-titulo">Manicure<br>semipermanente</div>
              <div class="promo-desc">Uñas impecables que duran hasta 3 semanas. Base incluida.</div>
              <div class="promo-descuento">
                <span class="promo-pct">15%</span>
                <span class="promo-pct-label">de<br>descuento</span>
              </div>
              <button class="promo-cta" onclick="showView('agendar',document.querySelectorAll('.nav-link')[1])">Agendar ahora</button>
            </div>
          </div>

          <!-- Slide 3: Facial básico — Domingo -->
          <div class="carousel-slide">
            <img src="facialbasico.jfif" alt="Facial básico">
            <div class="carousel-overlay">
              <div class="promo-dia">Domingo de bienestar</div>
              <div class="promo-titulo">Facial básico<br>rejuvenecedor</div>
              <div class="promo-desc">Limpieza profunda, exfoliación e hidratación. Tu piel lo agradecerá.</div>
              <div class="promo-descuento">
                <span class="promo-pct">25%</span>
                <span class="promo-pct-label">de<br>descuento</span>
              </div>
              <button class="promo-cta" onclick="showView('agendar',document.querySelectorAll('.nav-link')[1])">Agendar ahora</button>
            </div>
          </div>

          <!-- Slide 4: Mechas / Highlights — Viernes -->
          <div class="carousel-slide">
            <img src="mechas.jfif" alt="Mechas Highlights">
            <div class="carousel-overlay">
              <div class="promo-dia">Viernes con brillo</div>
              <div class="promo-titulo">Mechas &<br>Highlights</div>
              <div class="promo-desc">Ilumina tu cabello con las técnicas más modernas de coloración parcial.</div>
              <div class="promo-descuento">
                <span class="promo-pct">20%</span>
                <span class="promo-pct-label">de<br>descuento</span>
              </div>
              <button class="promo-cta" onclick="showView('agendar',document.querySelectorAll('.nav-link')[1])">Agendar ahora</button>
            </div>
          </div>

          <!-- Slide 5: Pedicure spa — Domingo -->
          <div class="carousel-slide">
            <img src="pedicure.jpg" alt="Pedicure spa">
            <div class="carousel-overlay">
              <div class="promo-dia">Domingo de lujo</div>
              <div class="promo-titulo">Pedicure spa<br>completo</div>
              <div class="promo-desc">Relájate con nuestro pedicure spa. Exfoliación, masaje y esmaltado incluidos.</div>
              <div class="promo-descuento">
                <span class="promo-pct">15%</span>
                <span class="promo-pct-label">de<br>descuento</span>
              </div>
              <button class="promo-cta" onclick="showView('agendar',document.querySelectorAll('.nav-link')[1])">Agendar ahora</button>
            </div>
          </div>

          <!-- Slide 6: Maquillaje social — Miércoles -->
          <div class="carousel-slide">
            <img src="social.jfif" alt="Maquillaje social">
            <div class="carousel-overlay">
              <div class="promo-dia">Miércoles glamour</div>
              <div class="promo-titulo">Maquillaje<br>social</div>
              <div class="promo-desc">Luce radiante en cualquier evento. Maquillaje profesional a la mitad del precio.</div>
              <div class="promo-descuento">
                <span class="promo-pct">20%</span>
                <span class="promo-pct-label">de<br>descuento</span>
              </div>
              <button class="promo-cta" onclick="showView('agendar',document.querySelectorAll('.nav-link')[1])">Agendar ahora</button>
            </div>
          </div>

        </div><!-- /carousel-track -->

        <!-- Flechas -->
        <button class="carousel-arrow prev" onclick="carouselMove(-1)">&#8249;</button>
        <button class="carousel-arrow next" onclick="carouselMove(1)">&#8250;</button>

        <!-- Dots -->
        <div class="carousel-dots" id="carousel-dots"></div>
      </div><!-- /carousel-wrap -->
    </div><!-- /promo-section -->

    <!-- ===== GALERÍA RÁPIDA DE SERVICIOS ===== -->
    <div class="srv-preview-section">
      <div class="section-label">Nuestros servicios</div>
      <div class="srv-preview-grid">
        <div class="srv-preview-card" onclick="showView('agendar',document.querySelectorAll('.nav-link')[1])">
          <img class="srv-preview-img" src="cortecabello.jpg" alt="Corte de cabello">
          <div class="srv-preview-body"><div class="srv-preview-name">Corte de cabello</div><div class="srv-preview-cat">Cabello</div></div>
        </div>
        <div class="srv-preview-card" onclick="showView('agendar',document.querySelectorAll('.nav-link')[1])">
          <img class="srv-preview-img" src="mechas.jfif" alt="Mechas">
          <div class="srv-preview-body"><div class="srv-preview-name">Mechas / Highlights</div><div class="srv-preview-cat">Coloración</div></div>
        </div>
        <div class="srv-preview-card" onclick="showView('agendar',document.querySelectorAll('.nav-link')[1])">
          <img class="srv-preview-img" src="tintecastaño.webp" alt="Tinte">
          <div class="srv-preview-body"><div class="srv-preview-name">Tinte castaño oscuro</div><div class="srv-preview-cat">Coloración</div></div>
        </div>
        <div class="srv-preview-card" onclick="showView('agendar',document.querySelectorAll('.nav-link')[1])">
          <img class="srv-preview-img" src="Keratina.jfif" alt="Keratina">
          <div class="srv-preview-body"><div class="srv-preview-name">Keratina</div><div class="srv-preview-cat">Tratamiento</div></div>
        </div>
        <div class="srv-preview-card" onclick="showView('agendar',document.querySelectorAll('.nav-link')[1])">
          <img class="srv-preview-img" src="manicureclasic.jfif" alt="Manicure clásico">
          <div class="srv-preview-body"><div class="srv-preview-name">Manicure clásico</div><div class="srv-preview-cat">Uñas</div></div>
        </div>
        <div class="srv-preview-card" onclick="showView('agendar',document.querySelectorAll('.nav-link')[1])">
          <img class="srv-preview-img" src="manicuresemiper.jfif" alt="Semipermanente">
          <div class="srv-preview-body"><div class="srv-preview-name">Manicure semipermanente</div><div class="srv-preview-cat">Uñas</div></div>
        </div>
        <div class="srv-preview-card" onclick="showView('agendar',document.querySelectorAll('.nav-link')[1])">
          <img class="srv-preview-img" src="pedicure.jpg" alt="Pedicure spa">
          <div class="srv-preview-body"><div class="srv-preview-name">Pedicure spa</div><div class="srv-preview-cat">Uñas</div></div>
        </div>
        <div class="srv-preview-card" onclick="showView('agendar',document.querySelectorAll('.nav-link')[1])">
          <img class="srv-preview-img" src="facialbasico.jfif" alt="Facial básico">
          <div class="srv-preview-body"><div class="srv-preview-name">Facial básico</div><div class="srv-preview-cat">Faciales</div></div>
        </div>
        <div class="srv-preview-card" onclick="showView('agendar',document.querySelectorAll('.nav-link')[1])">
          <img class="srv-preview-img" src="depilacioncejas.jpg" alt="Depilación cejas">
          <div class="srv-preview-body"><div class="srv-preview-name">Depilación cejas</div><div class="srv-preview-cat">Depilación</div></div>
        </div>
        <div class="srv-preview-card" onclick="showView('agendar',document.querySelectorAll('.nav-link')[1])">
          <img class="srv-preview-img" src="depilacionlabios.jfif" alt="Depilación labio">
          <div class="srv-preview-body"><div class="srv-preview-name">Depilación labio</div><div class="srv-preview-cat">Depilación</div></div>
        </div>
        <div class="srv-preview-card" onclick="showView('agendar',document.querySelectorAll('.nav-link')[1])">
          <img class="srv-preview-img" src="social.jfif" alt="Maquillaje social">
          <div class="srv-preview-body"><div class="srv-preview-name">Maquillaje social</div><div class="srv-preview-cat">Maquillaje</div></div>
        </div>
        <div class="srv-preview-card" onclick="showView('agendar',document.querySelectorAll('.nav-link')[1])">
          <img class="srv-preview-img" src="maquillajenovia.jfif" alt="Maquillaje de novia">
          <div class="srv-preview-body"><div class="srv-preview-name">Maquillaje de novia</div><div class="srv-preview-cat">Maquillaje</div></div>
        </div>
        <div class="srv-preview-card" onclick="showView('agendar',document.querySelectorAll('.nav-link')[1])">
          <img class="srv-preview-img" src="aco.jfif" alt="Acondicionador">
          <div class="srv-preview-body"><div class="srv-preview-name">Acondicionador</div><div class="srv-preview-cat">Tratamiento</div></div>
        </div>
        <div class="srv-preview-card" onclick="showView('agendar',document.querySelectorAll('.nav-link')[1])">
          <img class="srv-preview-img" src="base.jfif" alt="Base de maquillaje">
          <div class="srv-preview-body"><div class="srv-preview-name">Base de maquillaje</div><div class="srv-preview-cat">Maquillaje</div></div>
        </div>
        <div class="srv-preview-card" onclick="showView('agendar',document.querySelectorAll('.nav-link')[1])">
          <img class="srv-preview-img" src="cera.jfif" alt="Depilación con cera">
          <div class="srv-preview-body"><div class="srv-preview-name">Depilación con cera</div><div class="srv-preview-cat">Depilación</div></div>
        </div>
        <div class="srv-preview-card" onclick="showView('agendar',document.querySelectorAll('.nav-link')[1])">
          <img class="srv-preview-img" src="desmaq.jfif" alt="Desmaquillante">
          <div class="srv-preview-body"><div class="srv-preview-name">Desmaquillante</div><div class="srv-preview-cat">Facial</div></div>
        </div>
        <div class="srv-preview-card" onclick="showView('agendar',document.querySelectorAll('.nav-link')[1])">
          <img class="srv-preview-img" src="esmalte.jfif" alt="Esmalte">
          <div class="srv-preview-body"><div class="srv-preview-name">Esmalte</div><div class="srv-preview-cat">Uñas</div></div>
        </div>
        <div class="srv-preview-card" onclick="showView('agendar',document.querySelectorAll('.nav-link')[1])">
          <img class="srv-preview-img" src="esmalterojo.jfif" alt="Esmalte rojo">
          <div class="srv-preview-body"><div class="srv-preview-name">Esmalte rojo</div><div class="srv-preview-cat">Uñas</div></div>
        </div>
        <div class="srv-preview-card" onclick="showView('agendar',document.querySelectorAll('.nav-link')[1])">
          <img class="srv-preview-img" src="shampoopro.webp" alt="Shampoo profesional">
          <div class="srv-preview-body"><div class="srv-preview-name">Shampoo profesional</div><div class="srv-preview-cat">Cabello</div></div>
        </div>
        <div class="srv-preview-card" onclick="showView('agendar',document.querySelectorAll('.nav-link')[1])">
          <img class="srv-preview-img" src="tintecompl.jfif" alt="Tinte completo">
          <div class="srv-preview-body"><div class="srv-preview-name">Tinte completo</div><div class="srv-preview-cat">Coloración</div></div>
        </div>
        <div class="srv-preview-card" onclick="showView('agendar',document.querySelectorAll('.nav-link')[1])">
          <img class="srv-preview-img" src="tinterubio.jfif" alt="Tinte rubio">
          <div class="srv-preview-body"><div class="srv-preview-name">Tinte rubio</div><div class="srv-preview-cat">Coloración</div></div>
        </div>
        <div class="srv-preview-card" onclick="showView('agendar',document.querySelectorAll('.nav-link')[1])">
          <img class="srv-preview-img" src="tratamientpkera.jpg" alt="Tratamiento keratina">
          <div class="srv-preview-body"><div class="srv-preview-name">Tratamiento keratina</div><div class="srv-preview-cat">Tratamiento</div></div>
        </div>
      </div>
    </div>

    <!-- ===== PRÓXIMAS CITAS ===== -->
    <div class="section-label">Próximas citas</div>
    <div class="proximas-grid" id="proximas-grid"></div>
  </section>

  <!-- ========== AGENDAR ========== -->
  <section class="view" id="view-agendar">
    <div class="wizard-header">
      <h2>Agendar cita</h2>
      <div class="steps-indicator">
        <div class="step-dot active" id="dot-1">1</div>
        <div class="step-line" id="line-12"></div>
        <div class="step-dot" id="dot-2">2</div>
        <div class="step-line" id="line-23"></div>
        <div class="step-dot" id="dot-3">3</div>
      </div>
    </div>

    <div class="wizard-body">

      <!-- Paso 1: Servicio -->
      <div class="step active" id="step-1">
        <p class="step-hint">Elige el servicio que deseas:</p>
        <div class="srv-grid" id="srv-grid"></div>
        <div class="step-nav">
          <div></div>
          <button class="btn-book" id="btn-paso2" onclick="goStep(2)" disabled>Siguiente →</button>
        </div>
      </div>

      <!-- Paso 2: Fecha y hora -->
      <div class="step" id="step-2">
        <div class="cal-slots-layout">
          <div class="mini-cal">
            <div class="mini-cal-head">
              <span class="mini-cal-month" id="cli-cal-month"></span>
              <div class="mini-cal-nav">
                <button onclick="cliChangeMonth(-1)">&#8249;</button>
                <button onclick="cliChangeMonth(1)">&#8250;</button>
              </div>
            </div>
            <div class="mini-cal-wd">
              <span>D</span><span>L</span><span>M</span><span>Mi</span><span>J</span><span>V</span><span>S</span>
            </div>
            <div id="cli-cal-grid"></div>
          </div>
          <div class="slots-panel">
            <div class="slots-title" id="slots-title">Selecciona una fecha</div>
            <div class="slots-grid" id="cli-slots"></div>
            <input type="hidden" id="sel-hora">
          </div>
        </div>
        <div class="step-nav">
          <button class="btn-outline" onclick="goStep(1)">← Atrás</button>
          <button class="btn-book" id="btn-paso3" onclick="goStep(3)" disabled>Siguiente →</button>
        </div>
      </div>

      <!-- Paso 3: Confirmación -->
      <div class="step" id="step-3">
        <p class="step-hint">Revisa tu cita antes de confirmar:</p>
        <div class="resumen">
          <div class="resumen-row"><span class="r-label">Servicio</span><span id="res-servicio">—</span></div>
          <div class="resumen-row"><span class="r-label">Fecha</span><span id="res-fecha">—</span></div>
          <div class="resumen-row"><span class="r-label">Hora</span><span id="res-hora">—</span></div>
          <div class="resumen-row"><span class="r-label">Duración</span><span id="res-dur">—</span></div>
          <div class="resumen-row total"><span class="r-label">Total</span><span id="res-precio">—</span></div>
        </div>
        <div class="field" style="margin-top:16px">
          <label>Notas adicionales (opcional)</label>
          <input class="field-input" type="text" id="res-notas" placeholder="Alergias, preferencias, etc.">
        </div>
        <div class="step-nav">
          <button class="btn-outline" onclick="goStep(2)">← Atrás</button>
          <button class="btn-book" onclick="confirmarCita()">✓ Confirmar cita</button>
        </div>
      </div>

    </div>
  </section>

  <!-- ========== HISTORIAL ========== -->
  <section class="view" id="view-historial">
    <div class="section-label">Mis citas</div>
    <div class="hist-wrap">
      <table class="hist-table">
        <thead>
          <tr><th>Fecha</th><th>Hora</th><th>Servicio</th><th>Empleada</th><th>Estado</th><th>Total</th><th></th></tr>
        </thead>
        <tbody id="hist-tbody"></tbody>
      </table>
    </div>
  </section>

  <!-- ========== PERFIL ========== -->
  <section class="view" id="view-perfil">
    <div class="section-label">Mi perfil</div>
    <div class="perfil-card">
      <div class="perfil-avatar"><?= $iniciales ?></div>
      <div class="field">
        <label>Nombre</label>
        <input class="field-input" id="prf-nombre" value="<?= htmlspecialchars($user['nombre']) ?>">
      </div>
      <div class="field">
        <label>Apellido</label>
        <input class="field-input" id="prf-apellido" value="<?= htmlspecialchars($user['apellido']) ?>">
      </div>
      <div class="field">
        <label>Teléfono</label>
        <input class="field-input" type="tel" id="prf-tel" value="<?= htmlspecialchars($user['telefono'] ?? '') ?>">
      </div>
      <div class="field">
        <label>Correo (no editable)</label>
        <input class="field-input" value="<?= htmlspecialchars($user['email']) ?>" disabled>
      </div>
      <div class="perfil-actions">
        <button class="btn-book" onclick="guardarPerfil()">Guardar cambios</button>
        <button class="btn-logout-big" onclick="doLogout()">Cerrar sesión</button>
      </div>
    </div>
  </section>

</main>

<div class="toast" id="toast"></div>

<script>
  window.BELEZA = {
    hoy:    '<?= $hoy ?>',
    api:    'api.php',
    nombre: '<?= $nombreDisplay ?>'
  };

  // ============================================================
  // CARRUSEL
  // ============================================================
  (function () {
    const track  = document.getElementById('carousel-track');
    const slides = track ? track.querySelectorAll('.carousel-slide') : [];
    const dotsEl = document.getElementById('carousel-dots');
    let current  = 0;
    let timer    = null;

    function buildDots() {
      dotsEl.innerHTML = '';
      slides.forEach((_, i) => {
        const d = document.createElement('button');
        d.className = 'c-dot' + (i === 0 ? ' active' : '');
        d.onclick = () => goTo(i);
        dotsEl.appendChild(d);
      });
    }

    function goTo(n) {
      slides[current].classList.remove('active');
      dotsEl.children[current].classList.remove('active');
      current = (n + slides.length) % slides.length;
      slides[current].classList.add('active');
      dotsEl.children[current].classList.add('active');
      track.style.transform = `translateX(-${current * 100}%)`;
      resetTimer();
    }

    function resetTimer() {
      clearInterval(timer);
      timer = setInterval(() => goTo(current + 1), 5500);
    }

    window.carouselMove = function (dir) { goTo(current + dir); };

    if (slides.length > 0) {
      buildDots();
      resetTimer();
      // Pausar al hover
      const wrap = document.getElementById('promo-carousel');
      wrap.addEventListener('mouseenter', () => clearInterval(timer));
      wrap.addEventListener('mouseleave', resetTimer);
    }
  })();
</script>
<script src="js/cliente.js"></script>
</body>
</html>
