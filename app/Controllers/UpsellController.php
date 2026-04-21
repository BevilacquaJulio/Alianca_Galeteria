<?php

class UpsellController {

    public static function index(): void {
        Auth::requireAuth();
        $filters = [];
        if (!empty($_GET['customer_id'])) $filters['customer_id'] = (int)$_GET['customer_id'];

        $tokens = UpsellToken::all($filters);
        jsonResponse(['success' => true, 'data' => $tokens]);
    }

    public static function create(): void {
        Auth::requireAuth();
        requireMethod('POST');

        $data = getRequestBody();

        if (empty($data['customer_id'])) errorResponse('Cliente é obrigatório.');

        $customerId = (int)$data['customer_id'];
        if (!Customer::find($customerId)) errorResponse('Cliente não encontrado.', 404);

        $tokenData = UpsellToken::create($customerId, [
            'discount_percent' => (int)($data['discount_percent'] ?? 10),
            'product_id'       => !empty($data['product_id']) ? (int)$data['product_id'] : null,
            'message'          => sanitize($data['message'] ?? ''),
            'expires_days'     => (int)($data['expires_days'] ?? 7),
        ]);

        $link    = UpsellToken::buildLink($tokenData['token']);
        $qrUrl   = UpsellToken::qrUrl($link);

        $created = UpsellToken::verify($tokenData['token']);
        if (!$created) {
            $created = ['customer_name' => '', 'discount_percent' => $data['discount_percent'] ?? 10, 'product_name' => '', 'expires_at' => $tokenData['expires_at']];
        }
        $whatsapp = UpsellToken::whatsappMessage($created, $link);

        jsonResponse([
            'success'   => true,
            'message'   => 'Token de upsell criado.',
            'token'     => $tokenData['token'],
            'link'      => $link,
            'qr_url'    => $qrUrl,
            'whatsapp'  => $whatsapp,
            'expires_at'=> $tokenData['expires_at'],
        ], 201);
    }

    public static function verify(string $token): void {
        $data = UpsellToken::verify($token);
        if (!$data) {
            errorResponse('Token inválido, expirado ou já utilizado.', 404);
        }
        $link = UpsellToken::buildLink($token);
        jsonResponse([
            'success'  => true,
            'data'     => $data,
            'link'     => $link,
            'qr_url'   => UpsellToken::qrUrl($link),
        ]);
    }

    public static function use(string $token): void {
        requireMethod('POST');
        $data = UpsellToken::verify($token);
        if (!$data) {
            errorResponse('Token inválido, expirado ou já utilizado.', 404);
        }
        UpsellToken::markUsed((int)$data['id']);
        jsonResponse(['success' => true, 'message' => 'Oferta aplicada com sucesso!', 'discount_percent' => $data['discount_percent']]);
    }
}
