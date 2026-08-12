<?php
/**
 * Funcoes de alerta por Telegram para o Helpdesk Infoservice.
 * Compativel com hospedagem compartilhada: usa cURL quando existe e tenta file_get_contents como alternativa.
 */

function tg_texto($valor) {
    return trim((string)($valor ?? ''));
}

function tg_html($valor) {
    return htmlspecialchars((string)($valor ?? ''), ENT_QUOTES, 'UTF-8');
}

function telegramGarantirTabelas($pdo) {
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS config_telegram_alertas (
            id TINYINT(1) NOT NULL,
            ativo TINYINT(1) NOT NULL DEFAULT 0,
            bot_token VARCHAR(255) DEFAULT NULL,
            chat_ids TEXT DEFAULT NULL,
            url_sistema VARCHAR(255) DEFAULT NULL,
            notificar_novo_chamado TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci");

        $pdo->exec("INSERT IGNORE INTO config_telegram_alertas
            (id, ativo, bot_token, chat_ids, url_sistema, notificar_novo_chamado)
            VALUES (1, 0, '', '', '', 1)");

        $pdo->exec("CREATE TABLE IF NOT EXISTS telegram_alertas_log (
            id INT(11) NOT NULL AUTO_INCREMENT,
            solicitacao_id INT(11) DEFAULT NULL,
            canal VARCHAR(30) NOT NULL DEFAULT 'telegram',
            destino VARCHAR(80) DEFAULT NULL,
            status VARCHAR(30) NOT NULL DEFAULT 'pendente',
            resposta TEXT DEFAULT NULL,
            criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY solicitacao_id (solicitacao_id)
        ) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci");
    } catch (Throwable $e) {
        error_log('Telegram - erro ao garantir tabelas: ' . $e->getMessage());
    }
}

function telegramBuscarConfig($pdo) {
    telegramGarantirTabelas($pdo);
    try {
        $stmt = $pdo->query("SELECT * FROM config_telegram_alertas WHERE id = 1 LIMIT 1");
        $config = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;
        if (!$config) {
            $pdo->exec("INSERT IGNORE INTO config_telegram_alertas (id, ativo, bot_token, chat_ids, url_sistema, notificar_novo_chamado) VALUES (1, 0, '', '', '', 1)");
            $stmt = $pdo->query("SELECT * FROM config_telegram_alertas WHERE id = 1 LIMIT 1");
            $config = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;
        }
        return $config ?: [];
    } catch (Throwable $e) {
        error_log('Telegram - erro ao buscar configuracao: ' . $e->getMessage());
        return [];
    }
}

function telegramSalvarConfig($pdo, $ativo, $botToken, $chatIds, $urlSistema, $notificarNovoChamado) {
    telegramGarantirTabelas($pdo);
    $stmt = $pdo->prepare("UPDATE config_telegram_alertas
        SET ativo = ?, bot_token = ?, chat_ids = ?, url_sistema = ?, notificar_novo_chamado = ?
        WHERE id = 1");
    $stmt->execute([
        (int)$ativo,
        tg_texto($botToken),
        tg_texto($chatIds),
        tg_texto($urlSistema),
        (int)$notificarNovoChamado
    ]);
}

function telegramNormalizarChatIds($chatIds) {
    $partes = preg_split('/[\s,;]+/', (string)$chatIds, -1, PREG_SPLIT_NO_EMPTY);
    $limpos = [];
    foreach ($partes as $parte) {
        $parte = trim($parte);
        if ($parte !== '') {
            $limpos[] = $parte;
        }
    }
    return array_values(array_unique($limpos));
}

function telegramHttpPost($url, array $dados) {
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($dados));
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 8);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        $body = curl_exec($ch);
        $erro = curl_error($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return ['body' => $body, 'error' => $erro, 'http_code' => $httpCode];
    }

    $contexto = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
            'content' => http_build_query($dados),
            'timeout' => 15
        ]
    ]);
    $body = @file_get_contents($url, false, $contexto);
    return ['body' => $body, 'error' => $body === false ? 'Falha em file_get_contents. Verifique allow_url_fopen ou cURL.' : '', 'http_code' => 0];
}

function telegramHttpGet($url) {
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 8);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        $body = curl_exec($ch);
        $erro = curl_error($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return ['body' => $body, 'error' => $erro, 'http_code' => $httpCode];
    }
    $body = @file_get_contents($url);
    return ['body' => $body, 'error' => $body === false ? 'Falha em file_get_contents. Verifique allow_url_fopen ou cURL.' : '', 'http_code' => 0];
}

function telegramRegistrarLog($pdo, $solicitacaoId, $destino, $status, $resposta) {
    try {
        telegramGarantirTabelas($pdo);
        $stmt = $pdo->prepare("INSERT INTO telegram_alertas_log (solicitacao_id, canal, destino, status, resposta) VALUES (?, 'telegram', ?, ?, ?)");
        $stmt->execute([
            $solicitacaoId ? (int)$solicitacaoId : null,
            (string)$destino,
            (string)$status,
            mb_substr((string)$resposta, 0, 5000, 'UTF-8')
        ]);
    } catch (Throwable $e) {
        error_log('Telegram - erro ao registrar log: ' . $e->getMessage());
    }
}

