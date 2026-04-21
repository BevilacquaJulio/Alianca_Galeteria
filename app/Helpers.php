<?php

function jsonResponse(mixed $data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function errorResponse(string $message, int $status = 400): void {
    jsonResponse(['success' => false, 'error' => $message], $status);
}

function successResponse(mixed $data = [], string $message = 'OK'): void {
    jsonResponse(array_merge(['success' => true, 'message' => $message], (array)$data));
}

function sanitize(string $value): string {
    return htmlspecialchars(strip_tags(trim($value)), ENT_QUOTES, 'UTF-8');
}

function sanitizeEmail(string $email): string {
    return filter_var(trim($email), FILTER_SANITIZE_EMAIL);
}

function validateEmail(string $email): bool {
    return (bool)filter_var($email, FILTER_VALIDATE_EMAIL);
}

function formatBRL(float $value): string {
    return 'R$ ' . number_format($value, 2, ',', '.');
}

function formatDate(string $datetime): string {
    if (!$datetime) return '—';
    $dt = new DateTime($datetime);
    return $dt->format('d/m/Y H:i');
}

function formatDateOnly(string $date): string {
    if (!$date) return '—';
    $dt = new DateTime($date);
    return $dt->format('d/m/Y');
}

function statusLabel(string $status): string {
    $labels = [
        'rascunho'   => 'Rascunho',
        'confirmado' => 'Confirmado',
        'em_preparo' => 'Em Preparo',
        'pronto'     => 'Pronto',
        'entregue'   => 'Entregue',
        'cancelado'  => 'Cancelado',
    ];
    return $labels[$status] ?? ucfirst($status);
}

function generateToken(int $length = 32): string {
    return bin2hex(random_bytes($length));
}

function getRequestBody(): array {
    $body = file_get_contents('php://input');
    return json_decode($body, true) ?? [];
}

function getMethod(): string {
    return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
}

function requireMethod(string ...$methods): void {
    if (!in_array(getMethod(), $methods, true)) {
        errorResponse('Método não permitido.', 405);
    }
}

function paginate(array $params): array {
    $page  = max(1, (int)($params['page'] ?? 1));
    $limit = min(100, max(5, (int)($params['limit'] ?? 20)));
    return ['page' => $page, 'limit' => $limit, 'offset' => ($page - 1) * $limit];
}

function loadClasses(): void {
    $files = [
        __DIR__ . '/Database.php',
        __DIR__ . '/Auth.php',
        __DIR__ . '/Models/Product.php',
        __DIR__ . '/Models/Order.php',
        __DIR__ . '/Models/Customer.php',
        __DIR__ . '/Models/UpsellToken.php',
        __DIR__ . '/Models/Raffle.php',
        __DIR__ . '/Controllers/AuthController.php',
        __DIR__ . '/Controllers/ProductController.php',
        __DIR__ . '/Controllers/OrderController.php',
        __DIR__ . '/Controllers/CustomerController.php',
        __DIR__ . '/Controllers/StockController.php',
        __DIR__ . '/Controllers/UpsellController.php',
        __DIR__ . '/Controllers/RaffleController.php',
        __DIR__ . '/Controllers/ReportController.php',
    ];
    foreach ($files as $file) {
        if (file_exists($file)) require_once $file;
    }
}
