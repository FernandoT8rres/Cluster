// ==================== SCRIPT DE DIAGNÓSTICO SIMPLE ====================
// Diagnóstico para demo_gestion_grafico.html

console.log('🔍 Iniciando diagnóstico de demo_gestion_grafico.html...');

// Verificar disponibilidad de elementos clave
function diagnosticarElementos() {
    const elementos = [
        'menuAcciones',
        'cargarDatosReales', 
        'previsualizarGrafico',
        'guardarConfig',
        'dataSource',
        'previewChart',
        'customDataPanel',
        'notificationContainer'
    ];
    
    const problemas = [];
    const encontrados = [];
    
    elementos.forEach(id => {
        const elemento = document.getElementById(id);
        if (elemento) {
            encontrados.push(id);
        } else {
            problemas.push(id);
        }
    });
    
    console.log('✅ Elementos encontrados:', encontrados);
    console.log('❌ Elementos faltantes:', problemas);
    
    return { encontrados, problemas };
}

// Verificar carga de scripts
function diagnosticarScripts() {
    console.log('📋 Verificando scripts...');
    
    // Chart.js
    if (typeof Chart !== 'undefined') {
        console.log('✅ Chart.js cargado correctamente');
    } else {
        console.log('❌ Chart.js NO cargado');
    }
    
    // GestionGraficos
    if (typeof GestionGraficos !== 'undefined') {
        console.log('✅ Clase GestionGraficos disponible');
    } else {
        console.log('❌ Clase GestionGraficos NO disponible');
    }
    
    // Variable global
    if (typeof gestionGraficos !== 'undefined') {
        console.log('✅ Variable global gestionGraficos disponible');
    } else {
        console.log('❌ Variable global gestionGraficos NO disponible');
    }
}

// Función de reparación rápida
function reparacionRapida() {
    console.log('🔧 Aplicando reparación rápida...');
    
    // Crear instancia si no existe
    if (typeof Chart !== 'undefined' && typeof GestionGraficos !== 'undefined') {
        if (typeof gestionGraficos === 'undefined') {
            window.gestionGraficos = new GestionGraficos();
            console.log('✅ Instancia de GestionGraficos creada');
        }
    }
    
    // Agregar event listeners básicos
    const cargarDatos = document.getElementById('cargarDatosReales');
    if (cargarDatos && !cargarDatos.dataset.listenerAdded) {
        cargarDatos.addEventListener('click', () => {
            console.log('🔄 Botón cargar datos clickeado');
            if (window.gestionGraficos) {
                window.gestionGraficos.cargarDatosReales();
            } else {
                console.log('❌ gestionGraficos no disponible');
            }
        });
        cargarDatos.dataset.listenerAdded = 'true';
        console.log('✅ Event listener agregado a cargar datos');
    }
    
    const previsualizar = document.getElementById('previsualizarGrafico');
    if (previsualizar && !previsualizar.dataset.listenerAdded) {
        previsualizar.addEventListener('click', () => {
            console.log('👁️ Botón previsualizar clickeado');
            if (window.gestionGraficos) {
                window.gestionGraficos.previsualizarGrafico();
            }
        });
        previsualizar.dataset.listenerAdded = 'true';
        console.log('✅ Event listener agregado a previsualizar');
    }
    
    const guardar = document.getElementById('guardarConfig');
    if (guardar && !guardar.dataset.listenerAdded) {
        guardar.addEventListener('click', () => {
            console.log('💾 Botón guardar clickeado');
            if (window.gestionGraficos) {
                window.gestionGraficos.guardarConfiguracion();
            }
        });
        guardar.dataset.listenerAdded = 'true';
        console.log('✅ Event listener agregado a guardar');
    }
    
    // Agregar datos de ejemplo si no hay datos
    if (window.gestionGraficos && (!window.gestionGraficos.currentData || window.gestionGraficos.currentData.length === 0)) {
        console.log('📊 Cargando datos de ejemplo...');
        window.gestionGraficos.loadSampleData();
    }
}

// Función de diagnóstico completo
function diagnosticoCompleto() {
    console.log('🚀 Ejecutando diagnóstico completo...');
    
    diagnosticarScripts();
    const elementos = diagnosticarElementos();
    
    console.log('📊 Resumen del diagnóstico:');
    console.log(`- Scripts funcionando: ${typeof Chart !== 'undefined' && typeof GestionGraficos !== 'undefined'}`);
    console.log(`- Elementos encontrados: ${elementos.encontrados.length}/${elementos.encontrados.length + elementos.problemas.length}`);
    console.log(`- Sistema funcional: ${typeof gestionGraficos !== 'undefined'}`);
    
    // Aplicar reparaciones
    reparacionRapida();
    
    return {
        scriptsOK: typeof Chart !== 'undefined' && typeof GestionGraficos !== 'undefined',
        elementosOK: elementos.problemas.length === 0,
        sistemaOK: typeof gestionGraficos !== 'undefined'
    };
}

// Ejecutar cuando el DOM esté listo
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        setTimeout(diagnosticoCompleto, 1000);
    });
} else {
    setTimeout(diagnosticoCompleto, 1000);
}

// Función global para ejecutar desde consola
window.diagnosticoGraficos = diagnosticoCompleto;
window.reparar = reparacionRapida;

console.log('🛠️ Script de diagnóstico cargado. Ejecuta diagnosticoGraficos() para diagnosticar o reparar() para reparar.');
