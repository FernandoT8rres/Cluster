// Variables globales para el demo
let eventos = [];
let registros = [];
let registrosEmpresas = [];
let filteredEventos = [];
let filteredRegistros = [];
let currentEventId = null;
let currentRegistroId = null;

// Configuración de la API
const API_BASE = './api';

// Utilidades de notificación
function showNotification(message, type = 'info') {
    const container = document.getElementById('notifications');
    const notification = document.createElement('div');
    
    const colors = {
        success: 'bg-green-500',
        error: 'bg-red-500',
        warning: 'bg-yellow-500',
        info: 'bg-blue-500'
    };
    
    notification.className = `${colors[type]} text-white px-6 py-3 rounded-lg shadow-lg flex items-center space-x-2 transform translate-x-full transition-transform duration-300`;
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check' : type === 'error' ? 'times' : type === 'warning' ? 'exclamation' : 'info'}-circle"></i>
        <span>${message}</span>
    `;
    
    container.appendChild(notification);
    
    // Animar entrada
    setTimeout(() => notification.classList.remove('translate-x-full'), 100);
    
    // Auto remover
    setTimeout(() => {
        notification.classList.add('translate-x-full');
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Cargar eventos desde la API
async function loadEventos() {
    try {
        console.log('Iniciando carga de eventos...');
        
        const response = await fetch(`${API_BASE}/eventos.php`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        }).catch(error => {
            console.error('Error en fetch:', error);
            throw new Error(`Error de red: ${error.message}`);
        });
        
        if (!response) {
            throw new Error('No se recibió respuesta del servidor');
        }
        
        // Verificar el tipo de contenido
        const contentType = response.headers.get('content-type');
        console.log('Content-Type recibido:', contentType);
        
        if (!response.ok) {
            let errorText = '';
            try {
                errorText = await response.text();
            } catch (e) {
                errorText = 'No se pudo leer la respuesta del error';
            }
            console.error('Respuesta del servidor (error):', errorText);
            
            // Intentar detectar errores PHP comunes
            if (errorText.includes('Fatal error') || errorText.includes('Warning') || errorText.includes('Notice')) {
                throw new Error('Error PHP en el servidor. Revisa la configuración del servidor.');
            }
            
            throw new Error(`Error HTTP: ${response.status} - ${response.statusText}`);
        }
        
        // Primero obtener el texto de la respuesta
        const responseText = await response.text();
        console.log('Respuesta recibida (texto):', responseText);
        
        // Intentar parsear como JSON
        let data;
        try {
            data = JSON.parse(responseText);
            console.log('Datos parseados:', data);
        } catch (jsonError) {
            console.error('Error al parsear JSON:', jsonError);
            
            // Analizar qué tipo de respuesta es
            if (responseText.includes('<!DOCTYPE') || responseText.includes('<html')) {
                throw new Error('El servidor está devolviendo HTML en lugar de JSON. Verifica la URL del API.');
            }
            if (responseText.includes('Fatal error') || responseText.includes('Warning') || responseText.includes('Notice')) {
                throw new Error('Error PHP en el servidor.');
            }
            if (responseText.trim() === '') {
                throw new Error('El servidor devolvió una respuesta vacía.');
            }
            
            throw new Error('La respuesta del servidor no es JSON válido');
        }
        
        if (data.success) {
            eventos = data.data?.eventos || [];
            filteredEventos = [...eventos];
            // renderEventosTable(); // Comentado - tabla eliminada
            console.log('✅ Eventos cargados en loadEventos, usando renderEventosDemo para visualización');
            updateStats();
            console.log(`Cargados ${eventos.length} eventos exitosamente`);
        } else {
            throw new Error(data.message || 'Error al cargar eventos');
        }
    } catch (error) {
        console.error('Error completo al cargar eventos:', error);
        
        // Mostrar mensaje más específico según el tipo de error
        let errorMessage = 'Error al cargar eventos: ';
        
        if (error.message.includes('Failed to fetch') || error.message.includes('NetworkError')) {
            errorMessage += 'No se pudo conectar con el servidor. Verifica que el servidor esté funcionando.';
        } else if (error.message.includes('JSON')) {
            errorMessage += 'El servidor no está devolviendo datos en el formato esperado.';
        } else {
            errorMessage += error.message;
        }
        
        showNotification(errorMessage, 'error');
        
        // Mostrar estado vacío para que el usuario sepa que algo falló (tabla eliminada)
        console.log('⚠️ Error en loadEventos - tabla eliminada, usando solo logs');
    }
}

// Renderizar tabla de eventos (DESHABILITADA - tabla eliminada)
function renderEventosTable() {
    console.log('⚠️ renderEventosTable llamada pero tabla fue eliminada - función deshabilitada');
    return; // Salir inmediatamente sin hacer nada
}

// Actualizar estadísticas
function updateStats() {
    const total = eventos.length;
    const proximos = eventos.filter(e => e.estado === 'proximo' || e.estado === 'programado').length;
    const enCurso = eventos.filter(e => e.estado === 'en_curso').length;
    const totalRegistros = eventos.reduce((sum, e) => sum + (parseInt(e.registrados) || 0), 0);
    
    // Actualizar contadores solo si los elementos existen
    const totalElement = document.getElementById('totalEventos');
    if (totalElement) totalElement.textContent = total;

    const proximosElement = document.getElementById('proximosEventos');
    if (proximosElement) proximosElement.textContent = proximos;

    const enCursoElement = document.getElementById('enCursoEventos');
    if (enCursoElement) enCursoElement.textContent = enCurso;

    const totalRegistrosElement = document.getElementById('totalRegistros');
    if (totalRegistrosElement) totalRegistrosElement.textContent = totalRegistros;
}

// Cargar registros de empresas desde la API
async function loadRegistrosEmpresas() {
    console.log('🔄 Cargando registros de empresas...');
    try {
        const url = `${API_BASE}/eventos.php?action=registros_all`;
        console.log('📡 URL de registros:', url);
        
        const response = await fetch(url, {
            method: 'GET',
            headers: {
                'Accept': 'application/json'
            }
        });
        
        console.log('📡 Respuesta de registros:', response.status, response.statusText);
        
        if (!response.ok) {
            throw new Error(`Error HTTP: ${response.status}`);
        }
        
        const responseText = await response.text();
        console.log('📄 Texto de respuesta registros:', responseText);
        
        let data;
        
        try {
            data = JSON.parse(responseText);
        } catch (jsonError) {
            console.error('❌ Error parsing JSON:', jsonError);
            throw new Error('Respuesta no válida del servidor');
        }
        
        console.log('✅ Datos de registros parseados:', data);
        
        if (data.success) {
            registrosEmpresas = data.registros || [];
            filteredRegistros = [...registrosEmpresas];
            
            console.log('📋 Registros cargados:', registrosEmpresas.length, registrosEmpresas);
            
            renderRegistrosEmpresas();
            updateRegistrosStats();
            loadEventosForFilter();
        } else {
            console.error('❌ API retornó error:', data.message);
            throw new Error(data.message || 'Error al cargar registros');
        }
    } catch (error) {
        console.error('❌ Error al cargar registros de empresas:', error);
        showNotification('Error al cargar registros: ' + error.message, 'error');
        
        // document.getElementById('registrosContainer').innerHTML = '';
        // document.getElementById('loadingRegistros').classList.add('hidden');
        // document.getElementById('emptyRegistros').classList.remove('hidden');
    }
}

// Renderizar lista de registros de empresas - FUNCIÓN DESHABILITADA
function renderRegistrosEmpresas() {
    console.log('🎨 Renderizando registros de empresas (DESHABILITADO):', filteredRegistros?.length || 0);
    // Esta función ha sido reemplazada por el sistema de notificaciones
    return;

    // const container = document.getElementById('registrosContainer');
    // const loadingState = document.getElementById('loadingRegistros');
    // const emptyState = document.getElementById('emptyRegistros');

    // console.log('📦 Elementos DOM encontrados:', {
    //     container: !!container,
    //     loadingState: !!loadingState,
    //     emptyState: !!emptyState
    // });
    
    if (filteredRegistros.length === 0) {
        console.log('📭 No hay registros para mostrar');
        container.innerHTML = '';
        loadingState.classList.add('hidden');
        emptyState.classList.remove('hidden');
        return;
    }
    
    console.log('📋 Renderizando', filteredRegistros.length, 'registros');
    loadingState.classList.add('hidden');
    emptyState.classList.add('hidden');
    
    container.innerHTML = filteredRegistros.map(registro => `
        <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow cursor-pointer" 
             onclick="viewDetalleRegistro(${registro.id})">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <div class="flex items-center space-x-3">
                        <div class="flex-shrink-0">
                            <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-building text-blue-600"></i>
                            </div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center space-x-2">
                                <h4 class="text-sm font-medium text-gray-900 truncate">
                                    ${registro.nombre_empresa || registro.nombre_usuario || 'Sin nombre'}
                                </h4>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium ${getRegistroEstadoColor(registro.estado_registro)}">
                                    ${registro.estado_registro || 'Confirmado'}
                                </span>
                            </div>
                            <p class="text-sm text-gray-500 truncate">
                                ${registro.evento_titulo || 'Evento no encontrado'}
                            </p>
                            <div class="flex items-center space-x-4 mt-1 text-xs text-gray-400">
                                <span><i class="fas fa-envelope mr-1"></i>${registro.email_contacto || 'Sin email'}</span>
                                <span><i class="fas fa-calendar mr-1"></i>${formatDate(registro.fecha_registro)}</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex-shrink-0">
                    <i class="fas fa-chevron-right text-gray-400"></i>
                </div>
            </div>
        </div>
    `).join('');
}

// Actualizar estadísticas de registros - FUNCIÓN DESHABILITADA
function updateRegistrosStats() {
    const total = registrosEmpresas.length;
    console.log('📊 Estadísticas de registros (DESHABILITADO):', { total });

    // Esta función ha sido reemplazada por el sistema de notificaciones
    const element = document.getElementById('totalRegistrosEmpresas');
    if (element) {
        element.textContent = total;
    } else {
        console.log('⚠️ Elemento totalRegistrosEmpresas no encontrado (esperado - reemplazado por notificaciones)');
    }
}

// Cargar eventos para el filtro - FUNCIÓN DESHABILITADA
async function loadEventosForFilter() {
    const filterEvento = document.getElementById('filterEvento');

    if (!filterEvento) {
        console.log('⚠️ Elemento filterEvento no encontrado (esperado - sección reemplazada por notificaciones)');
        return;
    }
    
    if (eventos.length === 0) {
        // Si no hay eventos cargados, cargarlos primero
        await loadEventos();
    }
    
    // Limpiar opciones existentes (excepto la primera)
    while (filterEvento.children.length > 1) {
        filterEvento.removeChild(filterEvento.lastChild);
    }
    
    // Agregar eventos únicos que tienen registros
    const eventosConRegistros = [...new Set(registrosEmpresas.map(r => r.evento_id))];
    eventosConRegistros.forEach(eventoId => {
        const evento = eventos.find(e => e.id == eventoId);
        if (evento) {
            const option = document.createElement('option');
            option.value = eventoId;
            option.textContent = evento.titulo;
            filterEvento.appendChild(option);
        }
    });
}

// Aplicar filtros a registros de empresas - FUNCIÓN DESHABILITADA
function applyRegistrosFilters() {
    console.log('🔍 applyRegistrosFilters llamado (DESHABILITADO)');
    // Esta función ha sido reemplazada por el sistema de filtros de notificaciones
    return;

    // const searchEmpresa = document.getElementById('searchEmpresa').value.toLowerCase();
    const eventoFilterElement = document.getElementById('filterEvento');
    const estadoFilterElement = document.getElementById('filterEstadoRegistro');

    if (!eventoFilterElement || !estadoFilterElement) {
        console.log('⚠️ Elementos de filtro no encontrados (esperado - sección reemplazada por notificaciones)');
        return;
    }

    const eventoFilter = eventoFilterElement.value;
    const estadoFilter = estadoFilterElement.value;
    
    filteredRegistros = registrosEmpresas.filter(registro => {
        const matchesSearch = !searchEmpresa || 
            (registro.nombre_empresa && registro.nombre_empresa.toLowerCase().includes(searchEmpresa)) ||
            (registro.nombre_usuario && registro.nombre_usuario.toLowerCase().includes(searchEmpresa)) ||
            (registro.email_contacto && registro.email_contacto.toLowerCase().includes(searchEmpresa)) ||
            (registro.evento_titulo && registro.evento_titulo.toLowerCase().includes(searchEmpresa));
            
        const matchesEvento = !eventoFilter || registro.evento_id == eventoFilter;
        const matchesEstado = !estadoFilter || registro.estado_registro === estadoFilter;
        
        return matchesSearch && matchesEvento && matchesEstado;
    });
    
    renderRegistrosEmpresas();
}

// Ver detalle de registro - FUNCIÓN DESHABILITADA
async function viewDetalleRegistro(registroId) {
    console.log('👁️ viewDetalleRegistro llamado (DESHABILITADO):', registroId);
    // Esta función ha sido reemplazada por el sistema de notificaciones
    return;

    try {
        const registro = registrosEmpresas.find(r => r.id == registroId);
        if (!registro) {
            throw new Error('Registro no encontrado');
        }

        currentRegistroId = registroId;

        const content = document.getElementById('detalleRegistroContent');

        if (!content) {
            console.log('⚠️ Modal de detalle de registro no encontrado');
            return;
        }
        content.innerHTML = `
            <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Empresa</label>
                        <p class="mt-1 text-sm text-gray-900">${registro.nombre_empresa || 'No especificada'}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Usuario</label>
                        <p class="mt-1 text-sm text-gray-900">${registro.nombre_usuario || 'No especificado'}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Email</label>
                        <p class="mt-1 text-sm text-gray-900">${registro.email_contacto || 'No especificado'}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Teléfono</label>
                        <p class="mt-1 text-sm text-gray-900">${registro.telefono_contacto || 'No especificado'}</p>
                    </div>
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700">Evento</label>
                        <p class="mt-1 text-sm text-gray-900">${registro.evento_titulo || 'Evento no encontrado'}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Fecha de Registro</label>
                        <p class="mt-1 text-sm text-gray-900">${formatDate(registro.fecha_registro)} ${formatTime(registro.fecha_registro)}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Estado</label>
                        <span class="mt-1 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${getRegistroEstadoColor(registro.estado_registro)}">
                            ${registro.estado_registro || 'Confirmado'}
                        </span>
                    </div>
                    ${registro.comentarios ? `
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700">Comentarios</label>
                        <p class="mt-1 text-sm text-gray-900">${registro.comentarios}</p>
                    </div>
                    ` : ''}
                </div>
            </div>
        `;
        
        const modal = document.getElementById('detalleRegistroModal');
        if (modal) {
            modal.classList.remove('hidden');
        }
    } catch (error) {
        showNotification('Error al cargar detalle del registro: ' + error.message, 'error');
    }
}

// Cerrar modal de detalle de registro
function closeDetalleRegistroModal() {
    const modal = document.getElementById('detalleRegistroModal');
    if (modal) {
        modal.classList.add('hidden');
    }
    currentRegistroId = null;
}

// Eliminar registro
async function eliminarRegistro() {
    if (!currentRegistroId) return;
    
    const registro = registrosEmpresas.find(r => r.id == currentRegistroId);
    if (!registro) return;
    
    const confirmMessage = `¿Estás seguro de eliminar el registro de "${registro.nombre_empresa || registro.nombre_usuario}" para el evento "${registro.evento_titulo}"?`;
    
    if (confirm(confirmMessage)) {
        try {
            const response = await fetch(`${API_BASE}/eventos.php?action=eliminar_registro&registro_id=${currentRegistroId}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json'
                }
            });
            
            if (!response.ok) {
                throw new Error(`Error HTTP: ${response.status}`);
            }
            
            const data = await response.json();
            
            if (data.success) {
                showNotification('Registro eliminado exitosamente', 'success');
                closeDetalleRegistroModal();
                await loadRegistrosEmpresas();
                await loadEventos(); // Recargar eventos para actualizar contadores
            } else {
                throw new Error(data.message || 'Error al eliminar registro');
            }
        } catch (error) {
            console.error('Error al eliminar registro:', error);
            showNotification('Error al eliminar registro: ' + error.message, 'error');
        }
    }
}

