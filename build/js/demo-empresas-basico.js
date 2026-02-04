// demo-empresas-basico.js - Versión funcional básica del CRUD

// ===== VARIABLES GLOBALES =====
let currentPage = 1;
let totalPages = 1;
let empresas = [];

// ===== DATOS DE PRUEBA =====
const empresasDePrueba = [
    {
        id: 1,
        nombre_empresa: "TechCorp Solutions",
        categoria: "Tecnología",
        email: "contacto@techcorp.com",
        telefono: "+52 55 1234-5678",
        sitio_web: "https://techcorp.com",
        logo_url: "",
        direccion: "Av. Reforma 123, CDMX",
        descripcion: "Empresa líder en soluciones tecnológicas",
        beneficios: "20% descuento en servicios de TI",
        descuento: 20,
        activo: 1,
        destacado: 1,
        fecha_inicio_convenio: "2024-01-01",
        fecha_fin_convenio: "2024-12-31"
    },
    {
        id: 2,
        nombre_empresa: "AutoParts México",
        categoria: "Automotriz",
        email: "ventas@autoparts.mx",
        telefono: "+52 55 9876-5432",
        sitio_web: "https://autoparts.mx",
        logo_url: "",
        direccion: "Industrial Norte 456, Guadalajara",
        descripcion: "Distribuidora de autopartes originales",
        beneficios: "15% descuento en refacciones",
        descuento: 15,
        activo: 1,
        destacado: 0,
        fecha_inicio_convenio: "2024-02-01",
        fecha_fin_convenio: "2025-01-31"
    },
    {
        id: 3,
        nombre_empresa: "LogiTransport",
        categoria: "Logística",
        email: "info@logitransport.com",
        telefono: "+52 33 5555-1234",
        sitio_web: "",
        logo_url: "",
        direccion: "Zona Industrial 789, Monterrey",
        descripcion: "Servicios de transporte y logística",
        beneficios: "10% descuento en envíos",
        descuento: 10,
        activo: 0,
        destacado: 0,
        fecha_inicio_convenio: "2024-03-01",
        fecha_fin_convenio: "2024-11-30"
    }
];

// Cargar datos de prueba al localStorage si no existen
if (!localStorage.getItem('empresas')) {
    localStorage.setItem('empresas', JSON.stringify(empresasDePrueba));
}

// ===== FUNCIONES BÁSICAS =====
function abrirModalEmpresa(empresaId = null) {
    const modal = document.getElementById('modalEmpresa');
    const form = document.getElementById('formEmpresa');
    const title = document.getElementById('modalTitle');
    const submitBtn = document.getElementById('submitBtn');
    
    form.reset();
    document.getElementById('empresaId').value = '';
    
    if (empresaId) {
        const empresa = empresas.find(e => e.id == empresaId);
        if (empresa) {
            title.textContent = 'Editar Empresa';
            submitBtn.innerHTML = '<i class="fas fa-save mr-2"></i>Actualizar Empresa';
            llenarFormulario(empresa);
        }
    } else {
        title.textContent = 'Nueva Empresa';
        submitBtn.innerHTML = '<i class="fas fa-save mr-2"></i>Guardar Empresa';
        document.getElementById('activo').checked = true;
    }
    
    modal.classList.remove('hidden');
}

function cerrarModalEmpresa() {
    document.getElementById('modalEmpresa').classList.add('hidden');
}

function llenarFormulario(empresa) {
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
    document.getElementById('descuento').value = empresa.descuento || '';
    document.getElementById('fecha_inicio_convenio').value = empresa.fecha_inicio_convenio || '';
    document.getElementById('fecha_fin_convenio').value = empresa.fecha_fin_convenio || '';
    document.getElementById('activo').checked = empresa.activo == 1;
    document.getElementById('destacado').checked = empresa.destacado == 1;
}

function cargarEmpresas() {
    try {
        const data = localStorage.getItem('empresas');
        empresas = data ? JSON.parse(data) : [];
        mostrarEmpresas(empresas);
        actualizarEstadisticas();
        actualizarIndicadorConexion(true);
    } catch (error) {
        console.error('Error al cargar empresas:', error);
        mostrarErrorTabla('Error al cargar los datos');
        actualizarIndicadorConexion(false);
    }
}

