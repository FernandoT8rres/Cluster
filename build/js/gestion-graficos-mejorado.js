// ==================== SISTEMA DE GESTIÓN DE GRÁFICOS MEJORADO ====================
// Archivo: gestion-graficos-mejorado.js
// Parte 1: Estructura básica y configuración inicial

/**
 * Clase principal para gestionar gráficos de forma avanzada
 */
class GestionGraficos {
    constructor() {
        this.currentChart = null;
        this.currentData = [];
        this.currentConfig = {
            type: 'line',
            dataSource: 'empresas',
            period: 6,
            title: 'Empresas Registradas',
            color: '#3B82F6',
            animation: true,
            grid: true,
            tooltips: true,
            legend: false
        };
        this.init();
    }

    /**
     * Inicializar el sistema
     */
    init() {
        console.log('🚀 Iniciando sistema de gestión de gráficos...');
        this.setupBasicEventListeners();
        this.initializeTabs();
        this.loadInitialData();
        console.log('✅ Sistema de gestión de gráficos iniciado');
    }

    /**
     * Configurar event listeners básicos
     */
    setupBasicEventListeners() {
        // Navegación principal
        const menuAcciones = document.getElementById('menuAcciones');
        if (menuAcciones) {
            menuAcciones.addEventListener('click', () => this.toggleActionMenu());
        }

        // Cargar datos reales
        const cargarDatos = document.getElementById('cargarDatosReales');
        if (cargarDatos) {
            cargarDatos.addEventListener('click', () => this.cargarDatosReales());
        }

        // Previsualizar gráfico
        const previsualizar = document.getElementById('previsualizarGrafico');
        if (previsualizar) {
            previsualizar.addEventListener('click', () => this.previsualizarGrafico());
        }

        // Guardar configuración
        const guardar = document.getElementById('guardarConfig');
        if (guardar) {
            guardar.addEventListener('click', () => this.guardarConfiguracion());
        }

        // Cerrar dropdown al hacer clic fuera
        document.addEventListener('click', (e) => {
            if (!e.target.closest('#menuAcciones')) {
                this.hideActionMenu();
            }
        });

        console.log('✅ Event listeners básicos configurados');
    }

    /**
     * Mostrar/ocultar menú de acciones
     */
    toggleActionMenu() {
        const dropdown = document.getElementById('menuAccionesDropdown');
        if (dropdown) {
            dropdown.classList.toggle('hidden');
        }
    }

    /**
     * Ocultar menú de acciones
     */
    hideActionMenu() {
        const dropdown = document.getElementById('menuAccionesDropdown');
        if (dropdown) {
            dropdown.classList.add('hidden');
        }
    }

    /**
     * Cargar datos iniciales
     */
    async loadInitialData() {
        try {
            console.log('📊 Cargando datos iniciales...');
            this.showLoading();
            await this.cargarDatosReales();
            this.updateDataStatus('Datos cargados correctamente', 'success');
        } catch (error) {
            console.error('Error cargando datos iniciales:', error);
            this.loadSampleData();
            this.updateDataStatus('Usando datos de ejemplo', 'warning');
        } finally {
            this.hideLoading();
        }
    }

    /**
     * Mostrar indicador de carga
     */
    showLoading() {
        const loading = document.getElementById('chartLoading');
        if (loading) {
            loading.style.display = 'flex';
        }
        
        // Actualizar estado
        this.updateDataStatus('Cargando datos...', 'info');
    }

    /**
     * Ocultar indicador de carga
     */
    hideLoading() {
        const loading = document.getElementById('chartLoading');
        if (loading) {
            loading.style.display = 'none';
        }
    }

    /**
     * Actualizar estado de los datos
     */
    updateDataStatus(mensaje, tipo) {
        const statusText = document.getElementById('dataStatusText');
        const statusIcon = document.getElementById('dataStatusIcon');
        
        if (statusText) {
            statusText.textContent = mensaje;
        }
        
        if (statusIcon) {
            const colorClass = {
                'success': 'bg-green-500',
                'warning': 'bg-yellow-500', 
                'error': 'bg-red-500',
                'info': 'bg-blue-500'
            }[tipo] || 'bg-blue-500';
            
            statusIcon.className = `w-2 h-2 rounded-full ${colorClass}`;
        }
    }

