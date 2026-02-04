/**
 * Funciones mejoradas para cargar anuncios en el index.html
 * Este código mejora la carga de anuncios con datos de respaldo
 */

// Datos de respaldo para anuncios importantes
const anunciosRespaldo = [
    {
        id: 1,
        titulo: "🎉 Bienvenidos a la Plataforma Clúster",
        resumen: "Nueva intranet para mejorar la comunicación entre empresas del clúster automotriz",
        contenido: "Nos complace anunciar el lanzamiento oficial de la nueva plataforma intranet Clúster.",
        tipo: "anuncio",
        prioridad: "alta",
        fecha_publicacion: new Date().toISOString()
    },
    {
        id: 2,
        titulo: "📅 Próxima Reunión del Comité de Capital Humano",
        resumen: "Reunión mensual programada para revisar iniciativas de desarrollo de talento",
        contenido: "Se llevará a cabo la reunión mensual del Comité de Capital Humano el próximo viernes.",
        tipo: "boletin",
        prioridad: "media",
        fecha_publicacion: new Date().toISOString()
    },
    {
        id: 3,
        titulo: "💰 Nuevos Descuentos Exclusivos Disponibles",
        resumen: "Beneficios especiales para todas las empresas miembro del clúster",
        contenido: "Hemos agregado una amplia gama de descuentos exclusivos para todos los miembros del clúster automotriz.",
        tipo: "noticia",
        prioridad: "media",
        fecha_publicacion: new Date().toISOString()
    },
    {
        id: 4,
        titulo: "🌱 Conferencia de Sustentabilidad Automotriz 2025",
        resumen: "Evento principal sobre el futuro sostenible de la industria automotriz",
        contenido: "Te invitamos a participar en la Conferencia de Sustentabilidad Automotriz 2025.",
        tipo: "comunicado",
        prioridad: "alta",
        fecha_publicacion: new Date().toISOString()
    },
    {
        id: 5,
        titulo: "🔧 Taller de Digitalización e Industria 4.0",
        resumen: "Capacitación práctica en herramientas digitales para la industria automotriz",
        contenido: "Únete a nuestro taller especializado en digitalización e Industria 4.0.",
        tipo: "boletin",
        prioridad: "media",
        fecha_publicacion: new Date().toISOString()
    }
];

// Función mejorada para cargar anuncios
async function cargarAnuncios() {
    const announcementsList = document.getElementById('announcementsList');
    if (!announcementsList) {
        console.warn('Elemento announcementsList no encontrado');
        return;
    }

    try {
        console.log('Intentando cargar anuncios desde la API...');
        
        // Mostrar skeleton mientras carga
        mostrarSkeletonAnuncios();
        
        // Intentar cargar desde la API
        const response = await api.getBoletines(5);
        let anuncios = [];
        
        // Verificar si la respuesta tiene datos válidos
        if (response && Array.isArray(response) && response.length > 0) {
            anuncios = response;
            console.log('✓ Anuncios cargados desde la API:', anuncios.length);
        } else if (response && response.data && Array.isArray(response.data) && response.data.length > 0) {
            anuncios = response.data;
            console.log('✓ Anuncios cargados desde la API (data):', anuncios.length);
        } else {
            throw new Error('No se encontraron anuncios en la API');
        }
        
        // Renderizar anuncios de la API
        renderizarAnuncios(anuncios, 'api');
        
    } catch (error) {
        console.warn('⚠️ No se pudieron cargar anuncios desde la API:', error.message);
        console.log('📋 Usando datos de respaldo...');
        
        // Usar datos de respaldo
        renderizarAnuncios(anunciosRespaldo, 'respaldo');
        
        // Mostrar notificación informativa (opcional)
        if (typeof mostrarNotificacion === 'function') {
            mostrarNotificacion('Usando datos de ejemplo. Configure la base de datos para datos reales.', 'info', 3000);
        }
    }
}

