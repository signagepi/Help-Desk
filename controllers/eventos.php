<?php
session_start();
header('Content-Type: application/json');

require '../config/db.php';

if (!isset($_SESSION['cliente_id'])) {
    echo json_encode([]);
    exit;
}

$cliente_id = $_SESSION['cliente_id'];

try {
    $stmt = $pdo->prepare("
        SELECT id, titulo, data_inicio, data_fim
        FROM eventos
        WHERE cliente_id = ?
    ");
    $stmt->execute([$cliente_id]);
    $eventos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $resultado = [];

    foreach ($eventos as $e) {
        $resultado[] = [
            'id' => $e['id'],
            'title' => $e['titulo'],
            'start' => $e['data_inicio'],
            'end' => $e['data_fim']
        ];
    }

    echo json_encode($resultado);
} catch (PDOException $e) {
    echo json_encode([]);
}