    /**
     * Cargar datos reales desde la API
     */
    async cargarDatosReales() {
        try {
            console.log(`📊 Cargando datos de: ${this.currentConfig.dataSource}`);
            this.showLoading();
            
            const endpoint = `./api/estadisticas_simple.php?action=${this.currentConfig.dataSource}_historico`;
            console.log(`🔗 Endpoint: ${endpoint}`);
            
            const response = await fetch(endpoint);
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const result = await response.json();
            
            if (result.success && result.data && result.data.length > 0) {
                this.currentData = result.data;
                this.updateDataInfo(result.data.length);
                this.updateDataStatus('Datos reales cargados', 'success');
                this.previsualizarGrafico();
                
                this.mostrarNotificacion('Datos cargados correctamente desde la API', 'success');
                console.log(`✅ ${result.data.length} registros cargados`);
            } else {
                throw new Error(result.message || 'No se encontraron datos');
            }
            
        } catch (error) {
            console.warn('⚠️ Error cargando datos reales:', error.message);
            this.loadSampleData();
            this.updateDataStatus('Error - usando datos de ejemplo', 'error');
            this.mostrarNotificacion('Error cargando datos reales, usando datos de ejemplo', 'warning');
        } finally {
            this.hideLoading();
        }
    }

    /**
     * Cargar datos de ejemplo
     */
    loadSampleData() {
        console.log('📋 Generando datos de ejemplo...');
        
        const meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul'];
        const data = [];
        let total = Math.floor(Math.random() * 10) + 15; // Entre 15-25 inicial
        
        for (let i = 0; i < meses.length; i++) {
            const nuevas = Math.floor(Math.random() * 6) + 2; // Entre 2-7 nuevas
            total += nuevas;
            
            data.push({
                mes: meses[i],
                empresas: total,
                valor: total,
                nuevas: nuevas,
                categoria: `Cat-${String.fromCharCode(65 + (i % 3))}` // A, B, C
            });
        }
        
        this.currentData = data;
        this.updateDataInfo(data.length);
        this.updateDataStatus('Datos de ejemplo generados', 'info');
        this.previsualizarGrafico();
        
        console.log(`✅ ${data.length} registros de ejemplo generados`);
    }

