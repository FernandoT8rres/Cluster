/**
 * Gestión de Estadísticas Dinámicas para Dashboard
 */

console.log('📊 Estadísticas Dinámicas: Script cargado');

let estadisticasConfig = [];
let updateInterval = null;

// Inicializar estadísticas dinámicas
document.addEventListener('DOMContentLoaded', () => {
    console.log('📊 Inicializando sistema de estadísticas dinámicas...');
    
    // Esperar un poco para asegurarse de que el DOM esté completamente listo
    setTimeout(() => {
        console.log('📊 DOM listo, iniciando estadísticas dinámicas...');
        inicializarEstadisticasDinamicas();
        
        // Actualizar cada 5 minutos
        updateInterval = setInterval(actualizarEstadisticasDinamicas, 300000);
    }, 1000);
});

// Función principal de inicialización
async function inicializarEstadisticasDinamicas() {
    let intentos = 0;
    const maxIntentos = 3;
    
    while (intentos < maxIntentos) {
        try {
            intentos++;
            console.log(`📊 Intento ${intentos}/${maxIntentos} - Cargando configuración de estadísticas...`);
            
            await cargarConfiguracionEstadisticas();
            
            console.log('📊 Aplicando estadísticas dinámicas al dashboard...');
            await aplicarEstadisticasDinamicas();
            
            console.log('✅ Sistema de estadísticas dinámicas iniciado correctamente');
            return; // Éxito, salir del bucle
            
        } catch (error) {
            console.error(`❌ Error en intento ${intentos}:`, error);
            
            if (intentos < maxIntentos) {
                const esperaMs = intentos * 2000; // Espera incremental: 2s, 4s, 6s
                console.log(`⏳ Esperando ${esperaMs}ms antes del siguiente intento...`);
                await new Promise(resolve => setTimeout(resolve, esperaMs));
            } else {
                console.error('❌ Todos los intentos fallaron. Usando estadísticas estáticas como fallback');
                mostrarEstadisticasFallback();
            }
        }
    }
}

// Función para mostrar estadísticas de fallback
function mostrarEstadisticasFallback() {
    console.log('📊 Aplicando estadísticas de fallback...');
    
    const fallbackStats = [
        { nombre: 'comites', valor: 'N/A', error: null },
        { nombre: 'empresas', valor: 'N/A', error: null },
        { nombre: 'descuentos', valor: 'N/A', error: null },
        { nombre: 'eventos', valor: 'N/A', error: null }
    ];
    
    fallbackStats.forEach((stat, index) => {
        const elemento = document.getElementById(`stats${stat.nombre.charAt(0).toUpperCase() + stat.nombre.slice(1)}`);
        if (elemento) {
            elemento.textContent = stat.valor;
            console.log(`🔄 Fallback aplicado para: ${stat.nombre}`);
        }
    });
}

// Cargar configuración desde la API
async function cargarConfiguracionEstadisticas() {
    try {
        const response = await fetch('./api/estadisticas-config.php?activo=1');
        const data = await response.json();
        
        if (data.success && data.data && data.data.length > 0) {
            estadisticasConfig = data.data.sort((a, b) => a.posicion - b.posicion);
            console.log(`📊 ${estadisticasConfig.length} configuraciones de estadísticas cargadas`);
            return true;
        } else {
            console.warn('⚠️ No se encontraron configuraciones de estadísticas activas');
            return false;
        }
    } catch (error) {
        console.error('❌ Error cargando configuración de estadísticas:', error);
        throw error;
    }
}

// Aplicar estadísticas dinámicas al dashboard
async function aplicarEstadisticasDinamicas() {
    try {
        console.log('📊 Obteniendo valores de estadísticas...');
        
        // Obtener valores reales de las estadísticas
        const response = await fetch('./api/estadisticas-config.php?accion=valores');
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const data = await response.json();
        
        console.log('📊 Respuesta de API recibida:', data);
        
        if (!data.success) {
            throw new Error(data.message || 'Error obteniendo valores');
        }

        const estadisticas = data.valores || [];
        console.log(`📊 Aplicando ${estadisticas.length} estadísticas dinámicas:`, estadisticas);
        
        // Debug: mostrar cada estadística individualmente
        estadisticas.forEach((stat, index) => {
            console.log(`📊 Estadística ${index + 1}:`, {
                nombre: stat.nombre,
                titulo: stat.titulo, 
                valor: stat.valor,
                error: stat.error,
                hasError: !!stat.error
            });
        });

        // Verificar si alguna estadística tiene errores
        const conErrores = estadisticas.filter(stat => stat.error);
        if (conErrores.length > 0) {
            console.warn(`⚠️ ${conErrores.length} estadísticas con errores:`, conErrores);
        }

        // Renderizar todas las estadísticas dinámicamente
        renderizarEstadisticasCompletas(estadisticas);

        console.log('✅ Todas las estadísticas aplicadas correctamente');

    } catch (error) {
        console.error('❌ Error aplicando estadísticas dinámicas:', error);
        console.error('❌ Detalles del error:', error.stack);
        mostrarErrorEstadisticas(error.message);
        throw error;
    }
}

