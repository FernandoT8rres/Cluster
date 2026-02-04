/**
 * Componente para mostrar empresas destacadas en la página principal
 * Carga las empresas marcadas como destacadas desde la base de datos
 */

class EmpresasDestacadasWidget {
    constructor(containerId = 'empresasDestacadasContainer') {
        this.containerId = containerId;
        this.empresasDestacadas = [];
        this.isLoading = false;
        this.API_URL = 'api/empresas_convenio.php';
    }

    async init() {
        const container = document.getElementById(this.containerId);
        if (!container) {
            console.warn(`Container ${this.containerId} no encontrado`);
            return;
        }
        
        await this.cargarEmpresasDestacadas();
    }

    async cargarEmpresasDestacadas() {
        const container = document.getElementById(this.containerId);
        if (!container) return;

        // Mostrar estado de carga
        this.mostrarCargando();

        try {
            // Hacer petición a la API para obtener solo empresas destacadas y activas
            const response = await fetch(`${this.API_URL}?destacado=1&activo=1&limit=8`);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const result = await response.json();
            
            if (result.success && result.data) {
                this.empresasDestacadas = result.data;
                this.renderizarEmpresas();
            } else {
                // Si no hay datos, mostrar datos de ejemplo
                this.mostrarEmpresasEjemplo();
            }
        } catch (error) {
            console.error('Error cargando empresas destacadas:', error);
            // En caso de error, mostrar empresas de ejemplo
            this.mostrarEmpresasEjemplo();
        }
    }

    mostrarCargando() {
        const container = document.getElementById(this.containerId);
        if (!container) return;

        container.innerHTML = `
            <div class="col-span-full flex justify-center items-center py-12">
                <div class="text-center">
                    <i class="fas fa-spinner fa-spin text-4xl text-blue-500 mb-4"></i>
                    <p class="text-gray-500">Cargando empresas en convenio...</p>
                </div>
            </div>
        `;
    }

    renderizarEmpresas() {
        const container = document.getElementById(this.containerId);
        if (!container) return;

        if (this.empresasDestacadas.length === 0) {
            this.mostrarEstadoVacio();
            return;
        }

        // Crear el HTML para las tarjetas de empresas
        const empresasHTML = this.empresasDestacadas.map(empresa => 
            this.crearTarjetaEmpresa(empresa)
        ).join('');

        container.innerHTML = empresasHTML;

        // Agregar animaciones de entrada
        this.aplicarAnimaciones();
    }

    crearTarjetaEmpresa(empresa) {
        // Generar URL del logo o usar avatar por defecto
        const logoUrl = empresa.logo_url || 
            `https://ui-avatars.com/api/?name=${encodeURIComponent(empresa.nombre_empresa)}&background=random&size=128`;
        
        // Formatear descuento
        const descuentoHTML = empresa.descuento > 0 ? 
            `<span class="inline-block px-3 py-1 bg-green-100 text-green-800 text-sm font-semibold rounded-full">
                ${empresa.descuento}% Descuento
            </span>` : '';

        // Formatear beneficios
        const beneficiosHTML = empresa.beneficios ? 
            `<p class="text-gray-600 text-sm mt-2">${empresa.beneficios}</p>` : '';

        // Verificar vigencia del convenio
        const hoy = new Date();
        const fechaFin = empresa.fecha_fin_convenio ? new Date(empresa.fecha_fin_convenio) : null;
        const esVigente = !fechaFin || fechaFin >= hoy;
        
        const vigenciaHTML = esVigente ? 
            '<span class="text-xs text-green-600"><i class="fas fa-check-circle mr-1"></i>Convenio vigente</span>' :
            '<span class="text-xs text-red-600"><i class="fas fa-exclamation-circle mr-1"></i>Convenio vencido</span>';

        return `
            <div class="empresa-card transform transition-all duration-300 hover:scale-105">
                <div class="bg-white rounded-lg shadow-lg overflow-hidden h-full">
                    <div class="relative">
                        <!-- Logo/Imagen de la empresa -->
                        <div class="h-32 bg-gradient-to-br from-blue-400 to-purple-600 flex items-center justify-center">
                            <img src="${logoUrl}" 
                                 alt="${empresa.nombre_empresa}" 
                                 class="w-20 h-20 rounded-full bg-white p-2 shadow-lg"
                                 onerror="this.src='https://ui-avatars.com/api/?name=${encodeURIComponent(empresa.nombre_empresa)}&background=random&size=128'">
                        </div>
                        <!-- Badge destacado -->
                        <span class="absolute top-2 right-2 bg-yellow-400 text-yellow-900 text-xs font-bold px-2 py-1 rounded-full">
                            <i class="fas fa-star mr-1"></i>Destacado
                        </span>
                    </div>
                    
                    <div class="p-4">
                        <h3 class="font-bold text-lg text-gray-800 mb-2 truncate" title="${empresa.nombre_empresa}">
                            ${empresa.nombre_empresa}
                        </h3>
                        
                        ${empresa.categoria ? 
                            `<span class="inline-block px-2 py-1 bg-blue-100 text-blue-800 text-xs font-semibold rounded mb-2">
                                ${empresa.categoria}
                            </span>` : ''}
                        
                        ${descuentoHTML}
                        
                        <p class="text-gray-600 text-sm mt-2 line-clamp-2" title="${empresa.descripcion || ''}">
                            ${empresa.descripcion || 'Empresa colaboradora con beneficios exclusivos para nuestros socios.'}
                        </p>
                        
                        ${beneficiosHTML}
                        
                        <div class="mt-4 pt-3 border-t border-gray-200">
                            <div class="flex justify-between items-center">
                                ${vigenciaHTML}
                                ${empresa.sitio_web ? 
                                    `<a href="${empresa.sitio_web}" 
                                        target="_blank" 
                                        class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                        Visitar <i class="fas fa-external-link-alt ml-1"></i>
                                    </a>` : 
                                    ''}
                            </div>
                        </div>
                        
                        ${empresa.email || empresa.telefono ? `
                            <div class="mt-3 pt-3 border-t border-gray-200 space-y-1">
                                ${empresa.email ? 
                                    `<p class="text-xs text-gray-500">
                                        <i class="fas fa-envelope mr-1"></i>${empresa.email}
                                    </p>` : ''}
                                ${empresa.telefono ? 
                                    `<p class="text-xs text-gray-500">
                                        <i class="fas fa-phone mr-1"></i>${empresa.telefono}
                                    </p>` : ''}
                            </div>
                        ` : ''}
                    </div>
                </div>
            </div>
        `;
    }

