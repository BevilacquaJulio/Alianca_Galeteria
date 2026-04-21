const API = BASE_URL + '/api.php?route=';
let currentPage = 'dashboard';
let currentAdmin = null;

async function api(route, method = 'GET', body = null) {
  const opts = {
    method,
    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
  };
  if (body) opts.body = JSON.stringify(body);
  const res = await fetch(API + route, opts);
  const data = await res.json().catch(() => ({}));
  return { ok: res.ok, status: res.status, data };
}

function fmtBRL(v) {
  return 'R$ ' + parseFloat(v || 0).toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}
function fmtDate(d) {
  if (!d) return '—';
  const dt = new Date(d.replace(' ', 'T'));
  return dt.toLocaleDateString('pt-BR') + ' ' + dt.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
}
function fmtDateOnly(d) {
  if (!d) return '—';
  return new Date(d + 'T00:00:00').toLocaleDateString('pt-BR');
}
function escHtml(s) {
  if (s == null) return '';
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function statusBadge(s) {
  const map = {
    rascunho:   ['Rascunho',   'rascunho'],
    confirmado: ['Confirmado', 'confirmado'],
    em_preparo: ['Em Preparo', 'em_preparo'],
    pronto:     ['Pronto',     'pronto'],
    entregue:   ['Entregue',   'entregue'],
    cancelado:  ['Cancelado',  'cancelado'],
  };
  const [lbl, cls] = map[s] || [s, s];
  return `<span class="status-badge status-${cls}">${lbl}</span>`;
}
function stockBadge(qty, min) {
  if (qty === 0) return `<span class="stock-zero">Zerado</span>`;
  if (qty <= min) return `<span class="stock-low">⚠ Baixo (${qty})</span>`;
  return `<span class="stock-ok">✓ ${qty}</span>`;
}

function toast(msg, type = 'info') {
  const c = document.getElementById('adminToast');
  if (!c) return;
  const el = document.createElement('div');
  el.className = `toast toast-${type}`;
  el.innerHTML = ({ success:'✅', error:'❌', info:'ℹ️', warning:'⚠️' }[type] || 'ℹ️') + '&nbsp;' + escHtml(msg);
  c.appendChild(el);
  setTimeout(() => el.remove(), 4000);
}

function loading(html = '') {
  return `<div class="loading-area"><div class="spinner"></div><span>${html || 'Carregando...'}</span></div>`;
}
function emptyState(icon, title, desc = '') {
  return `<div class="empty-state"><div class="empty-state-icon">${icon}</div><h3>${title}</h3>${desc ? `<p>${desc}</p>` : ''}</div>`;
}

const Modal = {
  show(title, bodyHtml, footerHtml = '', size = '') {
    const overlay = document.getElementById('globalModal');
    const box     = document.getElementById('globalModalBox');
    document.getElementById('globalModalTitle').textContent = title;
    document.getElementById('globalModalBody').innerHTML  = bodyHtml;
    document.getElementById('globalModalFooter').innerHTML = footerHtml;
    if (size) box.classList.add(size);
    overlay.style.display = 'flex';
  },
  hide() {
    document.getElementById('globalModal').style.display = 'none';
    document.getElementById('globalModalBox').className = 'modal-container';
  },
};

const Confirm = {
  resolve: null,
  show(message, title = 'Confirmar') {
    document.getElementById('confirmTitle').textContent = title;
    document.getElementById('confirmMessage').textContent = message;
    document.getElementById('confirmModal').style.display = 'flex';
    return new Promise(resolve => { this.resolve = resolve; });
  },
  answer(ok) {
    document.getElementById('confirmModal').style.display = 'none';
    if (this.resolve) { this.resolve(ok); this.resolve = null; }
  },
};

async function tryLogin() {
  const email = document.getElementById('loginEmail').value.trim();
  const pass  = document.getElementById('loginPassword').value;
  const err   = document.getElementById('loginError');
  const btn   = document.getElementById('btnLogin');

  err.style.display = 'none';
  btn.disabled = true;
  btn.textContent = 'Entrando...';

  const { ok, data } = await api('auth/login', 'POST', { email, password: pass });

  btn.disabled = false;
  btn.textContent = 'Entrar no Sistema';

  if (ok && data.success) {
    currentAdmin = data.admin;
    document.getElementById('loginScreen').style.display = 'none';
    document.getElementById('appShell').style.display = 'flex';
    initApp(data.admin);
  } else {
    err.textContent = data.error || 'Credenciais inválidas.';
    err.style.display = '';
  }
}

async function doLogout() {
  if (!(await Confirm.show('Deseja sair do sistema?', 'Sair'))) return;
  await api('auth/logout', 'POST');
  location.reload();
}

function initApp(admin) {
  updateUserDisplay(admin);
  startClock();
  loadDashboard();

  document.querySelectorAll('.nav-item[data-page]').forEach(el => {
    el.addEventListener('click', e => {
      e.preventDefault();
      const page = el.dataset.page;
      navigate(page);
    });
  });

  document.getElementById('sidebarToggle').addEventListener('click', () => {
    document.getElementById('sidebar').classList.toggle('open');
    document.getElementById('sidebarOverlay').classList.toggle('show');
  });
  document.getElementById('sidebarOverlay').addEventListener('click', () => {
    document.getElementById('sidebar').classList.remove('open');
    document.getElementById('sidebarOverlay').classList.remove('show');
  });

  document.getElementById('globalModalClose').addEventListener('click', Modal.hide);
  document.getElementById('globalModal').addEventListener('click', e => {
    if (e.target === document.getElementById('globalModal')) Modal.hide();
  });
  document.getElementById('confirmCancel').addEventListener('click', () => Confirm.answer(false));
  document.getElementById('confirmOk').addEventListener('click', () => Confirm.answer(true));

  document.getElementById('btnLogout').addEventListener('click', doLogout);

  window.addEventListener('hashchange', () => {
    const page = location.hash.replace('#', '') || 'dashboard';
    if (page !== currentPage) navigate(page, false);
  });
  const initHash = location.hash.replace('#', '') || 'dashboard';
  if (initHash !== 'dashboard') navigate(initHash, false);
}

function updateUserDisplay(admin) {
  if (!admin) return;
  document.getElementById('userName').textContent  = admin.name || 'Admin';
  document.getElementById('userRole').textContent  = admin.role || '';
  document.getElementById('userAvatar').textContent = (admin.name || 'A')[0].toUpperCase();
}

function startClock() {
  const el = document.getElementById('topbarTime');
  const tick = () => {
    if (el) el.textContent = new Date().toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
  };
  tick();
  setInterval(tick, 1000);
}

function navigate(page, pushHash = true) {
  currentPage = page;
  if (pushHash) location.hash = page;

  document.querySelectorAll('.nav-item').forEach(el => {
    el.classList.toggle('active', el.dataset.page === page);
  });

  document.getElementById('sidebar').classList.remove('open');
  document.getElementById('sidebarOverlay').classList.remove('show');

  const titles = {
    dashboard: 'Dashboard',
    orders:    'Pedidos',
    products:  'Produtos',
    stock:     'Estoque',
    customers: 'Clientes',
    upsell:    'Upsell / QR Code',
    raffles:   'Sorteios',
    reports:   'Relatórios',
  };
  document.getElementById('topbarTitle').textContent = titles[page] || page;

  const content = document.getElementById('pageContent');
  content.innerHTML = loading();

  const loaders = {
    dashboard: loadDashboard,
    orders:    loadOrders,
    products:  loadProducts,
    stock:     loadStock,
    customers: loadCustomers,
    upsell:    loadUpsell,
    raffles:   loadRaffles,
    reports:   loadReports,
  };
  (loaders[page] || loadDashboard)();
}

async function loadDashboard() {
  const { ok, data } = await api('dashboard');
  if (!ok) {
    document.getElementById('pageContent').innerHTML = '<p class="text-muted" style="padding:40px">Erro ao carregar dashboard.</p>';
    return;
  }
  const d = data;

  const lowCount = (d.low_stock || []).length;
  const badge = document.getElementById('badgeStock');
  const alert = document.getElementById('topbarAlertLow');
  if (lowCount > 0) {
    badge.textContent = lowCount; badge.classList.add('show');
    alert.style.display = 'flex';
    document.getElementById('lowStockCount').textContent = lowCount;
  }

  const todayOrders = parseInt(d.kpi_today?.total_orders || 0);
  const ordBadge = document.getElementById('badgeOrders');
  if (todayOrders > 0) { ordBadge.textContent = todayOrders; ordBadge.classList.add('show'); }

  const salesWeek = d.sales_week || [];
  const chartLabels = salesWeek.map(r => {
    const d = new Date(r.day + 'T12:00:00');
    return d.toLocaleDateString('pt-BR', { weekday: 'short', day: '2-digit' });
  });
  const chartRevenue = salesWeek.map(r => parseFloat(r.total_revenue));
  const chartOrders  = salesWeek.map(r => parseInt(r.total_orders));

  const statusMap = {};
  (d.status_today || []).forEach(r => statusMap[r.status] = r.count);

  const lastOrdersRows = (d.last_orders || []).slice(0, 5).map(o => `
    <tr>
      <td class="font-bold">#${o.id}</td>
      <td>${escHtml(o.customer_name || '— Balcão')}</td>
      <td>${statusBadge(o.status)}</td>
      <td class="text-gold">${fmtBRL(o.total)}</td>
      <td class="text-muted">${fmtDate(o.created_at)}</td>
      <td>
        <button class="btn btn-xs btn-outline" onclick="openOrder(${o.id})">Ver</button>
      </td>
    </tr>`).join('') || `<tr><td colspan="6">${emptyState('📋', 'Nenhum pedido ainda')}</td></tr>`;

  const lowStockRows = (d.low_stock || []).slice(0, 5).map(s => `
    <div class="flex flex-center gap-2" style="padding:8px 0; border-bottom:1px solid var(--border)">
      <span style="flex:1; font-size:.85rem; color:var(--text)">${escHtml(s.name)}</span>
      ${stockBadge(s.quantity, s.min_quantity)}
    </div>`).join('') || `<p class="text-muted" style="padding:12px 0; font-size:.85rem">Todos os estoques OK ✓</p>`;

  document.getElementById('pageContent').innerHTML = `
    <div class="page-header">
      <div class="page-header-left">
        <h1>Dashboard</h1>
        <p>Visão geral de hoje — ${new Date().toLocaleDateString('pt-BR', { weekday:'long', day:'numeric', month:'long', year:'numeric' })}</p>
      </div>
    </div>

    <div class="kpi-grid">
      <div class="kpi-card kpi-accent-gold">
        <div class="kpi-label">Receita Hoje</div>
        <div class="kpi-value">${fmtBRL(d.kpi_today?.total_revenue)}</div>
        <div class="kpi-sub">Pedidos não cancelados</div>
        <div class="kpi-icon">💰</div>
      </div>
      <div class="kpi-card kpi-accent-wine">
        <div class="kpi-label">Pedidos Hoje</div>
        <div class="kpi-value">${d.kpi_today?.total_orders || 0}</div>
        <div class="kpi-sub">Total do dia</div>
        <div class="kpi-icon">📋</div>
      </div>
      <div class="kpi-card kpi-accent-green">
        <div class="kpi-label">Ticket Médio</div>
        <div class="kpi-value">${fmtBRL(d.kpi_today?.avg_ticket)}</div>
        <div class="kpi-sub">Por pedido hoje</div>
        <div class="kpi-icon">🎯</div>
      </div>
      <div class="kpi-card kpi-accent-ember">
        <div class="kpi-label">Estoque Baixo</div>
        <div class="kpi-value">${lowCount}</div>
        <div class="kpi-sub">Produto(s) abaixo do mínimo</div>
        <div class="kpi-icon">📦</div>
      </div>
    </div>

    <div class="grid-3-1">
      <div class="card">
        <div class="card-header">
          <span class="card-title">Vendas (últimos 7 dias)</span>
          <span class="text-muted" style="font-size:.8rem">Receita diária</span>
        </div>
        <div class="card-body">
          <div class="chart-wrap" style="height:220px">
            <canvas id="chartSalesWeek" style="width:100%; height:100%"></canvas>
          </div>
        </div>
      </div>
      <div class="card">
        <div class="card-header"><span class="card-title">Status Hoje</span></div>
        <div class="card-body">
          <div class="chart-wrap" style="height:220px">
            <canvas id="chartStatusToday" style="width:100%; height:100%"></canvas>
          </div>
        </div>
      </div>
    </div>

    <div class="grid-2" style="margin-top:20px">
      <div class="card">
        <div class="card-header">
          <span class="card-title">Últimos Pedidos</span>
          <button class="btn btn-xs btn-outline" onclick="navigate('orders')">Ver todos</button>
        </div>
        <div class="table-wrap">
          <table class="data-table">
            <thead><tr><th>#</th><th>Cliente</th><th>Status</th><th>Total</th><th>Data</th><th></th></tr></thead>
            <tbody>${lastOrdersRows}</tbody>
          </table>
        </div>
      </div>
      <div class="card">
        <div class="card-header">
          <span class="card-title">Alertas de Estoque</span>
          <button class="btn btn-xs btn-outline" onclick="navigate('stock')">Gerenciar</button>
        </div>
        <div class="card-body">${lowStockRows}</div>
        <div class="card-footer" style="display:flex; gap:12px">
          <div style="flex:1; text-align:center">
            <div style="font-size:1.5rem; font-weight:800; color:var(--text-warm)">${d.total_products || 0}</div>
            <div style="font-size:.75rem; color:var(--text-muted)">Produtos Ativos</div>
          </div>
          <div style="flex:1; text-align:center">
            <div style="font-size:1.5rem; font-weight:800; color:var(--text-warm)">${d.total_customers || 0}</div>
            <div style="font-size:.75rem; color:var(--text-muted)">Clientes</div>
          </div>
        </div>
      </div>
    </div>`;

  requestAnimationFrame(() => {
    if (chartLabels.length > 0) {
      Charts.line('chartSalesWeek', chartLabels,
        [{ label: 'Receita', data: chartRevenue, color: '#D48A1C' }],
        { formatY: v => 'R$' + v.toLocaleString('pt-BR') }
      );
    }

    const statusLabels = Object.keys(statusMap);
    const statusValues = Object.values(statusMap).map(Number);
    if (statusLabels.length > 0) {
      Charts.donut('chartStatusToday', statusLabels, statusValues);
    }
  });
}

let ordersPage = 1;
let ordersFilter = { status: '', search: '' };

async function loadOrders() {
  const { ok, data } = await api(`orders?page=${ordersPage}&limit=20&status=${ordersFilter.status}&search=${encodeURIComponent(ordersFilter.search)}`);

  const rows = (data.data || []).map(o => `
    <tr>
      <td class="font-bold">#${o.id}</td>
      <td>
        <div style="font-weight:600; color:var(--text-warm)">${escHtml(o.customer_name || '— Balcão')}</div>
        <div class="text-sm">${escHtml(o.customer_phone || '')}</div>
      </td>
      <td>${statusBadge(o.status)}</td>
      <td class="text-gold">${fmtBRL(o.total)}</td>
      <td class="text-muted">${fmtDate(o.created_at)}</td>
      <td>
        <div class="flex gap-2">
          <button class="btn btn-xs btn-outline" onclick="openOrder(${o.id})">Ver</button>
          <button class="btn btn-xs btn-ghost" onclick="openStatusModal(${o.id}, '${o.status}')">Status</button>
        </div>
      </td>
    </tr>`).join('') || `<tr><td colspan="6">${emptyState('📋','Nenhum pedido encontrado')}</td></tr>`;

  const statusOptions = ['', 'rascunho', 'confirmado', 'em_preparo', 'pronto', 'entregue', 'cancelado'];
  const statusLabels  = ['Todos', 'Rascunho', 'Confirmado', 'Em Preparo', 'Pronto', 'Entregue', 'Cancelado'];

  document.getElementById('pageContent').innerHTML = `
    <div class="page-header">
      <div class="page-header-left"><h1>Pedidos</h1><p>Total: ${data.total || 0} registros</p></div>
      <div class="page-header-actions">
        <button class="btn btn-primary" onclick="openNewOrder()">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          Novo Pedido
        </button>
      </div>
    </div>
    <div class="search-bar">
      <div class="search-input-wrap">
        <div class="search-icon"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg></div>
        <input class="search-input" id="ordersSearch" placeholder="Buscar por cliente ou #ID..." value="${escHtml(ordersFilter.search)}" oninput="debounce(() => { ordersFilter.search = this.value; ordersPage=1; loadOrders(); }, 350)()">
      </div>
      <select class="form-select" style="width:180px" onchange="ordersFilter.status=this.value; ordersPage=1; loadOrders()">
        ${statusOptions.map((v,i) => `<option value="${v}" ${v===ordersFilter.status?'selected':''}>${statusLabels[i]}</option>`).join('')}
      </select>
    </div>
    <div class="table-wrap">
      <table class="data-table">
        <thead><tr><th>#</th><th>Cliente</th><th>Status</th><th>Total</th><th>Data</th><th>Ações</th></tr></thead>
        <tbody>${rows}</tbody>
      </table>
    </div>
    <div class="pagination">
      <span>Página ${ordersPage}</span>
      <div class="pagination-pages">
        <button class="page-btn" onclick="if(ordersPage>1){ordersPage--;loadOrders();}" ${ordersPage<=1?'disabled':''}>‹</button>
        <button class="page-btn active">${ordersPage}</button>
        <button class="page-btn" onclick="ordersPage++;loadOrders();" ${(data.data||[]).length<20?'disabled':''}>›</button>
      </div>
    </div>`;
}

async function openOrder(id) {
  Modal.show('Detalhes do Pedido', loading(), '', 'modal-lg');
  const { ok, data } = await api(`orders/${id}`);
  if (!ok || !data.data) { Modal.show('Erro', '<p>Pedido não encontrado.</p>'); return; }
  const o = data.data;
  const itemsRows = (o.items || []).map(i => `
    <tr>
      <td>${escHtml(i.product_name)}</td>
      <td>${i.quantity}</td>
      <td>${fmtBRL(i.unit_price)}</td>
      <td class="text-gold font-bold">${fmtBRL(i.subtotal)}</td>
    </tr>`).join('');

  Modal.show(`Pedido #${id}`, `
    <div class="grid-2">
      <div>
        <div class="form-label">Cliente</div>
        <div style="font-size:1rem; font-weight:600; color:var(--text-warm); margin-bottom:4px">${escHtml(o.customer_name || '— Balcão')}</div>
        ${o.customer_phone ? `<div class="text-muted">${escHtml(o.customer_phone)}</div>` : ''}
        ${o.customer_address ? `<div class="text-muted" style="margin-top:4px; font-size:.85rem">${escHtml(o.customer_address)}</div>` : ''}
      </div>
      <div>
        <div class="form-label">Status</div>
        <div>${statusBadge(o.status)}</div>
        <div class="text-muted" style="margin-top:8px; font-size:.8rem">${fmtDate(o.created_at)}</div>
      </div>
    </div>
    ${o.notes ? `<div style="background:var(--bg-raised); border-radius:var(--radius); padding:12px; margin-top:16px; font-size:.875rem; color:var(--text-muted)">📝 ${escHtml(o.notes)}</div>` : ''}
    <div class="divider"></div>
    <div class="table-wrap" style="margin-bottom:16px">
      <table class="data-table">
        <thead><tr><th>Produto</th><th>Qtd</th><th>Preço Unit.</th><th>Subtotal</th></tr></thead>
        <tbody>${itemsRows}</tbody>
      </table>
    </div>
    <div style="display:flex; justify-content:flex-end; gap:16px; padding:12px 16px; background:var(--bg-raised); border-radius:var(--radius)">
      ${o.discount > 0 ? `<span class="text-muted">Desconto: <strong>${fmtBRL(o.discount)}</strong></span>` : ''}
      <span style="font-size:1.1rem; font-weight:800; color:var(--gold)">Total: ${fmtBRL(o.total)}</span>
    </div>`,
    `<button class="btn btn-outline no-print" onclick="printOrder(${id})">🖨 Imprimir</button>
     <button class="btn btn-ghost no-print" onclick="openStatusModal(${id},'${o.status}')">Atualizar Status</button>
     <button class="btn btn-ghost no-print" onclick="Modal.hide()">Fechar</button>`,
    'modal-lg'
  );
}

async function openStatusModal(orderId, current) {
  const statuses = ['rascunho','confirmado','em_preparo','pronto','entregue','cancelado'];
  const labels   = ['Rascunho','Confirmado','Em Preparo','Pronto','Entregue','Cancelado'];

  Modal.show('Atualizar Status', `
    <p style="color:var(--text-muted); margin-bottom:20px">Pedido #${orderId} — Status atual: ${statusBadge(current)}</p>
    <div class="form-group">
      <label class="form-label">Novo Status</label>
      <select class="form-select" id="newStatusSel">
        ${statuses.map((s,i) => `<option value="${s}" ${s===current?'selected':''}>${labels[i]}</option>`).join('')}
      </select>
    </div>`,
    `<button class="btn btn-ghost" onclick="Modal.hide()">Cancelar</button>
     <button class="btn btn-primary" onclick="doUpdateStatus(${orderId})">Salvar</button>`
  );
}

async function doUpdateStatus(orderId) {
  const status = document.getElementById('newStatusSel').value;
  const { ok, data } = await api(`orders/${orderId}/status`, 'PUT', { status });
  if (ok) {
    toast('Status atualizado!', 'success');
    Modal.hide();
    loadOrders();
  } else {
    toast(data.error || 'Erro ao atualizar.', 'error');
  }
}

async function openNewOrder() {
  const [prodRes, custRes] = await Promise.all([
    api('products?active=1'),
    api('customers'),
  ]);
  const products  = prodRes.data?.data || [];
  const customers = custRes.data?.data || [];

  Modal.show('Novo Pedido', `
    <div class="form-group">
      <label class="form-label">Cliente (opcional)</label>
      <select class="form-select" id="noCustomer">
        <option value="">— Venda Balcão —</option>
        ${customers.map(c => `<option value="${c.id}">${escHtml(c.name)} ${c.phone ? '- '+c.phone : ''}</option>`).join('')}
      </select>
    </div>
    <div class="form-group">
      <label class="form-label">Observações</label>
      <textarea class="form-textarea" id="noNotes" placeholder="Ponto da carne, restrições..."></textarea>
    </div>
    <div class="divider"></div>
    <div class="form-label" style="margin-bottom:10px">Itens do Pedido</div>
    <div id="orderItemsList"></div>
    <button class="btn btn-outline btn-sm" onclick="addOrderItem()">+ Adicionar Item</button>
    <div style="display:flex; justify-content:flex-end; margin-top:16px; font-size:1rem; font-weight:700; color:var(--gold)" id="orderTotal">Total: R$ 0,00</div>`,
    `<button class="btn btn-ghost" onclick="Modal.hide()">Cancelar</button>
     <button class="btn btn-primary" onclick="submitNewOrder()">Confirmar Pedido</button>`,
    'modal-lg'
  );

  window._orderProducts = products;
  window._orderItems    = [];
  addOrderItem();
}

function addOrderItem() {
  const list = document.getElementById('orderItemsList');
  if (!list) return;
  const idx = window._orderItems.length;
  window._orderItems.push({ product_id: '', unit_price: 0, quantity: 1 });

  const opts = (window._orderProducts || []).map(p =>
    `<option value="${p.id}" data-price="${p.price}">${escHtml(p.name)} — ${fmtBRL(p.price)}</option>`
  ).join('');

  const row = document.createElement('div');
  row.className = 'form-row';
  row.dataset.idx = idx;
  row.style.marginBottom = '10px';
  row.innerHTML = `
    <select class="form-select" onchange="selectOrderProduct(${idx}, this)">
      <option value="">— Selecione —</option>${opts}
    </select>
    <div style="display:flex; gap:8px; align-items:center">
      <input type="number" class="form-input" id="orderQty${idx}" min="1" value="1" style="width:80px" onchange="calcOrderTotal()">
      <span style="font-size:.85rem; color:var(--gold); min-width:80px; font-weight:700" id="orderSubtotal${idx}">R$ 0,00</span>
      <button class="btn btn-xs btn-danger" onclick="removeOrderItem(${idx}, this.closest('[data-idx]'))">✕</button>
    </div>`;
  list.appendChild(row);
}

function selectOrderProduct(idx, sel) {
  const opt   = sel.options[sel.selectedIndex];
  const price = parseFloat(opt.dataset.price || 0);
  window._orderItems[idx] = { product_id: sel.value, unit_price: price, quantity: 1 };
  calcOrderTotal();
}

function removeOrderItem(idx, el) {
  window._orderItems.splice(idx, 1);
  el?.remove();
  calcOrderTotal();
}

function calcOrderTotal() {
  let total = 0;
  window._orderItems.forEach((item, idx) => {
    const qty = parseInt(document.getElementById('orderQty' + idx)?.value || 1);
    item.quantity = qty;
    const sub = item.unit_price * qty;
    const subEl = document.getElementById('orderSubtotal' + idx);
    if (subEl) subEl.textContent = fmtBRL(sub);
    total += sub;
  });
  const totalEl = document.getElementById('orderTotal');
  if (totalEl) totalEl.textContent = 'Total: ' + fmtBRL(total);
}

async function submitNewOrder() {
  const items = window._orderItems.filter(i => i.product_id && i.quantity > 0);
  if (items.length === 0) { toast('Adicione ao menos um item.', 'error'); return; }

  const payload = {
    customer_id:  document.getElementById('noCustomer')?.value || null,
    notes:        document.getElementById('noNotes')?.value?.trim() || null,
    status:       'confirmado',
    items:        items.map(i => ({ product_id: i.product_id, quantity: i.quantity, unit_price: i.unit_price })),
  };

  const { ok, data } = await api('orders', 'POST', payload);
  if (ok) {
    toast('Pedido criado com sucesso!', 'success');
    Modal.hide();
    loadOrders();
  } else {
    toast(data.error || 'Erro ao criar pedido.', 'error');
  }
}

function printOrder(orderId) {
  const content = document.getElementById('globalModalBody').innerHTML;
  const win = window.open('', '_blank');
  win.document.write(`<html><head><title>Pedido #${orderId}</title>
    <link href="${BASE_URL}/assets/css/admin.css" rel="stylesheet">
    <style>body{background:#fff;color:#111;padding:32px;} .no-print{display:none}</style>
    </head><body>${content}</body></html>`);
  win.document.close();
  win.print();
}

async function loadProducts() {
  const [pRes, catRes] = await Promise.all([api('products'), api('products/categories')]);
  const products = pRes.data?.data || [];
  const cats     = catRes.data?.data || [];
  window._cats   = cats;

  const rows = products.map(p => `
    <tr>
      <td>
        <div class="flex flex-center gap-2">
          <div class="product-thumb">${p.image_url ? `<img src="${escHtml(p.image_url)}" alt="">` : '🍗'}</div>
          <div>
            <div class="font-bold">${escHtml(p.name)}</div>
            <div class="text-sm">${escHtml(p.category_name || '')}</div>
          </div>
        </div>
      </td>
      <td class="text-gold">${fmtBRL(p.price)}</td>
      <td>${stockBadge(p.stock_qty ?? 0, p.stock_min ?? 5)}</td>
      <td>${p.active ? '<span class="stock-ok">Ativo</span>' : '<span class="stock-zero">Inativo</span>'}</td>
      <td>${p.featured ? '⭐' : '—'}</td>
      <td>
        <div class="flex gap-2">
          <button class="btn btn-xs btn-outline" onclick="editProduct(${p.id})">Editar</button>
          <button class="btn btn-xs btn-danger" onclick="deleteProduct(${p.id})">Desativar</button>
        </div>
      </td>
    </tr>`).join('') || `<tr><td colspan="6">${emptyState('🏷','Nenhum produto cadastrado')}</td></tr>`;

  document.getElementById('pageContent').innerHTML = `
    <div class="page-header">
      <div class="page-header-left"><h1>Produtos</h1><p>${products.length} produto(s)</p></div>
      <div class="page-header-actions">
        <button class="btn btn-primary" onclick="openProductForm()">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          Novo Produto
        </button>
      </div>
    </div>
    <div class="table-wrap">
      <table class="data-table">
        <thead><tr><th>Produto</th><th>Preço</th><th>Estoque</th><th>Status</th><th>Destaque</th><th>Ações</th></tr></thead>
        <tbody>${rows}</tbody>
      </table>
    </div>`;
}

function openProductForm(id = null, existing = null) {
  const cats = (window._cats || []).map(c => `<option value="${c.id}" ${existing?.category_id==c.id?'selected':''}>${escHtml(c.name)}</option>`).join('');
  const e = existing || {};

  Modal.show(id ? 'Editar Produto' : 'Novo Produto', `
    <div class="form-row">
      <div class="form-group">
        <label class="form-label">Nome *</label>
        <input class="form-input" id="pfName" value="${escHtml(e.name||'')}">
      </div>
      <div class="form-group">
        <label class="form-label">Categoria *</label>
        <select class="form-select" id="pfCat"><option value="">Selecione</option>${cats}</select>
      </div>
    </div>
    <div class="form-group">
      <label class="form-label">Descrição</label>
      <textarea class="form-textarea" id="pfDesc">${escHtml(e.description||'')}</textarea>
    </div>
    <div class="form-row-3">
      <div class="form-group">
        <label class="form-label">Preço (R$) *</label>
        <input class="form-input" id="pfPrice" type="number" step="0.01" min="0" value="${e.price||''}">
      </div>
      <div class="form-group">
        <label class="form-label">Estoque Inicial</label>
        <input class="form-input" id="pfStock" type="number" min="0" value="${e.stock_qty||0}">
      </div>
      <div class="form-group">
        <label class="form-label">Mínimo Alerta</label>
        <input class="form-input" id="pfMin" type="number" min="0" value="${e.stock_min||5}">
      </div>
    </div>
    <div class="form-group">
      <label class="form-label">URL da Imagem</label>
      <input class="form-input" id="pfImg" type="url" placeholder="https://..." value="${escHtml(e.image_url||'')}">
    </div>
    <div class="form-row">
      <div class="form-group">
        <label class="form-label">Ativo</label>
        <select class="form-select" id="pfActive">
          <option value="1" ${e.active!=0?'selected':''}>Sim</option>
          <option value="0" ${e.active==0?'selected':''}>Não</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Destaque</label>
        <select class="form-select" id="pfFeatured">
          <option value="0" ${!e.featured?'selected':''}>Não</option>
          <option value="1" ${e.featured?'selected':''}>Sim</option>
        </select>
      </div>
    </div>`,
    `<button class="btn btn-ghost" onclick="Modal.hide()">Cancelar</button>
     <button class="btn btn-primary" onclick="submitProduct(${id || 'null'})">Salvar</button>`
  );
}

async function editProduct(id) {
  const { ok, data } = await api(`products/${id}`);
  if (!ok) { toast('Erro ao carregar produto.', 'error'); return; }
  openProductForm(id, data.data);
}

async function submitProduct(id) {
  const payload = {
    name:         document.getElementById('pfName')?.value?.trim(),
    category_id:  document.getElementById('pfCat')?.value,
    description:  document.getElementById('pfDesc')?.value?.trim(),
    price:        parseFloat(document.getElementById('pfPrice')?.value),
    stock_qty:    parseInt(document.getElementById('pfStock')?.value || 0),
    stock_min:    parseInt(document.getElementById('pfMin')?.value || 5),
    image_url:    document.getElementById('pfImg')?.value?.trim(),
    active:       parseInt(document.getElementById('pfActive')?.value),
    featured:     parseInt(document.getElementById('pfFeatured')?.value),
  };

  if (!payload.name || !payload.category_id || !payload.price) {
    toast('Preencha nome, categoria e preço.', 'error'); return;
  }

  const { ok, data } = id
    ? await api(`products/${id}`, 'PUT', payload)
    : await api('products', 'POST', payload);

  if (ok) {
    toast(id ? 'Produto atualizado!' : 'Produto criado!', 'success');
    Modal.hide();
    loadProducts();
  } else {
    toast(data.error || 'Erro ao salvar.', 'error');
  }
}

async function deleteProduct(id) {
  if (!(await Confirm.show('Desativar este produto?'))) return;
  const { ok, data } = await api(`products/${id}`, 'DELETE');
  if (ok) { toast('Produto desativado.', 'success'); loadProducts(); }
  else toast(data.error || 'Erro.', 'error');
}

async function loadStock() {
  const { ok, data } = await api('stock');
  const items = data?.data || [];

  const rows = items.map(s => `
    <tr>
      <td>
        <div class="font-bold">${escHtml(s.product_name)}</div>
        <div class="text-sm">${escHtml(s.category_name||'')}</div>
      </td>
      <td>${stockBadge(s.quantity, s.min_quantity)} <span class="text-muted" style="font-size:.8rem">${s.unit}</span></td>
      <td class="text-muted">${s.min_quantity}</td>
      <td class="text-muted">${fmtDate(s.updated_at)}</td>
      <td>
        <div class="flex gap-2">
          <button class="btn btn-xs btn-success" onclick="openStockAdjust(${s.product_id}, 'in', '${escHtml(s.product_name)}')">+ Entrada</button>
          <button class="btn btn-xs btn-danger"  onclick="openStockAdjust(${s.product_id}, 'out', '${escHtml(s.product_name)}')">− Saída</button>
          <button class="btn btn-xs btn-outline" onclick="openStockAdjust(${s.product_id}, 'adjustment', '${escHtml(s.product_name)}')">Ajustar</button>
          <button class="btn btn-xs btn-ghost"   onclick="viewMovements(${s.product_id}, '${escHtml(s.product_name)}')">Histórico</button>
        </div>
      </td>
    </tr>`).join('') || `<tr><td colspan="5">${emptyState('📦','Sem estoque registrado')}</td></tr>`;

  const lowCount = items.filter(s => s.quantity <= s.min_quantity).length;

  document.getElementById('pageContent').innerHTML = `
    <div class="page-header">
      <div class="page-header-left"><h1>Controle de Estoque</h1>
        <p>${items.length} produto(s) cadastrado(s) ${lowCount > 0 ? `— <span class="text-warn">${lowCount} abaixo do mínimo</span>` : ''}</p>
      </div>
    </div>
    <div class="table-wrap">
      <table class="data-table">
        <thead><tr><th>Produto</th><th>Estoque Atual</th><th>Mínimo</th><th>Última Atualização</th><th>Ações</th></tr></thead>
        <tbody>${rows}</tbody>
      </table>
    </div>`;
}

function openStockAdjust(productId, type, name) {
  const typeLabels = { in: 'Entrada', out: 'Saída', adjustment: 'Ajuste (absoluto)' };
  Modal.show(`${typeLabels[type]} — ${name}`, `
    <div class="form-group">
      <label class="form-label">Quantidade *</label>
      <input class="form-input" id="saQty" type="number" min="1" value="1" autofocus>
    </div>
    <div class="form-group">
      <label class="form-label">Motivo</label>
      <input class="form-input" id="saReason" placeholder="${typeLabels[type]} manual...">
    </div>`,
    `<button class="btn btn-ghost" onclick="Modal.hide()">Cancelar</button>
     <button class="btn btn-primary" onclick="submitStockAdjust(${productId},'${type}')">Confirmar</button>`
  );
}

async function submitStockAdjust(productId, type) {
  const qty    = parseInt(document.getElementById('saQty')?.value);
  const reason = document.getElementById('saReason')?.value?.trim();
  if (!qty || qty < 1) { toast('Quantidade inválida.', 'error'); return; }

  const { ok, data } = await api(`stock/${productId}/adjust`, 'POST', { type, quantity: qty, reason });
  if (ok) { toast('Estoque atualizado!', 'success'); Modal.hide(); loadStock(); }
  else toast(data.error || 'Erro.', 'error');
}

async function viewMovements(productId, name) {
  Modal.show(`Movimentações — ${name}`, loading(), '', 'modal-lg');
  const { data } = await api(`stock/${productId}/movements`);
  const rows = (data.data || []).map(m => `
    <tr>
      <td>${fmtDate(m.created_at)}</td>
      <td>
        <span class="status-badge ${m.type==='in'?'status-entregue':m.type==='out'?'status-cancelado':m.type==='return'?'status-confirmado':'status-em_preparo'}">
          ${{in:'Entrada',out:'Saída',adjustment:'Ajuste',return:'Devolução'}[m.type]||m.type}
        </span>
      </td>
      <td class="${m.type==='in'||m.type==='return'?'text-green':'text-warn'} font-bold">
        ${m.type==='in'||m.type==='return'?'+':'-'}${m.quantity}
      </td>
      <td class="text-muted">${escHtml(m.reason||'—')}</td>
      <td class="text-muted">${escHtml(m.admin_name||'Sistema')}</td>
    </tr>`).join('') || `<tr><td colspan="5">${emptyState('📦','Sem movimentações')}</td></tr>`;

  Modal.show(`Movimentações — ${name}`, `
    <div class="table-wrap">
      <table class="data-table">
        <thead><tr><th>Data</th><th>Tipo</th><th>Qtd</th><th>Motivo</th><th>Responsável</th></tr></thead>
        <tbody>${rows}</tbody>
      </table>
    </div>`, '<button class="btn btn-ghost" onclick="Modal.hide()">Fechar</button>', 'modal-lg');
}

let custPage = 1, custSearch = '';

async function loadCustomers() {
  const { data } = await api(`customers?page=${custPage}&search=${encodeURIComponent(custSearch)}`);
  const customers = data?.data || [];

  const rows = customers.map(c => `
    <tr>
      <td>
        <div class="font-bold">${escHtml(c.name)}</div>
        <div class="text-sm">${escHtml(c.email||'')}</div>
      </td>
      <td class="text-muted">${escHtml(c.phone||'—')}</td>
      <td class="text-muted">${fmtDate(c.created_at)}</td>
      <td>
        <div class="flex gap-2">
          <button class="btn btn-xs btn-outline" onclick="viewCustomer(${c.id})">Ver</button>
          <button class="btn btn-xs btn-gold" onclick="openUpsellFor(${c.id}, '${escHtml(c.name)}')">QR Upsell</button>
          <button class="btn btn-xs btn-ghost" onclick="editCustomer(${c.id})">Editar</button>
        </div>
      </td>
    </tr>`).join('') || `<tr><td colspan="4">${emptyState('👥','Nenhum cliente cadastrado')}</td></tr>`;

  document.getElementById('pageContent').innerHTML = `
    <div class="page-header">
      <div class="page-header-left"><h1>Clientes</h1><p>Total: ${data?.total||0}</p></div>
      <div class="page-header-actions">
        <button class="btn btn-primary" onclick="openCustomerForm()">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          Novo Cliente
        </button>
      </div>
    </div>
    <div class="search-bar">
      <div class="search-input-wrap">
        <div class="search-icon"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg></div>
        <input class="search-input" id="custSearchInput" placeholder="Buscar por nome, e-mail ou telefone..." value="${escHtml(custSearch)}" oninput="debounce(() => { custSearch=this.value; custPage=1; loadCustomers(); }, 350)()">
      </div>
    </div>
    <div class="table-wrap">
      <table class="data-table">
        <thead><tr><th>Cliente</th><th>Telefone</th><th>Cadastro</th><th>Ações</th></tr></thead>
        <tbody>${rows}</tbody>
      </table>
    </div>`;
}

async function viewCustomer(id) {
  Modal.show('Perfil do Cliente', loading(), '', 'modal-lg');
  const { data } = await api(`customers/${id}`);
  const c = data?.data;
  if (!c) { Modal.show('Erro', '<p>Cliente não encontrado.</p>'); return; }

  const ordersRows = (c.orders || []).map(o => `
    <tr>
      <td>#${o.id}</td>
      <td>${statusBadge(o.status)}</td>
      <td>${o.items_count} item(s)</td>
      <td class="text-gold">${fmtBRL(o.total)}</td>
      <td class="text-muted">${fmtDate(o.created_at)}</td>
    </tr>`).join('') || `<tr><td colspan="5" class="text-muted" style="padding:20px;text-align:center">Sem pedidos</td></tr>`;

  Modal.show(c.name, `
    <div class="grid-2" style="margin-bottom:20px">
      <div>
        <div class="form-label">Contato</div>
        <div>${escHtml(c.phone||'—')}</div>
        <div class="text-muted">${escHtml(c.email||'—')}</div>
      </div>
      <div>
        <div class="form-label">Total Gasto</div>
        <div style="font-size:1.25rem; font-weight:800; color:var(--gold)">${fmtBRL(c.total_spent)}</div>
        <div class="text-muted">${(c.orders||[]).length} pedidos</div>
      </div>
    </div>
    ${c.address ? `<div class="form-label">Endereço</div><div class="text-muted" style="margin-bottom:16px">${escHtml(c.address)}</div>` : ''}
    <div class="divider"></div>
    <div class="form-label" style="margin-bottom:10px">Histórico de Pedidos</div>
    <div class="table-wrap">
      <table class="data-table">
        <thead><tr><th>#</th><th>Status</th><th>Itens</th><th>Total</th><th>Data</th></tr></thead>
        <tbody>${ordersRows}</tbody>
      </table>
    </div>`,
    `<button class="btn btn-gold" onclick="openUpsellFor(${c.id},'${escHtml(c.name)}')">Gerar Upsell</button>
     <button class="btn btn-ghost" onclick="editCustomer(${c.id})">Editar</button>
     <button class="btn btn-ghost" onclick="Modal.hide()">Fechar</button>`,
    'modal-lg'
  );
}

function openCustomerForm(id = null, existing = null) {
  const e = existing || {};
  Modal.show(id ? 'Editar Cliente' : 'Novo Cliente', `
    <div class="form-row">
      <div class="form-group">
        <label class="form-label">Nome *</label>
        <input class="form-input" id="cfName" value="${escHtml(e.name||'')}">
      </div>
      <div class="form-group">
        <label class="form-label">Telefone</label>
        <input class="form-input" id="cfPhone" value="${escHtml(e.phone||'')}" placeholder="(11) 99999-9999">
      </div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label class="form-label">E-mail</label>
        <input class="form-input" id="cfEmail" type="email" value="${escHtml(e.email||'')}">
      </div>
      <div class="form-group">
        <label class="form-label">CPF</label>
        <input class="form-input" id="cfCpf" value="${escHtml(e.cpf||'')}" placeholder="000.000.000-00">
      </div>
    </div>
    <div class="form-group">
      <label class="form-label">Endereço</label>
      <textarea class="form-textarea" id="cfAddress">${escHtml(e.address||'')}</textarea>
    </div>
    <div class="form-group">
      <label class="form-label">Observações</label>
      <textarea class="form-textarea" id="cfNotes">${escHtml(e.notes||'')}</textarea>
    </div>`,
    `<button class="btn btn-ghost" onclick="Modal.hide()">Cancelar</button>
     <button class="btn btn-primary" onclick="submitCustomer(${id||'null'})">Salvar</button>`
  );
}

async function editCustomer(id) {
  const { data } = await api(`customers/${id}`);
  openCustomerForm(id, data?.data);
}

async function submitCustomer(id) {
  const payload = {
    name:    document.getElementById('cfName')?.value?.trim(),
    phone:   document.getElementById('cfPhone')?.value?.trim(),
    email:   document.getElementById('cfEmail')?.value?.trim(),
    cpf:     document.getElementById('cfCpf')?.value?.trim(),
    address: document.getElementById('cfAddress')?.value?.trim(),
    notes:   document.getElementById('cfNotes')?.value?.trim(),
  };
  if (!payload.name) { toast('Nome é obrigatório.', 'error'); return; }

  const { ok, data } = id
    ? await api(`customers/${id}`, 'PUT', payload)
    : await api('customers', 'POST', payload);

  if (ok) { toast(id ? 'Cliente atualizado!' : 'Cliente criado!', 'success'); Modal.hide(); loadCustomers(); }
  else toast(data.error || 'Erro ao salvar.', 'error');
}

async function loadUpsell() {
  const [tokRes, custRes, prodRes] = await Promise.all([
    api('upsell'),
    api('customers'),
    api('products?active=1'),
  ]);
  const tokens    = tokRes.data?.data    || [];
  const customers = custRes.data?.data   || [];
  const products  = prodRes.data?.data   || [];
  window._upsellCustomers = customers;
  window._upsellProducts  = products;

  const rows = tokens.map(t => {
    const used    = !!t.used_at;
    const expired = !used && new Date(t.expires_at) < new Date();
    const status  = used ? '✓ Usado' : expired ? '⌛ Expirado' : '⬤ Ativo';
    const stCls   = used ? 'text-muted' : expired ? 'text-warn' : 'text-green';
    return `
      <tr>
        <td class="font-bold">${escHtml(t.customer_name)}</td>
        <td><span class="text-gold font-bold">${t.discount_percent}%</span> ${t.product_name ? `— ${escHtml(t.product_name)}` : ''}</td>
        <td class="${stCls}" style="font-size:.8rem">${status}</td>
        <td class="text-muted">${fmtDateOnly(t.expires_at)}</td>
        <td>${fmtDate(t.created_at)}</td>
      </tr>`;
  }).join('') || `<tr><td colspan="5">${emptyState('📲','Nenhum token gerado ainda')}</td></tr>`;

  document.getElementById('pageContent').innerHTML = `
    <div class="page-header">
      <div class="page-header-left">
        <h1>Upsell / QR Code</h1>
        <p>Crie ofertas personalizadas com QR Code para seus clientes</p>
      </div>
      <div class="page-header-actions">
        <button class="btn btn-primary" onclick="openUpsellForm()">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          Gerar Novo Token
        </button>
      </div>
    </div>
    <div class="table-wrap">
      <table class="data-table">
        <thead><tr><th>Cliente</th><th>Oferta</th><th>Status</th><th>Expira</th><th>Criado</th></tr></thead>
        <tbody>${rows}</tbody>
      </table>
    </div>`;
}

function openUpsellFor(customerId, customerName) {
  Modal.hide();
  setTimeout(() => openUpsellForm(customerId, customerName), 150);
}

function openUpsellForm(presetCustomer = null, presetName = null) {
  const customers = window._upsellCustomers || [];
  const products  = window._upsellProducts  || [];

  Modal.show('Gerar Token de Upsell', `
    <div class="form-group">
      <label class="form-label">Cliente *</label>
      <select class="form-select" id="ufCustomer">
        <option value="">Selecione um cliente</option>
        ${customers.map(c => `<option value="${c.id}" ${presetCustomer==c.id?'selected':''}>${escHtml(c.name)} ${c.phone?'- '+c.phone:''}</option>`).join('')}
      </select>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label class="form-label">Desconto (%)</label>
        <input class="form-input" id="ufDiscount" type="number" min="1" max="100" value="10">
      </div>
      <div class="form-group">
        <label class="form-label">Válido por (dias)</label>
        <input class="form-input" id="ufDays" type="number" min="1" max="60" value="7">
      </div>
    </div>
    <div class="form-group">
      <label class="form-label">Produto em Destaque (opcional)</label>
      <select class="form-select" id="ufProduct">
        <option value="">Desconto em qualquer produto</option>
        ${products.map(p => `<option value="${p.id}">${escHtml(p.name)} — ${fmtBRL(p.price)}</option>`).join('')}
      </select>
    </div>
    <div class="form-group">
      <label class="form-label">Mensagem Personalizada</label>
      <textarea class="form-textarea" id="ufMessage" placeholder="Sentimos sua falta! Volte e aproveite..." rows="3"></textarea>
    </div>`,
    `<button class="btn btn-ghost" onclick="Modal.hide()">Cancelar</button>
     <button class="btn btn-primary" onclick="submitUpsell()">Gerar QR Code</button>`
  );
}

async function submitUpsell() {
  const customerId = document.getElementById('ufCustomer')?.value;
  if (!customerId) { toast('Selecione um cliente.', 'error'); return; }

  const payload = {
    customer_id:      parseInt(customerId),
    discount_percent: parseInt(document.getElementById('ufDiscount')?.value || 10),
    expires_days:     parseInt(document.getElementById('ufDays')?.value || 7),
    product_id:       document.getElementById('ufProduct')?.value || null,
    message:          document.getElementById('ufMessage')?.value?.trim() || null,
  };

  const { ok, data } = await api('upsell', 'POST', payload);
  if (!ok) { toast(data.error || 'Erro ao gerar token.', 'error'); return; }

  Modal.show('Token Gerado com Sucesso!', `
    <div style="text-align:center; margin-bottom:24px">
      <div style="background:#fff; border-radius:12px; padding:16px; display:inline-block; margin-bottom:16px">
        <img src="${escHtml(data.qr_url)}" width="200" height="200" alt="QR Code" style="display:block; border-radius:6px">
      </div>
      <div class="text-gold" style="font-size:1.1rem; font-weight:700; margin-bottom:6px">Desconto: ${payload.discount_percent}%</div>
      <div class="text-muted" style="font-size:.85rem">Expira em: ${fmtDate(data.expires_at)}</div>
    </div>
    <div style="background:var(--bg-raised); border-radius:var(--radius); padding:14px; margin-bottom:16px">
      <div class="form-label" style="margin-bottom:6px">Link da Oferta</div>
      <div style="display:flex; gap:8px; align-items:center">
        <input class="form-input" value="${escHtml(data.link)}" id="upsellLinkInput" readonly style="font-size:.8rem">
        <button class="btn btn-xs btn-outline" onclick="copyUpsellLink()">Copiar</button>
      </div>
    </div>
    <div style="background:var(--bg-raised); border-radius:var(--radius); padding:14px">
      <div class="form-label" style="margin-bottom:6px">Mensagem WhatsApp</div>
      <textarea class="form-textarea" readonly rows="6" style="font-size:.8rem">${escHtml(data.whatsapp)}</textarea>
    </div>`,
    `<a href="${escHtml(getWhatsAppUrl(data))}" target="_blank" rel="noopener" class="btn btn-success">
       <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
         <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413z"/>
       </svg>
       Enviar WhatsApp
     </a>
     <button class="btn btn-ghost" onclick="Modal.hide(); loadUpsell()">Fechar</button>`,
    'modal-lg'
  );
}

function copyUpsellLink() {
  const inp = document.getElementById('upsellLinkInput');
  if (inp) { inp.select(); document.execCommand('copy'); toast('Link copiado!', 'success'); }
}

function getWhatsAppUrl(data) {
  const phone = APP_PHONE.replace(/\D/g,'');
  return `https://wa.me/55${phone}?text=${encodeURIComponent(data.whatsapp)}`;
}

async function loadRaffles() {
  const { data } = await api('raffles');
  const raffles = data?.data || [];

  const rows = raffles.map(r => `
    <tr>
      <td>${statusBadge(r.type === 'weekly' ? 'confirmado' : 'pronto')} <span style="margin-left:4px">${r.type==='weekly'?'Semanal':'Mensal'}</span></td>
      <td class="font-bold">${escHtml(r.period_label)}</td>
      <td class="text-gold font-bold">${escHtml(r.winner_name || '—')}</td>
      <td class="text-muted">${r.participants_count} participantes</td>
      <td class="text-muted">${fmtDate(r.drawn_at)}</td>
      <td>
        <button class="btn btn-xs btn-outline" onclick="viewRaffleParticipants(${r.id}, '${escHtml(r.period_label)}')">Participantes</button>
      </td>
    </tr>`).join('') || `<tr><td colspan="6">${emptyState('🎰','Nenhum sorteio realizado ainda')}</td></tr>`;

  document.getElementById('pageContent').innerHTML = `
    <div class="page-header">
      <div class="page-header-left">
        <h1>Sorteios</h1>
        <p>Sorteio automático baseado em pedidos entregues</p>
      </div>
      <div class="page-header-actions">
        <button class="btn btn-outline" onclick="openRaffleDraw('weekly')">🎯 Sortear Semanal</button>
        <button class="btn btn-primary" onclick="openRaffleDraw('monthly')">🏆 Sortear Mensal</button>
      </div>
    </div>
    <div class="grid-2" style="margin-bottom:20px">
      <div class="card" style="text-align:center; padding:32px">
        <div style="font-size:2rem; margin-bottom:8px">🎯</div>
        <div style="font-size:.875rem; color:var(--text-muted); margin-bottom:4px">Sorteio Semanal</div>
        <div style="font-size:.85rem; color:var(--text-muted)">Pedidos entregues na semana anterior</div>
        <button class="btn btn-outline btn-sm" style="margin-top:16px" onclick="openRaffleDraw('weekly')">Realizar Sorteio</button>
      </div>
      <div class="card" style="text-align:center; padding:32px">
        <div style="font-size:2rem; margin-bottom:8px">🏆</div>
        <div style="font-size:.875rem; color:var(--text-muted); margin-bottom:4px">Sorteio Mensal</div>
        <div style="font-size:.85rem; color:var(--text-muted)">Pedidos entregues no mês anterior</div>
        <button class="btn btn-primary btn-sm" style="margin-top:16px" onclick="openRaffleDraw('monthly')">Realizar Sorteio</button>
      </div>
    </div>
    <div class="card">
      <div class="card-header"><span class="card-title">Histórico de Sorteios</span></div>
      <div class="table-wrap">
        <table class="data-table">
          <thead><tr><th>Tipo</th><th>Período</th><th>Vencedor</th><th>Participantes</th><th>Realizado em</th><th></th></tr></thead>
          <tbody>${rows}</tbody>
        </table>
      </div>
    </div>`;
}

function openRaffleDraw(type) {
  const label = type === 'weekly' ? 'Semanal' : 'Mensal';
  Modal.show(`Sorteio ${label}`, `
    <p style="color:var(--text-muted); margin-bottom:20px">
      Serão elegíveis todos os clientes com pedidos <strong>entregues</strong> no período.
    </p>
    <div class="form-group">
      <label class="form-label">Data de Referência (opcional — para teste)</label>
      <input class="form-input" id="rafSimDate" type="date" placeholder="Deixe vazio para usar data atual">
      <small class="text-muted" style="margin-top:4px; display:block">Preencha para simular outra data</small>
    </div>`,
    `<button class="btn btn-ghost" onclick="Modal.hide()">Cancelar</button>
     <button class="btn btn-primary" onclick="doRaffleDraw('${type}')">🎰 Sortear Agora</button>`
  );
}

async function doRaffleDraw(type) {
  const simDate = document.getElementById('rafSimDate')?.value || null;
  const { ok, data } = await api(`raffles/${type}`, 'POST', { simulated_date: simDate });

  if (ok && data.success) {
    Modal.show('🎉 Vencedor do Sorteio!', `
      <div style="text-align:center; padding:24px">
        <div style="font-size:4rem; margin-bottom:16px">🏆</div>
        <div style="font-size:1.75rem; font-weight:800; color:var(--gold); margin-bottom:8px">${escHtml(data.data.winner)}</div>
        <div class="text-muted" style="margin-bottom:16px">Pedido #${data.data.winner_order_id}</div>
        <div style="background:var(--bg-raised); border-radius:var(--radius); padding:16px; display:inline-block">
          <div class="text-muted" style="font-size:.8rem">Período</div>
          <div style="font-weight:700">${escHtml(data.data.period)}</div>
          <div class="text-muted" style="font-size:.8rem; margin-top:8px">Total de participantes</div>
          <div style="font-weight:700">${data.data.participants_count}</div>
        </div>
      </div>`,
      `<button class="btn btn-primary" onclick="Modal.hide(); loadRaffles()">Fechar</button>`,
      'modal-sm'
    );
  } else {
    toast(data.message || data.error || 'Erro ao realizar sorteio.', 'warning');
    Modal.hide();
  }
}

async function viewRaffleParticipants(raffleId, period) {
  Modal.show(`Participantes — ${period}`, loading(), '', 'modal-lg');
  const { data } = await api(`raffles/${raffleId}/participants`);
  const rows = (data.data || []).map(p => `
    <tr>
      <td class="font-bold">${escHtml(p.customer_name)}</td>
      <td class="text-muted">${escHtml(p.customer_phone||'—')}</td>
      <td class="font-bold">#${p.order_id}</td>
      <td class="text-gold">${fmtBRL(p.order_total)}</td>
    </tr>`).join('') || `<tr><td colspan="4">${emptyState('👥','Sem participantes registrados')}</td></tr>`;

  Modal.show(`Participantes — ${period}`, `
    <div class="table-wrap">
      <table class="data-table">
        <thead><tr><th>Cliente</th><th>Telefone</th><th>Pedido</th><th>Valor</th></tr></thead>
        <tbody>${rows}</tbody>
      </table>
    </div>`, '<button class="btn btn-ghost" onclick="Modal.hide()">Fechar</button>', 'modal-lg');
}

async function loadReports() {
  const today = new Date().toISOString().split('T')[0];
  const firstDay = today.slice(0, 8) + '01';

  document.getElementById('pageContent').innerHTML = `
    <div class="page-header">
      <div class="page-header-left"><h1>Relatórios</h1><p>Análise de vendas e desempenho</p></div>
    </div>
    <div class="card" style="margin-bottom:20px">
      <div class="card-body">
        <div class="flex flex-center gap-3" style="flex-wrap:wrap">
          <div class="form-group" style="margin:0">
            <label class="form-label">De</label>
            <input class="form-input" id="rpFrom" type="date" value="${firstDay}">
          </div>
          <div class="form-group" style="margin:0">
            <label class="form-label">Até</label>
            <input class="form-input" id="rpTo" type="date" value="${today}">
          </div>
          <div style="margin-top:20px; display:flex; gap:8px">
            <button class="btn btn-primary" onclick="fetchReport()">Gerar Relatório</button>
            <button class="btn btn-outline" onclick="exportReport('csv')">⬇ CSV</button>
            <button class="btn btn-outline" onclick="exportReport('json')">⬇ JSON</button>
          </div>
        </div>
      </div>
    </div>
    <div id="reportContent">${emptyState('📊','Selecione o período e clique em Gerar Relatório')}</div>`;
}

async function fetchReport() {
  const from = document.getElementById('rpFrom')?.value;
  const to   = document.getElementById('rpTo')?.value;
  if (!from || !to) { toast('Selecione o período.', 'error'); return; }

  document.getElementById('reportContent').innerHTML = loading('Gerando relatório...');
  const { ok, data } = await api(`reports/sales?from=${from}&to=${to}`);
  if (!ok) { toast('Erro ao gerar relatório.', 'error'); return; }

  const d    = data;
  const days = d.by_day || [];
  const chartLabels  = days.map(r => new Date(r.day+'T12:00:00').toLocaleDateString('pt-BR', { day:'2-digit', month:'2-digit' }));
  const chartRevenue = days.map(r => parseFloat(r.total_revenue));

  const productRows = (d.products || []).map((p, i) => `
    <tr>
      <td>${i+1}º</td>
      <td class="font-bold">${escHtml(p.name)}</td>
      <td>${p.qty_sold}</td>
      <td class="text-gold font-bold">${fmtBRL(p.revenue)}</td>
    </tr>`).join('') || `<tr><td colspan="4" class="text-muted" style="padding:20px;text-align:center">Sem dados</td></tr>`;

  const customerRows = (d.customers || []).map((c, i) => `
    <tr>
      <td>${i+1}º</td>
      <td class="font-bold">${escHtml(c.name)}</td>
      <td class="text-muted">${escHtml(c.phone||'—')}</td>
      <td>${c.total_orders}</td>
      <td class="text-gold font-bold">${fmtBRL(c.total_spent)}</td>
    </tr>`).join('') || `<tr><td colspan="5" class="text-muted" style="padding:20px;text-align:center">Sem dados</td></tr>`;

  document.getElementById('reportContent').innerHTML = `
    <div class="kpi-grid" style="margin-bottom:20px">
      <div class="kpi-card kpi-accent-gold">
        <div class="kpi-label">Receita Total</div>
        <div class="kpi-value">${fmtBRL(d.summary?.total_revenue)}</div>
        <div class="kpi-sub">Período selecionado</div>
      </div>
      <div class="kpi-card kpi-accent-wine">
        <div class="kpi-label">Total de Pedidos</div>
        <div class="kpi-value">${d.summary?.total_orders || 0}</div>
        <div class="kpi-sub">Não cancelados</div>
      </div>
      <div class="kpi-card kpi-accent-green">
        <div class="kpi-label">Ticket Médio</div>
        <div class="kpi-value">${fmtBRL(d.summary?.avg_ticket)}</div>
        <div class="kpi-sub">Por pedido</div>
      </div>
      <div class="kpi-card kpi-accent-ember">
        <div class="kpi-label">Clientes Únicos</div>
        <div class="kpi-value">${d.summary?.unique_customers || 0}</div>
        <div class="kpi-sub">Compradores no período</div>
      </div>
    </div>
    <div class="card" style="margin-bottom:20px">
      <div class="card-header"><span class="card-title">Receita por Dia</span></div>
      <div class="card-body">
        <div class="chart-wrap" style="height:260px"><canvas id="rpChartRevenue" style="width:100%;height:100%"></canvas></div>
      </div>
    </div>
    <div class="grid-2">
      <div class="card">
        <div class="card-header"><span class="card-title">🏆 Ranking de Produtos</span></div>
        <div class="table-wrap">
          <table class="data-table">
            <thead><tr><th>#</th><th>Produto</th><th>Qtd</th><th>Receita</th></tr></thead>
            <tbody>${productRows}</tbody>
          </table>
        </div>
      </div>
      <div class="card">
        <div class="card-header"><span class="card-title">👑 Top Clientes</span></div>
        <div class="table-wrap">
          <table class="data-table">
            <thead><tr><th>#</th><th>Cliente</th><th>Tel</th><th>Pedidos</th><th>Total</th></tr></thead>
            <tbody>${customerRows}</tbody>
          </table>
        </div>
      </div>
    </div>`;

  requestAnimationFrame(() => {
    if (chartLabels.length > 0) {
      Charts.bar('rpChartRevenue', chartLabels,
        [{ label: 'Receita', data: chartRevenue, color: '#D48A1C' }],
        { formatY: v => 'R$' + v.toLocaleString('pt-BR') }
      );
    }
  });
}

function exportReport(format) {
  const from = document.getElementById('rpFrom')?.value;
  const to   = document.getElementById('rpTo')?.value;
  if (!from || !to) { toast('Selecione o período.', 'error'); return; }

  if (format === 'json') {
    window.open(`${BASE_URL}/api.php?route=reports/export-json&from=${from}&to=${to}`, '_blank');
  } else {
    const type = 'orders';
    window.open(`${BASE_URL}/api.php?route=reports/export-csv&from=${from}&to=${to}&type=${type}`, '_blank');
  }
}

const _debounceMap = {};
function debounce(fn, delay) {
  return function (...args) {
    const key = fn.toString().slice(0, 60);
    clearTimeout(_debounceMap[key]);
    _debounceMap[key] = setTimeout(() => fn.apply(this, args), delay);
  };
}

document.addEventListener('DOMContentLoaded', async () => {
  document.getElementById('togglePass')?.addEventListener('click', () => {
    const inp = document.getElementById('loginPassword');
    inp.type  = inp.type === 'password' ? 'text' : 'password';
  });

  document.getElementById('loginForm')?.addEventListener('submit', e => {
    e.preventDefault();
    tryLogin();
  });

  if (typeof INITIAL_AUTH !== 'undefined' && INITIAL_AUTH) {
    const { ok, data } = await api('auth/me');
    if (ok && data.success) {
      currentAdmin = data.admin;
      initApp(data.admin);
    } else {
      document.getElementById('loginScreen').style.display = 'flex';
      document.getElementById('appShell').style.display   = 'none';
    }
  }
});
