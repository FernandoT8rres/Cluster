/**
 * Módulo para gestionar empresas en convenio
 * Funciones específicas para mostrar y gestionar empresas en la interfaz
 */

class EmpresasConvenioModule {
    constructor() {
        this.empresas = [];
        this.filtroActual = '';
        this.ordenActual = 'nombre';
        this.direccionOrden = 'asc';
    }

    /**
     * Inicializar el módulo
     */
    async init() {
        try {
            await this.cargarEmpresas();
            this.configurarEventListeners();
            this.renderizarEmpresas();
        } catch (error) {
            console.error('Error inicializando módulo de empresas:', error);
            this.mostrarError('Error al cargar las empresas en convenio');
        }
    }

    /**
     * Cargar empresas desde la API
     */
    async cargarEmpresas(limite = null, incluirEstadisticas = false) {
        try {
            const response = await api.getEmpresasConvenio(limite, incluirEstadisticas);
            
            if (response.empresas) {
                // Respuesta con estadísticas
                this.empresas = response.empresas;
                this.estadisticas = response.estadisticas;
            } else {
                // Respuesta simple
                this.empresas = Array.isArray(response) ? response : response.data || [];
            }
            
            return this.empresas;
        } catch (error) {
            console.error('Error cargando empresas:', error);
            throw error;
        }
    }

    /**
     * Renderizar empresas en la tabla del dashboard
     */
    renderizarEmpresasDashboard(limite = 10) {
        const tableBody = document.getElementById('companiesTableBody');
        if (!tableBody) return;

        // Remover skeleton si existe
        const skeletons = tableBody.querySelectorAll('.skeleton-row');
        skeletons.forEach(skeleton => skeleton.remove());

        if (this.empresas && this.empresas.length > 0) {
            const empresasLimitadas = limite ? this.empresas.slice(0, limite) : this.empresas;
            
            tableBody.innerHTML = empresasLimitadas.map(empresa => 
                this.generarFilaEmpresaDashboard(empresa)
            ).join('');
        } else {
            tableBody.innerHTML = this.generarMensajeVacio();
        }
    }

    /**
     * Generar fila para tabla del dashboard
     */
    generarFilaEmpresaDashboard(empresa) {
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
                                ${this.truncarTexto(empresa.descripcion || 'Empresa en convenio', 30)}
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
    }

    /**
     * Generar mensaje vacío
     */
    generarMensajeVacio() {
        return `
            <tr>
                <td colspan="4" class="p-4 text-center text-gray-500">
                    <div class="flex flex-col items-center">
                        <i class="fas fa-building text-3xl mb-2 opacity-50"></i>
                        <p>No hay empresas en convenio registradas</p>
                    </div>
                </td>
            </tr>
        `;
    }

    /**
     * Mostrar error
     */
    mostrarError(mensaje) {
        const tableBody = document.getElementById('companiesTableBody');
        if (tableBody) {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="4" class="p-4 text-center text-red-500">
                        <div class="flex flex-col items-center">
                            <i class="fas fa-exclamation-triangle text-3xl mb-2"></i>
                            <p>${mensaje}</p>
                            <button onclick="cargarEmpresasComites()" class="mt-2 px-3 py-1 text-xs bg-red-500 text-white rounded hover:bg-red-600">
                                Reintentar
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        }
    }

    /**
     * Utilidades
     */
    truncarTexto(texto, limite = 100) {
        if (!texto) return '';
        return texto.length > limite ? texto.substring(0, limite) + '...' : texto;
    }

    formatearFecha(fecha) {
        const date = new Date(fecha);
        if (isNaN(date.getTime())) return 'Fecha inválida';
        return date.toLocaleDateString('es-ES');
    }

    /**
     * Configurar event listeners básicos
     */
    configurarEventListeners() {
        // Event listeners básicos para el dashboard
        console.log('Event listeners configurados para empresas');
    }

    /**
     * Renderizar empresas (placeholder para página completa)
     */
    renderizarEmpresas() {
        console.log('Renderizando empresas:', this.empresas.length);
    }
}

// Instancia global del módulo
const empresasModule = new EmpresasConvenioModule();

// Exportar para uso global
if (typeof window !== 'undefined') {
    window.empresasModule = empresasModule;
}

// Función específica para el dashboard
window.cargarEmpresasComites = async function() {
    try {
        await empresasModule.cargarEmpresas(10, false);
        empresasModule.renderizarEmpresasDashboard(10);
    } catch (error) {
        console.error('Error cargando empresas para dashboard:', error);
        empresasModule.mostrarError('Error al cargar empresas en convenio');
    }
};

// Función para refrescar tabla
window.refreshCompaniesTable = async function() {
    try {
        await empresasModule.cargarEmpresas(10, false);
        empresasModule.renderizarEmpresasDashboard(10);
        mostrarNotificacion('Tabla actualizada correctamente', 'success');
    } catch (error) {
        console.error('Error actualizando tabla:', error);
        mostrarNotificacion('Error al actualizar la tabla', 'error');
    }
};

// Función para gestionar empresas en convenio (compatibilidad)
window.gestionarEmpresasConvenio = async function() {
    try {
        const response = await empresasModule.cargarEmpresas(null, true);
        return response;
    } catch (error) {
        console.error('Error gestionando empresas en convenio:', error);
        throw error;
    }
};
