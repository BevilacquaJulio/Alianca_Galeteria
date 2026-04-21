<?php

class ReportController {

    public static function sales(): void {
        Auth::requireAuth();

        $from = $_GET['from'] ?? date('Y-m-01');
        $to   = $_GET['to']   ?? date('Y-m-d');

        $days     = Order::salesByDay($from, $to);
        $products = Order::rankingProducts($from, $to, 10);
        $customers= Order::rankingCustomers($from, $to, 10);

        $pdo  = Database::get();
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) AS total_orders,
                    COALESCE(SUM(total), 0) AS total_revenue,
                    COALESCE(AVG(total), 0) AS avg_ticket,
                    COUNT(DISTINCT customer_id) AS unique_customers
             FROM orders
             WHERE DATE(created_at) BETWEEN ? AND ? AND status != 'cancelado'"
        );
        $stmt->execute([$from, $to]);
        $summary = $stmt->fetch();

        jsonResponse([
            'success'    => true,
            'period'     => ['from' => $from, 'to' => $to],
            'summary'    => $summary,
            'by_day'     => $days,
            'products'   => $products,
            'customers'  => $customers,
        ]);
    }

    public static function exportCsv(): void {
        Auth::requireAuth();

        $from = $_GET['from'] ?? date('Y-m-01');
        $to   = $_GET['to']   ?? date('Y-m-d');
        $type = $_GET['type'] ?? 'orders';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="alianca_' . $type . '_' . $from . '_' . $to . '.csv"');

        $out = fopen('php://output', 'w');
        fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));

        if ($type === 'orders') {
            fputcsv($out, ['ID', 'Cliente', 'Status', 'Total', 'Desconto', 'Data']);
            $orders = Order::all(['date_from' => $from, 'date_to' => $to, 'limit' => 9999, 'offset' => 0]);
            foreach ($orders as $o) {
                fputcsv($out, [
                    $o['id'],
                    $o['customer_name'] ?? 'Sem cliente',
                    statusLabel($o['status']),
                    number_format((float)$o['total'], 2, '.', ''),
                    number_format((float)$o['discount'], 2, '.', ''),
                    $o['created_at'],
                ]);
            }
        } elseif ($type === 'products') {
            fputcsv($out, ['ID', 'Produto', 'Qtd Vendida', 'Receita']);
            $products = Order::rankingProducts($from, $to, 9999);
            foreach ($products as $p) {
                fputcsv($out, [$p['id'], $p['name'], $p['qty_sold'], number_format((float)$p['revenue'], 2, '.', '')]);
            }
        } elseif ($type === 'customers') {
            fputcsv($out, ['ID', 'Cliente', 'Telefone', 'Pedidos', 'Total Gasto']);
            $customers = Order::rankingCustomers($from, $to, 9999);
            foreach ($customers as $c) {
                fputcsv($out, [$c['id'], $c['name'], $c['phone'], $c['total_orders'], number_format((float)$c['total_spent'], 2, '.', '')]);
            }
        }

        fclose($out);
        exit;
    }

    public static function exportJson(): void {
        Auth::requireAuth();

        $from = $_GET['from'] ?? date('Y-m-01');
        $to   = $_GET['to']   ?? date('Y-m-d');

        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="alianca_report_' . $from . '_' . $to . '.json"');

        $pdo  = Database::get();
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) AS total_orders, COALESCE(SUM(total), 0) AS total_revenue, COALESCE(AVG(total), 0) AS avg_ticket
             FROM orders WHERE DATE(created_at) BETWEEN ? AND ? AND status != 'cancelado'"
        );
        $stmt->execute([$from, $to]);

        echo json_encode([
            'generated_at' => date('c'),
            'period'       => ['from' => $from, 'to' => $to],
            'summary'      => $stmt->fetch(),
            'by_day'       => Order::salesByDay($from, $to),
            'top_products' => Order::rankingProducts($from, $to),
            'top_customers'=> Order::rankingCustomers($from, $to),
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    public static function dashboard(): void {
        Auth::requireAuth();

        $today    = date('Y-m-d');
        $weekAgo  = date('Y-m-d', strtotime('-6 days'));
        $monthAgo = date('Y-m-d', strtotime('-29 days'));

        $pdo = Database::get();

        $kpiToday = Order::todaySummary();

        $totalCustomers = (int)$pdo->query("SELECT COUNT(*) FROM customers WHERE active = 1")->fetchColumn();
        $totalProducts  = (int)$pdo->query("SELECT COUNT(*) FROM products WHERE active = 1")->fetchColumn();

        $lowStock = Product::lowStock();

        $stmt = $pdo->prepare(
            "SELECT status, COUNT(*) AS count FROM orders WHERE DATE(created_at) = ? GROUP BY status"
        );
        $stmt->execute([$today]);
        $statusToday = $stmt->fetchAll();

        $salesWeek  = Order::salesByDay($weekAgo, $today);
        $lastOrders = Order::all(['limit' => 5, 'offset' => 0]);

        jsonResponse([
            'success'         => true,
            'kpi_today'       => $kpiToday,
            'total_customers' => $totalCustomers,
            'total_products'  => $totalProducts,
            'low_stock'       => $lowStock,
            'status_today'    => $statusToday,
            'sales_week'      => $salesWeek,
            'last_orders'     => $lastOrders,
        ]);
    }
}
