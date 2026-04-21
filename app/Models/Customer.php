<?php

class Customer {

    public static function all(array $filters = []): array {
        $pdo    = Database::get();
        $where  = ['1=1'];
        $params = [];

        if (!empty($filters['search'])) {
            $where[]  = '(name LIKE ? OR email LIKE ? OR phone LIKE ?)';
            $s = '%' . $filters['search'] . '%';
            $params[] = $s; $params[] = $s; $params[] = $s;
        }
        if (isset($filters['active'])) {
            $where[]  = 'active = ?';
            $params[] = (int)$filters['active'];
        }

        $limit  = (int)($filters['limit']  ?? 50);
        $offset = (int)($filters['offset'] ?? 0);

        $stmt = $pdo->prepare(
            "SELECT * FROM customers WHERE " . implode(' AND ', $where) .
            " ORDER BY name ASC LIMIT {$limit} OFFSET {$offset}"
        );
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function count(array $filters = []): int {
        $pdo    = Database::get();
        $where  = ['1=1'];
        $params = [];

        if (!empty($filters['search'])) {
            $where[]  = '(name LIKE ? OR email LIKE ? OR phone LIKE ?)';
            $s = '%' . $filters['search'] . '%';
            $params[] = $s; $params[] = $s; $params[] = $s;
        }

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM customers WHERE " . implode(' AND ', $where));
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    public static function find(int $id): ?array {
        $pdo  = Database::get();
        $stmt = $pdo->prepare('SELECT * FROM customers WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function create(array $data): int {
        $pdo  = Database::get();
        $stmt = $pdo->prepare(
            'INSERT INTO customers (name, email, phone, cpf, address, notes) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['name'],
            !empty($data['email']) ? $data['email'] : null,
            $data['phone'] ?? null,
            $data['cpf']   ?? null,
            $data['address'] ?? null,
            $data['notes'] ?? null,
        ]);
        return (int)$pdo->lastInsertId();
    }

    public static function update(int $id, array $data): bool {
        $pdo  = Database::get();
        $stmt = $pdo->prepare(
            'UPDATE customers SET name=?, email=?, phone=?, cpf=?, address=?, notes=? WHERE id=?'
        );
        return $stmt->execute([
            $data['name'],
            !empty($data['email']) ? $data['email'] : null,
            $data['phone'] ?? null,
            $data['cpf']   ?? null,
            $data['address'] ?? null,
            $data['notes'] ?? null,
            $id,
        ]);
    }

    public static function delete(int $id): bool {
        $pdo  = Database::get();
        return $pdo->prepare('UPDATE customers SET active = 0 WHERE id = ?')->execute([$id]);
    }

    public static function history(int $id): array {
        $pdo  = Database::get();
        $stmt = $pdo->prepare(
            "SELECT o.id, o.status, o.total, o.created_at,
                    COUNT(oi.id) AS items_count
             FROM orders o
             LEFT JOIN order_items oi ON oi.order_id = o.id
             WHERE o.customer_id = ?
             GROUP BY o.id
             ORDER BY o.created_at DESC"
        );
        $stmt->execute([$id]);
        return $stmt->fetchAll();
    }

    public static function totalSpent(int $id): float {
        $pdo  = Database::get();
        $stmt = $pdo->prepare(
            "SELECT COALESCE(SUM(total), 0) FROM orders WHERE customer_id = ? AND status != 'cancelado'"
        );
        $stmt->execute([$id]);
        return (float)$stmt->fetchColumn();
    }
}
