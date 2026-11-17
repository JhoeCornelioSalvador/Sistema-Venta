/**
 * Gestor Dinámico de la Barra Lateral (Sidebar)
 * Sistema de Ventas & POS "El Gusto Marino"
 */

function cargarSidebar(tipo) {
  const ruta = tipo === "admin"
    ? "../Dashboard/partials/sidebar_admin.html"
    : "../Dashboard/partials/sidebar_mesero.html";

  fetch(ruta)
    .then(res => res.text())
    .then(html => {
      const sidebarContainer = document.getElementById("sidebar");
      if (!sidebarContainer) return;

      sidebarContainer.innerHTML = html;

      // Navegación en los botones del menú
      sidebarContainer.querySelectorAll("button[data-target]").forEach(btn => {
        btn.addEventListener("click", () => {
          const destino = btn.getAttribute("data-target");
          if (destino) {
            window.location.href = destino;
          }
        });
      });

      // Botón de cerrar sesión seguro
      const logoutBtn = document.getElementById("logout");
      if (logoutBtn) {
        logoutBtn.addEventListener("click", () => {
          fetch("../login/logout.php")
            .then(() => (window.location.href = "../login/login.html"))
            .catch(() => {
              window.location.href = "../login/login.html";
            });
        });
      }

      // Marcar ítem activo automáticamente según la ruta actual
      marcarActivoSidebar();
    })
    .catch(err => {
      console.error("Error cargando sidebar:", err);
      const container = document.getElementById("sidebar");
      if (container) {
        container.innerHTML = '<div class="sidebar-error" style="color:#ef4444;padding:20px;">Error al cargar el menú</div>';
      }
    });
}

function marcarActivoSidebar() {
  const currentPath = window.location.pathname.toLowerCase();
  const currentPage = currentPath.split('/').pop();

  document.querySelectorAll('#sidebar button[data-target]').forEach(btn => {
    const target = (btn.getAttribute('data-target') || '').toLowerCase();
    const targetPage = target.split('/').pop();

    btn.classList.remove('active');
    if (currentPage && targetPage && currentPage === targetPage) {
      btn.classList.add('active');
    }
  });
}
