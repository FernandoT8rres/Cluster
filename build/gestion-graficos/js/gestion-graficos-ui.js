        // Fuente de datos
        if (this.elements.dataSource) {
            config.fuente = this.elements.dataSource.value;
        }
        
        // Período
        if (this.elements.periodRange) {
            config.periodo = parseInt(this.elements.periodRange.value);
        }
        
        // Título
        if (this.elements.chartTitle) {
            config.titulo = this.elements.chartTitle.value || 'Sin título';
        }
        
        // Color primario
        if (this.elements.primaryColor) {
            config.color_primario = this.elements.primaryColor.value;
        }
        
        // Opciones booleanas
        config.animaciones = this.elements.showAnimation ? this.elements.showAnimation.checked : true;
        config.mostrar_grilla = this.elements.showGrid ? this.elements.showGrid.checked : true;
        config.mostrar_tooltips = this.elements.showTooltips ? this.elements.showTooltips.checked : true;
        config.mostrar_leyenda = this.elements.showLegend ? this.elements.showLegend.checked : false;
        
        // Filtros
        config.filtros = this.getFiltrosFromUI();
        
        this.core.setCurrentConfig(config);
        return config;
    }
    
    /**
     * Eventos de cambio de tipo de gráfico
     */
    onChartTypeChange() {
        this.updateConfigFromUI();
        this.updatePreviewDebounced();
    }
    
    /**
     * Eventos de cambio de fuente de datos
     */
    onDataSourceChange() {
        this.updateConfigFromUI();
        this.updateFilterOptions();
        this.updatePreviewDebounced();
        
        // Mostrar panel de datos personalizados si es necesario
        if (this.elements.dataSource.value === 'custom') {
            this.showCustomDataPanel();
        } else {
            this.closeCustomDataPanel();
        }
    }
    
    /**
     * Eventos de cambio de período
     */
    onPeriodChange() {
        this.updateConfigFromUI();
        this.updatePreviewDebounced();
    }
    
    /**
     * Eventos de cambio de título
     */
    onTitleChange() {
        this.updateConfigFromUI();
        
        // Actualizar título en tiempo real si hay gráfico
        if (this.core.currentChart) {
            this.core.currentChart.options.plugins.title.text = this.elements.chartTitle.value;
            this.core.currentChart.update('none');
        }
    }
    
    /**
     * Eventos de cambio de color
     */
    onColorChange() {
        this.updateConfigFromUI();
        this.updateColorPresets(this.elements.primaryColor.value);
        this.updatePreviewDebounced();
    }
    
    /**
     * Eventos de cambio de opciones
     */
    onOptionChange() {
        this.updateConfigFromUI();
        this.updatePreviewDebounced();
    }
    
    /**
     * Eventos de previsualización
     */
    async onPreview() {
        try {
            this.showLoading(true);
            
            this.updateConfigFromUI();
            
            // Crear o actualizar gráfico
            await this.core.applyCurrentConfig('previewChart');
            
            this.core.showNotification('Gráfico actualizado', 'success');
            
        } catch (error) {
            console.error('Error en previsualización:', error);
            this.core.showNotification('Error al generar gráfico: ' + error.message, 'error');
        } finally {
            this.showLoading(false);
        }
    }
    
    /**
     * Eventos de actualización de datos
     */
    async onRefreshData() {
        try {
            this.showLoading(true);
            
            if (this.core.currentChart) {
                await this.core.updateChartData('previewChart');
                this.core.showNotification('Datos actualizados', 'success');
            } else {
                await this.onPreview();
            }
            
        } catch (error) {
            console.error('Error actualizando datos:', error);
            this.core.showNotification('Error al actualizar datos: ' + error.message, 'error');
        } finally {
            this.showLoading(false);
        }
    }
    
    /**
     * Eventos de descarga
     */
    onDownloadChart() {
        try {
            if (!this.core.currentChart) {
                this.core.showNotification('Primero debes generar un gráfico', 'warning');
                return;
            }
            
            const config = this.core.getCurrentConfig();
            const filename = `${config.titulo.replace(/[^a-z0-9]/gi, '_').toLowerCase()}_${new Date().getTime()}.png`;
            
            this.core.exportChart('previewChart', filename);
            this.core.showNotification('Gráfico descargado', 'success');
            
        } catch (error) {
            console.error('Error descargando gráfico:', error);
            this.core.showNotification('Error al descargar: ' + error.message, 'error');
        }
    }
    
    /**
     * Eventos de pantalla completa
     */
    onFullscreen() {
        const canvas = document.getElementById('previewChart');
        if (!canvas) return;
        
        if (canvas.requestFullscreen) {
            canvas.requestFullscreen();
        } else if (canvas.webkitRequestFullscreen) {
            canvas.webkitRequestFullscreen();
        } else if (canvas.mozRequestFullScreen) {
            canvas.mozRequestFullScreen();
        }
    }
    
    /**
     * Eventos de guardar configuración
     */
    async onSaveConfig() {
        const config = this.updateConfigFromUI();
        
        // Mostrar modal para pedir nombre y descripción
        const result = await this.showSaveConfigDialog();
        
        if (result) {
            try {
                await this.core.guardarConfiguracion(
                    result.nombre,
                    result.descripcion,
                    config,
                    result.esPredeterminada
                );
                
                this.core.showNotification('Configuración guardada exitosamente', 'success');
                this.loadSavedConfigurations();
                
            } catch (error) {
                console.error('Error guardando configuración:', error);
                this.core.showNotification('Error al guardar: ' + error.message, 'error');
            }
        }
    }
    
    /**
     * Eventos de nueva configuración
     */
    onNewConfiguration() {
        if (confirm('¿Deseas crear una nueva configuración? Se perderán los cambios no guardados.')) {
            this.core.currentConfig = this.core.getDefaultConfig();
            this.updateUIFromConfig();
            this.updatePreviewDebounced();
        }
    }
    
    /**
     * Eventos de click en preset de color
     */
    onColorPresetClick(event) {
        const color = event.target.dataset.color;
        if (color && this.elements.primaryColor) {
            this.elements.primaryColor.value = color;
            this.updateColorPresets(color);
            this.onColorChange();
        }
    }
    
    /**
     * Eventos de teclado
     */
    onKeyDown(event) {
        // Ctrl/Cmd + S para guardar
        if ((event.ctrlKey || event.metaKey) && event.key === 's') {
            event.preventDefault();
            this.onSaveConfig();
        }
        
        // Ctrl/Cmd + R para actualizar
        if ((event.ctrlKey || event.metaKey) && event.key === 'r') {
            event.preventDefault();
            this.onRefreshData();
        }
        
        // Enter para previsualizar
        if (event.key === 'Enter' && event.target.tagName !== 'TEXTAREA') {
            if (event.target.closest('.config-panel')) {
                event.preventDefault();
                this.onPreview();
            }
        }
    }
    
    /**
     * Mostrar/ocultar loading
     */
    showLoading(show) {
        if (this.elements.chartLoading) {
            this.elements.chartLoading.style.display = show ? 'block' : 'none';
        }
    }
    
    /**
     * Función debounce para evitar demasiadas actualizaciones
     */
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
    
    /**
     * Actualizar presets de color
     */
    updateColorPresets(selectedColor) {
        this.elements.colorPresets.forEach(preset => {
            preset.classList.remove('active');
            if (preset.dataset.color === selectedColor) {
                preset.classList.add('active');
            }
        });
    }
    
    /**
     * Actualizar opciones de filtros según la fuente de datos
     */
    updateFilterOptions() {
        if (!this.elements.filtrosContainer) return;
        
        const fuente = this.elements.dataSource ? this.elements.dataSource.value : 'empresas';
        
        // Limpiar filtros existentes
        this.filtros = [];
        this.elements.filtrosContainer.innerHTML = '';
        
        // Agregar filtros según la fuente
        const filtrosDisponibles = this.getFiltrosDisponibles(fuente);
        
        if (filtrosDisponibles.length > 0) {
            this.elements.agregarFiltro.style.display = 'block';
        } else {
            this.elements.agregarFiltro.style.display = 'none';
        }
    }
    
    /**
     * Obtener filtros disponibles por fuente
     */
    getFiltrosDisponibles(fuente) {
        const filtros = {
            empresas: [
                { id: 'estado', nombre: 'Estado', opciones: ['activo', 'inactivo', 'pendiente'] },
                { id: 'sector', nombre: 'Sector', opciones: ['tecnologia', 'manufactura', 'servicios', 'retail'] }
            ],
            usuarios: [
                { id: 'rol', nombre: 'Rol', opciones: ['admin', 'empleado', 'supervisor'] },
                { id: 'estado', nombre: 'Estado', opciones: ['activo', 'inactivo', 'pendiente'] }
            ],
            eventos: [
                { id: 'tipo', nombre: 'Tipo', opciones: ['conferencia', 'taller', 'reunion', 'seminario'] },
                { id: 'estado', nombre: 'Estado', opciones: ['programado', 'en_curso', 'finalizado', 'cancelado'] }
            ],
            comites: []
        };
        
        return filtros[fuente] || [];
    }
    
    /**
     * Agregar nuevo filtro
     */
    onAddFilter() {
        const fuente = this.elements.dataSource ? this.elements.dataSource.value : 'empresas';
        const filtrosDisponibles = this.getFiltrosDisponibles(fuente);
        
        if (filtrosDisponibles.length === 0) {
            this.core.showNotification('No hay filtros disponibles para esta fuente de datos', 'info');
            return;
        }
        
        const filtroId = `filtro_${Date.now()}`;
        const filtro = {
            id: filtroId,
            campo: filtrosDisponibles[0].id,
            operador: 'igual',
            valor: ''
        };
        
        this.filtros.push(filtro);
        this.renderFiltro(filtro, filtrosDisponibles);
    }
    
    /**
     * Renderizar filtro en la UI
     */
    renderFiltro(filtro, filtrosDisponibles) {
        const filtroElement = document.createElement('div');
        filtroElement.className = 'filtro-item';
        filtroElement.dataset.filtroId = filtro.id;
        
        const campoOptions = filtrosDisponibles.map(f => 
            `<option value="${f.id}" ${f.id === filtro.campo ? 'selected' : ''}>${f.nombre}</option>`
        ).join('');
        
        const valorOptions = this.getValorOptions(filtro.campo, filtrosDisponibles);
        
        filtroElement.innerHTML = `
            <div class="filtro-header">
                <span class="filtro-title">Filtro</span>
                <button type="button" class="filtro-remove" onclick="window.clústerGraficosUI?.removeFiltro('${filtro.id}')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="filtro-controls">
                <div class="form-group">
                    <label>Campo:</label>
                    <select class="form-control filtro-campo" onchange="window.clústerGraficosUI?.onFiltroFieldChange('${filtro.id}', this.value)">
                        ${campoOptions}
                    </select>
                </div>
                <div class="form-group">
                    <label>Operador:</label>
                    <select class="form-control filtro-operador">
                        <option value="igual" ${filtro.operador === 'igual' ? 'selected' : ''}>Igual a</option>
                        <option value="diferente" ${filtro.operador === 'diferente' ? 'selected' : ''}>Diferente de</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Valor:</label>
                    <select class="form-control filtro-valor">
                        ${valorOptions}
                    </select>
                </div>
            </div>
        `;
        
        this.elements.filtrosContainer.appendChild(filtroElement);
    }
    
    /**
     * Obtener opciones de valor para un campo
     */
    getValorOptions(campo, filtrosDisponibles) {
        const filtroConfig = filtrosDisponibles.find(f => f.id === campo);
        if (!filtroConfig) return '<option value="">Seleccionar...</option>';
        
        return '<option value="">Todos</option>' + 
               filtroConfig.opciones.map(opcion => 
                   `<option value="${opcion}">${opcion.charAt(0).toUpperCase() + opcion.slice(1)}</option>`
               ).join('');
    }
    
    /**
     * Cambio de campo en filtro
     */
    onFiltroFieldChange(filtroId, nuevoCampo) {
        const filtro = this.filtros.find(f => f.id === filtroId);
        if (filtro) {
            filtro.campo = nuevoCampo;
            filtro.valor = '';
            
            // Actualizar opciones de valor
            const filtroElement = document.querySelector(`[data-filtro-id="${filtroId}"]`);
            const valorSelect = filtroElement.querySelector('.filtro-valor');
            const fuente = this.elements.dataSource.value;
            const filtrosDisponibles = this.getFiltrosDisponibles(fuente);
            
            valorSelect.innerHTML = this.getValorOptions(nuevoCampo, filtrosDisponibles);
        }
    }
    
    /**
     * Remover filtro
     */
    removeFiltro(filtroId) {
        this.filtros = this.filtros.filter(f => f.id !== filtroId);
        
        const filtroElement = document.querySelector(`[data-filtro-id="${filtroId}"]`);
        if (filtroElement) {
            filtroElement.remove();
        }
        
        this.updateConfigFromUI();
        this.updatePreviewDebounced();
    }
    
    /**
     * Obtener filtros desde la UI
     */
    getFiltrosFromUI() {
        const filtros = {};
        
        this.filtros.forEach(filtro => {
            const filtroElement = document.querySelector(`[data-filtro-id="${filtro.id}"]`);
            if (filtroElement) {
                const campo = filtroElement.querySelector('.filtro-campo').value;
                const operador = filtroElement.querySelector('.filtro-operador').value;
                const valor = filtroElement.querySelector('.filtro-valor').value;
                
                if (valor && valor !== '') {
                    filtros[campo] = valor;
                }
            }
        });
        
        return filtros;
    }
    
    /**
     * Mostrar panel de datos personalizados
     */
    showCustomDataPanel() {
        if (this.elements.customDataPanel) {
            this.elements.customDataPanel.style.display = 'block';
        }
    }
    
    /**
     * Cerrar panel de datos personalizados
     */
    closeCustomDataPanel() {
        if (this.elements.customDataPanel) {
            this.elements.customDataPanel.style.display = 'none';
        }
    }
    
    /**
     * Validar datos personalizados
     */
    onValidateCustomData() {
        if (!this.elements.customDataEditor) return;
        
        const jsonString = this.elements.customDataEditor.value.trim();
        
        if (!jsonString) {
            this.core.showNotification('Por favor ingresa datos para validar', 'warning');
            return;
        }
        
        try {
            const data = JSON.parse(jsonString);
            
            if (!Array.isArray(data)) {
                throw new Error('Los datos deben ser un array');
            }
            
            if (data.length === 0) {
                throw new Error('El array no puede estar vacío');
            }
            
            // Validar estructura
            data.forEach((item, index) => {
                if (!item.hasOwnProperty('label') && !item.hasOwnProperty('mes') && !item.hasOwnProperty('nombre')) {
                    throw new Error(`Elemento ${index + 1}: debe tener 'label', 'mes' o 'nombre'`);
                }
                if (!item.hasOwnProperty('valor')) {
                    throw new Error(`Elemento ${index + 1}: debe tener 'valor'`);
                }
                if (isNaN(parseFloat(item.valor))) {
                    throw new Error(`Elemento ${index + 1}: 'valor' debe ser numérico`);
                }
            });
            
            this.renderDataPreview(data);
            this.core.showNotification('Datos válidos', 'success');
            
        } catch (error) {
            this.clearDataPreview();
            this.core.showNotification('Error en datos: ' + error.message, 'error');
        }
    }
    
    /**
     * Cargar datos de ejemplo
     */
    onLoadExample() {
        if (!this.elements.customDataEditor) return;
        
        const exampleData = [
            { "mes": "Ene", "valor": 45 },
            { "mes": "Feb", "valor": 52 },
            { "mes": "Mar", "valor": 48 },
            { "mes": "Abr", "valor": 61 },
            { "mes": "May", "valor": 55 },
            { "mes": "Jun", "valor": 67 }
        ];
        
        this.elements.customDataEditor.value = JSON.stringify(exampleData, null, 2);
        this.renderDataPreview(exampleData);
        this.core.showNotification('Datos de ejemplo cargados', 'info');
    }
    
    /**
     * Renderizar vista previa de datos
     */
    renderDataPreview(data) {
        if (!this.elements.dataPreview) return;
        
        let html = '<h5>Vista Previa de Datos</h5>';
        html += '<table class="data-preview-table">';
        html += '<thead><tr>';
        
        // Headers
        if (data.length > 0) {
            Object.keys(data[0]).forEach(key => {
                html += `<th>${key}</th>`;
            });
        }
        
        html += '</tr></thead><tbody>';
        
        // Data rows
        data.forEach(item => {
            html += '<tr>';
            Object.values(item).forEach(value => {
                html += `<td>${value}</td>`;
            });
            html += '</tr>';
        });
        
        html += '</tbody></table>';
        html += `<p class="mt-2 text-sm text-gray-600">Total: ${data.length} registros</p>`;
        
        this.elements.dataPreview.innerHTML = html;
    }
    
    /**
     * Limpiar vista previa de datos
     */
    clearDataPreview() {
        if (this.elements.dataPreview) {
            this.elements.dataPreview.innerHTML = '<p class="text-gray-500">Los datos validados aparecerán aquí...</p>';
        }
    }
    
    /**
     * Cargar configuraciones guardadas
     */
    async loadSavedConfigurations() {
        if (!this.elements.configList) return;
        
        try {
            const configuraciones = await this.core.getConfiguraciones();
            this.renderSavedConfigurations(configuraciones);
            
        } catch (error) {
            console.error('Error cargando configuraciones:', error);
            this.elements.configList.innerHTML = '<p class="text-gray-500">Error cargando configuraciones</p>';
        }
    }
    
    /**
     * Renderizar configuraciones guardadas
     */
    renderSavedConfigurations(configuraciones) {
        if (!this.elements.configList) return;
        
        if (configuraciones.length === 0) {
            this.elements.configList.innerHTML = '<p class="text-gray-500">No hay configuraciones guardadas</p>';
            return;
        }
        
        let html = '';
        
        configuraciones.forEach(config => {
            const isDefault = config.es_predeterminada;
            const fecha = new Date(config.fecha_actualizacion).toLocaleDateString();
            
            html += `
                <div class="config-item ${isDefault ? 'active' : ''}" data-config-id="${config.id}">
                    <div class="config-info">
                        <div class="config-name">
                            ${config.nombre}
                            ${isDefault ? '<span class="badge badge-primary ml-2">Por defecto</span>' : ''}
                        </div>
                        <div class="config-details">
                            ${config.descripcion || 'Sin descripción'} • ${fecha}
                        </div>
                    </div>
                    <div class="config-actions">
                        <button type="button" class="btn-icon" onclick="window.clústerGraficosUI?.loadConfiguration(${config.id})" title="Cargar">
                            <i class="fas fa-upload"></i>
                        </button>
                        ${!isDefault ? `
                            <button type="button" class="btn-icon" onclick="window.clústerGraficosUI?.deleteConfiguration(${config.id})" title="Eliminar">
                                <i class="fas fa-trash"></i>
                            </button>
                        ` : ''}
                    </div>
                </div>
            `;
        });
        
        this.elements.configList.innerHTML = html;
    }
    
    /**
     * Cargar configuración
     */
    async loadConfiguration(configId) {
        try {
            const response = await fetch(`${this.core.API_BASE}?endpoint=configuracion&id=${configId}`);
            const result = await response.json();
            
            if (result.success) {
                this.core.setCurrentConfig(result.data.configuracion);
                this.updateUIFromConfig();
                this.updatePreviewDebounced();
                
                this.core.showNotification('Configuración cargada', 'success');
            } else {
                throw new Error(result.error);
            }
            
        } catch (error) {
            console.error('Error cargando configuración:', error);
            this.core.showNotification('Error al cargar configuración: ' + error.message, 'error');
        }
    }
    
    /**
     * Eliminar configuración
     */
    async deleteConfiguration(configId) {
        if (!confirm('¿Estás seguro de que deseas eliminar esta configuración?')) {
            return;
        }
        
        try {
            await this.core.eliminarConfiguracion(configId);
            this.loadSavedConfigurations();
            this.core.showNotification('Configuración eliminada', 'success');
            
        } catch (error) {
            console.error('Error eliminando configuración:', error);
            this.core.showNotification('Error al eliminar: ' + error.message, 'error');
        }
    }
    
    /**
     * Mostrar diálogo para guardar configuración
     */
    async showSaveConfigDialog() {
        return new Promise((resolve) => {
            const modal = document.createElement('div');
            modal.className = 'modal';
            modal.style.display = 'flex';
            
            modal.innerHTML = `
                <div class="modal-content">
                    <div class="modal-header">
                        <h3>Guardar Configuración</h3>
                        <button class="modal-close" onclick="this.closest('.modal').remove();">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="configName">Nombre de la configuración:</label>
                            <input type="text" id="configName" class="form-control" placeholder="Ej: Gráfico de ventas Q4" required>
                        </div>
                        <div class="form-group">
                            <label for="configDescription">Descripción (opcional):</label>
                            <textarea id="configDescription" class="form-control" rows="3" placeholder="Describe el propósito de esta configuración..."></textarea>
                        </div>
                        <div class="checkbox-group">
                            <label class="checkbox-item">
                                <input type="checkbox" id="configDefault">
                                <span class="checkbox-custom"></span>
                                <span>Establecer como configuración por defecto</span>
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn-outline" onclick="this.closest('.modal').remove(); resolve(null);">Cancelar</button>
                        <button class="btn-primary" onclick="window.clústerGraficosUI?.submitSaveConfig(resolve, this)">Guardar</button>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
            document.getElementById('configName').focus();
            
            // Manejar click fuera del modal
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    modal.remove();
                    resolve(null);
                }
            });
        });
    }
    
    /**
     * Enviar datos de guardado de configuración
     */
    submitSaveConfig(resolve, button) {
        const modal = button.closest('.modal');
        const nombre = modal.querySelector('#configName').value.trim();
        const descripcion = modal.querySelector('#configDescription').value.trim();
        const esPredeterminada = modal.querySelector('#configDefault').checked;
        
        if (!nombre) {
            this.core.showNotification('El nombre es requerido', 'warning');
            return;
        }
        
        modal.remove();
        
        resolve({
            nombre,
            descripcion,
            esPredeterminada
        });
    }
}

// Hacer disponible globalmente para eventos onclick
window.ClústerGraficosUI = ClústerGraficosUI;