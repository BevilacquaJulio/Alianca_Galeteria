const API = BASE_URL + '/api.php?route=';

const Cart = {
  items: JSON.parse(localStorage.getItem('ag_cart') || '[]'),

  save() {
    localStorage.setItem('ag_cart', JSON.stringify(this.items));
    this.render();
  },

  add(product) {
    const existing = this.items.find(i => i.id === product.id);
    if (existing) {
      existing.qty += 1;
    } else {
      this.items.push({ id: product.id, name: product.name, price: parseFloat(product.price), qty: 1, image_url: product.image_url || '' });
    }
    this.save();
    showToast('Adicionado ao carrinho!', 'success');
  },

  remove(id) {
    this.items = this.items.filter(i => i.id !== id);
    this.save();
  },

  update(id, qty) {
    const item = this.items.find(i => i.id === id);
    if (item) {
      item.qty = Math.max(1, qty);
      this.save();
    }
  },

  total() {
    return this.items.reduce((sum, i) => sum + i.price * i.qty, 0);
  },

  count() {
    return this.items.reduce((sum, i) => sum + i.qty, 0);
  },

  clear() {
    this.items = [];
    this.save();
  },

  render() {
    const badge = document.getElementById('cartBadge');
    const count = this.count();
    if (badge) {
      badge.textContent = count;
      badge.classList.toggle('show', count > 0);
    }

    const body = document.getElementById('cartBody');
    const footer = document.getElementById('cartFooter');
    const total = document.getElementById('cartTotal');

    if (!body) return;

    if (this.items.length === 0) {
      body.innerHTML = `
        <div class="cart-empty">
          <div class="cart-empty-icon">🛒</div>
          <p>Seu carrinho está vazio</p>
          <small>Adicione itens do cardápio</small>
        </div>`;
      if (footer) footer.style.display = 'none';
      return;
    }

    body.innerHTML = this.items.map(item => `
      <div class="cart-item" data-id="${item.id}">
        <div class="cart-item-img">
          ${item.image_url ? `<img src="${escHtml(item.image_url)}" alt="">` : '🍗'}
        </div>
        <div class="cart-item-info">
          <div class="cart-item-name">${escHtml(item.name)}</div>
          <div class="cart-item-price">${fmtBRL(item.price)}</div>
          <div class="cart-item-controls">
            <button class="qty-btn" onclick="Cart.update(${item.id}, ${item.qty - 1})">−</button>
            <span class="qty-value">${item.qty}</span>
            <button class="qty-btn" onclick="Cart.update(${item.id}, ${item.qty + 1})">+</button>
            <button class="btn-remove-item" onclick="Cart.remove(${item.id})">Remover</button>
          </div>
        </div>
      </div>`).join('');

    if (footer) footer.style.display = '';
    if (total) total.textContent = fmtBRL(this.total());
  }
};

let allProducts = [];
let currentCategory = '';

async function loadProducts() {
  try {
    const res = await fetch(API + 'products/public');
    const data = await res.json();
    if (!data.success) throw new Error();

    allProducts = data.data;
    renderCategoryFilters(data.categories);
    renderProducts(allProducts);
  } catch {
    document.getElementById('productsGrid').innerHTML = `
      <div class="loading-spinner">
        <p>Não foi possível carregar o cardápio. Tente recarregar a página.</p>
      </div>`;
  }
}

function renderCategoryFilters(categories) {
  const wrap = document.getElementById('categoryFilters');
  if (!wrap) return;
  categories.forEach(cat => {
    const btn = document.createElement('button');
    btn.className = 'cat-btn';
    btn.dataset.cat = cat.id;
    btn.textContent = cat.name;
    btn.addEventListener('click', () => filterByCategory(cat.id));
    wrap.appendChild(btn);
  });
}

function filterByCategory(catId) {
  currentCategory = catId;
  document.querySelectorAll('.cat-btn').forEach(b => {
    b.classList.toggle('active', String(b.dataset.cat) === String(catId));
  });
  const filtered = catId ? allProducts.filter(p => p.category_id == catId) : allProducts;
  renderProducts(filtered);
}

