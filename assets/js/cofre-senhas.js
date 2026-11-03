(() => {
  'use strict';
  const $ = id => document.getElementById(id);
  const form = $('cofre-form');
  const status = $('cofre-status');
  const painel = $('cofre-resultado');
  const confirmacao = $('cofre-senha-consulta');
  const token = form.querySelector('input[name="csrf"]').value;
  const campos = { usuario: $('cofre-usuario-visual'), senha: $('cofre-senha-visual'), endereco: $('cofre-endereco-visual'), observacoes: $('cofre-notas-visual') };
  let corrente = null;
  let cronometro = null;
  function ocultar() {
    if (cronometro) clearTimeout(cronometro);
    for (const campo of Object.values(campos)) campo.value = '';
    campos.senha.type = 'password';
    confirmacao.value = '';
    corrente = null;
    painel.hidden = true;
  }
  function limpar() {
    form.reset();
    $('cofre-edit-id').value = '';
    $('cofre-form-titulo').textContent = 'Cadastrar credencial';
    ajustarEscopo();
  }
  function ajustarEscopo() {
    const pessoal = $('cofre-escopo').value === 'pessoal';
    $('cofre-cliente').disabled = pessoal;
    $('cofre-cliente').required = !pessoal;
    if (pessoal) $('cofre-cliente').value = '';
  }
  $('cofre-escopo').addEventListener('change', ajustarEscopo);
  $('cofre-limpar').addEventListener('click', () => { limpar(); ocultar(); });
  ajustarEscopo();
  async function consultar(id) {
    const senhaAdmin = confirmacao.value;
    if (!senhaAdmin) { status.textContent = 'Informe sua senha administrativa para consultar.'; confirmacao.focus(); return; }
    ocultar();
    status.textContent = 'Validando acesso...';
    try {
      const resposta = await fetch('../../controllers/cofre-revelar.php', {
        method: 'POST', credentials: 'same-origin', cache: 'no-store',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ csrf: token, id: String(id), senha_admin: senhaAdmin })
      });
      const payload = await resposta.json();
      if (!resposta.ok || !payload.ok) throw new Error(payload.erro || 'Não foi possível consultar.');
      corrente = { id, dados: payload.dados, escopo: payload.escopo, cliente_id: payload.cliente_id };
      for (const [chave, campo] of Object.entries(campos)) campo.value = payload.dados[chave] || '';
      painel.hidden = false;
      status.textContent = 'Consulta autorizada e registrada no histórico.';
      cronometro = setTimeout(() => { ocultar(); status.textContent = 'Consulta encerrada automaticamente.'; }, 45000);
    } catch (err) {
      status.textContent = err.message || 'Erro de comunicação.';
    }
  }
  document.querySelectorAll('.cofre-abrir').forEach(btn => btn.addEventListener('click', () => consultar(Number(btn.dataset.id))));
  $('cofre-fechar').addEventListener('click', () => { ocultar(); status.textContent = 'Consulta encerrada.'; });
  $('cofre-alternar').addEventListener('click', () => {
    campos.senha.type = campos.senha.type === 'password' ? 'text' : 'password';
  });
  $('cofre-copiar').addEventListener('click', async () => {
    if (!corrente) return;
    try {
      await navigator.clipboard.writeText(campos.senha.value);
      status.textContent = 'Senha copiada. Limpe a área de transferência após o uso.';
    } catch (_) { status.textContent = 'Não foi possível copiar automaticamente. Verifique se está usando HTTPS.'; }
  });
  $('cofre-preencher').addEventListener('click', () => {
    if (!corrente) return;
    const { id, dados, escopo, cliente_id } = corrente;
    const botao = document.querySelector(`.cofre-abrir[data-id="${id}"]`);
    const linha = botao && botao.closest('tr');
    // Metadados da linha não são usados como HTML: só os valores controlados no formulário.
    $('cofre-edit-id').value = String(id);
    for (const chave of ['usuario', 'senha', 'endereco', 'observacoes']) $('cofre-' + chave).value = dados[chave] || '';
    if (linha) {
      $('cofre-titulo').value = linha.querySelector('td strong').textContent;
      $('cofre-categoria').value = linha.children[1].textContent.trim();
    }
    // O escopo/cliente são recuperados do servidor ao consultar, não de atributos manipuláveis.
    $('cofre-escopo').value = escopo;
    ajustarEscopo();
    if (cliente_id) $('cofre-cliente').value = String(cliente_id);
    $('cofre-form-titulo').textContent = 'Editar credencial #' + id;
    $('cofre-formulario').scrollIntoView({ behavior: 'smooth' });
    ocultar();
    cronometro = setTimeout(() => { limpar(); status.textContent = 'Edição encerrada automaticamente por segurança.'; }, 120000);
  });
  document.querySelectorAll('.cofre-excluir').forEach(btn => btn.addEventListener('click', () => {
    const senhaAdmin = confirmacao.value;
    if (!senhaAdmin) { status.textContent = 'Informe sua senha administrativa antes de excluir.'; confirmacao.focus(); return; }
    if (!window.confirm('Excluir definitivamente esta credencial? Essa operação não pode ser desfeita.')) return;
    $('cofre-excluir-id').value = btn.dataset.id;
    $('cofre-excluir-senha').value = senhaAdmin;
    $('cofre-form-excluir').submit();
  }));
  window.addEventListener('pagehide', ocultar);
})();