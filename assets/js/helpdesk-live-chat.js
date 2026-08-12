(function(){
  const cfg = window.HD_CHAT_CONFIG || {};
  const base = cfg.endpointBase || '../../controllers/';
  const chatBox = document.getElementById('hdLiveChatHistory');
  const form = document.getElementById('hdLiveChatForm');
  const input = document.getElementById('hdLiveChatInput');
  const status = document.getElementById('hdLiveChatStatus');
  if(!chatBox || !form || !input) return;
  let lastId = Number(chatBox.dataset.lastId || 0);
  let loading = false;

  function escapeHtml(s){ return String(s).replace(/[&<>'"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'}[c])); }
  function scrollBottom(){ chatBox.scrollTop = chatBox.scrollHeight; }
  function setStatus(t){ if(status) status.textContent = t; }

  function append(m){
    if(document.querySelector('[data-msg-id="'+m.id+'"]')) return;
    const row = document.createElement('div');
    row.className = 'hd-chat-row ' + (m.mine ? 'mine' : 'other');
    row.dataset.msgId = m.id;
    row.innerHTML = '<div class="hd-chat-bubble"><div class="hd-chat-meta">'+(m.mine?'Você':'Atendimento')+' • '+escapeHtml(m.data)+'</div><div class="hd-chat-text">'+escapeHtml(m.mensagem).replace(/\n/g,'<br>')+'</div></div>';
    chatBox.appendChild(row);
    lastId = Math.max(lastId, Number(m.id));
    chatBox.dataset.lastId = String(lastId);
  }

  async function fetchMessages(initial=false){
    if(loading) return; loading = true;
    try{
      const qs = new URLSearchParams();
      if(!initial && lastId > 0) qs.set('last_id', lastId);
      if(cfg.clienteId) qs.set('cliente_id', cfg.clienteId);
      const r = await fetch(base + 'chat-fetch.php?' + qs.toString(), {cache:'no-store'});
      const data = await r.json();
      if(data.ok && data.messages){
        const before = chatBox.scrollHeight;
        data.messages.forEach(append);
        if(initial || data.messages.length) scrollBottom();
        if(data.messages.length) setStatus('Atualizado agora');
      }
    }catch(e){ setStatus('Conexão instável. Tentando novamente...'); }
    loading = false;
  }

  form.addEventListener('submit', async function(e){
    e.preventDefault();
    const msg = input.value.trim();
    if(!msg) return;
    const fd = new FormData();
    fd.append('mensagem', msg);
    if(cfg.clienteId) fd.append('cliente_id', cfg.clienteId);
    input.value = '';
    setStatus('Enviando...');
    try{
      const r = await fetch(base + 'chat-send.php', {method:'POST', body:fd});
      const data = await r.json();
      if(!data.ok){ setStatus(data.error || 'Erro ao enviar.'); input.value = msg; return; }
      await fetchMessages(false);
      setStatus('Mensagem enviada');
    }catch(e){ input.value = msg; setStatus('Falha ao enviar. Verifique a conexão.'); }
  });

  input.addEventListener('keydown', function(e){
    if(e.key === 'Enter' && !e.shiftKey){ e.preventDefault(); form.requestSubmit(); }
  });

  fetchMessages(true);
  setInterval(()=>fetchMessages(false), Number(cfg.chatInterval || 4000));
})();
