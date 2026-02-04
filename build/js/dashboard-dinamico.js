// ==================== SISTEMA DE GRÁFICOS DINÁMICOS ====================
// Sistema mejorado para cargar estadísticas reales desde la base de datos
// y actualizar los gráficos de empresas registradas dinámicamente

/**
 * Clase principal para manejar las estadísticas del dashboard
 */
class DashboardStats {
  constructor() {
    this.apiBase = './api/';
    this.chart = null;
    this.statsCache = new Map();
    this.cacheTimeout = 5 * 60 * 1000; // 5 minutos
    this.updateInterval = null;
    this.isLoading = false;
  }

  /**
   * Inicializar el sistema de estadísticas
   */
  async init() {
    console.log('📊 Inicializando sistema de estadísticas dinámicas...');
    
    try {
      // Cargar estadísticas iniciales
      await this.cargarTodasLasEstadisticas();
      
      // Inicializar gráfico de empresas
      await this.inicializarGraficoEmpresas();
      
      // Configurar actualización automática cada 5 minutos
      this.configurarActualizacionAutomatica();
      
      console.log('✅ Sistema de estadísticas inicializado correctamente');
      
    } catch (error) {
      console.error('❌ Error inicializando sistema de estadísticas:', error);
      this.mostrarErrorEstadisticas();
    }
  }

  /**
   * Cargar todas las estadísticas desde la API
   */
  async cargarTodasLasEstadisticas() {
    if (this.isLoading) return;
    
    this.isLoading = true;
    this.mostrarSkeletonStats();
    
    try {
      console.log('🔄 Cargando estadísticas desde la base de datos...');
      
      // Cargar estadísticas generales
      const statsResponse = await this.fetchAPI('estadisticas_mejoradas.php?action=general');
      
      if (statsResponse.success) {
        const stats = statsResponse.data;
        
        // Actualizar tarjetas de estadísticas
        this.actualizarTarjetasEstadisticas(stats);
        
        // Guardar en cache
        this.statsCache.set('general', {
          data: stats,
          timestamp: Date.now()
        });
        
        console.log('✅ Estadísticas cargadas correctamente:', stats);
        
      } else {
        throw new Error(statsResponse.message || 'Error obteniendo estadísticas');
      }
      
      // Cargar datos específicos para empresas
      await this.cargarDatosEmpresas();
      
    } catch (error) {
      console.error('❌ Error cargando estadísticas:', error);
      this.mostrarErrorEstadisticas();
      
      // Fallback con datos de ejemplo
      this.usarDatosFallback();
      
    } finally {
      this.isLoading = false;
      this.ocultarSkeletonStats();
    }
  }

  /**
   * Cargar datos específicos de empresas para el gráfico
   */
  async cargarDatosEmpresas() {
    try {
      console.log('🏢 Cargando datos históricos de empresas...');
      
      // Obtener histórico de empresas desde la nueva API
      const historicoResponse = await this.fetchAPI('estadisticas_mejoradas.php?action=empresas_historico');
      
      if (historicoResponse.success) {
        const datosHistoricos = historicoResponse.data;
        
        // Guardar en cache
        this.statsCache.set('empresas_historico', {
          data: datosHistoricos,
          timestamp: Date.now()
        });
        
        console.log('✅ Datos históricos de empresas cargados:', datosHistoricos);
        
      } else {
        throw new Error('Error obteniendo histórico de empresas');
      }
      
    } catch (error) {
      console.error('❌ Error cargando datos de empresas:', error);
      
      // Fallback: generar datos de ejemplo
      const datosFallback = this.generarDatosFallback();
      this.statsCache.set('empresas_historico', {
        data: datosFallback,
        timestamp: Date.now()
      });
    }
  }

