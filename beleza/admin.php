<?php
require_once 'config.php';
$user = requireLogin('admin');
$initials = strtoupper(substr($user['nombre'],0,1) . substr($user['apellido'],0,1));
$nombreCompleto = htmlspecialchars($user['nombre'] . ' ' . $user['apellido']);
$hoy = date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Beleza — Panel Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;1,400&family=Manrope:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/admin.css">
</head>
<body>

<!-- SIDEBAR -->
<aside class="sidebar" id="sidebar">
  <div class="sb-top">
    <div class="sb-brand">
      <span class="sb-brand-letter">B</span>
      <div>
        <div class="sb-brand-name">Beleza</div>
        <div class="sb-brand-sub">Panel Admin</div>
      </div>
    </div>
  </div>

  <nav class="sb-nav">
    <div class="sb-section">General</div>
    <button class="sb-item active" onclick="showView('dashboard',this)" data-view="dashboard">
      <svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg>
      Dashboard
    </button>
    <button class="sb-item" onclick="showView('agenda',this)" data-view="agenda">
      <svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="16" y1="2" x2="16" y2="6"/></svg>
      Agenda
    </button>
    <div class="sb-section">Gestión</div>
    <button class="sb-item" onclick="showView('clientes',this)" data-view="clientes">
      <svg viewBox="0 0 24 24"><circle cx="9" cy="7" r="4"/><path d="M3 21v-2a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2"/><path d="M16 3.13a4 4 0 0 1 0 7.75M21 21v-2a4 4 0 0 0-3-3.87"/></svg>
      Clientes
    </button>
    <button class="sb-item" onclick="showView('servicios',this)" data-view="servicios">
      <svg viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
      Servicios
    </button>
    <button class="sb-item" onclick="showView('empleadas',this)" data-view="empleadas">
      <svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>
      Empleadas
    </button>
    <button class="sb-item" onclick="showView('inventario',this)" data-view="inventario">
      <svg viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
      Inventario
    </button>
    <div class="sb-section">Análisis</div>
    <button class="sb-item" onclick="showView('reportes',this)" data-view="reportes">
      <svg viewBox="0 0 24 24"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
      Reportes
    </button>
  </nav>

  <div class="sb-footer">
    <div class="sb-user">
      <div class="sb-avatar"><?= $initials ?></div>
      <div class="sb-user-info">
        <div class="sb-user-name"><?= $nombreCompleto ?></div>
        <div class="sb-user-role">Administrador</div>
      </div>
    </div>
    <button class="sb-logout" onclick="doLogout()">Cerrar sesión →</button>
  </div>
</aside>

