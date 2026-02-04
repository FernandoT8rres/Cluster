/**
 * Gestión de empresas destacadas para empresas-convenio.html
 * Solo muestra empresas marcadas como destacadas con efecto de tarjeta flip
 */

class EmpresasDestacadas {
    constructor() {
        this.empresas = [];
        this.filtros = {
            estado: 'todas',
            busqueda: '',
            ordenPor: 'nombre'
        };
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.cargarEmpresasDestacadas();
    }

    setupEventListeners() {
        // Filtros
        const busquedaInput = document.getElementById('busquedaEmpresa');
        if (busquedaInput) {
            busquedaInput.addEventListener('input', () => {
                this.filtros.busqueda = busquedaInput.value;
                this.renderizarEmpresas();
            });
        }

        const filtroEstado = document.getElementById('filtroEstado');
        if (filtroEstado) {
            filtroEstado.addEventListener('change', () => {
                this.filtros.estado = filtroEstado.value;
                this.renderizarEmpresas();
            });
        }

        const ordenarPor = document.getElementById('ordenarPor');
        if (ordenarPor) {
            ordenarPor.addEventListener('change', () => {
                this.filtros.ordenPor = ordenarPor.value;
                this.renderizarEmpresas();
            });
        }

        // Botón actualizar
        const btnActualizar = document.getElementById('btnActualizarEmpresas');
        if (btnActualizar) {
            btnActualizar.addEventListener('click', () => {
                this.cargarEmpresasDestacadas();
            });
        }
    }

    async cargarEmpresasDestacadas() {
        try {
            this.mostrarCargando(true);
            
            const response = await fetch('./api/empresas_convenio.php?action=destacadas');
            
            if (!response.ok) {
                throw new Error(`Error HTTP: ${response.status}`);
            }
            
            const data = await response.json();
            
            if (data.success) {
                this.empresas = data.data || [];
                this.renderizarEmpresas();
                this.actualizarContador(data.total || 0);
                this.mostrarNotificacion(`${this.empresas.length} empresas destacadas cargadas`, 'success');
            } else {
                throw new Error(data.message || 'Error al cargar empresas');
            }
            
        } catch (error) {
            console.error('Error al cargar empresas destacadas:', error);
            this.mostrarError('Error al cargar empresas destacadas: ' + error.message);
        } finally {
            this.mostrarCargando(false);
        }
    }

    renderizarEmpresas() {
        const container = document.getElementById('empresasContainer');
        if (!container) return;

        // Filtrar empresas
        const empresasFiltradas = this.filtrarEmpresas();

        // Limpiar container
        container.innerHTML = '';

        if (empresasFiltradas.length === 0) {
            this.mostrarEstadoVacio(container);
            return;
        }

        // Renderizar empresas
        empresasFiltradas.forEach(empresa => {
            const card = this.crearTarjetaEmpresa(empresa);
            container.appendChild(card);
        });
    }

    filtrarEmpresas() {
        let empresasFiltradas = [...this.empresas];

        // Filtrar por estado
        if (this.filtros.estado !== 'todas') {
            const activo = this.filtros.estado === 'activa';
            empresasFiltradas = empresasFiltradas.filter(e => Boolean(e.activo) === activo);
        }

        // Filtrar por búsqueda
        if (this.filtros.busqueda) {
            const busqueda = this.filtros.busqueda.toLowerCase();
            empresasFiltradas = empresasFiltradas.filter(e => 
                e.nombre_empresa.toLowerCase().includes(busqueda) ||
                (e.descripcion && e.descripcion.toLowerCase().includes(busqueda)) ||
                (e.email && e.email.toLowerCase().includes(busqueda))
            );
        }

        // Ordenar
        empresasFiltradas.sort((a, b) => {
            switch (this.filtros.ordenPor) {
                case 'nombre':
                    return a.nombre_empresa.localeCompare(b.nombre_empresa);
                case 'fecha':
                    return new Date(b.created_at) - new Date(a.created_at);
                default:
                    return a.nombre_empresa.localeCompare(b.nombre_empresa);
            }
        });

        return empresasFiltradas;
    }

