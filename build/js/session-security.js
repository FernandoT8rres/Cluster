/**
 * Sistema de Seguridad de Sesión
 * Maneja timeout de inactividad y validación automática de sesión
 */

class SessionSecurity {
    constructor(options = {}) {
        this.inactivityTimeout = options.inactivityTimeout || 5; // minutos
        this.checkInterval = options.checkInterval || 30; // segundos
        this.loginUrl = options.loginUrl || './pages/sign-in.html';
        this.apiValidationUrl = options.apiValidationUrl || './api/auth/validate-session.php';

        this.lastActivity = Date.now();
        this.inactivityTimer = null;
        this.sessionChecker = null;
        this.isActive = true;

        this.init();
    }

    init() {
        console.log('🔒 Inicializando sistema de seguridad de sesión...');

        // Verificar sesión inicial
        this.validateSession();

        // Configurar detectores de actividad
        this.setupActivityDetectors();

        // Iniciar timer de inactividad
        this.startInactivityTimer();

        // Iniciar validador periódico de sesión
        this.startSessionValidator();

        console.log(`✅ Seguridad configurada: ${this.inactivityTimeout}min inactividad, verificación cada ${this.checkInterval}s`);
    }

    setupActivityDetectors() {
        const events = ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart', 'click'];

        events.forEach(event => {
            document.addEventListener(event, () => {
                this.updateActivity();
            }, true);
        });
    }

    updateActivity() {
        this.lastActivity = Date.now();
        console.log('👆 Actividad detectada, timer reiniciado');
    }

    startInactivityTimer() {
        this.inactivityTimer = setInterval(() => {
            const now = Date.now();
            const timeSinceActivity = now - this.lastActivity;
            const inactivityLimit = this.inactivityTimeout * 60 * 1000; // convertir a ms

            if (timeSinceActivity >= inactivityLimit) {
                console.log('⏰ Tiempo de inactividad excedido, redirigiendo al login...');
                this.redirectToLogin('inactivity');
            }
        }, 30000); // verificar cada 30 segundos
    }

    startSessionValidator() {
        this.sessionChecker = setInterval(() => {
            this.validateSession();
        }, this.checkInterval * 1000);
    }

    validateSession() {
        // Método 1: Verificar datos locales (compatible con sistema actual)
        const hasLocalSession = this.checkLocalSession();

        if (!hasLocalSession) {
            console.log('❌ No se detectó sesión local válida');
            this.redirectToLogin('no_session');
            return;
        }

        // Método 2: Verificar con el servidor (opcional)
        this.checkServerSession();

        // Método 3: Verificar restricciones de acceso por página
        this.checkPageRestrictions();
    }

    checkLocalSession() {
        // Usar la misma lógica que el sistema actual
        const userData = localStorage.getItem('userData') || sessionStorage.getItem('userData');
        const hasUserElement = document.querySelector('.user-name') ||
                              document.querySelector('[data-user]') ||
                              window.currentUser;
        const hasAuthClass = document.body.classList.contains('authenticated');

        return userData || hasUserElement || hasAuthClass || window.currentUser;
    }

