/**
 * Gestión mejorada de empresas en convenio con vista previa modal
 */

class EmpresasConvenioManager {
    constructor() {
        this.empresas = [];
        this.filtros = {
            estado: 'todas',
            busqueda: '',
            ordenPor: 'nombre'
        };
        this.apiUrl = './api/empresas-simple.php';
        this.init();
    }

    init() {
        console.log('🏢 Inicializando gestor de empresas en convenio...');
        this.setupEventListeners();
        this.cargarEmpresas();
        this.crearModalVistaPrevia();
    }

    setupEventListeners() {
        // Botón actualizar
        const btnActualizar = document.getElementById('btnActualizarEmpresas');
        if (btnActualizar) {
            btnActualizar.addEventListener('click', () => this.cargarEmpresas());
        }

        // Filtros de búsqueda
        const busquedaInput = document.getElementById('busquedaEmpresa');
        if (busquedaInput) {
            busquedaInput.addEventListener('input', () => {
                this.filtros.busqueda = busquedaInput.value;
                this.aplicarFiltros();
            });
        }

        // Filtro de estado
        const filtroEstado = document.getElementById('filtroEstado');
        if (filtroEstado) {
            filtroEstado.addEventListener('change', () => {
                this.filtros.estado = filtroEstado.value;
                this.aplicarFiltros();
            });
        }

        // Filtro de ordenamiento
        const ordenarPor = document.getElementById('ordenarPor');
        if (ordenarPor) {
            ordenarPor.addEventListener('change', () => {
                this.filtros.ordenPor = ordenarPor.value;
                this.aplicarFiltros();
            });
        }
    }

    async cargarEmpresas() {
        try {
            console.log('📡 Cargando empresas desde API...');
            const response = await fetch(`${this.apiUrl}?action=listar`);
            const data = await response.json();

            if (data.success) {
                this.empresas = data.data.empresas;
                console.log(`✅ ${this.empresas.length} empresas cargadas`);
                this.renderizarEmpresas();
                this.actualizarContador();
            } else {
                throw new Error(data.message || 'Error al cargar empresas');
            }
        } catch (error) {
            console.error('❌ Error cargando empresas:', error);
            this.mostrarError('Error al cargar empresas: ' + error.message);
        }
    }

    aplicarFiltros() {
        let empresasFiltradas = [...this.empresas];

        // Filtrar por búsqueda
        if (this.filtros.busqueda) {
            const termino = this.filtros.busqueda.toLowerCase();
            empresasFiltradas = empresasFiltradas.filter(empresa => 
                empresa.nombre.toLowerCase().includes(termino) ||
                (empresa.descripcion && empresa.descripcion.toLowerCase().includes(termino)) ||
                (empresa.sector && empresa.sector.toLowerCase().includes(termino))
            );
        }

        // Filtrar por estado
        if (this.filtros.estado !== 'todas') {
            empresasFiltradas = empresasFiltradas.filter(empresa => 
                empresa.estado === this.filtros.estado
            );
        }

        // Ordenar
        empresasFiltradas.sort((a, b) => {
            switch (this.filtros.ordenPor) {
                case 'nombre':
                    return a.nombre.localeCompare(b.nombre);
                case 'sector':
                    return (a.sector || '').localeCompare(b.sector || '');
                case 'fecha':
                    return new Date(b.fecha_registro) - new Date(a.fecha_registro);
                default:
                    return 0;
            }
        });

        this.renderizarEmpresas(empresasFiltradas);
        this.actualizarContador(empresasFiltradas.length);
    }

    renderizarEmpresas(empresasList = null) {
        const container = document.getElementById('empresasContainer');
        if (!container) return;

        const empresas = empresasList || this.empresas;

        if (empresas.length === 0) {
            container.innerHTML = `
                <div class="col-span-full text-center py-12">
                    <i class="fas fa-building text-6xl text-gray-300 mb-4"></i>
                    <h3 class="text-xl font-semibold text-gray-600 mb-2">No hay empresas disponibles</h3>
                    <p class="text-gray-500">No se encontraron empresas que coincidan con los filtros aplicados.</p>
                </div>
            `;
            return;
        }

        const empresasHTML = empresas.map(empresa => this.crearTarjetaEmpresa(empresa)).join('');
        container.innerHTML = empresasHTML;

        // Configurar eventos de click en las imágenes
        container.querySelectorAll('.empresa-logo-clickable').forEach(img => {
            img.addEventListener('click', (e) => {
                e.preventDefault();
                const empresaId = img.closest('.empresa-card').dataset.empresaId;
                this.abrirVistaPrevia(empresaId);
            });
        });
    }

