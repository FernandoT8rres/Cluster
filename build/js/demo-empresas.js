// Variables globales para el demo de empresas
let empresas = [];
let filteredEmpresas = [];
let currentEmpresaId = null;

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

// Cargar empresas desde la API
async function loadEmpresas() {
    try {
        console.log('Iniciando carga de empresas...');
        
        const response = await fetch(`${API_BASE}/empresas_convenio.php`, {
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
        
        // Guardar las empresas
        empresas = data.data || [];
        filteredEmpresas = [...empresas];
        
        console.log(`${empresas.length} empresas cargadas exitosamente`);
        
        // Actualizar la interfaz
        updateEmpresasTable();
        updateStats();
        
        showNotification(`Se cargaron ${empresas.length} empresas correctamente`, 'success');
        
    } catch (error) {
        console.error('Error completo al cargar empresas:', error);
        
        // Mostrar mensaje de error al usuario
        showNotification(`Error: ${error.message}`, 'error');
        
        // Mostrar estado de error en la tabla
        showErrorState(error.message);
    }
}

// Actualizar tabla de empresas
function updateEmpresasTable() {
    const tbody = document.getElementById('empresasTableBody');
    const loadingState = document.getElementById('loadingState');
    const emptyState = document.getElementById('emptyState');
    
    if (!tbody) return;
    
    // Ocultar estados de loading y empty
    if (loadingState) loadingState.classList.add('hidden');
    if (emptyState) emptyState.classList.add('hidden');
    
    // Limpiar tabla
    tbody.innerHTML = '';
    
    if (filteredEmpresas.length === 0) {
        if (emptyState) emptyState.classList.remove('hidden');
        return;
    }
    
    // Generar filas
    filteredEmpresas.forEach(empresa => {
        const row = createEmpresaRow(empresa);
        tbody.appendChild(row);
    });
}

// Crear fila de empresa
function createEmpresaRow(empresa) {
    const row = document.createElement('tr');
    row.className = 'hover:bg-gray-50';
    
    const logoUrl = empresa.logo_url || 
        `https://ui-avatars.com/api/?name=${encodeURIComponent(empresa.nombre_empresa)}&background=0ea5e9&color=fff&size=40`;
    
    const estadoBadge = empresa.activo == 1
        ? '<span class="inline-flex px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full">Activo</span>'
        : '<span class="inline-flex px-2 py-1 text-xs font-medium bg-red-100 text-red-800 rounded-full">Inactivo</span>';
    
    const destacadoBadge = empresa.destacado == 1
        ? '<i class="fas fa-star text-yellow-500 ml-2" title="Empresa destacada"></i>'
        : '';
    
    row.innerHTML = `
        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${empresa.id}</td>
        <td class="px-6 py-4 whitespace-nowrap">
            <div class="flex items-center">
                <img class="h-10 w-10 rounded-lg company-logo" src="${logoUrl}" alt="${empresa.nombre_empresa}"
                     onerror="this.src='https://ui-avatars.com/api/?name=${encodeURIComponent(empresa.nombre_empresa)}&background=0ea5e9&color=fff&size=40'">
                <div class="ml-4">
                    <div class="text-sm font-medium text-gray-900">${empresa.nombre_empresa}</div>
                    <div class="text-sm text-gray-500">${empresa.descripcion || ''}</div>
                </div>
            </div>
        </td>
        <td class="px-6 py-4 whitespace-nowrap">
            <span class="inline-flex px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded-full">
                ${empresa.categoria || 'Sin categoría'}
            </span>
        </td>
        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
            ${empresa.email ? `<div class="mb-1"><i class="fas fa-envelope text-gray-400 mr-1"></i> ${empresa.email}</div>` : ''}
            ${empresa.telefono ? `<div><i class="fas fa-phone text-gray-400 mr-1"></i> ${empresa.telefono}</div>` : ''}
        </td>
        <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-900">
            ${empresa.descuento > 0 ? `${empresa.descuento}%` : '-'}
        </td>
        <td class="px-6 py-4 whitespace-nowrap text-center">
            ${estadoBadge}
            ${destacadoBadge}
        </td>
        <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
            <div class="flex justify-center space-x-2">
                <button onclick="editEmpresa(${empresa.id})" 
                        class="text-indigo-600 hover:text-indigo-900 transition" title="Editar">
                    <i class="fas fa-edit"></i>
                </button>
                <button onclick="deleteEmpresa(${empresa.id})" 
                        class="text-red-600 hover:text-red-900 transition" title="Eliminar">
                    <i class="fas fa-trash"></i>
                </button>
                ${empresa.sitio_web ? `
                    <a href="${empresa.sitio_web}" target="_blank" 
                       class="text-green-600 hover:text-green-900 transition" title="Visitar sitio web">
                        <i class="fas fa-external-link-alt"></i>
                    </a>
                ` : ''}
            </div>
        </td>
    `;
    
    return row;
}

// Actualizar estadísticas
function updateStats() {
    const totalElement = document.getElementById('totalEmpresas');
    const activasElement = document.getElementById('activasEmpresas');
    const destacadasElement = document.getElementById('destacadasEmpresas');
    const descuentosElement = document.getElementById('descuentosEmpresas');
    
    const total = empresas.length;
    const activas = empresas.filter(e => e.activo == 1).length;
    const destacadas = empresas.filter(e => e.destacado == 1).length;
    const conDescuentos = empresas.filter(e => e.descuento > 0).length;
    
    if (totalElement) totalElement.textContent = total;
    if (activasElement) activasElement.textContent = activas;
    if (destacadasElement) destacadasElement.textContent = destacadas;
    if (descuentosElement) descuentosElement.textContent = conDescuentos;
}

// Mostrar estado de error
function showErrorState(errorMessage) {
    const tbody = document.getElementById('empresasTableBody');
    const loadingState = document.getElementById('loadingState');
    const emptyState = document.getElementById('emptyState');
    
    if (loadingState) loadingState.classList.add('hidden');
    if (emptyState) emptyState.classList.add('hidden');
    
    if (tbody) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7" class="px-6 py-8 text-center">
                    <i class="fas fa-exclamation-triangle text-4xl text-red-400 mb-4"></i>
                    <p class="text-red-600 font-medium mb-2">Error al cargar empresas</p>
                    <p class="text-gray-600 text-sm mb-4">${errorMessage}</p>
                    <button onclick="reloadEmpresas()" 
                            class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition">
                        <i class="fas fa-sync-alt mr-2"></i>Reintentar
                    </button>
                </td>
            </tr>
        `;
    }
}

// Mostrar modal crear empresa
function showCreateModal() {
    currentEmpresaId = null;
    document.getElementById('modalTitle').textContent = 'Crear Empresa';
    document.getElementById('empresaForm').reset();
    document.getElementById('empresaId').value = '';
    document.getElementById('activo').checked = true;
    document.getElementById('destacado').checked = false;
    document.getElementById('empresaModal').classList.remove('hidden');
}

// Editar empresa
async function editEmpresa(id) {
    const empresa = empresas.find(e => e.id == id);
    if (!empresa) {
        showNotification('Empresa no encontrada', 'error');
        return;
    }
    
    currentEmpresaId = id;
    document.getElementById('modalTitle').textContent = 'Editar Empresa';
    document.getElementById('empresaId').value = empresa.id;
    document.getElementById('nombre_empresa').value = empresa.nombre_empresa || '';
    document.getElementById('categoria').value = empresa.categoria || '';
    document.getElementById('email').value = empresa.email || '';
    document.getElementById('telefono').value = empresa.telefono || '';
    document.getElementById('sitio_web').value = empresa.sitio_web || '';
    document.getElementById('logo_url').value = empresa.logo_url || '';
    document.getElementById('direccion').value = empresa.direccion || '';
    document.getElementById('descripcion').value = empresa.descripcion || '';
    document.getElementById('beneficios').value = empresa.beneficios || '';
    document.getElementById('descuento').value = empresa.descuento || 0;
    document.getElementById('fecha_inicio_convenio').value = empresa.fecha_inicio_convenio || '';
    document.getElementById('fecha_fin_convenio').value = empresa.fecha_fin_convenio || '';
    document.getElementById('activo').checked = empresa.activo == 1;
    document.getElementById('destacado').checked = empresa.destacado == 1;
    
    document.getElementById('empresaModal').classList.remove('hidden');
}

// Eliminar empresa
async function deleteEmpresa(id) {
    if (!confirm('¿Estás seguro de que quieres eliminar esta empresa?')) {
        return;
    }
    
    try {
        const response = await fetch(`${API_BASE}/empresas_convenio.php`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ id: id })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showNotification('Empresa eliminada correctamente', 'success');
            await reloadEmpresas();
        } else {
            throw new Error(data.message || 'Error al eliminar empresa');
        }
    } catch (error) {
        console.error('Error al eliminar empresa:', error);
        showNotification(`Error al eliminar empresa: ${error.message}`, 'error');
    }
}

// Cerrar modal
function closeModal() {
    document.getElementById('empresaModal').classList.add('hidden');
    currentEmpresaId = null;
}

// Recargar empresas
async function reloadEmpresas() {
    const loadingState = document.getElementById('loadingState');
    if (loadingState) loadingState.classList.remove('hidden');
    
    await loadEmpresas();
}

// Manejar envío del formulario
async function handleEmpresaSubmit(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    
    // Convertir FormData a objeto
    const data = {
        nombre_empresa: formData.get('nombre_empresa'),
        categoria: formData.get('categoria'),
        email: formData.get('email'),
        telefono: formData.get('telefono'),
        sitio_web: formData.get('sitio_web'),
        logo_url: formData.get('logo_url'),
        direccion: formData.get('direccion'),
        descripcion: formData.get('descripcion'),
        beneficios: formData.get('beneficios'),
        descuento: parseFloat(formData.get('descuento')) || 0,
        fecha_inicio_convenio: formData.get('fecha_inicio_convenio') || null,
        fecha_fin_convenio: formData.get('fecha_fin_convenio') || null,
        activo: formData.has('activo') ? 1 : 0,
        destacado: formData.has('destacado') ? 1 : 0
    };
    
    try {
        let response;
        if (currentEmpresaId) {
            // Actualizar empresa existente
            data.id = currentEmpresaId;
            response = await fetch(`${API_BASE}/empresas_convenio.php`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            });
        } else {
            // Crear nueva empresa
            response = await fetch(`${API_BASE}/empresas_convenio.php`, {
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
                currentEmpresaId ? 'Empresa actualizada correctamente' : 'Empresa creada correctamente', 
                'success'
            );
            closeModal();
            await reloadEmpresas();
        } else {
            throw new Error(result.message || 'Error al guardar empresa');
        }
    } catch (error) {
        console.error('Error al guardar empresa:', error);
        showNotification(`Error al guardar empresa: ${error.message}`, 'error');
    }
}

// Filtros
function applyFilters() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    const categoria = document.getElementById('filterCategoria').value;
    const activo = document.getElementById('filterActivo').value;
    const destacado = document.getElementById('filterDestacado').value;
    
    filteredEmpresas = empresas.filter(empresa => {
        const matchesSearch = !searchTerm || 
            empresa.nombre_empresa.toLowerCase().includes(searchTerm) ||
            (empresa.descripcion && empresa.descripcion.toLowerCase().includes(searchTerm));
        
        const matchesCategoria = !categoria || empresa.categoria === categoria;
        const matchesActivo = activo === '' || empresa.activo.toString() === activo;
        const matchesDestacado = destacado === '' || empresa.destacado.toString() === destacado;
        
        return matchesSearch && matchesCategoria && matchesActivo && matchesDestacado;
    });
    
    updateEmpresasTable();
}

// Panel de reparación de BD (funciones básicas)
function toggleRepairPanel() {
    const panel = document.getElementById('repairPanel');
    const btn = document.getElementById('repairToggleBtn');
    
    if (panel.classList.contains('hidden')) {
        panel.classList.remove('hidden');
        btn.innerHTML = '<i class="fas fa-chevron-up mr-1"></i>Ocultar Herramientas';
    } else {
        panel.classList.add('hidden');
        btn.innerHTML = '<i class="fas fa-chevron-down mr-1"></i>Mostrar Herramientas';
    }
}

function checkDatabaseIntegrity() {
    addRepairLog('Verificando integridad de la base de datos...');
    showNotification('Función de verificación en desarrollo', 'info');
}

function repairDatabaseStructure() {
    addRepairLog('Reparando estructura de la base de datos...');
    showNotification('Función de reparación en desarrollo', 'info');
}

function cleanCorruptData() {
    addRepairLog('Limpiando datos corruptos...');
    showNotification('Función de limpieza en desarrollo', 'info');
}

function optimizeDatabase() {
    addRepairLog('Optimizando base de datos...');
    showNotification('Función de optimización en desarrollo', 'info');
}

function createBackup() {
    addRepairLog('Creando respaldo...');
    showNotification('Función de respaldo en desarrollo', 'info');
}

function restoreBackup() {
    addRepairLog('Restaurando respaldo...');
    showNotification('Función de restauración en desarrollo', 'info');
}

function addRepairLog(message) {
    const log = document.getElementById('repairLog');
    const timestamp = new Date().toLocaleTimeString();
    const logEntry = document.createElement('div');
    logEntry.className = 'text-sm text-gray-700 mb-1';
    logEntry.innerHTML = `<span class="text-gray-500">[${timestamp}]</span> ${message}`;
    
    if (log.children.length === 1 && log.children[0].textContent.includes('No hay operaciones')) {
        log.innerHTML = '';
    }
    
    log.appendChild(logEntry);
    log.scrollTop = log.scrollHeight;
}

// Inicialización cuando se carga la página
document.addEventListener('DOMContentLoaded', function() {
    console.log('Inicializando demo de empresas...');
    
    // Configurar event listeners
    const empresaForm = document.getElementById('empresaForm');
    if (empresaForm) {
        empresaForm.addEventListener('submit', handleEmpresaSubmit);
    }
    
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('input', applyFilters);
    }
    
    const filterCategoria = document.getElementById('filterCategoria');
    if (filterCategoria) {
        filterCategoria.addEventListener('change', applyFilters);
    }
    
    const filterActivo = document.getElementById('filterActivo');
    if (filterActivo) {
        filterActivo.addEventListener('change', applyFilters);
    }
    
    const filterDestacado = document.getElementById('filterDestacado');
    if (filterDestacado) {
        filterDestacado.addEventListener('change', applyFilters);
    }
    
    // Cerrar modal al hacer clic fuera
    const modal = document.getElementById('empresaModal');
    if (modal) {
        modal.addEventListener('click', function(event) {
            if (event.target === modal) {
                closeModal();
            }
        });
    }
    
    // Cargar empresas iniciales
    loadEmpresas();
});