function mostrarEmpresas(empresasList) {
    const tbody = document.getElementById('empresasTableBody');
    
    if (empresasList.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="6" class="px-6 py-12 text-center">
                    <div class="flex flex-col items-center">
                        <i class="fas fa-search text-4xl text-gray-300 mb-4"></i>
                        <p class="text-lg font-medium text-gray-500 mb-2">No se encontraron empresas</p>
                        <p class="text-sm text-gray-400">Agrega una nueva empresa para comenzar</p>
                        <button onclick="abrirModalEmpresa()" class="mt-4 px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors">
                            <i class="fas fa-plus mr-2"></i>Agregar Empresa
                        </button>
                    </div>
                </td>
            </tr>
        `;
        return;
    }
    
    tbody.innerHTML = empresasList.map(empresa => `
        <tr class="hover:bg-gray-50 transition-colors">
            <td class="px-6 py-4">
                <div class="flex items-center">
                    ${empresa.logo_url ? 
                        `<img src="${empresa.logo_url}" alt="Logo" class="w-10 h-10 rounded-lg object-cover mr-3" onerror="this.style.display='none'">` : 
                        `<div class="w-10 h-10 rounded-lg bg-gray-200 flex items-center justify-center mr-3">
                            <i class="fas fa-building text-gray-500"></i>
                        </div>`
                    }
                    <div>
                        <div class="font-medium text-gray-900">${escapeHtml(empresa.nombre_empresa)}</div>
                        ${empresa.sitio_web ? 
                            `<a href="${empresa.sitio_web}" target="_blank" class="text-sm text-blue-600 hover:underline">
                                <i class="fas fa-external-link-alt mr-1"></i>Sitio web
                            </a>` : 
                            `<span class="text-sm text-gray-500">Sin sitio web</span>`
                        }
                    </div>
                </div>
            </td>
            <td class="px-6 py-4">
                ${empresa.categoria ? 
                    `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                        ${escapeHtml(empresa.categoria)}
                    </span>` : 
                    `<span class="text-gray-400 text-sm">Sin categoría</span>`
                }
            </td>
            <td class="px-6 py-4">
                <div class="text-sm">
                    ${empresa.email ? 
                        `<div class="text-gray-900 mb-1">
                            <i class="fas fa-envelope mr-1 text-gray-400"></i>
                            <a href="mailto:${empresa.email}" class="hover:text-blue-600">${escapeHtml(empresa.email)}</a>
                        </div>` : ''
                    }
                    ${empresa.telefono ? 
                        `<div class="text-gray-900">
                            <i class="fas fa-phone mr-1 text-gray-400"></i>
                            <a href="tel:${empresa.telefono}" class="hover:text-blue-600">${escapeHtml(empresa.telefono)}</a>
                        </div>` : ''
                    }
                    ${!empresa.email && !empresa.telefono ? 
                        `<span class="text-gray-400 text-sm">Sin contacto</span>` : ''
                    }
                </div>
            </td>
            <td class="px-6 py-4 text-center">
                ${empresa.descuento && empresa.descuento > 0 ? 
                    `<span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                        <i class="fas fa-percentage mr-1"></i>${empresa.descuento}%
                    </span>` : 
                    `<span class="text-gray-400 text-sm">Sin descuento</span>`
                }
            </td>
            <td class="px-6 py-4 text-center">
                <div class="flex flex-col items-center space-y-1">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${
                        empresa.activo == 1 ? 'badge-activo' : 'badge-inactivo'
                    }">
                        <i class="fas fa-circle mr-1 text-xs"></i>
                        ${empresa.activo == 1 ? 'Activo' : 'Inactivo'}
                    </span>
                    ${empresa.destacado == 1 ? 
                        `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium badge-destacado">
                            <i class="fas fa-star mr-1"></i>Destacado
                        </span>` : ''
                    }
                </div>
            </td>
            <td class="px-6 py-4 text-center">
                <div class="flex justify-center space-x-2">
                    <button onclick="verDetalleEmpresa(${empresa.id})" 
                            class="text-blue-600 hover:text-blue-900 transition-colors p-1 rounded hover:bg-blue-50" 
                            title="Ver detalles">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button onclick="abrirModalEmpresa(${empresa.id})" 
                            class="text-indigo-600 hover:text-indigo-900 transition-colors p-1 rounded hover:bg-indigo-50" 
                            title="Editar">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button onclick="toggleEstadoEmpresa(${empresa.id}, ${empresa.activo})" 
                            class="text-yellow-600 hover:text-yellow-900 transition-colors p-1 rounded hover:bg-yellow-50" 
                            title="${empresa.activo == 1 ? 'Desactivar' : 'Activar'}">
                        <i class="fas fa-${empresa.activo == 1 ? 'pause' : 'play'}"></i>
                    </button>
                    <button onclick="eliminarEmpresa(${empresa.id}, '${empresa.nombre_empresa.replace(/'/g, "\\'")}')" 
                            class="text-red-600 hover:text-red-900 transition-colors p-1 rounded hover:bg-red-50" 
                            title="Eliminar">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
}

