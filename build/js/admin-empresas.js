/**
 * Administrador completo de empresas - demo_empresas.html
 * CRUD completo con visualización, creación, edición y exportación
 */

class AdminEmpresasManager {
    constructor() {
        this.empresas = [];
        this.empresaEditando = null;
        this.enviandoFormulario = false;
        // Usar URL absoluta fija para evitar problemas de hostname
        // Detectar si estamos en el servidor correcto
        if (window.location.hostname === 'intranet.clústermetropolitano.mx' || 
            window.location.hostname === 'clústermetropolitano.mx') {
            // Servidor de producción - usar URL absoluta
            this.apiUrl = 'https://intranet.clústermetropolitano.mx/build/api/empresas-simple.php';
        } else {
            // Desarrollo local - usar ruta relativa
            this.apiUrl = './api/empresas-simple.php';
        }
        
        // Debug logging para verificar URLs
        console.log('🔧 [URL DEBUG] Hostname:', window.location.hostname);
        console.log('🔧 [URL DEBUG] API URL final:', this.apiUrl);
        console.log('🔧 [URL DEBUG] Es URL absoluta:', this.apiUrl.startsWith('http'));
        
        // Verificar que la URL es accesible
        this.verificarApiUrl();
        
        this.init();
    }

    async verificarApiUrl() {
        try {
            console.log('🔗 [API CHECK] Verificando conectividad con API...');
            const response = await fetch(this.apiUrl + '?action=listar&test=1', {
                method: 'GET',
                headers: { 'Content-Type': 'application/json' }
            });
            
            if (response.ok) {
                console.log('✅ [API CHECK] Conectividad OK - Status:', response.status);
            } else {
                console.warn('⚠️ [API CHECK] Respuesta no OK - Status:', response.status);
            }
        } catch (error) {
            console.error('❌ [API CHECK] Error de conectividad:', error.message);
            console.error('❌ [API CHECK] URL probada:', this.apiUrl);
            
            // Si hay error, intentar URL alternativa
            if (!this.apiUrl.startsWith('http')) {
                console.log('🔄 [API CHECK] Intentando URL absoluta como fallback...');
                this.apiUrl = 'https://intranet.clústermetropolitano.mx/build/api/empresas-simple.php';
                console.log('🔄 [API CHECK] Nueva URL:', this.apiUrl);
            }
        }
    }

    init() {
        console.log('🔧 Inicializando administrador de empresas...');
        this.setupEventListeners();
        this.cargarEmpresas();
        
        // Refresco automático cada 30 segundos para mantener datos actualizados
        setInterval(() => {
            this.refrescarSilencioso();
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
            console.log('📡 [ADMIN] Cargando empresas desde:', url);
            console.log('📡 [ADMIN] URL completa:', window.location.origin + '/' + url.replace('./', ''));
            
            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'Cache-Control': 'no-cache'
                }
            });
            
            console.log('📡 [ADMIN] Respuesta HTTP:', response.status, response.statusText);
            console.log('📡 [ADMIN] Response URL:', response.url);
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const responseText = await response.text();
            console.log('📡 [ADMIN] Respuesta RAW:', responseText);
            
            const data = JSON.parse(responseText);
            console.log('📡 [ADMIN] Datos parseados:', data);
            console.log('📡 [ADMIN] Datos recibidos:', data);

