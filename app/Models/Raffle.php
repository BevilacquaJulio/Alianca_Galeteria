<?php

class Raffle {

    public static function runWeekly(?string $simulatedDate = null): array {
        $ref  = $simulatedDate ? new DateTime($simulatedDate) : new DateTime();
        $ref->modify('last monday');
        $from = (clone $ref)->modify('last monday')->format('Y-m-d');
        $to   = (clone $ref)->modify('last sunday')->format('Y-m-d');

        $now   = new DateTime();
        $currentMonday = (clone $now)->modify('Monday this week')->format('Y-m-d');
        if (!$simulatedDate && $from >= $currentMonday) {
            $from = (clone $now)->modify('Monday last week')->format('Y-m-d');
            $to   = (clone $now)->modify('Sunday last week')->format('Y-m-d');
        }

        $label = 'Semana ' . date('d/m', strtotime($from)) . ' a ' . date('d/m/Y', strtotime($to));
        return self::draw('weekly', $label, $from, $to);
    }

    public static function runMonthly(?string $simulatedDate = null): array {
        $ref = $simulatedDate ? new DateTime($simulatedDate) : new DateTime();
        $ref->modify('first day of last month');
        $from  = $ref->format('Y-m-01');
        $to    = $ref->format('Y-m-t');
        $label = $ref->format('m/Y');
        return self::draw('monthly', $label, $from, $to);
    }

    private static function draw(string $type, string $label, string $from, string $to): array {
        $pdo          = Database::get();
        $participants = Order::deliveredBetween($from, $to);

        if (empty($participants)) {
            return ['success' => false, 'message' => 'Nenhum participante elegível no período ' . $label];
        }

        $exists = $pdo->prepare('SELECT id FROM raffles WHERE type = ? AND period_label = ? LIMIT 1');
        $exists->execute([$type, $label]);
        if ($exists->fetch()) {
            return ['success' => false, 'message' => 'Sorteio para o período ' . $label . ' já foi realizado.'];
        }

        $winnerIndex = random_int(0, count($participants) - 1);
        $winner      = $participants[$winnerIndex];

        Database::beginTransaction();
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO raffles (type, period_label, winner_order_id, winner_customer_id, winner_name, participants_count, simulated_date)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $type,
                $label,
                (int)$winner['id'],
                (int)$winner['customer_id'],
                $winner['customer_name'],
                count($participants),
                $from,
            ]);
            $raffleId = (int)$pdo->lastInsertId();

            $ins = $pdo->prepare('INSERT INTO raffle_participants (raffle_id, order_id, customer_id) VALUES (?, ?, ?)');
            foreach ($participants as $p) {
                $ins->execute([$raffleId, (int)$p['id'], (int)$p['customer_id']]);
            }

            Database::commit();

            return [
                'success'            => true,
                'raffle_id'          => $raffleId,
                'winner'             => $winner['customer_name'],
                'winner_order_id'    => $winner['id'],
                'participants_count' => count($participants),
                'period'             => $label,
            ];
        } catch (Throwable $e) {
            Database::rollback();
            throw $e;
        }
    }

    public static function history(int $limit = 20): array {
        $pdo  = Database::get();
        $stmt = $pdo->prepare(
            "SELECT r.*, c.phone AS winner_phone
             FROM raffles r
             LEFT JOIN customers c ON c.id = r.winner_customer_id
             ORDER BY r.drawn_at DESC
             LIMIT {$limit}"
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function participants(int $raffleId): array {
        $pdo  = Database::get();
        $stmt = $pdo->prepare(
            "SELECT rp.*, c.name AS customer_name, c.phone AS customer_phone, o.total AS order_total
             FROM raffle_participants rp
             JOIN customers c ON c.id = rp.customer_id
             JOIN orders o ON o.id = rp.order_id
             WHERE rp.raffle_id = ?
             ORDER BY c.name ASC"
        );
        $stmt->execute([$raffleId]);
        return $stmt->fetchAll();
    }
}