// Función para mostrar skeleton loading
function mostrarSkeletonAnuncios() {
    const announcementsList = document.getElementById('announcementsList');
    if (!announcementsList) return;
    
    announcementsList.innerHTML = `
        <li class="relative flex justify-between py-2 pr-4 mb-2 border-0 rounded-t-lg rounded-xl text-inherit skeleton-announcement">
            <div class="flex items-center">
                <div class="flex flex-col">
                    <div class="animate-pulse bg-gray-200 rounded h-4 w-32 mb-2"></div>
                    <div class="animate-pulse bg-gray-200 rounded h-3 w-40"></div>
                </div>
            </div>
            <div class="flex">
                <div class="animate-pulse bg-gray-200 rounded-full h-6 w-6"></div>
            </div>
        </li>
        <li class="relative flex justify-between py-2 pr-4 mb-2 border-0 rounded-t-lg rounded-xl text-inherit skeleton-announcement">
            <div class="flex items-center">
                <div class="flex flex-col">
                    <div class="animate-pulse bg-gray-200 rounded h-4 w-28 mb-2"></div>
                    <div class="animate-pulse bg-gray-200 rounded h-3 w-36"></div>
                </div>
            </div>
            <div class="flex">
                <div class="animate-pulse bg-gray-200 rounded-full h-6 w-6"></div>
            </div>
        </li>
        <li class="relative flex justify-between py-2 pr-4 mb-2 border-0 rounded-t-lg rounded-xl text-inherit skeleton-announcement">
            <div class="flex items-center">
                <div class="flex flex-col">
                    <div class="animate-pulse bg-gray-200 rounded h-4 w-30 mb-2"></div>
                    <div class="animate-pulse bg-gray-200 rounded h-3 w-38"></div>
                </div>
            </div>
            <div class="flex">
                <div class="animate-pulse bg-gray-200 rounded-full h-6 w-6"></div>
            </div>
        </li>
    `;
}

// Función para renderizar anuncios
function renderizarAnuncios(anuncios, fuente = 'api') {
    const announcementsList = document.getElementById('announcementsList');
    if (!announcementsList) return;
    
    console.log(`Renderizando ${anuncios.length} anuncios desde ${fuente}`);
    
    // Limpiar skeleton
    announcementsList.innerHTML = '';
    
    if (anuncios && anuncios.length > 0) {
        // Limitar a 5 anuncios máximo
        const anunciosLimitados = anuncios.slice(0, 5);
        
        announcementsList.innerHTML = anunciosLimitados.map(anuncio => {
            // Obtener icono basado en tipo y prioridad
            const icono = obtenerIconoAnuncio(anuncio.tipo, anuncio.prioridad);
            const colorClase = obtenerColorClaseAnuncio(anuncio.tipo, anuncio.prioridad);
            
            // Formatear fecha
            const fecha = formatearFechaAnuncio(anuncio.fecha_publicacion);
            
            // Truncar resumen
            const resumen = anuncio.resumen || anuncio.contenido?.substring(0, 60) + '...' || 'Sin descripción';
            
            return `
                <li class="relative flex justify-between py-3 pr-4 mb-3 border-0 rounded-lg text-inherit bg-gray-50 hover:bg-gray-100 transition-colors duration-200">
                    <div class="flex items-start w-full">
                        <div class="flex-shrink-0 mr-3">
                            <div class="w-8 h-8 rounded-full ${colorClase} flex items-center justify-center">
                                <i class="${icono} text-white text-sm"></i>
                            </div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h6 class="mb-1 text-sm font-semibold leading-normal text-slate-700 dark:text-white truncate">
                                ${anuncio.titulo}
                            </h6>
                            <p class="mb-1 text-xs leading-tight text-slate-500 dark:text-white/80">
                                ${resumen}
                            </p>
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-slate-400">${fecha}</span>
                                <span class="px-2 py-1 text-xs font-medium rounded-full ${obtenerBadgeClase(anuncio.tipo)}">
                                    ${anuncio.tipo || 'anuncio'}
                                </span>
                            </div>
                        </div>
                        <div class="flex-shrink-0 ml-2">
                            <button onclick="verDetalleAnuncio(${anuncio.id})" 
                                class="group ease-in leading-pro text-xs rounded-full p-2 h-8 w-8 inline-block cursor-pointer border-0 bg-transparent text-center align-middle font-bold text-slate-700 hover:bg-slate-200 shadow-none transition-all dark:text-white">
                                <i class="fas fa-chevron-right text-xs group-hover:translate-x-0.5 transition-all duration-200"></i>
                            </button>
                        </div>
                    </div>
                </li>
            `;
        }).join('');
        
        // Agregar indicador de fuente de datos
        if (fuente === 'respaldo') {
            agregarIndicadorRespaldo();
        }
        
    } else {
        // Mostrar mensaje cuando no hay anuncios
        announcementsList.innerHTML = `
            <li class="relative flex flex-col items-center justify-center py-8 text-center">
                <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-gray-100 flex items-center justify-center">
                    <i class="fas fa-bullhorn text-2xl text-gray-400"></i>
                </div>
                <h6 class="mb-2 text-sm font-semibold text-slate-700 dark:text-white">No hay anuncios disponibles</h6>
                <p class="text-xs text-slate-500 dark:text-white/80 max-w-xs">
                    Los anuncios importantes aparecerán aquí cuando estén disponibles.
                </p>
                <button onclick="cargarAnuncios()" class="mt-3 px-4 py-2 text-xs bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors">
                    <i class="fas fa-refresh mr-1"></i>
                    Actualizar
                </button>
            </li>
        `;
    }
}

