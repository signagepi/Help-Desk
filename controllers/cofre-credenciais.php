<?php
declare(strict_types=1);
session_start();
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../lib/cofre-funcoes.php';
cofre_cabecalhos();
cofre_exigir_https();
if (!cofre_admin_id()) { header('Location: ../auth/login-admin.php'); exit; }
$admin = cofre_admin_id();
$destino = '../views/admin/cofre-senhas.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }
try {
    cofre_exigir_csrf();
    cofre_chave(); // Fail closed: nenhum registro sem a chave correta.
    $acao = (string) ($_POST['acao'] ?? '');
    if ($acao === 'salvar') {
        $id = filter_var($_POST['id'] ?? 0, FILTER_VALIDATE_INT) ?: 0;
        $registro = $id > 0 ? cofre_credencial($pdo, $id, $admin) : null;
        // Ações de alteração exigem reautenticação para evitar uso indevido de sessão aberta.
        cofre_reautenticar($pdo, $admin, (string) ($_POST['senha_admin'] ?? ''));
        $dados = cofre_validar_dados($pdo, $_POST, $admin);
        if ($registro && $registro['escopo'] !== $dados['escopo']) {
            throw new RuntimeException('Não é permitido trocar o tipo de cofre de uma credencial existente.');
        }
        $pdo->beginTransaction();
        if ($registro) {
            $criador = (int) $registro['admin_criador_id'];
        } else {
            $criador = $admin;
            $stmt = $pdo->prepare('INSERT INTO cofre_credenciais (admin_criador_id, escopo, cliente_id, titulo, categoria) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$criador, $dados['escopo'], $dados['cliente_id'], $dados['titulo'], $dados['categoria']]);
            $id = (int) $pdo->lastInsertId();
        }
        [$nonce, $cifra] = cofre_cifrar($id, $criador, $dados['escopo'], $dados['cliente_id'], $dados['dados']);
        $stmt = $pdo->prepare('UPDATE cofre_credenciais SET escopo = ?, cliente_id = ?, titulo = ?, categoria = ?, nonce = ?, segredo_cifrado = ? WHERE id = ?');
        $stmt->bindValue(1, $dados['escopo']);
        $stmt->bindValue(2, $dados['cliente_id'], $dados['cliente_id'] === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(3, $dados['titulo']);
        $stmt->bindValue(4, $dados['categoria']);
        $stmt->bindValue(5, $nonce, PDO::PARAM_LOB);
        $stmt->bindValue(6, $cifra, PDO::PARAM_LOB);
        $stmt->bindValue(7, $id, PDO::PARAM_INT);
        $stmt->execute();
        cofre_log($pdo, $admin, $id, $registro ? 'atualizou' : 'cadastrou');
        $pdo->commit();
        $_SESSION['cofre_aviso'] = 'Credencial salva com segurança.';
    } elseif ($acao === 'excluir') {
        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
        if (!$id) { throw new RuntimeException('Identificador inválido.'); }
        cofre_credencial($pdo, $id, $admin);
        cofre_reautenticar($pdo, $admin, (string) ($_POST['senha_admin'] ?? ''));
        $pdo->beginTransaction();
        $pdo->prepare('DELETE FROM cofre_credenciais WHERE id = ?')->execute([$id]);
        cofre_log($pdo, $admin, $id, 'excluiu');
        $pdo->commit();
        $_SESSION['cofre_aviso'] = 'Credencial excluída.';
    } else {
        throw new RuntimeException('Operação inválida.');
    }
} catch (Throwable $e) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    $_SESSION['cofre_erro'] = $e instanceof RuntimeException ? $e->getMessage() : 'Não foi possível realizar a operação. Consulte o administrador.';
}
header('Location: ' . $destino, true, 303);
exit;