function renderProducts(products) {
  const grid = document.getElementById('productsGrid');
  if (!grid) return;

  if (products.length === 0) {
    grid.innerHTML = '<div class="loading-spinner"><p>Nenhum produto encontrado.</p></div>';
    return;
  }

  grid.innerHTML = products.map(p => {
    const stockQty = parseInt(p.stock_qty ?? 99);
    const lowStock = stockQty > 0 && stockQty <= 3;
    const outOfStock = stockQty === 0;
    return `
      <div class="product-card">
        <div class="product-img">
          ${p.image_url ? `<img src="${escHtml(p.image_url)}" alt="${escHtml(p.name)}" loading="lazy">` : '🍗'}
          ${p.featured ? '<span class="product-featured-badge">⭐ Destaque</span>' : ''}
        </div>
        <div class="product-body">
          <div class="product-category">${escHtml(p.category_name || '')}</div>
          <div class="product-name">${escHtml(p.name)}</div>
          <div class="product-desc">${escHtml(p.description || '')}</div>
          ${lowStock   ? '<div class="product-stock-low">⚠️ Últimas unidades!</div>' : ''}
          ${outOfStock ? '<div class="product-stock-low">❌ Esgotado</div>' : ''}
          <div class="product-footer">
            <span class="product-price">${fmtBRL(p.price)}</span>
            <button class="btn-add-cart" onclick="addToCart(${p.id})"
              ${outOfStock ? 'disabled style="opacity:.4;cursor:not-allowed"' : ''}>
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
              </svg>
              Pedir
            </button>
          </div>
        </div>
      </div>`;
  }).join('');
}

function addToCart(productId) {
  const product = allProducts.find(p => p.id == productId);
  if (!product) return;
  Cart.add(product);
  const btns = document.querySelectorAll(`.btn-add-cart`);
}

function openCart() {
  document.getElementById('cartDrawer').classList.add('open');
  document.getElementById('cartOverlay').classList.add('show');
  document.body.style.overflow = 'hidden';
}
function closeCart() {
  document.getElementById('cartDrawer').classList.remove('open');
  document.getElementById('cartOverlay').classList.remove('show');
  document.body.style.overflow = '';
}

async function handleCheckout() {
  if (Cart.items.length === 0) {
    showToast('Seu carrinho está vazio.', 'error');
    return;
  }

  closeCart();

  const form = document.getElementById('checkoutForm');
  const summary = document.getElementById('checkoutSummaryDetail');

  if (form) {
    form.style.display = '';
    form.scrollIntoView({ behavior: 'smooth' });
  }

  if (summary) {
    summary.innerHTML = Cart.items.map(i =>
      `<div class="checkout-summary-item">
        <span>${escHtml(i.name)} × ${i.qty}</span>
        <span>${fmtBRL(i.price * i.qty)}</span>
      </div>`
    ).join('') + `<div class="checkout-summary-item"><span>Total</span><span>${fmtBRL(Cart.total())}</span></div>`;
  }
}

