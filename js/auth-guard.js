/**
 * Auth Guard - Protección de Rutas y Control de Sesión
 * Sistema de Ventas "Gusto Marino"
 * 
 * Uso en páginas HTML:
 *   <script src="../js/auth-guard.js" data-role="administrador"></script>
 *   O data-role="mesero" / data-role="any" (por defecto any)
 */

(function() {
    // Determinar la ruta relativa hacia la API según la ubicación actual
    const currentScript = document.currentScript;
    const requiredRole = currentScript ? currentScript.getAttribute('data-role') : null;
    
    // Calcular ruta hacia api/session.php
    // La mayoría de páginas están en subdirectorios directos (ej: Dashboard/, menu/, etc.)
    const sessionApiUrl = '../api/session.php';
    const loginUrl = '../login/login.html';

    // Ocultar temporalmente el cuerpo para evitar parpadeos visuales (FOUC) antes de verificar
    document.documentElement.style.visibility = 'hidden';

    fetch(sessionApiUrl)
        .then(response => {
            if (!response.ok && response.status === 401) {
                throw new Error('UNAUTHORIZED');
            }
            return response.json();
        })
        .then(payload => {
            const data = payload.data || payload;
            if (!data.authenticated || !data.user) {
                // No hay sesión activa -> Redirigir al login
                sessionStorage.setItem('redirect_after_login', window.location.href);
                window.location.replace(loginUrl);
                return;
            }

            const user = data.user;
            window.CurrentUser = user;

            // Validación de roles RBAC
            if (requiredRole && requiredRole !== 'any') {
                const allowedRoles = requiredRole.split(',').map(r => r.trim());
                if (!allowedRoles.includes(user.rol)) {
                    console.warn(`Acceso restringido: Se requiere rol '${requiredRole}', usuario actual es '${user.rol}'`);
                    if (user.rol === 'mesero') {
                        window.location.replace('../Dashboard/dashboard_mesero.html');
                    } else {
                        window.location.replace('../Dashboard/dashboard_admin.html');
                    }
                    return;
                }
            }

            // Sesión y rol válidos -> Mostrar contenido
            document.documentElement.style.visibility = 'visible';

            // Actualizar interfaz cuando el DOM esté listo
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', () => actualizarUIUsuario(user));
            } else {
                actualizarUIUsuario(user);
            }
        })
        .catch(err => {
            console.error('Error en Auth Guard:', err);
            window.location.replace(loginUrl);
        });

    function actualizarUIUsuario(user) {
        // Actualizar nombres de usuario en cabeceras si existen
        const userElements = document.querySelectorAll('.user-info h4, #user-nombre, .current-user-name');
        userElements.forEach(el => {
            if (el.tagName === 'H4') {
                el.textContent = user.usuario.charAt(0).toUpperCase() + user.usuario.slice(1);
            }
        });

        const rolElements = document.querySelectorAll('.user-info p, #user-rol, .current-user-role');
        rolElements.forEach(el => {
            if (el.tagName === 'P') {
                el.textContent = user.rol === 'administrador' ? 'Panel de Administrador' : 'Panel de Mesero';
            }
        });
    }

    // Exponer API global
    window.AuthGuard = {
        getUser: () => window.CurrentUser || null,
        logout: async function() {
            try {
                await fetch('../api/session.php?action=logout');
            } finally {
                window.location.replace(loginUrl);
            }
        }
    };
})();
