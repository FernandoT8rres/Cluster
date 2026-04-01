/**
 * Administrador completo de empresas - demo_empresas.html
 * CRUD completo con visualización, creación, edición y exportación
 */

class AdminEmpresasManager {
    constructor() {
        this.empresas = [];
        this.usuarios = [];
        this.solicitudes = [];
        this.empresaEditando = null;
        this.enviandoFormulario = false;

        if (window.location.hostname === 'intranet.clústermetropolitano.mx' ||
            window.location.hostname === 'clústermetropolitano.mx') {
            this.apiUrl = 'https://intranet.clústermetropolitano.mx/build/api/empresas-simple.php';
            this.solicitudesApiUrl = 'https://intranet.clústermetropolitano.mx/build/api/solicitudes_empresa.php';
        } else {
            this.apiUrl = './api/empresas-simple.php';
            this.solicitudesApiUrl = './api/solicitudes_empresa.php';
        }

        this.apiUsuariosUrl = this.apiUrl.replace('/empresas-simple.php', '/admin/users.php');
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.cargarEmpresas();
        this.cargarUsuarios();
        this.cargarSolicitudes();

        // Refresco automático cada 30 segundos para mantener datos actualizados
        setInterval(() => {
            this.refrescarSilencioso();
            this.cargarSolicitudes(); // También refrescar solicitudes
        }, 30000);
    }

    setupEventListeners() {
        // Botón agregar empresa
        const btnAgregar = document.getElementById('btnAgregarEmpresa');
        if (btnAgregar) {
            btnAgregar.addEventListener('click', () => this.abrirModalCrear());
        }

        // Configurar eventos del modal una sola vez
        this.configurarEventosModal();

        // Búsqueda
        const busquedaInput = document.getElementById('searchInput');
        if (busquedaInput) {
            busquedaInput.addEventListener('input', (e) => this.filtrarEmpresas(e.target.value));
        }

        // Filtros
        const filtroEstado = document.getElementById('filtroEstado');
        if (filtroEstado) {
            filtroEstado.addEventListener('change', () => this.aplicarFiltros());
        }

        // Exportar
        const btnExportar = document.getElementById('btnExportar');
        if (btnExportar) {
            btnExportar.addEventListener('click', () => this.exportarEmpresas());
        }
    }