async function confirmOrder() {
  const name    = document.getElementById('checkoutName')?.value?.trim();
  const phone   = document.getElementById('checkoutPhone')?.value?.trim();
  const email   = document.getElementById('checkoutEmail')?.value?.trim();
  const address = document.getElementById('checkoutAddress')?.value?.trim();
  const notes   = document.getElementById('checkoutNotes')?.value?.trim();

  if (!name || !phone) {
    showToast('Preencha nome e telefone.', 'error');
    return;
  }

  const btn = document.getElementById('btnConfirmOrder');
  btn.disabled = true;
  btn.textContent = 'Enviando...';

  try {
    const orderRes = await fetch(API + 'orders', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        customer_name: name,
        customer_phone: phone,
        customer_email: email || null,
        customer_address: address || null,
        notes: notes || null,
        status: 'confirmado',
        items: Cart.items.map(i => ({
          product_id: i.id,
          quantity:   i.qty,
          unit_price: i.price,
        })),
      }),
    });

    const data = await orderRes.json();

    if (data.success || orderRes.status === 201) {
      const orderId = data.id || '—';
      const total   = fmtBRL(Cart.total());
      const itemsList = Cart.items.map(i => `${i.qty}x ${i.name}`).join(', ');
      const phone2  = APP_PHONE_RAW || '11932101000';
      const waMsg   = encodeURIComponent(
        `Olá! Acabei de fazer um pedido pelo site.\n\n` +
        `*Nome:* ${name}\n*Telefone:* ${phone}\n` +
        `*Itens:* ${itemsList}\n*Total:* ${total}\n` +
        (address ? `*Endereço:* ${address}\n` : '') +
        (notes   ? `*Obs:* ${notes}\n` : '') +
        `\nPedido #${orderId}`
      );
      const waUrl = `https://wa.me/55${phone2}?text=${waMsg}`;

      document.getElementById('modalSuccessMsg').textContent =
        `Pedido #${orderId} recebido com sucesso! Total: ${total}. Em breve você receberá contato.`;
      document.getElementById('modalWhatsApp').href = waUrl;
      document.getElementById('modalSuccess').style.display = 'flex';

      Cart.clear();
      document.getElementById('checkoutForm').style.display = 'none';
    } else {
      throw new Error(data.error || 'Erro desconhecido');
    }
  } catch (e) {
    showToast('Erro ao enviar pedido: ' + e.message, 'error');
  } finally {
    btn.disabled = false;
    btn.textContent = 'Confirmar Pedido';
  }
}

window.addEventListener('scroll', () => {
  const navbar = document.getElementById('navbar');
  if (navbar) navbar.classList.toggle('scrolled', window.scrollY > 40);
}, { passive: true });

function fmtBRL(v) {
  return 'R$ ' + parseFloat(v).toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}
function escHtml(str) {
  if (!str) return '';
  return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function showToast(msg, type = 'info') {
  const container = document.getElementById('toastContainer');
  if (!container) return;
  const toast = document.createElement('div');
  toast.className = `toast toast-${type}`;
  toast.innerHTML = {
    success: '✅', error: '❌', info: 'ℹ️', warning: '⚠️'
  }[type] + ' ' + msg;
  container.appendChild(toast);
  setTimeout(() => toast.remove(), 3800);
}

document.addEventListener('DOMContentLoaded', () => {
  Cart.render();
  loadProducts();

  const navbar = document.getElementById('navbar');
  if (navbar) navbar.classList.toggle('scrolled', window.scrollY > 40);

  document.getElementById('cartToggle')?.addEventListener('click', openCart);
  document.getElementById('cartClose')?.addEventListener('click', closeCart);
  document.getElementById('cartOverlay')?.addEventListener('click', closeCart);
  document.getElementById('btnCheckout')?.addEventListener('click', handleCheckout);
  document.getElementById('btnConfirmOrder')?.addEventListener('click', confirmOrder);

  const mobileBtn = document.getElementById('mobileMenuBtn');
  const navLinks   = document.querySelector('.navbar-links');
  if (mobileBtn && navLinks) {
    mobileBtn.addEventListener('click', () => {
      const open = navLinks.style.display === 'flex';
      navLinks.style.cssText = open ? '' : 'display:flex; flex-direction:column; position:fixed; top:72px; left:0; right:0; background:var(--bg-card); border-bottom:1px solid var(--border); padding:16px; gap:4px; z-index:850';
    });
  }

  document.querySelectorAll('a[href^="#"]').forEach(a => {
    a.addEventListener('click', e => {
      const target = document.querySelector(a.getAttribute('href'));
      if (target) {
        e.preventDefault();
        target.scrollIntoView({ behavior: 'smooth' });
        if (navLinks && navLinks.style.display === 'flex') navLinks.style.cssText = '';
      }
    });
  });
});

const APP_PHONE_RAW = (typeof APP_PHONE !== 'undefined' ? APP_PHONE : '').replace(/\D/g,'');
