// ============================================================
// js/admin.js — Lógica del panel de administración
// ============================================================
'use strict';

const API    = window.BELEZA.api;
const HOY    = window.BELEZA.hoy;
const MONTHS = ['Enero','Febrero','Marzo','Abril','Mayo','Junio',
                'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];

// Estado global
let calDate       = new Date();
let selectedDay   = null;
let bloquearMode  = false;
let calDiasCitas  = [];
let calDiasBloq   = [];
let serviciosCache = [];
let empleadasCache = [];
let clientesCache  = [];
let clientesFull   = [];

// ============================================================
// UTILIDADES
// ============================================================
function api(action, params = {}, method = 'GET') {
  if (method === 'GET') {
    const q = new URLSearchParams({ action, ...params });
    return fetch(`${API}?${q}`).then(r => r.json());
  }
  return fetch(`${API}?action=${action}`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(params)
  }).then(r => r.json());
}

function toast(msg, type = '') {
  const el = document.getElementById('toast');
  el.textContent = msg;
  el.className = 'toast' + (type ? ' ' + type : '');
  requestAnimationFrame(() => el.classList.add('show'));
  setTimeout(() => el.classList.remove('show'), 3200);
}

function badge(estado) {
  return `<span class="badge badge-${estado}">${estadoLabel(estado)}</span>`;
}

function estadoLabel(e) {
  const map = {
    pendiente: 'Pendiente', confirmada: 'Confirmada',
    en_proceso: 'En proceso', completada: 'Completada', cancelada: 'Cancelada'
  };
  return map[e] || e;
}

// ============================================================
// NAVEGACIÓN
// ============================================================
window.showView = function (name, el) {
  document.querySelectorAll('.view').forEach(v => v.classList.remove('active'));
  document.querySelectorAll('.sb-item').forEach(n => n.classList.remove('active'));
  document.getElementById('view-' + name).classList.add('active');
  if (el) el.classList.add('active');

  const titles = {
    dashboard: 'Dashboard', agenda: 'Agenda', clientes: 'Clientes',
    servicios: 'Servicios', empleadas: 'Empleadas',
    inventario: 'Inventario', reportes: 'Reportes'
  };
  document.getElementById('page-title').textContent = titles[name] || name;

  if (name === 'agenda')    renderCalendar();
  if (name === 'clientes')  loadClientes();
  if (name === 'servicios') loadServicios();
  if (name === 'empleadas') loadEmpleadas();
  if (name === 'inventario')loadInventario();
  if (name === 'reportes')  loadReportes();

  // Cerrar sidebar en móvil
  document.getElementById('sidebar').classList.remove('open');
};

window.toggleSidebar = function () {
  document.getElementById('sidebar').classList.toggle('open');
};

// ============================================================
// MODALES
// ============================================================
window.openModal  = function (name) { document.getElementById('overlay-' + name).classList.add('open'); };
window.closeModal = function (name) { document.getElementById('overlay-' + name).classList.remove('open'); };

// Cerrar con click fuera
document.querySelectorAll('.overlay').forEach(ov => {
  ov.addEventListener('click', e => {
    if (e.target === ov) ov.classList.remove('open');
  });
});

// ============================================================
// DASHBOARD
// ============================================================
async function loadDashboard() {
  const s = await api('stats');
  document.getElementById('stat-hoy').textContent      = s.citasHoy || 0;
  document.getElementById('stat-ingresos').textContent = '$' + Number(s.ingresosMes || 0).toLocaleString('es-MX');
  document.getElementById('stat-clientes').textContent = s.clientesActivos || 0;
  document.getElementById('stat-servicios').textContent= s.serviciosMes || 0;
  renderChart(s.ing12 || []);
  loadTodayCitas();
}

function renderChart(ing12) {
  const vals  = ing12.map(i => parseFloat(i.total) || 0);
  const max   = Math.max(...vals, 1);
  const bars  = document.getElementById('chart-bars');
  const lbls  = document.getElementById('chart-labels');

  bars.innerHTML = vals.map(v =>
    `<div class="chart-bar" style="height:${Math.round((v / max) * 96) || 4}px"
          title="$${v.toLocaleString('es-MX')}"></div>`
  ).join('');

  lbls.innerHTML = ing12.map(i =>
    `<div class="chart-lbl">${i.mes.split('-')[1]}</div>`
  ).join('');
}

