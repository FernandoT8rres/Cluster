/**
 * Frontend Integration - Clúster Intranet
 * Manejo de APIs, autenticación y funcionalidades del frontend
 */

// Configuración de la API
const API_BASE_URL = window.location.origin;

// Clase para manejo de API
class ClústerAPI {
    constructor() {
        this.baseURL = API_BASE_URL;
        // No usamos tokens en localStorage - autenticación por sesión
        this.token = null;
    }

    // Hacer petición HTTP
    async request(endpoint, options = {}) {
        const url = `${this.baseURL}/${endpoint}`;
        const config = {
            headers: {
                'Content-Type': 'application/json',
                ...options.headers
            },
            ...options
        };

        // Agregar token si existe
        if (this.token) {
            config.headers.Authorization = `Bearer ${this.token}`;
        }

        try {
            const response = await fetch(url, config);
            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || 'Error en la petición');
            }

            return data;
        } catch (error) {
            console.error('API Error:', error);
            throw error;
        }
    }

    // Métodos GET
    async get(endpoint) {
        return this.request(endpoint, { method: 'GET' });
    }

    // Métodos POST
    async post(endpoint, data) {
        return this.request(endpoint, {
            method: 'POST',
            body: JSON.stringify(data)
        });
    }

    // Métodos PUT
    async put(endpoint, data) {
        return this.request(endpoint, {
            method: 'PUT',
            body: JSON.stringify(data)
        });
    }

    // Métodos DELETE
    async delete(endpoint) {
        return this.request(endpoint, { method: 'DELETE' });
    }

    // ==================== USUARIOS ====================
    async login(email, password) {
        try {
            const response = await this.post('api/auth/login.php', { email, password });
            if (response.success && response.token) {
                this.token = response.token;
                // Los datos se guardan en sesión del servidor, no en localStorage
            }
            return response;
        } catch (error) {
            throw error;
        }
    }

    async register(userData) {
        return this.post('api/auth/register.php', userData);
    }

    async logout() {
        try {
            await this.post('api/auth/logout.php');
        } finally {
            this.token = null;
            // No usamos localStorage - limpiar sesión del servidor si es necesario
        }
    }

    async getCurrentUser() {
        return this.get('api/auth/me.php');
    }

    async updateProfile(userData) {
        return this.put('api/auth/profile.php', userData);
    }

    // ==================== EMPRESAS ====================
    async getEmpresas(limit = null) {
        const endpoint = limit ? `api/empresas.php?limit=${limit}` : 'api/empresas.php';
        return this.get(endpoint);
    }

    async getEmpresa(id) {
        return this.get(`api/empresas.php?id=${id}`);
    }

    async createEmpresa(empresaData) {
        return this.post('api/empresas.php', empresaData);
    }

    async updateEmpresa(id, empresaData) {
        return this.put(`api/empresas.php?id=${id}`, empresaData);
    }

    // ==================== EMPRESAS EN CONVENIO ====================
    async getEmpresasConvenio(limit = null, includeStats = false) {
        let endpoint = 'api/empresas/convenio.php';
        const params = [];
        
        if (limit) params.push(`limit=${limit}`);
        if (includeStats) params.push('include_stats=true');
        
        if (params.length > 0) {
            endpoint += '?' + params.join('&');
        }
        
        return this.get(endpoint);
    }

    async getEmpresaConvenio(id) {
        return this.get(`api/empresas/convenio.php?id=${id}`);
    }

    async createEmpresaConvenio(empresaData) {
        return this.post('api/empresas/convenio.php', empresaData);
    }

    async updateEmpresaConvenio(id, empresaData) {
        return this.put(`api/empresas/convenio.php?id=${id}`, empresaData);
    }

    async deleteEmpresaConvenio(id) {
        return this.delete(`api/empresas/convenio.php?id=${id}`);
    }

    // ==================== COMITÉS ====================
    async getComites() {
        return this.get('api/comites.php');
    }

    async getComite(id) {
        return this.get(`api/comites.php?id=${id}`);
    }

    async getComiteMiembros(comiteId) {
        return this.get(`api/comites.php?id=${comiteId}&action=miembros`);
    }

    // ==================== EVENTOS ====================
    async getEventos(limit = null) {
        const endpoint = limit ? `api/eventos.php?limit=${limit}` : 'api/eventos.php';
        return this.get(endpoint);
    }

    async getEvento(id) {
        return this.get(`api/eventos.php?id=${id}`);
    }

    async registrarseEvento(eventoId) {
        return this.post('api/eventos.php', { action: 'registrar', evento_id: eventoId });
    }

    // ==================== BOLETINES ====================
    async getBoletines(limit = null) {
        const endpoint = limit ? `api/boletines.php?limit=${limit}` : 'api/boletines.php';
        return this.get(endpoint);
    }

    async getBoletin(id) {
        return this.get(`api/boletines.php?id=${id}`);
    }

    // ==================== DESCUENTOS ====================
    async getDescuentos(limit = null) {
        const endpoint = limit ? `api/descuentos.php?limit=${limit}` : 'api/descuentos.php';
        return this.get(endpoint);
    }

    async getDescuento(id) {
        return this.get(`api/descuentos.php?id=${id}`);
    }

    async usarDescuento(descuentoId, comentarios = '') {
        return this.post('api/descuentos.php', { 
            action: 'usar', 
            descuento_id: descuentoId, 
            comentarios 
        });
    }

    // ==================== ESTADÍSTICAS ====================
    async getEstadisticas() {
        return this.get('api/estadisticas.php');
    }

    async getEmpresasConMiembros() {
        return this.get('api/empresas.php?action=con_miembros');
    }
}

