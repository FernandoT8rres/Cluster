// Variables globales para el demo de descuentos
let descuentos = [];
let filteredDescuentos = [];
let currentDescuentoId = null;

// Configuración de la API
const API_BASE = './api';

// Utilidades de notificación
function showNotification(message, type = 'info') {
    const container = document.createElement('div');
    container.className = 'fixed top-4 right-4 z-50 max-w-sm';
    
    const colors = {
        success: 'bg-green-500',
        error: 'bg-red-500',
        warning: 'bg-yellow-500',
        info: 'bg-blue-500'
    };
    
    const notification = document.createElement('div');
    notification.className = `${colors[type]} text-white px-6 py-3 rounded-lg shadow-lg flex items-center space-x-2 transform translate-x-full transition-transform duration-300`;
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check' : type === 'error' ? 'times' : type === 'warning' ? 'exclamation' : 'info'}-circle"></i>
        <span>${message}</span>
        <button onclick="this.parentElement.parentElement.remove()" class="ml-3 text-white hover:text-gray-200">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    container.appendChild(notification);
    document.body.appendChild(container);
    
    // Animar entrada
    setTimeout(() => notification.classList.remove('translate-x-full'), 100);
    
    // Auto remover
    setTimeout(() => {
        if (container.parentElement) {
            notification.classList.add('translate-x-full');
            setTimeout(() => {
                if (container.parentElement) {
                    container.remove();
                }
            }, 300);
        }
    }, 5000);
}

// Cargar descuentos desde la API
async function loadDescuentos() {
    try {
        console.log('Iniciando carga de descuentos...');
        
        const response = await fetch(`${API_BASE}/descuentos.php`, {
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
                throw new Error('Respuesta vacía del servidor.');
            }
            
            throw new Error(`Respuesta inválida del servidor: ${responseText.substring(0, 200)}`);
        }
        
        // Verificar estructura de respuesta esperada
        if (!data.success) {
            throw new Error(data.message || 'Error desconocido en la respuesta del servidor');
        }
        
        // Guardar los descuentos
        descuentos = data.data || [];
        filteredDescuentos = [...descuentos];
        
        console.log(`${descuentos.length} descuentos cargados exitosamente`);
        
        // Actualizar la interfaz
        updateDescuentosTable();
        
        showNotification(`Se cargaron ${descuentos.length} descuentos correctamente`, 'success');
        
    } catch (error) {
        console.error('Error completo al cargar descuentos:', error);
        
        // Mostrar mensaje de error al usuario
        showNotification(`Error: ${error.message}`, 'error');
        
        // Mostrar estado de error en la tabla
        showErrorState(error.message);
    }
}

// Actualizar tabla de descuentos
function updateDescuentosTable() {
    const tbody = document.getElementById('tablaDescuentos');
    const noData = document.getElementById('noData');
    
    if (!tbody) return;
    
    // Ocultar estado de "no data"
    if (noData) noData.classList.add('hidden');
    
    // Limpiar tabla
    tbody.innerHTML = '';
    
    if (filteredDescuentos.length === 0) {
        if (noData) noData.classList.remove('hidden');
        return;
    }
    
    // Generar filas
    filteredDescuentos.forEach(descuento => {
        const row = createDescuentoRow(descuento);
        tbody.appendChild(row);
    });
}

