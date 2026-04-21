<?php
require_once __DIR__ . '/config.php';
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Aliança Galeteria — Artesanal &amp; Premium</title>
  <meta name="description" content="Galeteria artesanal premium em São Paulo. Frango caipira assado lentamente com temperos exclusivos.">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/public.css">
  <script>const BASE_URL = '<?= BASE_URL ?>';</script>
</head>
<body>

<header class="navbar" id="navbar">
  <div class="navbar-inner">
    <a href="<?= BASE_URL ?>" class="navbar-logo">
      <img src="<?= BASE_URL ?>/assets/img/logo.png" alt="Aliança Galeteria" class="logo-img logo-navbar">
    </a>
    <nav class="navbar-links">
      <a href="#cardapio">Cardápio</a>
      <a href="#diferenciais">Diferenciais</a>
      <a href="#contato">Contato</a>
      <a href="<?= BASE_URL ?>/admin.php" class="btn-nav-admin">Área Admin</a>
    </nav>
    <button class="cart-toggle" id="cartToggle" aria-label="Carrinho">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>
        <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
      </svg>
      <span class="cart-badge" id="cartBadge">0</span>
    </button>
    <button class="mobile-menu-btn" id="mobileMenuBtn" aria-label="Menu">
      <span></span><span></span><span></span>
    </button>
  </div>
</header>

<section class="hero" id="hero">
  <div class="hero-bg-pattern"></div>
  <div class="hero-content">
    <span class="hero-tag">Artesanal &amp; Exclusivo</span>
    <h1 class="hero-title">
      O Verdadeiro <br>
      <em>Galetinho</em> <br>
      de São Paulo
    </h1>
    <p class="hero-desc">
      Frango caipira assado lentamente em forno a lenha, com ervas frescas
      e temperos exclusivos da casa. Uma tradição transmitida com orgulho.
    </p>
    <div class="hero-actions">
      <a href="#cardapio" class="btn btn-primary btn-lg">Ver Cardápio Completo</a>
      <a href="tel:+551193210-1000" class="btn btn-ghost btn-lg">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.61 3.42 2 2 0 0 1 3.6 1.24h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.83a16 16 0 0 0 6 6l.96-.96a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 21.5 16v.92z"/>
        </svg>
        <?= APP_PHONE ?>
      </a>
    </div>
    <div class="hero-badges">
      <span class="badge">🌿 Tempero Artesanal</span>
      <span class="badge">🔥 Assado Lentamente</span>
      <span class="badge">🐓 Frango Caipira</span>
    </div>
  </div>
  <div class="hero-image-area">
    <div class="hero-plate">
      <div class="plate-glow"></div>
      <img src="<?= BASE_URL ?>/assets/img/frango-hero.png" alt="Galetinho assado" class="plate-emoji">
      <div class="plate-ring"></div>
    </div>
  </div>
</section>

<section class="stats-bar">
  <div class="stats-inner">
    <div class="stat-item">
      <strong>+2.000</strong>
      <span>Galetinhos por mês</span>
    </div>
    <div class="stat-divider"></div>
    <div class="stat-item">
      <strong>15+</strong>
      <span>Anos de tradição</span>
    </div>
    <div class="stat-divider"></div>
    <div class="stat-item">
      <strong>100%</strong>
      <span>Ingredientes frescos</span>
    </div>
    <div class="stat-divider"></div>
    <div class="stat-item">
      <strong>4.9★</strong>
      <span>Avaliação média</span>
    </div>
  </div>
</section>

<section class="section" id="cardapio">
  <div class="container">
    <div class="section-header">
      <span class="section-tag">Nosso Cardápio</span>
      <h2 class="section-title">Escolha o seu favorito</h2>
      <p class="section-desc">Todos preparados diariamente com ingredientes selecionados.</p>
    </div>

    <div class="category-filters" id="categoryFilters">
      <button class="cat-btn active" data-cat="">Todos</button>
    </div>

    <div class="products-grid" id="productsGrid">
      <div class="loading-spinner">
        <div class="spinner"></div>
        <p>Carregando cardápio...</p>
      </div>
    </div>
  </div>
</section>

<section class="section section-dark" id="diferenciais">
  <div class="container">
    <div class="section-header">
      <span class="section-tag">Por que somos diferentes</span>
      <h2 class="section-title">Tradição que você sente no sabor</h2>
    </div>
    <div class="features-grid">
      <div class="feature-card">
        <div class="feature-icon">🔥</div>
        <h3>Assado Lentamente</h3>
        <p>Cada galetinho é assado por mais de 2 horas em temperatura controlada, garantindo carne suculenta por dentro e crocante por fora.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon">🌿</div>
        <h3>Tempero Secreto</h3>
        <p>Nossa marinada exclusiva com mais de 12 ervas frescas e especiarias selecionadas é preparada toda manhã. Receita da família há 15 anos.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon">🐓</div>
        <h3>Frango Caipira</h3>
        <p>Selecionamos apenas frangos caipiras de criadores parceiros, com alimentação natural e sem hormônios. Qualidade que você percebe.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon">📦</div>
        <h3>Entrega Cuidadosa</h3>
        <p>Embalagem especial que mantém a crocância e o calor do frango até a sua mesa. Cada pedido é uma experiência completa.</p>
      </div>
    </div>
  </div>
</section>

