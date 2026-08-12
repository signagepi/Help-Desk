(function(){
  const cfg = window.HD_CHAT_CONFIG || {};
  const base = cfg.endpointBase || '../../controllers/';
  const unreadUrl = base + 'chat-unread.php';
  let lastCount = Number(sessionStorage.getItem('hdUnreadCount') || 0);
  let soundEnabled = false;

  function enableSound(){ soundEnabled = true; document.removeEventListener('click', enableSound); document.removeEventListener('keydown', enableSound); }
  document.addEventListener('click', enableSound);
  document.addEventListener('keydown', enableSound);

  function beep(){
    if(!soundEnabled) return;
    try{
      const Ctx = window.AudioContext || window.webkitAudioContext;
      if(!Ctx) return;
      const ctx = new Ctx();
      const osc = ctx.createOscillator();
      const gain = ctx.createGain();
      osc.type = 'sine'; osc.frequency.value = 880;
      gain.gain.value = 0.06;
      osc.connect(gain); gain.connect(ctx.destination);
      osc.start();
      setTimeout(()=>{ osc.stop(); ctx.close(); }, 180);
      setTimeout(()=>{
        const ctx2 = new Ctx(); const o2 = ctx2.createOscillator(); const g2 = ctx2.createGain();
        o2.type = 'sine'; o2.frequency.value = 1175; g2.gain.value = 0.05;
        o2.connect(g2); g2.connect(ctx2.destination); o2.start();
        setTimeout(()=>{ o2.stop(); ctx2.close(); }, 170);
      }, 230);
    }catch(e){}
  }

  function toast(data){
    let box = document.querySelector('.hd-live-toast');
    if(!box){
      box = document.createElement('div');
      box.className = 'hd-live-toast';
      document.body.appendChild(box);
    }
    const name = data.last && data.last.from ? data.last.from : 'Nova mensagem';
    const msg = data.last && data.last.message ? data.last.message : 'Você recebeu uma nova mensagem.';
    box.innerHTML = '<strong>🔔 '+escapeHtml(name)+'</strong><span>'+escapeHtml(msg)+'</span><a href="'+(cfg.chatUrl || 'mensagens.php')+'">Abrir chat</a>';
    box.classList.add('show');
    setTimeout(()=>box.classList.remove('show'), 9000);
  }

  function escapeHtml(s){ return String(s).replace(/[&<>'"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'}[c])); }

  function updateBadges(count){
    document.querySelectorAll('[data-chat-badge]').forEach(el => {
      el.textContent = count > 0 ? count : '';
      el.style.display = count > 0 ? 'inline-flex' : 'none';
    });
    document.title = count > 0 ? '('+count+') ' + document.title.replace(/^\(\d+\)\s+/, '') : document.title.replace(/^\(\d+\)\s+/, '');
  }

  async function poll(){
    try{
      const r = await fetch(unreadUrl, {cache:'no-store'});
      const data = await r.json();
      if(!data.ok) return;
      updateBadges(Number(data.count || 0));
      if(Number(data.count || 0) > lastCount){ beep(); toast(data); }
      lastCount = Number(data.count || 0);
      sessionStorage.setItem('hdUnreadCount', String(lastCount));
    }catch(e){}
  }
  poll();
  setInterval(poll, Number(cfg.alertInterval || 6000));
})();
