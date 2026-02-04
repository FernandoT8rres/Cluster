<?php
/**
 * TEST: Verificar carga de variables de entorno
 */

require_once __DIR__ . '/config/env-loader.php';

echo "=== TEST DE VARIABLES DE ENTORNO ===\n\n";

try {
    EnvLoader::load();
    echo "✅ .env cargado correctamente\n\n";
    
    // Verificar variables de base de datos
    echo "📊 CONFIGURACIÓN DE BASE DE DATOS:\n";
    echo "  DB_HOST: " . EnvLoader::get('DB_HOST', 'NO DEFINIDO') . "\n";
    echo "  DB_PORT: " . EnvLoader::get('DB_PORT', 'NO DEFINIDO') . "\n";
    echo "  DB_NAME: " . EnvLoader::get('DB_NAME', 'NO DEFINIDO') . "\n";
    echo "  DB_USER: " . EnvLoader::get('DB_USER', 'NO DEFINIDO') . "\n";
    echo "  DB_PASS: " . (EnvLoader::has('DB_PASS') ? '****** (SET)' : 'NO DEFINIDO') . "\n\n";
    
    // Verificar variables de seguridad
    echo "🔒 CONFIGURACIÓN DE SEGURIDAD:\n";
    echo "  JWT_SECRET: " . (EnvLoader::has('JWT_SECRET') ? '****** (SET)' : 'NO DEFINIDO') . "\n";
    echo "  SESSION_SECRET: " . (EnvLoader::has('SESSION_SECRET') ? '****** (SET)' : 'NO DEFINIDO') . "\n\n";
    
    // Verificar variables de aplicación
    echo "⚙️ CONFIGURACIÓN DE APLICACIÓN:\n";
    echo "  APP_ENV: " . EnvLoader::get('APP_ENV', 'NO DEFINIDO') . "\n";
    echo "  APP_DEBUG: " . EnvLoader::get('APP_DEBUG', 'NO DEFINIDO') . "\n";
    echo "  APP_URL: " . EnvLoader::get('APP_URL', 'NO DEFINIDO') . "\n\n";
    
    // Verificar variables de rate limiting
    echo "🚦 CONFIGURACIÓN DE RATE LIMITING:\n";
    echo "  RATE_LIMIT_ENABLED: " . EnvLoader::get('RATE_LIMIT_ENABLED', 'NO DEFINIDO') . "\n";
    echo "  RATE_LIMIT_STORAGE: " . EnvLoader::get('RATE_LIMIT_STORAGE', 'NO DEFINIDO') . "\n\n";
    
    // Test de variable requerida
    echo "🧪 TEST DE VARIABLE REQUERIDA:\n";
    try {
        $dbUser = EnvLoader::required('DB_USER');
        echo "  ✅ DB_USER requerido: $dbUser\n";
    } catch (Exception $e) {
        echo "  ❌ Error: " . $e->getMessage() . "\n";
    }
    
    echo "\n✅ TODAS LAS PRUEBAS PASARON\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
?>
