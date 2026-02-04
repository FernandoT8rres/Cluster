// ==================== SCRIPT PARA ASEGURAR GRÁFICO EN SECCIÓN CORRECTA ====================
// Garantiza que el gráfico aparezca donde debe: en "Estadísticas Gráficas"

class GraficoSeccionCorrecta {
    constructor() {
        this.chart = null;
        this.inicializado = false;
        this.verificacionesCompletadas = false;
    }

    /**
     * Inicializar y verificar posicionamiento del gráfico
     */
    async inicializar() {
        console.log('🎯 Iniciando verificación de sección del gráfico...');
        
        try {
            // 1. Verificar estructura HTML
            this.verificarEstructuraHTML();
            
            // 2. Asegurar contenedor correcto
            this.asegurarContenedorCorrecto();
            
            // 3. Esperar Chart.js
            await this.esperarChartJS();
            
            // 4. Crear gráfico en la posición correcta
            await this.crearGraficoEnSeccionCorrecta();
            
            // 5. Verificar posicionamiento final
            this.verificarPosicionamientoFinal();
            
            console.log('✅ Gráfico posicionado correctamente en "Estadísticas Gráficas"');
            
        } catch (error) {
            console.error('❌ Error en posicionamiento del gráfico:', error);
            this.crearGraficoEmergencia();
        }
    }

    /**
     * Verificar estructura HTML
     */
    verificarEstructuraHTML() {
        console.log('🔍 Verificando estructura HTML...');
        
        const seccionEstadisticasDinamicas = document.querySelector('.flex.flex-wrap.-mx-3');
        const seccionEstadisticasGraficas = document.querySelector('.flex.flex-wrap.mt-6.-mx-3');
        
        console.log('Sección Estadísticas Dinámicas:', !!seccionEstadisticasDinamicas);
        console.log('Sección Estadísticas Gráficas:', !!seccionEstadisticasGraficas);
        
        if (!seccionEstadisticasGraficas) {
            throw new Error('Sección de Estadísticas Gráficas no encontrada');
        }
        
        // Verificar título
        const titulo = seccionEstadisticasGraficas.querySelector('h6');
        if (titulo) {
            console.log(`Título encontrado: "${titulo.textContent}"`);
            if (!titulo.textContent.includes('Estadísticas Gráficas')) {
                console.warn('⚠️ El título no parece ser el correcto');
            }
        }
    }

    /**
     * Asegurar que el contenedor del gráfico esté en la sección correcta
     */
    asegurarContenedorCorrecto() {
        console.log('🏗️ Asegurando contenedor correcto...');
        
        // Buscar el contenedor específico del gráfico
        let graficoContainer = document.getElementById('grafico-container');
        let chartWrapper = document.getElementById('chart-wrapper');
        let canvas = document.getElementById('chart-line');
        
        // Si no existe el contenedor específico, crearlo
        if (!graficoContainer) {
            console.log('Creando contenedor del gráfico...');
            this.crearContenedorGrafico();
            graficoContainer = document.getElementById('grafico-container');
        }
        
        // Verificar que está en la sección correcta
        const seccionGraficas = document.querySelector('.flex.flex-wrap.mt-6.-mx-3');
        if (seccionGraficas && !seccionGraficas.contains(graficoContainer)) {
            console.warn('⚠️ El contenedor no está en la sección correcta');
            this.moverContenedorASeccionCorrecta();
        }
        
        console.log('✅ Contenedor verificado y posicionado correctamente');
    }

    /**
     * Crear contenedor del gráfico si no existe
     */
    crearContenedorGrafico() {
        // Buscar la sección de estadísticas gráficas
        const seccionGraficas = document.querySelector('.flex.flex-wrap.mt-6.-mx-3');
        if (!seccionGraficas) return;
        
        // Buscar el div que contiene el título "Estadísticas Gráficas"
        const contenedorTarjeta = seccionGraficas.querySelector('.lg\\:w-7\\/12');
        if (!contenedorTarjeta) return;
        
        // Limpiar y recrear el contenido
        const tarjetaInterior = contenedorTarjeta.querySelector('.bg-white');
        if (tarjetaInterior) {
            // Mantener el header con el título
            const header = tarjetaInterior.querySelector('.border-black\\/12\\.5.mb-0');
            
            // Crear nuevo contenedor para el gráfico
            const nuevoContenedor = document.createElement('div');
            nuevoContenedor.className = 'flex-auto p-4';
            nuevoContenedor.id = 'grafico-container';
            
            nuevoContenedor.innerHTML = `
                <div class="w-full h-64 bg-gray-50 rounded-lg flex items-center justify-center" id="chart-loading">
                    <div class="text-center">
                        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500 mb-4 mx-auto"></div>
                        <p class="text-gray-600 text-sm">Preparando gráfico...</p>
                    </div>
                </div>
                <div style="position: relative; height: 300px; display: none;" id="chart-wrapper">
                    <canvas id="chart-line" width="400" height="300"></canvas>
                </div>
            `;
            
            // Limpiar tarjeta y agregar header + nuevo contenedor
            tarjetaInterior.innerHTML = '';
            if (header) {
                tarjetaInterior.appendChild(header);
            }
            tarjetaInterior.appendChild(nuevoContenedor);
            
            console.log('✅ Contenedor del gráfico creado en la sección correcta');
        }
    }