// Instancia global de la API
const api = new ClústerAPI();

// ==================== FUNCIONES DE UTILIDAD ====================

// Mostrar notificaciones
function mostrarNotificacion(mensaje, tipo = 'info', duracion = 5000) {
    const container = document.getElementById('notificationContainer') || createNotificationContainer();
    
    const notification = document.createElement('div');
    notification.className = `notification notification-${tipo} transform translate-x-full transition-transform duration-300 ease-in-out`;
    
    const iconMap = {
        success: 'fas fa-check-circle',
        error: 'fas fa-exclamation-circle',
        warning: 'fas fa-exclamation-triangle',
        info: 'fas fa-info-circle'
    };

    const colorMap = {
        success: 'bg-green-500',
        error: 'bg-red-500',
        warning: 'bg-yellow-500',
        info: 'bg-blue-500'
    };

    notification.innerHTML = `
        <div class="flex items-center p-4 rounded-lg shadow-lg text-white ${colorMap[tipo]} max-w-sm">
            <i class="${iconMap[tipo]} mr-3"></i>
            <span class="flex-1">${mensaje}</span>
            <button onclick="this.parentElement.parentElement.remove()" class="ml-2 text-white hover:text-gray-200">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;

    container.appendChild(notification);

    // Animar la entrada
    setTimeout(() => {
        notification.classList.remove('translate-x-full');
    }, 100);

    // Auto-remover después de la duración especificada
    setTimeout(() => {
        notification.classList.add('translate-x-full');
        setTimeout(() => notification.remove(), 300);
    }, duracion);

    return notification;
}

// Crear contenedor de notificaciones si no existe
function createNotificationContainer() {
    const container = document.createElement('div');
    container.id = 'notificationContainer';
    container.className = 'fixed top-4 right-4 z-50 space-y-2';
    document.body.appendChild(container);
    return container;
}

// Verificar autenticación
function isAuthenticated() {
    // Verificar autenticación desde sesión del servidor
    const token = null; // No usamos tokens en localStorage
    const user = null; // Los datos vienen de la sesión del servidor
    return !!(token && user);
}

// Obtener usuario actual
function getCurrentUser() {
    // Obtener usuario desde sesión del servidor
    const userStr = window.authSessionManager && window.authSessionManager.currentUser ? JSON.stringify(window.authSessionManager.currentUser) : null;
    try {
        return userStr ? JSON.parse(userStr) : null;
    } catch {
        return null;
    }
}

// Formatear fecha
function formatearFecha(fecha, formato = 'dd/mm/yyyy') {
    const date = new Date(fecha);
    if (isNaN(date.getTime())) return 'Fecha inválida';

    const opciones = {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
    };

    if (formato === 'dd/mm/yyyy') {
        return date.toLocaleDateString('es-ES');
    } else if (formato === 'dd/mm/yyyy hh:mm') {
        return date.toLocaleDateString('es-ES') + ' ' + date.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });
    }

    return date.toLocaleDateString('es-ES', opciones);
}

// Truncar texto
function truncarTexto(texto, limite = 100) {
    if (!texto) return '';
    return texto.length > limite ? texto.substring(0, limite) + '...' : texto;
}

// ==================== FUNCIONES ESPECÍFICAS DE PÁGINAS ====================

// Cargar estadísticas del dashboard
async function cargarEstadisticas() {
    try {
        const stats = await api.getEstadisticas();
        
        // Actualizar tarjetas de estadísticas
        if (stats.comites !== undefined) {
            updateStatCard('statsComites', stats.comites);
        }
        if (stats.empresas !== undefined) {
            updateStatCard('statsEmpresas', stats.empresas);
        }
        if (stats.descuentos !== undefined) {
            updateStatCard('statsDescuentos', stats.descuentos);
        }
        if (stats.eventos !== undefined) {
            updateStatCard('statsEventos', stats.eventos);
        }

        return stats;
    } catch (error) {
        console.error('Error cargando estadísticas:', error);
        mostrarNotificacion('Error al cargar estadísticas', 'error');
    }
}

// Cargar empresas en convenio para la tabla del dashboard
async function cargarEmpresasComites() {
    try {
        // Intentar cargar desde la API primero
        try {
            const response = await api.getEmpresasConvenio(10, false);
            const empresas = response.data || response;
            
            if (empresas && empresas.length > 0) {
                renderizarEmpresasEnTabla(empresas);
                return;
            }
        } catch (apiError) {
            console.log('API no disponible, usando datos de ejemplo');
        }
        
        // Si la API falla o no hay datos, usar datos de ejemplo
        // NO usar datos estáticos - solo base de datos
        console.log('❌ Datos estáticos de empresas eliminados - solo se usa la BD');
        mostrarMensajeVacio();
        
    } catch (error) {
        console.error('Error cargando empresas:', error);
        mostrarErrorEnTabla();
    }
}

function renderizarEmpresasEnTabla(empresas) {
    const tableBody = document.getElementById('companiesTableBody');
    if (!tableBody) return;

    // Remover skeleton
    const skeletons = tableBody.querySelectorAll('.skeleton-row');
    skeletons.forEach(skeleton => skeleton.remove());

    if (empresas && empresas.length > 0) {
        tableBody.innerHTML = empresas.map(empresa => {
            const logoUrl = empresa.logo || './assets/img/icons/flags/nissan.jpg';
            const estadoClass = empresa.estado === 'activa' ? 
                'text-emerald-500 border-emerald-500 bg-emerald-50' : 
                'text-red-500 border-red-500 bg-red-50';
            
            return `
                <tr class="hover:bg-gray-50 transition-colors duration-200">
                    <td class="p-2 align-middle bg-transparent border-b w-3/10 whitespace-nowrap dark:border-white/40">
                        <div class="flex items-center px-2 py-1">
                            <div>
                                <img src="${logoUrl}" 
                                     class="inline-flex items-center justify-center mr-4 text-sm text-white transition-all duration-200 ease-in-out h-9 w-9 rounded-xl object-cover" 
                                     alt="${empresa.nombre}" 
                                     onerror="this.src='./assets/img/icons/flags/nissan.jpg'">
                            </div>
                            <div class="flex flex-col justify-center">
                                <h6 class="mb-0 text-sm leading-normal dark:text-white font-semibold">${empresa.nombre}</h6>
                                <p class="mb-0 text-xs leading-tight dark:text-white dark:opacity-80 text-slate-400">
                                    ${truncarTexto(empresa.descripcion || 'Empresa en convenio', 30)}
                                </p>
                            </div>
                        </div>
                    </td>
                    <td class="p-2 align-middle bg-transparent border-b whitespace-nowrap dark:border-white/40">
                        <div class="text-center">
                            <p class="mb-0 text-xs font-semibold leading-tight dark:text-white dark:opacity-80">
                                ${empresa.total_empleados || 0}
                            </p>
                            <p class="mb-0 text-xs leading-tight dark:text-white dark:opacity-80 text-slate-400">Empleados</p>
                        </div>
                    </td>
                    <td class="p-2 align-middle bg-transparent border-b whitespace-nowrap dark:border-white/40">
                        <div class="text-center">
                            <p class="mb-0 text-xs font-semibold leading-tight dark:text-white dark:opacity-80">
                                ${empresa.comites_participando || 0}
                            </p>
                            <p class="mb-0 text-xs leading-tight dark:text-white dark:opacity-80 text-slate-400">Comités</p>
                        </div>
                    </td>
                    <td class="p-2 text-sm leading-normal align-middle bg-transparent border-b whitespace-nowrap dark:border-white/40">
                        <div class="flex-1 text-center">
                            <span class="px-2 py-1 text-xs font-bold uppercase border border-solid rounded-xl ${estadoClass}">
                                ${empresa.estado === 'activa' ? 'Activa' : 'Inactiva'}
                            </span>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');
    } else {
        mostrarMensajeVacio();
    }
}

