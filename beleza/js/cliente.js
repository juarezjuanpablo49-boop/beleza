// ============================================================
// js/cliente.js — Lógica del portal de cliente
// ============================================================
'use strict';

const API    = window.BELEZA.api;
const HOY    = window.BELEZA.hoy;
const MONTHS = ['Enero','Febrero','Marzo','Abril','Mayo','Junio',
                'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];

let cliDate      = new Date();
let selServicio  = null;
let selFecha     = null;
let selHora      = null;
let serviciosCache = [];
let calDiasBloq  = [];
let calDiasBusy  = [];

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
  const labels = {
    pendiente: 'Pendiente', confirmada: 'Confirmada',
    en_proceso: 'En proceso', completada: 'Completada', cancelada: 'Cancelada'
  };
  return `<span class="badge badge-${estado}">${labels[estado] || estado}</span>`;
}

function formatFecha(f) {
  const d = new Date(f + 'T12:00:00');
  return `${d.getDate()} ${MONTHS[d.getMonth()].substring(0, 3)} ${d.getFullYear()}`;
}

// ============================================================
// NAVEGACIÓN
// ============================================================
window.showView = function (name, el) {
  document.querySelectorAll('.view').forEach(v => v.classList.remove('active'));
  document.querySelectorAll('.nav-link').forEach(n => n.classList.remove('active'));
  document.getElementById('view-' + name).classList.add('active');
  if (el) el.classList.add('active');

  if (name === 'inicio')    loadInicio();
  if (name === 'historial') loadHistorial();
  if (name === 'agendar')   initAgendar();
};

// ============================================================
// INICIO — Próximas citas
// ============================================================
async function loadInicio() {
  const mes = HOY.substring(0, 7);
  const { citas } = await api('citas_list', { mes });
  const proximas = (citas || []).filter(c =>
    c.fecha >= HOY && c.estado !== 'cancelada' && c.estado !== 'completada'
  );
  const grid = document.getElementById('proximas-grid');
  if (!proximas.length) {
    grid.innerHTML = `<p style="font-size:14px;color:var(--sub)">No tienes citas próximas.<br><a href="#" onclick="showView('agendar',document.querySelectorAll('.nav-link')[1])" style="color:var(--rose)">¡Agenda una ahora!</a></p>`;
    return;
  }
  grid.innerHTML = proximas.map(c => {
    const d = new Date(c.fecha + 'T12:00:00');
    return `
      <div class="cita-card">
        <div class="cita-day">${d.getDate()}</div>
        <div class="cita-month">${MONTHS[d.getMonth()]} ${d.getFullYear()}</div>
        <div class="cita-name">${c.servicio_nombre}</div>
        <div class="cita-detail">${c.hora_inicio}h${c.empleada_nombre ? ' · ' + c.empleada_nombre : ''}</div>
        <div class="cita-footer">
          ${badge(c.estado)}
          ${['pendiente','confirmada'].includes(c.estado)
            ? `<button class="btn-sm danger" onclick="cancelarCita(${c.id})">Cancelar</button>`
            : ''}
        </div>
      </div>`;
  }).join('');
}

// ============================================================
// AGENDAR — Wizard 3 pasos
// ============================================================
async function initAgendar() {
  const { servicios } = await api('servicios_list');
  serviciosCache = servicios || [];
  const grid = document.getElementById('srv-grid');
  grid.innerHTML = serviciosCache.map(s => `
    <div class="srv-card" onclick="selectServicio(${s.id},this)" data-id="${s.id}">
      <div class="srv-name">${s.nombre}</div>
      <div class="srv-cat">${s.categoria}</div>
      <div class="srv-meta">
        <span>${s.duracion_min} min</span>
        <span class="srv-price">$${Number(s.precio).toLocaleString('es-MX')}</span>
      </div>
    </div>`).join('');

  selServicio = null; selFecha = null; selHora = null;
  document.getElementById('btn-paso2').disabled = true;
  document.getElementById('btn-paso3').disabled = true;
  goStep(1);
  renderCliCal();
}