            if (data.success) {
                this.empresas = data.data.empresas || [];
                console.log(`✅ [ADMIN] ${this.empresas.length} empresas cargadas`);
                this.renderizarTablaAdmin();
                this.actualizarEstadisticas();
            } else {
                throw new Error(data.message || 'Error en respuesta de API');
            }
        } catch (error) {
            console.error('❌ [ADMIN] Error completo:', error);
            console.error('❌ [ADMIN] Stack trace:', error.stack);
            console.error('❌ [ADMIN] API URL era:', this.apiUrl);
            console.error('❌ [ADMIN] Window location:', window.location.href);
            
            this.mostrarError(`Error cargando empresas: ${error.message}

🔍 DEBUG INFO:
- API URL: ${this.apiUrl}
- Page URL: ${window.location.href}
- Timestamp: ${new Date().toISOString()}`);
            
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

    renderizarTablaAdmin(empresasList = null) {
        const tbody = document.getElementById('empresasTableBody');
        if (!tbody) {
            console.error('❌ [ADMIN] No se encontró elemento empresasTableBody');
            return;
        }

        const empresas = empresasList || this.empresas || [];
        console.log(`📊 [ADMIN] Renderizando ${empresas.length} empresas en tabla`);

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
            tbody.innerHTML = empresas.map(empresa => `
            <tr class="hover:bg-gray-50">
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center">
                        <img src="${empresa.logo_url || this.generarLogoDefault(empresa.nombre)}" 
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
                    <span class="px-2 py-1 text-xs rounded-full ${
                        empresa.estado === 'activa' 
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
        `).join('');
        } catch (error) {
            console.error('❌ [ADMIN] Error renderizando tabla:', error);
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" class="px-6 py-12 text-center text-red-500">
                        <i class="fas fa-exclamation-triangle text-4xl mb-4"></i>
                        <p class="text-lg font-semibold">Error renderizando datos</p>
                        <p class="text-sm">${error.message}</p>
                    </td>
                </tr>
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
                <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
                    <div class="flex items-center justify-between p-6 border-b">
                        <h3 id="previaEmpresaNombre" class="text-lg font-medium text-gray-900"></h3>
                        <button id="btnCerrarPrevia" class="text-gray-400 hover:text-gray-600">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>
                    <div id="previaContenido" class="p-6">
                        <!-- Contenido dinámico -->
                    </div>
                </div>
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

        console.log('✅ [MODAL] Eventos de modal configurados sin duplicaciones');
    }

    abrirModalCrear() {
        console.log('🆕 [CREAR] Abriendo modal de creación...');
        this.empresaEditando = null;
        console.log('🆕 [CREAR] empresaEditando establecido a:', this.empresaEditando);
        document.getElementById('modalTitulo').textContent = 'Agregar Nueva Empresa';
        document.getElementById('btnGuardar').textContent = 'Crear Empresa';
        console.log('🆕 [CREAR] Título y botón configurados para creación');
        this.limpiarFormulario();
        console.log('🆕 [CREAR] Formulario limpiado');
        this.mostrarModal();
        console.log('🆕 [CREAR] Modal mostrado - listo para crear nueva empresa');
    }

    editarEmpresa(id) {
        console.log('✏️ [EDITAR] === INICIANDO EDICIÓN ===');
        console.log('✏️ [EDITAR] ID a editar:', id);

        const empresa = this.empresas.find(e => e.id == id);
        if (!empresa) {
            console.error('❌ [EDITAR] Empresa no encontrada con ID:', id);
            return;
        }

        console.log('✏️ [EDITAR] Empresa encontrada:', empresa);
        this.empresaEditando = empresa;
        console.log('✏️ [EDITAR] empresaEditando establecido a:', this.empresaEditando);

        document.getElementById('modalTitulo').textContent = 'Editar Empresa';
        document.getElementById('btnGuardar').textContent = 'Actualizar';

        this.llenarFormulario(empresa);
        this.mostrarModal();

        console.log('✏️ [EDITAR] Modal de edición configurado');
        console.log('✏️ [EDITAR] empresaEditando al final:', this.empresaEditando);
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

        if (empresa.logo_url) {
            this.actualizarPreviewLogo(empresa.logo_url);
        }
    }

    limpiarFormulario() {
        document.getElementById('formEmpresa').reset();
        document.getElementById('logoPreview').classList.add('hidden');
        document.getElementById('logo_file').value = '';
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

        // Prevenir envío múltiple
        if (this.enviandoFormulario) {
            console.log('⚠️ [GUARDAR] Ya hay un proceso de guardado en curso, ignorando...');
            return;
        }

        this.enviandoFormulario = true;
        console.log('💾 [GUARDAR] === INICIANDO PROCESO DE GUARDADO ===');
        console.log('💾 [GUARDAR] empresaEditando:', this.empresaEditando);
        console.log('💾 [GUARDAR] Tipo de empresaEditando:', typeof this.empresaEditando);
        console.log('💾 [GUARDAR] Es null?:', this.empresaEditando === null);
        console.log('💾 [GUARDAR] Es undefined?:', this.empresaEditando === undefined);
        
        const btnGuardar = document.getElementById('btnGuardar');
        const textoOriginal = btnGuardar.textContent;
        btnGuardar.textContent = 'Guardando...';
        btnGuardar.disabled = true;
        
        try {
            let logoUrl = document.getElementById('logo_url').value.trim();
            console.log('🔧 [DEBUG] logoUrl inicial:', logoUrl);
            
            // Primero subir imagen si se seleccionó archivo
            const logoFile = document.getElementById('logo_file').files[0];
            if (logoFile) {
                console.log('🔧 [DEBUG] Subiendo archivo de imagen:', logoFile.name);
                logoUrl = await this.subirImagen(logoFile);
                console.log('🔧 [DEBUG] URL de imagen subida:', logoUrl);
            }
            
            const formData = new FormData();
            const action = this.empresaEditando ? 'actualizar' : 'crear';
            console.log('🎯 [ACCIÓN] empresaEditando evaluación:', !!this.empresaEditando);
            console.log('🎯 [ACCIÓN] Acción determinada:', action);
            console.log('🎯 [ACCIÓN] Lógica: empresaEditando ?', this.empresaEditando ? 'TRUTHY (actualizar)' : 'FALSY (crear)');
            
            formData.append('action', action);
            
            if (this.empresaEditando) {
                formData.append('id', this.empresaEditando.id);
                console.log('🔧 [DEBUG] ID empresa a editar:', this.empresaEditando.id);
            }
            
            // Recopilar datos del formulario
            const campos = ['nombre', 'sector', 'estado', 'email', 'telefono',
                           'sitio_web', 'direccion', 'descripcion', 'descuento_porcentaje',
                           'fecha_convenio', 'beneficios', 'condiciones', 'contacto_persona',
                           'contacto_telefono', 'contacto_email'];
            
            console.log('🔧 [DEBUG] Recopilando datos del formulario...');
            const datosFormulario = {};
            
            campos.forEach(campo => {
                const elemento = document.getElementById(campo);
                const valor = elemento ? elemento.value.trim() : '';
                datosFormulario[campo] = valor;
                if (valor) {
                    formData.append(campo, valor);
                    console.log(`🔧 [DEBUG] ${campo}:`, valor);
                }
            });
            
            // Agregar logo URL (desde archivo subido o URL manual)
            if (logoUrl) {
                formData.append('logo_url', logoUrl);
                console.log('🔧 [DEBUG] Logo URL agregado:', logoUrl);
            }

            console.log('🔧 [DEBUG] Enviando petición a API:', this.apiUrl);
            console.log('🔧 [DEBUG] FormData entries:');
            for (let [key, value] of formData.entries()) {
                console.log(`  ${key}: ${value}`);
            }

            const response = await fetch(this.apiUrl, {
                method: 'POST',
                body: formData
            });
            
            console.log('🔧 [DEBUG] Respuesta HTTP status:', response.status);
            console.log('🔧 [DEBUG] Respuesta HTTP statusText:', response.statusText);
            
            const responseText = await response.text();
            console.log('🔧 [DEBUG] Respuesta RAW:', responseText);
            
            let data;
            try {
                data = JSON.parse(responseText);
                console.log('🔧 [DEBUG] Datos parseados:', data);
            } catch (parseError) {
                console.error('❌ [DEBUG] Error parsing JSON:', parseError);
                console.error('❌ [DEBUG] Respuesta no válida:', responseText);
                throw new Error('Respuesta del servidor no es JSON válido');
            }
            
            if (data.success) {
                console.log('✅ [DEBUG] Operación exitosa');
                this.mostrarExito(this.empresaEditando ? 'Empresa actualizada exitosamente' : 'Empresa creada exitosamente');
                this.cerrarModal();
                console.log('🔧 [DEBUG] Recargando lista de empresas...');
                await this.cargarEmpresas(); // Recargar lista
                console.log('✅ [DEBUG] Lista recargada');
            } else {
                console.error('❌ [DEBUG] Error en respuesta del API:', data.message);
                throw new Error(data.message || 'Error guardando empresa');
            }
        } catch (error) {
            console.error('❌ [DEBUG] Error completo en guardarEmpresa:', error);
            console.error('❌ [DEBUG] Stack trace:', error.stack);
            this.mostrarError('Error: ' + error.message);
        } finally {
            btnGuardar.textContent = textoOriginal;
            btnGuardar.disabled = false;
            this.enviandoFormulario = false; // Reset del flag
            console.log('🔧 [DEBUG] Proceso de guardado finalizado');
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

        if (!confirm(`¿Estás seguro de eliminar "${empresa.nombre}"?\n\nEsta acción no se puede deshacer.`)) return;

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
            <!-- Como se verá en empresas-convenio.html -->
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
                    const empresasAntes = this.empresas.length;
                    this.empresas = data.data.empresas || [];
                    
                    // Solo re-renderizar si hubo cambios
                    if (empresasAntes !== this.empresas.length) {
                        this.renderizarTablaAdmin();
                        this.actualizarEstadisticas();
                    }
                }
            }
        } catch (error) {
            // Refresco silencioso - no mostrar errores al usuario
            console.log('🔄 Refresco automático falló (silencioso):', error.message);
        }
    }

    actualizarEstadisticas() {
        const total = this.empresas.length;
        const activas = this.empresas.filter(e => e.estado === 'activa').length;
        
        // Actualizar elementos de estadísticas si existen
        const totalEl = document.getElementById('totalEmpresas');
        const activasEl = document.getElementById('empresasActivas');
        
        if (totalEl) totalEl.textContent = total;
        if (activasEl) activasEl.textContent = activas;
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
        console.log('✅', mensaje);
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
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    window.adminEmpresas = new AdminEmpresasManager();
});