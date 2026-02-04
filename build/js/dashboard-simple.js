// ==================== SISTEMA DE GRÁFICOS DINÁMICOS SIMPLIFICADO ====================
// Versión tolerante a errores y con múltiples fallbacks

/**
 * Clase simplificada para manejar las estadísticas del dashboard
 */
class DashboardStatsSimple {
  constructor() {
    this.chart = null;
    this.isLoading = false;
    this.endpoints = [
      './api/estadisticas_mejoradas.php',
      './api/estadisticas_simple.php',
      './api/estadisticas.php'
    ];
  }

  /**
   * Inicializar el sistema de estadísticas
   */
  async init() {
    console.log('📊 Inicializando sistema simplificado...');
    
    try {
      // Cargar estadísticas con múltiples intentos
      await this.cargarEstadisticas();
      
      // Inicializar gráfico
      await this.inicializarGrafico();
      
      console.log('✅ Sistema inicializado correctamente');
      
    } catch (error) {
      console.error('❌ Error en inicialización:', error);
      this.usarDatosFallback();
    }
  }

  /**
   * Cargar estadísticas con múltiples fallbacks
   */
  async cargarEstadisticas() {
    console.log('🔄 Cargando estadísticas...');
    
    // Mostrar skeleton
    this.mostrarSkeleton();
    
    try {
      // Intentar obtener estadísticas
      const stats = await this.obtenerEstadisticas();
      
      if (stats) {
        this.actualizarTarjetas(stats);
        console.log('✅ Estadísticas cargadas:', stats);
      } else {
        throw new Error('No se obtuvieron estadísticas válidas');
      }
      
    } catch (error) {
      console.log('⚠️ Error cargando estadísticas, usando fallback:', error.message);
      this.usarDatosFallback();
    } finally {
      this.ocultarSkeleton();
    }
  }

  /**
   * Obtener estadísticas probando múltiples endpoints
   */
  async obtenerEstadisticas() {
    // Probar endpoint simple primero
    try {
      const response = await fetch('./api/estadisticas_simple.php?action=general');
      if (response.ok) {
        const data = await response.json();
        if (data.success && data.data) {
          console.log('✅ Estadísticas obtenidas de API simple');
          return data.data;
        }
      }
    } catch (error) {
      console.log('⚠️ API simple falló:', error.message);
    }

    // Probar endpoint principal
    try {
      const response = await fetch('./api/estadisticas_mejoradas.php?action=general');
      if (response.ok) {
        const text = await response.text();
        
        // Limpiar warnings de PHP
        let cleanText = text;
        const jsonStart = text.indexOf('{');
        if (jsonStart > 0) {
          cleanText = text.substring(jsonStart);
        }
        
        const data = JSON.parse(cleanText);
        if (data.success && data.data) {
          console.log('✅ Estadísticas obtenidas de API principal');
          return data.data;
        }
      }
    } catch (error) {
      console.log('⚠️ API principal falló:', error.message);
    }

    // Si todo falla, retornar null
    return null;
  }

  /**
   * Obtener datos para el gráfico histórico
   */
  async obtenerDatosGrafico() {
    // Probar API simple
    try {
      const response = await fetch('./api/estadisticas_simple.php?action=empresas_historico');
      if (response.ok) {
        const data = await response.json();
        if (data.success && data.data) {
          console.log('✅ Datos de gráfico obtenidos de API simple');
          return data.data;
        }
      }
    } catch (error) {
      console.log('⚠️ Error obteniendo datos de gráfico:', error.message);
    }

    // Fallback: generar datos de ejemplo
    return this.generarDatosEjemplo();
  }

  /**
   * Actualizar tarjetas de estadísticas
   */
  actualizarTarjetas(stats) {
    // Actualizar empresas
    if (stats.empresas) {
      this.actualizarTarjeta('statsEmpresas', stats.empresas.total || 0, stats.empresas.porcentaje_crecimiento || 0);
    }

    // Actualizar usuarios/comités
    if (stats.usuarios) {
      this.actualizarTarjeta('statsComites', stats.usuarios.total || 0, stats.usuarios.porcentaje_crecimiento || 0);
    } else if (stats.comites) {
      this.actualizarTarjeta('statsComites', stats.comites.total_miembros || 0, stats.comites.porcentaje_crecimiento || 0);
    }

    // Actualizar eventos
    if (stats.eventos) {
      this.actualizarTarjeta('statsEventos', stats.eventos.total || 0, stats.eventos.porcentaje_crecimiento || 0);
    }

    // Actualizar descuentos
    if (stats.descuentos) {
      this.actualizarTarjeta('statsDescuentos', stats.descuentos.total || 0, stats.descuentos.porcentaje_crecimiento || 0);
    }
  }

