<?php
require_once __DIR__ . '/build/config/database.php';

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $stmt = $conn->query("DESCRIBE eventos");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    header('Content-Type: application/json');
    echo json_encode(['exists' => true, 'columns' => $columns]);
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['exists' => false, 'error' => $e->getMessage()]);
}