    crearTarjetaEmpresa(empresa) {
        const logoUrl = empresa.logo_url || `https://via.placeholder.com/300x200/6366f1/ffffff?text=${encodeURIComponent(empresa.nombre)}`;
        const descripcion = empresa.descripcion_corta || empresa.descripcion || 'Información de la empresa no disponible.';
        const sector = empresa.sector || 'Sin especificar';
        const descuento = empresa.descuento_porcentaje ? `${empresa.descuento_porcentaje}% descuento` : 'Consultar beneficios';

        return `
            <div class="empresa-card bg-white rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 overflow-hidden" 
                 data-empresa-id="${empresa.id}">
                <!-- Imagen de la empresa (clickeable) -->
                <div class="relative h-48 bg-gradient-to-br from-blue-50 to-indigo-100 cursor-pointer group" 
                     title="Click para ver detalles">
                    <img src="${logoUrl}" 
                         alt="Logo de ${empresa.nombre}"
                         class="empresa-logo-clickable w-full h-full object-contain p-4 transition-transform duration-300 group-hover:scale-105"
                         onerror="this.src='https://via.placeholder.com/300x200/6366f1/ffffff?text=${encodeURIComponent(empresa.nombre)}'">
                    
                    <!-- Overlay con icono de vista previa -->
                    <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-20 transition-all duration-300 flex items-center justify-center">
                        <div class="opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                            <i class="fas fa-search-plus text-white text-2xl"></i>
                        </div>
                    </div>
                    
                    <!-- Badge de descuento -->
                    ${empresa.descuento_porcentaje ? `
                        <div class="absolute top-3 right-3 bg-green-500 text-white px-2 py-1 rounded-full text-sm font-semibold">
                            -${empresa.descuento_porcentaje}%
                        </div>
                    ` : ''}
                </div>

                <!-- Información de la empresa -->
                <div class="p-6">
                    <div class="flex items-start justify-between mb-3">
                        <h3 class="text-lg font-bold text-gray-900 leading-tight">${empresa.nombre}</h3>
                        <span class="px-2 py-1 text-xs rounded-full ${empresa.estado === 'activa' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'}">
                            ${empresa.estado === 'activa' ? 'Activa' : 'Inactiva'}
                        </span>
                    </div>

                    <!-- Sector -->
                    <div class="mb-3">
                        <span class="inline-flex items-center px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded">
                            <i class="fas fa-industry mr-1"></i>
                            ${sector}
                        </span>
                    </div>

                    <!-- Descripción -->
                    <p class="text-gray-600 text-sm mb-4 line-clamp-3">${descripcion}</p>

                    <!-- Información de contacto rápida -->
                    <div class="space-y-2 mb-4">
                        ${empresa.email ? `
                            <div class="flex items-center text-sm text-gray-500">
                                <i class="fas fa-envelope w-4 mr-2"></i>
                                <span class="truncate">${empresa.email}</span>
                            </div>
                        ` : ''}
                        ${empresa.telefono ? `
                            <div class="flex items-center text-sm text-gray-500">
                                <i class="fas fa-phone w-4 mr-2"></i>
                                <span>${empresa.telefono}</span>
                            </div>
                        ` : ''}
                    </div>

                    <!-- Botones de acción -->
                    <div class="flex space-x-2">
                        <button onclick="empresasManager.abrirVistaPrevia('${empresa.id}')" 
                                class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                            <i class="fas fa-eye mr-1"></i>
                            Ver Detalles
                        </button>
                        ${empresa.sitio_web ? `
                            <a href="${empresa.sitio_web}" target="_blank" 
                               class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                                <i class="fas fa-external-link-alt"></i>
                            </a>
                        ` : ''}
                    </div>
                </div>
            </div>
        `;
    }

