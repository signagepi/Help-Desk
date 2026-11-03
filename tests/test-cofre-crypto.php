<?php
declare(strict_types=1);
require __DIR__ . '/../lib/cofre-funcoes.php';
if (!getenv('HELPDESK_VAULT_KEY')) {
    fwrite(STDERR, "Configure HELPDESK_VAULT_KEY para executar este teste.\n");
    exit(1);
}
$payload = ['usuario' => 'admin', 'senha' => 'teste-🔑', 'endereco' => '192.0.2.1', 'observacoes' => 'teste local'];
[$nonce, $cifra] = cofre_cifrar(100, 1, 'cliente', 2, $payload);
$registro = ['id' => 100, 'admin_criador_id' => 1, 'escopo' => 'cliente', 'cliente_id' => 2, 'nonce' => $nonce, 'segredo_cifrado' => $cifra];
assert(cofre_decifrar($registro) === $payload);
$registro['cliente_id'] = 3;
try { cofre_decifrar($registro); fwrite(STDERR, "Falhou: autenticação do cliente.\n"); exit(1); } catch (RuntimeException $e) { /* esperado */ }
$registro['cliente_id'] = 2;
$registro['segredo_cifrado'][1] = chr(ord($registro['segredo_cifrado'][1]) ^ 1);
try { cofre_decifrar($registro); fwrite(STDERR, "Falhou: autenticação da cifra.\n"); exit(1); } catch (RuntimeException $e) { /* esperado */ }
echo "OK: roundtrip, alteração de escopo e adulteração detectadas.\n";