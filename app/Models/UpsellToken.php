<?php

class UpsellToken {

    public static function create(int $customerId, array $data): array {
        $pdo   = Database::get();
        $token = bin2hex(random_bytes(20));
        $hash  = hash('sha256', $token . APP_SECRET);

        $expiresAt = date('Y-m-d H:i:s', strtotime('+' . (int)($data['expires_days'] ?? 7) . ' days'));

        $stmt = $pdo->prepare(
            'INSERT INTO upsell_tokens (customer_id, token_hash, discount_percent, product_id, message, expires_at)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $customerId,
            $hash,
            (int)($data['discount_percent'] ?? 10),
            !empty($data['product_id']) ? (int)$data['product_id'] : null,
            $data['message'] ?? null,
            $expiresAt,
        ]);

        return [
            'id'         => (int)$pdo->lastInsertId(),
            'token'      => $token,
            'token_hash' => $hash,
            'expires_at' => $expiresAt,
        ];
    }

    public static function verify(string $token): ?array {
        $hash = hash('sha256', $token . APP_SECRET);
        $pdo  = Database::get();
        $stmt = $pdo->prepare(
            "SELECT ut.*, c.name AS customer_name, c.phone AS customer_phone,
                    p.name AS product_name, p.price AS product_price, p.description AS product_description, p.image_url AS product_image
             FROM upsell_tokens ut
             JOIN customers c ON c.id = ut.customer_id
             LEFT JOIN products p ON p.id = ut.product_id
             WHERE ut.token_hash = ?
               AND ut.expires_at > NOW()
               AND ut.used_at IS NULL
             LIMIT 1"
        );
        $stmt->execute([$hash]);
        return $stmt->fetch() ?: null;
    }

    public static function markUsed(int $id): void {
        Database::get()->prepare('UPDATE upsell_tokens SET used_at = NOW() WHERE id = ?')->execute([$id]);
    }

    public static function allByCustomer(int $customerId): array {
        $pdo  = Database::get();
        $stmt = $pdo->prepare(
            "SELECT ut.*, p.name AS product_name
             FROM upsell_tokens ut
             LEFT JOIN products p ON p.id = ut.product_id
             WHERE ut.customer_id = ?
             ORDER BY ut.created_at DESC"
        );
        $stmt->execute([$customerId]);
        return $stmt->fetchAll();
    }

    public static function all(array $filters = []): array {
        $pdo    = Database::get();
        $where  = ['1=1'];
        $params = [];

        if (!empty($filters['customer_id'])) {
            $where[]  = 'ut.customer_id = ?';
            $params[] = (int)$filters['customer_id'];
        }

        $stmt = $pdo->prepare(
            "SELECT ut.*, c.name AS customer_name, c.phone AS customer_phone, p.name AS product_name
             FROM upsell_tokens ut
             JOIN customers c ON c.id = ut.customer_id
             LEFT JOIN products p ON p.id = ut.product_id
             WHERE " . implode(' AND ', $where) . "
             ORDER BY ut.created_at DESC
             LIMIT 100"
        );
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function buildLink(string $token): string {
        return BASE_URL . '/upsell.php?token=' . urlencode($token);
    }

    public static function qrUrl(string $link): string {
        return 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($link) . '&format=png&ecc=M';
    }

    public static function whatsappMessage(array $tokenData, string $link): string {
        $name     = $tokenData['customer_name'] ?? 'cliente';
        $discount = $tokenData['discount_percent'];
        $product  = !empty($tokenData['product_name']) ? " no {$tokenData['product_name']}" : '';
        $phone    = APP_PHONE;

        return "Olá, {$name}! 🍗\n\nA *Aliança Galeteria* preparou uma oferta especial para você!\n\n"
             . "*{$discount}% de desconto{$product}* — exclusivo para você!\n\n"
             . "Acesse sua oferta personalizada:\n{$link}\n\n"
             . "Válido até " . date('d/m/Y', strtotime($tokenData['expires_at'])) . ".\n\n"
             . "Dúvidas? Fale conosco: {$phone}";
    }
}
