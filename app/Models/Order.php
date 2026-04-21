<?php

class Order {

    public static function all(array $filters = []): array {
        $pdo    = Database::get();
        $where  = ['1=1'];
        $params = [];

        if (!empty($filters['status'])) {
            $where[]  = 'o.status = ?';
            $params[] = $filters['status'];
        }
        if (!empty($filters['customer_id'])) {
            $where[]  = 'o.customer_id = ?';
            $params[] = (int)$filters['customer_id'];
        }
        if (!empty($filters['date_from'])) {
            $where[]  = 'DATE(o.created_at) >= ?';
            $params[] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $where[]  = 'DATE(o.created_at) <= ?';
            $params[] = $filters['date_to'];
        }
        if (!empty($filters['search'])) {
            $where[]  = '(c.name LIKE ? OR o.id = ?)';
            $params[] = '%' . $filters['search'] . '%';
            $params[] = (int)$filters['search'];
        }

        $limit  = (int)($filters['limit'] ?? 50);
        $offset = (int)($filters['offset'] ?? 0);

        $sql = "SELECT o.*, c.name AS customer_name, c.phone AS customer_phone
                FROM orders o
                LEFT JOIN customers c ON c.id = o.customer_id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY o.created_at DESC
                LIMIT {$limit} OFFSET {$offset}";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function count(array $filters = []): int {
        $pdo    = Database::get();
        $where  = ['1=1'];
        $params = [];

        if (!empty($filters['status'])) {
            $where[]  = 'o.status = ?';
            $params[] = $filters['status'];
        }
        if (!empty($filters['date_from'])) {
            $where[]  = 'DATE(o.created_at) >= ?';
            $params[] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $where[]  = 'DATE(o.created_at) <= ?';
            $params[] = $filters['date_to'];
        }

        $sql  = "SELECT COUNT(*) FROM orders o LEFT JOIN customers c ON c.id = o.customer_id WHERE " . implode(' AND ', $where);
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    public static function find(int $id): ?array {
        $pdo  = Database::get();
        $stmt = $pdo->prepare(
            "SELECT o.*, c.name AS customer_name, c.phone AS customer_phone, c.email AS customer_email, c.address AS customer_address
             FROM orders o
             LEFT JOIN customers c ON c.id = o.customer_id
             WHERE o.id = ? LIMIT 1"
        );
        $stmt->execute([$id]);
        $order = $stmt->fetch();
        if (!$order) return null;

        $order['items'] = self::items($id);
        return $order;
    }

    public static function items(int $orderId): array {
        $pdo  = Database::get();
        $stmt = $pdo->prepare(
            "SELECT oi.*, p.name AS product_name, p.image_url
             FROM order_items oi
             JOIN products p ON p.id = oi.product_id
             WHERE oi.order_id = ?"
        );
        $stmt->execute([$orderId]);
        return $stmt->fetchAll();
    }

    public static function create(array $data): int {
        $pdo = Database::get();

        Database::beginTransaction();
        try {
            foreach ($data['items'] as $item) {
                $stmt = $pdo->prepare('SELECT quantity FROM stock WHERE product_id = ?');
                $stmt->execute([(int)$item['product_id']]);
                $stock = $stmt->fetchColumn();
                if ($stock === false || $stock < (int)$item['quantity']) {
                    Database::rollback();
                    throw new RuntimeException("Estoque insuficiente para o produto ID {$item['product_id']}.");
                }
            }

            $total = 0;
            foreach ($data['items'] as $item) {
                $total += (float)$item['unit_price'] * (int)$item['quantity'];
            }
            $discount = (float)($data['discount'] ?? 0);

            $stmt = $pdo->prepare(
                'INSERT INTO orders (customer_id, admin_id, status, total, discount, notes, upsell_token_id)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                !empty($data['customer_id']) ? (int)$data['customer_id'] : null,
                !empty($data['admin_id'])    ? (int)$data['admin_id']    : null,
                $data['status'] ?? 'confirmado',
                $total - $discount,
                $discount,
                $data['notes'] ?? null,
                !empty($data['upsell_token_id']) ? (int)$data['upsell_token_id'] : null,
            ]);
            $orderId = (int)$pdo->lastInsertId();

            foreach ($data['items'] as $item) {
                $subtotal = (float)$item['unit_price'] * (int)$item['quantity'];
                $pdo->prepare(
                    'INSERT INTO order_items (order_id, product_id, quantity, unit_price, subtotal) VALUES (?, ?, ?, ?, ?)'
                )->execute([$orderId, (int)$item['product_id'], (int)$item['quantity'], (float)$item['unit_price'], $subtotal]);

                $pdo->prepare('UPDATE stock SET quantity = quantity - ? WHERE product_id = ?')
                    ->execute([(int)$item['quantity'], (int)$item['product_id']]);

                $pdo->prepare(
                    'INSERT INTO stock_movements (product_id, type, quantity, reason, order_id) VALUES (?, "out", ?, ?, ?)'
                )->execute([(int)$item['product_id'], (int)$item['quantity'], 'Venda - Pedido #' . $orderId, $orderId]);
            }

            Database::commit();
            return $orderId;
        } catch (Throwable $e) {
            Database::rollback();
            throw $e;
        }
    }

