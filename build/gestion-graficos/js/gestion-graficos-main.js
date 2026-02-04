     */
    setupGlobalEvents() {
        // Manejo de errores globales
        window.addEventListener('error', (event) => {
            console.error('Error global:', event.error);
        });
        
        // Manejo de promesas rechazadas
        window.addEventListener('unhandledrejection', (event) => {
            console.error('Promesa rechazada:', event.reason);
        });
        
        // Eventos de visibilidad de página
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden && this.initialized) {
                this.onPageVisible();
            }
        });
        
        // Eventos de resize
        window.addEventListener('resize', this.debounce(() => {
            this.onWindowResize();
        }, 250));
    }
    
    /**
     * Cargar gráfico inicial
     */
    async loadInitialChart() {
        try {
            if (!this.core || !this.core.initialized) {
                console.warn('Core no inicializado aún');
                return;
            }
            
            const config = this.core.getCurrentConfig();
            
            // Crear gráfico con configuración inicial
            this.core.createChart('previewChart', config);
            
            // Cargar datos iniciales
            await this.core.updateChartData('previewChart');
            
            console.log('📊 Gráfico inicial cargado');
            
        } catch (error) {
            console.warn('⚠️ No se pudo cargar el gráfico inicial:', error.message);
            // No es crítico, el usuario puede generar manualmente
        }
    }
    
    /**
     * Evento cuando la página se vuelve visible
     */
    onPageVisible() {
        // Actualizar datos si han pasado más de 5 minutos
        const lastUpdate = this.core.currentChart?.lastUpdate;
        if (lastUpdate && Date.now() - lastUpdate > 5 * 60 * 1000) {
            this.refreshCurrentChart();
        }
    }
    
    /**
     * Evento de redimensionamiento de ventana
     */
    onWindowResize() {
        if (this.core && this.core.currentChart) {
            this.core.currentChart.resize();
        }
    }
    
    /**
     * Actualizar gráfico actual
     */
    async refreshCurrentChart() {
        if (this.ui && this.core.currentChart) {
            await this.ui.onRefreshData();
        }
    }
    
    /**
     * Obtener configuración para el gráfico del index
     */
    getChartConfigForIndex() {
        if (!this.initialized || !this.core) {
            return this.getDefaultIndexConfig();
        }
        
        const config = this.core.getCurrentConfig();
        
        return {
            tipo: config.tipo || 'line',
            titulo: config.titulo || 'Estadísticas del Sistema',
            color_primario: config.color_primario || '#3B82F6',
            animaciones: config.animaciones !== false,
            mostrar_grilla: config.mostrar_grilla !== false,
            mostrar_tooltips: config.mostrar_tooltips !== false,
            mostrar_leyenda: config.mostrar_leyenda || false
        };
    }
    
    /**
     * Configuración por defecto para el index
     */
    getDefaultIndexConfig() {
        return {
            tipo: 'line',
            titulo: 'Estadísticas del Sistema',
            color_primario: '#3B82F6',
            animaciones: true,
            mostrar_grilla: true,
            mostrar_tooltips: true,
            mostrar_leyenda: false
        };
    }
    
    /**
     * Actualizar gráfico en el index
     */
    async updateIndexChart(canvasId, fuente = 'empresas') {
        try {
            if (!this.initialized) {
                console.warn('Sistema no inicializado aún');
                return;
            }
            
            const config = this.getChartConfigForIndex();
            config.fuente = fuente;
            
            // Crear o actualizar gráfico
            this.core.createChart(canvasId, config);
            await this.core.updateChartData(canvasId, fuente);
            
            console.log(`📊 Gráfico del index actualizado: ${fuente}`);
            
        } catch (error) {
            console.error('Error actualizando gráfico del index:', error);
            this.showFallbackChart(canvasId);
        }
    }
    
    /**
     * Mostrar gráfico de respaldo
     */
    showFallbackChart(canvasId) {
        try {
            const canvas = document.getElementById(canvasId);
            if (!canvas || typeof Chart === 'undefined') return;
            
            const ctx = canvas.getContext('2d');
            const mockData = [
                { label: 'Ene', valor: 45 },
                { label: 'Feb', valor: 52 },
                { label: 'Mar', valor: 48 },
                { label: 'Abr', valor: 61 },
                { label: 'May', valor: 55 },
                { label: 'Jun', valor: 67 }
            ];
            
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: mockData.map(item => item.label),
                    datasets: [{
                        label: 'Datos de ejemplo',
                        data: mockData.map(item => item.valor),
                        borderColor: '#3B82F6',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        borderWidth: 2,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        title: {
                            display: true,
                            text: 'Datos de ejemplo'
                        }
                    }
                }
            });
            
        } catch (error) {
            console.error('Error mostrando gráfico de respaldo:', error);
        }
    }
    
    /**
     * Emitir evento personalizado
     */
    emitEvent(eventName, detail) {
        const event = new CustomEvent(eventName, { detail });
        document.dispatchEvent(event);
    }
    
    /**
     * Función debounce
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
     * Mostrar error de inicialización
     */
    showInitError(error) {
        console.error('Error de inicialización:', error);
        
        const errorContainer = document.getElementById('notificationContainer');
        if (errorContainer) {
            const notification = document.createElement('div');
            notification.className = 'notification error';
            notification.innerHTML = `
                <div class="notification-icon">
                    <i class="fas fa-exclamation-circle"></i>
                </div>
                <div class="notification-content">
                    <div class="notification-title">Error de Inicialización</div>
                    <div class="notification-message">${error.message}</div>
                </div>
                <button class="notification-close" onclick="this.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
            `;
            errorContainer.appendChild(notification);
        }
    }
    
    /**
     * Obtener estado del sistema
     */
    getSystemStatus() {
        return {
            initialized: this.initialized,
            coreReady: !!this.core,
            uiReady: !!this.ui,
            dataReady: !!this.data,
            chartActive: !!this.core?.currentChart
        };
    }
    
    /**
     * Reinicializar sistema
     */
    async reinitialize() {
        try {
            console.log('🔄 Reinicializando sistema...');
            
            // Limpiar estado actual
            if (this.core) {
                this.core.destroy();
            }
            
            this.core = null;
            this.ui = null;
            this.data = null;
            this.initialized = false;
            
            // Reinicializar
            await this.initializeModules();
            
            console.log('✅ Sistema reinicializado');
            
        } catch (error) {
            console.error('❌ Error reinicializando:', error);
            throw error;
        }
    }
}