<!-- MAIN -->
<main class="main">
  <header class="topbar">
    <div class="topbar-left">
      <button class="hamburger" onclick="toggleSidebar()" id="hamburger">
        <span></span><span></span><span></span>
      </button>
      <h1 class="page-title" id="page-title">Dashboard</h1>
    </div>
    <div class="topbar-right">
      <button class="btn-primary" onclick="openModal('nueva-cita')">+ Nueva cita</button>
    </div>
  </header>

  <div class="content">

    <!-- ===== DASHBOARD ===== -->
    <section class="view active" id="view-dashboard">
      <div class="stats-row">
        <div class="stat-card">
          <div class="stat-icon stat-icon-rose">
            <svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="16" y1="2" x2="16" y2="6"/></svg>
          </div>
          <div class="stat-info">
            <div class="stat-val" id="stat-hoy">—</div>
            <div class="stat-label">Citas hoy</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon stat-icon-gold">
            <svg viewBox="0 0 24 24"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
          </div>
          <div class="stat-info">
            <div class="stat-val" id="stat-ingresos">—</div>
            <div class="stat-label">Ingresos del mes</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon stat-icon-blue">
            <svg viewBox="0 0 24 24"><circle cx="9" cy="7" r="4"/><path d="M3 21v-2a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2"/></svg>
          </div>
          <div class="stat-info">
            <div class="stat-val" id="stat-clientes">—</div>
            <div class="stat-label">Clientes activos</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon stat-icon-green">
            <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
          </div>
          <div class="stat-info">
            <div class="stat-val" id="stat-servicios">—</div>
            <div class="stat-label">Servicios completados</div>
          </div>
        </div>
      </div>

      <div class="dash-grid">
        <div class="card card-chart">
          <div class="card-head">
            <h3>Ingresos mensuales <span class="card-sub">MXN</span></h3>
          </div>
          <div class="chart-wrap">
            <div class="chart-bars" id="chart-bars"></div>
            <div class="chart-labels" id="chart-labels"></div>
          </div>
        </div>

        <div class="card">
          <div class="card-head">
            <h3>Citas de hoy</h3>
            <button class="btn-ghost" onclick="showView('agenda',document.querySelector('[data-view=agenda]'))">Ver agenda →</button>
          </div>
          <table class="tbl">
            <thead><tr><th>Hora</th><th>Cliente</th><th>Servicio</th><th>Estado</th><th></th></tr></thead>
            <tbody id="today-tbody"></tbody>
          </table>
        </div>
      </div>
    </section>

    <!-- ===== AGENDA ===== -->
    <section class="view" id="view-agenda">
      <div class="agenda-layout">
        <div class="cal-panel">
          <div class="cal-header">
            <h3 class="cal-month" id="cal-month"></h3>
            <div class="cal-actions">
              <button class="btn-ghost-sm" onclick="toggleBloquear()">🔒 Bloquear día</button>
              <div class="cal-nav-btns">
                <button onclick="changeMonth(-1)">&#8249;</button>
                <button onclick="changeMonth(1)">&#8250;</button>
              </div>
            </div>
          </div>
          <div class="cal-weekdays">
            <span>Dom</span><span>Lun</span><span>Mar</span><span>Mié</span><span>Jue</span><span>Vie</span><span>Sáb</span>
          </div>
          <div class="cal-grid" id="cal-grid"></div>
          <div class="bloquear-panel" id="bloquear-panel" style="display:none">
            <p>Bloquear / desbloquear el día seleccionado:</p>
            <input class="field-input" id="motivo-bloqueo" placeholder="Motivo (Festivo, Capacitación...)">
            <div style="display:flex;gap:8px;margin-top:10px">
              <button class="btn-primary" onclick="bloquearDia()">Bloquear</button>
              <button class="btn-outline" onclick="desbloquearDia()">Desbloquear</button>
            </div>
          </div>
        </div>

        <div class="appts-panel">
          <div class="appts-head">
            <h3 id="appts-title">Selecciona un día</h3>
            <button class="btn-primary" onclick="openModal('nueva-cita')">+ Cita</button>
          </div>
          <div id="appts-list"></div>
        </div>
      </div>
    </section>

    <!-- ===== CLIENTES ===== -->
    <section class="view" id="view-clientes">
      <div class="card">
        <div class="card-head">
          <h3>Directorio de Clientes</h3>
          <input class="search-input" type="text" placeholder="Buscar..." oninput="filterClients(this.value)" id="client-search">
        </div>
        <table class="tbl">
          <thead><tr><th>Nombre</th><th>Teléfono</th><th>Correo</th><th>Citas</th><th>Estado</th></tr></thead>
          <tbody id="clientes-tbody"></tbody>
        </table>
      </div>
    </section>

    <!-- ===== SERVICIOS ===== -->
    <section class="view" id="view-servicios">
      <div class="card">
        <div class="card-head"><h3>Catálogo de Servicios</h3></div>
        <table class="tbl">
          <thead><tr><th>Servicio</th><th>Categoría</th><th>Duración</th><th>Precio</th></tr></thead>
          <tbody id="servicios-tbody"></tbody>
        </table>
      </div>
    </section>

    <!-- ===== EMPLEADAS ===== -->
    <section class="view" id="view-empleadas">
      <div class="card">
        <div class="card-head"><h3>Personal</h3></div>
        <table class="tbl">
          <thead><tr><th>Nombre</th><th>Especialidad</th><th>Teléfono</th><th>Estado</th></tr></thead>
          <tbody id="empleadas-tbody"></tbody>
        </table>
      </div>
    </section>

    <!-- ===== INVENTARIO ===== -->
    <section class="view" id="view-inventario">
      <div class="inv-header"><h3>Inventario</h3></div>
      <div class="inv-grid" id="inv-grid"></div>
    </section>

    <!-- ===== REPORTES ===== -->
    <section class="view" id="view-reportes">
      <div class="reports-grid" id="reports-grid">
        <div class="card"><div class="card-head"><h3>Cargando...</h3></div></div>
      </div>
    </section>

  </div><!-- /content -->