// Función para obtener icono basado en tipo y prioridad
function obtenerIconoAnuncio(tipo, prioridad) {
    const iconos = {
        'anuncio': 'fas fa-bullhorn',
        'noticia': 'fas fa-newspaper',
        'boletin': 'fas fa-file-alt',
        'comunicado': 'fas fa-envelope-open-text'
    };
    
    // Si es urgente, usar icono de alerta
    if (prioridad === 'urgente' || prioridad === 'alta') {
        return 'fas fa-exclamation-triangle';
    }
    
    return iconos[tipo] || 'fas fa-info-circle';
}

// Función para obtener clase de color basada en tipo y prioridad
function obtenerColorClaseAnuncio(tipo, prioridad) {
    if (prioridad === 'urgente') {
        return 'bg-red-500';
    } else if (prioridad === 'alta') {
        return 'bg-orange-500';
    }
    
    const colores = {
        'anuncio': 'bg-blue-500',
        'noticia': 'bg-green-500',
        'boletin': 'bg-purple-500',
        'comunicado': 'bg-indigo-500'
    };
    
    return colores[tipo] || 'bg-gray-500';
}

// Función para obtener clase de badge
function obtenerBadgeClase(tipo) {
    const clases = {
        'anuncio': 'bg-blue-100 text-blue-800',
        'noticia': 'bg-green-100 text-green-800',
        'boletin': 'bg-purple-100 text-purple-800',
        'comunicado': 'bg-indigo-100 text-indigo-800'
    };
    
    return clases[tipo] || 'bg-gray-100 text-gray-800';
}

// Función para formatear fecha del anuncio
function formatearFechaAnuncio(fecha) {
    if (!fecha) return 'Fecha no disponible';
    
    try {
        const fechaObj = new Date(fecha);
        const ahora = new Date();
        const diffMs = ahora - fechaObj;
        const diffDias = Math.floor(diffMs / (1000 * 60 * 60 * 24));
        
        if (diffDias === 0) {
            return 'Hoy';
        } else if (diffDias === 1) {
            return 'Ayer';
        } else if (diffDias < 7) {
            return `Hace ${diffDias} días`;
        } else {
            return fechaObj.toLocaleDateString('es-ES', { 
                day: 'numeric', 
                month: 'short' 
            });
        }
    } catch (error) {
        return 'Fecha inválida';
    }
}

// Función para agregar indicador de datos de respaldo
function agregarIndicadorRespaldo() {
    const announcementsList = document.getElementById('announcementsList');
    if (!announcementsList) return;
    
    const indicador = document.createElement('li');
    indicador.className = 'mt-4 p-2 bg-amber-50 border border-amber-200 rounded-lg';
    indicador.innerHTML = `
        <div class="flex items-center text-amber-700">
            <i class="fas fa-info-circle mr-2 text-sm"></i>
            <span class="text-xs">
                Mostrando datos de ejemplo. 
                <a href="#" onclick="inicializarBaseDatos()" class="underline hover:no-underline">
                    Inicializar base de datos
                </a> para datos reales.
            </span>
        </div>
    `;
    
    announcementsList.appendChild(indicador);
}

