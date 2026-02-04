/**
 * Visualizador simple de empresas - Version robusta sin conflictos
 */

(function() {
    'use strict';
    
    console.log('🏢 Iniciando visualizador de empresas...');
    
    class EmpresasSimpleViewer {
        constructor() {
            this.empresas = [];
            this.apiUrl = './api/empresas-simple.php';
            this.modalId = 'modalEmpresaInfo' + Date.now(); // ID único para evitar conflictos
            
            // Esperar a que el DOM esté listo
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', () => this.init());
            } else {
                this.init();
            }
        }

        init() {
            console.log('🏢 Inicializando visualizador...');
            
            // Verificar que tenemos el contenedor necesario
            const container = document.getElementById('empresasContainer');
            if (!container) {
                console.error('❌ No se encontró el contenedor empresasContainer');
                return;
            }
            
            this.setupBasicEvents();
            this.createModal();
            this.loadEmpresas();
        }

        setupBasicEvents() {
            // Solo eventos básicos para evitar conflictos
            const btnActualizar = document.getElementById('btnActualizarEmpresas');
            if (btnActualizar) {
                btnActualizar.onclick = () => this.loadEmpresas();
                console.log('✅ Botón actualizar configurado');
            }

            const busqueda = document.getElementById('busquedaEmpresa');
            if (busqueda) {
                busqueda.oninput = (e) => this.filterEmpresas(e.target.value);
                console.log('✅ Búsqueda configurada');
            }
        }

        async loadEmpresas() {
            console.log('📡 Cargando empresas...');
            const container = document.getElementById('empresasContainer');
            
            // Mostrar loading
            container.innerHTML = `
                <div class="col-span-full text-center py-12">
                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500 mx-auto mb-4"></div>
                    <p class="text-gray-600">Cargando empresas...</p>
                </div>
            `;
            
            try {
                const url = `${this.apiUrl}?action=listar&t=${Date.now()}`; // Anti-cache
                console.log('📡 URL:', url);
                
                const response = await fetch(url, {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json',
                        'Cache-Control': 'no-cache'
                    }
                });

                console.log('📡 Status:', response.status);
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }

                const data = await response.json();
                console.log('📡 Data:', data);

                if (data.success && data.data && data.data.empresas) {
                    this.empresas = data.data.empresas;
                    console.log(`✅ ${this.empresas.length} empresas cargadas`);
                    this.renderEmpresas();
                    this.updateCounter();
                } else {
                    throw new Error(data.message || 'No hay datos de empresas');
                }

            } catch (error) {
                console.error('❌ Error:', error);
                this.showError(container, error.message);
            }
        }

        renderEmpresas(empresasList = null) {
            const container = document.getElementById('empresasContainer');
            if (!container) return;

            const empresas = empresasList || this.empresas;
            console.log(`📊 Renderizando ${empresas.length} empresas`);

            if (empresas.length === 0) {
                container.innerHTML = `
                    <div class="col-span-full text-center py-12">
                        <i class="fas fa-building text-6xl text-gray-300 mb-4"></i>
                        <h3 class="text-xl font-semibold text-gray-600 mb-2">No hay empresas disponibles</h3>
                        <p class="text-gray-500">No se encontraron empresas.</p>
                    </div>
                `;
                return;
            }

            const html = empresas.map(empresa => this.createEmpresaCard(empresa)).join('');
            container.innerHTML = html;

            // Configurar clicks en las imágenes
            this.setupImageClicks();
        }

        createEmpresaCard(empresa) {
            const logoUrl = empresa.logo_url || `https://via.placeholder.com/300x200/6366f1/ffffff?text=${encodeURIComponent(empresa.nombre)}`;
            const descripcion = empresa.descripcion || 'Información disponible al hacer click en la imagen.';
            const sector = empresa.sector || 'Sin especificar';

            return `
                <div class="empresa-card animate-fade-in" style="animation-delay: ${Math.random() * 0.3}s">
                    <div class="empresa-card-content p-6">
                        <!-- Header con estado -->
                        <div class="empresa-header flex justify-between items-center">
                            <div class="flex items-center gap-2">
                                <i class="fas fa-building text-white"></i>
                                <span class="font-semibold">${empresa.estado === 'activa' ? 'Convenio Activo' : empresa.estado}</span>
                            </div>
                            ${empresa.descuento_porcentaje ? `
                                <div class="descuento-badge">
                                    <i class="fas fa-percentage"></i>
                                    ${empresa.descuento_porcentaje}% OFF
                                </div>
                            ` : ''}
                        </div>
                        
                        <!-- Información principal -->
                        <div class="empresa-info-grid mt-4">
                            <!-- Logo clickeable (tamaño reducido) -->
                            <div class="cursor-pointer" onclick="empresasViewer.openModal(${empresa.id})" title="Click para ver información completa">
                                <img src="${logoUrl}" 
                                     alt="Logo de ${empresa.nombre}"
                                     class="empresa-logo"
                                     onerror="this.src='https://via.placeholder.com/120x80/6366f1/ffffff?text=${encodeURIComponent(empresa.nombre)}'">
                            </div>
                            
                            <!-- Información de texto -->
                            <div class="space-y-3">
                                <h3 class="empresa-titulo">${empresa.nombre}</h3>
                                
                                <div class="flex gap-2 flex-wrap">
                                    <span class="sector-badge">
                                        <i class="fas fa-industry mr-1"></i>
                                        ${sector}
                                    </span>
                                    ${empresa.estado === 'activa' ? '<span class="estado-activo">✓ Activo</span>' : ''}
                                </div>
                                
                                <p class="empresa-descripcion">${descripcion}</p>
                            </div>
                        </div>
                        
                        <!-- Botón ver más -->
                        <button onclick="empresasViewer.openModal(${empresa.id})" class="btn-ver-mas">
                            <i class="fas fa-eye"></i>
                            Ver información completa
                        </button>
                    </div>
                </div>
            `;
        }

        setupImageClicks() {
            // Ya configurado con onclick inline para evitar conflictos
            console.log('✅ Clicks configurados');
        }

        createModal() {
            // Crear modal único
            if (document.getElementById(this.modalId)) return;

            const modal = document.createElement('div');
            modal.id = this.modalId;
            modal.className = 'fixed inset-0 z-50 hidden items-center justify-center bg-black bg-opacity-50';
            modal.style.backdropFilter = 'blur(4px)';
            
            modal.innerHTML = `
                <div class="modal-content max-w-3xl w-full mx-4 max-h-[90vh] overflow-y-auto">
                    <div class="bg-gradient-to-r from-slate-800 to-slate-700 text-white p-6 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <i class="fas fa-building text-2xl text-blue-300"></i>
                            <h2 id="${this.modalId}Title" class="text-2xl font-bold"></h2>
                        </div>
                        <button onclick="empresasViewer.closeModal()" class="text-white/70 hover:text-white hover:bg-white/20 rounded-lg p-2 transition-all">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>
                    <div id="${this.modalId}Content" class="p-8"></div>
                </div>
            `;

            document.body.appendChild(modal);
            console.log('✅ Modal creado:', this.modalId);
        }

        openModal(empresaId) {
            console.log('👁️ Abriendo modal para empresa:', empresaId);
            
            const empresa = this.empresas.find(e => e.id == empresaId);
            if (!empresa) {
                console.error('❌ Empresa no encontrada:', empresaId);
                return;
            }

            document.getElementById(this.modalId + 'Title').textContent = empresa.nombre;
            document.getElementById(this.modalId + 'Content').innerHTML = this.createModalContent(empresa);

            const modal = document.getElementById(this.modalId);
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.style.overflow = 'hidden';
        }

        closeModal() {
            const modal = document.getElementById(this.modalId);
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.style.overflow = 'auto';
        }

        createModalContent(empresa) {
            const logoUrl = empresa.logo_url || `https://via.placeholder.com/400x250/6366f1/ffffff?text=${encodeURIComponent(empresa.nombre)}`;
            const sector = empresa.sector || 'Sin especificar';
            
            return `
                <!-- Layout mejorado con grid -->
                <div class="grid md:grid-cols-3 gap-8">
                    <!-- Columna izquierda: Logo e información básica -->
                    <div class="md:col-span-1">
                        <div class="text-center mb-6">
                            <img src="${logoUrl}" 
                                 alt="Logo de ${empresa.nombre}"
                                 class="mx-auto w-40 h-28 object-contain rounded-lg shadow-lg border-2 border-gray-200 bg-white p-2"
                                 onerror="this.src='https://via.placeholder.com/160x112/6366f1/ffffff?text=${encodeURIComponent(empresa.nombre)}'">
                        </div>
                        
                        <!-- Info básica -->
                        <div class="space-y-4">
                            <div class="bg-gradient-to-br from-blue-50 to-indigo-100 rounded-xl p-4">
                                <h4 class="font-semibold text-gray-800 mb-2 flex items-center gap-2">
                                    <i class="fas fa-industry text-blue-600"></i>
                                    Sector
                                </h4>
                                <span class="sector-badge">${sector}</span>
                            </div>
                            
                            <div class="bg-gradient-to-br from-green-50 to-emerald-100 rounded-xl p-4">
                                <h4 class="font-semibold text-gray-800 mb-2 flex items-center gap-2">
                                    <i class="fas fa-check-circle text-green-600"></i>
                                    Estado
                                </h4>
                                ${empresa.estado === 'activa' ? '<span class="estado-activo">✓ Convenio Activo</span>' : `<span class="text-sm px-3 py-1 bg-yellow-200 text-yellow-800 rounded-full">${empresa.estado}</span>`}
                            </div>
                            
                            ${empresa.email ? `
                                <div class="bg-gradient-to-br from-purple-50 to-violet-100 rounded-xl p-4">
                                    <h4 class="font-semibold text-gray-800 mb-2 flex items-center gap-2">
                                        <i class="fas fa-envelope text-purple-600"></i>
                                        Contacto
                                    </h4>
                                    <a href="mailto:${empresa.email}" class="text-purple-700 hover:text-purple-900 text-sm break-all">${empresa.email}</a>
                                </div>
                            ` : ''}
                        </div>
                    </div>
                    
                    <!-- Columna derecha: Descripción y beneficios -->
                    <div class="md:col-span-2">
                        <!-- Descripción -->
                        ${empresa.descripcion ? `
                            <div class="mb-8">
                                <h3 class="text-xl font-bold text-gray-900 mb-4 flex items-center gap-2">
                                    <i class="fas fa-info-circle text-blue-600"></i>
                                    Acerca de la empresa
                                </h3>
                                <div class="bg-gradient-to-br from-gray-50 to-slate-100 rounded-xl p-6">
                                    <p class="text-gray-700 leading-relaxed text-justify">${empresa.descripcion}</p>
                                </div>
                            </div>
                        ` : ''}

                        <!-- Beneficios destacados -->
                        ${(empresa.beneficios || empresa.descuento_porcentaje) ? `
                            <div class="mb-8">
                                <h3 class="text-xl font-bold text-gray-900 mb-4 flex items-center gap-2">
                                    <i class="fas fa-gift text-amber-600"></i>
                                    Beneficios Exclusivos
                                </h3>
                                
                                <div class="space-y-4">
                                    ${empresa.descuento_porcentaje ? `
                                        <div class="bg-gradient-to-r from-amber-400 to-orange-500 text-white rounded-xl p-6 shadow-lg">
                                            <div class="flex items-center justify-center mb-3">
                                                <i class="fas fa-percentage text-3xl mr-3"></i>
                                                <div class="text-center">
                                                    <div class="text-3xl font-bold">${empresa.descuento_porcentaje}%</div>
                                                    <div class="text-sm opacity-90">DESCUENTO</div>
                                                </div>
                                            </div>
                                            <p class="text-center font-semibold">Descuento especial para empleados</p>
                                        </div>
                                    ` : ''}

                                    ${empresa.beneficios ? `
                                        <div class="bg-gradient-to-br from-blue-50 to-cyan-100 border-2 border-blue-200 rounded-xl p-6">
                                            <i class="fas fa-star text-blue-600 text-lg mb-3"></i>
                                            <p class="text-blue-800 leading-relaxed">${empresa.beneficios}</p>
                                        </div>
                                    ` : ''}
                                </div>
                            </div>
                        ` : ''}

                        <!-- Información adicional -->
                        ${(empresa.telefono || empresa.direccion) ? `
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                                ${empresa.telefono ? `
                                    <div class="bg-gradient-to-br from-green-50 to-teal-100 rounded-xl p-4">
                                        <h4 class="font-semibold text-gray-800 mb-2 flex items-center gap-2">
                                            <i class="fas fa-phone text-green-600"></i>
                                            Teléfono
                                        </h4>
                                        <a href="tel:${empresa.telefono}" class="text-green-700 hover:text-green-900 font-medium">${empresa.telefono}</a>
                                    </div>
                                ` : ''}
                                
                                ${empresa.direccion ? `
                                    <div class="bg-gradient-to-br from-red-50 to-pink-100 rounded-xl p-4">
                                        <h4 class="font-semibold text-gray-800 mb-2 flex items-center gap-2">
                                            <i class="fas fa-map-marker-alt text-red-600"></i>
                                            Ubicación
                                        </h4>
                                        <p class="text-red-700 text-sm">${empresa.direccion}</p>
                                    </div>
                                ` : ''}
                            </div>
                        ` : ''}
                    </div>
                </div>

                <!-- Botón sitio web destacado -->
                ${empresa.sitio_web ? `
                    <div class="mt-8 pt-6 border-t-2 border-gray-200 text-center">
                        <a href="${empresa.sitio_web}" target="_blank" rel="noopener noreferrer"
                           class="inline-flex items-center bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white px-8 py-4 rounded-xl font-bold text-lg shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-300">
                            <i class="fas fa-external-link-alt mr-3 text-xl"></i>
                            Visitar Sitio Web Oficial
                            <i class="fas fa-arrow-right ml-3"></i>
                        </a>
                    </div>
                ` : ''}
            `;
        }

        filterEmpresas(termino) {
            if (!termino.trim()) {
                this.renderEmpresas();
                this.updateCounter();
                return;
            }

            const filtered = this.empresas.filter(empresa => 
                empresa.nombre.toLowerCase().includes(termino.toLowerCase()) ||
                (empresa.descripcion && empresa.descripcion.toLowerCase().includes(termino.toLowerCase())) ||
                (empresa.sector && empresa.sector.toLowerCase().includes(termino.toLowerCase()))
            );

            this.renderEmpresas(filtered);
            this.updateCounter(filtered.length);
        }

        updateCounter(cantidad = null) {
            const contador = document.getElementById('contadorEmpresas');
            if (contador) {
                const total = cantidad !== null ? cantidad : this.empresas.length;
                contador.innerHTML = `
                    <i class="fas fa-building mr-2"></i>
                    ${total} empresa${total !== 1 ? 's' : ''} disponible${total !== 1 ? 's' : ''}
                `;
            }
        }

        showError(container, message) {
            container.innerHTML = `
                <div class="col-span-full text-center py-12">
                    <i class="fas fa-exclamation-triangle text-4xl text-red-400 mb-4"></i>
                    <h3 class="text-lg font-semibold text-gray-700 mb-2">Error al cargar</h3>
                    <p class="text-gray-500 mb-4">${message}</p>
                    <button onclick="empresasViewer.loadEmpresas()" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                        Reintentar
                    </button>
                </div>
            `;
        }
    }

    // Inicializar globalmente sin conflictos
    window.empresasViewer = new EmpresasSimpleViewer();
    
})();