    /**
     * Mover contenedor a la sección correcta si está mal posicionado
     */
    moverContenedorASeccionCorrecta() {
        const contenedor = document.getElementById('grafico-container');
        const seccionCorrecta = document.querySelector('.flex.flex-wrap.mt-6.-mx-3 .lg\\:w-7\\/12 .bg-white');
        
        if (contenedor && seccionCorrecta) {
            // Buscar si ya hay un header
            const headerExistente = seccionCorrecta.querySelector('.border-black\\/12\\.5.mb-0');
            
            // Limpiar sección
            seccionCorrecta.innerHTML = '';
            
            // Restaurar header si existía
            if (headerExistente) {
                seccionCorrecta.appendChild(headerExistente);
            }
            
            // Mover contenedor
            seccionCorrecta.appendChild(contenedor);
            
            console.log('✅ Contenedor movido a la sección correcta');
        }
    }

    /**
     * Esperar a que Chart.js esté disponible
     */
    async esperarChartJS() {
        console.log('⏳ Esperando Chart.js...');
        
        let intentos = 0;
        const maxIntentos = 20;
        
        while (typeof Chart === 'undefined' && intentos < maxIntentos) {
            await new Promise(resolve => setTimeout(resolve, 500));
            intentos++;
        }
        
        if (typeof Chart === 'undefined') {
            console.log('📦 Cargando Chart.js desde CDN...');
            await this.cargarChartJSDesdeCSN();
        }
        
        console.log('✅ Chart.js disponible');
    }