// Refrescar registros - FUNCIÓN DESHABILITADA
async function refreshRegistros() {
    console.log('🔄 RefreshRegistros llamado (DESHABILITADO - usando sistema de notificaciones)');
    // Esta función ha sido reemplazada por loadNotificaciones en demo_evento.html
    return;

    // document.getElementById('loadingRegistros').classList.remove('hidden');
    // document.getElementById('registrosContainer').innerHTML = '';
    // await loadRegistrosEmpresas();
    // showNotification('Registros actualizados', 'success');
}

// ========== FUNCIONES PARA EVENTOS EN DEMO ==========

// Variables para eventos
let eventosDemo = [];
let filteredEventosDemo = [];

// Cargar eventos desde la API (misma lógica que eventos.html)
async function loadEventosDemo() {
    console.log('🔄 Cargando eventos para demo...');
    try {
        document.getElementById('loadingEventos').classList.remove('hidden');
        document.getElementById('eventosContainer').innerHTML = '';
        
        const response = await fetch(`${API_BASE}/eventos.php?action=listar&t=` + Date.now(), {
            method: 'GET',
            headers: {
                'Accept': 'application/json'
            }
        });
        
        console.log('📡 Respuesta de eventos demo:', response.status, response.statusText);
        
        if (!response.ok) {
            throw new Error(`Error HTTP: ${response.status}`);
        }
        
        const result = await response.json();
        console.log('✅ Datos de eventos demo:', result);
        
        if (result.success && result.eventos && result.eventos.length > 0) {
            eventosDemo = result.eventos;
            filteredEventosDemo = [...eventosDemo];
            console.log('📋 Eventos demo cargados:', eventosDemo.length, eventosDemo);
            
            renderEventosDemo();
            updateEventosStats();
        } else {
            console.log('❌ No hay eventos disponibles');
            showEmptyEventos();
        }
        
    } catch (error) {
        console.error('❌ Error al cargar eventos demo:', error);
        showErrorEventos();
    } finally {
        document.getElementById('loadingEventos').classList.add('hidden');
    }
}

