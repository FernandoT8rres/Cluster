// Configuración de la API
const API_URL = './backend/api.php';

// Variables globales
let descuentos = [];
let descuentosFiltrados = [];
let descuentoParaEliminar = null;

// Cargar descuentos al cargar la página
document.addEventListener('DOMContentLoaded', function() {
    cargarDescuentos();
});

// Función para cargar todos los descuentos
async function cargarDescuentos() {
    try {
        const response = await fetch(API_URL);
        
        if (response.ok) {
            const data = await response.json();
            descuentos = data.records || [];
            descuentosFiltrados = [...descuentos];
            renderizarTabla();
        } else {
            descuentos = [];
            descuentosFiltrados = [];
            renderizarTabla();
        }
    } catch (error) {
        console.error('Error al cargar descuentos:', error);
        mostrarNotificacion('Error al cargar los descuentos', 'error');
        descuentos = [];
        descuentosFiltrados = [];
        renderizarTabla();
    }
}

// Función para renderizar la tabla
function renderizarTabla() {
    const tbody = document.getElementById('tablaDescuentos');
    const noData = document.getElementById('noData');
    
    if (descuentosFiltrados.length === 0) {
        tbody.innerHTML = '';
        noData.classList.remove('hidden');
        return;
    }
    
    noData.classList.add('hidden');
    
    tbody.innerHTML = descuentosFiltrados.map(descuento => `
        <tr class="hover:bg-gray-50">
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                    ${descuento.logo_url ? `<img src="${descuento.logo_url}" alt="Logo" class="w-8 h-8 rounded-full mr-3 object-cover">` : '<div class="w-8 h-8 bg-gray-200 rounded-full mr-3 flex items-center justify-center"><i class="fas fa-building text-gray-400"></i></div>'}
                    <div>
                        <div class="text-sm font-medium text-gray-900">${descuento.nombre_empresa}</div>
                        ${descuento.codigo_promocional ? `<div class="text-xs text-gray-500">Código: ${descuento.codigo_promocional}</div>` : ''}
                    </div>
                </div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                    ${descuento.categoria}
                </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <span class="text-lg font-bold text-clúster-red">${descuento.porcentaje_descuento}%</span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${descuento.activo == 1 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">
                    ${descuento.activo == 1 ? 'Activo' : 'Inactivo'}
                </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                ${formatearFecha(descuento.fecha_creacion)}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                <div class="flex space-x-2">
                    <button onclick="editarDescuento(${descuento.id})" class="text-blue-600 hover:text-blue-900" title="Editar">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button onclick="mostrarModalEliminar(${descuento.id})" class="text-red-600 hover:text-red-900" title="Eliminar">
                        <i class="fas fa-trash"></i>
                    </button>
                    <button onclick="toggleEstado(${descuento.id}, ${descuento.activo})" class="text-yellow-600 hover:text-yellow-900" title="${descuento.activo == 1 ? 'Desactivar' : 'Activar'}">
                        <i class="fas fa-power-off"></i>
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
}

// Función para filtrar descuentos
function filtrarDescuentos() {
    const categoria = document.getElementById('filtroCategoria').value;
    const estado = document.getElementById('filtroEstado').value;
    const busqueda = document.getElementById('busqueda').value.toLowerCase();
    
    descuentosFiltrados = descuentos.filter(descuento => {
        const cumpleCategoria = !categoria || descuento.categoria === categoria;
        const cumpleEstado = estado === '' || descuento.activo == estado;
        const cumpleBusqueda = !busqueda || descuento.nombre_empresa.toLowerCase().includes(busqueda);
        
        return cumpleCategoria && cumpleEstado && cumpleBusqueda;
    });
    
    renderizarTabla();
}

// Función para mostrar modal de crear
function mostrarModalCrear() {
    document.getElementById('modalTitulo').textContent = 'Nuevo Descuento';
    document.getElementById('formDescuento').reset();
    document.getElementById('descuentoId').value = '';
    document.getElementById('activo').checked = true;
    document.getElementById('modalDescuento').classList.remove('hidden');
}

// Función para editar descuento
function editarDescuento(id) {
    const descuento = descuentos.find(d => d.id == id);
    if (!descuento) return;
    
    document.getElementById('modalTitulo').textContent = 'Editar Descuento';
    document.getElementById('descuentoId').value = descuento.id;
    document.getElementById('nombreEmpresa').value = descuento.nombre_empresa;
    document.getElementById('categoria').value = descuento.categoria;
    document.getElementById('porcentajeDescuento').value = descuento.porcentaje_descuento;
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

// Función para guardar descuento
async function guardarDescuento(event) {
    event.preventDefault();
    
    const id = document.getElementById('descuentoId').value;
    const formData = {
        nombre_empresa: document.getElementById('nombreEmpresa').value,
        categoria: document.getElementById('categoria').value,
        porcentaje_descuento: parseInt(document.getElementById('porcentajeDescuento').value),
        descripcion: document.getElementById('descripcion').value,
        ubicacion: document.getElementById('ubicacion').value,
        horario: document.getElementById('horario').value,
        como_aplicar: document.getElementById('comoAplicar').value,
        telefono: document.getElementById('telefono').value,
        codigo_promocional: document.getElementById('codigoPromocional').value,
        logo_url: document.getElementById('logoUrl').value,
        activo: document.getElementById('activo').checked ? 1 : 0
    };
    
    try {
        let response;
        
        if (id) {
            // Actualizar
            formData.id = parseInt(id);
            response = await fetch(API_URL, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(formData)
            });
        } else {
            // Crear
            response = await fetch(API_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(formData)
            });
        }
        
        if (response.ok) {
            mostrarNotificacion(id ? 'Descuento actualizado correctamente' : 'Descuento creado correctamente', 'success');
            cerrarModal();
            cargarDescuentos();
        } else {
            const errorData = await response.json();
            mostrarNotificacion(errorData.message || 'Error al guardar el descuento', 'error');
        }
    } catch (error) {
        console.error('Error al guardar descuento:', error);
        mostrarNotificacion('Error al guardar el descuento', 'error');
    }
}

// Función para mostrar modal de eliminar
function mostrarModalEliminar(id) {
    descuentoParaEliminar = id;
    document.getElementById('modalEliminar').classList.remove('hidden');
}

// Función para confirmar eliminación
async function confirmarEliminar() {
    if (!descuentoParaEliminar) return;
    
    try {
        const response = await fetch(API_URL, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ id: descuentoParaEliminar })
        });
        
        if (response.ok) {
            mostrarNotificacion('Descuento eliminado correctamente', 'success');
            cerrarModalEliminar();
            cargarDescuentos();
        } else {
            const errorData = await response.json();
            mostrarNotificacion(errorData.message || 'Error al eliminar el descuento', 'error');
        }
    } catch (error) {
        console.error('Error al eliminar descuento:', error);
        mostrarNotificacion('Error al eliminar el descuento', 'error');
    }
}

// Función para cambiar estado activo/inactivo
async function toggleEstado(id, estadoActual) {
    const descuento = descuentos.find(d => d.id == id);
    if (!descuento) return;
    
    const nuevoEstado = estadoActual == 1 ? 0 : 1;
    
    try {
        const response = await fetch(API_URL, {
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
        
        if (response.ok) {
            mostrarNotificacion(`Descuento ${nuevoEstado ? 'activado' : 'desactivado'} correctamente`, 'success');
            cargarDescuentos();
        } else {
            const errorData = await response.json();
            mostrarNotificacion(errorData.message || 'Error al cambiar el estado', 'error');
        }
    } catch (error) {
        console.error('Error al cambiar estado:', error);
        mostrarNotificacion('Error al cambiar el estado', 'error');
    }
}

// Función para cerrar modal
function cerrarModal() {
    document.getElementById('modalDescuento').classList.add('hidden');
}

// Función para cerrar modal de eliminar
function cerrarModalEliminar() {
    document.getElementById('modalEliminar').classList.add('hidden');
    descuentoParaEliminar = null;
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

// Función para mostrar notificaciones
function mostrarNotificacion(mensaje, tipo = 'info') {
    // Crear elemento de notificación
    const notificacion = document.createElement('div');
    notificacion.className = `fixed top-4 right-4 z-50 max-w-sm w-full p-4 rounded-lg shadow-lg transform transition-all duration-300 translate-x-full`;
    
    // Determinar colores según el tipo
    const colores = {
        success: 'bg-green-500 text-white',
        error: 'bg-red-500 text-white',
        warning: 'bg-yellow-500 text-white',
        info: 'bg-blue-500 text-white'
    };
    
    // Determinar iconos según el tipo
    const iconos = {
        success: 'fas fa-check-circle',
        error: 'fas fa-exclamation-circle',
        warning: 'fas fa-exclamation-triangle',
        info: 'fas fa-info-circle'
    };
    
    notificacion.className += ` ${colores[tipo] || colores.info}`;
    
    notificacion.innerHTML = `
        <div class="flex items-center">
            <i class="${iconos[tipo] || iconos.info} mr-3"></i>
            <span class="flex-1">${mensaje}</span>
            <button onclick="this.parentElement.parentElement.remove()" class="ml-3 text-white hover:text-gray-200">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;
    
    // Agregar al DOM
    document.body.appendChild(notificacion);
    
    // Animar entrada
    setTimeout(() => {
        notificacion.classList.remove('translate-x-full');
    }, 100);
    
    // Auto-remover después de 5 segundos
    setTimeout(() => {
        if (notificacion.parentElement) {
            notificacion.classList.add('translate-x-full');
            setTimeout(() => {
                if (notificacion.parentElement) {
                    notificacion.remove();
                }
            }, 300);
        }
    }, 5000);
}

// Función para exportar datos (opcional)
function exportarDatos() {
    const datosExportar = descuentos.map(descuento => ({
        'Empresa': descuento.nombre_empresa,
        'Categoría': descuento.categoria,
        'Descuento (%)': descuento.porcentaje_descuento,
        'Descripción': descuento.descripcion || '',
        'Teléfono': descuento.telefono || '',
        'Estado': descuento.activo == 1 ? 'Activo' : 'Inactivo',
        'Fecha Creación': formatearFecha(descuento.fecha_creacion)
    }));
    
    const csv = convertirACSV(datosExportar);
    descargarCSV(csv, 'descuentos_claut.csv');
}

// Función auxiliar para convertir a CSV
function convertirACSV(datos) {
    if (datos.length === 0) return '';
    
    const headers = Object.keys(datos[0]);
    const csvHeaders = headers.join(',');
    
    const csvRows = datos.map(row => 
        headers.map(header => {
            const value = row[header];
            // Escapar valores que contengan comas o comillas
            if (typeof value === 'string' && (value.includes(',') || value.includes('"'))) {
                return `"${value.replace(/"/g, '""')}"`;
            }
            return value;
        }).join(',')
    );
    
    return [csvHeaders, ...csvRows].join('\n');
}

// Función auxiliar para descargar CSV
function descargarCSV(csvContent, filename) {
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    
    if (link.download !== undefined) {
        const url = URL.createObjectURL(blob);
        link.setAttribute('href', url);
        link.setAttribute('download', filename);
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
}

// Cerrar modales al hacer clic fuera de ellos
document.addEventListener('click', function(event) {
    const modalDescuento = document.getElementById('modalDescuento');
    const modalEliminar = document.getElementById('modalEliminar');
    
    if (event.target === modalDescuento) {
        cerrarModal();
    }
    
    if (event.target === modalEliminar) {
        cerrarModalEliminar();
    }
});

// Cerrar modales con la tecla Escape
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        cerrarModal();
        cerrarModalEliminar();
    }
});