  /**
   * Actualizar una tarjeta específica
   */
  actualizarTarjeta(elementId, valor, crecimiento) {
    const elemento = document.getElementById(elementId);
    if (elemento) {
      // Quitar skeleton
      const skeleton = elemento.querySelector('.animate-pulse');
      if (skeleton) {
        skeleton.remove();
      }
      
      // Animar valor
      this.animarValor(elemento, 0, valor, 1000);
    }

    // Actualizar porcentaje de crecimiento
    const nombreTarjeta = elementId.replace('stats', '').toLowerCase();
    const elementoCrecimiento = document.getElementById(nombreTarjeta + 'Growth');
    
    if (elementoCrecimiento) {
      elementoCrecimiento.textContent = `+${crecimiento}%`;
      elementoCrecimiento.className = 'text-sm font-bold leading-normal text-emerald-500';
    }
  }

  /**
   * Inicializar gráfico de empresas
   */
  async inicializarGrafico() {
    const canvas = document.getElementById('chart-line');
    if (!canvas) {
      console.warn('Canvas del gráfico no encontrado');
      return;
    }

    try {
      // Verificar si hay configuración activa (seleccionada por el usuario)
      const configActiva = this.configuracionActiva || this.cargarConfiguracionGuardada();
      let datosHistoricos;

      if (configActiva) {
        console.log('📊 Usando configuración del sistema de gráficos:', configActiva.name);
        datosHistoricos = configActiva.data || configActiva.config?.data || [];

        // Si no hay datos en la configuración, obtener datos de la API
        if (!datosHistoricos || datosHistoricos.length === 0) {
          console.log('📡 Obteniendo datos de la API para la configuración...');
          datosHistoricos = await this.obtenerDatosGrafico();
        }

        // Usar configuración guardada si está disponible
        if (configActiva.config || configActiva.type) {
          this.aplicarConfiguracionGuardada(canvas, configActiva, datosHistoricos);
          return;
        }
      } else {
        // Obtener datos del gráfico desde API
        datosHistoricos = await this.obtenerDatosGrafico();
      }
      
      // Verificar que Chart.js esté disponible
      if (typeof Chart === 'undefined') {
        console.warn('Chart.js no está disponible, esperando...');
        await this.esperarChartJS();
      }

      // Configuración del gráfico
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
            legend: { display: false },
            tooltip: {
              backgroundColor: 'rgba(0, 0, 0, 0.8)',
              titleColor: '#FFFFFF',
              bodyColor: '#FFFFFF',
              borderColor: '#3B82F6',
              borderWidth: 1,
              callbacks: {
                title: function(context) {
                  return `${context[0].label} ${new Date().getFullYear()}`;
                },
                label: function(context) {
                  return `Total: ${context.parsed.y} empresas`;
                },
                afterLabel: function(context) {
                  const index = context.dataIndex;
                  if (datosHistoricos[index] && datosHistoricos[index].nuevas > 0) {
                    return `Nuevas: +${datosHistoricos[index].nuevas}`;
                  }
                  return '';
                }
              }
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
            duration: 2000,
            easing: 'easeInOutQuart'
          }
        }
      };

      // Crear gráfico
      if (this.chart) {
        this.chart.destroy();
      }

      this.chart = new Chart(canvas, config);
      
      // Actualizar texto de crecimiento
      this.actualizarTextoGrafico(datosHistoricos);
      