</main>

<!-- MODAL: Nueva Cita -->
<div class="overlay" id="overlay-nueva-cita">
  <div class="modal">
    <div class="modal-head">
      <h2>Nueva Cita</h2>
      <button class="modal-close" onclick="closeModal('nueva-cita')">×</button>
    </div>
    <div class="modal-body">
      <div class="mfield">
        <label>Cliente</label>
        <select id="nc-cliente"></select>
      </div>
      <div class="mrow2">
        <div class="mfield">
          <label>Servicio</label>
          <select id="nc-servicio" onchange="loadSlots()"></select>
        </div>
        <div class="mfield">
          <label>Empleada</label>
          <select id="nc-empleada" onchange="loadSlots()"><option value="">Cualquier disponible</option></select>
        </div>
      </div>
      <div class="mfield">
        <label>Fecha</label>
        <input type="date" id="nc-fecha" class="field-input" onchange="loadSlots()" min="<?= $hoy ?>">
      </div>
      <div class="mfield">
        <label>Horario disponible</label>
        <div class="slots-grid" id="nc-slots"><p class="slots-hint">Selecciona fecha y servicio.</p></div>
        <input type="hidden" id="nc-hora">
      </div>
      <div class="mfield">
        <label>Notas</label>
        <input type="text" class="field-input" id="nc-notas" placeholder="Alergias, preferencias...">
      </div>
    </div>
    <div class="modal-foot">
      <button class="btn-outline" onclick="closeModal('nueva-cita')">Cancelar</button>
      <button class="btn-primary" onclick="guardarCita()">Confirmar cita</button>
    </div>
  </div>
</div>

<!-- MODAL: Completar Cita -->
<div class="overlay" id="overlay-completar">
  <div class="modal modal-sm">
    <div class="modal-head">
      <h2>Completar Cita</h2>
      <button class="modal-close" onclick="closeModal('completar')">×</button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="comp-id">
      <p class="comp-desc" id="comp-desc"></p>
      <div class="mfield">
        <label>¿Fue pagado?</label>
        <select id="comp-pagado" class="field-input">
          <option value="1">Sí, pagó</option>
          <option value="0">No / pendiente</option>
        </select>
      </div>
      <div class="mfield">
        <label>Método de pago</label>
        <select id="comp-metodo" class="field-input">
          <option value="efectivo">Efectivo</option>
          <option value="tarjeta">Tarjeta</option>
          <option value="transferencia">Transferencia</option>
        </select>
      </div>
    </div>
    <div class="modal-foot">
      <button class="btn-outline" onclick="closeModal('completar')">Cancelar</button>
      <button class="btn-success" onclick="confirmarCompletar()">✓ Marcar completada</button>
    </div>
  </div>
</div>

<!-- TOAST -->
<div class="toast" id="toast"></div>

<script>
  // Pasar datos PHP a JS de forma segura
  window.BELEZA = {
    hoy: '<?= $hoy ?>',
    api: 'api.php'
  };
</script>
<script src="js/admin.js"></script>
</body>
</html>
