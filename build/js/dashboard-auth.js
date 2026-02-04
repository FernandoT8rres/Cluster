/**
 * JavaScript para manejar autenticación y funcionalidades del dashboard
 */

// Variables globales
let isUserAuthenticated = false;
let currentUser = null;
let authToken = null;

// Inicialización del dashboard
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Dashboard inicializando...');
    
    // Verificar autenticación
    checkAuthentication();
    
    // Configurar eventos
    setupEventListeners();
    
    // Cargar contenido
    loadDashboardContent();
});

// Verificar estado de autenticación
function checkAuthentication() {
    try {
        // Obtener datos de localStorage
        authToken = localStorage.getItem('clúster_token');
        const userString = localStorage.getItem('clúster_user');
        
        console.log('🔍 Verificando autenticación...');
        console.log('Token presente:', !!authToken);
        console.log('Usuario presente:', !!userString);
        
        if (authToken && userString) {
            try {
                currentUser = JSON.parse(userString);
                console.log('👤 Usuario autenticado:', currentUser);
                
                // Verificar que el token sea válido
                if (isValidToken(authToken)) {
                    isUserAuthenticated = true;
                    enableAuthenticatedFeatures();
                    updateUserInterface();
                    showWelcomeMessage();
                } else {
                    console.warn('⚠️ Token inválido, limpiando sesión');
                    clearAuthData();
                }
            } catch (error) {
                console.error('❌ Error parsing usuario:', error);
                clearAuthData();
            }
        } else {
            console.log('⚠️ No hay datos de autenticación');
            disableAuthenticatedFeatures();
        }
        
        // Verificar parámetro authenticated en URL
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('authenticated') === 'true' && isUserAuthenticated) {
            console.log('✅ Acceso autenticado confirmado por URL');
        }
        
    } catch (error) {
        console.error('❌ Error en verificación de autenticación:', error);
        clearAuthData();
    }
}

// Verificar si el token es válido (básico)
function isValidToken(token) {
    if (!token || typeof token !== 'string') return false;
    
    try {
        // Verificar formato JWT básico (3 partes separadas por puntos)
        const parts = token.split('.');
        if (parts.length !== 3) return false;
        
        // Intentar decodificar el payload
        const payload = JSON.parse(atob(parts[1].replace(/-/g, '+').replace(/_/g, '/')));
        
        // Verificar expiración
        if (payload.exp && payload.exp < Math.floor(Date.now() / 1000)) {
            console.warn('⚠️ Token expirado');
            return false;
        }
        
        return true;
    } catch (error) {
        console.warn('⚠️ Token con formato inválido:', error);
        return false;
    }
}

// Habilitar funcionalidades autenticadas
function enableAuthenticatedFeatures() {
    console.log('✅ Habilitando funcionalidades autenticadas');
    
    // Remover clases de restricción
    document.querySelectorAll('.restricted-nav-item').forEach(item => {
        item.classList.add('authenticated');
        const link = item.querySelector('a[data-restricted="true"]');
        if (link) {
            link.classList.add('authenticated');
            link.style.opacity = '1';
            link.style.cursor = 'pointer';
        }
    });
    
    // Habilitar secciones restringidas
    document.querySelectorAll('.restricted-section').forEach(section => {
        section.style.filter = 'none';
        section.style.pointerEvents = 'auto';
        section.style.userSelect = 'auto';
    });
    
    // Remover overlays de restricción
    document.querySelectorAll('.restricted-overlay').forEach(overlay => {
        overlay.style.display = 'none';
    });
    
    // Mostrar elementos solo para usuarios autenticados
    document.querySelectorAll('[data-auth-required]').forEach(element => {
        element.style.display = 'block';
    });
    
    // Ocultar elementos solo para usuarios no autenticados
    document.querySelectorAll('[data-no-auth]').forEach(element => {
        element.style.display = 'none';
    });
}

// Deshabilitar funcionalidades autenticadas
function disableAuthenticatedFeatures() {
    console.log('⚠️ Deshabilitando funcionalidades autenticadas');
    
    // Aplicar restricciones visuales
    document.querySelectorAll('.restricted-nav-item').forEach(item => {
        item.classList.remove('authenticated');
        const link = item.querySelector('a[data-restricted="true"]');
        if (link) {
            link.classList.remove('authenticated');
            link.style.opacity = '0.6';
            link.style.cursor = 'not-allowed';
        }
    });
    
    // Mostrar mensaje de acceso restringido
    showRestrictedAccessMessage();
}