    public static function updateStatus(int $id, string $status, int $adminId = 0): bool {
        $pdo = Database::get();

        Database::beginTransaction();
        try {
            $current = $pdo->prepare('SELECT status FROM orders WHERE id = ?');
            $current->execute([$id]);
            $row = $current->fetch();
            if (!$row) { Database::rollback(); return false; }

            $oldStatus = $row['status'];

            $stmt = $pdo->prepare('UPDATE orders SET status = ? WHERE id = ?');
            $stmt->execute([$status, $id]);

            if ($status === 'cancelado' && !in_array($oldStatus, ['cancelado', 'rascunho'])) {
                $items = self::items($id);
                foreach ($items as $item) {
                    $pdo->prepare('UPDATE stock SET quantity = quantity + ? WHERE product_id = ?')
                        ->execute([$item['quantity'], $item['product_id']]);

                    $pdo->prepare(
                        'INSERT INTO stock_movements (product_id, type, quantity, reason, order_id) VALUES (?, "return", ?, ?, ?)'
                    )->execute([$item['product_id'], $item['quantity'], 'Cancelamento - Pedido #' . $id, $id]);
                }
            }

            Database::commit();
            return true;
        } catch (Throwable $e) {
            Database::rollback();
            throw $e;
        }
    }

    public static function todaySummary(): array {
        $pdo  = Database::get();
        $today = date('Y-m-d');

        $stmt = $pdo->prepare(
            "SELECT COUNT(*) AS total_orders,
                    COALESCE(SUM(total), 0) AS total_revenue,
                    COALESCE(AVG(total), 0) AS avg_ticket
             FROM orders
             WHERE DATE(created_at) = ? AND status != 'cancelado'"
        );
        $stmt->execute([$today]);
        return $stmt->fetch();
    }

    public static function salesByDay(string $from, string $to): array {
        $pdo  = Database::get();
        $stmt = $pdo->prepare(
            "SELECT DATE(created_at) AS day,
                    COUNT(*) AS total_orders,
                    SUM(total) AS total_revenue
             FROM orders
             WHERE DATE(created_at) BETWEEN ? AND ? AND status != 'cancelado'
             GROUP BY DATE(created_at)
             ORDER BY day ASC"
        );
        $stmt->execute([$from, $to]);
        return $stmt->fetchAll();
    }

    public static function rankingProducts(string $from = '', string $to = '', int $limit = 10): array {
        $pdo    = Database::get();
        $where  = ["o.status != 'cancelado'"];
        $params = [];

        if ($from) { $where[] = 'DATE(o.created_at) >= ?'; $params[] = $from; }
        if ($to)   { $where[] = 'DATE(o.created_at) <= ?'; $params[] = $to;   }

        $stmt = $pdo->prepare(
            "SELECT p.id, p.name, SUM(oi.quantity) AS qty_sold, SUM(oi.subtotal) AS revenue
             FROM order_items oi
             JOIN orders o ON o.id = oi.order_id
             JOIN products p ON p.id = oi.product_id
             WHERE " . implode(' AND ', $where) . "
             GROUP BY p.id, p.name
             ORDER BY qty_sold DESC
             LIMIT {$limit}"
        );
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function rankingCustomers(string $from = '', string $to = '', int $limit = 10): array {
        $pdo    = Database::get();
        $where  = ["o.status != 'cancelado'", 'o.customer_id IS NOT NULL'];
        $params = [];

        if ($from) { $where[] = 'DATE(o.created_at) >= ?'; $params[] = $from; }
        if ($to)   { $where[] = 'DATE(o.created_at) <= ?'; $params[] = $to;   }

        $stmt = $pdo->prepare(
            "SELECT c.id, c.name, c.phone, COUNT(o.id) AS total_orders, SUM(o.total) AS total_spent
             FROM orders o
             JOIN customers c ON c.id = o.customer_id
             WHERE " . implode(' AND ', $where) . "
             GROUP BY c.id, c.name, c.phone
             ORDER BY total_spent DESC
             LIMIT {$limit}"
        );
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function deliveredBetween(string $from, string $to): array {
        $pdo  = Database::get();
        $stmt = $pdo->prepare(
            "SELECT o.id, o.customer_id, o.total, c.name AS customer_name
             FROM orders o
             JOIN customers c ON c.id = o.customer_id
             WHERE o.status = 'entregue' AND DATE(o.updated_at) BETWEEN ? AND ?
             ORDER BY o.id ASC"
        );
        $stmt->execute([$from, $to]);
        return $stmt->fetchAll();
    }
}
