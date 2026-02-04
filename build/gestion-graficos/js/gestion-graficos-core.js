/**
 * CLÚSTER GRÁFICOS - MÓDULO CORE
 * Funcionalidades principales para la gestión de gráficos
 */

class ClústerGraficos {
    constructor() {
        this.API_BASE = './api/graficos.php';
        this.currentChart = null;
        this.currentConfig = this.getDefaultConfig();
        this.charts = {};
        this.initialized = false;
    }
    
    /**
     * Inicialización del sistema
     */
    async init() {
        try {
            console.log('🚀 Inicializando Clúster Gráficos Core...');
            
            // Verificar que Chart.js esté disponible
            if (typeof Chart === 'undefined') {
                console.warn('Chart.js no está cargado, usando datos mock');
            } else {
                this.setupChartDefaults();
            }
            
            // Inicializar base de datos
            await this.initDatabase();
            
            // Cargar configuración predeterminada
            await this.loadDefaultConfig();
            
            this.initialized = true;
            console.log('✅ Clúster Gráficos Core inicializado correctamente');
            
        } catch (error) {
            console.error('❌ Error inicializando Clúster Gráficos:', error);
            this.showNotification('Error de inicialización: ' + error.message, 'error');
        }
    }
    
    /**
     * Configuración predeterminada
     */
    getDefaultConfig() {
        return {
            tipo: 'line',
            fuente: 'empresas',
            periodo: 12,
            titulo: 'Empresas Registradas',
            color_primario: '#3B82F6',
            animaciones: true,
            mostrar_grilla: true,
            mostrar_tooltips: true,
            mostrar_leyenda: false,
            filtros: {}
        };
    }
    
    /**
     * Configurar valores predeterminados de Chart.js
     */
    setupChartDefaults() {
        if (typeof Chart === 'undefined') return;
        
        Chart.defaults.font.family = "'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif";
        Chart.defaults.color = '#64748B';
        Chart.defaults.borderColor = '#E2E8F0';
        Chart.defaults.backgroundColor = 'rgba(59, 130, 246, 0.1)';
        Chart.defaults.responsive = true;
        Chart.defaults.maintainAspectRatio = false;
        Chart.defaults.animation.duration = 750;
        Chart.defaults.animation.easing = 'easeInOutQuart';
    }
    
    /**
     * Inicializar base de datos
     */
    async initDatabase() {
        try {
            const response = await fetch(`${this.API_BASE}?init=true`);
            const result = await response.json();
            
            if (!result.success) {
                throw new Error(result.error || 'Error inicializando base de datos');
            }
            
        } catch (error) {
            console.warn('Advertencia al inicializar BD:', error.message);
        }
    }
    
    /**
     * Cargar configuración predeterminada
     */
    async loadDefaultConfig() {
        try {
            const configuraciones = await this.getConfiguraciones();
            const defaultConfig = configuraciones.find(c => c.es_predeterminada);
            
            if (defaultConfig) {
                this.currentConfig = { ...this.currentConfig, ...defaultConfig.configuracion };
            }
            
        } catch (error) {
            console.warn('Usando configuración por defecto local:', error.message);
        }
    }
    
    /**
     * Realizar petición a la API
     */
    async apiRequest(endpoint, options = {}) {
        const url = `${this.API_BASE}?endpoint=${endpoint}`;
        
        const defaultOptions = {
            headers: {
                'Content-Type': 'application/json',
            }
        };
        
        const finalOptions = { ...defaultOptions, ...options };
        
        try {
            const response = await fetch(url, finalOptions);
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const result = await response.json();
            
            if (!result.success) {
                throw new Error(result.error || 'Error en la respuesta del servidor');
            }
            
            return result.data;
            
        } catch (error) {
            console.error('Error en API request:', error);
            // En caso de error, devolver datos mock para testing
            return this.getMockData(endpoint);
        }
    }
    
