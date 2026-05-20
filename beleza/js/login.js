// js/login.js — Lógica de la página de login

(function () {
  'use strict';

  // ---- Inicializar slider al cargar ----
  document.addEventListener('DOMContentLoaded', function () {
    const tipo = document.getElementById('input-tipo').value || 'cliente';
    setTipo(tipo);
  });

  // ---- Cambio de tipo admin/cliente ----
  window.setTipo = function (t) {
    const input  = document.getElementById('input-tipo');
    const slider = document.getElementById('type-slider');
    const submit = document.getElementById('btn-submit');
    const btnC   = document.getElementById('btn-cliente');
    const btnA   = document.getElementById('btn-admin');

    input.value = t;

    btnC.classList.toggle('active', t === 'cliente');
    btnA.classList.toggle('active', t === 'admin');

    if (t === 'admin') {
      slider.classList.add('right');
      submit.classList.add('admin-mode');
      submit.textContent = 'Ingresar como Administrador';
    } else {
      slider.classList.remove('right');
      submit.classList.remove('admin-mode');
      submit.textContent = 'Ingresar';
    }
  };

  // ---- Toggle visibilidad de contraseña ----
  window.togglePass = function () {
    const field = document.getElementById('password-field');
    const icon  = document.getElementById('eye-icon');

    if (field.type === 'password') {
      field.type = 'text';
      // Ícono de ojo tachado
      icon.innerHTML = `
        <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/>
        <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/>
        <line x1="1" y1="1" x2="23" y2="23"/>`;
    } else {
      field.type = 'password';
      icon.innerHTML = `
        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
        <circle cx="12" cy="12" r="3"/>`;
    }
  };
})();
