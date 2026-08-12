<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../auth/login-admin.php');
    exit;
}

function valorDecimal($valor) {
    $valor = trim((string)$valor);
    if ($valor === '') return 0;
    $valor = str_replace(['.', ','], ['', '.'], $valor);
    return is_numeric($valor) ? (float)$valor : 0;
}

function buscarFotoAtual(PDO $pdo, int $id): ?string {
    if ($id <= 0) return null;
    $stmt = $pdo->prepare('SELECT foto FROM catalogo_itens WHERE id=?');
    $stmt->execute([$id]);
    $foto = $stmt->fetchColumn();
    return $foto ? basename((string)$foto) : null;
}

function removerArquivoFoto(?string $foto): void {
    if (!$foto) return;
    $caminho = __DIR__ . '/../uploads/catalogo/' . basename($foto);
    if (is_file($caminho)) {
        @unlink($caminho);
    }
}

function salvarUploadFoto(string $campo = 'foto'): ?string {
    if (!isset($_FILES[$campo]) || !is_array($_FILES[$campo])) {
        return null;
    }

    $arquivo = $_FILES[$campo];
    if (($arquivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if (($arquivo['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new Exception('Erro ao enviar a foto do item.');
    }

    if (($arquivo['size'] ?? 0) > 4 * 1024 * 1024) {
        throw new Exception('A foto deve ter no máximo 4MB.');
    }

    $tmp = (string)($arquivo['tmp_name'] ?? '');
    if (!is_uploaded_file($tmp)) {
        throw new Exception('Upload de foto inválido.');
    }

    $mime = function_exists('mime_content_type') ? mime_content_type($tmp) : '';
    $permitidos = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif'
    ];

    if (!isset($permitidos[$mime])) {
        throw new Exception('Envie uma foto em JPG, PNG, WEBP ou GIF.');
    }

    $dir = __DIR__ . '/../uploads/catalogo';
    if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
        throw new Exception('Não foi possível criar a pasta uploads/catalogo.');
    }

    $nome = 'item_' . date('YmdHis') . '_' . bin2hex(random_bytes(5)) . '.' . $permitidos[$mime];
    $destino = $dir . '/' . $nome;

    if (!move_uploaded_file($tmp, $destino)) {
        throw new Exception('Não foi possível salvar a foto do item.');
    }

    return $nome;
}

try {
    $id = (int)($_POST['id'] ?? 0);
    $tipo = $_POST['tipo'] ?? 'servico';
    $tipo = in_array($tipo, ['produto', 'servico'], true) ? $tipo : 'servico';
    $nome = trim((string)($_POST['nome'] ?? ''));
    $descricao = trim((string)($_POST['descricao'] ?? ''));
    $preco = valorDecimal($_POST['preco'] ?? '0');
    $unidade = trim((string)($_POST['unidade'] ?? 'un')) ?: 'un';
    $estoque = trim((string)($_POST['estoque_atual'] ?? ''));
    $estoque = $estoque === '' ? null : valorDecimal($estoque);
    $ativo = isset($_POST['ativo']) ? 1 : 0;

    if ($nome === '') {
        throw new Exception('Informe o nome do item.');
    }

    $fotoAtual = buscarFotoAtual($pdo, $id);
    $novaFoto = salvarUploadFoto('foto');
    $removerFoto = isset($_POST['remover_foto']);

    if ($novaFoto) {
        removerArquivoFoto($fotoAtual);
        $fotoFinal = $novaFoto;
    } elseif ($removerFoto) {
        removerArquivoFoto($fotoAtual);
        $fotoFinal = null;
    } else {
        $fotoFinal = $fotoAtual;
    }

    if ($id > 0) {
        $stmt = $pdo->prepare('UPDATE catalogo_itens SET tipo=?, nome=?, descricao=?, preco=?, unidade=?, estoque_atual=?, foto=?, ativo=?, atualizado_em=NOW() WHERE id=?');
        $stmt->execute([$tipo, $nome, $descricao, $preco, $unidade, $estoque, $fotoFinal, $ativo, $id]);
        $_SESSION['success_msg'] = 'Item atualizado com sucesso.';
    } else {
        $stmt = $pdo->prepare('INSERT INTO catalogo_itens (tipo, nome, descricao, preco, unidade, estoque_atual, foto, ativo) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$tipo, $nome, $descricao, $preco, $unidade, $estoque, $fotoFinal, $ativo]);
        $_SESSION['success_msg'] = 'Item cadastrado com sucesso.';
    }
} catch (Throwable $e) {
    error_log('Erro no catálogo: ' . $e->getMessage());
    $_SESSION['error_msg'] = $e->getMessage();
}

header('Location: ../views/admin/catalogo.php');
exit;
