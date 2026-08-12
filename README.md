# Help Desk

Sistema de Helpdesk em PHP e MySQL/MariaDB, com areas para administradores/tecnicos e clientes.

## Requisitos

- PHP 7.4 ou superior com PDO MySQL
- MySQL ou MariaDB
- Servidor web (Apache/Nginx)

## Configuracao

1. Importe `database/schema.sql` em um banco vazio.
2. Configure `DB_HOST`, `DB_NAME`, `DB_USER` e `DB_PASS` no ambiente do servidor.
3. Aponte o servidor web para a raiz do projeto.
4. Garanta permissao de escrita nas pastas de `uploads/`.

## Atualizações de banco

Em instalações existentes, execute os arquivos de `database/migrations/` em ordem. A migração `20260811_central_servicos.sql` habilita a rastreabilidade e a abertura de O.S. pelo painel técnico.

O repositório não inclui credenciais, dados de clientes nem arquivos enviados em producao.
