<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/app/Helpers.php';
require_once __DIR__ . '/app/Database.php';
require_once __DIR__ . '/app/Auth.php';
loadClasses();

$token     = $_GET['token'] ?? '';
$tokenData = null;
$error     = '';
$link      = '';
$qrUrl     = '';
$whatsapp  = '';

if ($token) {
    $tokenData = UpsellToken::verify($token);
    if ($tokenData) {
        $link     = UpsellToken::buildLink($token);
        $qrUrl    = UpsellToken::qrUrl($link);
        $whatsapp = UpsellToken::whatsappMessage($tokenData, $link);
    } else {
        $error = 'Esta oferta não está mais disponível. Pode ter expirado ou já ter sido utilizada.';
    }
} else {
    $error = 'Link de oferta inválido.';
}
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Oferta Exclusiva — Aliança Galeteria</title>
  <meta property="og:title" content="Oferta Exclusiva — Aliança Galeteria">
  <meta property="og:description" content="Uma oferta especial foi preparada para você!">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/public.css">
  <style>
    body { background: #121212; }
    .upsell-wrapper {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 40px 16px;
    }
    .upsell-card {
      background: #1B1B1B;
      border: 1px solid #2A2A2A;
      border-radius: 20px;
      padding: 48px 40px;
      max-width: 560px;
      width: 100%;
      text-align: center;
      box-shadow: 0 24px 80px rgba(0,0,0,0.6);
    }
    .upsell-logo {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      margin-bottom: 32px;
    }
    .upsell-logo-img { width: 140px; height: auto; object-fit: contain; display: block; margin: 0 auto; }
    .upsell-tag {
      display: inline-block;
      background: rgba(212,138,28,0.15);
      border: 1px solid rgba(212,138,28,0.4);
      color: #D48A1C;
      font-size: 0.75rem;
      font-weight: 600;
      letter-spacing: 0.1em;
      text-transform: uppercase;
      padding: 6px 16px;
      border-radius: 100px;
      margin-bottom: 20px;
    }
    .upsell-greeting {
      font-size: 1.1rem;
      color: #9A9A9A;
      margin-bottom: 8px;
    }
    .upsell-name {
      font-size: 1.8rem;
      font-weight: 700;
      color: #F1F1F1;
      margin-bottom: 24px;
    }
    .upsell-discount-badge {
      background: linear-gradient(135deg, #6A0F1F, #8B1020);
      border-radius: 16px;
      padding: 32px 24px;
      margin: 24px 0;
      position: relative;
      overflow: hidden;
    }
    .upsell-discount-badge::before {
      content: '';
      position: absolute;
      top: -40px; right: -40px;
      width: 150px; height: 150px;
      background: rgba(255,255,255,0.03);
      border-radius: 50%;
    }
    .upsell-discount-number {
      font-size: 4rem;
      font-weight: 800;
      color: #D48A1C;
      line-height: 1;
    }
    .upsell-discount-label {
      font-size: 1.1rem;
      color: #F5EFE6;
      margin-top: 8px;
    }
    .upsell-product-card {
      background: #222;
      border: 1px solid #2A2A2A;
      border-radius: 12px;
      padding: 20px;
      margin: 16px 0;
      display: flex;
      align-items: center;
      gap: 16px;
      text-align: left;
    }
    .upsell-product-img {
      width: 64px;
      height: 64px;
      border-radius: 10px;
      background: #2A2A2A;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 2rem;
      flex-shrink: 0;
      overflow: hidden;
    }
    .upsell-product-img img { width: 100%; height: 100%; object-fit: cover; }
    .upsell-product-name { font-weight: 600; color: #F1F1F1; }
    .upsell-product-price { color: #D48A1C; font-size: 1.1rem; font-weight: 700; margin-top: 4px; }
    .upsell-product-desc { color: #9A9A9A; font-size: 0.85rem; margin-top: 4px; }
    .upsell-custom-message {
      background: #222;
      border-left: 3px solid #D48A1C;
      border-radius: 0 8px 8px 0;
      padding: 16px 20px;
      text-align: left;
      color: #F5EFE6;
      font-style: italic;
      margin: 20px 0;
    }
    .upsell-qr-section {
      padding: 24px 0 0;
    }
    .upsell-qr-title {
      font-size: 0.85rem;
      color: #9A9A9A;
      margin-bottom: 12px;
      text-transform: uppercase;
      letter-spacing: 0.08em;
    }
    .upsell-qr-img {
      background: #fff;
      border-radius: 12px;
      padding: 12px;
      display: inline-block;
    }
    .upsell-qr-img img { display: block; border-radius: 6px; }
    .upsell-expires {
      font-size: 0.85rem;
      color: #9A9A9A;
      margin-top: 20px;
    }
    .upsell-expires strong { color: #E4571E; }
    .upsell-actions {
      display: flex;
      flex-direction: column;
      gap: 12px;
      margin-top: 28px;
    }
    .upsell-error-icon { font-size: 3rem; margin-bottom: 16px; }
    .upsell-error-title { font-size: 1.5rem; font-weight: 700; color: #F1F1F1; margin-bottom: 12px; }
    .upsell-error-desc { color: #9A9A9A; margin-bottom: 24px; }
    @media (max-width: 560px) {
      .upsell-card { padding: 32px 20px; }
      .upsell-discount-number { font-size: 3rem; }
    }
  </style>
</head>
<body>
<div class="upsell-wrapper">
  <div class="upsell-card">

    <div class="upsell-logo">
      <img src="<?= BASE_URL ?>/assets/img/logo.png" alt="Aliança Galeteria" class="upsell-logo-img">
    </div>

    <?php if ($error): ?>
    <div class="upsell-error-icon">😞</div>
    <div class="upsell-error-title">Oferta não disponível</div>
    <p class="upsell-error-desc"><?= htmlspecialchars($error) ?></p>
    <a href="<?= BASE_URL ?>" class="btn btn-primary btn-lg">Ver Cardápio Completo</a>
    <p style="margin-top:16px; color:#9A9A9A; font-size:0.85rem;">
      Dúvidas? <a href="tel:+5511932101000" style="color:#D48A1C;"><?= APP_PHONE ?></a>
    </p>

    <?php else: ?>
    <span class="upsell-tag">🎉 Oferta Exclusiva para Você</span>

    <p class="upsell-greeting">Olá,</p>
    <h1 class="upsell-name"><?= htmlspecialchars($tokenData['customer_name']) ?>!</h1>

    <div class="upsell-discount-badge">
      <div class="upsell-discount-number"><?= $tokenData['discount_percent'] ?>%</div>
      <div class="upsell-discount-label">de desconto exclusivo para você</div>
    </div>

    <?php if (!empty($tokenData['product_name'])): ?>
    <div class="upsell-product-card">
      <div class="upsell-product-img">
        <?php if (!empty($tokenData['product_image'])): ?>
          <img src="<?= htmlspecialchars($tokenData['product_image']) ?>" alt="">
        <?php else: ?>
          🍗
        <?php endif; ?>
      </div>
      <div>
        <div class="upsell-product-name"><?= htmlspecialchars($tokenData['product_name']) ?></div>
        <?php if (!empty($tokenData['product_price'])): ?>
          <?php
            $originalPrice = (float)$tokenData['product_price'];
            $discountedPrice = $originalPrice * (1 - $tokenData['discount_percent'] / 100);
          ?>
          <div class="upsell-product-price">
            <span style="color:#9A9A9A; text-decoration:line-through; font-size:0.9rem; font-weight:400;"><?= formatBRL($originalPrice) ?></span>
            &nbsp;<?= formatBRL($discountedPrice) ?>
          </div>
        <?php endif; ?>
        <?php if (!empty($tokenData['product_description'])): ?>
          <div class="upsell-product-desc"><?= htmlspecialchars(substr($tokenData['product_description'], 0, 100)) ?>...</div>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($tokenData['message'])): ?>
    <div class="upsell-custom-message">
      "<?= htmlspecialchars($tokenData['message']) ?>"
    </div>
    <?php endif; ?>

    <div class="upsell-expires">
      Válido até <strong><?= date('d/m/Y', strtotime($tokenData['expires_at'])) ?></strong>
    </div>

    <div class="upsell-actions">
      <?php
        $phone = preg_replace('/\D/', '', APP_PHONE);
        $msg   = urlencode($whatsapp);
        $waUrl = "https://wa.me/55{$phone}?text={$msg}";
      ?>
      <a href="<?= $waUrl ?>" class="btn btn-whatsapp btn-lg" target="_blank" rel="noopener">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
          <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413z"/>
        </svg>
        Resgatar pelo WhatsApp
      </a>
      <a href="<?= BASE_URL ?>" class="btn btn-ghost btn-lg">Ver Cardápio Completo</a>
    </div>

    <div class="upsell-qr-section">
      <div class="upsell-qr-title">Escaneie para compartilhar</div>
      <div class="upsell-qr-img">
        <img src="<?= htmlspecialchars($qrUrl) ?>" width="200" height="200" alt="QR Code da oferta" loading="lazy">
      </div>
    </div>

    <p style="margin-top: 24px; color: #666; font-size: 0.8rem;">
      <?= APP_PHONE ?> · Aliança Galeteria
    </p>
    <?php endif; ?>

  </div>
</div>
</body>
</html>