function mostrarErrorTabla(mensaje) {
    const tbody = document.getElementById('empresasTableBody');
    tbody.innerHTML = `
        <tr>
            <td colspan="6" class="px-6 py-12 text-center">
                <div class="flex flex-col items-center">
                    <i class="fas fa-exclamation-triangle text-4xl text-red-300 mb-4"></i>
                    <p class="text-lg font-medium text-red-600 mb-2">Error al cargar empresas</p>
                    <p class="text-sm text-gray-500 mb-4">${escapeHtml(mensaje)}</p>
                    <button onclick="cargarEmpresas()" 
                            class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors">
                        <i class="fas fa-refresh mr-2"></i>Reintentar
                    </button>
                </div>
            </td>
        </tr>
    `;
}

function actualizarEstadisticas() {
    const total = empresas.length;
    const activas = empresas.filter(e => e.activo == 1).length;
    const destacadas = empresas.filter(e => e.destacado == 1).length;
    const conDescuentos = empresas.filter(e => e.descuento && e.descuento > 0).length;
    
    document.getElementById('statTotal').textContent = total;
    document.getElementById('statActivas').textContent = activas;
    document.getElementById('statDestacadas').textContent = destacadas;
    document.getElementById('statDescuentos').textContent = conDescuentos;
}

function actualizarIndicadorConexion(conectado) {
    const indicator = document.getElementById('connectionStatus');
    
    if (conectado) {
        indicator.className = 'connection-indicator connected';
        indicator.innerHTML = '<i class="fas fa-check-circle mr-2"></i>Modo Demo';
    } else {
        indicator.className = 'connection-indicator disconnected';
        indicator.innerHTML = '<i class="fas fa-exclamation-triangle mr-2"></i>Error';
    }
}

// ===== FUNCIONES CRUD =====
function guardarEmpresa(formData) {
    const empresaId = document.getElementById('empresaId').value;
    
    if (empresaId) {
        const index = empresas.findIndex(e => e.id == empresaId);
        if (index !== -1) {
            empresas[index] = { ...empresas[index], ...formData, id: parseInt(empresaId) };
            Swal.fire({
                icon: 'success',
                title: '¡Éxito!',
                text: 'Empresa actualizada correctamente',
                timer: 2000,
                showConfirmButton: false
            });
        }
    } else {
        const nuevoId = Math.max(...empresas.map(e => e.id), 0) + 1;
        const nuevaEmpresa = { ...formData, id: nuevoId };
        empresas.push(nuevaEmpresa);
        
        Swal.fire({
            icon: 'success',
            title: '¡Éxito!',
            text: 'Empresa creada correctamente',
            timer: 2000,
            showConfirmButton: false
        });
    }
    
    localStorage.setItem('empresas', JSON.stringify(empresas));
    cerrarModalEmpresa();
    cargarEmpresas();
}