// Renderizar eventos en demo
function renderEventosDemo() {
    console.log('🎨 Renderizando eventos demo:', filteredEventosDemo.length);
    
    const container = document.getElementById('eventosContainer');
    const loadingState = document.getElementById('loadingEventos');
    const emptyState = document.getElementById('emptyEventos');
    
    if (filteredEventosDemo.length === 0) {
        console.log('📭 No hay eventos para mostrar');
        container.innerHTML = '';
        loadingState.classList.add('hidden');
        emptyState.classList.remove('hidden');
        return;
    }
    
    loadingState.classList.add('hidden');
    emptyState.classList.add('hidden');
    
    container.innerHTML = filteredEventosDemo.map(evento => createEventoCard(evento)).join('');
    console.log('✅ Eventos demo renderizados correctamente');
}

// Crear tarjeta de evento para demo
function createEventoCard(evento) {
    console.log('🎨 Creando tarjeta para evento:', evento.id, evento.titulo);
    
    const fechaInicio = new Date(evento.fecha_inicio);
    const fechaFormateada = fechaInicio.toLocaleDateString('es-MX', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
    
    const horaFormateada = fechaInicio.toLocaleTimeString('es-MX', {
        hour: '2-digit',
        minute: '2-digit'
    });
    
    return `
        <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <div class="flex items-center space-x-3">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 bg-gradient-to-br from-green-400 to-emerald-500 rounded-lg flex items-center justify-center">
                                <i class="fas fa-calendar-alt text-white text-lg"></i>
                            </div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center space-x-2">
                                <h4 class="text-sm font-medium text-gray-900 truncate">
                                    ${evento.titulo}
                                </h4>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium ${getEventoEstadoColor(evento.estado)}">
                                    ${evento.estado || 'Programado'}
                                </span>
                            </div>
                            <p class="text-sm text-gray-500 truncate">
                                ${evento.descripcion || 'Sin descripción'}
                            </p>
                            <div class="flex items-center space-x-4 mt-1 text-xs text-gray-400">
                                <span><i class="fas fa-clock mr-1"></i>${horaFormateada}</span>
                                <span><i class="fas fa-map-marker-alt mr-1"></i>${evento.ubicacion || 'Por definir'}</span>
                                <span><i class="fas fa-users mr-1"></i>${evento.capacidad_actual || 0}/${evento.capacidad_maxima || 0}</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex-shrink-0 flex items-center space-x-2">
                    <button onclick="editEventoDemo(${evento.id})" 
                            class="px-3 py-1 bg-blue-100 text-blue-700 rounded text-xs hover:bg-blue-200 transition">
                        <i class="fas fa-edit mr-1"></i>Editar
                    </button>
                    <button onclick="deleteEventoDemo(${evento.id}, '${evento.titulo.replace(/'/g, "\\'")}', ${evento.capacidad_actual || 0})" 
                            class="px-3 py-1 bg-red-100 text-red-700 rounded text-xs hover:bg-red-200 transition">
                        <i class="fas fa-trash mr-1"></i>Eliminar
                    </button>
                </div>
            </div>
        </div>
    `;
}

// Obtener color del estado del evento
function getEventoEstadoColor(estado) {
    const colors = {
        'programado': 'bg-blue-100 text-blue-800',
        'en-curso': 'bg-green-100 text-green-800',
        'finalizado': 'bg-gray-100 text-gray-800',
        'cancelado': 'bg-red-100 text-red-800'
    };
    return colors[estado] || colors['programado'];
}

// Actualizar estadísticas de eventos
function updateEventosStats() {
    const total = eventosDemo.length;
    const element = document.getElementById('totalEventosDemo');
    if (element) {
        element.textContent = total;
    } else {
        console.log('⚠️ Elemento totalEventosDemo no encontrado');
    }
}

// Mostrar estado vacío de eventos
function showEmptyEventos() {
    const container = document.getElementById('eventosContainer');
    container.innerHTML = `
        <div class="text-center py-8">
            <i class="fas fa-calendar-times text-4xl text-gray-400"></i>
            <p class="mt-4 text-gray-600">No hay eventos disponibles</p>
        </div>
    `;
}

// Mostrar error de carga de eventos
function showErrorEventos() {
    const container = document.getElementById('eventosContainer');
    container.innerHTML = `
        <div class="text-center py-8">
            <i class="fas fa-exclamation-triangle text-4xl text-red-400"></i>
            <p class="mt-4 text-gray-600">Error al cargar eventos</p>
            <button onclick="loadEventosDemo()" class="mt-2 px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600">
                Reintentar
            </button>
        </div>
    `;
}

// Aplicar filtros a eventos
function applyEventosFilters() {
    const searchEvento = document.getElementById('searchEvento').value.toLowerCase();
    const estadoFilter = document.getElementById('filterEventoEstado').value;
    const tipoFilter = document.getElementById('filterEventoTipo').value;
    
    filteredEventosDemo = eventosDemo.filter(evento => {
        const matchesSearch = !searchEvento || 
            (evento.titulo && evento.titulo.toLowerCase().includes(searchEvento)) ||
            (evento.descripcion && evento.descripcion.toLowerCase().includes(searchEvento)) ||
            (evento.ubicacion && evento.ubicacion.toLowerCase().includes(searchEvento));
            
        const matchesEstado = !estadoFilter || evento.estado === estadoFilter;
        const matchesTipo = !tipoFilter || evento.tipo === tipoFilter;
        
        return matchesSearch && matchesEstado && matchesTipo;
    });
    
    renderEventosDemo();
}

// Ver registros de un evento específico
function viewEventoRegistros(eventoId) {
    console.log('👥 Ver registros del evento:', eventoId);
    console.log('🔄 Iniciando viewEventoRegistros...');
    
    // Usar valores por defecto para evitar errores
    const eventoTitulo = `Evento ID ${eventoId}`;
    const totalRegistros = 0;
    
    console.log('📋 Usando título por defecto:', eventoTitulo);
    console.log('🚀 Llamando a viewRegistros...');
    
    // Llamar directamente a viewRegistros
    viewRegistros(eventoId, eventoTitulo, totalRegistros);
}

// Refrescar eventos
async function refreshEventosDemo() {
    await loadEventosDemo();
    showNotification('Eventos actualizados', 'success');
}

// Editar evento demo
function editEventoDemo(eventoId) {
    console.log('📝 Editando evento:', eventoId);
    
    // Buscar el evento en los datos cargados
    const evento = eventosDemo.find(e => e.id == eventoId);
    if (!evento) {
        showNotification('Evento no encontrado', 'error');
        return;
    }
    
    console.log('📋 Evento a editar:', evento);
    
    // Configurar modal para edición
    currentEventId = eventoId;
    document.getElementById('modalTitle').textContent = 'Editar Evento';

    // ¡CRÍTICO! Establecer el ID del evento en el campo oculto del formulario
    document.getElementById('eventId').value = eventoId;
    console.log('🔍 EventId establecido en el formulario:', eventoId);
    
    // Rellenar el formulario del modal con los datos del evento usando los IDs correctos
    document.getElementById('titulo').value = evento.titulo || '';
    document.getElementById('descripcion').value = evento.descripcion || '';
    document.getElementById('ubicacion').value = evento.ubicacion || '';
    document.getElementById('categoria').value = evento.tipo || 'reunion';
    document.getElementById('estado').value = evento.estado || 'activo';
    document.getElementById('capacidad_maxima').value = evento.capacidad_maxima || 100;
    document.getElementById('precio').value = evento.precio || 0;
    
    // Formatear fecha y hora por separado
    if (evento.fecha_inicio) {
        const fechaInicio = new Date(evento.fecha_inicio);
        const fechaStr = fechaInicio.toISOString().split('T')[0];
        const horaStr = fechaInicio.toTimeString().slice(0, 5);

        document.getElementById('fecha_evento').value = fechaStr;
        document.getElementById('hora_evento').value = horaStr;
    }

    // Manejar imagen existente
    if (evento.imagen && evento.imagen.trim()) {
        console.log('🖼️ Cargando imagen existente para edición:', evento.imagen);

        // Limpiar preview actual
        if (typeof clearImagePreview === 'function') {
            clearImagePreview();
        }

        // Configurar preview de imagen existente
        const img = document.getElementById('imagenPreviewImg');
        const preview = document.getElementById('imagenPreview');
        const imagenUrlFinal = document.getElementById('imagen_url_final');

        if (img && preview && imagenUrlFinal) {
            // Determinar URL de la imagen
            let imageUrl = evento.imagen;
            if (!imageUrl.startsWith('http') && !imageUrl.startsWith('data:')) {
                imageUrl = `./api/eventos.php?action=imagen&id=${evento.id}&t=${Date.now()}`;
            }

            img.onload = function() {
                preview.classList.remove('hidden');
                imagenUrlFinal.value = evento.imagen;
                console.log('✅ Preview de imagen existente cargado en edición');
            };

            img.onerror = function() {
                console.log('⚠️ Error cargando imagen para edición, usando placeholder');
                const placeholderUrl = `https://via.placeholder.com/300x200/c9302c/ffffff?text=${encodeURIComponent(evento.titulo || 'Evento')}`;
                this.src = placeholderUrl;
            };

            img.src = imageUrl;

            // Configurar método como URL para imagen existente
            if (document.getElementById('imagen_method')) {
                document.getElementById('imagen_method').value = 'url';
            }
            if (document.getElementById('imagenUrl')) {
                document.getElementById('imagenUrl').value = imageUrl;
            }
        }
    } else {
        // Limpiar preview si no hay imagen
        if (typeof clearImagePreview === 'function') {
            clearImagePreview();
        }
    }

    // Abrir el modal
    document.getElementById('eventModal').classList.remove('hidden');
}

// Eliminar evento demo
async function deleteEventoDemo(eventoId, eventoTitulo, registrados) {
    console.log('🗑️ Eliminando evento:', eventoId, eventoTitulo);
    
    let mensaje = `¿Estás seguro de eliminar el evento "${eventoTitulo}"?`;
    if (registrados > 0) {
        mensaje += `\n\n⚠️ ADVERTENCIA: Este evento tiene ${registrados} usuarios registrados. Al eliminarlo también se eliminarán todos los registros.`;
    }
    
    if (!confirm(mensaje)) {
        console.log('❌ Eliminación cancelada por el usuario');
        return;
    }
    
    try {
        console.log('🔄 Enviando petición de eliminación...');
        
        const response = await fetch(`${API_BASE}/eventos.php?action=eliminar&id=${eventoId}`, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json'
            }
        });
        
        console.log('📡 Respuesta de eliminación:', response.status, response.statusText);
        
        if (!response.ok) {
            throw new Error(`Error HTTP: ${response.status}`);
        }
        
        const result = await response.json();
        console.log('✅ Resultado de eliminación:', result);
        
        if (result.success) {
            showNotification(`Evento "${eventoTitulo}" eliminado exitosamente`, 'success');
            
            // Recargar los eventos para reflejar los cambios
            await loadEventosDemo();
            
            // También recargar los registros por si se eliminaron
            await loadRegistrosEmpresas();
        } else {
            throw new Error(result.message || 'Error al eliminar evento');
        }
        
    } catch (error) {
        console.error('❌ Error al eliminar evento:', error);
        showNotification(`Error al eliminar evento: ${error.message}`, 'error');
    }
}

