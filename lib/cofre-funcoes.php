<?php
/** Utilitários do Cofre de Senhas. Nunca registrar payloads ou chaves em logs. */

declare(strict_types=1);

function cofre_cabecalhos(bool $json = false): void
{
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: no-referrer');
    header('X-Frame-Options: DENY');
    if ($json) {
        header('Content-Type: application/json; charset=utf-8');
    } else {
        header("Content-Security-Policy: default-src 'none'; style-src 'self'; script-src 'self'; connect-src 'self'; img-src 'self' data:; base-uri 'none'; form-action 'self'; frame-ancestors 'none'");
    }
}

function cofre_exigir_https(): void
{
    if (PHP_SAPI === 'cli') { return; }
    $https = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== '' && strtolower((string) $_SERVER['HTTPS']) !== 'off';
    $local = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true);
    if (!$https && !$local) {
        http_response_code(403);
        exit('O Cofre de Senhas exige uma conexão HTTPS segura.');
    }
}

function cofre_admin_id(): int
{
    return (int) ($_SESSION['admin_id'] ?? 0);
}

function cofre_csrf(): string
{
    if (empty($_SESSION['cofre_csrf'])) {
        $_SESSION['cofre_csrf'] = bin2hex(random_bytes(32));
    }
    return (string) $_SESSION['cofre_csrf'];
}

function cofre_exigir_csrf(): void
{
    $enviado = $_POST['csrf'] ?? '';
    if (!is_string($enviado) || !hash_equals(cofre_csrf(), $enviado)) {
        throw new RuntimeException('Requisição expirada. Atualize a página e tente novamente.');
    }
}

function cofre_chave(): string
{
    if (!function_exists('sodium_crypto_aead_xchacha20poly1305_ietf_encrypt')) {
        throw new RuntimeException('O cofre necessita da extensão PHP sodium.');
    }
    $bruta = getenv('HELPDESK_VAULT_KEY');
    $chave = is_string($bruta) ? base64_decode($bruta, true) : false;
    if ($chave === false || strlen($chave) !== SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_KEYBYTES) {
        throw new RuntimeException('O cofre não está configurado. Solicite a configuração segura da chave ao responsável pelo servidor.');
    }
    return $chave;
}

function cofre_contexto(int $id, int $admin, string $escopo, ?int $cliente): string
{
    return 'helpdesk-cofre-v1:' . $id . ':' . $admin . ':' . $escopo . ':' . ($cliente ?? 0);
}

function cofre_cifrar(int $id, int $admin, string $escopo, ?int $cliente, array $dados): array
{
    $chave = cofre_chave();
    $nonce = random_bytes(SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES);
    $json = json_encode($dados, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    $cifra = sodium_crypto_aead_xchacha20poly1305_ietf_encrypt($json, cofre_contexto($id, $admin, $escopo, $cliente), $nonce, $chave);
    sodium_memzero($chave);
    return [$nonce, $cifra];
}

function cofre_decifrar(array $registro): array
{
    $chave = cofre_chave();
    $json = sodium_crypto_aead_xchacha20poly1305_ietf_decrypt(
        (string) $registro['segredo_cifrado'],
        cofre_contexto((int) $registro['id'], (int) $registro['admin_criador_id'], (string) $registro['escopo'], isset($registro['cliente_id']) ? (int) $registro['cliente_id'] : null),
        (string) $registro['nonce'],
        $chave
    );
    sodium_memzero($chave);
    if ($json === false) {
        throw new RuntimeException('Não foi possível validar a integridade desta credencial.');
    }
    $dados = json_decode($json, true, 16, JSON_THROW_ON_ERROR);
    if (!is_array($dados)) {
        throw new RuntimeException('Credencial inválida.');
    }
    return $dados;
}

function cofre_log(PDO $pdo, int $admin, ?int $id, string $acao): void
{
    $stmt = $pdo->prepare('INSERT INTO cofre_auditoria (credencial_id, admin_id, acao) VALUES (?, ?, ?)');
    $stmt->execute([$id, $admin, $acao]);
}

function cofre_credencial(PDO $pdo, int $id, int $admin): array
{
    $stmt = $pdo->prepare("SELECT * FROM cofre_credenciais WHERE id = ? AND (escopo = 'cliente' OR admin_criador_id = ?) LIMIT 1");
    $stmt->execute([$id, $admin]);
    $linha = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$linha) {
        throw new RuntimeException('Credencial não encontrada ou acesso não autorizado.');
    }
    return $linha;
}

/** Bloqueio persistente por administrador, inclusive em sessões diferentes. */
function cofre_reautenticar(PDO $pdo, int $admin, string $senha): void
{
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM cofre_auditoria WHERE admin_id = ? AND acao = 'senha_incorreta' AND criado_em >= (NOW() - INTERVAL 15 MINUTE)");
    $stmt->execute([$admin]);
    if ((int) $stmt->fetchColumn() >= 5) {
        throw new RuntimeException('Muitas tentativas incorretas. Aguarde 15 minutos e tente novamente.');
    }
    $stmt = $pdo->prepare('SELECT senha FROM administradores WHERE id = ? LIMIT 1');
    $stmt->execute([$admin]);
    $hash = $stmt->fetchColumn();
    if (!is_string($hash) || !password_verify($senha, $hash)) {
        cofre_log($pdo, $admin, null, 'senha_incorreta');
        throw new RuntimeException('Senha administrativa incorreta.');
    }
}

function cofre_validar_dados(PDO $pdo, array $post, int $admin): array
{
    $titulo = trim((string) ($post['titulo'] ?? ''));
    $categoria = trim((string) ($post['categoria'] ?? ''));
    $escopo = (string) ($post['escopo'] ?? '');
    $cliente = $escopo === 'cliente' ? filter_var($post['cliente_id'] ?? null, FILTER_VALIDATE_INT) : null;
    if ($titulo === '' || mb_strlen($titulo) > 150 || !in_array($categoria, ['Roteador', 'Servidor', 'Computador', 'Hospedagem', 'Sistema', 'Banco de dados', 'Outro'], true)) {
        throw new RuntimeException('Informe um título (até 150 caracteres) e uma categoria válida.');
    }
    if (!in_array($escopo, ['pessoal', 'cliente'], true) || ($escopo === 'cliente' && (!$cliente || $cliente <= 0))) {
        throw new RuntimeException('Selecione o tipo de cofre e o cliente.');
    }
    if ($cliente !== null) {
        $stmt = $pdo->prepare('SELECT 1 FROM clientes WHERE id = ? LIMIT 1');
        $stmt->execute([$cliente]);
        if (!$stmt->fetchColumn()) {
            throw new RuntimeException('Cliente não encontrado.');
        }
    }
    $dados = [];
    foreach (['usuario' => 250, 'senha' => 4096, 'endereco' => 500, 'observacoes' => 4000] as $campo => $limite) {
        $valor = (string) ($post[$campo] ?? '');
        if (mb_strlen($valor) > $limite) {
            throw new RuntimeException('Um dos campos excede o tamanho permitido.');
        }
        $dados[$campo] = $valor;
    }
    if ($dados['senha'] === '') {
        throw new RuntimeException('Informe a senha ou segredo que deseja armazenar.');
    }
    return ['titulo' => $titulo, 'categoria' => $categoria, 'escopo' => $escopo, 'cliente_id' => $cliente, 'dados' => $dados, 'admin' => $admin];
}