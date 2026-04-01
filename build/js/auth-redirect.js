// auth-redirect.js
// Sistema de autenticación que NO redirige automáticamente, solo maneja la UI

class AuthRedirectManager {
    constructor() {
        this.init();
    }

    init() {
        // Ejecutar verificación al cargar la página
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.checkAuthentication());
        } else {
            this.checkAuthentication();
        }
    }

    checkAuthentication() {
        // console.log('🔐 Verificando autenticación...');
        
        // Si estamos en páginas de autenticación, no hacer nada
        if (window.location.pathname.includes('sign-in.html') || 
            window.location.pathname.includes('sign-up.html')) {
            // console.log('📍 En página de autenticación, saltando verificación');
            return;
        }
        
        // Obtener token y datos de usuario
        const token = localStorage.getItem('clúster_token');
        const userData = localStorage.getItem('clúster_user');
        const urlParams = new URLSearchParams(window.location.search);
        const authenticatedParam = urlParams.get('authenticated');
        
        // console.log('📋 Estado:', {
            token: !!token,
            userData: !!userData,
            authenticatedParam: authenticatedParam,
            pathname: window.location.pathname
        });

        // Si hay token, verificar que sea válido en el servidor
        if (token) {
            // console.log('🔍 Token encontrado, verificando validez en servidor...');
            this.verifyTokenWithServer(token);
        } else {
            // console.log('❌ No hay token, usuario no autenticado');
            this.setupUnauthenticatedUI();
        }
        
        // Limpiar URL si viene de login exitoso
        if (authenticatedParam) {
            this.cleanURL();
        }
    }

    async verifyTokenWithServer(token) {
        try {
            // console.log('🔗 Verificando token con servidor...');
            
            const response = await fetch('./api/auth/verify.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${token}`
                },
                body: JSON.stringify({ token: token })
            });

            const data = await response.json();
            
            if (data.success && data.user) {
                // console.log('✅ Token válido en servidor');
                // Actualizar datos de usuario con la información más reciente del servidor
                localStorage.setItem('clúster_user', JSON.stringify(data.user));
                this.setupAuthenticatedUI();
            } else {
                // console.log('❌ Token inválido o expirado en servidor');
                // Limpiar localStorage si el token no es válido
                localStorage.removeItem('clúster_token');
                localStorage.removeItem('clúster_user');
                this.setupUnauthenticatedUI();
            }
        } catch (error) {
            console.error('❌ Error verificando token:', error);
            // En caso de error de red, asumir que no está autenticado por seguridad
            this.setupUnauthenticatedUI();
        }
    }

    setupAuthenticatedUI() {
        // console.log('🎨 Configurando interfaz para usuario autenticado...');
        
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
        // console.log('🎨 Configurando interfaz para usuario no autenticado...');
        
        // Mostrar elementos de login
        this.showElements(['loginNavItem', 'signupNavItem', 'loginMenuItem', 'authRequiredMessage', 'restrictedOverlay']);
        
        // Ocultar elementos de usuario autenticado
        this.hideElements(['logoutMenuItem', 'userMenuDropdown', 'userInfo']);
        
        // Bloquear contenido principal
        this.lockContent();
        
        // Asegurar que todos los enlaces de login funcionen
        this.setupLoginLinks();
    }

    hideElements(elementIds) {
        elementIds.forEach(id => {
            const element = document.getElementById(id);
            if (element) {
                element.style.display = 'none';
                element.classList.add('hidden');
                // console.log(`🫥 Ocultado: ${id}`);
            }
        });
    }

    showElements(elementIds) {
        elementIds.forEach(id => {
            const element = document.getElementById(id);
            if (element) {
                element.style.display = '';
                element.classList.remove('hidden');
                
                // Para el botón de login del navbar, usar display flex
                if (id === 'loginMenuItem') {
                    element.style.display = 'flex';
                }
                // Para el mensaje de autenticación, usar display block
                if (id === 'authRequiredMessage') {
                    element.style.display = 'block';
                }
                
                // console.log(`👁️ Mostrado: ${id}`);
            }
        });
    }

    setupLoginLinks() {
        // Asegurar que todos los enlaces de login funcionen correctamente
        const loginLinks = document.querySelectorAll('a[href*="sign-in.html"]');
        loginLinks.forEach(link => {
            // Remover cualquier event listener previo que pueda interferir
            link.onclick = null;
            
            // Asegurar que el href esté correcto
            if (!link.href.includes('/pages/sign-in.html')) {
                link.href = './pages/sign-in.html';
            }
            
            // console.log('🔗 Configurado enlace de login:', link.href);
        });
        
        // console.log(`✅ ${loginLinks.length} enlaces de login configurados`);
    }

    loadUserInfo() {
        try {
            const userData = localStorage.getItem('clúster_user');
            if (userData) {
                const user = JSON.parse(userData);
                
                // Actualizar elementos de información del usuario
                const userNameElement = document.getElementById('userName');
                const userRoleElement = document.getElementById('userRole');
                const welcomeMessage = document.getElementById('welcomeMessage');
                
                if (userNameElement) {
                    userNameElement.textContent = user.nombre || user.email;
                }
                
                if (userRoleElement) {
                    userRoleElement.textContent = user.rol || 'Usuario';
                }
                
                if (welcomeMessage) {
                    const nombreUsuario = user.nombre ? user.nombre.split(' ')[0] : 'Usuario';
                    welcomeMessage.innerHTML = `<h6 class="mb-0 font-bold text-white capitalize">Bienvenido, ${nombreUsuario}</h6>`;
                }

                // console.log('👤 Información de usuario cargada:', user.nombre || user.email);
            }
        } catch (error) {
            console.error('❌ Error cargando información del usuario:', error);
        }
    }

    cleanURL() {
        // Limpiar parámetros de autenticación de la URL
        const url = new URL(window.location);
        url.searchParams.delete('authenticated');
        window.history.replaceState({}, document.title, url.pathname);
        // console.log('🧹 URL limpiada');
    }

    redirectToLogin() {
        // console.log('🚪 Redirigiendo al login...');
        window.location.href = './pages/sign-in.html';
    }

    // Método para logout
    logout() {
        // console.log('🚪 Cerrando sesión...');
        
        // Limpiar localStorage
        localStorage.removeItem('clúster_token');
        localStorage.removeItem('clúster_user');
        
        // Redirigir al login
        this.redirectToLogin();
    }
    
    lockContent() {
        // console.log('🔒 Bloqueando contenido...');
        const mainContent = document.getElementById('mainContent');
        const restrictedOverlay = document.getElementById('restrictedOverlay');
        
        if (mainContent) {
            mainContent.classList.add('restricted-section');
            // console.log('✅ Contenido principal bloqueado');
        }
        
        if (restrictedOverlay) {
            restrictedOverlay.classList.remove('hidden');
            // console.log('✅ Overlay de restricción mostrado');
        }
    }
    
    unlockContent() {
        // console.log('🔓 Desbloqueando contenido...');
        const mainContent = document.getElementById('mainContent');
        const restrictedOverlay = document.getElementById('restrictedOverlay');
        
        if (mainContent) {
            mainContent.classList.remove('restricted-section');
            // console.log('✅ Contenido principal desbloqueado');
        }
        
        if (restrictedOverlay) {
            restrictedOverlay.classList.add('hidden');
            // console.log('✅ Overlay de restricción ocultado');
        }
    }
}

// Inicializar el sistema de autenticación
const authManager = new AuthRedirectManager();

// Hacer disponible globalmente
window.authManager = authManager;
window.logout = function() {
    authManager.logout();
};

// console.log('🔧 Sistema de autenticación inicializado');