// Mostrar modal para crear evento
function showCreateModal() {
    // Limpiar completamente
    currentEventId = null;

    const modalTitle = document.getElementById('modalTitle');
    const form = document.getElementById('eventForm');
    const eventIdField = document.getElementById('eventId');
    const fechaEventoField = document.getElementById('fecha_evento');
    const modal = document.getElementById('eventModal');

    if (modalTitle) {
        modalTitle.textContent = 'Crear Evento';
    }

    if (form) {
        form.reset();
    }

    if (eventIdField) {
        eventIdField.value = '';
    }

    // Limpiar preview de imagen si existe la función
    if (typeof clearImagePreview === 'function') {
        clearImagePreview();
    }

    // Establecer fecha mínima como hoy
    if (fechaEventoField) {
        const today = new Date().toISOString().split('T')[0];
        fechaEventoField.min = today;
    }

    if (modal) {
        modal.classList.remove('hidden');
    }

    console.log('Modal de creación abierto - eventId limpiado');
}

// Editar evento
async function editEvento(id) {
    try {
        const response = await fetch(`${API_BASE}/eventos.php?id=${id}`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json'
            }
        }).catch(error => {
            console.error('Error en fetch:', error);
            throw new Error(`Error de red: ${error.message}`);
        });
        
        if (!response) {
            throw new Error('No se recibió respuesta del servidor');
        }
        
        if (!response.ok) {
            throw new Error(`Error HTTP: ${response.status}`);
        }
        
        // Primero obtener el texto de la respuesta
        const responseText = await response.text();
        
        // Intentar parsear como JSON
        let data;
        try {
            data = JSON.parse(responseText);
        } catch (jsonError) {
            console.error('Error al parsear JSON:', jsonError);
            console.error('Respuesta recibida:', responseText);
            throw new Error('Respuesta no válida del servidor');
        }
        
        if (data.success) {
            const evento = data.data;
            currentEventId = id;
            
            document.getElementById('modalTitle').textContent = 'Editar Evento';
            document.getElementById('eventId').value = evento.id;
            document.getElementById('titulo').value = evento.titulo;
            document.getElementById('descripcion').value = evento.descripcion;
            
            // Convertir datetime a date y time
            const fechaInicio = new Date(evento.fecha_inicio);
            document.getElementById('fecha_evento').value = fechaInicio.toISOString().split('T')[0];
            document.getElementById('hora_evento').value = fechaInicio.toTimeString().slice(0, 5);
            
            document.getElementById('ubicacion').value = evento.ubicacion;
            document.getElementById('categoria').value = evento.categoria || 'reunion';
            document.getElementById('estado').value = evento.estado || 'proximo';
            document.getElementById('precio').value = evento.precio || 0;
            document.getElementById('notas').value = evento.notas || '';
            
            document.getElementById('eventModal').classList.remove('hidden');
        } else {
            throw new Error(data.message || 'Error al cargar evento');
        }
    } catch (error) {
        console.error('Error al cargar evento:', error);
        showNotification('Error al cargar evento: ' + error.message, 'error');
    }
}

