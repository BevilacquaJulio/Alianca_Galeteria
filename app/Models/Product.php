<?php

class Product {

    public static function all(array $filters = []): array {
        $pdo    = Database::get();
        $where  = ['1=1'];
        $params = [];

        if (!empty($filters['category_id'])) {
            $where[]  = 'p.category_id = ?';
            $params[] = (int)$filters['category_id'];
        }
        if (isset($filters['active'])) {
            $where[]  = 'p.active = ?';
            $params[] = (int)$filters['active'];
        }
        if (!empty($filters['search'])) {
            $where[]  = 'p.name LIKE ?';
            $params[] = '%' . $filters['search'] . '%';
        }

        $sql = "SELECT p.*, c.name AS category_name,
                       s.quantity AS stock_qty, s.min_quantity AS stock_min, s.unit AS stock_unit
                FROM products p
                LEFT JOIN categories c ON c.id = p.category_id
                LEFT JOIN stock s ON s.product_id = p.id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY p.featured DESC, p.name ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array {
        $pdo  = Database::get();
        $stmt = $pdo->prepare(
            "SELECT p.*, c.name AS category_name,
                    s.quantity AS stock_qty, s.min_quantity AS stock_min, s.unit AS stock_unit
             FROM products p
             LEFT JOIN categories c ON c.id = p.category_id
             LEFT JOIN stock s ON s.product_id = p.id
             WHERE p.id = ? LIMIT 1"
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function create(array $data): int {
        $pdo  = Database::get();
        $stmt = $pdo->prepare(
            'INSERT INTO products (category_id, name, description, price, image_url, active, featured)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            (int)$data['category_id'],
            $data['name'],
            $data['description'] ?? null,
            (float)$data['price'],
            $data['image_url'] ?? null,
            isset($data['active']) ? (int)$data['active'] : 1,
            isset($data['featured']) ? (int)$data['featured'] : 0,
        ]);
        $id = (int)$pdo->lastInsertId();

        $qty = (int)($data['stock_qty'] ?? 0);
        $min = (int)($data['stock_min'] ?? 5);
        $unit = $data['stock_unit'] ?? 'un';
        $pdo->prepare('INSERT INTO stock (product_id, quantity, min_quantity, unit) VALUES (?, ?, ?, ?)')
            ->execute([$id, $qty, $min, $unit]);

        if ($qty > 0) {
            $pdo->prepare(
                'INSERT INTO stock_movements (product_id, type, quantity, reason) VALUES (?, "in", ?, "Estoque inicial")'
            )->execute([$id, $qty]);
        }

        return $id;
    }

    public static function update(int $id, array $data): bool {
        $pdo  = Database::get();
        $stmt = $pdo->prepare(
            'UPDATE products SET category_id=?, name=?, description=?, price=?, image_url=?, active=?, featured=?
             WHERE id=?'
        );
        return $stmt->execute([
            (int)$data['category_id'],
            $data['name'],
            $data['description'] ?? null,
            (float)$data['price'],
            $data['image_url'] ?? null,
            isset($data['active']) ? (int)$data['active'] : 1,
            isset($data['featured']) ? (int)$data['featured'] : 0,
            $id,
        ]);
    }

    public static function delete(int $id): bool {
        $pdo  = Database::get();
        $stmt = $pdo->prepare('UPDATE products SET active = 0 WHERE id = ?');
        return $stmt->execute([$id]);
    }

    public static function categories(): array {
        $pdo = Database::get();
        return $pdo->query('SELECT * FROM categories WHERE active = 1 ORDER BY sort_order, name')->fetchAll();
    }

    public static function featured(): array {
        return self::all(['active' => 1, 'featured' => 1]);
    }

    public static function lowStock(): array {
        $pdo  = Database::get();
        $stmt = $pdo->query(
            "SELECT p.id, p.name, s.quantity, s.min_quantity, s.unit
             FROM stock s
             JOIN products p ON p.id = s.product_id
             WHERE s.quantity <= s.min_quantity AND p.active = 1
             ORDER BY s.quantity ASC"
        );
        return $stmt->fetchAll();
    }
}