    /**
     * Actualizar información de datos
     */
    updateDataInfo(count) {
        const recordCount = document.getElementById('recordCount');
        const lastUpdate = document.getElementById('lastDataUpdate');
        
        if (recordCount) {
            recordCount.textContent = count;
        }
        
        if (lastUpdate) {
            lastUpdate.textContent = new Date().toLocaleString('es-ES', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit'
            });
        }
    }

    /**
     * Actualizar datos desde la fuente actual
     */
    async actualizarDatos() {
        if (this.currentConfig.dataSource === 'custom') {
            this.mostrarNotificacion('Los datos personalizados no se pueden actualizar automáticamente', 'info');
            return;
        }
        
        console.log('🔄 Actualizando datos...');
        
        try {
            this.updateDataStatus('Actualizando...', 'info');
            await this.cargarDatosReales();
        } catch (error) {
            this.updateDataStatus('Error al actualizar', 'error');
    /**
     * Previsualizar gráfico con configuración actual
     */
    previsualizarGrafico() {
        const canvas = document.getElementById('previewChart');
        if (!canvas) {
            console.warn('⚠️ Canvas de preview no encontrado');
            return;
        }
        
        if (!this.currentData.length) {
            console.warn('⚠️ No hay datos para previsualizar');
            this.mostrarNotificacion('No hay datos disponibles para el gráfico', 'warning');
            return;
        }

        console.log(`📊 Previsualizando gráfico tipo: ${this.currentConfig.type}`);

        // Destruir gráfico anterior
        if (this.currentChart) {
            this.currentChart.destroy();
            this.currentChart = null;
        }

        // Preparar datos según el tipo de gráfico
        const labels = this.currentData.map(item => item.mes || item.label || item.etiqueta || 'Sin etiqueta');
        const values = this.currentData.map(item => {
            return item.valor || item.empresas || item.count || item.usuarios || 0;
        });

        console.log(`📋 Datos preparados: ${labels.length} etiquetas, valores: [${values.slice(0, 3).join(', ')}...]`);

        // Configuración del gráfico
        const config = {
            type: this.currentConfig.type,
            data: {
                labels: labels,
                datasets: [{
                    label: this.currentConfig.title,
                    data: values,
                    borderColor: this.currentConfig.color,
                    backgroundColor: this.hexToRgba(this.currentConfig.color, 0.1),
                    borderWidth: 3,
                    fill: this.currentConfig.type === 'line' || this.currentConfig.type === 'area',
                    tension: 0.4,
                    pointBackgroundColor: this.currentConfig.color,
                    pointBorderColor: '#FFFFFF',
                    pointBorderWidth: 2,
                    pointRadius: 6,
                    pointHoverRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: this.currentConfig.animation ? {
                    duration: 2000,
                    easing: 'easeInOutQuart'
                } : false,
                plugins: {
                    legend: {
                        display: this.currentConfig.legend
                    },
                    tooltip: {
                        enabled: this.currentConfig.tooltips,
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleColor: '#FFFFFF',
                        bodyColor: '#FFFFFF',
                        borderColor: this.currentConfig.color,
                        borderWidth: 1
                    }
                },
                scales: {
                    x: {
                        display: true,
                        grid: { 
                            display: this.currentConfig.grid,
                            color: 'rgba(148, 163, 184, 0.1)' 
                        },
                        ticks: { color: '#64748B' }
                    },
                    y: {
                        display: true,
                        grid: { 
                            display: this.currentConfig.grid,
                            color: 'rgba(148, 163, 184, 0.1)' 
                        },
                        ticks: { color: '#64748B' },
                        beginAtZero: true
                    }
                }
            }
        };

        try {
            // Mostrar preview del gráfico
            this.mostrarPreviewDatos(data);
            
            // Mostrar estadísticas
            if (stats) this.mostrarEstadisticasDatos(data, stats);
            
            this.mostrarNotificacion('Datos JSON válidos ✅', 'success');
            console.log(`✅ ${data.length} registros validados correctamente`);
            
        } catch (error) {
            console.error('❌ Error validando JSON:', error.message);
            
            preview.innerHTML = `
                <div class="text-center py-8">
                    <i class="fas fa-exclamation-triangle text-4xl text-red-400 mb-3"></i>
                    <p class="text-red-600 font-medium">Error en los datos JSON</p>
                    <p class="text-red-500 text-sm mt-1">${error.message}</p>
                </div>
            `;
            
            if (stats) stats.style.display = 'none';
            this.mostrarNotificacion(`Error en JSON: ${error.message}`, 'error');
        }
    };

    /**
     * Mostrar preview de los datos en un mini-gráfico
     */
    GestionGraficos.prototype.mostrarPreviewDatos = function(data) {
        console.log('📊 Generando preview de datos...');
        
        const preview = document.getElementById('dataPreview');
        if (!preview) return;
        
        // Limpiar preview anterior
        preview.innerHTML = '';
        
        // Crear canvas para mini-gráfico
        const canvas = document.createElement('canvas');
        canvas.width = 300;
        canvas.height = 150;
        canvas.style.maxWidth = '100%';
        
        preview.appendChild(canvas);
        
        // Preparar datos
        const labels = data.map(item => item.mes || item.label || item.etiqueta || 'Sin etiqueta');
        const values = data.map(item => item.valor || item.count || item.empresas || 0);
        
        // Crear mini-gráfico
        try {
            new Chart(canvas, {
                type: this.currentConfig.type,
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Vista Previa',
                        data: values,
                        borderColor: this.currentConfig.color,
                        backgroundColor: this.hexToRgba(this.currentConfig.color, 0.1),
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { 
                        legend: { display: false },
                        tooltip: { enabled: false }
                    },
                    scales: { 
                        x: { display: false },
                        y: { display: false }
                    },
                    animation: { duration: 1000 }
                }
            });
            
            console.log('✅ Preview generado');
        } catch (error) {
            console.error('Error generando preview:', error);
            preview.innerHTML = '<p class="text-red-500 text-center">Error generando preview</p>';
        }
    };

    /**
     * Mostrar estadísticas de los datos
     */
    GestionGraficos.prototype.mostrarEstadisticasDatos = function(data, statsElement) {
        if (!statsElement) return;
        
        const values = data.map(item => item.valor || item.count || item.empresas || 0);
        const minValue = Math.min(...values);
        const maxValue = Math.max(...values);
        const fields = Object.keys(data[0]).length;
        
        const statsRecords = document.getElementById('statsRecords');
        const statsFields = document.getElementById('statsFields');
        const statsRange = document.getElementById('statsRange');
        
        if (statsRecords) statsRecords.textContent = data.length;
        if (statsFields) statsFields.textContent = fields;
        if (statsRange) statsRange.textContent = `${minValue} - ${maxValue}`;
        
        statsElement.style.display = 'block';
        
        console.log(`📈 Estadísticas: ${data.length} registros, ${fields} campos, rango: ${minValue}-${maxValue}`);
    };

    /**
     * Guardar datos personalizados
     */
    GestionGraficos.prototype.guardarDatosPersonalizados = function() {
        const editor = document.getElementById('customDataEditor');
        if (!editor) return;
        
        try {
            const data = JSON.parse(editor.value);
            
            if (!Array.isArray(data) || data.length === 0) {
                throw new Error('Datos inválidos');
            }
            
            // Guardar datos
            this.currentData = data;
            this.currentConfig.dataSource = 'custom';
            
            // Actualizar información
            this.updateDataInfo(data.length);
            this.updateDataStatus('Datos personalizados aplicados', 'success');
            
            // Previsualizar gráfico
            this.previsualizarGrafico();
            
            // Cerrar panel
            this.cerrarEditorDatos();
            
            this.mostrarNotificacion('Datos personalizados guardados y aplicados', 'success');
            console.log('✅ Datos personalizados guardados');
            
        } catch (error) {
            this.mostrarNotificacion('Error al guardar datos: ' + error.message, 'error');
        }
    };

    /**
     * Guardar configuración completa
     */
    GestionGraficos.prototype.guardarConfiguracion = function() {
        const config = {
            ...this.currentConfig,
            data: this.currentData,
            timestamp: new Date().toISOString()
        };
        
        // Guardar en localStorage
        const configs = JSON.parse(localStorage.getItem('graficos_configuraciones') || '[]');
        const configName = prompt('Nombre para esta configuración:', `Configuración ${configs.length + 1}`);
        
        if (configName) {
            configs.push({
                name: configName,
                config: config
            });
            
            localStorage.setItem('graficos_configuraciones', JSON.stringify(configs));
            this.mostrarNotificacion('Configuración guardada correctamente', 'success');
            console.log('💾 Configuración guardada:', configName);
        }
    };

    /**
     * Inicializar editor de tabla (placeholder)
     */
    GestionGraficos.prototype.initializeTableEditor = function() {
        console.log('📋 Inicializando editor de tabla...');
        this.mostrarNotificacion('Editor de tabla disponible próximamente', 'info');
    };

    /**
     * Inicializar importador CSV (placeholder)
     */
    GestionGraficos.prototype.initializeCSVImport = function() {
        console.log('📥 Inicializando importador CSV...');
        this.mostrarNotificacion('Importador CSV disponible próximamente', 'info');
    };

    /**
     * Mostrar/ocultar período personalizado
     */
    GestionGraficos.prototype.toggleCustomPeriod = function() {
        const panel = document.getElementById('customPeriodPanel');
        if (panel) {
            const isHidden = panel.style.display === 'none';
            panel.style.display = isHidden ? 'block' : 'none';
            
            if (isHidden) {
                // Establecer fechas por defecto
                const hoy = new Date();
                const hace6Meses = new Date(hoy.getFullYear(), hoy.getMonth() - 6, hoy.getDate());
                
                const fechaInicio = document.getElementById('fechaInicio');
                const fechaFin = document.getElementById('fechaFin');
                
                if (fechaInicio) fechaInicio.value = hace6Meses.toISOString().split('T')[0];
                if (fechaFin) fechaFin.value = hoy.toISOString().split('T')[0];
            }
        }
    };

    console.log('🎯 Sistema de gestión de gráficos completamente cargado');
} Crear nuevo gráfico
            this.currentChart = new Chart(canvas, config);
            
            // Actualizar estadísticas del gráfico
            this.updateChartStats();
            
            console.log('✅ Gráfico previsualizado correctamente');
            this.mostrarNotificacion('Gráfico actualizado correctamente', 'success');
            
        } catch (error) {
            console.error('❌ Error creando gráfico:', error);
            this.mostrarNotificacion('Error al crear el gráfico: ' + error.message, 'error');
        }
    }

    /**
     * Actualizar estadísticas del gráfico
     */
    updateChartStats() {
        if (!this.currentData.length) return;
        
        const values = this.currentData.map(item => item.valor || item.empresas || item.count || 0);
        const lastValue = values[values.length - 1];
        const firstValue = values[0];
        const trend = lastValue > firstValue ? '↗️ Creciente' : 
                     lastValue < firstValue ? '↘️ Decreciente' : '➡️ Estable';
        
        // Actualizar elementos de estadísticas
        const totalPoints = document.getElementById('totalDataPoints');
        const lastVal = document.getElementById('lastValue');
        const trendIndicator = document.getElementById('trendIndicator');
        const lastUpd = document.getElementById('lastUpdate');
        
        if (totalPoints) totalPoints.textContent = values.length;
        if (lastVal) lastVal.textContent = lastValue.toLocaleString();
        if (trendIndicator) trendIndicator.textContent = trend;
        if (lastUpd) lastUpd.textContent = new Date().toLocaleString('es-ES', {
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    /**
     * Convertir hex a rgba
     */
    hexToRgba(hex, alpha) {
        // Verificar formato hex válido
        if (!/^#[0-9A-F]{6}$/i.test(hex)) {
            console.warn(`Color hex inválido: ${hex}, usando azul por defecto`);
            hex = '#3B82F6';
        }
        
        const r = parseInt(hex.slice(1, 3), 16);
        const g = parseInt(hex.slice(3, 5), 16);
        const b = parseInt(hex.slice(5, 7), 16);
        return `rgba(${r}, ${g}, ${b}, ${alpha})`;
    }

    /**
     * Cuando cambia la configuración
     */
    onConfigChange() {
        console.log('🔄 Configuración cambiada, actualizando gráfico...');
        
        // Actualizar el gráfico automáticamente si existe
        if (this.currentChart && this.currentData.length > 0) {
            // Pequeño delay para mejor UX
            setTimeout(() => {
                this.previsualizarGrafico();
            }, 300);
        }
    }
}

// Variable global para acceso desde HTML
let gestionGraficos;

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    console.log('🔧 DOM cargado, inicializando gestión de gráficos...');
    
    if (typeof Chart !== 'undefined') {
        gestionGraficos = new GestionGraficos();
    } else {
        console.log('⏳ Esperando a que Chart.js se cargue...');
        const checkChart = setInterval(() => {
            if (typeof Chart !== 'undefined') {
                clearInterval(checkChart);
                gestionGraficos = new GestionGraficos();
            }
        }, 100);
    }
});