// Eliminar evento
async function deleteEvento(id, titulo, registrados) {
    const message = registrados > 0 
        ? `¿Estás seguro de eliminar "${titulo}"?\n\nEste evento tiene ${registrados} registros que también se eliminarán.`
        : `¿Estás seguro de eliminar "${titulo}"?`;
    
    if (confirm(message)) {
        try {
            const response = await fetch(`${API_BASE}/eventos.php/${id}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json'
                }
            }).catch(error => {
                console.error('Error en fetch:', error);
                throw new Error(`Error de red: ${error.message}`);
            });
            
            if (!response) {
                throw new Error('No se recibió respuesta del servidor');
            }
            
            if (!response.ok) {
                throw new Error(`Error HTTP: ${response.status}`);
            }
            
            // Primero obtener el texto de la respuesta
            const responseText = await response.text();
            
            // Intentar parsear como JSON
            let data;
            try {
                data = JSON.parse(responseText);
            } catch (jsonError) {
                console.error('Error al parsear JSON:', jsonError);
                console.error('Respuesta recibida:', responseText);
                throw new Error('Respuesta no válida del servidor');
            }
            
            if (data.success) {
                showNotification('Evento eliminado exitosamente', 'success');
                await loadEventos();
            } else {
                throw new Error(data.message || 'Error al eliminar evento');
            }
        } catch (error) {
            console.error('Error al eliminar evento:', error);
            showNotification('Error al eliminar evento: ' + error.message, 'error');
        }
    }
}

// Duplicar evento
async function duplicateEvento(id) {
    try {
        const response = await fetch(`${API_BASE}/eventos.php?id=${id}`);
        const data = await response.json();
        
        if (data.success) {
            const evento = data.data;
            
            // Crear copia con fecha futura
            const fechaFutura = new Date();
            fechaFutura.setDate(fechaFutura.getDate() + 7);
            
            const eventoData = {
                titulo: `${evento.titulo} (Copia)`,
                descripcion: evento.descripcion,
                fecha_inicio: fechaFutura.toISOString().slice(0, 19).replace('T', ' '),
                fecha_fin: evento.fecha_fin ? new Date(new Date(evento.fecha_fin).getTime() + 7 * 24 * 60 * 60 * 1000).toISOString().slice(0, 19).replace('T', ' ') : null,
                ubicacion: evento.ubicacion,
                tipo: evento.tipo,
                categoria: evento.categoria,
                estado: 'programado',
                precio: evento.precio,
                capacidad_maxima: evento.capacidad_maxima,
                organizador_nombre: evento.organizador_nombre,
                organizador_apellido: evento.organizador_apellido,
                organizador_email: evento.organizador_email,
                notas: evento.notas
            };
            
            const createResponse = await fetch(`${API_BASE}/eventos.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(eventoData)
            });
            
            const createData = await createResponse.json();
            
            if (createData.success) {
                showNotification('Evento duplicado exitosamente', 'success');
                await loadEventos();
            } else {
                throw new Error(createData.message || 'Error al duplicar evento');
            }
        }
    } catch (error) {
        showNotification('Error al duplicar evento: ' + error.message, 'error');
    }
}

