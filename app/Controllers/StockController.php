<?php

class StockController {

    public static function index(): void {
        Auth::requireAuth();

        $pdo  = Database::get();
        $sql  = "SELECT p.id AS product_id, p.name AS product_name, p.active AS product_active,
                        c.name AS category_name,
                        s.id, s.quantity, s.min_quantity, s.unit, s.updated_at,
                        CASE WHEN s.quantity <= s.min_quantity THEN 1 ELSE 0 END AS is_low
                 FROM products p
                 JOIN stock s ON s.product_id = p.id
                 LEFT JOIN categories c ON c.id = p.category_id
                 ORDER BY is_low DESC, p.name ASC";

        $stmt = $pdo->query($sql);
        jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);
    }

    public static function adjust(int $productId): void {
        Auth::requireAuth();
        requireMethod('POST');
        $data = getRequestBody();

        $type   = $data['type'] ?? '';
        $qty    = (int)($data['quantity'] ?? 0);
        $reason = sanitize($data['reason'] ?? '');

        if (!in_array($type, ['in', 'out', 'adjustment'], true)) {
            errorResponse('Tipo inválido. Use: in, out, adjustment.');
        }
        if ($qty <= 0) errorResponse('Quantidade deve ser maior que zero.');

        $pdo  = Database::get();
        $stmt = $pdo->prepare('SELECT quantity FROM stock WHERE product_id = ?');
        $stmt->execute([$productId]);
        $stock = $stmt->fetch();

        if (!$stock) errorResponse('Produto sem registro de estoque.', 404);

        Database::beginTransaction();
        try {
            if ($type === 'out') {
                if ($stock['quantity'] < $qty) {
                    Database::rollback();
                    errorResponse("Estoque insuficiente. Disponível: {$stock['quantity']}.", 422);
                }
                $pdo->prepare('UPDATE stock SET quantity = quantity - ? WHERE product_id = ?')
                    ->execute([$qty, $productId]);
            } elseif ($type === 'in') {
                $pdo->prepare('UPDATE stock SET quantity = quantity + ? WHERE product_id = ?')
                    ->execute([$qty, $productId]);
            } else {
                $pdo->prepare('UPDATE stock SET quantity = ? WHERE product_id = ?')
                    ->execute([$qty, $productId]);
            }

            $pdo->prepare(
                'INSERT INTO stock_movements (product_id, type, quantity, reason, admin_id) VALUES (?, ?, ?, ?, ?)'
            )->execute([$productId, $type, $qty, $reason ?: 'Ajuste manual', Auth::adminId()]);

            Database::commit();
            jsonResponse(['success' => true, 'message' => 'Estoque atualizado.']);
        } catch (Throwable $e) {
            Database::rollback();
            errorResponse('Erro ao atualizar estoque.', 500);
        }
    }

    public static function updateThreshold(int $productId): void {
        Auth::requireAuth();
        requireMethod('PUT');
        $data = getRequestBody();

        $min  = (int)($data['min_quantity'] ?? 5);
        $unit = sanitize($data['unit'] ?? 'un');

        Database::get()->prepare('UPDATE stock SET min_quantity = ?, unit = ? WHERE product_id = ?')
            ->execute([$min, $unit, $productId]);

        jsonResponse(['success' => true, 'message' => 'Limites atualizados.']);
    }

    public static function movements(int $productId): void {
        Auth::requireAuth();

        $pdo  = Database::get();
        $stmt = $pdo->prepare(
            "SELECT sm.*, a.name AS admin_name
             FROM stock_movements sm
             LEFT JOIN admins a ON a.id = sm.admin_id
             WHERE sm.product_id = ?
             ORDER BY sm.created_at DESC
             LIMIT 100"
        );
        $stmt->execute([$productId]);
        jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);
    }

    public static function low(): void {
        Auth::requireAuth();
        jsonResponse(['success' => true, 'data' => Product::lowStock()]);
    }
}