    /**
     * Obtener datos mock para testing
     */
    getMockData(endpoint) {
        const mockData = {
            estadisticas: {
                empresas: [
                    { periodo: '2024-01', label: 'Ene 2024', valor: 45 },
                    { periodo: '2024-02', label: 'Feb 2024', valor: 52 },
                    { periodo: '2024-03', label: 'Mar 2024', valor: 48 },
                    { periodo: '2024-04', label: 'Abr 2024', valor: 61 },
                    { periodo: '2024-05', label: 'May 2024', valor: 55 },
                    { periodo: '2024-06', label: 'Jun 2024', valor: 67 }
                ],
                usuarios: [
                    { periodo: '2024-01', label: 'Ene 2024', valor: 120 },
                    { periodo: '2024-02', label: 'Feb 2024', valor: 135 },
                    { periodo: '2024-03', label: 'Mar 2024', valor: 142 },
                    { periodo: '2024-04', label: 'Abr 2024', valor: 158 },
                    { periodo: '2024-05', label: 'May 2024', valor: 164 },
                    { periodo: '2024-06', label: 'Jun 2024', valor: 171 }
                ],
                resumen: {
                    total_empresas: 67,
                    total_usuarios: 171,
                    eventos_proximos: 12,
                    comites_activos: 6
                }
            },
            configuraciones: [
                {
                    id: 1,
                    nombre: 'Configuración por Defecto',
                    descripcion: 'Configuración estándar del sistema',
                    configuracion: this.getDefaultConfig(),
                    es_predeterminada: true
                }
            ]
        };
        
        return mockData[endpoint] || [];
    }
    
    /**
     * Obtener estadísticas generales
     */
    async getEstadisticas() {
        return await this.apiRequest('estadisticas');
    }
    
    /**
     * Obtener datos para gráfico
     */
    async getDatosGrafico(fuente = null, periodo = null, filtros = {}) {
        const params = new URLSearchParams();
        
        if (fuente) params.append('fuente', fuente);
        if (periodo) params.append('periodo', periodo);
        
        Object.keys(filtros).forEach(key => {
            if (filtros[key]) {
                params.append(`filtros[${key}]`, filtros[key]);
            }
        });
        
        const url = `${this.API_BASE}?endpoint=datos_grafico&${params.toString()}`;
        
        try {
            const response = await fetch(url);
            const result = await response.json();
            
            if (!result.success) {
                throw new Error(result.error);
            }
            
            return result;
            
        } catch (error) {
            console.error('Error obteniendo datos del gráfico:', error);
            
            // Devolver datos mock
            const mockData = this.getMockData('estadisticas');
            const data = mockData[fuente || 'empresas'] || mockData.empresas;
            
            return {
                data: data,
                metadata: {
                    fuente: fuente || 'empresas',
                    periodo: periodo || 12,
                    total_puntos: data.length,
                    ultimo_valor: data[data.length - 1]?.valor || 0,
                    timestamp: new Date().toISOString()
                }
            };
        }
    }
    
    /**
     * Obtener configuraciones guardadas
     */
    async getConfiguraciones() {
        return await this.apiRequest('configuraciones');
    }
    
    /**
     * Guardar configuración
     */
    async guardarConfiguracion(nombre, descripcion, configuracion, esPredeterminada = false) {
        return await this.apiRequest('configuracion', {
            method: 'POST',
            body: JSON.stringify({
                nombre,
                descripcion,
                configuracion,
                es_predeterminada: esPredeterminada
            })
        });
    }
    
    /**
     * Crear gráfico
     */
    createChart(canvasId, config = null) {
        if (typeof Chart === 'undefined') {
            console.warn('Chart.js no disponible');
            return null;
        }
        
        const canvas = document.getElementById(canvasId);
        if (!canvas) {
            throw new Error(`Canvas con ID '${canvasId}' no encontrado`);
        }
        
        const ctx = canvas.getContext('2d');
        const chartConfig = config || this.currentConfig;
        
        // Destruir gráfico existente si existe
        if (this.charts[canvasId]) {
            this.charts[canvasId].destroy();
        }
        
        const chartOptions = this.buildChartConfig(chartConfig);
        
        try {
            const chart = new Chart(ctx, chartOptions);
            this.charts[canvasId] = chart;
            this.currentChart = chart;
            
            return chart;
            
        } catch (error) {
            console.error('Error creando gráfico:', error);
            throw error;
        }
    }
    