// Renderizar estadísticas completas dinámicamente
function renderizarEstadisticasCompletas(estadisticas) {
    const container = document.getElementById('estadisticasContainer');
    if (!container) {
        console.error('❌ No se encontró el contenedor de estadísticas');
        return;
    }

    // Limpiar contenedor
    container.innerHTML = '';

    if (!estadisticas || estadisticas.length === 0) {
        container.innerHTML = `
            <div class="w-full text-center py-8">
                <div class="text-gray-500">
                    <i class="fas fa-chart-bar text-4xl mb-4"></i>
                    <p>No hay estadísticas configuradas</p>
                </div>
            </div>
        `;
        return;
    }

    // Renderizar cada estadística
    estadisticas.forEach((stat, index) => {
        const tarjeta = crearTarjetaEstadistica(stat, index);
        container.appendChild(tarjeta);
    });
}

// Crear una tarjeta de estadística individual
function crearTarjetaEstadistica(estadistica, index) {
    const div = document.createElement('div');
    div.className = 'w-full max-w-full px-3 mb-6 sm:w-1/2 sm:flex-none xl:mb-0 xl:w-1/4';
    
    // Determinar colores de gradiente
    const gradientes = getGradienteColor(estadistica.color);
    
    // Determinar si hay error
    let valorMostrado = '';
    let claseError = '';
    let indicadorError = '';
    
    if (estadistica.error) {
        claseError = 'border-l-4 border-red-400';
        if (estadistica.valor !== null && estadistica.valor !== undefined) {
            valorMostrado = formatearValor(estadistica.valor, estadistica.formato);
            indicadorError = '<i class="fas fa-exclamation-triangle text-yellow-500 ml-2" title="' + estadistica.error + '"></i>';
        } else {
            valorMostrado = '<span class="text-red-500">Error</span>';
            indicadorError = '<i class="fas fa-times-circle text-red-500 ml-2" title="' + estadistica.error + '"></i>';
        }
    } else {
        valorMostrado = formatearValor(estadistica.valor, estadistica.formato);
    }
    
    div.innerHTML = `
        <div class="relative flex flex-col min-w-0 break-words bg-white shadow-xl dark:bg-slate-850 dark:shadow-dark-xl rounded-2xl bg-clip-border ${claseError}">
            <div class="flex-auto p-4">
                <div class="flex flex-row -mx-3">
                    <div class="flex-none w-2/3 max-w-full px-3">
                        <div>
                            <p class="mb-0 font-sans text-sm font-semibold leading-normal uppercase dark:text-white dark:opacity-60">
                                ${estadistica.titulo}
                            </p>
                            <h5 class="mb-2 font-bold dark:text-white" id="stats${estadistica.nombre.charAt(0).toUpperCase() + estadistica.nombre.slice(1)}" data-stat="${estadistica.nombre}">
                                ${valorMostrado}${indicadorError}
                            </h5>
                            <p class="mb-0 dark:text-white dark:opacity-60">
                                <span class="text-sm font-bold leading-normal ${estadistica.crecimiento >= 0 ? 'text-emerald-500' : 'text-red-500'}" id="${estadistica.nombre}Growth">
                                    ${estadistica.error ? 'N/A' : (estadistica.crecimiento > 0 ? '+' : '') + (estadistica.crecimiento || 0)}
                                </span>
                                ${estadistica.error ? 'Error en datos' : estadistica.crecimiento_texto || 'Valor actual'}
                            </p>
                        </div>
                    </div>
                    <div class="px-3 text-right basis-1/3">
                        <div class="inline-block w-12 h-12 text-center rounded-circle bg-gradient-to-tl ${gradientes.from} ${gradientes.to}">
                            <i class="${estadistica.icono} text-lg relative top-3.5 text-white"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    return div;
}

// Obtener colores de gradiente según el color configurado
function getGradienteColor(color) {
    const gradientes = {
        'blue': { from: 'from-blue-500', to: 'to-violet-500' },
        'green': { from: 'from-emerald-500', to: 'to-teal-400' },
        'red': { from: 'from-red-600', to: 'to-orange-600' },
        'orange': { from: 'from-orange-500', to: 'to-yellow-500' },
        'purple': { from: 'from-purple-700', to: 'to-pink-500' }
    };
    
    return gradientes[color] || gradientes['blue'];
}

// Mostrar error cuando no se pueden cargar estadísticas
function mostrarErrorEstadisticas(mensaje) {
    const container = document.getElementById('estadisticasContainer');
    if (container) {
        container.innerHTML = `
            <div class="w-full text-center py-8">
                <div class="text-red-500">
                    <i class="fas fa-exclamation-triangle text-4xl mb-4"></i>
                    <p class="text-lg font-semibold">Error al cargar estadísticas</p>
                    <p class="text-sm">${mensaje}</p>
                    <button onclick="window.actualizarEstadisticas()" class="mt-4 bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">
                        Reintentar
                    </button>
                </div>
            </div>
        `;
    }
}

// Actualizar una tarjeta de estadística específica
function actualizarTarjetaEstadistica(estadistica, index) {
    const elementos = {
        'comites': {
            stat: document.getElementById('statsComites'),
            growth: document.getElementById('comitesGrowth'),
            container: document.querySelector('[data-stat="comites"]')?.closest('.relative')
        },
        'empresas': {
            stat: document.getElementById('statsEmpresas'),
            growth: document.getElementById('empresasGrowth'),
            container: document.querySelector('[data-stat="empresas"]')?.closest('.relative')
        },
        'descuentos': {
            stat: document.getElementById('statsDescuentos'),
            growth: document.getElementById('descuentosGrowth'),
            container: document.querySelector('[data-stat="descuentos"]')?.closest('.relative')
        },
        'eventos': {
            stat: document.getElementById('statsEventos'),
            growth: document.getElementById('eventosGrowth'),
            container: document.querySelector('[data-stat="eventos"]')?.closest('.relative')
        }
    };

    const elemento = elementos[estadistica.nombre];
    
    if (elemento && elemento.stat) {
        // Si hay error, mostrar el valor pero con advertencia en consola
        if (estadistica.error) {
            console.error(`❌ Error en estadística '${estadistica.nombre}': ${estadistica.error}`);
            console.log(`🔧 Intentando usar valor: ${estadistica.valor}`);
            
            // Aún mostrar el valor si existe, pero con indicador visual de error
            if (estadistica.valor !== undefined && estadistica.valor !== null) {
                elemento.stat.innerHTML = `
                    <span>${formatearValor(estadistica.valor, estadistica.formato)}</span>
                    <i class="fas fa-exclamation-triangle text-red-500 text-xs ml-1" title="${estadistica.error}"></i>
                `;
            } else {
                elemento.stat.innerHTML = `
                    <span class="text-red-500">Error</span>
                    <i class="fas fa-exclamation-triangle text-red-500 text-xs ml-1" title="${estadistica.error}"></i>
                `;
            }
        } else {
            // No hay error, mostrar normalmente
            elemento.stat.textContent = formatearValor(estadistica.valor, estadistica.formato);
        }
        
        elemento.stat.setAttribute('data-stat', estadistica.nombre);
        
        // Actualizar indicador de crecimiento solo si no hay error
        if (elemento.growth && !estadistica.error) {
            const crecimientoTexto = estadistica.crecimiento > 0 ? 
                `+${estadistica.crecimiento}` : 
                estadistica.crecimiento.toString();
            
            elemento.growth.textContent = crecimientoTexto;
            elemento.growth.className = `text-sm font-bold leading-normal ${
                estadistica.crecimiento >= 0 ? 'text-emerald-500' : 'text-red-500'
            }`;
        } else if (elemento.growth && estadistica.error) {
            elemento.growth.textContent = 'Error en datos';
            elemento.growth.className = 'text-sm font-bold leading-normal text-red-500';
        }

        // Actualizar icono si existe contenedor
        if (elemento.container) {
            const iconoElement = elemento.container.querySelector('.ni, .fas, .far, .fab');
            if (iconoElement && estadistica.icono) {
                iconoElement.className = estadistica.icono + ' relative top-0 text-sm leading-normal text-white';
            }

            // Actualizar color de fondo
            const bgElement = elemento.container.querySelector('.bg-gradient-to-tl');
            if (bgElement && estadistica.color) {
                actualizarColorFondo(bgElement, estadistica.color);
            }
        }

        console.log(`✅ Estadística '${estadistica.nombre}' actualizada: ${estadistica.valor} ${estadistica.error ? '(con error)' : ''}`);
        
    } else {
        console.warn(`⚠️ No se encontró elemento DOM para estadística: ${estadistica.nombre}`);
    }
}

// Formatear valor según el tipo
function formatearValor(valor, formato) {
    const num = Number(valor) || 0;
    
    switch (formato) {
        case 'percentage':
            return num.toFixed(1) + '%';
        case 'currency':
            return '$' + num.toLocaleString('es-ES', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            });
        case 'text':
            return valor.toString();
        case 'number':
        default:
            return num.toLocaleString('es-ES');
    }
}

// Actualizar color de fondo según configuración
function actualizarColorFondo(elemento, color) {
    // Remover clases de color existentes
    elemento.classList.remove(
        'from-purple-700', 'to-pink-500',
        'from-blue-600', 'to-violet-600', 
        'from-orange-500', 'to-yellow-500',
        'from-green-600', 'to-lime-400',
        'from-red-600', 'to-rose-400'
    );
    
    // Aplicar nuevas clases según el color
    const colorClasses = {
        'blue': ['from-blue-600', 'to-violet-600'],
        'green': ['from-green-600', 'to-lime-400'],
        'orange': ['from-orange-500', 'to-yellow-500'],
        'purple': ['from-purple-700', 'to-pink-500'],
        'red': ['from-red-600', 'to-rose-400']
    };
    
    const classes = colorClasses[color] || colorClasses['blue'];
    elemento.classList.add(...classes);
}

// Ajustar layout según cantidad de estadísticas
function ajustarLayoutEstadisticas(cantidad) {
    const container = document.querySelector('.grid.mb-12');
    if (!container) return;

    // Remover clases de grid existentes
    container.classList.remove(
        'grid-cols-1', 'grid-cols-2', 'grid-cols-3', 'grid-cols-4',
        'xl:grid-cols-1', 'xl:grid-cols-2', 'xl:grid-cols-3', 'xl:grid-cols-4'
    );
    
    // Aplicar clases según cantidad
    if (cantidad <= 2) {
        container.classList.add('grid-cols-1', 'xl:grid-cols-2');
    } else if (cantidad <= 3) {
        container.classList.add('grid-cols-1', 'xl:grid-cols-3');
    } else {
        container.classList.add('grid-cols-1', 'xl:grid-cols-4');
    }
    
    console.log(`📊 Layout ajustado para ${cantidad} estadísticas`);
}

// Función para actualizar estadísticas (llamada por botón o automáticamente)
async function actualizarEstadisticasDinamicas() {
    console.log('🔄 Actualizando estadísticas dinámicas...');
    
    try {
        await aplicarEstadisticasDinamicas();
        console.log('✅ Estadísticas actualizadas correctamente');
        
        // Mostrar notificación visual opcional
        mostrarNotificacionActualizacion();
        
    } catch (error) {
        console.error('❌ Error actualizando estadísticas:', error);
    }
}

// Mostrar notificación de actualización (opcional)
function mostrarNotificacionActualizacion() {
    const notification = document.createElement('div');
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: linear-gradient(135deg, #27AE60, #2ECC71);
        color: white;
        padding: 0.75rem 1rem;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(39, 174, 96, 0.3);
        z-index: 1000;
        font-size: 0.875rem;
        font-weight: 600;
        opacity: 0;
        transform: translateX(100%);
        transition: all 0.3s ease;
    `;
    
    notification.innerHTML = `
        <i class="fas fa-check-circle mr-2"></i>
        Estadísticas actualizadas
    `;
    
    document.body.appendChild(notification);
    
    // Mostrar notificación
    setTimeout(() => {
        notification.style.opacity = '1';
        notification.style.transform = 'translateX(0)';
    }, 100);
    
    // Ocultar después de 3 segundos
    setTimeout(() => {
        notification.style.opacity = '0';
        notification.style.transform = 'translateX(100%)';
        
        setTimeout(() => {
            document.body.removeChild(notification);
        }, 300);
    }, 3000);
}

