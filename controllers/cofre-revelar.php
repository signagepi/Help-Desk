<?php
declare(strict_types=1);
session_start();
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../lib/cofre-funcoes.php';
cofre_cabecalhos(true);
cofre_exigir_https();
if (!cofre_admin_id()) { http_response_code(401); echo json_encode(['erro' => 'Sessão expirada.']); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['erro' => 'Método não permitido.']); exit; }
try {
    cofre_exigir_csrf();
    $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
    if (!$id || $id <= 0) { throw new RuntimeException('Credencial inválida.'); }
    $admin = cofre_admin_id();
    $credencial = cofre_credencial($pdo, $id, $admin);
    cofre_reautenticar($pdo, $admin, (string) ($_POST['senha_admin'] ?? ''));
    $dados = cofre_decifrar($credencial);
    cofre_log($pdo, $admin, $id, 'consultou');
    echo json_encode(['ok' => true, 'dados' => $dados, 'escopo' => $credencial['escopo'], 'cliente_id' => $credencial['cliente_id']], JSON_THROW_ON_ERROR);
} catch (Throwable $e) {
    http_response_code($e instanceof RuntimeException && str_contains($e->getMessage(), 'Muitas tentativas') ? 429 : 400);
    echo json_encode(['erro' => $e instanceof RuntimeException ? $e->getMessage() : 'Não foi possível consultar a credencial.'], JSON_UNESCAPED_UNICODE);
}