function mostrarMensajeVacio() {
    const tableBody = document.getElementById('companiesTableBody');
    if (tableBody) {
        tableBody.innerHTML = `
            <tr>
                <td colspan="4" class="p-4 text-center text-gray-500">
                    <div class="flex flex-col items-center">
                        <i class="fas fa-building text-3xl mb-2 opacity-50"></i>
                        <p>No hay empresas en convenio registradas</p>
                        <small class="text-xs mt-1 text-gray-400">Configure la base de datos para ver datos reales</small>
                    </div>
                </td>
            </tr>
        `;
    }
}

function mostrarErrorEnTabla() {
    const tableBody = document.getElementById('companiesTableBody');
    if (tableBody) {
        tableBody.innerHTML = `
            <tr>
                <td colspan="4" class="p-4 text-center text-red-500">
                    <div class="flex flex-col items-center">
                        <i class="fas fa-exclamation-triangle text-3xl mb-2"></i>
                        <p>Error al cargar empresas en convenio</p>
                        <button onclick="cargarEmpresasComites()" class="mt-2 px-3 py-1 text-xs bg-red-500 text-white rounded hover:bg-red-600 transition-colors">
                            Reintentar
                        </button>
                        <small class="text-xs mt-1 text-gray-400">Verifique la configuración de la base de datos</small>
                    </div>
                </td>
            </tr>
        `;
    }
}