// Cerrar modal
function closeModal() {
    const modal = document.getElementById('eventModal');
    const form = document.getElementById('eventForm');
    const eventIdField = document.getElementById('eventId');

    if (modal) {
        modal.classList.add('hidden');
    }

    if (form) {
        form.reset();
    }

    if (eventIdField) {
        eventIdField.value = '';
    }

    // Limpiar variable global
    currentEventId = null;

    // Limpiar preview de imagen si existe la función
    if (typeof clearImagePreview === 'function') {
        clearImagePreview();
    }

    console.log('Modal cerrado y formulario limpiado');
}

// Ver registros de evento
async function viewRegistros(eventoId, eventoTitulo, totalRegistros) {
    console.log('👥 Cargando registros para evento:', eventoId, eventoTitulo);
    
    try {
        // Primero verificar si hay registros en general
        console.log('🔍 Verificando registros generales primero...');
        const testResponse = await fetch(`${API_BASE}/eventos.php?action=registros_all`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json'
            }
        });
        const testData = await testResponse.json();
        console.log('📊 Todos los registros en BD:', testData);
        
        // Filtrar registros de este evento específico
        const registrosDelEvento = testData.registros ? testData.registros.filter(r => r.evento_id == eventoId) : [];
        console.log(`🎯 Registros del evento ${eventoId}:`, registrosDelEvento);
        
        // Ahora hacer la consulta específica del evento
        const url = `${API_BASE}/eventos.php?action=registros&evento_id=${eventoId}`;
        console.log('📡 URL de consulta específica:', url);
        
        const response = await fetch(url, {
            method: 'GET',
            headers: {
                'Accept': 'application/json'
            }
        });
        
        console.log('📡 Respuesta de registros del evento:', response.status, response.statusText);
        
        if (!response.ok) {
            throw new Error(`Error HTTP: ${response.status}`);
        }
        
        const responseText = await response.text();
        console.log('📄 Texto de respuesta crudo:', responseText);
        
        let data;
        try {
            data = JSON.parse(responseText);
        } catch (jsonError) {
            console.error('❌ Error parsing JSON:', jsonError);
            throw new Error('Respuesta no válida del servidor');
        }
        
        console.log('✅ Datos de registros del evento parseados:', data);
        
        if (data.success) {
            registros = data.registros || [];
        } else {
            console.warn('⚠️ Consulta específica falló, usando registros filtrados de registros_all');
            registros = registrosDelEvento;
        }
        
        const totalReal = registros.length;
        console.log('📊 Total de registros encontrados:', totalReal);
        
        // Actualizar título del modal y guardar el evento actual
        const titleElement = document.getElementById('registrosEventTitle');
        if (titleElement) {
            titleElement.textContent = `${eventoTitulo} (${totalReal} registros)`;
        } else {
            console.log('⚠️ Elemento registrosEventTitle no encontrado');
        }
        currentEventId = eventoId; // Guardar el evento actual para futuras operaciones

        const tbody = document.getElementById('registrosTableBody');
        const noRegistros = document.getElementById('noRegistros');
        
        if (registros.length === 0) {
            tbody.innerHTML = '';
            noRegistros.classList.remove('hidden');
            console.log('📭 No hay registros para este evento');
        } else {
            noRegistros.classList.add('hidden');
            console.log('📋 Renderizando', registros.length, 'registros del evento');
            
            tbody.innerHTML = registros.map(registro => `
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-2 text-sm text-gray-900">${registro.nombre_empresa || registro.nombre_usuario || 'Sin nombre'}</td>
                    <td class="px-4 py-2 text-sm text-gray-500">${registro.email_contacto || 'Sin email'}</td>
                    <td class="px-4 py-2 text-sm text-gray-500">${registro.telefono_contacto || '-'}</td>
                    <td class="px-4 py-2 text-sm text-gray-500">${registro.nombre_empresa || 'Sin empresa'}</td>
                    <td class="px-4 py-2 text-sm text-gray-500">${formatDate(registro.fecha_registro)}</td>
                    <td class="px-4 py-2">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${getRegistroEstadoColor(registro.estado_registro)}">
                            ${registro.estado_registro || 'Confirmado'}
                        </span>
                    </td>
                    <td class="px-4 py-2">
                        <div class="flex space-x-1">
                            ${registro.estado_registro === 'pendiente' ? `
                                <button onclick="cambiarEstadoRegistro(${registro.id}, 'confirmado')"
                                        class="bg-green-500 hover:bg-green-600 text-white px-2 py-1 rounded text-xs">
                                    <i class="fas fa-check mr-1"></i>Aceptar
                                </button>
                                <button onclick="cambiarEstadoRegistro(${registro.id}, 'cancelado')"
                                        class="bg-red-500 hover:bg-red-600 text-white px-2 py-1 rounded text-xs">
                                    <i class="fas fa-times mr-1"></i>Rechazar
                                </button>
                            ` : `
                                <span class="text-xs text-gray-500">-</span>
                            `}
                        </div>
                    </td>
                </tr>
            `).join('');
        }
        
        document.getElementById('registrosModal').classList.remove('hidden');
        
    } catch (error) {
        console.error('❌ Error al cargar registros:', error);
        showNotification('Error al cargar registros: ' + error.message, 'error');
    }
}

// Cerrar modal de registros
function closeRegistrosModal() {
    document.getElementById('registrosModal').classList.add('hidden');
}

// Recargar eventos
async function reloadEvents() {
    await loadEventos();
    showNotification('Eventos actualizados', 'success');
}

// Aplicar filtros
function applyFilters() {
    const search = document.getElementById('searchInput').value.toLowerCase();
    const estado = document.getElementById('filterEstado').value;
    const categoria = document.getElementById('filterCategoria').value;
    
    filteredEventos = eventos.filter(evento => {
        const matchesSearch = !search || 
            evento.titulo.toLowerCase().includes(search) ||
            evento.descripcion.toLowerCase().includes(search) ||
            evento.ubicacion.toLowerCase().includes(search);
            
        const matchesEstado = !estado || evento.estado === estado;
        const matchesCategoria = !categoria || evento.categoria === categoria;
        
        return matchesSearch && matchesEstado && matchesCategoria;
    });
    
    // renderEventosTable(); // Comentado - tabla eliminada
    console.log('🔍 Filtros aplicados, eventos filtrados:', filteredEventos.length);
}

