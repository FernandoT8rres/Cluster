/**
 * Sistema de autenticación basado en sesiones del servidor
 * No usa localStorage - todo se maneja desde la base de datos
 */

class AuthSessionManager {
    constructor() {
        this.API_URL = './api/auth/session.php';
        this.currentUser = null;
        this.isAuthenticated = false;
        this.init();
    }

    init() {
        console.log('🔐 Inicializando sistema de autenticación basado en sesiones...');

        // Verificar estado de autenticación al cargar la página
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.checkAuthentication());
        } else {
            this.checkAuthentication();
        }
    }

    async checkAuthentication() {
        console.log('🔍 Verificando autenticación desde el servidor...');
        console.log('🌐 Ubicación actual:', window.location.href);
        console.log('🍪 Cookies disponibles:', document.cookie);

        // Si estamos en páginas de login, no verificar
        if (window.location.pathname.includes('sign-in.html') ||
            window.location.pathname.includes('sign-up.html')) {
            console.log('📍 En página de login, saltando verificación');
            return;
        }

        // Probar múltiples endpoints - priorizar el que sabemos que funciona
        const endpoints = [
            './api/auth/login-compatible.php?action=check',
            '../api/auth/login-compatible.php?action=check',
            './api/auth/session.php?action=check',
            '../api/auth/session.php?action=check'
        ];

        let authSuccess = false;
        let userData = null;

        for (const endpoint of endpoints) {
            try {
                console.log(`🔍 Probando endpoint para verificación: ${endpoint}`);

                const response = await fetch(endpoint, {
                    method: 'GET',
                    credentials: 'include',
                    headers: {
                        'Content-Type': 'application/json'
                    }
                });

                if (!response.ok) {
                    console.log(`❌ HTTP error en ${endpoint}: ${response.status}`);
                    continue;
                }

                const result = await response.json();
                console.log(`📄 Respuesta de ${endpoint}:`, result);

                if (result.success && result.data) {
                    console.log(`✅ Usuario autenticado desde ${endpoint}:`, result.data.nombre || result.data.email);
                    userData = result.data;
                    authSuccess = true;
                    break;
                }
            } catch (error) {
                console.log(`❌ Error en ${endpoint}:`, error.message);
                continue;
            }
        }

        if (authSuccess && userData) {
            this.currentUser = userData;
            this.isAuthenticated = true;

            // Guardar timestamp de autenticación exitosa para preservar navegación
            localStorage.setItem('lastAuthCheck', Date.now().toString());

            this.setupAuthenticatedUI();
        } else {
            console.log('❌ Usuario no autenticado en ningún endpoint');

            // Verificar contexto de sesión más cuidadosamente
            const urlParams = new URLSearchParams(window.location.search);
            const recentLogin = sessionStorage.getItem('recentLogin');
            const lastAuthCheck = localStorage.getItem('lastAuthCheck');
            const isFromInternalNavigation = document.referrer && document.referrer.includes(window.location.hostname);

            // Si hay login reciente o parámetros válidos, dar acceso temporal
            if (recentLogin === 'true' || urlParams.get('login') === 'success') {
                console.log('🔄 Login reciente detectado, configurando acceso temporal...');
                this.setupTemporaryAccess();
                sessionStorage.removeItem('recentLogin');

                // Si viene de navegación interna y tuvo autenticación reciente, intentar preservar
            } else if (isFromInternalNavigation && lastAuthCheck &&
                (Date.now() - parseInt(lastAuthCheck)) < 5 * 60 * 1000) { // 5 minutos
                console.log('🔄 Navegación interna detectada, preservando sesión...');
                this.setupTemporaryAccess();

            } else {
                // Usuario definitivamente no autenticado - redirigir al login
                console.log('❌ Usuario no autenticado - redirigiendo al login');
                this.isAuthenticated = false;
                this.currentUser = null;
                this.redirectToLogin();
            }
        }
    }

    async login(email, password) {
        console.log('🚪 Intentando login para:', email);

        try {
            const response = await fetch(`${this.API_URL}?action=login`, {
                method: 'POST',
                credentials: 'include',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    email: email,
                    password: password
                })
            });

            const result = await response.json();

            if (result.success && result.data) {
                console.log('✅ Login exitoso');
                this.currentUser = result.data;
                this.isAuthenticated = true;
                return { success: true, user: result.data };
            } else {
                console.log('❌ Login fallido:', result.message);
                return { success: false, message: result.message };
            }
        } catch (error) {
            console.error('❌ Error en login:', error);
            return { success: false, message: 'Error de conexión' };
        }
    }

    async logout() {
        console.log('🚪 Cerrando sesión...');

        // ============================================
        // NUEVO: Limpiar tokens JWT si existen
        // ============================================
        if (window.jwtManager) {
            await window.jwtManager.logout();
            console.log('✅ Tokens JWT limpiados');
        }

        // Detener worker de renovación
        if (window.tokenRefreshWorker) {
            window.tokenRefreshWorker.stop();
            console.log('⏹️ Worker de renovación detenido');
        }
        // ============================================

        // Probar múltiples endpoints para logout
        const endpoints = [
            './api/auth/login-compatible.php?action=logout',
            './api/auth/session.php?action=logout',
            '../api/auth/login-compatible.php?action=logout',
            '../api/auth/session.php?action=logout'
        ];

        let logoutSuccess = false;

        for (const endpoint of endpoints) {
            try {
                const response = await fetch(endpoint, {
                    method: 'POST',
                    credentials: 'include',
                    headers: {
                        'Content-Type': 'application/json'
                    }
                });

                if (!response.ok) continue;

                const result = await response.json();

                if (result.success) {
                    console.log(`✅ Logout exitoso desde ${endpoint}`);
                    logoutSuccess = true;
                    break;
                }
            } catch (error) {
                console.error(`❌ Error en logout ${endpoint}:`, error);
                continue;
            }
        }

        // Limpiar estado local independientemente del resultado
        this.currentUser = null;
        this.isAuthenticated = false;

        // Redirigir al login
        window.location.href = './pages/sign-in.html';
    }

    redirectToLogin() {
        console.log('🔄 Redirigiendo al login...');

        // Solo limpiar datos si definitivamente no hay sesión válida
        // Preservar parámetros importantes que indiquen navegación válida
        const isFromValidNavigation = document.referrer && document.referrer.includes(window.location.hostname);

        if (!isFromValidNavigation) {
            // Solo limpiar si no viene de navegación interna válida
            sessionStorage.removeItem('recentLogin');
            // NO limpiar todo sessionStorage para preservar navegación
        }

        // Determinar la ruta correcta basada en la ubicación actual
        const currentPath = window.location.pathname;
        const loginPath = './pages/sign-in.html';

        // Agregar parámetro para indicar de dónde viene
        const redirectUrl = `${loginPath}?from=${encodeURIComponent(window.location.pathname)}`;

        // Redirigir inmediatamente
        window.location.href = redirectUrl;
    }

    async getCurrentUser() {
        if (this.currentUser) {
            return this.currentUser;
        }

        // Probar múltiples endpoints para obtener usuario
        const endpoints = [
            './api/auth/login-compatible.php?action=user',
            './api/auth/session.php?action=user',
            '../api/auth/login-compatible.php?action=user',
            '../api/auth/session.php?action=user'
        ];

        for (const endpoint of endpoints) {
            try {
                const response = await fetch(endpoint, {
                    method: 'GET',
                    credentials: 'include',
                    headers: {
                        'Content-Type': 'application/json'
                    }
                });

                if (!response.ok) continue;

                const result = await response.json();

                if (result.success && result.data) {
                    this.currentUser = result.data;
                    return result.data;
                }
            } catch (error) {
                console.error(`❌ Error obteniendo usuario de ${endpoint}:`, error);
                continue;
            }
        }

        return null;
    }

    setupAuthenticatedUI() {
        console.log('🎨 Configurando interfaz para usuario autenticado...');

        // Ocultar elementos de login
        this.hideElements(['loginNavItem', 'signupNavItem', 'loginMenuItem', 'authRequiredMessage', 'restrictedOverlay']);

        // Mostrar elementos de usuario autenticado
        this.showElements(['logoutMenuItem', 'userMenuDropdown']);

        // Desbloquear contenido principal
        this.unlockContent();

        // Cargar información del usuario
        this.loadUserInfo();
    }

    setupUnauthenticatedUI() {
        console.log('🎨 Configurando interfaz para usuario no autenticado...');

        // Mostrar elementos de login
        this.showElements(['loginNavItem', 'signupNavItem', 'loginMenuItem', 'authRequiredMessage', 'restrictedOverlay']);

        // Ocultar elementos de usuario autenticado
        this.hideElements(['logoutMenuItem', 'userMenuDropdown', 'userInfo']);

        // Bloquear contenido principal
        this.lockContent();

        // Configurar enlaces de login
        this.setupLoginLinks();
    }

    setupTemporaryAccess() {
        console.log('🔄 Configurando acceso temporal...');

        // Crear usuario temporal
        this.currentUser = {
            id: 'temp',
            nombre: 'Usuario Temporal',
            apellidos: '',
            email: 'temporal@clúster.com',
            rol: 'usuario'
        };

        this.isAuthenticated = true;

        // Guardar timestamp para preservar navegación
        localStorage.setItem('lastAuthCheck', Date.now().toString());

        // Configurar UI como si estuviera autenticado
        this.setupAuthenticatedUI();

        // Mostrar mensaje de acceso temporal
        this.showTemporaryAccessMessage();
    }


    showTemporaryAccessMessage() {
        // Solo mostrar si realmente hay un login reciente válido
        console.log('ℹ️ Acceso temporal concedido por login reciente');
        // No mostrar mensaje visual para mejorar UX
    }


    hideElements(elementIds) {
        elementIds.forEach(id => {
            const element = document.getElementById(id);
            if (element) {
                element.style.display = 'none';
                element.classList.add('hidden');
            }
        });
    }

    showElements(elementIds) {
        elementIds.forEach(id => {
            const element = document.getElementById(id);
            if (element) {
                element.style.display = '';
                element.classList.remove('hidden');

                if (id === 'loginMenuItem') {
                    element.style.display = 'flex';
                }
                if (id === 'authRequiredMessage') {
                    element.style.display = 'block';
                }
            }
        });
    }

    setupLoginLinks() {
        const loginLinks = document.querySelectorAll('a[href*="sign-in.html"]');
        loginLinks.forEach(link => {
            link.onclick = null;
            if (!link.href.includes('/pages/sign-in.html')) {
                link.href = './pages/sign-in.html';
            }
        });
    }

    loadUserInfo() {
        if (!this.currentUser) return;

        const user = this.currentUser;

        // Actualizar elementos de información del usuario
        const userNameElement = document.getElementById('userName');
        const userRoleElement = document.getElementById('userRole');
        const userAvatarElement = document.getElementById('userAvatar');
        const userEmpresaElement = document.getElementById('userEmpresa');
        const userCargoElement = document.getElementById('userCargo');
        const userTelefonoElement = document.getElementById('userTelefono');
        const welcomeMessage = document.getElementById('welcomeMessage');

        if (userNameElement) {
            userNameElement.textContent = `${user.nombre} ${user.apellido || ''}`.trim() || user.email;
        }

        if (userRoleElement) {
            const rolesMap = {
                'admin': 'Administrador',
                'empresa': 'Empresa',
                'empleado': 'Empleado'
            };
            userRoleElement.textContent = rolesMap[user.rol] || 'Usuario';
        }

        if (userAvatarElement && user.avatar_url) {
            userAvatarElement.src = user.avatar_url;
        }

        if (userEmpresaElement) {
            userEmpresaElement.textContent = user.nombre_empresa || 'Sin empresa asignada';
        }

        if (userCargoElement) {
            userCargoElement.textContent = user.cargo || '-';
        }

        if (userTelefonoElement) {
            userTelefonoElement.textContent = user.telefono || '-';
        }

        if (welcomeMessage) {
            const nombreUsuario = user.nombre ? user.nombre.split(' ')[0] : 'Usuario';
            welcomeMessage.innerHTML = `<h6 class="mb-0 font-bold text-white capitalize">Bienvenido, ${nombreUsuario}</h6>`;
        }

        console.log('👤 Información de usuario cargada:', user.nombre || user.email);
        console.log('🏢 Empresa:', user.nombre_empresa);
        console.log('🎭 Rol:', user.rol);
    }

    lockContent() {
        console.log('🔒 Bloqueando contenido...');
        const mainContent = document.getElementById('mainContent');
        const restrictedOverlay = document.getElementById('restrictedOverlay');

        if (mainContent) {
            mainContent.classList.add('restricted-section');
        }

        if (restrictedOverlay) {
            restrictedOverlay.classList.remove('hidden');
        }
    }

    unlockContent() {
        console.log('🔓 Desbloqueando contenido...');
        const mainContent = document.getElementById('mainContent');
        const restrictedOverlay = document.getElementById('restrictedOverlay');

        if (mainContent) {
            mainContent.classList.remove('restricted-section');
        }

        if (restrictedOverlay) {
            restrictedOverlay.classList.add('hidden');
        }

        // Desbloquear elementos del menú específicamente
        this.unlockMenuItems();
    }

    unlockMenuItems() {
        console.log('🔓 Desbloqueando elementos del menú...');

        // 1. Desbloquear todos los elementos restricted-nav-item
        const restrictedNavItems = document.querySelectorAll('.restricted-nav-item');
        console.log(`📋 Encontrados ${restrictedNavItems.length} elementos restringidos`);

        restrictedNavItems.forEach((item, index) => {
            const link = item.querySelector('a[data-restricted="true"]');
            if (link) {
                // Agregar clase authenticated
                link.classList.add('authenticated');

                // Remover restricciones de estilo
                link.style.opacity = '1';
                link.style.cursor = 'pointer';
                link.style.pointerEvents = 'auto';

                // Remover el ícono de candado si existe
                const lockIcon = item.querySelector('.restricted-icon');
                if (lockIcon) {
                    lockIcon.style.display = 'none';
                }

                // También agregar authenticated al elemento padre
                item.classList.add('authenticated');

                const linkText = link.textContent.trim();
                console.log(`✅ Desbloqueado ${index + 1}: "${linkText}"`);
            }
        });

        // 2. Remover clase restricted-nav-item de todos los elementos para evitar conflictos CSS
        restrictedNavItems.forEach(item => {
            item.classList.add('nav-item-unlocked');
        });

        // 3. Habilitar todos los enlaces de navegación por si acaso
        const allNavLinks = document.querySelectorAll('nav a, .nav-link, a[data-restricted="true"]');
        allNavLinks.forEach(link => {
            link.style.pointerEvents = 'auto';
            link.style.opacity = '1';
            link.classList.remove('disabled');
            link.classList.add('authenticated');
        });

        // 4. Agregar CSS override temporal para forzar desbloqueado
        this.addUnlockStyles();

        console.log('✅ Proceso de desbloqueo de menú completado');
        console.log('🔍 Elementos procesados:', restrictedNavItems.length);
    }

    addUnlockStyles() {
        // Agregar estilos CSS para forzar desbloqueado
        const styleId = 'auth-unlock-styles';
        if (!document.getElementById(styleId)) {
            const style = document.createElement('style');
            style.id = styleId;
            style.textContent = `
                /* Forzar desbloqueo de elementos del menú */
                .restricted-nav-item.nav-item-unlocked a,
                .restricted-nav-item a.authenticated,
                a[data-restricted="true"].authenticated {
                    opacity: 1 !important;
                    cursor: pointer !important;
                    pointer-events: auto !important;
                    background-color: transparent !important;
                }
                
                .restricted-nav-item.nav-item-unlocked a:hover,
                .restricted-nav-item a.authenticated:hover,
                a[data-restricted="true"].authenticated:hover {
                    opacity: 1 !important;
                    background-color: rgba(255, 255, 255, 0.1) !important;
                }
                
                .restricted-icon {
                    display: none !important;
                }
                
                /* Desbloquear elementos de perfil específicamente */
                #profileMenuItem a,
                #profileMenuItem a.authenticated {
                    opacity: 1 !important;
                    pointer-events: auto !important;
                    cursor: pointer !important;
                }
            `;
            document.head.appendChild(style);
            console.log('✅ Estilos de desbloqueo agregados');
        }
    }
}

