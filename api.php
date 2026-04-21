<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/app/Helpers.php';
require_once __DIR__ . '/app/Database.php';
require_once __DIR__ . '/app/Auth.php';

Auth::start();
loadClasses();

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

$route  = trim($_GET['route'] ?? '', '/');
$method = strtoupper($_SERVER['REQUEST_METHOD']);

$segments = $route ? explode('/', $route) : [];
$r0       = $segments[0] ?? '';
$r1       = isset($segments[1]) && is_numeric($segments[1]) ? (int)$segments[1] : ($segments[1] ?? null);
$r2       = $segments[2] ?? '';

try {
    match(true) {
        $r0 === 'auth' && $r1 === 'login'           => AuthController::login(),
        $r0 === 'auth' && $r1 === 'logout'          => AuthController::logout(),
        $r0 === 'auth' && $r1 === 'me'              => AuthController::me(),
        $r0 === 'auth' && $r1 === 'change-password' => AuthController::changePassword(),

        $r0 === 'dashboard'                         => ReportController::dashboard(),

        $r0 === 'products' && $r1 === null && $method === 'GET'  => ProductController::index(),
        $r0 === 'products' && $r1 === null && $method === 'POST' => ProductController::store(),
        $r0 === 'products' && $r1 === 'categories'               => ProductController::categories(),
        $r0 === 'products' && $r1 === 'public'                   => ProductController::publicIndex(),
        $r0 === 'products' && is_int($r1) && $method === 'GET'   => ProductController::show($r1),
        $r0 === 'products' && is_int($r1) && $method === 'PUT'   => ProductController::update($r1),
        $r0 === 'products' && is_int($r1) && $method === 'DELETE'=> ProductController::destroy($r1),

        $r0 === 'orders' && $r1 === null && $method === 'GET'    => OrderController::index(),
        $r0 === 'orders' && $r1 === null && $method === 'POST'   => OrderController::store(),
        $r0 === 'orders' && $r1 === 'summary'                    => OrderController::summary(),
        $r0 === 'orders' && $r1 === 'chart'                      => OrderController::salesChart(),
        $r0 === 'orders' && is_int($r1) && $r2 === '' && $method === 'GET' => OrderController::show($r1),
        $r0 === 'orders' && is_int($r1) && $r2 === 'status'      => OrderController::updateStatus($r1),

        $r0 === 'customers' && $r1 === null && $method === 'GET'  => CustomerController::index(),
        $r0 === 'customers' && $r1 === null && $method === 'POST' => CustomerController::store(),
        $r0 === 'customers' && is_int($r1) && $r2 === '' && $method === 'GET'    => CustomerController::show($r1),
        $r0 === 'customers' && is_int($r1) && $r2 === '' && $method === 'PUT'    => CustomerController::update($r1),
        $r0 === 'customers' && is_int($r1) && $r2 === '' && $method === 'DELETE' => CustomerController::destroy($r1),

        $r0 === 'stock' && $r1 === null                       => StockController::index(),
        $r0 === 'stock' && $r1 === 'low'                      => StockController::low(),
        $r0 === 'stock' && is_int($r1) && $r2 === 'adjust'    => StockController::adjust($r1),
        $r0 === 'stock' && is_int($r1) && $r2 === 'threshold' => StockController::updateThreshold($r1),
        $r0 === 'stock' && is_int($r1) && $r2 === 'movements' => StockController::movements($r1),

        $r0 === 'upsell' && $r1 === null && $method === 'GET'        => UpsellController::index(),
        $r0 === 'upsell' && $r1 === null && $method === 'POST'       => UpsellController::create(),
        $r0 === 'upsell' && $r1 === 'verify' && !empty($segments[2]) => UpsellController::verify($segments[2]),
        $r0 === 'upsell' && $r1 === 'use'    && !empty($segments[2]) => UpsellController::use($segments[2]),

        $r0 === 'raffles' && $r1 === null      => RaffleController::history(),
        $r0 === 'raffles' && $r1 === 'weekly'  => RaffleController::drawWeekly(),
        $r0 === 'raffles' && $r1 === 'monthly' => RaffleController::drawMonthly(),
        $r0 === 'raffles' && is_int($r1) && $r2 === 'participants' => RaffleController::participants($r1),

        $r0 === 'reports' && $r1 === 'sales'       => ReportController::sales(),
        $r0 === 'reports' && $r1 === 'export-csv'  => ReportController::exportCsv(),
        $r0 === 'reports' && $r1 === 'export-json' => ReportController::exportJson(),

        default => errorResponse('Rota não encontrada: ' . $route, 404),
    };
} catch (Throwable $e) {
    error_log('API Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    errorResponse('Erro interno do servidor.', 500);
}