window.selectServicio = function (id, el) {
  document.querySelectorAll('.srv-card').forEach(c => c.classList.remove('sel'));
  el.classList.add('sel');
  selServicio = serviciosCache.find(s => s.id == id);
  document.getElementById('btn-paso2').disabled = false;
};

window.goStep = function (n) {
  for (let i = 1; i <= 3; i++) {
    document.getElementById('step-' + i).classList.toggle('active', i === n);
    const dot  = document.getElementById('dot-' + i);
    dot.classList.remove('active', 'done');
    if (i === n) dot.classList.add('active');
    else if (i < n) dot.classList.add('done');
    if (i < 3) {
      const line = document.getElementById(`line-${i}${i+1}`);
      line.classList.toggle('done', i < n);
    }
  }
  if (n === 3) fillResumen();
};

function fillResumen() {
  document.getElementById('res-servicio').textContent = selServicio?.nombre || '—';
  if (selFecha) {
    const d = new Date(selFecha + 'T12:00:00');
    document.getElementById('res-fecha').textContent = `${d.getDate()} de ${MONTHS[d.getMonth()]} ${d.getFullYear()}`;
  }
  document.getElementById('res-hora').textContent    = selHora || '—';
  document.getElementById('res-dur').textContent     = selServicio ? selServicio.duracion_min + ' min' : '—';
  document.getElementById('res-precio').textContent  = selServicio ? '$' + Number(selServicio.precio).toLocaleString('es-MX') : '—';
}