// Actualizar interfaz de usuario
function updateUserInterface() {
    if (!currentUser) return;
    
    console.log('🎨 Actualizando interfaz de usuario');
    
    // Actualizar nombre de usuario en elementos
    document.querySelectorAll('[data-user-name]').forEach(element => {
        element.textContent = currentUser.nombre || 'Usuario';
    });
    
    document.querySelectorAll('[data-user-email]').forEach(element => {
        element.textContent = currentUser.email || '';
    });
    
    document.querySelectorAll('[data-user-role]').forEach(element => {
        element.textContent = currentUser.rol || 'empleado';
    });
    
    // Mostrar opciones específicas para admin
    if (currentUser.rol === 'admin') {
        document.querySelectorAll('[data-admin-only]').forEach(element => {
            element.style.display = 'block';
        });
        
        console.log('👑 Funcionalidades de admin habilitadas');
    }
}

// Mostrar mensaje de bienvenida
function showWelcomeMessage() {
    if (!currentUser) return;
    
    const welcomeMsg = `¡Bienvenido, ${currentUser.nombre}!`;
    console.log('👋 ' + welcomeMsg);
    
    // Actualizar elementos de bienvenida
    document.querySelectorAll('[data-welcome-message]').forEach(element => {
        element.textContent = welcomeMsg;
    });
}

// Mostrar mensaje de acceso restringido
function showRestrictedAccessMessage() {
    const restrictedSections = document.querySelectorAll('.restricted-section');
    
    restrictedSections.forEach(section => {
        if (!section.querySelector('.restricted-overlay')) {
            const overlay = document.createElement('div');
            overlay.className = 'restricted-overlay';
            overlay.innerHTML = `
                <div class="restricted-message">
                    <i class="fas fa-lock text-3xl text-gray-400 mb-4"></i>
                    <h3 class="text-lg font-semibold text-gray-700 mb-2">Acceso Restringido</h3>
                    <p class="text-gray-500 mb-4">Para acceder a todas las funcionalidades, por favor inicia sesión.</p>
                    <a href="pages/sign-in.html" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition-colors">
                        Iniciar Sesión
                    </a>
                </div>
            `;
            section.appendChild(overlay);
        }
    });
}

// Cargar contenido del dashboard
function loadDashboardContent() {
    console.log('📊 Cargando contenido del dashboard...');
    
    // Cargar banners
    loadBanners();
    
    // Cargar estadísticas
    loadStatistics();
    
    // Cargar anuncios
    loadAnnouncements();
    
    // Cargar empresas destacadas
    loadFeaturedCompanies();
}

// Cargar banners
async function loadBanners() {
    console.log('🖼️ Cargando banners...');
    
    try {
        const response = await fetch('./api/banners.php?action=active');
        const data = await response.json();
        
        if (data.success && data.data && data.data.length > 0) {
            console.log(`✅ ${data.data.length} banners cargados`);
            displayBanners(data.data);
        } else {
            console.warn('⚠️ No hay banners disponibles');
            showNoBannersMessage();
        }
    } catch (error) {
        console.error('❌ Error cargando banners:', error);
        showBannersError();
    }
}

// Mostrar banners
function displayBanners(banners) {
    const bannerContainer = document.getElementById('bannerCarousel') || 
                           document.querySelector('[data-banner-container]') ||
                           document.querySelector('.banner-container');
    
    if (!bannerContainer) {
        console.warn('⚠️ Contenedor de banners no encontrado');
        return;
    }
    
    // Limpiar mensaje de carga
    const loadingMsg = bannerContainer.querySelector('[data-loading]');
    if (loadingMsg) {
        loadingMsg.style.display = 'none';
    }
    
    // Mostrar banners (implementar carrusel si es necesario)
    console.log('🖼️ Mostrando banners en el contenedor');
}

// Cargar estadísticas
async function loadStatistics() {
    if (!isUserAuthenticated) {
        console.log('ℹ️ Estadísticas requieren autenticación');
        return;
    }
    
    console.log('📊 Cargando estadísticas...');
    
    try {
        const response = await fetch('./api/estadisticas.php', {
            headers: {
                'Authorization': `Bearer ${authToken}`
            }
        });
        
        if (response.ok) {
            const data = await response.json();
            if (data.success) {
                displayStatistics(data.data);
                console.log('✅ Estadísticas cargadas');
            }
        }
    } catch (error) {
        console.error('❌ Error cargando estadísticas:', error);
        showStatsError();
    }
}

// Cargar anuncios importantes
async function loadAnnouncements() {
    console.log('📢 Cargando anuncios importantes...');
    
    try {
        const response = await fetch('./api/anuncios.php?active=true');
        const data = await response.json();
        
        if (data.success && data.data) {
            displayAnnouncements(data.data);
            console.log('✅ Anuncios cargados');
        } else {
            showNoAnnouncementsMessage();
        }
    } catch (error) {
        console.error('❌ Error cargando anuncios:', error);
        // Mostrar anuncios de ejemplo si falla la API
        showExampleAnnouncements();
    }
}

