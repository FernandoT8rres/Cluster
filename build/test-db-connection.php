<?php
/**
 * TEST: Verificar conexión a base de datos con variables de entorno
 */

require_once __DIR__ . '/config/database.php';

echo "=== TEST DE CONEXIÓN A BASE DE DATOS ===\n\n";

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    echo "✅ Conexión a base de datos exitosa\n\n";
    
    // Información del entorno
    $envInfo = $db->getEnvironmentInfo();
    echo "📊 INFORMACIÓN DEL ENTORNO:\n";
    echo "  Usando MySQL remoto: " . ($envInfo['using_remote'] ? 'SÍ' : 'NO') . "\n";
    echo "  Usando SQLite: " . ($envInfo['using_sqlite'] ? 'SÍ' : 'NO') . "\n";
    echo "  Server name: " . $envInfo['server_name'] . "\n";
    echo "  HTTP host: " . $envInfo['http_host'] . "\n\n";
    
    // Test de consulta simple
    echo "🧪 TEST DE CONSULTA:\n";
    $stmt = $conn->query("SELECT COUNT(*) as total FROM usuarios_perfil");
    $result = $stmt->fetch();
    echo "  Total usuarios en BD: " . $result['total'] . "\n\n";
    
    echo "✅ TODAS LAS PRUEBAS DE BD PASARON\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}
?>