// Función para ver detalle de anuncio
function verDetalleAnuncio(anuncioId) {
    console.log('Ver detalle del anuncio:', anuncioId);
    
    // Buscar el anuncio en los datos cargados
    let anuncio = anunciosRespaldo.find(a => a.id == anuncioId);
    
    if (anuncio) {
        mostrarModalAnuncio(anuncio);
    } else {
        // Intentar cargar desde API
        api.getBoletin(anuncioId)
            .then(response => {
                const anuncioData = response.data || response;
                mostrarModalAnuncio(anuncioData);
            })
            .catch(error => {
                console.error('Error cargando detalle del anuncio:', error);
                if (typeof mostrarNotificacion === 'function') {
                    mostrarNotificacion('No se pudo cargar el detalle del anuncio', 'error');
                }
            });
    }
}

// Función para mostrar modal con detalle del anuncio
function mostrarModalAnuncio(anuncio) {
    // Crear modal si no existe
    let modal = document.getElementById('anuncioModal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'anuncioModal';
        modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden';
        modal.innerHTML = `
            <div class="bg-white rounded-lg max-w-2xl w-full mx-4 max-h-[80vh] overflow-y-auto">
                <div class="p-6">
                    <div class="flex justify-between items-start mb-4">
                        <div class="flex-1">
                            <h3 id="modalTitulo" class="text-xl font-bold text-gray-900 mb-2"></h3>
                            <div id="modalMeta" class="flex items-center space-x-4 text-sm text-gray-500"></div>
                        </div>
                        <button onclick="cerrarModalAnuncio()" class="text-gray-400 hover:text-gray-600">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>
                    <div id="modalContenido" class="prose max-w-none text-gray-700 leading-relaxed"></div>
                    <div class="mt-6 flex justify-end">
                        <button onclick="cerrarModalAnuncio()" class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors">
                            Cerrar
                        </button>
                    </div>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
    }
    
    // Llenar contenido del modal
    document.getElementById('modalTitulo').textContent = anuncio.titulo;
    document.getElementById('modalMeta').innerHTML = `
        <span class="px-2 py-1 bg-gray-100 rounded-full text-xs">${anuncio.tipo || 'anuncio'}</span>
        <span>${formatearFechaAnuncio(anuncio.fecha_publicacion)}</span>
        ${anuncio.prioridad ? `<span class="px-2 py-1 bg-red-100 text-red-800 rounded-full text-xs">${anuncio.prioridad}</span>` : ''}
    `;
    document.getElementById('modalContenido').textContent = anuncio.contenido || anuncio.resumen || 'No hay contenido disponible.';
    
    // Mostrar modal
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

// Función para cerrar modal
function cerrarModalAnuncio() {
    const modal = document.getElementById('anuncioModal');
    if (modal) {
        modal.classList.add('hidden');
        document.body.style.overflow = 'auto';
    }
}

// Función para inicializar base de datos (llamar al script PHP)
async function inicializarBaseDatos() {
    if (confirm('¿Desea inicializar la base de datos con datos de ejemplo? Esto creará usuarios, empresas y anuncios de prueba.')) {
        try {
            const response = await fetch('init_database_with_announcements.php');
            const result = await response.text();
            
            if (response.ok) {
                if (typeof mostrarNotificacion === 'function') {
                    mostrarNotificacion('Base de datos inicializada correctamente', 'success');
                }
                
                // Recargar anuncios después de inicializar
                setTimeout(() => {
                    cargarAnuncios();
                }, 1000);
            } else {
                throw new Error('Error en la respuesta del servidor');
            }
        } catch (error) {
            console.error('Error inicializando base de datos:', error);
            if (typeof mostrarNotificacion === 'function') {
                mostrarNotificacion('Error al inicializar la base de datos', 'error');
            }
        }
    }
}

// Función para refrescar anuncios manualmente
async function refrescarAnuncios() {
    console.log('Refrescando anuncios...');
    await cargarAnuncios();
    
    if (typeof mostrarNotificacion === 'function') {
        mostrarNotificacion('Anuncios actualizados', 'success', 2000);
    }
}

// Exportar funciones al scope global
window.cargarAnuncios = cargarAnuncios;
window.verDetalleAnuncio = verDetalleAnuncio;
window.cerrarModalAnuncio = cerrarModalAnuncio;
window.inicializarBaseDatos = inicializarBaseDatos;
window.refrescarAnuncios = refrescarAnuncios;

// Auto-ejecutar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    // Cargar anuncios si estamos en la página principal
    if (window.location.pathname.includes('index.html') || window.location.pathname === '/' || window.location.pathname.includes('/build/')) {
        setTimeout(cargarAnuncios, 500); // Pequeño delay para asegurar que la API esté ready
    }
});