<section class="section" id="contato">
  <div class="container">
    <div class="contact-grid">
      <div class="contact-info">
        <span class="section-tag">Fale Conosco</span>
        <h2 class="section-title">Estamos aqui <br>para atender você</h2>
        <div class="contact-items">
          <div class="contact-item">
            <div class="contact-icon">📞</div>
            <div>
              <strong>Telefone / WhatsApp</strong>
              <a href="tel:+5511932101000"><?= APP_PHONE ?></a>
            </div>
          </div>
          <div class="contact-item">
            <div class="contact-icon">⏰</div>
            <div>
              <strong>Horário de Funcionamento</strong>
              <span>Seg–Sex: 11h às 22h | Sáb–Dom: 11h às 23h</span>
            </div>
          </div>
          <div class="contact-item">
            <div class="contact-icon">📍</div>
            <div>
              <strong>Localização</strong>
              <span>São Paulo — SP</span>
            </div>
          </div>
        </div>
        <a href="https://wa.me/5511932101000?text=Olá,%20Aliança%20Galeteria!%20Gostaria%20de%20fazer%20um%20pedido."
           class="btn btn-whatsapp btn-lg" target="_blank" rel="noopener">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413z"/>
          </svg>
          Pedir pelo WhatsApp
        </a>
      </div>
      <div class="contact-form-area">
        <div class="checkout-form" id="checkoutForm" style="display:none">
          <h3>Finalizar Pedido</h3>
          <div class="form-group">
            <label>Seu nome *</label>
            <input type="text" id="checkoutName" placeholder="Nome completo" required>
          </div>
          <div class="form-group">
            <label>Telefone *</label>
            <input type="tel" id="checkoutPhone" placeholder="(11) 99999-9999" required>
          </div>
          <div class="form-group">
            <label>E-mail</label>
            <input type="email" id="checkoutEmail" placeholder="seu@email.com">
          </div>
          <div class="form-group">
            <label>Endereço de entrega</label>
            <textarea id="checkoutAddress" rows="2" placeholder="Rua, número, bairro..."></textarea>
          </div>
          <div class="form-group">
            <label>Observações</label>
            <textarea id="checkoutNotes" rows="2" placeholder="Ponto da carne, restrições..."></textarea>
          </div>
          <div class="checkout-summary" id="checkoutSummaryDetail"></div>
          <button class="btn btn-primary btn-lg w-full" id="btnConfirmOrder">
            Confirmar Pedido
          </button>
        </div>
      </div>
    </div>
  </div>
</section>

<footer class="footer">
  <div class="container">
    <div class="footer-grid">
      <div class="footer-brand">
        <img src="<?= BASE_URL ?>/assets/img/logo.png" alt="Aliança Galeteria" class="logo-img logo-footer">
        <span class="logo-text">Aliança <strong>Galeteria</strong></span>
        <p>Tradição e sabor em cada galetinho. Artesanal como deve ser.</p>
      </div>
      <div class="footer-links">
        <h4>Navegação</h4>
        <a href="#cardapio">Cardápio</a>
        <a href="#diferenciais">Diferenciais</a>
        <a href="#contato">Contato</a>
      </div>
      <div class="footer-contact">
        <h4>Contato</h4>
        <p><?= APP_PHONE ?></p>
        <p>São Paulo — SP</p>
        <p>Seg–Dom: 11h às 23h</p>
      </div>
    </div>
    <div class="footer-bottom">
      <p>&copy; <?= date('Y') ?> Aliança Galeteria. Todos os direitos reservados.</p>
    </div>
  </div>
</footer>

<div class="cart-overlay" id="cartOverlay"></div>
<aside class="cart-drawer" id="cartDrawer" role="dialog" aria-label="Carrinho">
  <div class="cart-header">
    <h3>Seu Pedido</h3>
    <button class="cart-close" id="cartClose" aria-label="Fechar carrinho">✕</button>
  </div>
  <div class="cart-body" id="cartBody">
    <div class="cart-empty">
      <div class="cart-empty-icon">🛒</div>
      <p>Seu carrinho está vazio</p>
      <small>Adicione itens do cardápio</small>
    </div>
  </div>
  <div class="cart-footer" id="cartFooter" style="display:none">
    <div class="cart-total">
      <span>Total</span>
      <strong id="cartTotal">R$ 0,00</strong>
    </div>
    <button class="btn btn-primary w-full" id="btnCheckout">
      Finalizar Pedido
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
        <path d="M5 12h14M12 5l7 7-7 7"/>
      </svg>
    </button>
  </div>
</aside>

<div class="toast-container" id="toastContainer"></div>

<div class="modal-overlay" id="modalSuccess" style="display:none">
  <div class="modal-box">
    <div class="modal-success-icon">✅</div>
    <h2>Pedido Enviado!</h2>
    <p id="modalSuccessMsg">Seu pedido foi recebido. Em breve entraremos em contato pelo WhatsApp.</p>
    <div class="modal-actions">
      <a id="modalWhatsApp" href="#" class="btn btn-whatsapp" target="_blank">
        Confirmar pelo WhatsApp
      </a>
      <button class="btn btn-ghost" onclick="document.getElementById('modalSuccess').style.display='none'">
        Fechar
      </button>
    </div>
  </div>
</div>

<script src="assets/js/public.js"></script>
</body>
</html>
