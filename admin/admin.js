/**
 * admin.js — Funciones JavaScript del panel de administracion
 * 
 * Este archivo contiene las utilidades JS usadas en las paginas
 * del admin: toggle de contrasena, etc.
 * NO carga la pagina publica ni hace fetch a la API.
 */

/**
 * Alternar visibilidad de contrasena (ojo abierto/cerrado)
 * @param {string} inputId - ID del campo de contrasena
 * @param {HTMLElement} btn - Boton del ojo que se presiono
 */
function togglePassword(inputId, btn) {
  const input = document.getElementById(inputId);
  if (!input) return;
  const eyeOpen = btn.querySelector('.eye-open');
  const eyeClosed = btn.querySelector('.eye-closed');
  if (input.type === 'password') {
    input.type = 'text';
    if (eyeOpen) eyeOpen.style.display = 'none';
    if (eyeClosed) eyeClosed.style.display = 'block';
  } else {
    input.type = 'password';
    if (eyeOpen) eyeOpen.style.display = 'block';
    if (eyeClosed) eyeClosed.style.display = 'none';
  }
}