    crearTarjetaEmpresa(empresa) {
        const card = document.createElement('div');
        card.className = 'empresa-card-flip-container relative h-80';
        
        const logoUrl = empresa.logo_url || 
            `https://ui-avatars.com/api/?name=${encodeURIComponent(empresa.nombre_empresa)}&background=0ea5e9&color=fff&size=200`;
        
        const estadoBadge = empresa.activo == 1 
            ? '<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">Activa</span>'
            : '<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">Inactiva</span>';

        const descuento = empresa.descuento > 0 
            ? `<div class="bg-red-100 text-red-800 text-sm font-medium px-2 py-1 rounded-full">
                ${empresa.descuento}% descuento
               </div>`
            : '';

        card.innerHTML = `
            <div class="empresa-card-flip w-full h-full relative preserve-3d transition-transform duration-700 cursor-pointer">
                <!-- Frente de la tarjeta -->
                <div class="card-front absolute inset-0 w-full h-full backface-hidden bg-white rounded-xl shadow-lg overflow-hidden">
                    <div class="relative h-full flex flex-col">
                        <!-- Imagen de la empresa -->
                        <div class="relative h-48 bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center overflow-hidden">
                            <img src="${logoUrl}" 
                                 alt="${empresa.nombre_empresa}" 
                                 class="w-24 h-24 object-contain rounded-lg bg-white p-2"
                                 onerror="this.src='https://ui-avatars.com/api/?name=${encodeURIComponent(empresa.nombre_empresa)}&background=0ea5e9&color=fff&size=96'">
                            
                            <!-- Badge de destacado -->
                            <div class="absolute top-3 right-3">
                                <i class="fas fa-star text-yellow-300 text-xl drop-shadow-lg"></i>
                            </div>
                            
                            <!-- Badge de descuento si existe -->
                            ${descuento ? `<div class="absolute top-3 left-3">${descuento}</div>` : ''}
                        </div>
                        
                        <!-- Información básica -->
                        <div class="p-6 flex-1 flex flex-col justify-between">
                            <div>
                                <h3 class="text-xl font-bold text-gray-900 mb-2 line-clamp-2">
                                    ${empresa.nombre_empresa}
                                </h3>
                                <div class="flex items-center justify-between mb-4">
                                    ${estadoBadge}
                                    <span class="text-sm text-gray-500">
                                        ${empresa.categoria || 'Sin categoría'}
                                    </span>
                                </div>
                            </div>
                            
                            <div class="text-center">
                                <div class="text-sm text-gray-500 mb-2">
                                    <i class="fas fa-mouse-pointer mr-1"></i>
                                    Pasa el mouse para más información
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Reverso de la tarjeta -->
                <div class="card-back absolute inset-0 w-full h-full backface-hidden bg-gradient-to-br from-gray-800 to-gray-900 rounded-xl shadow-lg text-white transform-rotate-y-180">
                    <div class="p-6 h-full flex flex-col justify-between">
                        <div>
                            <h3 class="text-xl font-bold mb-4">${empresa.nombre_empresa}</h3>
                            
                            <div class="space-y-3 mb-6">
                                ${empresa.descripcion ? `
                                    <div class="text-sm">
                                        <i class="fas fa-info-circle mr-2 text-blue-300"></i>
                                        <span class="line-clamp-3">${empresa.descripcion}</span>
                                    </div>
                                ` : ''}
                                
                                ${empresa.email ? `
                                    <div class="text-sm">
                                        <i class="fas fa-envelope mr-2 text-green-300"></i>
                                        <span>${empresa.email}</span>
                                    </div>
                                ` : ''}
                                
                                ${empresa.telefono ? `
                                    <div class="text-sm">
                                        <i class="fas fa-phone mr-2 text-yellow-300"></i>
                                        <span>${empresa.telefono}</span>
                                    </div>
                                ` : ''}
                                
                                ${empresa.direccion ? `
                                    <div class="text-sm">
                                        <i class="fas fa-map-marker-alt mr-2 text-red-300"></i>
                                        <span class="line-clamp-2">${empresa.direccion}</span>
                                    </div>
                                ` : ''}
                            </div>
                            
                            ${empresa.beneficios ? `
                                <div class="text-xs bg-white bg-opacity-10 rounded-lg p-3 mb-4">
                                    <i class="fas fa-gift mr-2 text-purple-300"></i>
                                    <span class="line-clamp-2">${empresa.beneficios}</span>
                                </div>
                            ` : ''}
                        </div>
                        
                        <div class="space-y-3">
                            ${empresa.sitio_web ? `
                                <button onclick="window.open('${empresa.sitio_web}', '_blank')" 
                                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition-colors duration-200 flex items-center justify-center">
                                    <i class="fas fa-external-link-alt mr-2"></i>
                                    Visitar Sitio Web
                                </button>
                            ` : `
                                <div class="w-full bg-gray-600 text-gray-400 font-medium py-2 px-4 rounded-lg text-center">
                                    <i class="fas fa-globe mr-2"></i>
                                    Sin sitio web disponible
                                </div>
                            `}
                            
                            <div class="text-xs text-center text-gray-400">
                                ${empresa.categoria || 'Sin categoría'} • 
                                Desde ${new Date(empresa.created_at).toLocaleDateString('es-ES')}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Agregar evento hover
        card.addEventListener('mouseenter', () => {
            card.querySelector('.empresa-card-flip').style.transform = 'rotateY(180deg)';
        });

        card.addEventListener('mouseleave', () => {
            card.querySelector('.empresa-card-flip').style.transform = 'rotateY(0deg)';
        });

        return card;
    }

    mostrarEstadoVacio(container) {
        container.innerHTML = `
            <div class="col-span-full flex flex-col items-center justify-center py-12 text-gray-500">
                <i class="fas fa-building text-4xl mb-4"></i>
                <h3 class="text-xl font-medium mb-2">No hay empresas destacadas</h3>
                <p class="text-sm">No se encontraron empresas que coincidan con los filtros aplicados.</p>
                <p class="text-xs mt-2 text-gray-400">Marca empresas como destacadas en el panel de administración.</p>
            </div>
        `;
    }

    mostrarCargando(mostrar) {
        const container = document.getElementById('empresasContainer');
        if (!container) return;

        if (mostrar) {
            container.innerHTML = `
                <div class="col-span-full flex items-center justify-center py-12">
                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
                    <span class="ml-3 text-gray-600">Cargando empresas destacadas...</span>
                </div>
            `;
        }
    }

    mostrarError(mensaje) {
        const container = document.getElementById('empresasContainer');
        if (!container) return;

        container.innerHTML = `
            <div class="col-span-full bg-red-50 border border-red-200 rounded-lg p-6 text-center">
                <i class="fas fa-exclamation-triangle text-red-500 text-3xl mb-3"></i>
                <h3 class="text-red-800 font-medium mb-2">Error al cargar empresas</h3>
                <p class="text-red-600 text-sm mb-4">${mensaje}</p>
                <button onclick="window.empresasManager.cargarEmpresasDestacadas()" 
                        class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-colors">
                    <i class="fas fa-retry mr-2"></i>Reintentar
                </button>
            </div>
        `;
    }

    actualizarContador(total) {
        const contador = document.getElementById('contadorEmpresas');
        if (contador) {
            contador.textContent = `${total} empresas destacadas encontradas`;
        }
    }

    mostrarNotificacion(mensaje, tipo = 'info') {
        const container = document.getElementById('notificationContainer');
        if (!container) return;

        const colores = {
            success: 'bg-green-500',
            error: 'bg-red-500',
            warning: 'bg-yellow-500',
            info: 'bg-blue-500'
        };

        const iconos = {
            success: 'check-circle',
            error: 'exclamation-circle',
            warning: 'exclamation-triangle',
            info: 'info-circle'
        };

        const notification = document.createElement('div');
        notification.className = `${colores[tipo]} text-white px-6 py-3 rounded-lg shadow-lg flex items-center space-x-3 animate-fade-in`;
        notification.innerHTML = `
            <i class="fas fa-${iconos[tipo]}"></i>
            <span>${mensaje}</span>
            <button onclick="this.parentElement.remove()" class="ml-auto text-white hover:text-gray-200">
                <i class="fas fa-times"></i>
            </button>
        `;

        container.appendChild(notification);

        // Auto-remover después de 5 segundos
        setTimeout(() => {
            if (notification.parentElement) {
                notification.remove();
            }
        }, 5000);
    }

    exportarDatos() {
        if (this.empresas.length === 0) {
            this.mostrarNotificacion('No hay datos para exportar', 'warning');
            return;
        }

        const datosExport = this.empresas.map(empresa => ({
            'Nombre': empresa.nombre_empresa,
            'Categoría': empresa.categoria || '',
            'Email': empresa.email || '',
            'Teléfono': empresa.telefono || '',
            'Sitio Web': empresa.sitio_web || '',
            'Descuento': empresa.descuento ? `${empresa.descuento}%` : '0%',
            'Estado': empresa.activo ? 'Activa' : 'Inactiva',
            'Fecha Registro': new Date(empresa.created_at).toLocaleDateString('es-ES')
        }));

        this.descargarCSV(datosExport, 'empresas-destacadas.csv');
    }

    descargarCSV(datos, filename) {
        if (datos.length === 0) return;

        const headers = Object.keys(datos[0]);
        const csvContent = [
            headers.join(','),
            ...datos.map(row => 
                headers.map(header => {
                    let cell = row[header];
                    if (typeof cell === 'string' && (cell.includes(',') || cell.includes('"'))) {
                        cell = `"${cell.replace(/"/g, '""')}"`;
                    }
                    return cell;
                }).join(',')
            )
        ].join('\n');

        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = filename;
        link.style.display = 'none';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
}

// CSS personalizado para el efecto flip
const flipStyles = `
<style>
.preserve-3d { transform-style: preserve-3d; }
.backface-hidden { backface-visibility: hidden; }
.transform-rotate-y-180 { transform: rotateY(180deg); }

.empresa-card-flip-container {
    perspective: 1000px;
}

.empresa-card-flip {
    transform-style: preserve-3d;
    transition: transform 0.7s cubic-bezier(0.4, 0.2, 0.2, 1);
}

.card-front,
.card-back {
    backface-visibility: hidden;
}

.card-back {
    transform: rotateY(180deg);
}

.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.line-clamp-3 {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
`;

// Insertar estilos en el head
document.head.insertAdjacentHTML('beforeend', flipStyles);

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    window.empresasManager = new EmpresasDestacadas();
});

// Función auxiliar para verificar autenticación
function isAuthenticated() {
    // Esta función debería implementar la lógica de autenticación real
    return true; // Por ahora, asumir que está autenticado
}