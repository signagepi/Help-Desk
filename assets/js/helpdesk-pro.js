
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('[data-fill]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      const target = document.querySelector(btn.getAttribute('data-target') || '#descricao');
      if (target) { target.value = btn.getAttribute('data-fill'); target.dispatchEvent(new Event('input')); target.focus(); }
    });
  });
  document.querySelectorAll('[data-counter]').forEach(function (el) {
    const out = document.querySelector(el.getAttribute('data-counter'));
    const update = () => { if (out) out.textContent = el.value.length; };
    el.addEventListener('input', update); update();
  });
  document.querySelectorAll('[data-logo-preview]').forEach(function (input) {
    input.addEventListener('change', function () {
      const box = document.querySelector(input.getAttribute('data-logo-preview'));
      const file = input.files && input.files[0];
      if (!box || !file) return;
      const reader = new FileReader();
      reader.onload = e => { box.innerHTML = '<img src="' + e.target.result + '" alt="Prévia da logo">'; };
      reader.readAsDataURL(file);
    });
  });
  if (/\/views\/admin\/dashboard\.php$/.test(window.location.pathname)) {
    const container = document.querySelector('.hd-main > .hd-container');
    if (container) {
      const actions = document.createElement('div');
      actions.className = 'hd-actions hd-central-shortcut';
      actions.innerHTML = '<a class="hd-btn hd-btn-primary" href="central-servicos.php">🧰 Central de Serviços</a>';
      container.insertBefore(actions, container.firstChild);
    }

    const manageDetails = Array.from(document.querySelectorAll('.hd-table details'));
    const closeManage = function (details) {
      details.open = false;
      document.body.classList.remove('hd-modal-open');
    };
    manageDetails.forEach(function (details) {
      const panel = details.querySelector(':scope > div');
      const row = details.closest('tr');
      if (!panel) return;
      const title = row && row.querySelector('.hd-ticket-title');
      const meta = row && row.querySelector('.hd-ticket-meta');
      const head = document.createElement('div');
      head.className = 'hd-manage-modal-head';
      head.innerHTML = '<div><strong>Gerenciar chamado</strong><span></span></div><button type="button" class="hd-manage-close" aria-label="Fechar painel">×</button>';
      head.querySelector('span').textContent = [title && title.textContent.trim(), meta && meta.textContent.trim()].filter(Boolean).join(' — ');
      panel.insertBefore(head, panel.firstChild);
      head.querySelector('.hd-manage-close').addEventListener('click', function () { closeManage(details); });
      details.addEventListener('toggle', function () {
        if (details.open) {
          manageDetails.forEach(function (other) { if (other !== details) other.open = false; });
          document.body.classList.add('hd-modal-open');
          head.querySelector('.hd-manage-close').focus();
        } else if (!manageDetails.some(function (item) { return item.open; })) {
          document.body.classList.remove('hd-modal-open');
        }
      });
    });
    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape') {
        const opened = manageDetails.find(function (details) { return details.open; });
        if (opened) closeManage(opened);
      }
    });
  }
});
function imprimirRelatorio(){ window.print(); }
