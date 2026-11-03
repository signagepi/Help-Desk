# Cofre de Senhas — implantação e segurança

## Escopo

Módulo administrativo para credenciais pessoais (privadas por administrador) e credenciais de clientes (acessíveis aos administradores atuais). Não cria acesso para o cliente nem para visitantes. A listagem apresenta somente título, categoria, cliente e atualização. Usuário, senha, endereço e observações são criptografados com `libsodium` (XChaCha20-Poly1305, nonce aleatório por gravação, autenticação do registro). As senhas de login do Helpdesk continuam com `password_hash`, sem reversão.

## Antes da primeira utilização

1. Faça backup **seguro** do banco de dados e dos arquivos do Helpdesk. Não envie credenciais ou backup para um repositório público.
2. Confirme HTTPS ativo, PHP 8.2+ com `pdo_mysql`, `mbstring` e `sodium` habilitados.
3. Execute `database/2026-11-03_cofre_senhas.sql` **uma única vez** no banco usado pelo Helpdesk; confirme que as duas tabelas foram criadas.
4. Gere uma chave aleatória de 32 bytes em ambiente privado: `php -r 'echo base64_encode(random_bytes(32)), PHP_EOL;'`.
5. Configure a variável de ambiente de processo `HELPDESK_VAULT_KEY` com esse valor **fora do diretório público**, em mecanismo seguro da hospedagem (não na árvore Git). Reinicie/recicle o PHP-FPM conforme necessário e valide se a variável está visível a PHP **sem imprimir seu valor**. A extensão sodium deve estar ativa.
6. Acesse o menu **Cofre de Senhas** na área administrativa e cadastre um registro de teste sem valor real. Faça uma consulta e verifique se apareceu a auditoria no banco.

**Essencial:** Guarde uma cópia da chave em um cofre externo protegido e offline. Sem essa chave, nem um backup do banco recupera as credenciais. Não inclua a chave em logs, páginas, e-mails, backup público, `.env` versionado ou capturas de tela. Se a hospedagem não permite variáveis de ambiente confiáveis, não ative o cofre antes de definir um método seguro alternativo.

## Permissões e funcionamento

- É obrigatório login administrativo. Todos os administradores podem consultar as credenciais associadas aos clientes. Credenciais pessoais são visíveis somente ao administrador que as criou.
- Cadastro, atualização, exclusão e consulta exigem confirmação da senha administrativa (reautenticação). Cinco falhas de confirmação em 15 minutos bloqueiam novas tentativas até expirarem.
- Todas as operações bem-sucedidas e as falhas de confirmação entram em `cofre_auditoria`, sem guardar segredos; exclusões não removem o registro de auditoria.
- Na consulta, os dados são exibidos apenas durante 45 segundos e jamais entram no HTML inicial. A cópia para a área de transferência depende de HTTPS e permissão do navegador. O sistema não apaga a área de transferência automaticamente.
- A exclusão é definitiva. É obrigatório aplicar política de backups criptografados, retenção e controle de acesso.

## Limitações e cuidados

- Este cofre é **gerenciado pelo servidor**, não é um gerenciador de senhas de conhecimento zero. Quem obtiver acesso ao banco **e** à chave pode decriptar os registros. Administradores com acesso à infraestrutura devem ser confiáveis.
- O histórico de auditoria fica no banco de dados e pode ser alterado por quem tem privilégios de DBA. Para rastreabilidade reforçada, envie registros sem segredos para um coletor de auditoria externo.
- A interface atual não implementa autenticação multifator, rotação automática de chave, exportação, compartilhamento por permissão individual nem recuperação sem chave. Recomendado ativar MFA no acesso administrativo antes de operar com credenciais reais.
- Informe os clientes e obtenha autorização adequada para custodiar credenciais de seus equipamentos. Evite guardar senhas de serviços críticos quando for possível usar contas de técnico limitadas e individuais.
- Não cole senhas em chamados, chats ou observações fora do cofre.

## Testes mínimos antes de produzir

1. Teste sintaxe PHP: `php -l` em todos os PHP novos.
2. Teste de criptografia: `php tests/test-cofre-crypto.php` com a variável `HELPDESK_VAULT_KEY` configurada no terminal de teste.
3. Acesse com usuário cliente: URLs do cofre e endpoints devem negar acesso.
4. Teste credencial pessoal com 2 administradores distintos: o segundo não deve listá-la nem consultá-la.
5. Teste consulta incorreta e limite de tentativas; confira registros na auditoria.
6. Teste edição, cliente inexistente, exclusão, csrf ausente, chave incorreta, restauração de backup e navegação sem JavaScript.

Nenhuma migration é executada automaticamente pelo PHP. Fazer homologação antes da instalação em produção.