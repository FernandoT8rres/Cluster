// ==================== INICIALIZACIÓN DE EMERGENCIA ====================
// Script de emergencia para garantizar funcionalidad

console.log('🚑 Cargando sistema de emergencia...');

// Verificar y cargar Chart.js si no está disponible
function asegurarChartJS() {
    return new Promise((resolve) => {
        if (typeof Chart !== 'undefined') {
            console.log('✅ Chart.js ya disponible');
            resolve();
            return;
        }
        
        console.log('📦 Cargando Chart.js desde CDN...');
        const script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/chart.js';
        script.onload = () => {
            console.log('✅ Chart.js cargado desde CDN');
            resolve();
        };
        script.onerror = () => {
            console.log('❌ Error cargando Chart.js desde CDN');
            resolve(); // Continuar aunque falle
        };
        document.head.appendChild(script);
    });
}

// Sistema de gestión simplificado para emergencia
class SistemaEmergencia {
    constructor() {
        this.currentData = [];
        this.currentChart = null;
        this.config = {
            type: 'line',
            color: '#C7252B',
            title: 'Gráfico de Datos'
        };
        console.log('🆘 Sistema de emergencia inicializado');
    }
    
    // Cargar datos de ejemplo
    cargarDatosEjemplo() {
        console.log('📊 Intentando cargar datos reales antes que ejemplos...');
        
        // Intentar cargar datos reales primero
        if (window.gestorDatosReales) {
            this.cargarDatosRealesDesdeGestor('empresas');
        } else {
            console.log('📋 Gestor no disponible, cargando datos de ejemplo...');
            this.generarDatosEjemploLocal();
        }
    }
    
    // Cargar datos reales usando el gestor
    async cargarDatosRealesDesdeGestor(tipo = 'empresas') {
        try {
            console.log(`🔄 Cargando datos reales de: ${tipo}`);
            
            const datos = await window.gestorDatosReales.obtenerDatos(tipo);
            
            if (datos && datos.length > 0) {
                this.currentData = datos;
                this.mostrarNotificacion(`✅ ${datos.length} registros de ${tipo} cargados desde BD`, 'success');
                this.crearGrafico();
                this.actualizarInformacionDatos(datos, tipo);
            } else {
                throw new Error('No se obtuvieron datos válidos');
            }
            
        } catch (error) {
            console.error('❌ Error cargando datos reales:', error);
            this.mostrarNotificacion('Error con BD, usando datos de ejemplo', 'warning');
            this.generarDatosEjemploLocal();
        }
    }
    
    // Generar datos de ejemplo localmente
    generarDatosEjemploLocal() {
        console.log('📋 Generando datos de ejemplo local...');
        
        this.currentData = [
            { mes: 'Ene', valor: 15, empresas: 15, categoria: 'ejemplo' },
            { mes: 'Feb', valor: 19, empresas: 19, categoria: 'ejemplo' },
            { mes: 'Mar', valor: 23, empresas: 23, categoria: 'ejemplo' },
            { mes: 'Abr', valor: 27, empresas: 27, categoria: 'ejemplo' },
            { mes: 'May', valor: 31, empresas: 31, categoria: 'ejemplo' },
            { mes: 'Jun', valor: 35, empresas: 35, categoria: 'ejemplo' }
        ];
        
        this.mostrarNotificacion('Datos de ejemplo cargados', 'info');
        this.crearGrafico();
    }
    
    // Actualizar información de los datos
    actualizarInformacionDatos(datos, tipo) {
        const recordCount = document.getElementById('recordCount');
        const lastUpdate = document.getElementById('lastDataUpdate');
        
        if (recordCount) {
            recordCount.textContent = datos.length;
        }
        
        if (lastUpdate) {
            lastUpdate.textContent = new Date().toLocaleString('es-ES');
        }

        // Actualizar estadísticas en tiempo real
        this.actualizarEstadisticasTiempoReal(datos, tipo);

        // Mostrar información adicional sobre los datos
        const primer = datos[0];
        const ultimo = datos[datos.length - 1];

        console.log('📈 Información de los datos:');
        console.log(`- Tipo: ${tipo}`);
        console.log(`- Registros: ${datos.length}`);
        console.log(`- Rango: ${primer.valor} - ${ultimo.valor}`);
        console.log(`- Período: ${primer.mes} a ${ultimo.mes}`);

        if (primer.detalles && primer.detalles.generado) {
            console.log('- Fuente: Datos de ejemplo');
        } else {
            console.log('- Fuente: Base de datos');
        }
    }
    