    async cargarEmpresas() {
        try {
            const url = `${this.apiUrl}?action=listar&t=${Date.now()}`;

            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'Cache-Control': 'no-cache'
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const responseText = await response.text();
            const data = JSON.parse(responseText);

            if (data.success) {
                this.empresas = data.data.empresas || [];
                this.renderizarTablaAdmin();
                this.actualizarEstadisticas();
            } else {
                throw new Error(data.message || 'Error en respuesta de API');
            }
        } catch (error) {
            console.error('Error cargando empresas:', error);
            this.mostrarError(`Error cargando empresas: ${error.message}`);

            // Mostrar información de debug en la tabla
            const tbody = document.getElementById('empresasTableBody');
            if (tbody) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center">
                            <div class="text-red-600">
                                <i class="fas fa-exclamation-triangle text-4xl mb-4"></i>
                                <h3 class="text-lg font-semibold mb-2">Error al cargar empresas</h3>
                                <p class="text-sm text-gray-600 mb-4">${error.message}</p>
                                <button onclick="adminEmpresas.cargarEmpresas()" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                                    Reintentar
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            }
        }
    }

    async cargarUsuarios() {
        try {
            const response = await fetch(this.apiUsuariosUrl);
            const data = await response.json();
            if (data.success) {
                this.usuarios = data.data || [];
                // Llenar el select
                const selectUsuario = document.getElementById('admin_usuario_id');
                if (selectUsuario) {
                    selectUsuario.innerHTML = '<option value="">Seleccionar Usuario Asignado...</option>';
                    this.usuarios.forEach(user => {
                        selectUsuario.innerHTML += `<option value="${user.id}">${user.nombre} ${user.apellidos || ''} (${user.email})</option>`;
                    });
                }
            }
        } catch (error) {
            console.error('❌ [ADMIN] Error cargando usuarios:', error);
        }
    }

    renderizarTablaAdmin(empresasList = null) {
        const tbody = document.getElementById('empresasTableBody');
        if (!tbody) {
            console.error('❌ [ADMIN] No se encontró elemento empresasTableBody');
            return;
        }

        const empresas = empresasList || this.empresas || [];

        if (empresas.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                        <i class="fas fa-building text-4xl mb-4 text-gray-300"></i>
                        <p class="text-lg font-medium">No hay empresas registradas</p>
                        <p class="text-sm">Comienza agregando una nueva empresa</p>
                    </td>
                </tr>
            `;
            return;
        }

        try {
            tbody.innerHTML = empresas.map(empresa => {
                // Normalizar la URL del logo removiendo el prefijo ./
                // Cache-busting solo para uploads locales (no afecta URLs externas)
                let logoUrl = empresa.logo_url ? empresa.logo_url.replace(/^\.\//, '') : this.generarLogoDefault(empresa.nombre);
                if (logoUrl && logoUrl.startsWith('uploads/')) {
                    logoUrl += (logoUrl.includes('?') ? '&' : '?') + 't=' + (empresa.updated_at ? new Date(empresa.updated_at).getTime() : Date.now());
                }

                return `
            <tr class="hover:bg-gray-50">
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center">
                        <img src="${logoUrl}" 
                             alt="${empresa.nombre}"
                             class="company-logo mr-3"
                             onerror="this.src='${this.generarLogoDefault(empresa.nombre)}'">
                        <div>
                            <div class="text-sm font-medium text-gray-900">${empresa.nombre}</div>
                            <div class="text-sm text-gray-500">${empresa.sector || 'Sin sector'}</div>
                        </div>
                    </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm text-gray-900">${empresa.email || 'No especificado'}</div>
                    <div class="text-sm text-gray-500">${empresa.telefono || 'No especificado'}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="px-2 py-1 text-xs rounded-full ${empresa.estado === 'activa'
                        ? 'bg-green-100 text-green-800'
                        : 'bg-yellow-100 text-yellow-800'
                    }">
                        ${empresa.estado === 'activa' ? 'Activa' : 'Inactiva'}
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    ${empresa.descuento_porcentaje ? empresa.descuento_porcentaje + '%' : 'Sin descuento'}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    ${empresa.sitio_web ?
                        `<a href="${empresa.sitio_web}" target="_blank" class="text-blue-600 hover:text-blue-800">
                            <i class="fas fa-external-link-alt"></i>
                        </a>` :
                        '<span class="text-gray-400">No especificado</span>'
                    }
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    ${this.formatearFecha(empresa.fecha_registro)}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm font-medium text-indigo-600">${empresa.admin_nombre || '<span class="text-gray-400 font-normal">Sin asignar</span>'}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                    <div class="flex space-x-2">
                        <button onclick="adminEmpresas.previsualizarEmpresa(${empresa.id})" 
                                class="text-indigo-600 hover:text-indigo-900" title="Vista Previa">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button onclick="adminEmpresas.editarEmpresa(${empresa.id})" 
                                class="text-blue-600 hover:text-blue-900" title="Editar">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button onclick="adminEmpresas.eliminarEmpresa(${empresa.id})" 
                                class="text-red-600 hover:text-red-900" title="Eliminar">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
                    `;
            }).join('');
        } catch (error) {
            console.error('❌ [ADMIN] Error renderizando tabla:', error);
            tbody.innerHTML = `
                    < tr >
                    <td colspan="7" class="px-6 py-12 text-center text-red-500">
                        <i class="fas fa-exclamation-triangle text-4xl mb-4"></i>
                        <p class="text-lg font-semibold">Error renderizando datos</p>
                        <p class="text-sm">${error.message}</p>
                    </td>
                </tr >
                    `;
        }
    }

    crearModalesAdmin() {
        // El modal ya existe en el HTML, solo configurar eventos
        const btnCerrar = document.getElementById('btnCerrarModal');
        const btnCancelar = document.getElementById('btnCancelar');
        const formEmpresa = document.getElementById('formEmpresa');

        // Remover event listeners existentes para evitar duplicación
        if (btnCerrar) {
            btnCerrar.replaceWith(btnCerrar.cloneNode(true));
            document.getElementById('btnCerrarModal').addEventListener('click', () => this.cerrarModal());
        }
        if (btnCancelar) {
            btnCancelar.replaceWith(btnCancelar.cloneNode(true));
            document.getElementById('btnCancelar').addEventListener('click', () => this.cerrarModal());
        }
        if (formEmpresa) {
            formEmpresa.replaceWith(formEmpresa.cloneNode(true));
            document.getElementById('formEmpresa').addEventListener('submit', (e) => this.guardarEmpresa(e));
        }

        // Preview de logo
        document.getElementById('logo_url').addEventListener('input', (e) => this.actualizarPreviewLogo(e.target.value));
        document.getElementById('logo_file').addEventListener('change', (e) => this.manejarArchivoLogo(e));

        // Modal de vista previa (simple para administrador)
        if (!document.getElementById('modalVistaPrevia')) {
            const modalPrevia = document.createElement('div');
            modalPrevia.id = 'modalVistaPrevia';
            modalPrevia.className = 'fixed inset-0 z-50 hidden items-center justify-center modal-backdrop bg-black bg-opacity-50';
            modalPrevia.innerHTML = `
                    < div class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto" >
                    <div class="flex items-center justify-between p-6 border-b">
                        <h3 id="previaEmpresaNombre" class="text-lg font-medium text-gray-900"></h3>
                        <button id="btnCerrarPrevia" class="text-gray-400 hover:text-gray-600">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>
                    <div id="previaContenido" class="p-6">
                        <!-- Contenido dinámico -->
                    </div>
                </div >
                    `;
            document.body.appendChild(modalPrevia);
            document.getElementById('btnCerrarPrevia').addEventListener('click', () => this.cerrarModalPrevia());
        }
    }

    configurarEventosModal() {
        // Configurar eventos del modal una sola vez para evitar duplicaciones
        const btnCerrar = document.getElementById('btnCerrarModal');
        const btnCancelar = document.getElementById('btnCancelar');
        const formEmpresa = document.getElementById('formEmpresa');

        if (btnCerrar && !btnCerrar.dataset.configured) {
            btnCerrar.addEventListener('click', () => this.cerrarModal());
            btnCerrar.dataset.configured = 'true';
        }

        if (btnCancelar && !btnCancelar.dataset.configured) {
            btnCancelar.addEventListener('click', () => this.cerrarModal());
            btnCancelar.dataset.configured = 'true';
        }

        if (formEmpresa && !formEmpresa.dataset.configured) {
            formEmpresa.addEventListener('submit', (e) => this.guardarEmpresa(e));
            formEmpresa.dataset.configured = 'true';
        }

        // Preview de logo
        const logoUrl = document.getElementById('logo_url');
        const logoFile = document.getElementById('logo_file');

        if (logoUrl && !logoUrl.dataset.configured) {
            logoUrl.addEventListener('input', (e) => this.actualizarPreviewLogo(e.target.value));
            logoUrl.dataset.configured = 'true';
        }

        if (logoFile && !logoFile.dataset.configured) {
            logoFile.addEventListener('change', (e) => this.manejarArchivoLogo(e));
            logoFile.dataset.configured = 'true';
        }

    }

    abrirModalCrear() {
        this.empresaEditando = null;

        const hiddenIdField = document.getElementById('empresa_id_hidden');
        if (hiddenIdField) hiddenIdField.value = '';

        document.getElementById('modalTitulo').textContent = 'Agregar Nueva Empresa';
        document.getElementById('btnGuardar').textContent = 'Crear Empresa';
        this.limpiarFormulario();
        this.mostrarModal();
    }

    editarEmpresa(id) {
        const empresa = this.empresas.find(e => e.id == id);
        if (!empresa) return;

        this.empresaEditando = empresa;

        let hiddenIdField = document.getElementById('empresa_id_hidden');
        if (!hiddenIdField) {
            hiddenIdField = document.createElement('input');
            hiddenIdField.type = 'hidden';
            hiddenIdField.id = 'empresa_id_hidden';
            hiddenIdField.name = 'empresa_id_hidden';
            document.getElementById('formEmpresa').appendChild(hiddenIdField);
        }
        hiddenIdField.value = empresa.id;

        document.getElementById('modalTitulo').textContent = 'Editar Empresa';
        document.getElementById('btnGuardar').textContent = 'Actualizar';

        this.llenarFormulario(empresa);
        this.mostrarModal();
    }

    llenarFormulario(empresa) {
        document.getElementById('nombre').value = empresa.nombre || '';
        document.getElementById('sector').value = empresa.sector || '';
        document.getElementById('estado').value = empresa.estado || 'activa';
        document.getElementById('logo_url').value = empresa.logo_url || '';
        document.getElementById('email').value = empresa.email || '';
        document.getElementById('telefono').value = empresa.telefono || '';
        document.getElementById('sitio_web').value = empresa.sitio_web || '';
        document.getElementById('direccion').value = empresa.direccion || '';
        document.getElementById('descripcion').value = empresa.descripcion || '';
        document.getElementById('descuento_porcentaje').value = empresa.descuento_porcentaje || '';
        document.getElementById('fecha_convenio').value = empresa.fecha_convenio || '';
        document.getElementById('beneficios').value = empresa.beneficios || '';
        document.getElementById('condiciones').value = empresa.condiciones || '';
        document.getElementById('contacto_persona').value = empresa.contacto_nombre || empresa.contacto_persona || '';
        document.getElementById('contacto_telefono').value = empresa.contacto_telefono || '';
        document.getElementById('contacto_email').value = empresa.contacto_email || '';

        const adminUsuarioSelect = document.getElementById('admin_usuario_id');
        if (adminUsuarioSelect) {
            adminUsuarioSelect.value = empresa.admin_usuario_id || '';
        }

        if (empresa.logo_url) {
            this.actualizarPreviewLogo(empresa.logo_url);
        }
    }

    limpiarFormulario() {
        document.getElementById('formEmpresa').reset();
        document.getElementById('logoPreview').classList.add('hidden');
        document.getElementById('logo_file').value = '';
        const adminUsuarioSelect = document.getElementById('admin_usuario_id');
        if (adminUsuarioSelect) adminUsuarioSelect.value = '';
    }

    manejarArchivoLogo(event) {
        const file = event.target.files[0];
        if (!file) return;

        // Validar que sea imagen
        if (!file.type.startsWith('image/')) {
            alert('Por favor selecciona un archivo de imagen válido');
            event.target.value = '';
            return;
        }

        // Validar tamaño (5MB max)
        const maxSize = 5 * 1024 * 1024;
        if (file.size > maxSize) {
            alert('La imagen es muy grande. El tamaño máximo es 5MB');
            event.target.value = '';
            return;
        }

        // Crear preview del archivo
        const reader = new FileReader();
        reader.onload = (e) => {
            this.mostrarPreviewLogo(e.target.result);
        };
        reader.readAsDataURL(file);

        // Limpiar URL cuando se selecciona archivo
        document.getElementById('logo_url').value = '';
    }

    actualizarPreviewLogo(url) {
        if (url && url.trim()) {
            this.mostrarPreviewLogo(url);
        } else {
            this.ocultarPreviewLogo();
        }
    }

    mostrarPreviewLogo(src) {
        const preview = document.getElementById('logoPreview');
        const img = document.getElementById('logoImg');

        img.src = src;
        img.onload = () => preview.classList.remove('hidden');
        img.onerror = () => {
            preview.classList.add('hidden');
            if (src.startsWith('http')) {
                alert('No se pudo cargar la imagen desde esa URL');
            }
        };
    }

    ocultarPreviewLogo() {
        const preview = document.getElementById('logoPreview');
        preview.classList.add('hidden');
    }

    async guardarEmpresa(e) {
        e.preventDefault();

        if (this.enviandoFormulario) return;

        // Validación frontend de campos obligatorios
        const nombre = document.getElementById('nombre').value.trim();
        if (!nombre) {
            this.mostrarError('El nombre de la empresa es obligatorio');
            document.getElementById('nombre').focus();
            return;
        }

        this.enviandoFormulario = true;

        const btnGuardar = document.getElementById('btnGuardar');
        const textoOriginal = btnGuardar.textContent;
        btnGuardar.textContent = 'Guardando...';
        btnGuardar.disabled = true;

        try {
            let logoUrl = document.getElementById('logo_url').value.trim();

            // Subir imagen si se seleccionó archivo
            const logoFile = document.getElementById('logo_file').files[0];
            if (logoFile) {
                logoUrl = await this.subirImagen(logoFile);
            }

            const formData = new FormData();

            // Recuperar empresaEditando desde campo oculto si se perdió
            if (!this.empresaEditando) {
                const hiddenIdField = document.getElementById('empresa_id_hidden');
                if (hiddenIdField && hiddenIdField.value) {
                    const empresaId = parseInt(hiddenIdField.value);
                    this.empresaEditando = this.empresas.find(e => e.id === empresaId);
                }
            }

            const action = this.empresaEditando ? 'actualizar' : 'crear';
            formData.append('action', action);

            if (this.empresaEditando) {
                formData.append('id', this.empresaEditando.id);
            }

            // Recopilar TODOS los campos (incluyendo vacíos para permitir borrado en edición)
            const campos = ['nombre', 'sector', 'estado', 'email', 'telefono',
                'sitio_web', 'direccion', 'descripcion', 'descuento_porcentaje',
                'fecha_convenio', 'beneficios', 'condiciones', 'contacto_persona',
                'contacto_telefono', 'contacto_email', 'admin_usuario_id'];

            campos.forEach(campo => {
                const elemento = document.getElementById(campo);
                if (elemento) {
                    formData.append(campo, elemento.value.trim());
                }
            });

            if (logoUrl) {
                formData.append('logo_url', logoUrl);
            }

            const response = await fetch(this.apiUrl, {
                method: 'POST',
                body: formData
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const responseText = await response.text();
            let data;
            try {
                data = JSON.parse(responseText);
            } catch {
                throw new Error('Respuesta del servidor no es JSON válido');
            }

            if (data.success) {
                this.mostrarExito(this.empresaEditando ? 'Empresa actualizada exitosamente' : 'Empresa creada exitosamente');
                this.cerrarModal();
                await this.cargarEmpresas();
            } else {
                throw new Error(data.message || 'Error guardando empresa');
            }
        } catch (error) {
            console.error('Error en guardarEmpresa:', error);
            this.mostrarError('Error: ' + error.message);
        } finally {
            btnGuardar.textContent = textoOriginal;
            btnGuardar.disabled = false;
            this.enviandoFormulario = false;
        }
    }

    async subirImagen(file) {
        const formData = new FormData();
        formData.append('image', file);

        const response = await fetch('./api/upload-image.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            return data.data.url;
        } else {
            throw new Error(data.message || 'Error subiendo imagen');
        }
    }

    async eliminarEmpresa(id) {
        const empresa = this.empresas.find(e => e.id == id);
        if (!empresa) return;

        if (!confirm(`¿Estás seguro de eliminar "${empresa.nombre}" ?\n\nEsta acción no se puede deshacer.`)) return;

        try {
            const formData = new FormData();
            formData.append('action', 'eliminar');
            formData.append('id', id);

            const response = await fetch(this.apiUrl, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                this.mostrarExito('Empresa eliminada exitosamente');
                await this.cargarEmpresas(); // Refresco automático
                this.actualizarEstadisticas(); // Actualizar estadísticas
            } else {
                throw new Error(data.message || 'Error eliminando empresa');
            }
        } catch (error) {
            console.error('❌ Error eliminando:', error);
            this.mostrarError('Error eliminando empresa: ' + error.message);
        }
    }

    previsualizarEmpresa(id) {
        const empresa = this.empresas.find(e => e.id == id);
        if (!empresa) return;

        document.getElementById('previaEmpresaNombre').textContent = empresa.nombre;
        document.getElementById('previaContenido').innerHTML = this.generarVistaPrevia(empresa);

        const modal = document.getElementById('modalVistaPrevia');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    generarVistaPrevia(empresa) {
        return `
                    < !--Como se verá en empresas - convenio.html-- >
            <div class="text-center mb-6">
                <img src="${empresa.logo_url || this.generarLogoDefault(empresa.nombre)}" 
                     alt="${empresa.nombre}"
                     class="mx-auto h-32 w-auto object-contain rounded-lg shadow-md border">
            </div>
            <div class="space-y-4">
                <h3 class="text-xl font-bold text-center">${empresa.nombre}</h3>
                ${empresa.descripcion ? `
                    <p class="text-gray-600 text-center leading-relaxed">${empresa.descripcion}</p>
                ` : ''}
                <div class="flex justify-center space-x-4 pt-4">
                    ${empresa.sitio_web ? `
                        <a href="${empresa.sitio_web}" target="_blank" 
                           class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">
                            <i class="fas fa-external-link-alt mr-2"></i>Visitar Sitio Web
                        </a>
                    ` : ''}
                </div>
            </div>
                `;
    }

    mostrarModal() {
        const modal = document.getElementById('modalEmpresa');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.style.overflow = 'hidden';
    }

    cerrarModal() {
        const modal = document.getElementById('modalEmpresa');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.style.overflow = 'auto';
    }

    cerrarModalPrevia() {
        const modal = document.getElementById('modalVistaPrevia');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    generarLogoDefault(nombre) {
        return `https://via.placeholder.com/200x120/6366f1/ffffff?text=${encodeURIComponent(nombre)}`;
    }

    formatearFecha(fecha) {
        if (!fecha) return 'N/A';
        return new Date(fecha).toLocaleDateString('es-ES');
    }

    async refrescarSilencioso() {
        try {
            const url = `${this.apiUrl}?action=listar&t=${Date.now()}`;
            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'Cache-Control': 'no-cache'
                }
            });

            if (response.ok) {
                const data = await response.json();
                if (data.success) {
                    const nuevasEmpresas = data.data.empresas || [];
                    const hashAntes = JSON.stringify(this.empresas.map(e => ({ id: e.id, logo_url: e.logo_url, nombre: e.nombre })));
                    const hashDespues = JSON.stringify(nuevasEmpresas.map(e => ({ id: e.id, logo_url: e.logo_url, nombre: e.nombre })));

                    this.empresas = nuevasEmpresas;

                    if (hashAntes !== hashDespues) {
                        this.renderizarTablaAdmin();
                        this.actualizarEstadisticas();
                    }
                }
            }
        } catch (error) {
            // Refresco silencioso - no mostrar errores al usuario
            // console.log('🔄 Refresco automático falló (silencioso):', error.message);
        }
    }

    actualizarEstadisticas() {
        const total = this.empresas.length;
        const activas = this.empresas.filter(e => e.estado === 'activa').length;
        const destacadas = this.empresas.filter(e => e.destacado === true || e.destacado == 1).length;
        const conDescuento = this.empresas.filter(e => parseFloat(e.descuento_porcentaje) > 0).length;

        const totalEl = document.getElementById('totalEmpresas');
        const activasEl = document.getElementById('empresasActivas');
        const destacadasEl = document.getElementById('destacadasEmpresas');
        const descuentosEl = document.getElementById('descuentosEmpresas');

        if (totalEl) totalEl.textContent = total;
        if (activasEl) activasEl.textContent = activas;
        if (destacadasEl) destacadasEl.textContent = destacadas;
        if (descuentosEl) descuentosEl.textContent = conDescuento;
    }

    filtrarEmpresas(termino) {
        // Implementar filtro de búsqueda
        const empresasFiltradas = this.empresas.filter(empresa =>
            empresa.nombre.toLowerCase().includes(termino.toLowerCase()) ||
            (empresa.sector && empresa.sector.toLowerCase().includes(termino.toLowerCase()))
        );
        this.renderizarTablaAdmin(empresasFiltradas);
    }

    exportarEmpresas() {
        const datosExportar = this.empresas.map(empresa => ({
            id: empresa.id,
            nombre: empresa.nombre,
            sector: empresa.sector,
            email: empresa.email,
            telefono: empresa.telefono,
            sitio_web: empresa.sitio_web,
            estado: empresa.estado,
            descuento_porcentaje: empresa.descuento_porcentaje,
            fecha_convenio: empresa.fecha_convenio
        }));

        const dataStr = "data:text/json;charset=utf-8," + encodeURIComponent(JSON.stringify(datosExportar, null, 2));
        const downloadAnchorNode = document.createElement('a');
        downloadAnchorNode.setAttribute("href", dataStr);
        downloadAnchorNode.setAttribute("download", `empresas-convenio-${new Date().toISOString().split('T')[0]}.json`);
        document.body.appendChild(downloadAnchorNode);
        downloadAnchorNode.click();
        downloadAnchorNode.remove();

        this.mostrarExito('Datos exportados exitosamente');
    }

    mostrarExito(mensaje) {
        // console.log('✅', mensaje);
        this.mostrarNotificacion(mensaje, 'success');
    }

    mostrarError(mensaje) {
        console.error('❌', mensaje);
        this.mostrarNotificacion(mensaje, 'error');
    }

    mostrarNotificacion(mensaje, tipo = 'info', duracion = 4000) {
        // Crear contenedor de notificaciones si no existe
        let contenedor = document.getElementById('notificacionesContainer');
        if (!contenedor) {
            contenedor = document.createElement('div');
            contenedor.id = 'notificacionesContainer';
            contenedor.className = 'fixed top-4 right-4 z-50 space-y-2';
            document.body.appendChild(contenedor);
        }

        // Crear notificación
        const notificacion = document.createElement('div');
        notificacion.className = `notification max-w-sm p-4 rounded-lg shadow-lg border-l-4 transform translate-x-full transition-transform duration-300 ${this.obtenerClasesNotificacion(tipo)}`;

        notificacion.innerHTML = `
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <i class="${this.obtenerIconoNotificacion(tipo)} mr-3"></i>
                    <span class="text-sm font-medium">${mensaje}</span>
                </div>
                <button onclick="this.parentElement.parentElement.remove()" class="ml-4 text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;

        contenedor.appendChild(notificacion);

        // Animar entrada
        setTimeout(() => {
            notificacion.classList.remove('translate-x-full');
        }, 10);

        // Auto-remover después de la duración especificada
        setTimeout(() => {
            if (notificacion.parentElement) {
                notificacion.classList.add('translate-x-full');
                setTimeout(() => {
                    if (notificacion.parentElement) {
                        notificacion.remove();
                    }
                }, 300);
            }
        }, duracion);
    }

    obtenerClasesNotificacion(tipo) {
        switch (tipo) {
            case 'success':
                return 'bg-green-50 border-green-400 text-green-800';
            case 'error':
                return 'bg-red-50 border-red-400 text-red-800';
            case 'warning':
                return 'bg-yellow-50 border-yellow-400 text-yellow-800';
            default:
                return 'bg-blue-50 border-blue-400 text-blue-800';
        }
    }

    obtenerIconoNotificacion(tipo) {
        switch (tipo) {
            case 'success':
                return 'fas fa-check-circle text-green-500';
            case 'error':
                return 'fas fa-exclamation-circle text-red-500';
            case 'warning':
                return 'fas fa-exclamation-triangle text-yellow-500';
            default:
                return 'fas fa-info-circle text-blue-500';
        }
    }

    // ============================================
    // Company Requests Management
    // ============================================

    async cargarSolicitudes() {
        try {
            const url = `${this.solicitudesApiUrl}?estado=pendiente&t=${Date.now()}`;

            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'Cache-Control': 'no-cache'
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const data = await response.json();

            if (data.success) {
                this.solicitudes = data.solicitudes || [];
                this.renderizarSolicitudes();
            } else {
                throw new Error(data.error || 'Error en respuesta de API');
            }
        } catch (error) {
            // Silencioso: no bloquear UI si no hay solicitudes
            console.warn('Solicitudes no disponibles:', error.message);
        }
    }

    renderizarSolicitudes() {
        const section = document.getElementById('solicitudesSection');
        const container = document.getElementById('solicitudesContainer');
        const badge = document.getElementById('solicitudesBadge');

        if (!section || !container || !badge) {
            console.warn('⚠️ [SOLICITUDES] Elementos de UI no encontrados');
            return;
        }

        const solicitudesPendientes = this.solicitudes.filter(s => s.estado === 'pendiente');

        // Update badge
        badge.textContent = solicitudesPendientes.length;

        // Show/hide section
        if (solicitudesPendientes.length === 0) {
            section.classList.add('hidden');
            return;
        }

        section.classList.remove('hidden');

        // Render requests
        container.innerHTML = solicitudesPendientes.map(sol => this.renderizarSolicitudCard(sol)).join('');
    }

    renderizarSolicitudCard(sol) {
        const tipoText = sol.tipo_solicitud === 'crear' ? 'Crear nueva empresa' : 'Mostrar empresa existente';
        const tipoIcon = sol.tipo_solicitud === 'crear' ? 'fa-plus-circle' : 'fa-eye';
        const tipoColor = sol.tipo_solicitud === 'crear' ? 'text-green-600' : 'text-blue-600';

        let datosEmpresa = '';
        if (sol.datos_empresa) {
            const datos = typeof sol.datos_empresa === 'string'
                ? JSON.parse(sol.datos_empresa)
                : sol.datos_empresa;

            datosEmpresa = `
                <div class="bg-gray-50 rounded-lg p-4 mb-4">
                    <h4 class="font-semibold text-gray-900 mb-2">Datos de la empresa:</h4>
                    <div class="grid grid-cols-2 gap-2 text-sm">
                        ${datos.nombre ? `<div><strong>Nombre:</strong> ${datos.nombre}</div>` : ''}
                        ${datos.sector ? `<div><strong>Sector:</strong> ${datos.sector}</div>` : ''}
                        ${datos.email ? `<div><strong>Email:</strong> ${datos.email}</div>` : ''}
                        ${datos.telefono ? `<div><strong>Teléfono:</strong> ${datos.telefono}</div>` : ''}
                        ${datos.descripcion ? `<div class="col-span-2"><strong>Descripción:</strong> ${datos.descripcion}</div>` : ''}
                    </div>
                </div>
            `;
        }

        return `
            <div class="border border-gray-200 rounded-lg p-4 mb-4 bg-white hover:shadow-md transition">
                <div class="flex items-start justify-between mb-3">
                    <div class="flex items-start space-x-3">
                        <div class="flex-shrink-0">
                            <i class="fas ${tipoIcon} text-2xl ${tipoColor}"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900">${tipoText}</h3>
                            <p class="text-sm text-gray-600">
                                Solicitado por: <strong>${sol.usuario_nombre} ${sol.usuario_apellidos || ''}</strong>
                            </p>
                            <p class="text-xs text-gray-500">
                                <i class="far fa-clock mr-1"></i>${this.formatearFecha(sol.fecha_solicitud)}
                            </p>
                        </div>
                    </div>
                    <span class="px-3 py-1 bg-yellow-100 text-yellow-800 text-xs font-semibold rounded-full">
                        Pendiente
                    </span>
                </div>

                ${datosEmpresa}

                ${sol.empresa_nombre_existente ? `
                    <div class="bg-blue-50 rounded-lg p-3 mb-4">
                        <p class="text-sm"><strong>Empresa existente:</strong> ${sol.empresa_nombre_existente}</p>
                    </div>
                ` : ''}

                <div class="flex items-center justify-end space-x-3 pt-3 border-t">
                    <button onclick="adminEmpresas.rechazarSolicitud(${sol.id})" 
                            class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">
                        <i class="fas fa-times mr-2"></i>Rechazar
                    </button>
                    <button onclick="adminEmpresas.aprobarSolicitud(${sol.id})" 
                            class="px-4 py-2 text-white rounded-lg hover:opacity-90 transition" 
                            style="background: #C7252B;">
                        <i class="fas fa-check mr-2"></i>Aprobar
                    </button>
                </div>
            </div>
        `;
    }

    async aprobarSolicitud(id) {
        if (!confirm('¿Estás seguro de que deseas aprobar esta solicitud?')) {
            return;
        }

        try {
            const response = await fetch(this.solicitudesApiUrl, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    id: id,
                    accion: 'aprobar'
                })
            });

            const data = await response.json();

            if (data.success) {
                this.mostrarNotificacion('Solicitud aprobada exitosamente', 'success');
                await this.cargarSolicitudes();
                await this.cargarEmpresas(); // Reload companies list
            } else {
                throw new Error(data.error || 'Error aprobando solicitud');
            }
        } catch (error) {
            console.error('❌ [SOLICITUDES] Error aprobando:', error);
            this.mostrarNotificacion('Error aprobando solicitud: ' + error.message, 'error');
        }
    }

    async rechazarSolicitud(id) {
        const notas = prompt('¿Por qué rechazas esta solicitud? (opcional)');
        if (notas === null) return; // User cancelled

        try {
            const response = await fetch(this.solicitudesApiUrl, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    id: id,
                    accion: 'rechazar',
                    notas: notas || 'Sin motivo especificado'
                })
            });

            const data = await response.json();

            if (data.success) {
                this.mostrarNotificacion('Solicitud rechazada', 'success');
                await this.cargarSolicitudes();
            } else {
                throw new Error(data.error || 'Error rechazando solicitud');
            }
        } catch (error) {
            console.error('❌ [SOLICITUDES] Error rechazando:', error);
            this.mostrarNotificacion('Error rechazando solicitud: ' + error.message, 'error');
        }
    }
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    window.adminEmpresas = new AdminEmpresasManager();
});