// Crear instancia global
window.authSessionManager = new AuthSessionManager();

// Función global para logout
window.logout = function () {
    window.authSessionManager.logout();
};

// Función para obtener usuario actual
window.getCurrentUser = function () {
    return window.authSessionManager.getCurrentUser();
};

// Función de emergencia para desbloquear menú manualmente
window.forceUnlockMenu = function () {
    console.log('🚨 Forzando desbloqueo manual del menú...');
    if (window.authSessionManager) {
        window.authSessionManager.unlockMenuItems();
    }

    // Desbloqueo adicional de emergencia
    const allRestrictedItems = document.querySelectorAll('.restricted-nav-item, [data-restricted="true"]');
    allRestrictedItems.forEach(item => {
        if (item.tagName === 'A') {
            item.style.opacity = '1';
            item.style.pointerEvents = 'auto';
            item.style.cursor = 'pointer';
            item.classList.add('authenticated');
        } else {
            const link = item.querySelector('a');
            if (link) {
                link.style.opacity = '1';
                link.style.pointerEvents = 'auto';
                link.style.cursor = 'pointer';
                link.classList.add('authenticated');
            }
        }
    });

    console.log('✅ Desbloqueo manual completado');
    alert('Menú desbloqueado manualmente. Si el problema persiste, recarga la página.');
};