// Crear fila de descuento
function createDescuentoRow(descuento) {
    const row = document.createElement('tr');
    row.className = 'hover:bg-gray-50';
    
    const logoUrl = descuento.logo_url || 
        `https://ui-avatars.com/api/?name=${encodeURIComponent(descuento.nombre_empresa)}&background=c7252b&color=fff&size=40`;
    
    const estadoBadge = descuento.activo == 1
        ? '<span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Activo</span>'
        : '<span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Inactivo</span>';
    
    const categoriaBadge = {
        'Restaurante': 'bg-orange-100 text-orange-800',
        'Hoteles': 'bg-blue-100 text-blue-800',
        'Pastelería': 'bg-pink-100 text-pink-800',
        'Entretenimiento': 'bg-purple-100 text-purple-800',
        'Tecnología': 'bg-green-100 text-green-800',
        'Salud': 'bg-cyan-100 text-cyan-800',
        'Otro': 'bg-gray-100 text-gray-800'
    };
    
    const categoriaClass = categoriaBadge[descuento.categoria] || categoriaBadge['Otro'];
    
    row.innerHTML = `
        <td class="px-6 py-4 whitespace-nowrap">
            <div class="flex items-center">
                <img class="h-10 w-10 rounded-full" src="${logoUrl}" alt="${descuento.nombre_empresa}"
                     onerror="this.src='https://ui-avatars.com/api/?name=${encodeURIComponent(descuento.nombre_empresa)}&background=c7252b&color=fff&size=40'">
                <div class="ml-4">
                    <div class="text-sm font-medium text-gray-900">${descuento.nombre_empresa}</div>
                    ${descuento.codigo_promocional ? `<div class="text-xs text-gray-500">Código: ${descuento.codigo_promocional}</div>` : ''}
                </div>
            </div>
        </td>
        <td class="px-6 py-4 whitespace-nowrap">
            <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium ${categoriaClass}">
                ${descuento.categoria}
            </span>
        </td>
        <td class="px-6 py-4 whitespace-nowrap">
            <span class="text-lg font-bold text-clúster-red">${descuento.porcentaje_descuento}%</span>
        </td>
        <td class="px-6 py-4 whitespace-nowrap">
            ${estadoBadge}
        </td>
        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
            ${formatearFecha(descuento.fecha_creacion)}
        </td>
        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
            <div class="flex space-x-2">
                <button onclick="editarDescuento(${descuento.id})" 
                        class="text-blue-600 hover:text-blue-900 transition" title="Editar">
                    <i class="fas fa-edit"></i>
                </button>
                <button onclick="eliminarDescuento(${descuento.id})" 
                        class="text-red-600 hover:text-red-900 transition" title="Eliminar">
                    <i class="fas fa-trash"></i>
                </button>
                <button onclick="toggleEstado(${descuento.id}, ${descuento.activo})" 
                        class="text-yellow-600 hover:text-yellow-900 transition" 
                        title="${descuento.activo == 1 ? 'Desactivar' : 'Activar'}">
                    <i class="fas fa-power-off"></i>
                </button>
            </div>
        </td>
    `;
    
    return row;
}

// Mostrar estado de error
function showErrorState(errorMessage) {
    const tbody = document.getElementById('tablaDescuentos');
    const noData = document.getElementById('noData');
    
    if (noData) noData.classList.add('hidden');
    
    if (tbody) {
        tbody.innerHTML = `
            <tr>
                <td colspan="6" class="px-6 py-8 text-center">
                    <i class="fas fa-exclamation-triangle text-4xl text-red-400 mb-4"></i>
                    <p class="text-red-600 font-medium mb-2">Error al cargar descuentos</p>
                    <p class="text-gray-600 text-sm mb-4">${errorMessage}</p>
                    <button onclick="loadDescuentos()" 
                            class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition">
                        <i class="fas fa-sync-alt mr-2"></i>Reintentar
                    </button>
                </td>
            </tr>
        `;
    }
}

// Mostrar modal crear descuento
function mostrarModalCrear() {
    currentDescuentoId = null;
    document.getElementById('modalTitulo').textContent = 'Nuevo Descuento';
    document.getElementById('formDescuento').reset();
    document.getElementById('descuentoId').value = '';
    document.getElementById('activo').checked = true;
    document.getElementById('modalDescuento').classList.remove('hidden');
}

// Editar descuento
async function editarDescuento(id) {
    const descuento = descuentos.find(d => d.id == id);
    if (!descuento) {
        showNotification('Descuento no encontrado', 'error');
        return;
    }
    
    currentDescuentoId = id;
    document.getElementById('modalTitulo').textContent = 'Editar Descuento';
    document.getElementById('descuentoId').value = descuento.id;
    document.getElementById('nombreEmpresa').value = descuento.nombre_empresa || '';
    document.getElementById('categoria').value = descuento.categoria || '';
    document.getElementById('porcentajeDescuento').value = descuento.porcentaje_descuento || '';
    document.getElementById('descripcion').value = descuento.descripcion || '';
    document.getElementById('ubicacion').value = descuento.ubicacion || '';
    document.getElementById('horario').value = descuento.horario || '';
    document.getElementById('comoAplicar').value = descuento.como_aplicar || '';
    document.getElementById('telefono').value = descuento.telefono || '';
    document.getElementById('codigoPromocional').value = descuento.codigo_promocional || '';
    document.getElementById('logoUrl').value = descuento.logo_url || '';
    document.getElementById('activo').checked = descuento.activo == 1;
    
    document.getElementById('modalDescuento').classList.remove('hidden');
}

// Eliminar descuento
async function eliminarDescuento(id) {
    if (!confirm('¿Estás seguro de que quieres eliminar este descuento?')) {
        return;
    }
    
    try {
        const response = await fetch(`${API_BASE}/descuentos.php`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ id: id })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showNotification('Descuento eliminado correctamente', 'success');
            await loadDescuentos();
        } else {
            throw new Error(data.message || 'Error al eliminar descuento');
        }
    } catch (error) {
        console.error('Error al eliminar descuento:', error);
        showNotification(`Error al eliminar descuento: ${error.message}`, 'error');
    }
}