    crearModalVistaPrevia() {
        // Crear modal si no existe
        if (document.getElementById('empresaModal')) return;

        const modal = document.createElement('div');
        modal.id = 'empresaModal';
        modal.className = 'fixed inset-0 z-50 hidden items-center justify-center bg-black bg-opacity-50';
        modal.innerHTML = `
            <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
                <!-- Header del modal -->
                <div class="flex items-center justify-between p-6 border-b">
                    <h2 id="modalEmpresaNombre" class="text-2xl font-bold text-gray-900"></h2>
                    <button id="btnCerrarModal" class="text-gray-400 hover:text-gray-600 text-2xl">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <!-- Contenido del modal -->
                <div id="modalContenido" class="p-6">
                    <!-- Aquí se cargará el contenido dinámico -->
                </div>
            </div>
        `;

        document.body.appendChild(modal);

        // Eventos del modal
        document.getElementById('btnCerrarModal').addEventListener('click', () => this.cerrarModal());
        modal.addEventListener('click', (e) => {
            if (e.target === modal) this.cerrarModal();
        });
    }

    async abrirVistaPrevia(empresaId) {
        try {
            console.log(`👁️ Abriendo vista previa para empresa ID: ${empresaId}`);
            
            // Buscar empresa en datos locales
            const empresa = this.empresas.find(e => e.id == empresaId);
            if (!empresa) {
                throw new Error('Empresa no encontrada');
            }

            // Actualizar contenido del modal
            document.getElementById('modalEmpresaNombre').textContent = empresa.nombre;
            document.getElementById('modalContenido').innerHTML = this.crearContenidoModal(empresa);

            // Mostrar modal
            const modal = document.getElementById('empresaModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.style.overflow = 'hidden';

        } catch (error) {
            console.error('❌ Error abriendo vista previa:', error);
            this.mostrarError('Error al cargar la vista previa');
        }
    }

    crearContenidoModal(empresa) {
        const logoUrl = empresa.logo_url || `https://via.placeholder.com/400x250/6366f1/ffffff?text=${encodeURIComponent(empresa.nombre)}`;
        
        return `
            <!-- Logo de la empresa -->
            <div class="text-center mb-6">
                <img src="${logoUrl}" 
                     alt="Logo de ${empresa.nombre}"
                     class="mx-auto h-32 w-auto object-contain rounded-lg shadow-md"
                     onerror="this.src='https://via.placeholder.com/400x250/6366f1/ffffff?text=${encodeURIComponent(empresa.nombre)}'">
            </div>

            <!-- Información principal -->
            <div class="grid md:grid-cols-2 gap-6 mb-6">
                <!-- Información básica -->
                <div class="space-y-4">
                    <h3 class="text-lg font-semibold text-gray-900 border-b pb-2">Información General</h3>
                    
                    ${empresa.sector ? `
                        <div class="flex items-center">
                            <i class="fas fa-industry text-blue-600 w-5 mr-3"></i>
                            <div>
                                <span class="text-sm text-gray-500">Sector</span>
                                <p class="font-medium">${empresa.sector}</p>
                            </div>
                        </div>
                    ` : ''}

                    ${empresa.email ? `
                        <div class="flex items-center">
                            <i class="fas fa-envelope text-blue-600 w-5 mr-3"></i>
                            <div>
                                <span class="text-sm text-gray-500">Email</span>
                                <p class="font-medium">${empresa.email}</p>
                            </div>
                        </div>
                    ` : ''}

                    ${empresa.telefono ? `
                        <div class="flex items-center">
                            <i class="fas fa-phone text-blue-600 w-5 mr-3"></i>
                            <div>
                                <span class="text-sm text-gray-500">Teléfono</span>
                                <p class="font-medium">${empresa.telefono}</p>
                            </div>
                        </div>
                    ` : ''}

                    ${empresa.direccion ? `
                        <div class="flex items-start">
                            <i class="fas fa-map-marker-alt text-blue-600 w-5 mr-3 mt-1"></i>
                            <div>
                                <span class="text-sm text-gray-500">Dirección</span>
                                <p class="font-medium">${empresa.direccion}</p>
                            </div>
                        </div>
                    ` : ''}
                </div>

                <!-- Información de contacto -->
                <div class="space-y-4">
                    <h3 class="text-lg font-semibold text-gray-900 border-b pb-2">Contacto</h3>
                    
                    ${empresa.contacto_nombre ? `
                        <div class="flex items-center">
                            <i class="fas fa-user text-green-600 w-5 mr-3"></i>
                            <div>
                                <span class="text-sm text-gray-500">Contacto</span>
                                <p class="font-medium">${empresa.contacto_nombre}</p>
                                ${empresa.contacto_cargo ? `<p class="text-sm text-gray-600">${empresa.contacto_cargo}</p>` : ''}
                            </div>
                        </div>
                    ` : ''}

                    ${empresa.contacto_email ? `
                        <div class="flex items-center">
                            <i class="fas fa-envelope text-green-600 w-5 mr-3"></i>
                            <div>
                                <span class="text-sm text-gray-500">Email de Contacto</span>
                                <p class="font-medium">${empresa.contacto_email}</p>
                            </div>
                        </div>
                    ` : ''}

                    ${empresa.contacto_telefono ? `
                        <div class="flex items-center">
                            <i class="fas fa-phone text-green-600 w-5 mr-3"></i>
                            <div>
                                <span class="text-sm text-gray-500">Teléfono de Contacto</span>
                                <p class="font-medium">${empresa.contacto_telefono}</p>
                            </div>
                        </div>
                    ` : ''}
                </div>
            </div>

            <!-- Descripción -->
            ${empresa.descripcion ? `
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-3">Descripción</h3>
                    <p class="text-gray-700 leading-relaxed">${empresa.descripcion}</p>
                </div>
            ` : ''}

            <!-- Beneficios y descuentos -->
            ${empresa.beneficios || empresa.descuento_porcentaje ? `
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-3">Beneficios</h3>
                    
                    ${empresa.descuento_porcentaje ? `
                        <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-4">
                            <div class="flex items-center">
                                <i class="fas fa-percentage text-green-600 text-xl mr-3"></i>
                                <div>
                                    <h4 class="font-semibold text-green-800">Descuento Especial</h4>
                                    <p class="text-green-700">${empresa.descuento_porcentaje}% de descuento para empleados de Clúster</p>
                                </div>
                            </div>
                        </div>
                    ` : ''}

                    ${empresa.beneficios ? `
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                            <h4 class="font-semibold text-blue-800 mb-2">Beneficios Adicionales</h4>
                            <p class="text-blue-700">${empresa.beneficios}</p>
                        </div>
                    ` : ''}
                </div>
            ` : ''}

            <!-- Condiciones -->
            ${empresa.condiciones ? `
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-3">Términos y Condiciones</h3>
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                        <p class="text-yellow-800 text-sm">${empresa.condiciones}</p>
                    </div>
                </div>
            ` : ''}

            <!-- Botones de acción -->
            <div class="flex space-x-4 pt-4 border-t">
                ${empresa.sitio_web ? `
                    <a href="${empresa.sitio_web}" target="_blank" 
                       class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-medium text-center transition-colors">
                        <i class="fas fa-external-link-alt mr-2"></i>
                        Visitar Sitio Web
                    </a>
                ` : ''}
                
                ${empresa.contacto_email ? `
                    <a href="mailto:${empresa.contacto_email}" 
                       class="flex-1 bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg font-medium text-center transition-colors">
                        <i class="fas fa-envelope mr-2"></i>
                        Contactar
                    </a>
                ` : ''}
            </div>
        `;
    }

    cerrarModal() {
        const modal = document.getElementById('empresaModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.style.overflow = 'auto';
    }

    actualizarContador(cantidad = null) {
        const contador = document.getElementById('contadorEmpresas');
        if (contador) {
            const total = cantidad !== null ? cantidad : this.empresas.length;
            contador.textContent = `${total} empresa${total !== 1 ? 's' : ''} disponible${total !== 1 ? 's' : ''}`;
        }
    }

    mostrarError(mensaje) {
        console.error('❌', mensaje);
        // Aquí podrías agregar una notificación visual si existe el sistema
    }

    exportarDatos() {
        // Funcionalidad para exportar datos de empresas
        const datosExportar = this.empresas.map(empresa => ({
            nombre: empresa.nombre,
            sector: empresa.sector,
            email: empresa.email,
            telefono: empresa.telefono,
            estado: empresa.estado,
            descuento: empresa.descuento_porcentaje,
            fecha_convenio: empresa.fecha_convenio
        }));

        const dataStr = "data:text/json;charset=utf-8," + encodeURIComponent(JSON.stringify(datosExportar, null, 2));
        const downloadAnchorNode = document.createElement('a');
        downloadAnchorNode.setAttribute("href", dataStr);
        downloadAnchorNode.setAttribute("download", "empresas-convenio.json");
        document.body.appendChild(downloadAnchorNode);
        downloadAnchorNode.click();
        downloadAnchorNode.remove();
    }
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    window.empresasManager = new EmpresasConvenioManager();
});