  /**
   * Actualizar las tarjetas de estadísticas en el dashboard
   */
  actualizarTarjetasEstadisticas(stats) {
    // Estadísticas de comités
    if (stats.comites) {
      this.actualizarTarjeta('statsComites', stats.comites.total_miembros || 0, `+${stats.comites.porcentaje_crecimiento || 0}%`);
    }
    
    // Estadísticas de empresas
    if (stats.empresas) {
      this.actualizarTarjeta('statsEmpresas', stats.empresas.total || 0, `+${stats.empresas.porcentaje_crecimiento || 0}%`);
    }
    
    // Estadísticas de descuentos
    if (stats.descuentos) {
      this.actualizarTarjeta('statsDescuentos', stats.descuentos.total || 0, `+${stats.descuentos.porcentaje_crecimiento || 0}%`);
    }
    
    // Estadísticas de eventos
    if (stats.eventos) {
      this.actualizarTarjeta('statsEventos', stats.eventos.total || 0, `+${stats.eventos.porcentaje_crecimiento || 0}%`);
    }
  }

  /**
   * Actualizar una tarjeta específica con animación
   */
  actualizarTarjeta(elementId, valor, crecimiento) {
    const elemento = document.getElementById(elementId);
    const elementoCrecimiento = document.getElementById(elementId.replace('stats', '').toLowerCase() + 'Growth');
    
    if (elemento) {
      // Quitar skeleton
      const skeleton = elemento.querySelector('.animate-pulse');
      if (skeleton) {
        skeleton.remove();
      }
      
      // Animar el valor
      this.animarValor(elemento, 0, valor, 1500);
    }
    
    if (elementoCrecimiento && crecimiento) {
      elementoCrecimiento.textContent = crecimiento;
      elementoCrecimiento.className = 'text-sm font-bold leading-normal text-emerald-500';
    }
  }

  /**
   * Inicializar el gráfico de empresas registradas
   */
  async inicializarGraficoEmpresas() {
    const canvas = document.getElementById('chart-line');
    if (!canvas) {
      console.warn('Canvas del gráfico no encontrado');
      return;
    }

    try {
      // Obtener datos del cache o usar fallback
      let datosHistoricos = this.obtenerDatosCache('empresas_historico');
      
      if (!datosHistoricos) {
        console.log('⚠️ Usando datos de fallback para el gráfico');
        datosHistoricos = this.generarDatosFallback();
      }

      // Configuración del gráfico mejorada
      const config = {
        type: 'line',
        data: {
          labels: datosHistoricos.map(d => d.mes),
          datasets: [{
            label: 'Empresas Registradas',
            data: datosHistoricos.map(d => d.empresas),
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
            legend: {
              display: false
            },
            tooltip: {
              backgroundColor: 'rgba(0, 0, 0, 0.8)',
              titleColor: '#FFFFFF',
              bodyColor: '#FFFFFF',
              borderColor: '#3B82F6',
              borderWidth: 1,
              callbacks: {
                title: function(context) {
                  const index = context[0].dataIndex;
                  const año = datosHistoricos[index].año || new Date().getFullYear();
                  return `${context[0].label} ${año}`;
                },
                label: function(context) {
                  return `Total de empresas: ${context.parsed.y}`;
                },
                afterLabel: function(context) {
                  const index = context.dataIndex;
                  if (index >= 0 && datosHistoricos[index].nuevas > 0) {
                    return `Empresas nuevas: +${datosHistoricos[index].nuevas}`;
                  }
                  return '';
                }
              }
            }
          },
          scales: {
            x: {
              display: true,
              grid: {
                display: false
              },
              ticks: {
                color: '#64748B'
              }
            },
            y: {
              display: true,
              grid: {
                color: 'rgba(148, 163, 184, 0.1)'
              },
              ticks: {
                color: '#64748B',
                callback: function(value) {
                  return value;
                }
              },
              beginAtZero: true
            }
          },
          interaction: {
            intersect: false,
            mode: 'index'
          },
          animation: {
            duration: 2000,
            easing: 'easeInOutQuart'
          }
        }
      };

      // Crear el gráfico
      if (this.chart) {
        this.chart.destroy();
      }

      this.chart = new Chart(canvas, config);
      
      // Actualizar texto de crecimiento
      this.actualizarTextoGrafico(datosHistoricos);
      
      console.log('✅ Gráfico de empresas inicializado correctamente');
      
    } catch (error) {
      console.error('❌ Error inicializando gráfico:', error);
      this.mostrarErrorGrafico(canvas);
    }
  }

  // Resto de métodos...
  actualizarTextoGrafico(datos) {
    const chartGrowth = document.getElementById('chartGrowth');
    if (chartGrowth && datos.length > 1) {
      const ultimo = datos[datos.length - 1].empresas;
      const penultimo = datos[datos.length - 2].empresas;
      const crecimiento = penultimo > 0 ? Math.round(((ultimo - penultimo) / penultimo) * 100) : 0;
      
      chartGrowth.textContent = `${crecimiento}% más`;
    }
  }

  async actualizarGrafico() {
    if (!this.chart) return;
    
    try {
      console.log('🔄 Actualizando gráfico...');
      
      await this.cargarDatosEmpresas();
      const datosHistoricos = this.obtenerDatosCache('empresas_historico');
      
      if (datosHistoricos) {
        this.chart.data.labels = datosHistoricos.map(d => d.mes);
        this.chart.data.datasets[0].data = datosHistoricos.map(d => d.empresas);
        this.chart.update('active');
        
        this.actualizarTextoGrafico(datosHistoricos);
        
        console.log('✅ Gráfico actualizado correctamente');
      }
      
    } catch (error) {
      console.error('❌ Error actualizando gráfico:', error);
    }
  }

  configurarActualizacionAutomatica() {
    // Actualizar cada 5 minutos
    this.updateInterval = setInterval(async () => {
      console.log('⏰ Actualización automática de estadísticas...');
      await this.cargarTodasLasEstadisticas();
      await this.actualizarGrafico();
    }, 5 * 60 * 1000);
    
    console.log('⏰ Actualización automática configurada (cada 5 minutos)');
  }

  async fetchAPI(endpoint, options = {}) {
    const url = `${this.apiBase}${endpoint}`;
    
    const defaultOptions = {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      }
    };
    
    const response = await fetch(url, { ...defaultOptions, ...options });
    
    if (!response.ok) {
      throw new Error(`HTTP Error: ${response.status}`);
    }
    
    const text = await response.text();
    
    // Limpiar posibles warnings de PHP
    let cleanText = text;
    if (text.includes('<br />') || text.includes('Warning:')) {
      const jsonStart = text.indexOf('{');
      const jsonStartArray = text.indexOf('[');
      const actualStart = Math.min(
        jsonStart !== -1 ? jsonStart : Infinity,
        jsonStartArray !== -1 ? jsonStartArray : Infinity
      );
      
      if (actualStart !== Infinity) {
        cleanText = text.substring(actualStart);
      }
    }
    
    return JSON.parse(cleanText);
  }

