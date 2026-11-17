/**
 * Interacciones y Autenticación del Restaurante
 * Sistema de Ventas "El Gusto Marino"
 */

document.addEventListener('DOMContentLoaded', () => {
  // 1. Elementos del DOM
  const loginForm = document.getElementById('loginForm');
  const usuarioInput = document.getElementById('usuario');
  const passwordInput = document.getElementById('password');
  const togglePasswordBtn = document.getElementById('togglePassword');
  const rememberMeCheckbox = document.getElementById('rememberMe');
  const btnSubmit = document.getElementById('btnSubmit');
  const alertBox = document.getElementById('mensaje');
  const restaurantCard = document.getElementById('restaurantCard');
  const roleButtons = document.querySelectorAll('.btn-role-tab');

  // 3. Cargar credencial recordada
  const savedUser = localStorage.getItem('gusto_marino_remembered_user');
  if (savedUser) {
    usuarioInput.value = savedUser;
    rememberMeCheckbox.checked = true;
    passwordInput.focus();
  } else {
    usuarioInput.focus();
  }

  // 4. Selector Rápido de Rol (Administrador / Salón)
  roleButtons.forEach(btn => {
    btn.addEventListener('click', () => {
      const u = btn.getAttribute('data-user');
      const p = btn.getAttribute('data-pass');

      roleButtons.forEach(b => b.classList.remove('active'));
      btn.classList.add('active');

      usuarioInput.value = u;
      passwordInput.value = p;
      clearAlert();

      // Animación de enfoque
      usuarioInput.focus();
    });
  });

  // 5. Toggle de visualización de contraseña
  togglePasswordBtn.addEventListener('click', () => {
    const isPassword = passwordInput.getAttribute('type') === 'password';
    passwordInput.setAttribute('type', isPassword ? 'text' : 'password');
    togglePasswordBtn.setAttribute('aria-pressed', isPassword ? 'true' : 'false');

    const icon = togglePasswordBtn.querySelector('i');
    if (isPassword) {
      icon.className = 'fas fa-eye-slash';
      togglePasswordBtn.setAttribute('aria-label', 'Ocultar contraseña');
    } else {
      icon.className = 'fas fa-eye';
      togglePasswordBtn.setAttribute('aria-label', 'Ver contraseña');
    }
  });

  // 6. Limpieza de alertas
  [usuarioInput, passwordInput].forEach(input => {
    input.addEventListener('input', () => {
      clearAlert();
      roleButtons.forEach(b => b.classList.remove('active'));
    });
  });

  // 7. Envío de Formulario
  loginForm.addEventListener('submit', async (e) => {
    e.preventDefault();

    const usuario = usuarioInput.value.trim();
    const password = passwordInput.value.trim();
    clearAlert();

    if (!usuario || !password) {
      showAlert('Por favor, ingresa tu usuario y contraseña de acceso.', 'error');
      triggerShake();
      if (!usuario) usuarioInput.focus();
      else passwordInput.focus();
      return;
    }

    setLoading(true);

    try {
      const formData = new URLSearchParams();
      formData.append('usuario', usuario);
      formData.append('password', password);

      const response = await fetch('login.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded; charset=utf-8',
          'Accept': 'application/json'
        },
        body: formData
      });

      const data = await response.json().catch(() => null);

      if (response.ok && data && data.success) {
        if (rememberMeCheckbox.checked) {
          localStorage.setItem('gusto_marino_remembered_user', usuario);
        } else {
          localStorage.removeItem('gusto_marino_remembered_user');
        }

        showAlert('✓ Acceso concedido. Iniciando turno en el sistema...', 'success');

        const targetRedirect = sessionStorage.getItem('redirect_after_login') || data.redirect;
        sessionStorage.removeItem('redirect_after_login');

        setTimeout(() => {
          window.location.href = targetRedirect;
        }, 650);

      } else {
        const msg = data && data.message 
          ? data.message 
          : 'Usuario o contraseña incorrectos. Verifica tus credenciales.';

        showAlert(msg, 'error');
        triggerShake();
        passwordInput.value = '';
        passwordInput.focus();
      }

    } catch (err) {
      console.error('Error de autenticación:', err);
      showAlert('Error al conectar con el servidor local de XAMPP.', 'error');
      triggerShake();
    } finally {
      setLoading(false);
    }
  });

  function showAlert(msg, type) {
    alertBox.textContent = msg;
    alertBox.className = `restaurant-alert visible ${type}`;
  }

  function clearAlert() {
    alertBox.textContent = '';
    alertBox.className = 'restaurant-alert';
  }

  function triggerShake() {
    const card = document.getElementById('restaurantCard') || document.querySelector('.restaurant-card');
    if (card) {
      card.classList.remove('shake');
      void card.offsetWidth;
      card.classList.add('shake');
    }
  }

  function setLoading(loading) {
    if (loading) {
      btnSubmit.classList.add('loading');
      btnSubmit.disabled = true;
    } else {
      btnSubmit.classList.remove('loading');
      btnSubmit.disabled = false;
    }
  }
});