// Función específica para gestionar empresas en convenio
async function gestionarEmpresasConvenio() {
    try {
        const response = await api.getEmpresasConvenio(null, true); // Sin límite y con estadísticas
        return response;
    } catch (error) {
        console.error('Error gestionando empresas en convenio:', error);
        throw error;
    }
}

// Manejar logout
async function handleLogout() {
    try {
        await api.logout();
        mostrarNotificacion('Sesión cerrada correctamente', 'success');
        setTimeout(() => {
            window.location.reload();
        }, 1000);
    } catch (error) {
        console.error('Error en logout:', error);
        // Forzar logout local aunque falle el servidor
        localStorage.removeItem('clúster_token');
        localStorage.removeItem('clúster_user');
        window.location.reload();
    }
}

// ==================== EVENT LISTENERS ====================

// Inicialización cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    // Configurar botón de logout
    const logoutBtn = document.getElementById('logoutBtn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', handleLogout);
    }

    // Cargar datos iniciales si estamos en el dashboard
    if (window.location.pathname.includes('dashboard.html') || window.location.pathname === '/') {
        cargarEstadisticas();
        cargarEmpresasComites();
    }

    // Verificar autenticación cada 5 minutos
    setInterval(() => {
        if (isAuthenticated()) {
            api.getCurrentUser().catch(() => {
                mostrarNotificacion('Sesión expirada. Por favor, inicia sesión nuevamente.', 'warning');
                setTimeout(() => {
                    window.location.href = './pages/sign-in.html';
                }, 2000);
            });
        }
    }, 5 * 60 * 1000); // 5 minutos
});

// Exportar funciones globales
window.api = api;
window.mostrarNotificacion = mostrarNotificacion;
window.isAuthenticated = isAuthenticated;
window.getCurrentUser = getCurrentUser;
window.handleLogout = handleLogout;
window.cargarEstadisticas = cargarEstadisticas;
window.cargarEmpresasComites = cargarEmpresasComites;
window.gestionarEmpresasConvenio = gestionarEmpresasConvenio;
