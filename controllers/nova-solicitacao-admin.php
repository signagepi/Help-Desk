<?php
session_start();
require '../config/db.php';
require_once __DIR__ . '/telegram-alertas-funcoes.php';
require_once __DIR__ . '/solicitacao-funcoes.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../auth/login-admin.php');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../views/admin/nova-os.php');
    exit;
}

try {
    $clienteId = (int)($_POST['cliente_id'] ?? 0);
    $stmt = $pdo->prepare('SELECT id FROM clientes WHERE id = ?');
    $stmt->execute([$clienteId]);
    if (!$stmt->fetch()) {
        throw new InvalidArgumentException('Selecione um cliente válido.');
    }
    $origens = ['Central de Serviços', 'Telefone', 'WhatsApp', 'Atendimento presencial', 'Técnico'];
    $origem = textoSolicitacao($_POST['origem'] ?? 'Central de Serviços');
    if (!in_array($origem, $origens, true)) {
        throw new InvalidArgumentException('Selecione uma origem válida.');
    }
    $tecnicoId = !empty($_POST['tecnico_id']) ? (int)$_POST['tecnico_id'] : null;
    if ($tecnicoId) {
        $stmt = $pdo->prepare('SELECT id FROM administradores WHERE id = ?');
        $stmt->execute([$tecnicoId]);
        if (!$stmt->fetch()) {
            throw new InvalidArgumentException('Selecione um técnico válido.');
        }
    }

    $resultado = criarSolicitacao($pdo, [
        'cliente_id' => $clienteId,
        'solicitante_nome' => $_POST['solicitante_nome'] ?? '',
        'registrado_por_tipo' => 'admin',
        'registrado_por_id' => (int)$_SESSION['admin_id'],
        'origem' => $origem,
        'titulo' => $_POST['titulo'] ?? '',
        'descricao' => $_POST['descricao'] ?? '',
        'categoria' => $_POST['categoria'] ?? 'Suporte técnico',
        'prioridade' => $_POST['prioridade'] ?? 'media',
        'equipamento' => $_POST['equipamento'] ?? '',
        'local_atendimento' => $_POST['local_atendimento'] ?? '',
        'tecnico_id' => $tecnicoId,
    ]);

    try {
        enviarAlertaNovoChamado($pdo, $resultado['id']);
    } catch (Throwable $alertaErro) {
        error_log('Erro ao enviar alerta Telegram: ' . $alertaErro->getMessage());
    }
    $_SESSION['success_msg'] = 'O.S. registrada com sucesso! Protocolo: ' . $resultado['protocolo'];
    header('Location: ../views/admin/dashboard.php');
} catch (Throwable $e) {
    error_log('Erro ao registrar O.S. pela Central: ' . $e->getMessage());
    $_SESSION['error_msg'] = $e instanceof InvalidArgumentException ? $e->getMessage() : 'Não foi possível registrar a O.S.';
    $_SESSION['nova_os_dados'] = $_POST;
    header('Location: ../views/admin/nova-os.php');
}
exit;