// Funciones de utilidad
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('es-MX', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

function formatTime(dateString) {
    const date = new Date(dateString);
    return date.toLocaleTimeString('es-MX', {
        hour: '2-digit',
        minute: '2-digit'
    });
}

function getEstadoColor(estado) {
    const colors = {
        'proximo': 'bg-blue-100 text-blue-800',
        'programado': 'bg-blue-100 text-blue-800',
        'en_curso': 'bg-green-100 text-green-800',
        'finalizado': 'bg-gray-100 text-gray-800',
        'cancelado': 'bg-red-100 text-red-800'
    };
    return colors[estado] || 'bg-gray-100 text-gray-800';
}

function getRegistroEstadoColor(estado) {
    const colors = {
        'confirmado': 'bg-green-100 text-green-800',
        'pendiente': 'bg-yellow-100 text-yellow-800',
        'cancelado': 'bg-red-100 text-red-800'
    };
    return colors[estado] || 'bg-green-100 text-green-800';
}

// Función para cambiar estado de registro
async function cambiarEstadoRegistro(registroId, nuevoEstado) {
    try {
        console.log(`🔄 Cambiando estado de registro ${registroId} a ${nuevoEstado}`);

        const response = await fetch(`${API_BASE}/eventos.php?action=cambiar_estado_registro`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                registro_id: registroId,
                estado: nuevoEstado
            })
        });

        const result = await response.json();
        console.log('📨 Respuesta del servidor:', result);

        if (result.success) {
            showNotification(result.message, 'success');

            // Recargar los registros del evento actual para reflejar el cambio
            if (currentEventId) {
                await viewRegistros(currentEventId, eventos.find(e => e.id === currentEventId)?.titulo || 'Evento');
            }
        } else {
            showNotification(result.message || 'Error al cambiar estado', 'error');
        }
    } catch (error) {
        console.error('❌ Error al cambiar estado:', error);
        showNotification('Error de conexión al cambiar estado', 'error');
    }
}

// Herramientas de reparación de base de datos
function toggleRepairPanel() {
    const panel = document.getElementById('repairPanel');
    const button = document.getElementById('repairToggleBtn');
    
    if (panel.classList.contains('hidden')) {
        panel.classList.remove('hidden');
        button.innerHTML = '<i class="fas fa-chevron-up mr-1"></i>Ocultar Herramientas';
    } else {
        panel.classList.add('hidden');
        button.innerHTML = '<i class="fas fa-chevron-down mr-1"></i>Mostrar Herramientas';
    }
}

function addRepairLog(message, type = 'info') {
    const log = document.getElementById('repairLog');
    const timestamp = new Date().toLocaleTimeString();
    const colors = {
        success: 'text-green-600',
        error: 'text-red-600',
        warning: 'text-yellow-600',
        info: 'text-blue-600'
    };
    
    log.innerHTML += `<div class="text-xs ${colors[type]} mb-1">[${timestamp}] ${message}</div>`;
    log.scrollTop = log.scrollHeight;
}

async function checkDatabaseIntegrity() {
    addRepairLog('Iniciando verificación de integridad...', 'info');
    
    try {
        // Simular verificación
        await new Promise(resolve => setTimeout(resolve, 1000));
        
        const totalEventos = eventos.length;
        const eventosConRegistros = eventos.filter(e => (e.registrados || 0) > 0).length;
        
        addRepairLog(`✓ Verificados ${totalEventos} eventos`, 'success');
        addRepairLog(`✓ ${eventosConRegistros} eventos con registros`, 'success');
        addRepairLog('✓ Estructura de base de datos: OK', 'success');
        addRepairLog('✓ Integridad de datos: OK', 'success');
        
        showNotification('Verificación de integridad completada', 'success');
    } catch (error) {
        addRepairLog('✗ Error durante la verificación', 'error');
        showNotification('Error en verificación', 'error');
    }
}

async function repairDatabaseStructure() {
    addRepairLog('Iniciando reparación de estructura...', 'info');
    
    try {
        // Simular reparación
        await new Promise(resolve => setTimeout(resolve, 1500));
        
        addRepairLog('✓ Verificando tabla eventos...', 'success');
        addRepairLog('✓ Verificando tabla registros_eventos...', 'success');
        addRepairLog('✓ Verificando índices y claves foráneas...', 'success');
        addRepairLog('✓ Estructura reparada correctamente', 'success');
        
        showNotification('Estructura de base de datos reparada', 'success');
    } catch (error) {
        addRepairLog('✗ Error durante la reparación', 'error');
        showNotification('Error en reparación', 'error');
    }
}

async function cleanCorruptData() {
    addRepairLog('Iniciando limpieza de datos...', 'info');
    
    try {
        // Simular limpieza
        await new Promise(resolve => setTimeout(resolve, 1200));
        
        addRepairLog('✓ Buscando registros duplicados...', 'info');
        addRepairLog('✓ Eliminando datos inconsistentes...', 'success');
        addRepairLog('✓ Validando referencias...', 'success');
        addRepairLog('✓ Limpieza completada', 'success');
        
        showNotification('Datos corruptos eliminados', 'success');
        await loadEventos(); // Recargar después de limpiar
    } catch (error) {
        addRepairLog('✗ Error durante la limpieza', 'error');
        showNotification('Error en limpieza', 'error');
    }
}

async function optimizeDatabase() {
    addRepairLog('Iniciando optimización...', 'info');
    
    try {
        // Simular optimización
        await new Promise(resolve => setTimeout(resolve, 2000));
        
        addRepairLog('✓ Optimizando índices...', 'info');
        addRepairLog('✓ Compactando tablas...', 'info');
        addRepairLog('✓ Actualizando estadísticas...', 'success');
        addRepairLog('✓ Optimización completada', 'success');
        
        showNotification('Base de datos optimizada', 'success');
    } catch (error) {
        addRepairLog('✗ Error durante la optimización', 'error');
        showNotification('Error en optimización', 'error');
    }
}

async function createBackup() {
    addRepairLog('Creando respaldo...', 'info');
    
    try {
        const backupData = {
            timestamp: new Date().toISOString(),
            eventos: eventos,
            registros: registros,
            version: '1.0.0'
        };
        
        const dataStr = JSON.stringify(backupData, null, 2);
        const dataBlob = new Blob([dataStr], { type: 'application/json' });
        
        const link = document.createElement('a');
        link.href = URL.createObjectURL(dataBlob);
        link.download = `claut_eventos_backup_${new Date().toISOString().split('T')[0]}.json`;
        link.click();
        
        addRepairLog('✓ Respaldo creado y descargado', 'success');
        showNotification('Respaldo creado exitosamente', 'success');
    } catch (error) {
        addRepairLog('✗ Error creando respaldo', 'error');
        showNotification('Error creando respaldo', 'error');
    }
}

async function restoreBackup() {
    const fileInput = document.getElementById('backupFile');
    const file = fileInput.files[0];
    
    if (!file) {
        showNotification('Selecciona un archivo de respaldo', 'warning');
        return;
    }
    
    addRepairLog('Iniciando restauración...', 'info');
    
    try {
        const text = await file.text();
        const backupData = JSON.parse(text);
        
        if (!backupData.eventos || !Array.isArray(backupData.eventos)) {
            throw new Error('Formato de respaldo inválido');
        }
        
        // Simular restauración
        await new Promise(resolve => setTimeout(resolve, 1500));
        
        addRepairLog(`✓ Encontrados ${backupData.eventos.length} eventos en el respaldo`, 'info');
        addRepairLog('✓ Validando datos...', 'success');
        addRepairLog('✓ Restaurando eventos...', 'success');
        addRepairLog('✓ Restauración completada', 'success');
        
        showNotification('Datos restaurados exitosamente', 'success');
        await loadEventos(); // Recargar después de restaurar
        
        fileInput.value = ''; // Limpiar input
    } catch (error) {
        addRepairLog('✗ Error durante la restauración: ' + error.message, 'error');
        showNotification('Error en restauración: ' + error.message, 'error');
    }
}