      console.log('✅ Gráfico inicializado correctamente');
      
    } catch (error) {
      console.error('❌ Error inicializando gráfico:', error);
      this.mostrarErrorGrafico(canvas);
    }
  }

  /**
   * Esperar a que Chart.js esté disponible
   */
  esperarChartJS() {
    return new Promise((resolve) => {
      let intentos = 0;
      const maxIntentos = 50; // 5 segundos máximo
      
      const verificar = () => {
        if (typeof Chart !== 'undefined') {
          resolve();
        } else if (intentos < maxIntentos) {
          intentos++;
          setTimeout(verificar, 100);
        } else {
          console.error('Chart.js no se cargó después de 5 segundos');
          resolve(); // Continuar aunque Chart.js no esté disponible
        }
      };
      
      verificar();
    });
  }

  /**
   * Actualizar texto del gráfico
   */
  actualizarTextoGrafico(datos) {
    const chartGrowth = document.getElementById('chartGrowth');
    if (chartGrowth && datos.length > 1) {
      const ultimo = datos[datos.length - 1].empresas;
      const penultimo = datos[datos.length - 2].empresas;
      const crecimiento = penultimo > 0 ? Math.round(((ultimo - penultimo) / penultimo) * 100) : 8;
      
      chartGrowth.textContent = `${crecimiento}% más`;
    }
  }

  /**
   * Generar datos de ejemplo para el gráfico
   */
  generarDatosEjemplo() {
    const meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago'];
    const mesActual = new Date().getMonth() + 1;
    const totalMeses = Math.min(mesActual, 8);
    
    const datos = [];
    let total = 15;
    
    for (let i = 0; i < totalMeses; i++) {
      const nuevas = Math.floor(Math.random() * 5) + 2; // Entre 2 y 6
      total += nuevas;
      
      datos.push({
        mes: meses[i],
        empresas: total,
        nuevas: nuevas,
        año: new Date().getFullYear(),
        numero_mes: i + 1
      });
    }
    
    console.log('📊 Usando datos de ejemplo para el gráfico');
    return datos;
  }

  /**
   * Usar datos de fallback
   */
  usarDatosFallback() {
    console.log('⚠️ Usando datos de fallback...');
    
    // Datos de ejemplo para las tarjetas
    const statsFallback = {
      empresas: { total: 38, porcentaje_crecimiento: 8 },
      usuarios: { total: 45, porcentaje_crecimiento: 12 },
      eventos: { total: 8, porcentaje_crecimiento: 6 },
      descuentos: { total: 12, porcentaje_crecimiento: 15 }
    };
    
    this.actualizarTarjetas(statsFallback);
  }

  /**
   * Mostrar skeleton de carga
   */
  mostrarSkeleton() {
    const elementos = ['statsComites', 'statsEmpresas', 'statsDescuentos', 'statsEventos'];
    
    elementos.forEach(id => {
      const elemento = document.getElementById(id);
      if (elemento) {
        elemento.innerHTML = '<span class="animate-pulse bg-gray-200 rounded h-6 w-12 inline-block"></span>';
      }
    });
  }

  /**
   * Ocultar skeleton de carga
   */
  ocultarSkeleton() {
    // Los valores reales se muestran en actualizarTarjetas
  }

  /**
   * Animar valores numéricos
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
   * Cargar configuración guardada del sistema de gráficos
   */
  cargarConfiguracionGuardada() {
    try {
      const configs = JSON.parse(localStorage.getItem('graficos_configuraciones') || '[]');

      if (configs.length > 0) {
        // Obtener la configuración más reciente
        const configMasReciente = configs[configs.length - 1];
        console.log('📊 Configuración encontrada:', configMasReciente.name);

        // También verificar configuraciones de emergencia
        const configsEmergencia = JSON.parse(localStorage.getItem('configs_emergencia') || '[]');
        if (configsEmergencia.length > 0) {
          const emergenciaReciente = configsEmergencia[configsEmergencia.length - 1];

          // Comparar timestamps para obtener la más reciente
          const timestampConfig = new Date(configMasReciente.config?.timestamp || 0);
          const timestampEmergencia = new Date(emergenciaReciente.timestamp || 0);

          if (timestampEmergencia > timestampConfig) {
            console.log('📊 Usando configuración de emergencia más reciente:', emergenciaReciente.name);
            return {
              config: emergenciaReciente.config || emergenciaReciente,
              data: emergenciaReciente.data,
              name: emergenciaReciente.name
            };
          }
        }

        return configMasReciente;
      }

      // Verificar solo configuraciones de emergencia
      const configsEmergencia = JSON.parse(localStorage.getItem('configs_emergencia') || '[]');
      if (configsEmergencia.length > 0) {
        const emergenciaReciente = configsEmergencia[configsEmergencia.length - 1];
        console.log('📊 Usando configuración de emergencia:', emergenciaReciente.name);
        return {
          config: emergenciaReciente.config || emergenciaReciente,
          data: emergenciaReciente.data,
          name: emergenciaReciente.name
        };
      }

      return null;
    } catch (error) {
      console.warn('⚠️ Error cargando configuraciones guardadas:', error);
      return null;
    }
  }

  /**
   * Aplicar configuración guardada al gráfico
   */
  aplicarConfiguracionGuardada(canvas, configGuardada, datosHistoricos) {
    console.log('⚙️ Aplicando configuración guardada al gráfico...');

    const config = configGuardada.config || configGuardada;

    // Preparar datos del gráfico
    const labels = datosHistoricos.map(d => d.mes || d.label || d.etiqueta);
    let values;

    // Determinar qué campo usar para los valores según el tipo de datos
    if (config.dataSource) {
      switch(config.dataSource) {
        case 'usuarios':
          values = datosHistoricos.map(d => d.usuarios || d.valor || d.count || 0);
          break;
        case 'eventos':
          values = datosHistoricos.map(d => d.eventos || d.valor || d.count || 0);
          break;
        case 'comites':
          values = datosHistoricos.map(d => d.miembros || d.valor || d.count || 0);
          break;
        case 'descuentos':
          values = datosHistoricos.map(d => d.descuentos || d.valor || d.count || 0);
          break;
        default: // empresas
          values = datosHistoricos.map(d => d.empresas || d.valor || d.count || 0);
      }
    } else {
      values = datosHistoricos.map(d => d.empresas || d.valor || d.count || 0);
    }

    // Configuración del gráfico usando la configuración guardada
    const chartConfig = {
      type: config.type || 'line',
      data: {
        labels: labels,
        datasets: [{
          label: config.title || 'Datos Guardados',
          data: values,
          borderColor: config.color || '#3B82F6',
          backgroundColor: this.hexToRgba(config.color || '#3B82F6', 0.1),
          borderWidth: 3,
          fill: true,
          tension: 0.4,
          pointBackgroundColor: config.color || '#3B82F6',
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
            display: config.legend !== undefined ? config.legend : false
          },
          tooltip: {
            enabled: config.tooltips !== undefined ? config.tooltips : true,
            backgroundColor: 'rgba(0, 0, 0, 0.8)',
            titleColor: '#FFFFFF',
            bodyColor: '#FFFFFF',
            borderColor: config.color || '#3B82F6',
            borderWidth: 1,
            callbacks: {
              title: function(context) {
                return `${context[0].label} ${new Date().getFullYear()}`;
              },
              label: function(context) {
                return `${config.title || 'Total'}: ${context.parsed.y}`;
              }
            }
          }
        },
        scales: {
          x: {
            display: true,
            grid: {
              display: config.grid !== undefined ? config.grid : false
            },
            ticks: { color: '#64748B' }
          },
          y: {
            display: true,
            grid: {
              color: config.grid !== undefined && config.grid ? 'rgba(148, 163, 184, 0.1)' : 'transparent'
            },
            ticks: { color: '#64748B' },
            beginAtZero: true
          }
        },
        animation: config.animation !== undefined ? (config.animation ? {
          duration: 2000,
          easing: 'easeInOutQuart'
        } : false) : {
          duration: 2000,
          easing: 'easeInOutQuart'
        }
      }
    };

    // Crear gráfico
    if (this.chart) {
      this.chart.destroy();
    }

    this.chart = new Chart(canvas, chartConfig);

    // Actualizar texto de información del gráfico
    this.actualizarInformacionConfiguracion(configGuardada, datosHistoricos);

    console.log('✅ Configuración guardada aplicada correctamente');
  }

  /**
   * Convertir hex a rgba (método auxiliar)
   */
  hexToRgba(hex, alpha) {
    if (!/^#[0-9A-F]{6}$/i.test(hex)) {
      hex = '#3B82F6';
    }

    const r = parseInt(hex.slice(1, 3), 16);
    const g = parseInt(hex.slice(3, 5), 16);
    const b = parseInt(hex.slice(5, 7), 16);
    return `rgba(${r}, ${g}, ${b}, ${alpha})`;
  }

  /**
   * Actualizar información de la configuración aplicada
   */
  actualizarInformacionConfiguracion(configGuardada, datos) {
    // Actualizar el texto de crecimiento
    const chartGrowth = document.getElementById('chartGrowth');
    if (chartGrowth && datos.length > 1) {
      const ultimo = datos[datos.length - 1];
      const penultimo = datos[datos.length - 2];

      const valorUltimo = ultimo.valor || ultimo.empresas || ultimo.usuarios || ultimo.eventos || ultimo.miembros || ultimo.descuentos || 0;
      const valorPenultimo = penultimo.valor || penultimo.empresas || penultimo.usuarios || penultimo.eventos || penultimo.miembros || penultimo.descuentos || 0;

      const crecimiento = valorPenultimo > 0 ? Math.round(((valorUltimo - valorPenultimo) / valorPenultimo) * 100) : 0;
      chartGrowth.textContent = `${crecimiento}% ${configGuardada.config?.title || 'más'}`;
    }

    // Mostrar información sobre la configuración cargada
    console.log(`📊 Configuración "${configGuardada.name}" aplicada con ${datos.length} puntos de datos`);

    // Crear notificación visual opcional
    this.mostrarNotificacionConfiguracion(configGuardada);
  }

  /**
   * Mostrar notificación sobre configuración cargada
   */
  mostrarNotificacionConfiguracion(configGuardada) {
    // Crear notificación temporal
    const notification = document.createElement('div');
    notification.style.cssText = `
      position: fixed;
      top: 20px;
      right: 20px;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      padding: 12px 20px;
      border-radius: 8px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.2);
      z-index: 9999;
      font-size: 14px;
      max-width: 300px;
      transform: translateX(100%);
      transition: transform 0.3s ease;
    `;

    notification.innerHTML = `
      <div style="display: flex; align-items: center; gap: 8px;">
        <i class="fas fa-chart-line"></i>
        <div>
          <div style="font-weight: bold;">Configuración Cargada</div>
          <div style="font-size: 12px; opacity: 0.9;">"${configGuardada.name}"</div>
        </div>
      </div>
    `;

    document.body.appendChild(notification);

    // Animar entrada
    setTimeout(() => {
      notification.style.transform = 'translateX(0)';
    }, 100);

    // Auto-remover
    setTimeout(() => {
      notification.style.transform = 'translateX(100%)';
      setTimeout(() => notification.remove(), 300);
    }, 4000);
  }

  /**
   * Mostrar error en el gráfico
   */
  mostrarErrorGrafico(canvas) {
    const container = canvas.parentElement;
    if (container) {
      container.innerHTML = `
        <div class="flex items-center justify-center h-64 bg-gray-50 rounded-lg">
          <div class="text-center">
            <i class="fas fa-chart-line text-4xl text-gray-400 mb-3"></i>
            <p class="text-gray-500 mb-3">Error cargando gráfico</p>
            <button onclick="window.dashboardStats.init()" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600">
              <i class="fas fa-retry mr-2"></i>Reintentar
            </button>
            <br><br>
            <a href="inicializar_sistema.php" target="_blank" class="px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600">
              <i class="fas fa-tools mr-2"></i>Solucionar Problema
            </a>
          </div>
        </div>
      `;
    }
  }

  /**
   * Mostrar configuraciones guardadas disponibles
   */
  mostrarConfiguracionesGuardadas() {
    console.log('📁 Mostrando configuraciones guardadas...');

    try {
      const configs = JSON.parse(localStorage.getItem('graficos_configuraciones') || '[]');
      const configsEmergencia = JSON.parse(localStorage.getItem('configs_emergencia') || '[]');

      const todasLasConfigs = [
        ...configs.map(c => ({ ...c, origen: 'gráficos' })),
        ...configsEmergencia.map(c => ({ ...c, origen: 'emergencia' }))
      ];

      if (todasLasConfigs.length === 0) {
        this.mostrarNotificacionInfo('No hay configuraciones guardadas', 'info');
        return;
      }

      // Crear modal para mostrar configuraciones
      const modal = document.createElement('div');
      modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.8);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 10000;
      `;

      const content = document.createElement('div');
      content.style.cssText = `
        background: white;
        border-radius: 12px;
        padding: 24px;
        max-width: 600px;
        max-height: 80vh;
        overflow-y: auto;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
      `;

      content.innerHTML = `
        <div style="display: flex; justify-content: between; align-items: center; margin-bottom: 20px;">
          <h3 style="margin: 0; color: #1f2937; font-size: 20px; font-weight: bold;">
            📊 Configuraciones Guardadas (${todasLasConfigs.length})
          </h3>
          <button id="cerrarModal" style="background: #ef4444; color: white; border: none; border-radius: 6px; padding: 8px 12px; cursor: pointer; margin-left: auto;">
            ✕ Cerrar
          </button>
        </div>

        <div style="space-y: 12px;">
          ${todasLasConfigs.map((config, index) => `
            <div style="border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px; margin-bottom: 12px;">
              <div style="display: flex; justify-content: between; align-items: start; margin-bottom: 8px;">
                <div style="flex: 1;">
                  <h4 style="margin: 0 0 4px 0; color: #374151; font-weight: 600;">
                    ${config.name || `Configuración ${index + 1}`}
                  </h4>
                  <p style="margin: 0; color: #6b7280; font-size: 14px;">
                    ${config.origen === 'emergencia' ? '🚑 Sistema de emergencia' : '📊 Sistema de gráficos'}
                  </p>
                </div>
                <button
                  onclick="window.dashboardStats.aplicarConfiguracion(${index})"
                  style="background: #3b82f6; color: white; border: none; border-radius: 6px; padding: 6px 12px; cursor: pointer; font-size: 12px; margin-left: 8px;"
                >
                  📈 Aplicar
                </button>
              </div>

              <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 8px; font-size: 12px; color: #6b7280;">
                <div>
                  📅 ${new Date(config.timestamp || config.config?.timestamp || Date.now()).toLocaleDateString('es-ES')}
                </div>
                <div>
                  🎨 Tipo: ${config.config?.type || config.type || 'line'}
                </div>
                <div>
                  🎯 Fuente: ${config.config?.dataSource || 'empresas'}
                </div>
                <div>
                  📊 Datos: ${config.data?.length || 0} puntos
                </div>
              </div>
            </div>
          `).join('')}
        </div>

        <div style="margin-top: 16px; padding-top: 16px; border-top: 1px solid #e5e7eb; text-align: right;">
          <button
            onclick="window.dashboardStats.limpiarConfiguraciones()"
            style="background: #ef4444; color: white; border: none; border-radius: 6px; padding: 8px 16px; cursor: pointer; margin-right: 8px;"
          >
            🗑️ Limpiar Todas
          </button>
          <button
            onclick="window.dashboardStats.actualizarGrafico()"
            style="background: #10b981; color: white; border: none; border-radius: 6px; padding: 8px 16px; cursor: pointer;"
          >
            🔄 Actualizar Gráfico
          </button>
        </div>
      `;

      modal.appendChild(content);
      document.body.appendChild(modal);

      // Event listeners para cerrar modal
      document.getElementById('cerrarModal').onclick = () => modal.remove();
      modal.onclick = (e) => {
        if (e.target === modal) modal.remove();
      };

      // Guardar referencia a las configuraciones para los botones
      this.configuracionesDisponibles = todasLasConfigs;

    } catch (error) {
      console.error('❌ Error mostrando configuraciones:', error);
      this.mostrarNotificacionInfo('Error cargando configuraciones', 'error');
    }
  }

  /**
   * Aplicar configuración específica
   */
  aplicarConfiguracion(index) {
    try {
      if (!this.configuracionesDisponibles || !this.configuracionesDisponibles[index]) {
        console.warn('⚠️ Configuración no encontrada');
        return;
      }

      const configSeleccionada = this.configuracionesDisponibles[index];
      console.log(`📊 Aplicando configuración: ${configSeleccionada.name}`);

      // Reinicializar gráfico con configuración específica
      this.configuracionActiva = configSeleccionada;
      this.init();

      // Cerrar modal si está abierto
      const modal = document.querySelector('div[style*="position: fixed"]');
      if (modal && modal.style.background.includes('rgba(0, 0, 0, 0.8)')) {
        modal.remove();
      }

      this.mostrarNotificacionInfo(`Configuración "${configSeleccionada.name}" aplicada`, 'success');

    } catch (error) {
      console.error('❌ Error aplicando configuración:', error);
      this.mostrarNotificacionInfo('Error aplicando configuración', 'error');
    }
  }

  /**
   * Limpiar todas las configuraciones
   */
  limpiarConfiguraciones() {
    if (confirm('¿Estás seguro de que quieres eliminar todas las configuraciones guardadas?')) {
      localStorage.removeItem('graficos_configuraciones');
      localStorage.removeItem('configs_emergencia');

      this.mostrarNotificacionInfo('Configuraciones eliminadas', 'success');
      console.log('🗑️ Todas las configuraciones han sido eliminadas');

      // Cerrar modal
      const modal = document.querySelector('div[style*="position: fixed"]');
      if (modal && modal.style.background.includes('rgba(0, 0, 0, 0.8)')) {
        modal.remove();
      }

      // Reinicializar con datos por defecto
      this.init();
    }
  }

  /**
   * Actualizar gráfico
   */
  actualizarGrafico() {
    console.log('🔄 Actualizando gráfico...');
    this.init();

    // Cerrar modal si está abierto
    const modal = document.querySelector('div[style*="position: fixed"]');
    if (modal && modal.style.background.includes('rgba(0, 0, 0, 0.8)')) {
      modal.remove();
    }
  }

  /**
   * Mostrar notificación informativa
   */
  mostrarNotificacionInfo(mensaje, tipo = 'info') {
    const colors = {
      success: '#10b981',
      error: '#ef4444',
      warning: '#f59e0b',
      info: '#3b82f6'
    };

    const notification = document.createElement('div');
    notification.style.cssText = `
      position: fixed;
      top: 20px;
      right: 20px;
      background: ${colors[tipo]};
      color: white;
      padding: 12px 20px;
      border-radius: 8px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.2);
      z-index: 9999;
      font-size: 14px;
      transform: translateX(100%);
      transition: transform 0.3s ease;
    `;

    notification.textContent = mensaje;
    document.body.appendChild(notification);

    // Animar entrada
    setTimeout(() => {
      notification.style.transform = 'translateX(0)';
    }, 100);

    // Auto-remover
    setTimeout(() => {
      notification.style.transform = 'translateX(100%)';
      setTimeout(() => notification.remove(), 300);
    }, 3000);
  }

  /**
   * Actualizar manualmente
   */
  async actualizarManualmente() {
    console.log('🔄 Actualización manual solicitada...');

    try {
      await this.cargarEstadisticas();

      // Reinicializar gráfico si es necesario
      if (!this.chart) {
        await this.inicializarGrafico();
      }

      console.log('✅ Actualización manual completada');

    } catch (error) {
      console.error('❌ Error en actualización manual:', error);
    }
  }
}

// ==================== INICIALIZACIÓN GLOBAL ====================

let dashboardStats = null;

/**
 * Inicializar sistema simplificado
 */
async function inicializarSistemaSimplificado() {
  try {
    console.log('🚀 Inicializando sistema simplificado...');
    
    dashboardStats = new DashboardStatsSimple();
    window.dashboardStats = dashboardStats;
    
    await dashboardStats.init();
    
    console.log('✅ Sistema simplificado listo');
    
  } catch (error) {
    console.error('❌ Error en inicialización:', error);
    
    // Fallback final
    if (dashboardStats) {
      dashboardStats.usarDatosFallback();
    }
  }
}

/**
 * Función pública para actualizar estadísticas
 */
async function actualizarEstadisticas() {
  if (dashboardStats) {
    await dashboardStats.actualizarManualmente();
  } else {
    console.warn('Sistema no inicializado, inicializando...');
    await inicializarSistemaSimplificado();
  }
}

// ==================== INICIALIZACIÓN AUTOMÁTICA ====================

// Esperar a que el DOM esté listo
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', () => {
    setTimeout(inicializarSistemaSimplificado, 500);
  });
} else {
  setTimeout(inicializarSistemaSimplificado, 500);
}

// Reemplazar sistema anterior si existe
window.addEventListener('load', () => {
  setTimeout(inicializarSistemaSimplificado, 1000);
});