async function eliminarEmpresa(empresaId, nombreEmpresa) {
    const result = await Swal.fire({
        title: '¿Eliminar empresa?',
        html: `
            <div class="text-left">
                <p class="mb-4">¿Estás seguro de que quieres eliminar la empresa:</p>
                <p class="font-bold text-lg mb-4">"${escapeHtml(nombreEmpresa)}"</p>
                <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-triangle text-red-600 mr-2"></i>
                        <span class="text-red-800 font-semibold">¡Advertencia!</span>
                    </div>
                    <ul class="text-red-700 text-sm mt-2 ml-6">
                        <li>• Esta acción no se puede deshacer</li>
                        <li>• Se perderán todos los datos de la empresa</li>
                        <li>• Se eliminarán todos los convenios asociados</li>
                    </ul>
                </div>
                <p class="text-sm text-gray-600">Para confirmar, escribe <span class="font-mono bg-gray-100 px-2 py-1 rounded">ELIMINAR</span>:</p>
            </div>
        `,
        input: 'text',
        inputPlaceholder: 'Escribe ELIMINAR para confirmar',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Eliminar empresa',
        cancelButtonText: 'Cancelar',
        inputValidator: (value) => {
            if (value !== 'ELIMINAR') {
                return 'Debes escribir exactamente "ELIMINAR" para confirmar';
            }
        }
    });
    
    if (result.isConfirmed) {
        empresas = empresas.filter(e => e.id != empresaId);
        localStorage.setItem('empresas', JSON.stringify(empresas));
        
        Swal.fire({
            icon: 'success',
            title: '¡Empresa eliminada!',
            text: 'La empresa ha sido eliminada correctamente',
            timer: 3000,
            showConfirmButton: false
        });
        
        cargarEmpresas();
    }
}

