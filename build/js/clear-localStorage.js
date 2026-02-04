/**
 * Script para limpiar completamente localStorage 
 * y forzar que todo el sistema use únicamente la base de datos
 */

(function() {
    console.log('🧹 Limpiando localStorage y migrando a sistema basado en BD...');
    
    // Obtener todas las claves de localStorage antes de limpiar
    const keys = Object.keys(localStorage);
    const clautKeys = keys.filter(key => key.includes('claut'));
    
    if (clautKeys.length > 0) {
        console.log('🔍 Encontrados datos en localStorage:', clautKeys);
        
        // Limpiar todas las claves relacionadas con claut
        clautKeys.forEach(key => {
            console.log(`🗑️ Eliminando ${key} de localStorage`);
            localStorage.removeItem(key);
        });
        
        console.log('✅ localStorage limpiado completamente');
    } else {
        console.log('✅ localStorage ya está limpio');
    }
    
    // Limpiar también sessionStorage por si acaso
    const sessionKeys = Object.keys(sessionStorage);
    const clautSessionKeys = sessionKeys.filter(key => key.includes('claut'));
    
    if (clautSessionKeys.length > 0) {
        console.log('🔍 Encontrados datos en sessionStorage:', clautSessionKeys);
        clautSessionKeys.forEach(key => {
            console.log(`🗑️ Eliminando ${key} de sessionStorage`);
            sessionStorage.removeItem(key);
        });
        console.log('✅ sessionStorage limpiado completamente');
    }
    
    // Informar al usuario
    console.log('🔐 Sistema migrado exitosamente a autenticación basada en base de datos');
    console.log('📊 Todos los datos ahora se obtienen directamente de la BD');
    
    // Si hay funciones de cleanup adicionales, ejecutarlas
    if (window.cleanupOldSystem) {
        window.cleanupOldSystem();
    }
})();

// Función para verificar y reportar el estado del sistema
window.verifyDatabaseOnlySystem = function() {
    const hasLocalStorage = Object.keys(localStorage).some(key => key.includes('claut'));
    const hasSessionStorage = Object.keys(sessionStorage).some(key => key.includes('claut'));
    
    console.log('🔍 Verificación del sistema:');
    console.log('- localStorage limpio:', !hasLocalStorage);
    console.log('- sessionStorage limpio:', !hasSessionStorage);
    console.log('- Sistema basado en BD:', window.authSessionManager ? 'Activo' : 'No detectado');
    
    return !hasLocalStorage && !hasSessionStorage;
};

// Prevenir que otros scripts puedan guardar datos en localStorage
const originalSetItem = localStorage.setItem;
localStorage.setItem = function(key, value) {
    if (key.includes('claut')) {
        console.warn('⚠️ Intento de guardar datos en localStorage bloqueado:', key);
        console.warn('💡 El sistema ahora usa únicamente la base de datos');
        return;
    }
    return originalSetItem.call(this, key, value);
};

console.log('🛡️ Protección anti-localStorage activada para claves "claut"');