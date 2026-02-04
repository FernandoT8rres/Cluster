console.log('Cargando eventos.js...');

// Variables globales
let eventosData = [];
let filteredEventos = [];
let currentPage = 1;
const itemsPerPage = 6;

// API helper
const EventosAPI = {
    baseUrl: './api',
    
    async getEventos(params = {}) {
        try {
            const queryParams = new URLSearchParams(params);
            const response = await fetch(`${this.baseUrl}/eventos.php?${queryParams}`);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return await response.json();
        } catch (error) {
            console.error('Error en getEventos:', error);
            throw error;
        }
    },
    
    async getEvento(id) {
        try {
            const response = await fetch(`${this.baseUrl}/eventos.php?id=${id}`);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return await response.json();
        } catch (error) {
            console.error('Error en getEvento:', error);
            throw error;
        }
    },
    
    async registrarUsuario(data) {
        try {
            const response = await fetch(`${this.baseUrl}/registros_eventos.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            });
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return await response.json();
        } catch (error) {
            console.error('Error en registrarUsuario:', error);
            throw error;
        }
    }
};

// Notificaciones
function showNotification(message, type = 'info') {
    const container = document.getElementById('notificationContainer') || createNotificationContainer();
    
    const colors = {
        success: 'bg-green-500',
        error: 'bg-red-500',
        warning: 'bg-yellow-500',
        info: 'bg-blue-500'
    };
    
    const notification = document.createElement('div');
    notification.className = `${colors[type]} text-white px-6 py-3 rounded-lg shadow-lg flex items-center space-x-2 transform translate-x-full transition-transform duration-300`;
    notification.innerHTML = `
        <i class="fas fa-info-circle"></i>
        <span>${message}</span>
        <button onclick="this.parentElement.remove()" class="ml-auto">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    container.appendChild(notification);
    
    setTimeout(() => notification.classList.remove('translate-x-full'), 100);
    setTimeout(() => {
        if (notification.parentElement) {
            notification.classList.add('translate-x-full');
            setTimeout(() => notification.remove(), 300);
        }
    }, 3000);
}

function createNotificationContainer() {
    const container = document.createElement('div');
    container.id = 'notificationContainer';
    container.className = 'fixed top-4 right-4 z-50 space-y-2';
    document.body.appendChild(container);
    return container;
}

// Cargar eventos
async function cargarEventos() {
    console.log('Cargando eventos...');
    try {
        showLoading();
        
        const response = await EventosAPI.getEventos({
            limit: 50
        });
        
        console.log('Respuesta API:', response);
        
        if (response.success) {
            eventosData = response.data?.eventos || [];
            filteredEventos = [...eventosData];
            console.log('Eventos cargados:', eventosData.length);
            renderEventos();
            updateStats();
        } else {
            throw new Error(response.message || 'Error al cargar eventos');
        }
    } catch (error) {
        console.error('Error cargando eventos:', error);
        showNotification('Error al cargar eventos: ' + error.message, 'error');
        showEmptyState();
    } finally {
        hideLoading();
    }
}

// Renderizar eventos
function renderEventos() {
    const container = document.getElementById('eventos-container');
    if (!container) {
        console.error('Contenedor eventos-container no encontrado');
        return;
    }
    
    console.log('Renderizando eventos:', filteredEventos.length);
    
    if (filteredEventos.length === 0) {
        showEmptyState();
        return;
    }
    
    const startIndex = (currentPage - 1) * itemsPerPage;
    const endIndex = startIndex + itemsPerPage;
    const eventosPage = filteredEventos.slice(startIndex, endIndex);
    
    container.innerHTML = eventosPage.map(evento => createEventCard(evento)).join('');
}

// Crear tarjeta de evento
function createEventCard(evento) {
    const fechaEvento = new Date(evento.fecha_inicio);
    const fechaFormateada = fechaEvento.toLocaleDateString('es-MX');
    const horaFormateada = fechaEvento.toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit' });
    
    const registrados = evento.registrados || 0;
    const capacidad = evento.capacidad_maxima || 100;
    const porcentaje = Math.round((registrados / capacidad) * 100);
    
    return `
        <div class="bg-white rounded-xl shadow-lg overflow-hidden hover:shadow-xl transition-all duration-300">
            <div class="relative h-32 bg-gradient-to-r from-purple-500 to-pink-500 flex items-center justify-center">
                <div class="text-center text-white">
                    <div class="text-sm font-semibold">${evento.tipo || 'Evento'}</div>
                    <div class="text-xs mt-1">${fechaFormateada}</div>
                </div>
                <div class="absolute top-3 right-3">
                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-white text-purple-800">
                        ${evento.estado || 'Programado'}
                    </span>
                </div>
            </div>
            
            <div class="p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-2">${evento.titulo}</h3>
                <p class="text-gray-600 text-sm mb-4">${evento.descripcion.substring(0, 100)}...</p>
                
                <div class="space-y-2 mb-4">
                    <div class="flex items-center text-sm text-gray-500">
                        <i class="fas fa-clock mr-2 text-blue-500"></i>
                        <span>${horaFormateada}</span>
                    </div>
                    <div class="flex items-center text-sm text-gray-500">
                        <i class="fas fa-map-marker-alt mr-2 text-red-500"></i>
                        <span>${evento.ubicacion}</span>
                    </div>
                    <div class="flex items-center text-sm text-gray-500">
                        <i class="fas fa-users mr-2 text-green-500"></i>
                        <span>${registrados} / ${capacidad} personas</span>
                    </div>
                </div>
                
                <div class="mb-4">
                    <div class="flex justify-between text-xs text-gray-500 mb-1">
                        <span>Ocupación</span>
                        <span>${porcentaje}%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-gradient-to-r from-blue-500 to-purple-500 h-2 rounded-full" 
                             style="width: ${Math.min(porcentaje, 100)}%"></div>
                    </div>
                </div>
                
                <div class="flex space-x-2">
                    <button onclick="verDetallesEvento(${evento.id})" 
                            class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-800 font-medium py-2 px-4 rounded-lg transition-colors text-sm">
                        Ver Detalles
                    </button>
                    <button onclick="registrarseEvento(${evento.id}, '${evento.titulo.replace(/'/g, "\\'")}', ${evento.precio || 0})" 
                            class="flex-1 bg-purple-600 hover:bg-purple-700 text-white font-medium py-2 px-4 rounded-lg transition-colors text-sm">
                        Registrarse
                    </button>
                </div>
            </div>
        </div>
    `;
}

// Ver detalles del evento
async function verDetallesEvento(eventoId) {
    try {
        showLoading();
        const response = await EventosAPI.getEvento(eventoId);
        
        if (response.success) {
            mostrarModalDetalles(response.data);
        } else {
            throw new Error(response.message || 'Error al cargar detalles');
        }
    } catch (error) {
        showNotification('Error al cargar detalles: ' + error.message, 'error');
    } finally {
        hideLoading();
    }
}

// Mostrar modal de detalles
function mostrarModalDetalles(evento) {
    const modal = document.createElement('div');
    modal.id = 'eventModal';
    modal.className = 'fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4';
    
    modal.innerHTML = `
        <div class="bg-white rounded-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="relative p-6 bg-gradient-to-r from-purple-600 to-pink-600 text-white">
                <button onclick="cerrarModal()" class="absolute top-4 right-4 text-white hover:text-gray-200">
                    <i class="fas fa-times text-xl"></i>
                </button>
                <h2 class="text-2xl font-bold">${evento.titulo}</h2>
                <p class="text-purple-100">${evento.tipo} • ${new Date(evento.fecha_inicio).toLocaleDateString('es-MX')}</p>
            </div>
            
            <div class="p-6">
                <div class="space-y-4 mb-6">
                    <div class="flex items-center space-x-3">
                        <i class="fas fa-clock text-blue-500"></i>
                        <span>${new Date(evento.fecha_inicio).toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit' })}</span>
                    </div>
                    <div class="flex items-center space-x-3">
                        <i class="fas fa-map-marker-alt text-red-500"></i>
                        <span>${evento.ubicacion}</span>
                    </div>
                    <div class="flex items-center space-x-3">
                        <i class="fas fa-users text-green-500"></i>
                        <span>${evento.registrados || 0} / ${evento.capacidad_maxima} personas</span>
                    </div>
                </div>
                
                <div class="mb-6">
                    <h3 class="text-lg font-semibold mb-3">Descripción</h3>
                    <p class="text-gray-700">${evento.descripcion}</p>
                </div>
                
                <div class="flex space-x-3">
                    <button onclick="cerrarModal()" 
                            class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-800 font-medium py-3 px-6 rounded-lg">
                        Cerrar
                    </button>
                    <button onclick="registrarseEvento(${evento.id}, '${evento.titulo.replace(/'/g, "\\'")}', ${evento.precio || 0}); cerrarModal();" 
                            class="flex-1 bg-purple-600 hover:bg-purple-700 text-white font-medium py-3 px-6 rounded-lg">
                        Registrarse
                    </button>
                </div>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
}

// Cerrar modal
function cerrarModal() {
    const modal = document.getElementById('eventModal');
    if (modal) {
        modal.remove();
    }
}

// Registrarse a evento
async function registrarseEvento(eventoId, eventoTitulo, precio = 0) {
    console.log('Registrándose al evento:', eventoId, eventoTitulo);
    
    // Verificar usuario desde sesión
    let user = {};
    if (window.authSessionManager && window.authSessionManager.currentUser) {
        user = window.authSessionManager.currentUser;
    }
    
    if (!user.email) {
        showNotification('Debes iniciar sesión para registrarte', 'warning');
        setTimeout(() => {
            window.location.href = './pages/sign-in.html';
        }, 2000);
        return;
    }
    
    if (!confirm(`¿Deseas registrarte al evento "${eventoTitulo}"?`)) {
        return;
    }
    
    try {
        showLoading();
        
        const registroData = {
            evento_id: eventoId,
            user_id: user.id || null,
            nombre: user.nombre || 'Usuario',
            apellido: user.apellido || '',
            email: user.email,
            telefono: user.telefono || '',
            empresa: user.empresa || '',
            cargo: user.cargo || ''
        };
        
        const response = await EventosAPI.registrarUsuario(registroData);
        
        if (response.success) {
            showNotification('¡Registro exitoso!', 'success');
            await cargarEventos();
        } else {
            throw new Error(response.message || 'Error al registrarse');
        }
    } catch (error) {
        console.error('Error en registro:', error);
        showNotification('Error al registrarse: ' + error.message, 'error');
    } finally {
        hideLoading();
    }
}

// Actualizar estadísticas
function updateStats() {
    const totalEventos = eventosData.length;
    
    const elements = {
        'totalEventos': totalEventos + ' Eventos'
    };
    
    Object.entries(elements).forEach(([id, value]) => {
        const element = document.getElementById(id);
        if (element) {
            element.textContent = value;
        }
    });
}

// Estados de carga
function showLoading() {
    const spinner = document.getElementById('loadingSpinner');
    if (spinner) {
        spinner.classList.remove('hidden');
    }
}

function hideLoading() {
    const spinner = document.getElementById('loadingSpinner');
    if (spinner) {
        spinner.classList.add('hidden');
    }
}

function showEmptyState() {
    const container = document.getElementById('eventos-container');
    if (container) {
        container.innerHTML = `
            <div class="col-span-full text-center py-16">
                <div class="text-6xl mb-4">📅</div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No hay eventos disponibles</h3>
                <p class="text-gray-500">Intenta recargar la página</p>
            </div>
        `;
    }
}

// Funciones globales
window.verDetallesEvento = verDetallesEvento;
window.registrarseEvento = registrarseEvento;
window.cerrarModal = cerrarModal;

// Función de recargar eventos
window.refreshEvents = async function() {
    await cargarEventos();
    showNotification('Eventos actualizados', 'success');
};

// Inicialización
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, inicializando eventos...');
    
    // Solo si estamos en la página de eventos
    if (document.getElementById('eventos-container')) {
        console.log('Contenedor encontrado, cargando eventos...');
        cargarEventos();
    } else {
        console.log('No se encontró el contenedor de eventos');
    }
});

console.log('eventos.js cargado correctamente');