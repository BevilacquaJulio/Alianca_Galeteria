<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/app/Helpers.php';
require_once __DIR__ . '/app/Database.php';
require_once __DIR__ . '/app/Auth.php';
Auth::start();
$isLogged = Auth::check();
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Aliança Galeteria — Painel</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/admin.css">
  <script>
    const BASE_URL = '<?= BASE_URL ?>';
    const APP_PHONE = '<?= APP_PHONE ?>';
    const INITIAL_AUTH = <?= $isLogged ? 'true' : 'false' ?>;
  </script>
</head>
<body class="admin-body">

<div class="login-screen" id="loginScreen" <?= $isLogged ? 'style="display:none"' : '' ?>>
  <div class="login-bg-pattern"></div>
  <div class="login-card">
    <div class="login-logo">
      <img src="<?= BASE_URL ?>/assets/img/logo.png" alt="Aliança Galeteria" class="login-logo-img">
      <div>
        <span class="login-logo-name">Aliança Galeteria</span>
        <span class="login-logo-sub">Painel Administrativo</span>
      </div>
    </div>
    <form id="loginForm" autocomplete="off">
      <div class="form-group">
        <label class="form-label">E-mail</label>
        <input type="email" id="loginEmail" class="form-input" placeholder="admin@aliancagaleteria.com.br" autocomplete="username" required>
      </div>
      <div class="form-group">
        <label class="form-label">Senha</label>
        <div class="input-icon-wrap">
          <input type="password" id="loginPassword" class="form-input" placeholder="••••••••" autocomplete="current-password" required>
          <button type="button" class="input-eye" id="togglePass" tabindex="-1">
            <svg id="eyeIcon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
            </svg>
          </button>
        </div>
      </div>
      <div id="loginError" class="form-error" style="display:none"></div>
      <button type="submit" class="btn btn-primary w-full btn-lg" id="btnLogin">
        Entrar no Sistema
      </button>
    </form>
    <div class="login-hint">
      <small>Acesso restrito a colaboradores autorizados</small>
    </div>
  </div>
</div>

<div class="app-shell" id="appShell" <?= !$isLogged ? 'style="display:none"' : '' ?>>

  <aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
      <img src="<?= BASE_URL ?>/assets/img/logo.png" alt="Aliança Galeteria" class="sidebar-logo-img">
      <div class="sidebar-logo-text">
        <strong>Aliança</strong>
        <span>Galeteria</span>
      </div>
    </div>
    <nav class="sidebar-nav">
      <div class="sidebar-section-label">Principal</div>
      <a class="nav-item active" href="#dashboard" data-page="dashboard">
        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/>
          <rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>
        </svg>
        Dashboard
      </a>
      <a class="nav-item" href="#orders" data-page="orders">
        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/>
          <rect x="9" y="3" width="6" height="4" rx="2"/><path d="M9 12h6M9 16h4"/>
        </svg>
        Pedidos
        <span class="nav-badge" id="badgeOrders"></span>
      </a>

      <div class="sidebar-section-label">Operação</div>
      <a class="nav-item" href="#products" data-page="products">
        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/>
          <line x1="7" y1="7" x2="7.01" y2="7"/>
        </svg>
        Produtos
      </a>
      <a class="nav-item" href="#stock" data-page="stock">
        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 3H8L2 7h20z"/>
          <path d="M12 12v5M9.5 14.5h5"/>
        </svg>
        Estoque
        <span class="nav-badge nav-badge-warn" id="badgeStock"></span>
      </a>
      <a class="nav-item" href="#customers" data-page="customers">
        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
          <circle cx="9" cy="7" r="4"/>
          <path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>
        </svg>
        Clientes
      </a>

      <div class="sidebar-section-label">Marketing</div>
      <a class="nav-item" href="#upsell" data-page="upsell">
        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/>
          <polyline points="17 6 23 6 23 12"/>
        </svg>
        Upsell / QR
      </a>
      <a class="nav-item" href="#raffles" data-page="raffles">
        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="12" cy="12" r="10"/>
          <polygon points="10 8 16 12 10 16 10 8"/>
        </svg>
        Sorteios
      </a>

      <div class="sidebar-section-label">Análise</div>
      <a class="nav-item" href="#reports" data-page="reports">
        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/>
          <line x1="6" y1="20" x2="6" y2="14"/>
        </svg>
        Relatórios
      </a>
    </nav>
    <div class="sidebar-footer">
      <div class="sidebar-user" id="sidebarUser">
        <div class="user-avatar" id="userAvatar">A</div>
        <div class="user-info">
          <span id="userName">Admin</span>
          <small id="userRole">administrador</small>
        </div>
      </div>
      <button class="btn-logout" id="btnLogout" title="Sair">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
          <polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>
        </svg>
      </button>
    </div>
  </aside>

  <div class="main-area">

    <header class="topbar">
      <button class="sidebar-toggle" id="sidebarToggle" aria-label="Menu">
        <span></span><span></span><span></span>
      </button>
      <div class="topbar-title" id="topbarTitle">Dashboard</div>
      <div class="topbar-right">
        <div class="topbar-time" id="topbarTime"></div>
        <a href="<?= BASE_URL ?>" target="_blank" class="btn-topbar-link" title="Ver loja pública">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>
            <polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/>
          </svg>
          Loja
        </a>
        <div class="topbar-alert" id="topbarAlertLow" style="display:none" title="Produtos com estoque baixo">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
            <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
          </svg>
          <span id="lowStockCount">0</span>
        </div>
      </div>
    </header>

    <main class="page-content" id="pageContent"></main>

  </div>
</div>

<div class="modal-overlay" id="globalModal" style="display:none" role="dialog">
  <div class="modal-container" id="globalModalBox">
    <div class="modal-header">
      <h3 id="globalModalTitle">Modal</h3>
      <button class="modal-close" id="globalModalClose" aria-label="Fechar">✕</button>
    </div>
    <div class="modal-body" id="globalModalBody"></div>
    <div class="modal-footer" id="globalModalFooter"></div>
  </div>
</div>

<div class="modal-overlay" id="confirmModal" style="display:none" role="dialog">
  <div class="modal-container modal-sm">
    <div class="modal-header">
      <h3 id="confirmTitle">Confirmar</h3>
    </div>
    <div class="modal-body">
      <p id="confirmMessage"></p>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" id="confirmCancel">Cancelar</button>
      <button class="btn btn-danger" id="confirmOk">Confirmar</button>
    </div>
  </div>
</div>

<div class="toast-container" id="adminToast"></div>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<script src="assets/js/charts.js"></script>
<script src="assets/js/admin.js"></script>
</body>
</html>