  obtenerDatosCache(key) {
    const cached = this.statsCache.get(key);
    if (cached && (Date.now() - cached.timestamp) < this.cacheTimeout) {
      return cached.data;
    }
    return null;
  }

  mostrarSkeletonStats() {
    const statElements = ['statsComites', 'statsEmpresas', 'statsDescuentos', 'statsEventos'];
    
    statElements.forEach(elementId => {
      const elemento = document.getElementById(elementId);
      if (elemento) {
        elemento.innerHTML = '<span class="animate-pulse bg-gray-200 rounded h-6 w-12 inline-block"></span>';
      }
    });
  }

  ocultarSkeletonStats() {
    // Los valores reales se muestran en actualizarTarjetasEstadisticas
  }

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

  generarDatosFallback() {
    const meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago'];
    return meses.map((mes, index) => ({
      mes: mes,
      empresas: 15 + (index * 5) + Math.floor(Math.random() * 3),
      nuevas: index > 0 ? Math.floor(Math.random() * 3) + 1 : 15,
      año: new Date().getFullYear(),
      numero_mes: index + 1
    }));
  }

  usarDatosFallback() {
    console.log('⚠️ Usando datos de fallback...');
    
    // Estadísticas de fallback
    const statsFallback = {
      comites: { total_miembros: 45, porcentaje_crecimiento: 12 },
      empresas: { total: 38, porcentaje_crecimiento: 8 },
      descuentos: { total: 12, porcentaje_crecimiento: 15 },
      eventos: { total: 8, porcentaje_crecimiento: 6 }
    };
    
    this.actualizarTarjetasEstadisticas(statsFallback);
    
    // Datos de gráfico de fallback
    const datosFallback = this.generarDatosFallback();
    this.statsCache.set('empresas_historico', {
      data: datosFallback,
      timestamp: Date.now()
    });
  }

