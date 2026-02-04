/**
 * Módulo para cargar y mostrar empresas destacadas en la página principal
 * Se conecta a la base de datos para obtener empresas reales marcadas como destacadas
 */

class EmpresasDestacadasIndex {
    constructor() {
        this.API_URL = './api/empresas_convenio.php';
        this.empresasDestacadas = [];
        this.containerId = 'companiesTableBody';
        this.isLoading = false;
    }

    async init() {
        console.log('Inicializando empresas destacadas en index...');
        await this.cargarEmpresasDestacadas();
    }

    async cargarEmpresasDestacadas() {
        console.log('🔍 Iniciando carga de empresas destacadas...');
        const container = document.getElementById(this.containerId);
        if (!container) {
            console.warn('❌ Container para empresas no encontrado:', this.containerId);
            return;
        }
        console.log('✅ Container encontrado:', container);

        this.mostrarCargando();

        try {
            const apiUrl = `${this.API_URL}?destacado=1&activo=1&limit=6&orderBy=updated_at&order=DESC`;
            console.log('📡 Fetching from API:', apiUrl);
            console.log('🌐 Current URL:', window.location.href);
            console.log('📂 Base URL:', window.location.origin + window.location.pathname);
            
            // Obtener empresas destacadas y activas de la base de datos
            const response = await fetch(apiUrl);
            console.log('📊 Response status:', response.status, response.statusText);
            console.log('📊 Response headers:', Object.fromEntries([...response.headers]));
            
            if (!response.ok) {
                const errorText = await response.text();
                console.error('❌ Response error text:', errorText);
                throw new Error(`HTTP error! status: ${response.status} - ${errorText}`);
            }

            const responseText = await response.text();
            console.log('📄 Raw response:', responseText.substring(0, 500) + '...');
            
            let result;
            try {
                result = JSON.parse(responseText);
            } catch (parseError) {
                console.error('❌ JSON Parse Error:', parseError);
                console.error('❌ Response was not valid JSON:', responseText);
                throw new Error('Invalid JSON response from server');
            }
            
            console.log('📋 API Response:', result);
            
            if (result.success && result.data && result.data.length > 0) {
                console.log(`✅ Empresas encontradas: ${result.data.length}`);
                this.empresasDestacadas = result.data;
                this.renderizarEmpresasEnTabla();
                this.ocultarMensajeVacio();
            } else {
                console.log('⚠️ No hay empresas destacadas o respuesta vacía');
                console.log('📊 Result details:', {
                    success: result.success,
                    dataExists: !!result.data,
                    dataLength: result.data ? result.data.length : 'N/A',
                    message: result.message
                });
                this.mostrarMensajeError(result.message || 'No se encontraron empresas destacadas');
            }
        } catch (error) {
            console.error('❌ Error cargando empresas destacadas:', error);
            console.error('❌ Error stack:', error.stack);
            this.mostrarMensajeError(`Error de conexión: ${error.message}`);
        }
    }

    mostrarCargando() {
        const container = document.getElementById(this.containerId);
        if (!container) return;

        // Ocultar mensaje de "no empresas" si existe
        this.ocultarMensajeVacio();

        container.innerHTML = `
            <tr class="skeleton-row">
                <td colspan="4" class="p-4 text-center">
                    <div class="flex justify-center">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
                    </div>
                    <p class="text-sm text-gray-500 mt-2">Cargando empresas desde la base de datos...</p>
                </td>
            </tr>
        `;
    }

    renderizarEmpresasEnTabla() {
        const container = document.getElementById(this.containerId);
        if (!container) return;

        const empresasHTML = this.empresasDestacadas.map(empresa => this.crearFilaEmpresa(empresa)).join('');
        container.innerHTML = empresasHTML;

        // Agregar efecto de entrada
        this.aplicarAnimacionEntrada();
    }

    crearFilaEmpresa(empresa) {
        const logoUrl = empresa.logo_url || 
            `https://ui-avatars.com/api/?name=${encodeURIComponent(empresa.nombre_empresa)}&background=random&size=40`;
        
        const descuento = empresa.descuento > 0 ? `${empresa.descuento}%` : 'Consultar';
        
        const estadoBadge = empresa.activo 
            ? '<span class="text-emerald-500 text-xs font-semibold"><i class="fas fa-check-circle mr-1"></i>Activo</span>'
            : '<span class="text-red-500 text-xs font-semibold"><i class="fas fa-times-circle mr-1"></i>Inactivo</span>';

        const categoria = empresa.categoria || 'General';

        return `
            <tr class="empresa-row opacity-0 transform translate-y-2">
                <td class="p-2 align-middle bg-transparent border-b whitespace-nowrap shadow-transparent">
                    <div class="flex px-2 py-1">
                        <div class="mr-2">
                            <img src="${logoUrl}" 
                                 alt="${empresa.nombre_empresa}" 
                                 class="inline-flex items-center justify-center w-9 h-9 text-sm text-white transition-all duration-200 ease-in-out rounded-full bg-gradient-to-tl from-blue-600 to-cyan-400"
                                 onerror="this.src='https://ui-avatars.com/api/?name=${encodeURIComponent(empresa.nombre_empresa)}&background=random&size=40'">
                        </div>
                        <div class="flex flex-col justify-center">
                            <h6 class="mb-0 text-sm leading-normal dark:text-white font-semibold">${empresa.nombre_empresa}</h6>
                            <p class="mb-0 text-xs leading-tight text-slate-400">${empresa.descripcion || 'Empresa colaboradora'}</p>
                        </div>
                    </div>
                </td>
                <td class="p-2 text-center align-middle bg-transparent border-b whitespace-nowrap shadow-transparent">
                    <span class="bg-gradient-to-tl from-blue-600 to-cyan-400 px-2.5 text-xs rounded-1.8 py-1.4 inline-block whitespace-nowrap text-center align-baseline font-bold uppercase leading-none text-white">
                        ${categoria}
                    </span>
                </td>
                <td class="p-2 text-center align-middle bg-transparent border-b whitespace-nowrap shadow-transparent">
                    <span class="text-xs font-semibold leading-tight dark:text-white dark:opacity-80 text-slate-400">
                        ${descuento}
                    </span>
                </td>
                <td class="p-2 text-center align-middle bg-transparent border-b whitespace-nowrap shadow-transparent">
                    ${estadoBadge}
                    ${empresa.destacado ? '<span class="text-yellow-500 text-xs ml-2"><i class="fas fa-star"></i></span>' : ''}
                </td>
            </tr>
        `;
    }