async function toggleEstadoEmpresa(empresaId, estadoActual) {
    const nuevoEstado = estadoActual == 1 ? 0 : 1;
    const textoEstado = nuevoEstado == 1 ? 'activar' : 'desactivar';
    
    const result = await Swal.fire({
        title: `¿${textoEstado.charAt(0).toUpperCase() + textoEstado.slice(1)} empresa?`,
        text: `¿Estás seguro de que quieres ${textoEstado} esta empresa?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: nuevoEstado == 1 ? '#10b981' : '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: `Sí, ${textoEstado}`,
        cancelButtonText: 'Cancelar'
    });
    
    if (result.isConfirmed) {
        const index = empresas.findIndex(e => e.id == empresaId);
        if (index !== -1) {
            empresas[index].activo = nuevoEstado;
            localStorage.setItem('empresas', JSON.stringify(empresas));
            
            Swal.fire({
                icon: 'success',
                title: '¡Éxito!',
                text: `Empresa ${nuevoEstado == 1 ? 'activada' : 'desactivada'} correctamente`,
                timer: 2000,
                showConfirmButton: false
            });
            
            cargarEmpresas();
        }
    }
}

function verDetalleEmpresa(empresaId) {
    const empresa = empresas.find(e => e.id == empresaId);
    if (!empresa) return;
    
    Swal.fire({
        title: empresa.nombre_empresa,
        html: `
            <div class="text-left space-y-4 max-h-96 overflow-y-auto">
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div><strong>Categoría:</strong> ${empresa.categoria || 'No especificada'}</div>
                    <div><strong>Estado:</strong> 
                        <span class="px-2 py-1 rounded text-xs ${empresa.activo == 1 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">
                            ${empresa.activo == 1 ? 'Activo' : 'Inactivo'}
                        </span>
                    </div>
                    <div><strong>Email:</strong> ${empresa.email || 'No especificado'}</div>
                    <div><strong>Teléfono:</strong> ${empresa.telefono || 'No especificado'}</div>
                    <div><strong>Descuento:</strong> ${empresa.descuento ? empresa.descuento + '%' : 'Sin descuento'}</div>
                    <div><strong>Destacado:</strong> ${empresa.destacado == 1 ? 'Sí' : 'No'}</div>
                </div>
                
                ${empresa.direccion ? `<div><strong>Dirección:</strong> ${escapeHtml(empresa.direccion)}</div>` : ''}
                ${empresa.descripcion ? `<div><strong>Descripción:</strong> ${escapeHtml(empresa.descripcion)}</div>` : ''}
                ${empresa.beneficios ? `<div><strong>Beneficios:</strong> ${escapeHtml(empresa.beneficios)}</div>` : ''}
                ${empresa.sitio_web ? `<div><strong>Sitio Web:</strong> <a href="${empresa.sitio_web}" target="_blank" class="text-blue-600 hover:underline">${escapeHtml(empresa.sitio_web)}</a></div>` : ''}
            </div>
        `,
        width: '600px',
        confirmButtonText: 'Cerrar',
        confirmButtonColor: '#6b7280'
    });
}

// ===== FUNCIONES DE FILTROS =====
function aplicarFiltros() {
    const search = document.getElementById('searchInput').value.toLowerCase().trim();
    const categoria = document.getElementById('filterCategoria').value;
    const estado = document.getElementById('filterEstado').value;
    const destacado = document.getElementById('filterDestacado').value;
    
    let empresasFiltradas = [...empresas];
    
    if (search) {
        empresasFiltradas = empresasFiltradas.filter(empresa => 
            empresa.nombre_empresa.toLowerCase().includes(search) ||
            (empresa.categoria && empresa.categoria.toLowerCase().includes(search)) ||
            (empresa.email && empresa.email.toLowerCase().includes(search))
        );
    }
    
    if (categoria) {
        empresasFiltradas = empresasFiltradas.filter(empresa => empresa.categoria === categoria);
    }
    
    if (estado !== '') {
        empresasFiltradas = empresasFiltradas.filter(empresa => empresa.activo == estado);
    }
    
    if (destacado !== '') {
        empresasFiltradas = empresasFiltradas.filter(empresa => empresa.destacado == destacado);
    }
    
    mostrarEmpresas(empresasFiltradas);
}

function limpiarFiltros() {
    document.getElementById('searchInput').value = '';
    document.getElementById('filterCategoria').value = '';
    document.getElementById('filterEstado').value = '';
    document.getElementById('filterDestacado').value = '';
    mostrarEmpresas(empresas);
}

// ===== FUNCIONES AUXILIARES =====
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function cambiarPagina(page) {
    console.log('Función de paginación - Página:', page);
}

// ===== INICIALIZACIÓN =====
document.addEventListener('DOMContentLoaded', function() {
    cargarEmpresas();
    
    const form = document.getElementById('formEmpresa');
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const data = {};
        
        for (let [key, value] of formData.entries()) {
            data[key] = value;
        }
        
        data.activo = document.getElementById('activo').checked ? 1 : 0;
        data.destacado = document.getElementById('destacado').checked ? 1 : 0;
        
        if (!data.nombre_empresa.trim()) {
            Swal.fire({
                icon: 'warning',
                title: 'Campo requerido',
                text: 'El nombre de la empresa es obligatorio'
            });
            return;
        }
        
        guardarEmpresa(data);
    });
    
    let searchTimeout;
    document.getElementById('searchInput').addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            aplicarFiltros();
        }, 300);
    });
    
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            cerrarModalEmpresa();
        }
    });
    
    document.getElementById('modalEmpresa').addEventListener('click', function(e) {
        if (e.target === this) {
            cerrarModalEmpresa();
        }
    });
});

// Hacer funciones globales
window.abrirModalEmpresa = abrirModalEmpresa;
window.cerrarModalEmpresa = cerrarModalEmpresa;
window.cargarEmpresas = cargarEmpresas;
window.aplicarFiltros = aplicarFiltros;
window.limpiarFiltros = limpiarFiltros;
window.eliminarEmpresa = eliminarEmpresa;
window.toggleEstadoEmpresa = toggleEstadoEmpresa;
window.verDetalleEmpresa = verDetalleEmpresa;
window.cambiarPagina = cambiarPagina;