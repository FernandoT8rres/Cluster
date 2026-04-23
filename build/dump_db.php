<?php
require_once 'api/config/db.php';
session_start();
header('Content-Type: text/plain');

$db = new Database();
$conn = $db->connect();

$rolUsuario = strtolower($_SESSION['user_rol'] ?? $_SESSION['rol'] ?? $_SESSION['user_role'] ?? '');
$esAdmin = in_array($rolUsuario, ['admin', 'administrador'], true);

echo "Session Role: " . ($rolUsuario ?: 'NONE') . "\n";
echo "Is Admin: " . ($esAdmin ? 'YES' : 'NO') . "\n\n";

$whereClause = $esAdmin ? '' : 'WHERE e.activo = 1 AND (e.autoriza_directorio = 1 OR e.autoriza_directorio IS NULL)';
$sql = "SELECT e.id, COALESCE(e.nombre, e.nombre_empresa) as name, e.activo, e.autoriza_directorio 
        FROM empresas_convenio e 
        $whereClause";

echo "Executing SQL: $sql\n\n";

$stmt = $conn->query($sql);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Total Results: " . count($results) . "\n";
print_r($results);