    mostrarEmpresasEjemplo() {
        // Datos de ejemplo cuando no hay conexión o no hay empresas
        const empresasEjemplo = [
            {
                id: 1,
                nombre_empresa: 'Tech Solutions',
                categoria: 'Tecnología',
                descripcion: 'Soluciones tecnológicas innovadoras',
                descuento: 15,
                beneficios: '15% en todos los servicios',
                sitio_web: 'https://example.com',
                activo: 1,
                destacado: 1
            },
            {
                id: 2,
                nombre_empresa: 'Salud Integral',
                categoria: 'Salud',
                descripcion: 'Centro médico con especialistas',
                descuento: 20,
                beneficios: '20% en consultas médicas',
                email: 'info@saludintegral.com',
                activo: 1,
                destacado: 1
            },
            {
                id: 3,
                nombre_empresa: 'Educación Global',
                categoria: 'Educación',
                descripcion: 'Cursos y capacitaciones profesionales',
                descuento: 25,
                beneficios: '25% en cursos online',
                telefono: '+56 2 3456 7890',
                activo: 1,
                destacado: 1
            },
            {
                id: 4,
                nombre_empresa: 'Deportes Pro',
                categoria: 'Deportes',
                descripcion: 'Equipamiento deportivo de calidad',
                descuento: 10,
                beneficios: '10% en toda la tienda',
                sitio_web: 'https://deportespro.cl',
                activo: 1,
                destacado: 1
            }
        ];

        this.empresasDestacadas = empresasEjemplo;
        this.renderizarEmpresas();
        
        // Mostrar mensaje de modo demo
        const container = document.getElementById(this.containerId);
        if (container) {
            container.insertAdjacentHTML('afterend', `
                <div class="col-span-full text-center mt-4">
                    <p class="text-sm text-gray-500">
                        <i class="fas fa-info-circle mr-1"></i>
                        Mostrando empresas de ejemplo. 
                        <a href="demo_empresas.html" class="text-blue-600 hover:underline">
                            Gestionar empresas
                        </a>
                    </p>
                </div>
            `);
        }
    }

    mostrarEstadoVacio() {
        const container = document.getElementById(this.containerId);
        if (!container) return;

        container.innerHTML = `
            <div class="col-span-full">
                <div class="bg-white rounded-lg shadow-lg p-8 text-center">
                    <i class="fas fa-building text-6xl text-gray-300 mb-4"></i>
                    <h3 class="text-xl font-semibold text-gray-700 mb-2">
                        No hay empresas destacadas
                    </h3>
                    <p class="text-gray-500 mb-4">
                        Aún no se han marcado empresas como destacadas en el sistema.
                    </p>
                    <a href="demo_empresas.html" 
                       class="inline-block px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        <i class="fas fa-plus mr-2"></i>Gestionar Empresas
                    </a>
                </div>
            </div>
        `;
    }

    aplicarAnimaciones() {
        const cards = document.querySelectorAll('.empresa-card');
        cards.forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, index * 100);
        });
    }

    // Método para actualizar las empresas (útil para refrescar sin recargar la página)
    async actualizar() {
        await this.cargarEmpresasDestacadas();
    }
}

// CSS adicional para el componente
const estilosEmpresasDestacadas = `
    <style>
        .line-clamp-2 {
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }
        
        .empresa-card {
            animation: fadeInUp 0.6s ease-out;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .empresa-card:hover {
            z-index: 10;
        }
    </style>
`;

// Insertar estilos si no existen
if (!document.getElementById('empresas-destacadas-styles')) {
    const styleElement = document.createElement('div');
    styleElement.id = 'empresas-destacadas-styles';
    styleElement.innerHTML = estilosEmpresasDestacadas;
    document.head.appendChild(styleElement.firstElementChild);
}

// Exportar para uso global
window.EmpresasDestacadasWidget = EmpresasDestacadasWidget;