  mostrarErrorEstadisticas() {
    const statElements = ['statsComites', 'statsEmpresas', 'statsDescuentos', 'statsEventos'];
    
    statElements.forEach(elementId => {
      const elemento = document.getElementById(elementId);
      if (elemento) {
        elemento.innerHTML = '<span class="text-red-500">Error</span>';
      }
    });
  }

  mostrarErrorGrafico(canvas) {
    const container = canvas.parentElement;
    if (container) {
      container.innerHTML = `
        <div class="flex items-center justify-center h-64 bg-gray-50 rounded-lg">
          <div class="text-center">
            <i class="fas fa-chart-line text-4xl text-gray-400 mb-3"></i>
            <p class="text-gray-500 mb-3">Error cargando gráfico</p>
            <button onclick="dashboardStats.inicializarGraficoEmpresas()" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600">
              <i class="fas fa-retry mr-2"></i>Reintentar
            </button>
          </div>
        </div>
      `;
    }
  }

  async actualizarManualmente() {
    console.log('🔄 Actualización manual solicitada...');
    
    if (typeof mostrarNotificacion === 'function') {
      mostrarNotificacion('Actualizando estadísticas...', 'info');
    }
    
    try {
      await this.cargarTodasLasEstadisticas();
      await this.actualizarGrafico();
      
      if (typeof mostrarNotificacion === 'function') {
        mostrarNotificacion('Estadísticas actualizadas correctamente', 'success');
      }
      
    } catch (error) {
      console.error('❌ Error en actualización manual:', error);
      
      if (typeof mostrarNotificacion === 'function') {
        mostrarNotificacion('Error al actualizar estadísticas', 'error');
      }
    }
  }

  destroy() {
    if (this.updateInterval) {
      clearInterval(this.updateInterval);
    }
    
    if (this.chart) {
      this.chart.destroy();
    }
    
    this.statsCache.clear();
  }
}

// ==================== INICIALIZACIÓN GLOBAL ====================

// Instancia global del sistema de estadísticas
let dashboardStats = null;

async function inicializarEstadisticasDinamicas() {
  try {
    console.log('🚀 Inicializando sistema de estadísticas dinámicas...');
    
    // Crear instancia global
    dashboardStats = new DashboardStats();
    window.dashboardStats = dashboardStats; // Para debugging
    
    // Esperar a que Chart.js esté disponible
    await esperarChartJS();
    
    // Inicializar el sistema
    await dashboardStats.init();
    
    console.log('✅ Sistema de estadísticas dinámicas inicializado');
    
  } catch (error) {
    console.error('❌ Error inicializando estadísticas dinámicas:', error);
  }
}

function esperarChartJS() {
  return new Promise((resolve) => {
    if (typeof Chart !== 'undefined') {
      resolve();
      return;
    }
    
    const checkChart = () => {
      if (typeof Chart !== 'undefined') {
        resolve();
      } else {
        setTimeout(checkChart, 100);
      }
    };
    
    checkChart();
  });
}

async function actualizarEstadisticas() {
  if (dashboardStats) {
    await dashboardStats.actualizarManualmente();
  } else {
    console.warn('Sistema de estadísticas no inicializado');
  }
}

// ==================== INTEGRACIÓN CON EL DASHBOARD PRINCIPAL ====================

// Agregar al inicializador principal del dashboard
document.addEventListener('DOMContentLoaded', function() {
  // Esperar un poco para que otros sistemas se inicialicen
  setTimeout(inicializarEstadisticasDinamicas, 1000);
});

// También manejar el caso donde DOMContentLoaded ya se ejecutó
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', () => {
    setTimeout(inicializarEstadisticasDinamicas, 1000);
  });
} else {
  setTimeout(inicializarEstadisticasDinamicas, 1000);
}

// Limpiar cuando se abandona la página
window.addEventListener('beforeunload', function() {
  if (dashboardStats) {
    dashboardStats.destroy();
  }
});