    checkServerSession() {
        fetch(this.apiValidationUrl, {
            method: 'GET',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success || !data.valid) {
                console.log('❌ Sesión inválida en servidor');
                this.redirectToLogin('invalid_server_session');
            } else {
                console.log('✅ Sesión válida confirmada por servidor');
            }
        })
        .catch(error => {
            console.log('⚠️ Error verificando sesión en servidor:', error);
            // No redirigir por errores de red, solo por sesiones inválidas
        });
    }

    checkPageRestrictions() {
        // Obtener la página actual
        const currentPage = this.getCurrentPageName();

        // Solo verificar restricciones en páginas específicas
        const paginasConRestriccion = [
            'eventos', 'documentacion', 'boletines', 'comites',
            'contacto', 'empresas-convenio', 'descuentos', 'profile'
        ];

        if (!paginasConRestriccion.includes(currentPage)) {
            return; // No verificar restricciones para otras páginas
        }

        // Verificar restricciones con el servidor
        fetch('./api/restricciones.php?action=check&pagina=' + encodeURIComponent(currentPage), {
            method: 'GET',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data.acceso_restringido) {
                console.log(`🚫 Acceso restringido a la página: ${currentPage}`);
                this.showAccessDeniedMessage(currentPage);
            }
        })
        .catch(error => {
            console.log('⚠️ Error verificando restricciones de página:', error);
            // No redirigir por errores de red en verificación de restricciones
        });
    }

    getCurrentPageName() {
        const path = window.location.pathname;
        const filename = path.split('/').pop();

        // Extraer el nombre sin la extensión .html
        if (filename.endsWith('.html')) {
            return filename.slice(0, -5);
        }

        return filename;
    }

    showAccessDeniedMessage(pagina) {
        // Crear modal de advertencia
        const modal = document.createElement('div');
        modal.id = 'accessDeniedModal';
        modal.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 999999;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        `;

        const modalContent = document.createElement('div');
        modalContent.style.cssText = `
            background: white;
            border-radius: 16px;
            padding: 32px;
            max-width: 500px;
            margin: 20px;
            text-align: center;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            animation: modalFadeIn 0.3s ease-out;
        `;

        modalContent.innerHTML = `
            <style>
                @keyframes modalFadeIn {
                    from { opacity: 0; transform: scale(0.9); }
                    to { opacity: 1; transform: scale(1); }
                }
            </style>
            <div style="color: #dc2626; font-size: 48px; margin-bottom: 16px;">🚫</div>
            <h2 style="color: #dc2626; margin: 0 0 16px 0; font-size: 24px; font-weight: 600;">
                Acceso Restringido
            </h2>
            <p style="color: #6b7280; margin: 0 0 24px 0; font-size: 16px; line-height: 1.5;">
                No tienes permisos para acceder a la página <strong>"${this.getPageDisplayName(pagina)}"</strong>.<br>
                Serás redirigido al dashboard principal.
            </p>
            <div style="display: flex; gap: 12px; justify-content: center;">
                <button id="accessDeniedBtn" style="
                    background: #dc2626;
                    color: white;
                    border: none;
                    padding: 12px 24px;
                    border-radius: 8px;
                    font-size: 16px;
                    font-weight: 500;
                    cursor: pointer;
                    transition: background 0.2s;
                " onmouseover="this.style.background='#b91c1c'" onmouseout="this.style.background='#dc2626'">
                    Ir al Dashboard
                </button>
            </div>
        `;

        modal.appendChild(modalContent);
        document.body.appendChild(modal);

        // Manejar click del botón
        document.getElementById('accessDeniedBtn').addEventListener('click', () => {
            this.redirectToDashboard();
        });

        // Auto-redirigir después de 5 segundos
        setTimeout(() => {
            this.redirectToDashboard();
        }, 5000);
    }

    getPageDisplayName(pagina) {
        const displayNames = {
            'eventos': 'Eventos',
            'documentacion': 'Documentación',
            'boletines': 'Boletines',
            'comites': 'Comités',
            'contacto': 'Contacto',
            'empresas-convenio': 'Empresas en Convenio',
            'descuentos': 'Descuentos',
            'profile': 'Perfil'
        };

        return displayNames[pagina] || pagina;
    }

    redirectToDashboard() {
        if (!this.isActive) return;

        this.isActive = false;
        console.log('🔄 Redirigiendo al dashboard por restricción de acceso');

        // Limpiar timers
        if (this.inactivityTimer) clearInterval(this.inactivityTimer);
        if (this.sessionChecker) clearInterval(this.sessionChecker);

        window.location.href = 'dashboard.html';
    }

    redirectToLogin(reason) {
        if (!this.isActive) return; // Evitar múltiples redirecciones

        this.isActive = false;
        console.log(`🔄 Redirigiendo al login. Razón: ${reason}`);

        // Limpiar timers
        if (this.inactivityTimer) clearInterval(this.inactivityTimer);
        if (this.sessionChecker) clearInterval(this.sessionChecker);

        // Limpiar datos de sesión local si es necesario
        if (reason === 'invalid_server_session' || reason === 'no_session') {
            localStorage.removeItem('userData');
            sessionStorage.removeItem('userData');
        }

        // Redirigir
        window.location.href = this.loginUrl;
    }

    // Método público para pausar/reanudar el sistema
    pause() {
        this.isActive = false;
        if (this.inactivityTimer) clearInterval(this.inactivityTimer);
        if (this.sessionChecker) clearInterval(this.sessionChecker);
        console.log('⏸️ Sistema de seguridad pausado');
    }

    resume() {
        this.isActive = true;
        this.lastActivity = Date.now();
        this.startInactivityTimer();
        this.startSessionValidator();
        console.log('▶️ Sistema de seguridad reanudado');
    }

    // Método público para cambiar configuración
    updateConfig(newOptions) {
        Object.assign(this, newOptions);
        this.pause();
        this.resume();
        console.log('🔧 Configuración de seguridad actualizada');
    }
}

// Función de inicialización simple para usar en otros archivos
window.initSessionSecurity = function(options = {}) {
    if (window.sessionSecurity) {
        window.sessionSecurity.pause();
    }

    window.sessionSecurity = new SessionSecurity(options);
    return window.sessionSecurity;
};

// Función para usar en otros archivos con configuración mínima
window.enableSessionSecurity = function() {
    return window.initSessionSecurity({
        inactivityTimeout: 5, // 5 minutos
        checkInterval: 30,    // 30 segundos
        loginUrl: './pages/sign-in.html'
    });
};