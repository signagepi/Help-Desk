<?php
session_start();
require '../config/db.php';
require_once __DIR__ . '/telegram-alertas-funcoes.php';
require_once __DIR__ . '/solicitacao-funcoes.php';

if (!isset($_SESSION['cliente_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../views/cliente/solicitar.php');
    exit;
}

$cliente_id = (int)$_SESSION['cliente_id'];
$titulo = textoSolicitacao($_POST['titulo'] ?? '');
$descricao = textoSolicitacao($_POST['descricao'] ?? '');

if ($descricao === '') {
    $_SESSION['error_msg'] = 'Descreva o problema ou serviço solicitado.';
    header('Location: ../views/cliente/solicitar.php');
    exit;
}

try {
    $resultado = criarSolicitacao($pdo, [
        'cliente_id' => $cliente_id,
        'registrado_por_tipo' => 'cliente',
        'registrado_por_id' => $cliente_id,
        'origem' => 'Portal do cliente',
        'titulo' => $titulo,
        'descricao' => $descricao,
        'categoria' => $_POST['categoria'] ?? 'Suporte técnico',
        'prioridade' => $_POST['prioridade'] ?? 'media',
        'equipamento' => $_POST['equipamento'] ?? '',
        'local_atendimento' => $_POST['local_atendimento'] ?? '',
    ]);

    try {
        enviarAlertaNovoChamado($pdo, $resultado['id']);
    } catch (Throwable $alertaErro) {
        error_log('Erro ao enviar alerta Telegram: ' . $alertaErro->getMessage());
    }

    $_SESSION['success_msg'] = 'Solicitação aberta com sucesso! Protocolo: ' . $resultado['protocolo'];
} catch (Throwable $e) {
    error_log('Erro ao criar solicitação: ' . $e->getMessage());
    $_SESSION['error_msg'] = 'Não foi possível abrir a solicitação. Confirme se executou o SQL de modernização.';
    header('Location: ../views/cliente/solicitar.php');
    exit;
}

header('Location: ../views/cliente/dashboard.php');
exit;