// Mostrar anuncios
function displayAnnouncements(announcements) {
    const announcementContainer = document.querySelector('[data-announcements]') ||
                                document.querySelector('.announcements-container') ||
                                document.getElementById('announcementsContainer');
    
    if (!announcementContainer) {
        console.warn('⚠️ Contenedor de anuncios no encontrado');
        return;
    }
    
    if (announcements.length === 0) {
        announcementContainer.innerHTML = '<p class="text-gray-500">No hay anuncios importantes en este momento.</p>';
        return;
    }
    
    const announcementsHTML = announcements.map(announcement => `
        <div class="announcement-item bg-blue-50 border-l-4 border-blue-500 p-4 mb-3 rounded-r">
            <div class="flex items-start">
                <i class="fas fa-bullhorn text-blue-500 mt-1 mr-3"></i>
                <div>
                    <h4 class="font-semibold text-blue-800">${announcement.titulo || 'Anuncio Importante'}</h4>
                    <p class="text-blue-700 text-sm mt-1">${announcement.contenido || announcement.descripcion}</p>
                    ${announcement.fecha ? `<span class="text-blue-600 text-xs">${new Date(announcement.fecha).toLocaleDateString()}</span>` : ''}
                </div>
            </div>
        </div>
    `).join('');
    
    announcementContainer.innerHTML = announcementsHTML;
}

// Mostrar anuncios de ejemplo si falla la API
function showExampleAnnouncements() {
    const exampleAnnouncements = [
        {
            titulo: "Bienvenido a Clúster Intranet",
            contenido: "Explora todas las funcionalidades disponibles en tu plataforma corporativa.",
            fecha: new Date().toISOString()
        },
        {
            titulo: "Actualización del Sistema",
            contenido: "Se han implementado mejoras en la velocidad y seguridad de la plataforma.",
            fecha: new Date(Date.now() - 86400000).toISOString() // Ayer
        }
    ];
    
    displayAnnouncements(exampleAnnouncements);
}

// Configurar event listeners
function setupEventListeners() {
    // Click en elementos restringidos
    document.addEventListener('click', function(e) {
        const restrictedElement = e.target.closest('[data-restricted="true"]');
        
        if (restrictedElement && !isUserAuthenticated) {
            e.preventDefault();
            showLoginPrompt();
        }
    });
    
    // Botón de logout si existe
    const logoutBtn = document.querySelector('[data-logout]');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', logout);
    }
}

// Mostrar prompt de login
function showLoginPrompt() {
    const confirmed = confirm('Para acceder a esta funcionalidad necesitas iniciar sesión. ¿Deseas ir al login ahora?');
    if (confirmed) {
        window.location.href = './pages/sign-in.html';
    }
}

// Limpiar datos de autenticación
function clearAuthData() {
    localStorage.removeItem('clúster_token');
    localStorage.removeItem('clúster_user');
    authToken = null;
    currentUser = null;
    isUserAuthenticated = false;
    
    console.log('🧹 Datos de autenticación limpiados');
    
    disableAuthenticatedFeatures();
}

// Logout
function logout() {
    if (confirm('¿Estás seguro de que deseas cerrar sesión?')) {
        clearAuthData();
        
        // Redirigir al login
        window.location.href = './pages/sign-in.html?message=Sesión cerrada exitosamente';
    }
}

// Funciones de error/mensajes
function showNoBannersMessage() {
    const bannerContainer = document.getElementById('bannerCarousel');
    if (bannerContainer) {
        bannerContainer.innerHTML = '<p class="text-gray-500 text-center p-4">No hay banners disponibles en este momento.</p>';
    }
}

function showBannersError() {
    const bannerContainer = document.getElementById('bannerCarousel');
    if (bannerContainer) {
        bannerContainer.innerHTML = '<p class="text-red-500 text-center p-4">Error cargando banners. Por favor, actualiza la página.</p>';
    }
}

function showNoAnnouncementsMessage() {
    console.log('ℹ️ No hay anuncios disponibles, mostrando mensaje');
}

function showStatsError() {
    console.warn('⚠️ Error cargando estadísticas');
    
    // Actualizar elementos de estadísticas con mensaje de error
    document.querySelectorAll('[data-stats-error]').forEach(element => {
        element.style.display = 'block';
        element.textContent = 'Error al cargar estadísticas';
    });
}

// Función de utilidad para mostrar estadísticas
function displayStatistics(stats) {
    // Implementar según la estructura de estadísticas
    console.log('📊 Mostrando estadísticas:', stats);
}

// Cargar empresas destacadas
async function loadFeaturedCompanies() {
    console.log('🏢 Cargando empresas destacadas...');
    
    try {
        const response = await fetch('./api/empresas.php?destacadas=true');
        const data = await response.json();
        
        if (data.success && data.data) {
            console.log('✅ Empresas destacadas cargadas');
        }
    } catch (error) {
        console.error('❌ Error cargando empresas:', error);
    }
}

// Exportar funciones si es necesario
window.clautDashboard = {
    checkAuthentication,
    loadDashboardContent,
    logout,
    isAuthenticated: () => isUserAuthenticated,
    getCurrentUser: () => currentUser
};