// Toggle estado activo/inactivo
async function toggleEstado(id, estadoActual) {
    const descuento = descuentos.find(d => d.id == id);
    if (!descuento) return;
    
    const nuevoEstado = estadoActual == 1 ? 0 : 1;
    
    try {
        const response = await fetch(`${API_BASE}/descuentos.php`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                id: parseInt(id),
                nombre_empresa: descuento.nombre_empresa,
                categoria: descuento.categoria,
                porcentaje_descuento: descuento.porcentaje_descuento,
                descripcion: descuento.descripcion || '',
                ubicacion: descuento.ubicacion || '',
                horario: descuento.horario || '',
                como_aplicar: descuento.como_aplicar || '',
                telefono: descuento.telefono || '',
                codigo_promocional: descuento.codigo_promocional || '',
                logo_url: descuento.logo_url || '',
                activo: nuevoEstado
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showNotification(`Descuento ${nuevoEstado ? 'activado' : 'desactivado'} correctamente`, 'success');
            await loadDescuentos();
        } else {
            throw new Error(data.message || 'Error al cambiar el estado');
        }
    } catch (error) {
        console.error('Error al cambiar estado:', error);
        showNotification(`Error al cambiar el estado: ${error.message}`, 'error');
    }
}

// Cerrar modal
function cerrarModal() {
    document.getElementById('modalDescuento').classList.add('hidden');
    currentDescuentoId = null;
}

// Manejar envío del formulario
async function guardarDescuento(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    
    // Convertir FormData a objeto
    const data = {
        nombre_empresa: formData.get('nombre_empresa') || document.getElementById('nombreEmpresa').value,
        categoria: formData.get('categoria') || document.getElementById('categoria').value,
        porcentaje_descuento: parseInt(formData.get('porcentaje_descuento') || document.getElementById('porcentajeDescuento').value),
        descripcion: formData.get('descripcion') || document.getElementById('descripcion').value,
        ubicacion: formData.get('ubicacion') || document.getElementById('ubicacion').value,
        horario: formData.get('horario') || document.getElementById('horario').value,
        como_aplicar: formData.get('como_aplicar') || document.getElementById('comoAplicar').value,
        telefono: formData.get('telefono') || document.getElementById('telefono').value,
        codigo_promocional: formData.get('codigo_promocional') || document.getElementById('codigoPromocional').value,
        logo_url: formData.get('logo_url') || document.getElementById('logoUrl').value,
        activo: document.getElementById('activo').checked ? 1 : 0
    };
    
    try {
        let response;
        if (currentDescuentoId) {
            // Actualizar descuento existente
            data.id = currentDescuentoId;
            response = await fetch(`${API_BASE}/descuentos.php`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            });
        } else {
            // Crear nuevo descuento
            response = await fetch(`${API_BASE}/descuentos.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            });
        }
        
        const result = await response.json();
        
        if (result.success) {
            showNotification(
                currentDescuentoId ? 'Descuento actualizado correctamente' : 'Descuento creado correctamente', 
                'success'
            );
            cerrarModal();
            await loadDescuentos();
        } else {
            throw new Error(result.message || 'Error al guardar descuento');
        }
    } catch (error) {
        console.error('Error al guardar descuento:', error);
        showNotification(`Error al guardar descuento: ${error.message}`, 'error');
    }
}

// Filtros
function filtrarDescuentos() {
    const categoria = document.getElementById('filtroCategoria').value;
    const estado = document.getElementById('filtroEstado').value;
    const busqueda = document.getElementById('busqueda').value.toLowerCase();
    
    filteredDescuentos = descuentos.filter(descuento => {
        const matchesCategoria = !categoria || descuento.categoria === categoria;
        const matchesEstado = estado === '' || descuento.activo.toString() === estado;
        const matchesBusqueda = !busqueda || 
            descuento.nombre_empresa.toLowerCase().includes(busqueda) ||
            (descuento.descripcion && descuento.descripcion.toLowerCase().includes(busqueda));
        
        return matchesCategoria && matchesEstado && matchesBusqueda;
    });
    
    updateDescuentosTable();
}

// Función para formatear fecha
function formatearFecha(fecha) {
    if (!fecha) return '';
    const date = new Date(fecha);
    return date.toLocaleDateString('es-ES', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

// Inicialización cuando se carga la página
document.addEventListener('DOMContentLoaded', function() {
    console.log('Inicializando demo de descuentos...');
    
    // Configurar event listeners para los filtros
    const filtroCategoria = document.getElementById('filtroCategoria');
    if (filtroCategoria) {
        filtroCategoria.addEventListener('change', filtrarDescuentos);
    }
    
    const filtroEstado = document.getElementById('filtroEstado');
    if (filtroEstado) {
        filtroEstado.addEventListener('change', filtrarDescuentos);
    }
    
    const busqueda = document.getElementById('busqueda');
    if (busqueda) {
        busqueda.addEventListener('keyup', filtrarDescuentos);
    }
    
    // Cargar descuentos iniciales
    loadDescuentos();
});