    // Crear gráfico
    crearGrafico() {
        const canvas = document.getElementById('previewChart');
        if (!canvas) {
            console.log('❌ Canvas no encontrado');
            return;
        }
        
        if (!this.currentData.length) {
            console.log('❌ No hay datos para el gráfico');
            return;
        }
        
        // Destruir gráfico anterior
        if (this.currentChart) {
            this.currentChart.destroy();
        }
        
        const labels = this.currentData.map(item => item.mes);
        const values = this.currentData.map(item => item.valor || item.empresas);
        
        try {
            this.currentChart = new Chart(canvas, {
                type: this.config.type,
                data: {
                    labels: labels,
                    datasets: [{
                        label: this.config.title,
                        data: values,
                        borderColor: this.config.color,
                        backgroundColor: this.config.color + '20',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: { beginAtZero: true }
                    }
                }
            });
            
            console.log('✅ Gráfico creado con éxito');
            this.mostrarNotificacion('Gráfico generado correctamente', 'success');
            
        } catch (error) {
            console.error('❌ Error creando gráfico:', error);
            this.mostrarNotificacion('Error creando gráfico: ' + error.message, 'error');
        }
    }
    
    // Cambiar tipo de gráfico
    cambiarTipo(tipo) {
        this.config.type = tipo;
        console.log(`📊 Tipo cambiado a: ${tipo}`);
        if (this.currentData.length > 0) {
            this.crearGrafico();
        }
    }
    
    // Cambiar color
    cambiarColor(color) {
        this.config.color = color;
        console.log(`🎨 Color cambiado a: ${color}`);
        if (this.currentData.length > 0) {
            this.crearGrafico();
        }
    }
    
    // Abrir editor
    abrirEditor() {
        const panel = document.getElementById('customDataPanel');
        if (panel) {
            panel.style.display = 'flex';
            this.mostrarNotificacion('Editor de datos abierto', 'info');
        }
    }
    
    // Cerrar editor
    cerrarEditor() {
        const panel = document.getElementById('customDataPanel');
        if (panel) {
            panel.style.display = 'none';
        }
    }
    
    // Guardar configuración
    guardar() {
        const nombre = prompt('Nombre para guardar:', 'Mi Configuración');
        if (nombre) {
            const config = {
                name: nombre,
                data: this.currentData,
                config: this.config,
                timestamp: new Date().toISOString()
            };
            
            const configs = JSON.parse(localStorage.getItem('configs_emergencia') || '[]');
            configs.push(config);
            localStorage.setItem('configs_emergencia', JSON.stringify(configs));
            
            this.mostrarNotificacion(`Configuración "${nombre}" guardada`, 'success');
            console.log('💾 Configuración guardada:', nombre);
        }
    }
    
    // Mostrar notificación
    mostrarNotificacion(mensaje, tipo = 'info') {
        console.log(`📢 ${tipo.toUpperCase()}: ${mensaje}`);
        
        const container = document.getElementById('notificationContainer') || document.body;
        const colors = {
            success: '#10B981',
            error: '#EF4444', 
            warning: '#F59E0B',
            info: '#3B82F6'
        };
        
        const div = document.createElement('div');
        div.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: ${colors[tipo] || colors.info};
            color: white;
            padding: 12px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            z-index: 9999;
            font-size: 14px;
            max-width: 300px;
            transition: all 0.3s ease;
            transform: translateX(100%);
        `;
        div.textContent = mensaje;
        
        document.body.appendChild(div);
        
        // Animar entrada
        setTimeout(() => {
            div.style.transform = 'translateX(0)';
        }, 100);
        
        // Auto-remover
        setTimeout(() => {
            div.style.transform = 'translateX(100%)';
            setTimeout(() => div.remove(), 300);
        }, 3000);
    }

    // Actualizar estadísticas en tiempo real
    actualizarEstadisticasTiempoReal(datos, tipo) {
        console.log('📊 Actualizando estadísticas en tiempo real...');

        if (!datos || datos.length === 0) {
            console.log('❌ No hay datos para actualizar estadísticas');
            return;
        }

        // Total de puntos de datos
        const totalDataPoints = document.getElementById('totalDataPoints');
        if (totalDataPoints) {
            totalDataPoints.textContent = datos.length;
        }

        // Último valor
        const lastValue = document.getElementById('lastValue');
        if (lastValue) {
            const ultimoDato = datos[datos.length - 1];
            const valor = ultimoDato.valor || ultimoDato.empresas || ultimoDato.usuarios ||
                         ultimoDato.eventos || ultimoDato.miembros || ultimoDato.descuentos || 0;
            lastValue.textContent = valor.toLocaleString('es-ES');
        }

        // Tendencia (comparar últimos dos valores)
        const tendencia = document.getElementById('trendIndicator');
        if (tendencia && datos.length >= 2) {
            const ultimoValor = datos[datos.length - 1].valor || 0;
            const penultimoValor = datos[datos.length - 2].valor || 0;
            const diferencia = ultimoValor - penultimoValor;
            const porcentaje = penultimoValor > 0 ? ((diferencia / penultimoValor) * 100).toFixed(1) : 0;

            if (diferencia > 0) {
                tendencia.innerHTML = `<span style="color: #10B981;">↑ +${porcentaje}%</span>`;
            } else if (diferencia < 0) {
                tendencia.innerHTML = `<span style="color: #EF4444;">↓ ${porcentaje}%</span>`;
            } else {
                tendencia.innerHTML = `<span style="color: #6B7280;">→ 0%</span>`;
            }
        }

        // Actualizar fuente de datos (buscar el elemento de display de fuente)
        const fuenteDatos = document.getElementById('dataSourceDisplay') || document.getElementById('currentDataSource');
        if (fuenteDatos) {
            const primer = datos[0];
            if (primer.detalles && primer.detalles.generado) {
                fuenteDatos.textContent = 'Datos de ejemplo';
            } else {
                fuenteDatos.textContent = 'Base de datos';
            }
        }

        // Actualizar período de tiempo
        const periodoTiempo = document.getElementById('timePeriod');
        if (periodoTiempo && datos.length > 0) {
            const primer = datos[0];
            const ultimo = datos[datos.length - 1];
            periodoTiempo.textContent = `${primer.mes} - ${ultimo.mes} ${ultimo.año || new Date().getFullYear()}`;
        }

        console.log('✅ Estadísticas en tiempo real actualizadas');
    }
}

// Función principal de inicialización
async function inicializarSistemaEmergencia() {
    console.log('🚀 Inicializando sistema de emergencia...');
    
    // Asegurar Chart.js
    await asegurarChartJS();
    
    // Crear sistema de emergencia
    window.sistemaEmergencia = new SistemaEmergencia();
    
    // Configurar botones básicos
    setTimeout(() => {
        configurarBotonesEmergencia();
    }, 500);
}

// Configurar botones de emergencia
function configurarBotonesEmergencia() {
    console.log('🔘 Configurando botones de emergencia...');
    
    // Cargar datos
    const btnCargar = document.getElementById('cargarDatosReales');
    if (btnCargar) {
        btnCargar.onclick = () => {
            console.log('🔄 Cargando datos...');
            if (window.gestionGraficos && window.gestionGraficos.cargarDatosReales) {
                window.gestionGraficos.cargarDatosReales();
            } else {
                window.sistemaEmergencia.cargarDatosEjemplo();
            }
        };
    }
    
    // Previsualizar
    const btnPreview = document.getElementById('previsualizarGrafico');
    if (btnPreview) {
        btnPreview.onclick = () => {
            console.log('👁️ Previsualizando...');
            if (window.gestionGraficos && window.gestionGraficos.previsualizarGrafico) {
                window.gestionGraficos.previsualizarGrafico();
            } else {
                window.sistemaEmergencia.crearGrafico();
            }
        };
    }
    
    // Guardar
    const btnGuardar = document.getElementById('guardarConfig');
    if (btnGuardar) {
        btnGuardar.onclick = () => {
            console.log('💾 Guardando configuración...');
            if (window.sistemaEmergencia && window.sistemaEmergencia.guardarMejorado) {
                window.sistemaEmergencia.guardarMejorado();
            } else {
                window.sistemaEmergencia.guardar();
            }
        };
    }
    
    // Editor manual
    const btnEditor = document.getElementById('editarDatosManualmente');
    if (btnEditor) {
        btnEditor.onclick = () => {
            console.log('📝 Abriendo editor...');
            if (window.sistemaEmergencia && window.sistemaEmergencia.editarDatosMejorado) {
                window.sistemaEmergencia.editarDatosMejorado();
            } else {
                window.sistemaEmergencia.abrirEditor();
            }
        };
    }
    
    // Cerrar editor
    const btnCerrar = document.getElementById('cerrarCustomData');
    if (btnCerrar) {
        btnCerrar.onclick = () => {
            if (window.gestionGraficos && window.gestionGraficos.cerrarEditorDatos) {
                window.gestionGraficos.cerrarEditorDatos();
            } else {
                window.sistemaEmergencia.cerrarEditor();
            }
        };
    }
    
    // Ver configuraciones guardadas
    const btnVerConfigs = document.getElementById('verConfiguraciones');
    if (btnVerConfigs) {
        btnVerConfigs.onclick = (e) => {
            e.preventDefault();
            console.log('📁 Mostrando configuraciones guardadas...');
            if (window.sistemaEmergencia && window.sistemaEmergencia.mostrarConfiguracionesGuardadas) {
                window.sistemaEmergencia.mostrarConfiguracionesGuardadas();
            }
        };
    }
    
    // Tipos de gráfico
    document.querySelectorAll('input[name="chartType"]').forEach(radio => {
        radio.addEventListener('change', (e) => {
            if (window.sistemaEmergencia) {
                window.sistemaEmergencia.cambiarTipo(e.target.value);
            }
        });
    });
    
    // Color
    const colorInput = document.getElementById('primaryColor');
    if (colorInput) {
        colorInput.addEventListener('change', (e) => {
            if (window.sistemaEmergencia) {
                window.sistemaEmergencia.cambiarColor(e.target.value);
            }
        });
    }
    
    console.log('✅ Botones configurados');
    
    // Cargar datos iniciales
    window.sistemaEmergencia.cargarDatosEjemplo();
}

// Ejecutar al cargar
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', inicializarSistemaEmergencia);
} else {
    inicializarSistemaEmergencia();
}

console.log('🚑 Sistema de emergencia listo');