console.log('📄 Parte 1 del sistema de gestión cargada');

// ==================== FUNCIONES DE CONFIGURACIÓN Y TABS ====================

// Añadir métodos adicionales a la clase GestionGraficos
if (typeof GestionGraficos !== 'undefined') {
    /**
     * Inicializar sistema de tabs
     */
    GestionGraficos.prototype.initializeTabs = function() {
        console.log('📋 Inicializando sistema de tabs...');
        
        // Event listeners para las tabs del editor de datos
        const tabButtons = document.querySelectorAll('.tab-button');
        tabButtons.forEach(button => {
            button.addEventListener('click', (e) => {
                const tabId = e.target.id.replace('tab-', '') || e.target.closest('.tab-button').id.replace('tab-', '');
                this.switchTab(tabId);
            });
        });
        
        // Configurar event listeners de configuración
        this.setupConfigEventListeners();
        
        console.log('✅ Tabs inicializadas');
    };

    /**
     * Configurar event listeners de configuración
     */
    GestionGraficos.prototype.setupConfigEventListeners = function() {
        // Tipo de gráfico
        document.querySelectorAll('input[name="chartType"]').forEach(radio => {
            radio.addEventListener('change', (e) => {
                this.currentConfig.type = e.target.value;
                console.log(`📊 Tipo de gráfico cambiado a: ${e.target.value}`);
                this.onConfigChange();
            });
        });

        // Fuente de datos
        const dataSource = document.getElementById('dataSource');
        if (dataSource) {
            dataSource.addEventListener('change', (e) => {
                this.currentConfig.dataSource = e.target.value;
                console.log(`🗄️ Fuente de datos cambiada a: ${e.target.value}`);
                
                // Si no es custom, recargar datos
                if (e.target.value !== 'custom') {
                    this.cargarDatosReales();
                } else {
                    this.onConfigChange();
                }
            });
        }

        // Período de tiempo
        const periodRange = document.getElementById('periodRange');
        if (periodRange) {
            periodRange.addEventListener('change', (e) => {
                this.currentConfig.period = e.target.value;
                console.log(`📅 Período cambiado a: ${e.target.value}`);
                
                if (e.target.value === 'custom') {
                    this.toggleCustomPeriod();
                } else {
                    this.cargarDatosReales();
                }
            });
        }

        // Título del gráfico
        const chartTitle = document.getElementById('chartTitle');
        if (chartTitle) {
            chartTitle.addEventListener('input', (e) => {
                this.currentConfig.title = e.target.value;
                this.onConfigChange();
            });
        }

        // Color principal
        const primaryColor = document.getElementById('primaryColor');
        if (primaryColor) {
            primaryColor.addEventListener('change', (e) => {
                this.currentConfig.color = e.target.value;
                console.log(`🎨 Color cambiado a: ${e.target.value}`);
                this.onConfigChange();
            });
        }

        // Presets de color
        document.querySelectorAll('.color-preset').forEach(preset => {
            preset.addEventListener('click', (e) => {
                const color = e.target.dataset.color;
                if (primaryColor) primaryColor.value = color;
                this.currentConfig.color = color;
                console.log(`🎨 Color preset aplicado: ${color}`);
                this.onConfigChange();
            });
        });

        // Checkboxes de configuración
        const checkboxConfigs = {
            'showAnimation': 'animation',
            'showGrid': 'grid',
            'showTooltips': 'tooltips',
            'showLegend': 'legend'
        };
        
        Object.entries(checkboxConfigs).forEach(([elementId, configKey]) => {
            const checkbox = document.getElementById(elementId);
            if (checkbox) {
                checkbox.addEventListener('change', (e) => {
                    this.currentConfig[configKey] = e.target.checked;
                    console.log(`☑️ ${configKey} cambiado a: ${e.target.checked}`);
                    this.onConfigChange();
                });
            }
        });

        // Botón actualizar datos
        const actualizarDatos = document.getElementById('actualizarDatos');
        if (actualizarDatos) {
            actualizarDatos.addEventListener('click', () => {
                this.actualizarDatos();
            });
        }

        // Botón editar datos manualmente
        const editarDatos = document.getElementById('editarDatosManualmente');
        if (editarDatos) {
            editarDatos.addEventListener('click', () => {
                this.abrirEditorDatos();
            });
        }
    };

    /**
     * Cambiar tab activa
     */
    GestionGraficos.prototype.switchTab = function(tabId) {
        console.log(`📋 Cambiando a tab: ${tabId}`);
        
        // Desactivar todas las tabs
        document.querySelectorAll('.tab-button').forEach(btn => {
            btn.classList.remove('active', 'border-blue-500', 'text-blue-600');
            btn.classList.add('border-transparent', 'text-gray-500');
        });
        
        // Ocultar todo el contenido
        document.querySelectorAll('.tab-content').forEach(content => {
            content.classList.add('hidden');
        });
        
        // Activar tab seleccionada
        const activeButton = document.getElementById(`tab-${tabId}`);
        if (activeButton) {
            activeButton.classList.add('active', 'border-blue-500', 'text-blue-600');
            activeButton.classList.remove('border-transparent', 'text-gray-500');
        }
        
        // Mostrar contenido correspondiente
        const activeContent = document.getElementById(`content-${tabId}`);
        if (activeContent) {
            activeContent.classList.remove('hidden');
        }
        
        // Inicializar funcionalidad específica de la tab
        if (tabId === 'editor') {
            this.initializeDataEditor();
        } else if (tabId === 'table') {
            this.initializeTableEditor();
        } else if (tabId === 'import') {
            this.initializeCSVImport();
        }
    };

    /**
     * Abrir editor de datos
     */
    GestionGraficos.prototype.abrirEditorDatos = function() {
        console.log('📝 Abriendo editor de datos...');
        
        const panel = document.getElementById('customDataPanel');
        if (panel) {
            panel.style.display = 'flex';
            this.initializeDataEditor();
            
            // Cargar datos actuales en el editor si existen
            if (this.currentData.length > 0) {
                const editor = document.getElementById('customDataEditor');
                if (editor) {
                    editor.value = JSON.stringify(this.currentData, null, 2);
                }
            }
        }
    };

    /**
     * Cerrar editor de datos
     */
    GestionGraficos.prototype.cerrarEditorDatos = function() {
        console.log('❌ Cerrando editor de datos...');
        
        const panel = document.getElementById('customDataPanel');
        if (panel) {
            panel.style.display = 'none';
        }
    };

    /**
     * Mostrar notificación
     */
    GestionGraficos.prototype.mostrarNotificacion = function(mensaje, tipo = 'info') {
        const container = document.getElementById('notificationContainer');
        if (!container) return;
        
        const colors = {
            success: 'bg-green-500',
            error: 'bg-red-500',
            warning: 'bg-yellow-500',
            info: 'bg-blue-500'
        };
        
        const notificacion = document.createElement('div');
        notificacion.className = `${colors[tipo]} text-white px-4 py-3 rounded-lg shadow-lg mb-2 transition-all duration-300 transform translate-x-full`;
        notificacion.innerHTML = `
            <div class="flex items-center justify-between">
                <span class="text-sm">${mensaje}</span>
                <button onclick="this.parentElement.parentElement.remove()" class="ml-3 text-white hover:text-gray-200">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
        
        container.appendChild(notificacion);
        
        // Animar entrada
        setTimeout(() => {
            notificacion.classList.remove('translate-x-full');
        }, 100);
        
        // Auto-remover después de 5 segundos
        setTimeout(() => {
            if (notificacion.parentElement) {
                notificacion.classList.add('translate-x-full');
                setTimeout(() => notificacion.remove(), 300);
            }
        }, 5000);
    };

    console.log('📄 Funciones de configuración y tabs añadidas');
}