// ==================== FUNCIONES GLOBALES ====================

/**
 * Función global para cerrar modales
 */
function cerrarModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
    }
}

/**
 * Función global para mostrar/ocultar elementos
 */
function toggleElement(elementId) {
    const element = document.getElementById(elementId);
    if (element) {
        element.style.display = element.style.display === 'none' ? 'block' : 'none';
    }
}

/**
 * Función global para copiar configuración
 */
function copyConfigToClipboard() {
    if (window.clústerGraficos) {
        const config = window.clústerGraficos.getCurrentConfig();
        const configString = JSON.stringify(config, null, 2);
        
        navigator.clipboard.writeText(configString).then(() => {
            window.clústerGraficos.showNotification('Configuración copiada al portapapeles', 'success');
        }).catch(err => {
            console.error('Error copiando configuración:', err);
            window.clústerGraficos.showNotification('Error al copiar configuración', 'error');
        });
    }
}

/**
 * Función global para resetear sistema
 */
function resetSystem() {
    if (confirm('¿Estás seguro de que deseas resetear todo el sistema? Se perderán todos los cambios no guardados.')) {
        if (window.clústerGraficosMain) {
            window.clústerGraficosMain.reinitialize();
        } else {
            location.reload();
        }
    }
}

/**
 * Función para mostrar información del sistema
 */
function showSystemInfo() {
    if (!window.clústerGraficosMain) return;
    
    const status = window.clústerGraficosMain.getSystemStatus();
    const info = `
Sistema Clúster Gráficos
─────────────────────
Inicializado: ${status.initialized ? '✅' : '❌'}
Core: ${status.coreReady ? '✅' : '❌'}
UI: ${status.uiReady ? '✅' : '❌'}
Datos: ${status.dataReady ? '✅' : '❌'}
Gráfico Activo: ${status.chartActive ? '✅' : '❌'}

Versión: 1.0.0
`;
    
    alert(info);
}

// ==================== INICIALIZACIÓN ====================

// Inicializar sistema cuando se carga la página
document.addEventListener('DOMContentLoaded', function() {
    console.log('📄 DOM cargado, iniciando sistema...');
    
    // Verificar dependencias
    if (typeof Chart === 'undefined') {
        console.warn('⚠️ Chart.js no está cargado');
    }
    
    // Verificar que tenemos las clases necesarias
    if (typeof ClústerGraficos === 'undefined') {
        console.error('❌ ClústerGraficos no está disponible');
        return;
    }
    
    if (typeof ClústerGraficosUI === 'undefined') {
        console.error('❌ ClústerGraficosUI no está disponible');
        return;
    }
    
    // Inicializar sistema principal
    window.clústerGraficosMain = new ClústerGraficosMain();
});

// Manejar errores no capturados
window.addEventListener('error', function(event) {
    console.error('Error no capturado:', event.error);
});

// Manejar promesas rechazadas
window.addEventListener('unhandledrejection', function(event) {
    console.error('Promesa rechazada:', event.reason);
});

// Exportar funciones principales para uso global
window.ClústerGraficosMain = ClústerGraficosMain;
window.cerrarModal = cerrarModal;
window.toggleElement = toggleElement;
window.copyConfigToClipboard = copyConfigToClipboard;
window.resetSystem = resetSystem;
window.showSystemInfo = showSystemInfo;

// Mensaje de carga completada
console.log('📦 Clúster Gráficos - Archivo principal cargado');

/**
 * ==================== API PÚBLICA ====================
 * 
 * Funciones disponibles globalmente:
 * 
 * - window.clústerGraficos: Instancia principal del core
 * - window.clústerGraficosUI: Instancia de la interfaz de usuario  
 * - window.clústerGraficosMain: Instancia del módulo principal
 * 
 * Funciones utilitarias:
 * - cerrarModal(modalId)
 * - copyConfigToClipboard()
 * - resetSystem()
 * - showSystemInfo()
 * 
 * Eventos disponibles:
 * - 'claut:graficos:ready': Se emite cuando el sistema está completamente inicializado
 */