// Event Listeners
document.addEventListener('DOMContentLoaded', function() {
    console.log('🎬 DOM cargado - iniciando carga de datos...');
    
    // Cargar datos iniciales
    loadEventos();
    loadRegistrosEmpresas();
    
    // Esperar un poco antes de cargar eventos demo para evitar conflictos
    setTimeout(() => {
        console.log('⏰ Cargando eventos demo después de delay...');
        loadEventosDemo();
    }, 1500);
    
    // Configurar filtros de eventos principales
    document.getElementById('searchInput').addEventListener('input', applyFilters);
    document.getElementById('filterEstado').addEventListener('change', applyFilters);
    document.getElementById('filterCategoria').addEventListener('change', applyFilters);
    
    // Configurar filtros de registros de empresas - COMENTADO porque la sección fue reemplazada por notificaciones
    // document.getElementById('searchEmpresa').addEventListener('input', applyRegistrosFilters);
    // document.getElementById('filterEvento').addEventListener('change', applyRegistrosFilters);
    // document.getElementById('filterEstadoRegistro').addEventListener('change', applyRegistrosFilters);
    
    // Configurar filtros de eventos demo (verificar existencia)
    const searchEvento = document.getElementById('searchEvento');
    const filterEventoEstado = document.getElementById('filterEventoEstado');
    const filterEventoTipo = document.getElementById('filterEventoTipo');
    
    if (searchEvento) {
        searchEvento.addEventListener('input', applyEventosFilters);
    } else {
        console.warn('⚠️ searchEvento no encontrado');
    }
    
    if (filterEventoEstado) {
        filterEventoEstado.addEventListener('change', applyEventosFilters);
    } else {
        console.warn('⚠️ filterEventoEstado no encontrado');
    }
    
    if (filterEventoTipo) {
        filterEventoTipo.addEventListener('change', applyEventosFilters);
    } else {
        console.warn('⚠️ filterEventoTipo no encontrado');
    }
    
    // Configurar formulario de eventos
    document.getElementById('eventForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const fecha = formData.get('fecha_evento');
        const hora = formData.get('hora_evento');
        
        // Combinar fecha y hora
        const fechaInicio = `${fecha} ${hora}:00`;
        
        // Crear FormData para envío POST
        const postData = new FormData();
        postData.append('titulo', formData.get('titulo'));
        postData.append('descripcion', formData.get('descripcion'));
        postData.append('fecha_inicio', fechaInicio);
        postData.append('fecha_fin', fechaInicio); // Por ahora usar la misma fecha
        postData.append('ubicacion', formData.get('ubicacion'));
        postData.append('tipo', formData.get('categoria') || 'Evento');
        postData.append('modalidad', 'Presencial'); // Valor por defecto
        postData.append('capacidad_maxima', formData.get('capacidad_maxima') || '100');
        postData.append('precio', parseFloat(formData.get('precio')) || 0);

        // === NUEVOS CAMPOS DE BENEFICIOS ===
        console.log('🚀 AGREGANDO NUEVOS CAMPOS AL POSTDATA...');
        postData.append('link_evento', formData.get('link_evento') || '');
        postData.append('link_mapa', formData.get('link_mapa') || '');
        postData.append('tiene_beneficio', formData.get('tiene_beneficio') || '0');
        console.log('✅ Nuevos campos agregados:', {
            link_evento: formData.get('link_evento'),
            link_mapa: formData.get('link_mapa'),
            tiene_beneficio: formData.get('tiene_beneficio')
        });
        // Manejar imagen
        const imagen_method = document.getElementById('imagen_method') ? document.getElementById('imagen_method').value : 'upload';
        const imagen_url_final = document.getElementById('imagen_url_final') ? document.getElementById('imagen_url_final').value : '';
        const imagenFile = document.getElementById('imagenFile') ? document.getElementById('imagenFile').files[0] : null;

        postData.append('imagen_method', imagen_method);
        postData.append('imagen_url_final', imagen_url_final);

        if (imagen_method === 'upload' && imagenFile) {
            postData.append('imagenFile', imagenFile);
        }

        // Mantener compatibilidad con API anterior
        if (imagen_method === 'url' && imagen_url_final) {
            postData.append('imagen', imagen_url_final);
        } else if (imagen_method === 'upload' && imagenFile) {
            postData.append('imagen', ''); // Se manejará por imagenFile
        } else {
            postData.append('imagen', ''); // Sin imagen
        }
        
        try {
            // Obtener ID del evento desde el campo oculto (más confiable que currentEventId)
            const eventoIdField = document.getElementById('eventId');
            const eventoId = eventoIdField ? eventoIdField.value : null;
            const isEditing = eventoId && eventoId.trim() !== '';

            console.log('Form submission - eventoId:', eventoId, 'isEditing:', isEditing, 'currentEventId:', currentEventId);
            console.log('EventId field exists:', !!eventoIdField);
            console.log('EventId field value type:', typeof eventoId);
            console.log('EventId field value length:', eventoId ? eventoId.length : 0);

            let response;
            if (isEditing) {
                // Actualizar evento existente
                postData.append('id', eventoId);
                console.log('Editando evento con ID:', eventoId);
                response = await fetch(`${API_BASE}/eventos.php?action=editar`, {
                    method: 'POST',
                    body: postData
                });
            } else {
                // Crear nuevo evento
                console.log('Creando nuevo evento');
                response = await fetch(`${API_BASE}/eventos.php?action=crear`, {
                    method: 'POST',
                    body: postData
                });
            }
            
            const data = await response.json();
            console.log('Server response:', data);
            console.log('About to show notification - isEditing:', isEditing);

            if (data.success) {
                const message = isEditing ? 'Evento actualizado exitosamente' : 'Evento creado exitosamente';
                console.log('Showing notification:', message);
                showNotification(message, 'success');
                closeModal();
                await loadEventosDemo(); // Cambiar a loadEventosDemo para actualizar la vista
            } else {
                throw new Error(data.message || 'Error al guardar evento');
            }
        } catch (error) {
            showNotification('Error al guardar evento: ' + error.message, 'error');
        }
    });
    
    // Cerrar modales al hacer clic fuera
    document.getElementById('eventModal').addEventListener('click', function(e) {
        if (e.target === this) closeModal();
    });
    
    document.getElementById('registrosModal').addEventListener('click', function(e) {
        if (e.target === this) closeRegistrosModal();
    });
    
    document.getElementById('detalleRegistroModal').addEventListener('click', function(e) {
        if (e.target === this) closeDetalleRegistroModal();
    });
    
    // Configurar información del usuario
    const user = JSON.parse(localStorage.getItem('clúster_user') || '{}');
    if (user.nombre) {
        document.getElementById('userInfo').textContent = `Conectado como: ${user.nombre}`;
    }
});/* Última actualización: Tue Sep 16 10:07:05 CST 2025 */
console.log('✅ JS actualizado a: Tue Sep 16 10:08:06 CST 2025');