// Función para forzar acceso completo
window.forceFullAccess = function () {
    console.log('🚨 FORZANDO ACCESO COMPLETO AL DASHBOARD...');

    if (window.authSessionManager) {
        console.log('🔄 Activando acceso temporal...');
        window.authSessionManager.setupTemporaryAccess();

        // Remover overlay de restricción
        const restrictedOverlay = document.getElementById('restrictedOverlay');
        if (restrictedOverlay) {
            restrictedOverlay.style.display = 'none';
            restrictedOverlay.classList.add('hidden');
        }

        // Remover mensajes de acceso restringido
        const authRequiredMessage = document.getElementById('authRequiredMessage');
        if (authRequiredMessage) {
            authRequiredMessage.style.display = 'none';
            authRequiredMessage.classList.add('hidden');
        }

        // Desbloquear contenido principal
        const mainContent = document.getElementById('mainContent');
        if (mainContent) {
            mainContent.classList.remove('restricted-section');
            mainContent.style.opacity = '1';
            mainContent.style.pointerEvents = 'auto';
        }

        console.log('✅ Acceso completo forzado');
        alert('🔓 Dashboard completamente desbloqueado!\n\nTodos los menús y funciones están disponibles.');
    }
};

// Función para verificar estado del menú
window.checkMenuStatus = function () {
    const restrictedItems = document.querySelectorAll('.restricted-nav-item');
    const authenticatedItems = document.querySelectorAll('.restricted-nav-item a.authenticated');

    console.log('📊 Estado del menú:');
    console.log(`- Total elementos restringidos: ${restrictedItems.length}`);
    console.log(`- Elementos autenticados: ${authenticatedItems.length}`);
    console.log(`- Usuario autenticado: ${window.authSessionManager?.isAuthenticated ? 'SÍ' : 'NO'}`);

    if (restrictedItems.length > 0 && authenticatedItems.length === 0) {
        console.log('⚠️ PROBLEMA: Elementos restringidos no están desbloqueados');
        console.log('💡 Ejecuta forceUnlockMenu() para desbloquear manualmente');
    } else if (authenticatedItems.length > 0) {
        console.log('✅ Elementos del menú están desbloqueados');
    }
};

console.log('🔧 Sistema de autenticación basado en sesiones inicializado');
console.log('🔐 Verificación de autenticación activa - redirección automática al login si no hay sesión válida');