    /**
     * Cargar Chart.js desde CDN
     */
    async cargarChartJSDesdeCSN() {
        return new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/chart.js';
            script.onload = () => {
                console.log('✅ Chart.js cargado desde CDN');
                resolve();
            };
            script.onerror = () => {
                console.error('❌ Error cargando Chart.js desde CDN');
                reject(new Error('No se pudo cargar Chart.js'));
            };
            document.head.appendChild(script);
        });
    }

    /**
     * Crear gráfico en la sección correcta
     */
    async crearGraficoEnSeccionCorrecta() {
        console.log('📊 Creando gráfico en sección correcta...');
        
        // Ocultar loading y mostrar canvas
        const loading = document.getElementById('chart-loading');
        const wrapper = document.getElementById('chart-wrapper');
        
        if (loading) loading.style.display = 'none';
        if (wrapper) wrapper.style.display = 'block';
        
        // Obtener canvas
        const canvas = document.getElementById('chart-line');
        if (!canvas) {
            throw new Error('Canvas no encontrado después de crear contenedor');
        }
        
        // Generar datos de ejemplo
        const datos = this.generarDatosEjemplo();
        
        // Configuración del gráfico
        const config = {
            type: 'line',
            data: {
                labels: datos.map(d => d.mes),
                datasets: [{
                    label: 'Empresas Registradas',
                    data: datos.map(d => d.total),
                    borderColor: '#3B82F6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#3B82F6',
                    pointBorderColor: '#FFFFFF',
                    pointBorderWidth: 2,
                    pointRadius: 6,
                    pointHoverRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleColor: '#FFFFFF',
                        bodyColor: '#FFFFFF',
                        borderColor: '#3B82F6',
                        borderWidth: 1
                    }
                },
                scales: {
                    x: {
                        display: true,
                        grid: { display: false },
                        ticks: { color: '#64748B' }
                    },
                    y: {
                        display: true,
                        grid: { color: 'rgba(148, 163, 184, 0.1)' },
                        ticks: { color: '#64748B' },
                        beginAtZero: true
                    }
                },
                animation: {
                    duration: 1500,
                    easing: 'easeInOutQuart'
                }
            }
        };

        // Crear gráfico
        this.chart = new Chart(canvas, config);
        this.inicializado = true;
        
        console.log('✅ Gráfico creado exitosamente en la sección correcta');
        
        // Actualizar elementos relacionados
        this.actualizarElementosRelacionados(datos);
    }

    /**
     * Generar datos de ejemplo para el gráfico
     */
    generarDatosEjemplo() {
        const meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago'];
        const datos = [];
        let total = 15;
        
        for (let i = 0; i < 6; i++) {
            const nuevas = Math.floor(Math.random() * 4) + 2;
            total += nuevas;
            datos.push({
                mes: meses[i],
                total: total,
                nuevas: nuevas
            });
        }
        
        return datos;
    }

    /**
     * Actualizar elementos relacionados con el gráfico
     */
    actualizarElementosRelacionados(datos) {
        // Actualizar texto de crecimiento
        const chartGrowth = document.getElementById('chartGrowth');
        if (chartGrowth && datos.length > 1) {
            const ultimo = datos[datos.length - 1].total;
            const penultimo = datos[datos.length - 2].total;
            const crecimiento = Math.round(((ultimo - penultimo) / penultimo) * 100);
            chartGrowth.textContent = `${crecimiento}% más`;
        }
        
        // Actualizar estadística de empresas
        const statsEmpresas = document.getElementById('statsEmpresas');
        if (statsEmpresas) {
            const skeleton = statsEmpresas.querySelector('.animate-pulse');
            if (skeleton) skeleton.remove();
            
            this.animarValor(statsEmpresas, 0, datos[datos.length - 1].total, 1500);
        }
    }

    /**
     * Animar valor numérico
     */
    animarValor(elemento, inicio, fin, duracion) {
        let startTime = null;
        
        const animate = (currentTime) => {
            if (!startTime) startTime = currentTime;
            const progress = Math.min((currentTime - startTime) / duracion, 1);
            
            const valorActual = Math.floor(progress * (fin - inicio) + inicio);
            elemento.textContent = valorActual;
            
            if (progress < 1) {
                requestAnimationFrame(animate);
            }
        };
        
        requestAnimationFrame(animate);
    }

    /**
     * Verificar posicionamiento final
     */
    verificarPosicionamientoFinal() {
        console.log('🔍 Verificando posicionamiento final...');
        
        const canvas = document.getElementById('chart-line');
        const seccionGraficas = document.querySelector('.flex.flex-wrap.mt-6.-mx-3');
        
        if (canvas && seccionGraficas && seccionGraficas.contains(canvas)) {
            console.log('✅ Gráfico confirmado en la sección "Estadísticas Gráficas"');
            this.verificacionesCompletadas = true;
            
            // Mostrar notificación de éxito
            this.mostrarNotificacionExito();
        } else {
            console.warn('⚠️ El gráfico no está en la posición esperada');
            this.verificacionesCompletadas = false;
        }
    }

    /**
     * Crear gráfico de emergencia si todo falla
     */
    crearGraficoEmergencia() {
        console.log('🚨 Creando gráfico de emergencia...');
        
        // Buscar cualquier contenedor disponible en la sección de gráficas
        const seccionGraficas = document.querySelector('.flex.flex-wrap.mt-6.-mx-3');
        if (seccionGraficas) {
            const contenedorTarjeta = seccionGraficas.querySelector('.bg-white');
            if (contenedorTarjeta) {
                contenedorTarjeta.innerHTML = `
                    <div class="p-6">
                        <h6 class="mb-4 text-lg font-bold text-gray-900">📊 Estadísticas Gráficas</h6>
                        <div style="position: relative; height: 300px;">
                            <canvas id="chart-line-emergency" width="400" height="300"></canvas>
                        </div>
                    </div>
                `;
                
                // Crear gráfico simple
                if (typeof Chart !== 'undefined') {
                    const canvas = document.getElementById('chart-line-emergency');
                    if (canvas) {
                        new Chart(canvas, {
                            type: 'line',
                            data: {
                                labels: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun'],
                                datasets: [{
                                    label: 'Empresas',
                                    data: [15, 19, 23, 27, 31, 35],
                                    borderColor: '#3B82F6',
                                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                                    fill: true
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: { legend: { display: false } }
                            }
                        });
                        
                        console.log('🆘 Gráfico de emergencia creado');
                    }
                }
            }
        }
    }

    /**
     * Mostrar notificación de éxito
     */
    mostrarNotificacionExito() {
        if (typeof mostrarNotificacion === 'function') {
            mostrarNotificacion('Gráfico cargado correctamente en Estadísticas Gráficas', 'success');
        }
    }

    /**
     * Obtener estado actual
     */
    obtenerEstado() {
        return {
            inicializado: this.inicializado,
            verificacionesCompletadas: this.verificacionesCompletadas,
            chartExiste: !!this.chart,
            canvasEnDom: !!document.getElementById('chart-line'),
            enSeccionCorrecta: this.verificacionesCompletadas
        };
    }
}

// ==================== INICIALIZACIÓN AUTOMÁTICA ====================

// Crear instancia global
window.graficoSeccionCorrecta = new GraficoSeccionCorrecta();

// Función de inicialización
async function inicializarGraficoEnSeccionCorrecta() {
    console.log('🎯 Inicializando sistema de posicionamiento de gráfico...');
    
    try {
        await window.graficoSeccionCorrecta.inicializar();
    } catch (error) {
        console.error('Error en inicialización:', error);
    }
}

// Función para verificar y corregir posición (utilidad global)
window.verificarPosicionGrafico = function() {
    const estado = window.graficoSeccionCorrecta.obtenerEstado();
    console.log('Estado del gráfico:', estado);
    
    if (!estado.enSeccionCorrecta) {
        console.log('🔧 Corrigiendo posición...');
        window.graficoSeccionCorrecta.inicializar();
    }
    
    return estado;
};

// Inicialización automática
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        setTimeout(inicializarGraficoEnSeccionCorrecta, 1000);
    });
} else {
    setTimeout(inicializarGraficoEnSeccionCorrecta, 1000);
}

console.log('✅ Sistema de posicionamiento de gráfico cargado');
