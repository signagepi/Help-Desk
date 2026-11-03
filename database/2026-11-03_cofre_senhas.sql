-- Execute manualmente após backup. Não armazena senhas em texto simples.
-- Sem FK: compatibilidade com diferentes tipos INT existentes em clientes/administradores.
CREATE TABLE IF NOT EXISTS cofre_credenciais (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    admin_criador_id INT NOT NULL,
    escopo ENUM('pessoal', 'cliente') NOT NULL,
    cliente_id INT NULL,
    titulo VARCHAR(150) NOT NULL,
    categoria VARCHAR(40) NOT NULL,
    nonce VARBINARY(24) NULL,
    segredo_cifrado MEDIUMBLOB NULL,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_cofre_escopo_cliente (escopo, cliente_id),
    INDEX idx_cofre_admin (admin_criador_id),
    CONSTRAINT chk_cofre_escopo CHECK (
        (escopo = 'pessoal' AND cliente_id IS NULL) OR
        (escopo = 'cliente' AND cliente_id IS NOT NULL)
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cofre_auditoria (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    credencial_id BIGINT UNSIGNED NULL,
    admin_id INT NOT NULL,
    acao VARCHAR(32) NOT NULL,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_cofre_auditoria_admin_data (admin_id, criado_em),
    INDEX idx_cofre_auditoria_credencial (credencial_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;