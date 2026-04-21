<?php

class RaffleController {

    public static function history(): void {
        Auth::requireAuth();
        jsonResponse(['success' => true, 'data' => Raffle::history(50)]);
    }

    public static function participants(int $id): void {
        Auth::requireAuth();
        jsonResponse(['success' => true, 'data' => Raffle::participants($id)]);
    }

    public static function drawWeekly(): void {
        Auth::requireAuth();
        requireMethod('POST');

        $data          = getRequestBody();
        $simulatedDate = !empty($data['simulated_date']) ? $data['simulated_date'] : null;

        try {
            $result = Raffle::runWeekly($simulatedDate);
            if ($result['success']) {
                jsonResponse(['success' => true, 'data' => $result, 'message' => 'Sorteio semanal realizado!']);
            } else {
                jsonResponse(['success' => false, 'message' => $result['message']], 422);
            }
        } catch (Throwable $e) {
            errorResponse('Erro ao realizar sorteio: ' . $e->getMessage(), 500);
        }
    }

    public static function drawMonthly(): void {
        Auth::requireAuth();
        requireMethod('POST');

        $data          = getRequestBody();
        $simulatedDate = !empty($data['simulated_date']) ? $data['simulated_date'] : null;

        try {
            $result = Raffle::runMonthly($simulatedDate);
            if ($result['success']) {
                jsonResponse(['success' => true, 'data' => $result, 'message' => 'Sorteio mensal realizado!']);
            } else {
                jsonResponse(['success' => false, 'message' => $result['message']], 422);
            }
        } catch (Throwable $e) {
            errorResponse('Erro ao realizar sorteio: ' . $e->getMessage(), 500);
        }
    }
}