    mostrarEmpresasEjemplo() {
        // No mostrar datos de ejemplo - solo mensaje de error
        this.empresasDestacadas = [];
        this.mostrarMensajeError('No hay conexión a la base de datos. Las empresas destacadas se cargarán cuando esté disponible la conexión.');
    }

    mostrarMensajeDemo() {
        const table = document.getElementById('companiesTable');
        if (!table) return;

        // Crear mensaje de demo si no existe
        let demoMessage = document.getElementById('demoEmpresasMessage');
        if (!demoMessage) {
            demoMessage = document.createElement('div');
            demoMessage.id = 'demoEmpresasMessage';
            demoMessage.className = 'text-center mt-4 p-3 bg-blue-50 border border-blue-200 rounded-lg';
            demoMessage.innerHTML = `
                <p class="text-sm text-blue-800">
                    <i class="fas fa-info-circle mr-2"></i>
                    Mostrando empresas de ejemplo. Para gestionar empresas reales desde la base de datos, 
                    <a href="demo_empresas.html" class="font-semibold text-blue-600 hover:text-blue-800 underline">
                        haz clic aquí
                    </a>
                </p>
            `;
            table.parentNode.appendChild(demoMessage);
        }
    }

    aplicarAnimacionEntrada() {
        const rows = document.querySelectorAll('.empresa-row');
        rows.forEach((row, index) => {
            setTimeout(() => {
                row.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                row.style.opacity = '1';
                row.style.transform = 'translateY(0)';
            }, index * 100);
        });
    }

    ocultarMensajeVacio() {
        const noCompaniesMessage = document.getElementById('noCompaniesMessage');
        if (noCompaniesMessage) {
            noCompaniesMessage.classList.add('hidden');
        }
    }

    mostrarMensajeVacio() {
        const container = document.getElementById(this.containerId);
        if (!container) return;

        container.innerHTML = '';
        
        const noCompaniesMessage = document.getElementById('noCompaniesMessage');
        if (noCompaniesMessage) {
            noCompaniesMessage.classList.remove('hidden');
        }
    }

    mostrarMensajeError(mensaje) {
        const container = document.getElementById(this.containerId);
        if (!container) return;

        container.innerHTML = `
            <tr>
                <td colspan="4" class="p-4 text-center">
                    <div class="text-red-500">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        ${mensaje}
                    </div>
                    <button onclick="refreshCompaniesTable()" class="mt-2 px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
                        Reintentar
                    </button>
                </td>
            </tr>
        `;
    }

    // Método para refrescar los datos
    async actualizar() {
        await this.cargarEmpresasDestacadas();
    }
}

// Función global para refrescar la tabla de empresas
async function refreshCompaniesTable() {
    if (window.empresasDestacadasIndex) {
        await window.empresasDestacadasIndex.actualizar();
    }
}

// Inicialización cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    console.log('🚀 DOM Content Loaded - Inicializando empresas destacadas...');
    
    // Crear instancia global
    window.empresasDestacadasIndex = new EmpresasDestacadasIndex();
    
    // Verificar que el contenedor existe antes de inicializar
    const checkContainer = () => {
        const container = document.getElementById('companiesTableBody');
        if (container) {
            console.log('✅ Contenedor encontrado, inicializando...');
            window.empresasDestacadasIndex.init();
        } else {
            console.warn('⚠️ Contenedor no encontrado, reintentando en 500ms...');
            setTimeout(checkContainer, 500);
        }
    };
    
    // Inicializar después de un delay para asegurar que el DOM esté completamente cargado
    setTimeout(checkContainer, 100);
});

// También escuchar el evento window.load como respaldo
window.addEventListener('load', () => {
    console.log('🌐 Window Load Event - Verificando estado de empresas...');
    
    if (!window.empresasDestacadasIndex) {
        console.log('🚀 Instancia no existe, creando respaldo...');
        window.empresasDestacadasIndex = new EmpresasDestacadasIndex();
        
        setTimeout(() => {
            window.empresasDestacadasIndex.init();
        }, 1000);
    }
});

// Exportar para uso en otros módulos
if (typeof module !== 'undefined' && module.exports) {
    module.exports = EmpresasDestacadasIndex;
}