async function loadTodayCitas() {
  const mes = HOY.substring(0, 7);
  const { citas } = await api('citas_list', { mes });
  const hoyArr = (citas || []).filter(c => c.fecha === HOY && c.estado !== 'cancelada');
  const tb = document.getElementById('today-tbody');

  if (!hoyArr.length) {
    tb.innerHTML = `<tr><td colspan="5" style="text-align:center;padding:20px;color:var(--sub)">Sin citas para hoy</td></tr>`;
    return;
  }
  tb.innerHTML = hoyArr.map(c => `
    <tr>
      <td>${c.hora_inicio}</td>
      <td class="td-name">${c.cliente_nombre} ${c.cliente_apellido}</td>
      <td>${c.servicio_nombre}</td>
      <td>${badge(c.estado)}</td>
      <td>
        ${c.estado !== 'completada' && c.estado !== 'cancelada'
          ? `<button class="btn-xs success" onclick="abrirCompletar(${c.id},'${c.cliente_nombre} ${c.cliente_apellido}','${c.servicio_nombre}')">✓ Completar</button>`
          : '—'}
      </td>
    </tr>`).join('');
}

// ============================================================
// CALENDARIO / AGENDA
// ============================================================
async function renderCalendar() {
  const mes = `${calDate.getFullYear()}-${String(calDate.getMonth() + 1).padStart(2, '0')}`;
  document.getElementById('cal-month').textContent = `${MONTHS[calDate.getMonth()]} ${calDate.getFullYear()}`;

  const data = await api('calendario_dias', { mes });
  calDiasCitas = data.dias || [];
  calDiasBloq  = data.bloqueados || [];

  const diasMap = Object.fromEntries(calDiasCitas.map(d => [d.fecha.substring(0, 10), d]));
  const bloqSet = new Set(calDiasBloq.map(b => b.substring(0, 10)));
  const first   = new Date(calDate.getFullYear(), calDate.getMonth(), 1).getDay();
  const days    = new Date(calDate.getFullYear(), calDate.getMonth() + 1, 0).getDate();
  const today   = new Date();

  let html = '';
  for (let i = 0; i < first; i++) {
    const d = new Date(calDate.getFullYear(), calDate.getMonth(), 0 - first + i + 1).getDate();
    html += `<div class="cal-day other">${d}</div>`;
  }
  for (let d = 1; d <= days; d++) {
    const fStr  = `${calDate.getFullYear()}-${String(calDate.getMonth() + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
    const isToday = d === today.getDate() && calDate.getMonth() === today.getMonth() && calDate.getFullYear() === today.getFullYear();
    const bloq  = bloqSet.has(fStr);
    const hasDot= !!diasMap[fStr];
    const isSel = selectedDay === fStr;
    let cls = 'cal-day';
    if (isToday) cls += ' today';
    if (bloq)   cls += ' bloq';
    if (hasDot) cls += ' has-dot';
    if (isSel && !isToday) cls += ' selected';

    html += `<div class="${cls}" onclick="selectDay('${fStr}')">${d}</div>`;
  }
  document.getElementById('cal-grid').innerHTML = html;
  if (selectedDay) renderApptPanel(selectedDay);
}

window.selectDay = async function (fStr) {
  selectedDay = fStr;
  renderCalendar();
};

async function renderApptPanel(fStr) {
  const [y, m] = fStr.split('-');
  const { citas } = await api('citas_list', { mes: `${y}-${m}` });
  const dia = (citas || []).filter(c => c.fecha.substring(0, 10) === fStr);

  const d = new Date(fStr + 'T12:00:00');
  document.getElementById('appts-title').textContent = `${d.getDate()} de ${MONTHS[d.getMonth()]}`;
  const list = document.getElementById('appts-list');

  const bloq = calDiasBloq.some(b => b.substring(0, 10) === fStr);
  if (bloq) {
    list.innerHTML = `<div style="padding:20px;text-align:center;font-size:13px;color:#92400E">🔒 Día bloqueado</div>`;
    return;
  }
  if (!dia.length) {
    list.innerHTML = `<div style="padding:20px;text-align:center;font-size:13px;color:var(--sub)">Sin citas para este día</div>`;
    return;
  }
  list.innerHTML = dia.map(c => `
    <div class="appt-item">
      <div class="appt-time">${c.hora_inicio} – ${c.hora_fin}</div>
      <div class="appt-name">${c.cliente_nombre || ''} ${c.cliente_apellido || ''}</div>
      <div class="appt-srv">${c.servicio_nombre}${c.empleada_nombre ? ' · ' + c.empleada_nombre : ''}</div>
      <div style="margin-bottom:6px">${badge(c.estado)} <span style="font-size:11px;color:var(--sub)">$${Number(c.monto || 0).toLocaleString('es-MX')}</span></div>
      <div class="appt-acts">
        ${c.estado === 'pendiente'  ? `<button class="btn-xs" onclick="cambiarEstado(${c.id},'confirmada')">✓ Confirmar</button>` : ''}
        ${c.estado === 'confirmada' ? `<button class="btn-xs" onclick="cambiarEstado(${c.id},'en_proceso')">▶ En proceso</button>` : ''}
        ${['confirmada','en_proceso'].includes(c.estado) ? `<button class="btn-xs success" onclick="abrirCompletar(${c.id},'${c.cliente_nombre} ${c.cliente_apellido}','${c.servicio_nombre}')">✓ Completar</button>` : ''}
        ${!['cancelada','completada'].includes(c.estado) ? `<button class="btn-xs danger" onclick="cancelarCita(${c.id})">✕ Cancelar</button>` : ''}
      </div>
    </div>`).join('');
}

window.changeMonth = function (d) {
  calDate.setMonth(calDate.getMonth() + d);
  selectedDay = null;
  renderCalendar();
};

window.toggleBloquear = function () {
  bloquearMode = !bloquearMode;
  document.getElementById('bloquear-panel').style.display = bloquearMode ? 'block' : 'none';
};

window.bloquearDia = async function () {
  if (!selectedDay) { toast('Selecciona un día primero.', 'err'); return; }
  const motivo = document.getElementById('motivo-bloqueo').value;
  const r = await api('bloquear_dia', { fecha: selectedDay, motivo }, 'POST');
  if (r.ok) { toast('Día bloqueado.', 'ok'); renderCalendar(); }
  else toast(r.error, 'err');
};

window.desbloquearDia = async function () {
  if (!selectedDay) { toast('Selecciona un día primero.', 'err'); return; }
  const r = await api('desbloquear_dia', { fecha: selectedDay }, 'POST');
  if (r.ok) { toast('Día desbloqueado.', 'ok'); renderCalendar(); }
  else toast(r.error, 'err');
};

window.cambiarEstado = async function (id, estado) {
  const r = await api('cita_estado', { id, estado }, 'POST');
  if (r.ok) { toast('Estado actualizado.', 'ok'); renderApptPanel(selectedDay); }
  else toast(r.error, 'err');
};

window.cancelarCita = async function (id) {
  if (!confirm('¿Cancelar esta cita?')) return;
  const r = await api('cita_cancelar', { id }, 'POST');
  if (r.ok) { toast('Cita cancelada.'); renderApptPanel(selectedDay); loadDashboard(); }
  else toast(r.error, 'err');
};

// ============================================================
// COMPLETAR CITA
// ============================================================
window.abrirCompletar = function (id, cliente, servicio) {
  document.getElementById('comp-id').value = id;
  document.getElementById('comp-desc').textContent = `${cliente} — ${servicio}`;
  openModal('completar');
};

window.confirmarCompletar = async function () {
  const id     = document.getElementById('comp-id').value;
  const pagado = document.getElementById('comp-pagado').value;
  const metodo = document.getElementById('comp-metodo').value;
  const r = await api('cita_completar', { id, pagado, metodo_pago: metodo }, 'POST');
  if (r.ok) {
    toast('Cita completada.', 'ok');
    closeModal('completar');
    renderApptPanel(selectedDay || HOY);
    loadDashboard();
  } else toast(r.error, 'err');
};

// ============================================================
// NUEVA CITA (MODAL)
// ============================================================
async function loadFormCita() {
  const [sc, ec, cc] = await Promise.all([
    api('servicios_list'), api('empleadas_list'), api('clientes_list')
  ]);
  serviciosCache = sc.servicios || [];
  empleadasCache = ec.empleadas || [];
  clientesCache  = cc.clientes  || [];

  document.getElementById('nc-servicio').innerHTML =
    serviciosCache.map(s => `<option value="${s.id}">$${s.precio} — ${s.nombre} (${s.duracion_min}min)</option>`).join('');
  document.getElementById('nc-empleada').innerHTML =
    '<option value="">Cualquier disponible</option>' +
    empleadasCache.map(e => `<option value="${e.id}">${e.nombre} ${e.apellido}</option>`).join('');
  document.getElementById('nc-cliente').innerHTML =
    clientesCache.map(c => `<option value="${c.id}">${c.nombre} ${c.apellido}</option>`).join('');

  if (selectedDay) document.getElementById('nc-fecha').value = selectedDay;
}

window.loadSlots = async function () {
  const fecha = document.getElementById('nc-fecha').value;
  const srvId = document.getElementById('nc-servicio').value;
  const empId = document.getElementById('nc-empleada').value;
  if (!fecha || !srvId) return;

  document.getElementById('nc-slots').innerHTML = '<p class="slots-hint">Cargando...</p>';
  const { slots } = await api('disponibilidad', { fecha, servicio_id: srvId, empleada_id: empId || '' });
  document.getElementById('nc-hora').value = '';

  const libres = (slots || []).filter(s => s.disponible);
  if (!libres.length) {
    document.getElementById('nc-slots').innerHTML = '<p class="slots-hint" style="color:#92400E">Sin horarios disponibles.</p>';
    return;
  }
  document.getElementById('nc-slots').innerHTML = (slots || []).map(s =>
    `<div class="slot ${s.disponible ? 'libre' : 'ocupado'}"
          onclick="${s.disponible ? `selectSlot('${s.hora}',this)` : ''}">${s.hora}</div>`
  ).join('');
};

window.selectSlot = function (hora, el) {
  document.querySelectorAll('#nc-slots .slot').forEach(s => s.classList.remove('sel'));
  el.classList.add('sel');
  document.getElementById('nc-hora').value = hora;
};

window.guardarCita = async function () {
  const fecha = document.getElementById('nc-fecha').value;
  const hora  = document.getElementById('nc-hora').value;
  if (!fecha || !hora) { toast('Selecciona fecha y horario.', 'err'); return; }
  const r = await api('cita_crear', {
    cliente_id:  document.getElementById('nc-cliente').value,
    servicio_id: document.getElementById('nc-servicio').value,
    empleada_id: document.getElementById('nc-empleada').value || null,
    fecha, hora_inicio: hora,
    notas: document.getElementById('nc-notas').value
  }, 'POST');
  if (r.ok) {
    toast('Cita registrada.', 'ok');
    closeModal('nueva-cita');
    renderCalendar();
    loadDashboard();
  } else toast(r.error || 'Error', 'err');
};

// ============================================================
// CLIENTES
// ============================================================
async function loadClientes() {
  const { clientes } = await api('clientes_list');
  clientesFull = clientes || [];
  renderClientes(clientesFull);
}

function renderClientes(arr) {
  const tb = document.getElementById('clientes-tbody');
  if (!arr.length) {
    tb.innerHTML = `<tr><td colspan="5" style="text-align:center;padding:20px;color:var(--sub)">Sin clientes.</td></tr>`;
    return;
  }
  tb.innerHTML = arr.map(c => `
    <tr>
      <td class="td-name">${c.nombre} ${c.apellido}</td>
      <td>${c.telefono || '—'}</td>
      <td>${c.email}</td>
      <td>${c.total_citas}</td>
      <td><span class="badge badge-ok">Activo</span></td>
    </tr>`).join('');
}

window.filterClients = function (v) {
  renderClientes(clientesFull.filter(c =>
    (c.nombre + ' ' + c.apellido + c.email).toLowerCase().includes(v.toLowerCase())
  ));
};

// ============================================================
// SERVICIOS
// ============================================================
async function loadServicios() {
  const { servicios } = await api('servicios_list');
  document.getElementById('servicios-tbody').innerHTML = (servicios || []).map(s => `
    <tr>
      <td class="td-name">${s.nombre}</td>
      <td>${s.categoria}</td>
      <td>${s.duracion_min} min</td>
      <td>$${Number(s.precio).toLocaleString('es-MX')}</td>
    </tr>`).join('');
}

// ============================================================
// EMPLEADAS
// ============================================================
async function loadEmpleadas() {
  const { empleadas } = await api('empleadas_list');
  document.getElementById('empleadas-tbody').innerHTML = (empleadas || []).map(e => `
    <tr>
      <td class="td-name">${e.nombre} ${e.apellido}</td>
      <td>${e.especialidad || '—'}</td>
      <td>${e.telefono || '—'}</td>
      <td><span class="badge badge-ok">Activa</span></td>
    </tr>`).join('');
}

// ============================================================
// INVENTARIO
// ============================================================
async function loadInventario() {
  const { inventario } = await api('inventario_list');
  document.getElementById('inv-grid').innerHTML = (inventario || []).map(p => {
    const max = Math.max(p.minimo * 3, p.stock, 1);
    const pct = Math.min(100, Math.round((p.stock / max) * 100));
    const low = p.stock <= p.minimo;
    return `
      <div class="inv-card">
        <div class="inv-card-name">${p.nombre}</div>
        <div class="inv-card-cat">${p.categoria}</div>
        <div class="inv-stock-row">
          <span>Stock: <strong>${p.stock}</strong> ${p.unidad}</span>
          <span style="font-size:11px;color:${low ? '#92400E' : 'var(--sub)'}">${low ? '⚠ Bajo' : 'OK'}</span>
        </div>
        <div class="inv-bar"><div class="inv-fill${low ? ' low' : ''}" style="width:${pct}%"></div></div>
        <div class="inv-controls">
          <button class="btn-xs" onclick="adjustStock(${p.id},${p.stock},-1)">−</button>
          <span>${p.stock}</span>
          <button class="btn-xs" onclick="adjustStock(${p.id},${p.stock},1)">+</button>
        </div>
      </div>`;
  }).join('');
}

window.adjustStock = async function (id, current, delta) {
  const ns = Math.max(0, current + delta);
  const r = await api('inventario_update', { id, stock: ns }, 'POST');
  if (r.ok) loadInventario();
  else toast(r.error, 'err');
};

// ============================================================
// REPORTES
// ============================================================
async function loadReportes() {
  const mesStr = new Date().toISOString().substring(0, 7);
  const { citas } = await api('citas_list', { mes: mesStr });
  const completadas = (citas || []).filter(c => c.estado === 'completada');
  const total = completadas.reduce((s, c) => s + parseFloat(c.monto || 0), 0);
  const cobrado = completadas.filter(c => c.pagado == '1').reduce((s, c) => s + parseFloat(c.monto || 0), 0);

  const srvCount = {};
  completadas.forEach(c => { srvCount[c.servicio_nombre] = (srvCount[c.servicio_nombre] || 0) + 1; });
  const topSrv = Object.entries(srvCount).sort((a, b) => b[1] - a[1]).slice(0, 5);

  document.getElementById('reports-grid').innerHTML = `
    <div class="card">
      <div class="card-head"><h3>Servicios más realizados</h3></div>
      <div style="padding:4px 20px 16px">
        ${topSrv.map(([n, v]) => `<div class="report-row"><span class="r-label">${n}</span><span class="r-val">${v}</span></div>`).join('') || '<p style="padding:16px;font-size:13px;color:var(--sub)">Sin datos este mes.</p>'}
      </div>
    </div>
    <div class="card">
      <div class="card-head"><h3>Resumen del mes</h3></div>
      <div style="padding:4px 20px 16px">
        <div class="report-row"><span class="r-label">Total citas</span><span class="r-val">${(citas || []).length}</span></div>
        <div class="report-row"><span class="r-label">Completadas</span><span class="r-val">${completadas.length}</span></div>
        <div class="report-row"><span class="r-label">Ingreso cobrado</span><span class="r-val">$${cobrado.toLocaleString('es-MX')}</span></div>
        <div class="report-row"><span class="r-label">Ingreso total (servicios)</span><span class="r-val">$${total.toLocaleString('es-MX')}</span></div>
      </div>
    </div>`;
}

// ============================================================
// LOGOUT
// ============================================================
window.doLogout = async function () {
  const r = await api('logout', {}, 'POST');
  if (r.redirect) window.location.href = r.redirect;
};

// ============================================================
// INIT
// ============================================================
(async () => {
  await loadDashboard();
  await loadFormCita();
})();
