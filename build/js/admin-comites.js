/**
 * Administrador completo de comités - demo_comite.html
 * Siguiendo exactamente el patrón de admin-empresas.js
 */

class AdminComitesManager {
    constructor() {
        this.comites = [];
        this.comiteEditando = null;

        // Usar URL siguiendo el patrón de admin-empresas.js
        if (window.location.hostname === 'intranet.clautmetropolitano.mx' ||
            window.location.hostname === 'clautmetropolitano.mx') {
            // Servidor de producción - usar URL absoluta
            this.apiUrl = 'https://intranet.clautmetropolitano.mx/build/api/comites.php';
        } else {
            // Desarrollo local - usar ruta relativa
            this.apiUrl = './api/comites.php';
        }

        // Debug logging
        console.log('🔧 [URL DEBUG] Hostname:', window.location.hostname);
        console.log('🔧 [URL DEBUG] API URL final:', this.apiUrl);

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

            // Fallback a URL absoluta
            if (!this.apiUrl.startsWith('http')) {
                console.log('🔄 [API CHECK] Intentando URL absoluta como fallback...');
                this.apiUrl = 'https://intranet.clústermetropolitano.mx/build/api/comites.php';
                console.log('🔄 [API CHECK] Nueva URL:', this.apiUrl);
            }
        }
    }

    init() {
        console.log('🔧 Inicializando administrador de comités...');
        this.setupEventListeners();
        this.cargarComites();

        // Refresco automático cada 30 segundos
        setInterval(() => {
            this.refrescarSilencioso();
        }, 30000);
    }

    setupEventListeners() {
        // Event listener para el formulario
        const form = document.getElementById('comiteForm');
        if (form) {
            form.addEventListener('submit', (e) => this.guardarComite(e));
        }

        // Botón cancelar
        const btnCancelar = document.getElementById('cancelBtn');
        if (btnCancelar) {
            btnCancelar.addEventListener('click', () => this.cancelarEdicion());
        }

        // Manejo de imágenes - Siguiendo el patrón de admin-empresas.js
        const imagenUrl = document.getElementById('imagen_url');
        if (imagenUrl) {
            imagenUrl.addEventListener('input', (e) => this.actualizarPreviewImagen(e.target.value));
        }

        const imagenFile = document.getElementById('imagen_file');
        if (imagenFile) {
            imagenFile.addEventListener('change', (e) => this.manejarArchivoImagen(e));
        }
    }

    // === FUNCIONES DE MANEJO DE IMÁGENES ===
    // Basadas en admin-empresas.js

    manejarArchivoImagen(event) {
        const file = event.target.files[0];
        if (!file) return;

        // Validar que sea imagen
        if (!file.type.startsWith('image/')) {
            this.mostrarError('Por favor selecciona un archivo de imagen válido');
            event.target.value = '';
            return;
        }

        // Validar tamaño (5MB max)
        const maxSize = 5 * 1024 * 1024;
        if (file.size > maxSize) {
            this.mostrarError('La imagen es muy grande. El tamaño máximo es 5MB');
            event.target.value = '';
            return;
        }

        // Crear preview del archivo
        const reader = new FileReader();
        reader.onload = (e) => {
            this.mostrarPreviewImagen(e.target.result);
        };
        reader.readAsDataURL(file);

        // Limpiar URL cuando se selecciona archivo
        const imagenUrl = document.getElementById('imagen_url');
        if (imagenUrl) {
            imagenUrl.value = '';
        }
    }

    actualizarPreviewImagen(url) {
        if (url && url.trim()) {
            this.mostrarPreviewImagen(url);
        } else {
            this.ocultarPreviewImagen();
        }
    }

    mostrarPreviewImagen(src) {
        const preview = document.getElementById('imagenPreview');
        const img = document.getElementById('imagenImg');

        if (!preview || !img) return;

        img.src = src;
        img.onload = () => preview.classList.remove('hidden');
        img.onerror = () => {
            preview.classList.add('hidden');
            if (src.startsWith('http')) {
                this.mostrarError('No se pudo cargar la imagen desde esa URL');
            }
        };
    }

    ocultarPreviewImagen() {
        const preview = document.getElementById('imagenPreview');
        if (preview) {
            preview.classList.add('hidden');
        }
    }

    async subirImagen(file) {
        try {
            console.log('📤 [IMAGEN] Subiendo archivo:', file.name);

            const formData = new FormData();
            formData.append('action', 'subir_imagen');
            formData.append('imagen', file);

            const response = await fetch(this.apiUrl, {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                console.log('✅ [IMAGEN] Imagen subida exitosamente:', result.url);
                return result.url;
            } else {
                console.error('❌ [IMAGEN] Error en respuesta:', result);
                throw new Error(result.message || 'Error al subir imagen');
            }
        } catch (error) {
            console.error('❌ [IMAGEN] Error subiendo imagen:', error);
            this.mostrarError('Error al subir imagen: ' + error.message);
            return null;
        }
    }

    limpiarFormulario() {
        const form = document.getElementById('comiteForm');
        if (form) {
            form.reset();
        }

        // Limpiar preview de imagen
        this.ocultarPreviewImagen();

        // Limpiar archivo seleccionado
        const imagenFile = document.getElementById('imagen_file');
        if (imagenFile) {
            imagenFile.value = '';
        }

        // Resetear estado de edición
        this.comiteEditando = null;
        this.actualizarEstadoFormulario();
    }

    actualizarEstadoFormulario() {
        const submitText = document.getElementById('submitText');
        const form = document.getElementById('comiteForm');

        if (submitText) {
            if (this.comiteEditando) {
                submitText.textContent = 'Actualizar Comité';
            } else {
                submitText.textContent = 'Guardar Comité';
            }
        }

        // Actualizar título del formulario si existe
        const formTitle = document.querySelector('#comiteForm h3, .form-title');
        if (formTitle) {
            const icon = formTitle.querySelector('i');
            if (this.comiteEditando) {
                formTitle.innerHTML = `
                    <i class="fas fa-edit mr-2 text-blue-600"></i>
                    Editar Comité
                `;
            } else {
                formTitle.innerHTML = `
                    <i class="fas fa-plus-circle mr-2 text-green-600"></i>
                    Crear/Editar Comité
                `;
            }
        }
    }

    async cargarComites() {
        try {
            const url = `${this.apiUrl}?action=listar&t=${Date.now()}`;
            console.log('📡 [ADMIN] Cargando comités desde:', url);

            const response = await fetch(url);
            const data = await response.json();

            console.log('📡 [ADMIN] Respuesta del servidor:', data);

            // Manejar diferentes formatos de respuesta de la API
            if (data.success || data.success === undefined) {
                // Si la respuesta es exitosa o no tiene campo success
                this.comites = data.comites || data.data || data.records || data || [];

                // Asegurar que tenemos un array
                if (!Array.isArray(this.comites)) {
                    if (this.comites && typeof this.comites === 'object') {
                        // Si es un objeto, intentar extraer un array
                        this.comites = Object.values(this.comites);
                    } else {
                        this.comites = [];
                    }
                }

                this.renderComites();
                console.log(`✅ [ADMIN] ${this.comites.length} comités cargados exitosamente`);
                console.log('📡 [ADMIN] Datos de comités:', this.comites);
            } else {
                throw new Error(data.message || data.error || 'Error al cargar comités');
            }
        } catch (error) {
            console.error('❌ [ADMIN] Error cargando comités:', error);
            this.mostrarError('Error al cargar la lista de comités: ' + error.message);
            this.comites = [];
            this.renderComites();
        }
    }

    renderComites() {
        console.log('🎨 [RENDER] === INICIANDO RENDERIZADO DE COMITÉS ===');
        console.log('🎨 [RENDER] Número de comités:', this.comites.length);
        console.log('🎨 [RENDER] Datos de comités:', this.comites);

        const listEl = document.getElementById('comitesList');
        const emptyEl = document.getElementById('emptyState');
        const loadingEl = document.getElementById('loadingComites');

        console.log('🎨 [RENDER] Elementos DOM:', {
            listEl: !!listEl,
            emptyEl: !!emptyEl,
            loadingEl: !!loadingEl
        });

        if (loadingEl) loadingEl.classList.add('hidden');

        if (!listEl) {
            console.error('❌ [RENDER] Elemento comitesList no encontrado');
            return;
        }

        if (this.comites.length === 0) {
            console.log('🎨 [RENDER] No hay comités - mostrando estado vacío');
            listEl.classList.add('hidden');
            if (emptyEl) emptyEl.classList.remove('hidden');
            return;
        }

        console.log('🎨 [RENDER] Renderizando comités...');
        listEl.innerHTML = '';
        this.comites.forEach((comite, index) => {
            console.log(`🎨 [RENDER] Creando card ${index + 1}:`, comite.nombre);
            const comiteCard = this.createComiteCard(comite);
            listEl.appendChild(comiteCard);
        });

        listEl.classList.remove('hidden');
        if (emptyEl) emptyEl.classList.add('hidden');
        console.log('✅ [RENDER] Renderizado completado exitosamente');
    }

    createComiteCard(comite) {
        const card = document.createElement('div');
        card.className = 'bg-white border border-gray-200 rounded-lg shadow-sm hover:shadow-md transition-shadow';

        // Determinar URL de imagen
        let imageUrl = '';
        if (comite.imagen) {
            // Si es una URL completa, usarla directamente
            if (comite.imagen.startsWith('http://') || comite.imagen.startsWith('https://')) {
                imageUrl = comite.imagen;
            }
            // Si es una ruta de archivo, usar endpoint de imagen
            else if (comite.imagen.startsWith('uploads/')) {
                imageUrl = `${this.apiUrl}?action=imagen&id=${comite.id}&t=${Date.now()}`;
            }
            // Si no tiene prefijo, asumir que es una ruta de archivo
            else {
                imageUrl = `${this.apiUrl}?action=imagen&id=${comite.id}&t=${Date.now()}`;
            }
        }

        const imageHTML = imageUrl ?
            `<div class="h-48 bg-cover bg-center rounded-t-lg" style="background-image: url('${imageUrl}')"></div>` :
            `<div class="h-48 bg-gray-200 rounded-t-lg flex items-center justify-center">
                <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 715.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
            </div>`;

        card.innerHTML = `
            ${imageHTML}
            <div class="p-4">
                <h3 class="text-lg font-semibold text-gray-800 mb-2">${this.escapeHtml(comite.nombre)}</h3>
                <p class="text-gray-600 text-sm mb-2">${this.escapeHtml(comite.descripcion || 'Sin descripción')}</p>
                <p class="text-gray-500 text-xs mb-4">${this.escapeHtml(comite.objetivo || 'Sin objetivo definido')}</p>
                <div class="flex space-x-2">
                    <button onclick="window.adminComites.editComite(${comite.id})"
                            class="px-3 py-1 bg-blue-500 text-white text-sm rounded hover:bg-blue-600">
                        Editar
                    </button>
                    <button onclick="window.adminComites.deleteComite(${comite.id})"
                            class="px-3 py-1 bg-red-500 text-white text-sm rounded hover:bg-red-600">
                        Eliminar
                    </button>
                </div>
            </div>
        `;

        return card;
    }

    editComite(id) {
        const comite = this.comites.find(c => c.id === id);
        if (!comite) return;

        this.comiteEditando = comite;

        // Llenar el formulario
        document.getElementById('nombre').value = comite.nombre || '';
        document.getElementById('descripcion').value = comite.descripcion || '';
        document.getElementById('objetivo').value = comite.objetivo || '';
        document.getElementById('periodicidad').value = comite.periodicidad || 'Mensual';
        document.getElementById('miembros_activos').value = comite.miembros_activos || '0';
        document.getElementById('organizacion').value = comite.organizacion || '';
        document.getElementById('estado').value = comite.estado || 'activo';

        // Actualizar botón
        const submitBtn = document.getElementById('submitBtn');
        const submitText = document.getElementById('submitText');
        if (submitBtn && submitText) {
            submitText.textContent = 'Actualizar Comité';
        }

        // Scroll al formulario
        document.getElementById('comiteForm').scrollIntoView({ behavior: 'smooth' });
    }

    async deleteComite(id) {
        const comite = this.comites.find(c => c.id === id);
        if (!comite) return;

        if (!confirm(`¿Estás seguro de eliminar "${comite.nombre}"?\n\nEsta acción no se puede deshacer.`)) return;

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
                this.mostrarExito('Comité eliminado exitosamente');
                await this.cargarComites();
            } else {
                throw new Error(data.message || 'Error al eliminar');
            }
        } catch (error) {
            console.error('❌ Error:', error);
            this.mostrarError('Error al eliminar el comité: ' + error.message);
        }
    }

    async guardarComite(e) {
        e.preventDefault();
        console.log('💾 [GUARDAR] === INICIANDO PROCESO DE GUARDADO ===');
        console.log('💾 [GUARDAR] comiteEditando:', this.comiteEditando);

        // Validar campos requeridos
        const camposRequeridos = ['nombre', 'descripcion', 'objetivo'];
        const erroresValidacion = [];

        for (const campo of camposRequeridos) {
            const elemento = document.getElementById(campo);
            if (!elemento || !elemento.value.trim()) {
                erroresValidacion.push(`El campo "${campo}" es requerido`);
            }
        }

        if (erroresValidacion.length > 0) {
            this.mostrarError('Por favor completa todos los campos requeridos:\n' + erroresValidacion.join('\n'));
            return;
        }

        const btnGuardar = document.getElementById('submitBtn');
        const submitText = document.getElementById('submitText');
        const submitSpinner = document.getElementById('submitSpinner');

        const textoOriginal = submitText.textContent;
        submitText.textContent = this.comiteEditando ? 'Actualizando...' : 'Guardando...';
        btnGuardar.disabled = true;
        if (submitSpinner) submitSpinner.classList.remove('hidden');

        try {
            let imagenUrl = '';

            // Manejar imagen - leer de input pero guardar con nombre correcto de BD
            const imagenUrlInput = document.getElementById('imagen_url');
            if (imagenUrlInput) {
                imagenUrl = imagenUrlInput.value.trim();
                console.log('🔧 [DEBUG] imagenUrl desde formulario:', imagenUrl);
            }

            // Verificar si hay archivo para enviar directamente al API
            const imagenFile = document.getElementById('imagen_file');
            let tieneArchivo = false;
            if (imagenFile && imagenFile.files[0]) {
                console.log('🔧 [DEBUG] Archivo de imagen detectado:', imagenFile.files[0].name);
                tieneArchivo = true;
            }

            const formData = new FormData();

            // Intentar con las acciones más comunes primero
            console.log('🎯 [ACCIÓN] Usando acción específica para creación');

            // Basándome en APIs PHP estándar, probar estas acciones en orden de prioridad
            const action = this.comiteEditando ? 'editar' : 'crear';

            formData.append('action', action);
            console.log('🎯 [ACCIÓN] Acción principal:', action);

            // Agregar parámetros adicionales que podrían ser necesarios
            formData.append('metodo', action);
            formData.append('operacion', action);

            if (this.comiteEditando) {
                formData.append('id', this.comiteEditando.id);
                console.log('🔧 [DEBUG] ID comité a editar:', this.comiteEditando.id);
            }

            // Recopilar datos del formulario
            const campos = ['nombre', 'descripcion', 'objetivo', 'periodicidad',
                           'miembros_activos', 'organizacion', 'estado'];

            campos.forEach(campo => {
                const elemento = document.getElementById(campo);
                if (elemento) {
                    const valor = elemento.value.trim();
                    formData.append(campo, valor);
                    console.log(`🔧 [DEBUG] ${campo}:`, valor);
                }
            });

            // Agregar imagen - priorizar archivo sobre URL
            if (tieneArchivo && imagenFile.files[0]) {
                // Enviar archivo directamente al API para que lo procese
                formData.append('imagen', imagenFile.files[0]);
                console.log('🔧 [DEBUG] imagen archivo:', imagenFile.files[0].name);
            } else if (imagenUrl) {
                // Enviar URL de imagen
                formData.append('imagen_url', imagenUrl);
                console.log('🔧 [DEBUG] imagen URL:', imagenUrl);
            }

            // Agregar coordinador_id - enviar null si está vacío para respetar la FK
            // No enviar coordinador_id vacío, dejar que el API lo maneje como NULL

            console.log('📡 [API] Enviando datos al servidor...');
            console.log('📡 [API] URL de destino:', this.apiUrl);
            console.log('📡 [API] FormData contents:');
            for (let [key, value] of formData.entries()) {
                console.log(`   ${key}: ${value}`);
            }

            // Test de conectividad antes del envío real
            console.log('🔍 [TEST] Probando conectividad con API...');
            try {
                const testResponse = await fetch(this.apiUrl + '?action=test', { method: 'GET' });
                console.log('🔍 [TEST] API test status:', testResponse.status);
            } catch (testError) {
                console.log('🔍 [TEST] API test failed:', testError.message);
            }

            // El API busca la acción en $_GET['action'], no en POST
            const urlWithAction = `${this.apiUrl}?action=${action}`;
            console.log('🌐 [URL] URL completa con acción:', urlWithAction);

            const response = await fetch(urlWithAction, {
                method: 'POST',
                body: formData
            });

            console.log('📡 [API] Response status:', response.status, response.statusText);
            console.log('📡 [API] Response headers:', Object.fromEntries(response.headers.entries()));

            const data = await response.json();
            console.log('📡 [API] Respuesta del servidor:', data);

            // Verificar si la respuesta es exitosa
            if (!response.ok) {
                console.error('❌ [API] HTTP Error:', response.status, response.statusText);
                throw new Error(`HTTP Error ${response.status}: ${response.statusText}`);
            }

            // Estrategia simplificada: verificar si aumentó el número de comités
            console.log('🔍 [ANÁLISIS] Analizando respuesta y verificando cambios...');

            if (data.success === true) {
                // Guardar conteo actual antes de recargar
                const comitesAntesDeGuardar = this.comites.length;
                console.log('📊 [VERIFICACIÓN] Comités antes:', comitesAntesDeGuardar);

                // Recargar lista para verificar si se agregó
                console.log('🔄 [RELOAD] Recargando para verificar si se guardó...');
                await this.cargarComites();

                const comitesDespuesDeGuardar = this.comites.length;
                console.log('📊 [VERIFICACIÓN] Comités después:', comitesDespuesDeGuardar);

                // Verificar si se agregó un nuevo registro
                if (!this.comiteEditando && comitesDespuesDeGuardar > comitesAntesDeGuardar) {
                    // ¡Éxito! Se agregó un nuevo comité
                    console.log('✅ [ÉXITO] Comité creado exitosamente - aumentó el conteo');
                    this.mostrarExito('Comité creado exitosamente');
                    this.resetForm();
                } else if (this.comiteEditando) {
                    // Para edición, asumir éxito si no hay errores
                    console.log('✅ [ÉXITO] Comité actualizado exitosamente');
                    this.mostrarExito('Comité actualizado exitosamente');
                    this.resetForm();
                } else {
                    // No se agregó nuevo registro
                    console.warn('⚠️ [FALLO] No se detectó creación de nuevo comité');
                    throw new Error('El comité no se guardó correctamente. La API podría no estar procesando la acción de creación.');
                }

                // Forzar actualización de la vista
                setTimeout(() => {
                    this.renderComites();
                }, 300);
            } else {
                console.error('❌ [API] Error en respuesta del servidor:', data);

                // Mostrar mensaje específico del servidor si está disponible
                let errorMsg = 'Error en la operación';
                if (data.message) {
                    errorMsg = data.message;
                } else if (data.error) {
                    errorMsg = data.error;
                } else if (data.errors && Array.isArray(data.errors)) {
                    errorMsg = data.errors.join(', ');
                }

                throw new Error(errorMsg);
            }
        } catch (error) {
            console.error('❌ [GUARDAR] Error:', error);
            this.mostrarError('Error al guardar el comité: ' + error.message);
        } finally {
            // Restaurar botón
            submitText.textContent = textoOriginal;
            btnGuardar.disabled = false;
            if (submitSpinner) submitSpinner.classList.add('hidden');
        }
    }

    resetForm() {
        // Usar la función mejorada de limpiarFormulario
        this.limpiarFormulario();

        // Restaurar texto del botón
        const submitText = document.getElementById('submitText');
        if (submitText) {
            submitText.textContent = 'Guardar Comité';
        }
    }

    cancelarEdicion() {
        this.resetForm();
    }

    async refrescarSilencioso() {
        try {
            const response = await fetch(`${this.apiUrl}?action=listar&t=${Date.now()}`);
            const data = await response.json();

            if (data.success) {
                this.comites = data.comites || [];
                this.renderComites();
            }
        } catch (error) {
            console.warn('⚠️ Error en refresco silencioso:', error);
        }
    }

    isStandardListResponse(data) {
        // Detecta si es la respuesta estándar de lista que siempre devuelve
        return data && data.success && data.comites &&
               Array.isArray(data.comites) &&
               !data.message && !data.created && !data.inserted_id;
    }

    analyzeApiResponse(data) {
        console.log('🔍 [ANÁLISIS] Datos recibidos:', data);

        // Si solo tiene 'success' y 'comites', probablemente es una respuesta de lista
        if (this.isStandardListResponse(data)) {
            console.log('🔍 [ANÁLISIS] Detectada respuesta tipo lista');
            return 'list_only';
        }

        // Si tiene campos que indican operación específica
        if (data.message || data.id || data.created || data.inserted_id || data.affected_rows) {
            console.log('🔍 [ANÁLISIS] Detectada respuesta de operación exitosa');
            return 'success';
        }

        // Respuesta ambigua
        console.log('🔍 [ANÁLISIS] Respuesta ambigua');
        return 'ambiguous';
    }

    escapeHtml(text) {
        if (!text) return '';
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    mostrarExito(mensaje) {
        this.mostrarNotificacion(mensaje, 'success');
    }

    mostrarError(mensaje) {
        this.mostrarNotificacion(mensaje, 'error');
    }

    mostrarNotificacion(mensaje, tipo) {
        const container = document.getElementById('notifications') || document.body;
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 z-50 p-4 rounded-md shadow-lg max-w-md ${
            tipo === 'success' ? 'bg-green-500 text-white' : 'bg-red-500 text-white'
        }`;

        notification.innerHTML = `
            <div class="flex justify-between items-center">
                <span>${mensaje}</span>
                <button onclick="this.parentElement.parentElement.remove()" class="ml-4 text-current opacity-70 hover:opacity-100">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        `;

        container.appendChild(notification);

        // Auto remove después de 5 segundos
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 5000);
    }

    // ======== NUEVAS FUNCIONES PARA GESTIÓN DE SOLICITUDES ========

    async cargarSolicitudesPendientes() {
        console.log('📋 [SOLICITUDES] Cargando solicitudes pendientes...');

        try {
            const response = await fetch(`${this.apiUrl}?action=listar_registros_pendientes`);
            const data = await response.json();

            console.log('📋 [SOLICITUDES] Datos recibidos:', data);

            if (data.success && data.registros) {
                this.mostrarSolicitudes(data.registros);
            } else {
                this.mostrarError('No se pudieron cargar las solicitudes');
            }
        } catch (error) {
            console.error('❌ [SOLICITUDES] Error:', error);
            this.mostrarError('Error al cargar solicitudes: ' + error.message);
        }
    }

    mostrarSolicitudes(solicitudes) {
        const solicitudesSection = document.getElementById('solicitudesSection');
        const solicitudesList = document.getElementById('solicitudesList');

        if (!solicitudesSection || !solicitudesList) {
            console.error('❌ [SOLICITUDES] Elementos DOM no encontrados');
            return;
        }

        solicitudesSection.classList.remove('hidden');

        if (solicitudes.length === 0) {
            solicitudesList.innerHTML = '<p class="text-gray-500 text-center">No hay solicitudes pendientes</p>';
            return;
        }

        let html = '<div class="space-y-4">';
        solicitudes.forEach(solicitud => {
            html += `
                <div class="border border-gray-200 rounded-lg p-4">
                    <div class="flex justify-between items-start">
                        <div class="flex-1">
                            <h4 class="font-semibold text-gray-800">${this.escapeHtml(solicitud.nombre_empresa)}</h4>
                            <p class="text-sm text-gray-600">${this.escapeHtml(solicitud.nombre_usuario)} - ${this.escapeHtml(solicitud.cargo)}</p>
                            <p class="text-sm text-gray-500">${this.escapeHtml(solicitud.email_contacto)}</p>
                            <p class="text-xs text-gray-400">Comité: ${this.escapeHtml(solicitud.comite_nombre)}</p>
                            <p class="text-xs text-gray-400">Fecha: ${new Date(solicitud.fecha_registro).toLocaleDateString()}</p>
                            ${solicitud.comentarios ? `<p class="text-sm text-gray-600 mt-2"><strong>Comentarios:</strong> ${this.escapeHtml(solicitud.comentarios)}</p>` : ''}
                        </div>
                        <div class="flex space-x-2 ml-4">
                            <button onclick="window.adminComites.aprobarSolicitud(${solicitud.id})"
                                    class="bg-green-600 text-white px-3 py-1 rounded text-sm hover:bg-green-700 transition">
                                <i class="fas fa-check mr-1"></i>Aprobar
                            </button>
                            <button onclick="window.adminComites.rechazarSolicitud(${solicitud.id})"
                                    class="bg-red-600 text-white px-3 py-1 rounded text-sm hover:bg-red-700 transition">
                                <i class="fas fa-times mr-1"></i>Rechazar
                            </button>
                        </div>
                    </div>
                </div>
            `;
        });
        html += '</div>';

        solicitudesList.innerHTML = html;
    }

    async aprobarSolicitud(solicitudId) {
        if (!confirm('¿Estás seguro de que quieres aprobar esta solicitud?')) return;

        try {
            const formData = new FormData();
            formData.append('registro_id', solicitudId);
            formData.append('aprobado_por', 1); // ID del usuario admin

            const response = await fetch(`${this.apiUrl}?action=aprobar_registro`, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                this.mostrarExito(data.message || 'Solicitud aprobada exitosamente');
                this.cargarSolicitudesPendientes(); // Recargar lista
            } else {
                this.mostrarError(data.message || 'Error al aprobar solicitud');
            }
        } catch (error) {
            console.error('❌ [APROBAR] Error:', error);
            this.mostrarError('Error al aprobar solicitud: ' + error.message);
        }
    }

    async rechazarSolicitud(solicitudId) {
        if (!confirm('¿Estás seguro de que quieres rechazar esta solicitud?')) return;

        try {
            const formData = new FormData();
            formData.append('registro_id', solicitudId);
            formData.append('aprobado_por', 1); // ID del usuario admin

            const response = await fetch(`${this.apiUrl}?action=rechazar_registro`, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                this.mostrarExito(data.message || 'Solicitud rechazada');
                this.cargarSolicitudesPendientes(); // Recargar lista
            } else {
                this.mostrarError(data.message || 'Error al rechazar solicitud');
            }
        } catch (error) {
            console.error('❌ [RECHAZAR] Error:', error);
            this.mostrarError('Error al rechazar solicitud: ' + error.message);
        }
    }

    enviarMensajeComite() {
        // Abrir modal o redirigir a dashboard para enviar mensajes
        const mensaje = prompt('Ingresa el mensaje para enviar a todos los miembros del comité:');
        if (!mensaje) return;

        // Por ahora mostrar un mensaje de que se implementará
        this.mostrarExito('Función de mensajería se integrará con dashboard.html');

        // TODO: Integrar con el sistema de mensajería del dashboard
        console.log('📧 [MENSAJE] Mensaje a enviar:', mensaje);
    }
}

// Hacer disponible globalmente
window.AdminComitesManager = AdminComitesManager;