    /**
     * Construir configuración de Chart.js
     */
    buildChartConfig(config) {
        const baseConfig = {
            type: config.tipo || 'line',
            data: {
                labels: [],
                datasets: [{
                    label: config.titulo || 'Datos',
                    data: [],
                    borderColor: config.color_primario || '#3B82F6',
                    backgroundColor: this.hexToRgba(config.color_primario || '#3B82F6', 0.1),
                    borderWidth: 3,
                    fill: config.tipo === 'area',
                    tension: config.tipo === 'line' ? 0.4 : 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: {
                    duration: config.animaciones ? 750 : 0
                },
                plugins: {
                    title: {
                        display: !!config.titulo,
                        text: config.titulo,
                        font: {
                            size: 16,
                            weight: 'bold'
                        },
                        padding: 20
                    },
                    legend: {
                        display: config.mostrar_leyenda || false,
                        position: 'top',
                        labels: {
                            usePointStyle: true,
                            padding: 20
                        }
                    },
                    tooltip: {
                        enabled: config.mostrar_tooltips !== false,
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleColor: '#fff',
                        bodyColor: '#fff',
                        borderColor: config.color_primario || '#3B82F6',
                        borderWidth: 1,
                        cornerRadius: 8,
                        displayColors: true
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: config.mostrar_grilla !== false,
                            color: '#F1F5F9'
                        },
                        ticks: {
                            maxTicksLimit: 12
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            display: config.mostrar_grilla !== false,
                            color: '#F1F5F9'
                        },
                        ticks: {
                            callback: function(value) {
                                return Number.isInteger(value) ? value : '';
                            }
                        }
                    }
                }
            }
        };
        
        // Configuraciones específicas por tipo de gráfico
        if (config.tipo === 'bar') {
            baseConfig.data.datasets[0].backgroundColor = config.color_primario || '#3B82F6';
            baseConfig.data.datasets[0].borderRadius = 6;
            baseConfig.data.datasets[0].borderSkipped = false;
        }
        
        if (config.tipo === 'area') {
            baseConfig.data.datasets[0].fill = true;
            baseConfig.data.datasets[0].backgroundColor = this.hexToRgba(config.color_primario || '#3B82F6', 0.2);
        }
        
        return baseConfig;
    }
    
    /**
     * Actualizar datos del gráfico
     */
    async updateChartData(canvasId, fuente = null, periodo = null, filtros = {}) {
        const chart = this.charts[canvasId];
        if (!chart) {
            throw new Error(`Gráfico con ID '${canvasId}' no encontrado`);
        }
        
        try {
            const response = await this.getDatosGrafico(
                fuente || this.currentConfig.fuente,
                periodo || this.currentConfig.periodo,
                filtros || this.currentConfig.filtros
            );
            
            const data = response.data;
            const metadata = response.metadata;
            
            // Actualizar datos
            chart.data.labels = data.map(item => item.label);
            chart.data.datasets[0].data = data.map(item => item.valor);
            
            // Actualizar título si es necesario
            if (chart.options.plugins.title && chart.options.plugins.title.display) {
                chart.options.plugins.title.text = this.currentConfig.titulo;
            }
            
            chart.update();
            
            // Actualizar estadísticas en UI
            this.updateChartStats(metadata);
            
            return { data, metadata };
            
        } catch (error) {
            console.error('Error actualizando datos del gráfico:', error);
            this.showNotification('Error cargando datos: ' + error.message, 'error');
            throw error;
        }
    }
    
    /**
     * Actualizar estadísticas del gráfico en la UI
     */
    updateChartStats(metadata) {
        const elements = {
            totalDataPoints: document.getElementById('totalDataPoints'),
            lastValue: document.getElementById('lastValue'),
            trendIndicator: document.getElementById('trendIndicator'),
            lastUpdate: document.getElementById('lastUpdate')
        };
        
        if (elements.totalDataPoints) {
            elements.totalDataPoints.textContent = metadata.total_puntos || '--';
        }
        
        if (elements.lastValue) {
            elements.lastValue.textContent = metadata.ultimo_valor || '--';
        }
        
        if (elements.trendIndicator) {
            const trend = this.calculateTrend(this.currentChart);
            elements.trendIndicator.innerHTML = trend;
        }
        
        if (elements.lastUpdate) {
            elements.lastUpdate.textContent = new Date(metadata.timestamp).toLocaleTimeString();
        }
    }
    
    /**
     * Calcular tendencia del gráfico
     */
    calculateTrend(chart) {
        if (!chart || !chart.data.datasets[0].data.length) {
            return '<span class="text-gray-400">--</span>';
        }
        
        const data = chart.data.datasets[0].data;
        const lastTwo = data.slice(-2);
        
        if (lastTwo.length < 2) {
            return '<span class="text-gray-400">--</span>';
        }
        
        const [prev, current] = lastTwo;
        const change = current - prev;
        const percentage = prev > 0 ? ((change / prev) * 100).toFixed(1) : 0;
        
        if (change > 0) {
            return `<span class="text-green-600"><i class="fas fa-arrow-up mr-1"></i>+${percentage}%</span>`;
        } else if (change < 0) {
            return `<span class="text-red-600"><i class="fas fa-arrow-down mr-1"></i>${percentage}%</span>`;
        } else {
            return '<span class="text-gray-600"><i class="fas fa-minus mr-1"></i>0%</span>';
        }
    }
    
    /**
     * Convertir HEX a RGBA
     */
    hexToRgba(hex, alpha = 1) {
        const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
        if (!result) return `rgba(59, 130, 246, ${alpha})`;
        
        const r = parseInt(result[1], 16);
        const g = parseInt(result[2], 16);
        const b = parseInt(result[3], 16);
        
        return `rgba(${r}, ${g}, ${b}, ${alpha})`;
    }
    
    /**
     * Exportar gráfico como imagen
     */
    exportChart(canvasId, filename = null) {
        const chart = this.charts[canvasId];
        if (!chart) {
            throw new Error(`Gráfico con ID '${canvasId}' no encontrado`);
        }
        
        const url = chart.toBase64Image();
        const link = document.createElement('a');
        link.download = filename || `grafico_${new Date().getTime()}.png`;
        link.href = url;
        
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
    
    /**
     * Mostrar notificación
     */
    showNotification(message, type = 'info', duration = 5000) {
        const container = document.getElementById('notificationContainer');
        if (!container) {
            console.log(`[${type.toUpperCase()}] ${message}`);
            return;
        }
        
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        
        const icons = {
            success: 'fas fa-check-circle',
            error: 'fas fa-exclamation-circle',
            warning: 'fas fa-exclamation-triangle',
            info: 'fas fa-info-circle'
        };
        
        notification.innerHTML = `
            <div class="notification-icon">
                <i class="${icons[type] || icons.info}"></i>
            </div>
            <div class="notification-content">
                <div class="notification-message">${message}</div>
            </div>
            <button class="notification-close" onclick="this.parentElement.remove()">
                <i class="fas fa-times"></i>
            </button>
        `;
        
        container.appendChild(notification);
        
        // Auto-remover después del tiempo especificado
        setTimeout(() => {
            if (notification.parentElement) {
                notification.remove();
            }
        }, duration);
    }
    
    /**
     * Aplicar configuración actual
     */
    async applyCurrentConfig(canvasId) {
        if (this.charts[canvasId]) {
            this.charts[canvasId].destroy();
        }
        
        this.createChart(canvasId, this.currentConfig);
        await this.updateChartData(canvasId);
    }
    
    /**
     * Obtener configuración actual
     */
    getCurrentConfig() {
        return JSON.parse(JSON.stringify(this.currentConfig));
    }
    
    /**
     * Establecer configuración actual
     */
    setCurrentConfig(config) {
        this.currentConfig = JSON.parse(JSON.stringify(config));
    }
    
    /**
     * Limpiar recursos
     */
    destroy() {
        Object.keys(this.charts).forEach(canvasId => {
            if (this.charts[canvasId]) {
                this.charts[canvasId].destroy();
            }
        });
        
        this.charts = {};
        this.currentChart = null;
        
        console.log('✅ Clúster Gráficos destruido correctamente');
    }
}

// Instancia global
window.ClústerGraficos = ClústerGraficos;