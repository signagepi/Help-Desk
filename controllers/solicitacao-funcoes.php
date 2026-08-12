<?php

function textoSolicitacao($valor) {
    return trim((string)($valor ?? ''));
}

function criarSolicitacao(PDO $pdo, array $dados) {
    $prioridades = ['baixa', 'media', 'alta', 'urgente'];
    $prioridade = textoSolicitacao($dados['prioridade'] ?? 'media');
    if (!in_array($prioridade, $prioridades, true)) {
        $prioridade = 'media';
    }

    $clienteId = (int)($dados['cliente_id'] ?? 0);
    $descricao = textoSolicitacao($dados['descricao'] ?? '');
    if ($clienteId <= 0 || $descricao === '') {
        throw new InvalidArgumentException('Cliente e descrição são obrigatórios.');
    }

    $titulo = textoSolicitacao($dados['titulo'] ?? '');
    if ($titulo === '') {
        $titulo = mb_substr($descricao, 0, 120, 'UTF-8');
    }

    $horasSla = ['baixa' => 72, 'media' => 48, 'alta' => 24, 'urgente' => 8];
    $prazo = date('Y-m-d H:i:s', strtotime('+' . $horasSla[$prioridade] . ' hours'));
    $origem = textoSolicitacao($dados['origem'] ?? 'Portal do cliente');
    $registradoPorTipo = textoSolicitacao($dados['registrado_por_tipo'] ?? 'cliente');
    $registradoPorId = (int)($dados['registrado_por_id'] ?? 0);
    if (!in_array($registradoPorTipo, ['cliente', 'admin', 'api'], true)) {
        throw new InvalidArgumentException('Tipo de registrador inválido.');
    }

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("INSERT INTO solicitacoes
            (cliente_id, solicitante_nome, registrado_por_tipo, registrado_por_id,
             titulo, descricao, categoria, prioridade, canal, equipamento,
             local_atendimento, status, data, prazo, atualizado_em, tecnico_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pendente', NOW(), ?, NOW(), ?)");
        $stmt->execute([
            $clienteId,
            textoSolicitacao($dados['solicitante_nome'] ?? ''),
            $registradoPorTipo,
            $registradoPorId ?: null,
            $titulo,
            $descricao,
            textoSolicitacao($dados['categoria'] ?? 'Suporte técnico'),
            $prioridade,
            $origem,
            textoSolicitacao($dados['equipamento'] ?? ''),
            textoSolicitacao($dados['local_atendimento'] ?? ''),
            $prazo,
            !empty($dados['tecnico_id']) ? (int)$dados['tecnico_id'] : null,
        ]);

        $id = (int)$pdo->lastInsertId();
        $protocolo = 'INF-' . date('Y') . '-' . str_pad((string)$id, 5, '0', STR_PAD_LEFT);
        $pdo->prepare('UPDATE solicitacoes SET protocolo = ? WHERE id = ?')->execute([$protocolo, $id]);

        $descricaoAtividade = $origem === 'Portal do cliente'
            ? 'Solicitação aberta pelo portal do cliente.'
            : 'Solicitação registrada por técnico. Origem informada: ' . $origem . '.';
        $stmt = $pdo->prepare('INSERT INTO solicitacao_atividades (solicitacao_id, usuario_tipo, usuario_id, acao, descricao) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$id, $registradoPorTipo, $registradoPorId, 'abertura', $descricaoAtividade]);

        $pdo->commit();
        return ['id' => $id, 'protocolo' => $protocolo];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}