// ---- Mini calendario ----
async function renderCliCal() {
  const mes = `${cliDate.getFullYear()}-${String(cliDate.getMonth() + 1).padStart(2, '0')}`;
  document.getElementById('cli-cal-month').textContent = `${MONTHS[cliDate.getMonth()]} ${cliDate.getFullYear()}`;

  const data   = await api('calendario_dias', { mes });
  calDiasBloq  = (data.bloqueados || []).map(b => b.substring(0, 10));
  calDiasBusy  = (data.dias || []).map(d => d.fecha.substring(0, 10));

  const first  = new Date(cliDate.getFullYear(), cliDate.getMonth(), 1).getDay();
  const days   = new Date(cliDate.getFullYear(), cliDate.getMonth() + 1, 0).getDate();
  const today  = new Date(); today.setHours(0, 0, 0, 0);

  let html = '';
  for (let i = 0; i < first; i++) html += `<div class="cd other"></div>`;
  for (let d = 1; d <= days; d++) {
    const fStr   = `${cliDate.getFullYear()}-${String(cliDate.getMonth() + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
    const dt     = new Date(fStr + 'T12:00:00');
    const isPast = dt < today;
    const bloq   = calDiasBloq.includes(fStr);
    const busy   = calDiasBusy.includes(fStr);
    const isToday= d === today.getDate() && cliDate.getMonth() === today.getMonth() && cliDate.getFullYear() === today.getFullYear();
    const isSel  = selFecha === fStr;

    let cls = 'cd';
    if (isToday) cls += ' today';
    if (bloq)    cls += ' bloq';
    else if (isPast) cls += ' past';
    if (busy)    cls += ' busy';
    if (isSel && !isToday) cls += ' sel';

    const click = (!bloq && !isPast) ? `onclick="selectFecha('${fStr}')"` : '';
    html += `<div class="${cls}" ${click}>${d}</div>`;
  }
  document.getElementById('cli-cal-grid').innerHTML =
    `<div style="display:grid;grid-template-columns:repeat(7,1fr);gap:2px">${html}</div>`;
}

window.cliChangeMonth = function (d) {
  cliDate.setMonth(cliDate.getMonth() + d);
  renderCliCal();
};

window.selectFecha = async function (fStr) {
  selFecha = fStr; selHora = null;
  document.getElementById('sel-hora').value = '';
  document.getElementById('btn-paso3').disabled = true;
  renderCliCal();

  if (!selServicio) {
    document.getElementById('slots-title').textContent = 'Elige un servicio primero.';
    return;
  }
  const d = new Date(fStr + 'T12:00:00');
  document.getElementById('slots-title').textContent = `${d.getDate()} de ${MONTHS[d.getMonth()]}`;
  document.getElementById('cli-slots').innerHTML = '<p style="font-size:12px;color:var(--sub)">Cargando...</p>';

  const { slots } = await api('disponibilidad', { fecha: fStr, servicio_id: selServicio.id });
  const libres = (slots || []).filter(s => s.disponible);
  if (!libres.length) {
    document.getElementById('cli-slots').innerHTML = '<p style="font-size:12px;color:var(--amber)">Sin horarios disponibles este día.</p>';
    return;
  }
  document.getElementById('cli-slots').innerHTML = (slots || []).map(s =>
    `<div class="slot ${s.disponible ? 'libre' : 'ocup'}"
          onclick="${s.disponible ? `pickSlot('${s.hora}',this)` : ''}">${s.hora}</div>`
  ).join('');
};

window.pickSlot = function (hora, el) {
  document.querySelectorAll('.slot').forEach(s => s.classList.remove('sel'));
  el.classList.add('sel');
  selHora = hora;
  document.getElementById('sel-hora').value = hora;
  document.getElementById('btn-paso3').disabled = false;
};

window.confirmarCita = async function () {
  if (!selServicio || !selFecha || !selHora) { toast('Completa todos los pasos.', 'err'); return; }
  const r = await api('cita_crear', {
    servicio_id: selServicio.id,
    fecha: selFecha,
    hora_inicio: selHora,
    notas: document.getElementById('res-notas').value
  }, 'POST');
  if (r.ok) {
    toast('¡Cita confirmada!', 'ok');
    goStep(1);
    showView('inicio', document.querySelectorAll('.nav-link')[0]);
  } else toast(r.error || 'Error', 'err');
};

// ============================================================
// HISTORIAL
// ============================================================
async function loadHistorial() {
  const mesActual  = HOY.substring(0, 7);
  const mesAntes   = (() => { const d = new Date(); d.setMonth(d.getMonth() - 2); return `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}`; })();
  const [r1, r2]   = await Promise.all([
    api('citas_list', { mes: mesActual }),
    api('citas_list', { mes: mesAntes })
  ]);
  const todas = [...(r1.citas || []), ...(r2.citas || [])].sort((a, b) => b.fecha.localeCompare(a.fecha));
  const tb = document.getElementById('hist-tbody');
  if (!todas.length) {
    tb.innerHTML = `<tr><td colspan="7" style="text-align:center;padding:20px;color:var(--sub)">Sin historial de citas.</td></tr>`;
    return;
  }
  tb.innerHTML = todas.map(c => `
    <tr>
      <td>${formatFecha(c.fecha)}</td>
      <td>${c.hora_inicio}</td>
      <td class="td-name">${c.servicio_nombre}</td>
      <td>${c.empleada_nombre || '—'}</td>
      <td>${badge(c.estado)}</td>
      <td>$${Number(c.monto || 0).toLocaleString('es-MX')}${c.pagado == '1' ? ' <span style="font-size:10px;color:var(--green)">✓</span>' : ''}</td>
      <td>${['pendiente','confirmada'].includes(c.estado)
        ? `<button class="btn-sm danger" onclick="cancelarCita(${c.id})">Cancelar</button>` : '—'}</td>
    </tr>`).join('');
}

window.cancelarCita = async function (id) {
  if (!confirm('¿Cancelar esta cita?')) return;
  const r = await api('cita_cancelar', { id }, 'POST');
  if (r.ok) { toast('Cita cancelada.'); loadInicio(); loadHistorial(); }
  else toast(r.error, 'err');
};

// ============================================================
// PERFIL
// ============================================================
window.guardarPerfil = async function () {
  const r = await api('perfil_update', {
    nombre:   document.getElementById('prf-nombre').value,
    apellido: document.getElementById('prf-apellido').value,
    telefono: document.getElementById('prf-tel').value
  }, 'POST');
  if (r.ok) toast('Perfil actualizado.', 'ok');
  else toast(r.error, 'err');
};

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
loadInicio();