// Función global para actualizar desde botón del dashboard
window.actualizarEstadisticas = function() {
    console.log('🔄 Actualización manual de estadísticas solicitada');
    actualizarEstadisticasDinamicas();
};

// Función global para recargar configuración
window.recargarConfiguracionEstadisticas = async function() {
    console.log('🔄 Recargando configuración de estadísticas...');

    try {
        await cargarConfiguracionEstadisticas();
        await aplicarEstadisticasDinamicas();
        console.log('✅ Configuración recargada exitosamente');

        mostrarNotificacionActualizacion();
    } catch (error) {
        console.error('❌ Error recargando configuración:', error);
    }
};

// Función para eliminar una estadística específica del dashboard
window.eliminarEstadistica = async function(nombreEstadistica) {
    console.log('🗑️ Eliminando estadística:', nombreEstadistica);

    // Confirmar eliminación
    if (!confirm(`¿Estás seguro de que quieres eliminar la estadística "${nombreEstadistica}" del dashboard?`)) {
        return;
    }

    try {
        // Buscar el ID de la estadística
        const estadistica = estadisticasConfig.find(stat => stat.nombre === nombreEstadistica);
        if (!estadistica) {
            console.error('❌ No se encontró la estadística:', nombreEstadistica);
            alert('Error: No se encontró la estadística especificada');
            return;
        }

        console.log('🗑️ Desactivando estadística con ID:', estadistica.id);

        // Hacer petición para desactivar la estadística (PUT con activo = 0)
        const formData = new URLSearchParams();
        formData.append('nombre', estadistica.nombre);
        formData.append('titulo', estadistica.titulo);
        formData.append('icono', estadistica.icono);
        formData.append('color', estadistica.color);
        formData.append('query_sql', estadistica.query_sql);
        formData.append('formato', estadistica.formato);
        formData.append('posicion', estadistica.posicion);
        formData.append('activo', '0'); // Desactivar
        formData.append('descripcion', estadistica.descripcion || '');
        formData.append('crecimiento_query', estadistica.crecimiento_query || '');
        formData.append('crecimiento_texto', estadistica.crecimiento_texto || '');

        const response = await fetch(`./api/estadisticas-config.php?id=${estadistica.id}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            console.log('✅ Estadística eliminada del dashboard exitosamente');

            // Mostrar notificación de éxito
            mostrarNotificacionEliminacion(estadistica.titulo);

            // Recargar estadísticas para actualizar el dashboard
            await recargarConfiguracionEstadisticas();
        } else {
            throw new Error(result.message || 'Error eliminando estadística');
        }

    } catch (error) {
        console.error('❌ Error eliminando estadística:', error);
        alert('Error al eliminar la estadística: ' + error.message);
    }
};

// Función para mostrar notificación de eliminación
function mostrarNotificacionEliminacion(titulo) {
    const notification = document.createElement('div');
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: linear-gradient(135deg, #E74C3C, #C0392B);
        color: white;
        padding: 0.75rem 1rem;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(231, 76, 60, 0.3);
        z-index: 1000;
        font-size: 0.875rem;
        font-weight: 600;
        opacity: 0;
        transform: translateX(100%);
        transition: all 0.3s ease;
        max-width: 300px;
    `;

    notification.innerHTML = `
        <i class="fas fa-trash mr-2"></i>
        "${titulo}" eliminada del dashboard
    `;

    document.body.appendChild(notification);

    // Mostrar notificación
    setTimeout(() => {
        notification.style.opacity = '1';
        notification.style.transform = 'translateX(0)';
    }, 100);

    // Ocultar después de 4 segundos
    setTimeout(() => {
        notification.style.opacity = '0';
        notification.style.transform = 'translateX(100%)';

        setTimeout(() => {
            if (notification && notification.parentNode) {
                document.body.removeChild(notification);
            }
        }, 300);
    }, 4000);
}

// Limpiar interval al salir de la página
window.addEventListener('beforeunload', () => {
    if (updateInterval) {
        clearInterval(updateInterval);
        console.log('🔄 Interval de actualización limpiado');
    }
});

// Funciones para debugging
window.debugEstadisticas = {
    config: () => estadisticasConfig,
    actualizar: actualizarEstadisticasDinamicas,
    recargar: window.recargarConfiguracionEstadisticas,
    test: async () => {
        console.log('🧪 Test de estadísticas dinámicas');
        console.log('Configuración:', estadisticasConfig);
        await actualizarEstadisticasDinamicas();
    }
};

console.log('📊 Estadísticas Dinámicas: Funciones globales registradas');
console.log('💡 Usa window.debugEstadisticas para debugging');