function telegramEnviarMensagem($pdo, $texto, $urlBotao = '', $textoBotao = 'Abrir chamado', $solicitacaoId = null) {
    $config = telegramBuscarConfig($pdo);

    if (empty($config['ativo']) || empty($config['bot_token']) || empty($config['chat_ids'])) {
        return ['ok' => false, 'message' => 'Alertas do Telegram inativos ou incompletos.'];
    }

    $token = trim((string)$config['bot_token']);
    $chatIds = telegramNormalizarChatIds($config['chat_ids']);
    if (!$chatIds) {
        return ['ok' => false, 'message' => 'Nenhum chat_id informado.'];
    }

    $endpoint = 'https://api.telegram.org/bot' . rawurlencode($token) . '/sendMessage';
    $enviados = 0;
    $erros = [];

    foreach ($chatIds as $chatId) {
        $dados = [
            'chat_id' => $chatId,
            'text' => $texto,
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => 'true'
        ];

        if ($urlBotao !== '') {
            $dados['reply_markup'] = json_encode([
                'inline_keyboard' => [[
                    ['text' => $textoBotao, 'url' => $urlBotao]
                ]]
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $resposta = telegramHttpPost($endpoint, $dados);
        $json = json_decode((string)$resposta['body'], true);
        $okTelegram = is_array($json) && !empty($json['ok']);

        if ($okTelegram) {
            $enviados++;
            telegramRegistrarLog($pdo, $solicitacaoId, $chatId, 'enviado', (string)$resposta['body']);
        } else {
            $erro = $resposta['error'] ?: (string)$resposta['body'];
            $erros[] = 'Chat ' . $chatId . ': ' . $erro;
            telegramRegistrarLog($pdo, $solicitacaoId, $chatId, 'erro', $erro);
        }
    }

    return [
        'ok' => $enviados > 0,
        'message' => $enviados > 0 ? 'Mensagem enviada para ' . $enviados . ' destino(s).' : implode(' | ', $erros),
        'erros' => $erros
    ];
}

function telegramBuscarChatsPorToken($botToken) {
    $token = trim((string)$botToken);
    if ($token === '') {
        return ['ok' => false, 'message' => 'Informe o token do bot antes de buscar o chat_id.', 'chats' => []];
    }

    $url = 'https://api.telegram.org/bot' . rawurlencode($token) . '/getUpdates';
    $resposta = telegramHttpGet($url);
    $json = json_decode((string)$resposta['body'], true);

    if (!is_array($json) || empty($json['ok'])) {
        return ['ok' => false, 'message' => $resposta['error'] ?: (string)$resposta['body'], 'chats' => []];
    }

    $chats = [];
    foreach (($json['result'] ?? []) as $update) {
        $chat = $update['message']['chat'] ?? ($update['my_chat_member']['chat'] ?? null);
        if (!$chat || empty($chat['id'])) {
            continue;
        }
        $id = (string)$chat['id'];
        $nome = trim(($chat['first_name'] ?? '') . ' ' . ($chat['last_name'] ?? ''));
        if ($nome === '') {
            $nome = $chat['title'] ?? ($chat['username'] ?? 'Chat Telegram');
        }
        $chats[$id] = $nome;
    }

    return ['ok' => true, 'message' => count($chats) . ' chat(s) encontrado(s).', 'chats' => $chats];
}

function telegramMontarUrlChamado($config, $solicitacaoId) {
    $base = trim((string)($config['url_sistema'] ?? ''));
    if ($base === '') {
        return '';
    }
    return rtrim($base, '/') . '/views/admin/itens-chamado.php?id=' . (int)$solicitacaoId;
}

function enviarAlertaNovoChamado($pdo, $solicitacaoId) {
    $config = telegramBuscarConfig($pdo);
    if (empty($config['ativo']) || empty($config['notificar_novo_chamado'])) {
        return ['ok' => false, 'message' => 'Alerta de novo chamado desativado.'];
    }

    $stmt = $pdo->prepare("SELECT s.*, c.nome AS cliente_nome, c.email AS cliente_email, c.telefone AS cliente_telefone
        FROM solicitacoes s
        JOIN clientes c ON c.id = s.cliente_id
        WHERE s.id = ?
        LIMIT 1");
    $stmt->execute([(int)$solicitacaoId]);
    $s = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$s) {
        return ['ok' => false, 'message' => 'Chamado nao encontrado.'];
    }

    $protocolo = $s['protocolo'] ?: ('#' . $s['id']);
    $prioridade = ucfirst((string)($s['prioridade'] ?: 'media'));
    $titulo = $s['titulo'] ?: mb_substr((string)$s['descricao'], 0, 90, 'UTF-8');
    $descricao = mb_substr((string)$s['descricao'], 0, 700, 'UTF-8');

    $linhas = [];
    $linhas[] = "🚨 <b>Novo chamado aberto</b>";
    $linhas[] = "";
    $linhas[] = "<b>Protocolo:</b> " . tg_html($protocolo);
    $linhas[] = "<b>Cliente:</b> " . tg_html($s['cliente_nome'] ?? '');
    if (!empty($s['cliente_telefone'])) {
        $linhas[] = "<b>Telefone:</b> " . tg_html($s['cliente_telefone']);
    }
    $linhas[] = "<b>Título:</b> " . tg_html($titulo);
    $linhas[] = "<b>Categoria:</b> " . tg_html($s['categoria'] ?: 'Suporte técnico');
    $linhas[] = "<b>Prioridade:</b> " . tg_html($prioridade);
    if (!empty($s['equipamento'])) {
        $linhas[] = "<b>Equipamento:</b> " . tg_html($s['equipamento']);
    }
    if (!empty($s['local_atendimento'])) {
        $linhas[] = "<b>Local:</b> " . tg_html($s['local_atendimento']);
    }
    $linhas[] = "";
    $linhas[] = "<b>Descrição:</b> " . tg_html($descricao);

    $url = telegramMontarUrlChamado($config, (int)$solicitacaoId);
    return telegramEnviarMensagem($pdo, implode("\n", $linhas), $url, 'Abrir chamado', (int)$solicitacaoId);
}
