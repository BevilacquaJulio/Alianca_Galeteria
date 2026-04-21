<?php

class OrderController {

    public static function index(): void {
        Auth::requireAuth();

        $p = paginate($_GET);
        $filters = [
            'status'    => $_GET['status']    ?? '',
            'date_from' => $_GET['date_from'] ?? '',
            'date_to'   => $_GET['date_to']   ?? '',
            'search'    => $_GET['search']    ?? '',
            'limit'     => $p['limit'],
            'offset'    => $p['offset'],
        ];
        if (!$filters['status'])    unset($filters['status']);
        if (!$filters['date_from']) unset($filters['date_from']);
        if (!$filters['date_to'])   unset($filters['date_to']);
        if (!$filters['search'])    unset($filters['search']);

        $orders = Order::all($filters);
        $total  = Order::count($filters);

        jsonResponse(['success' => true, 'data' => $orders, 'total' => $total, 'page' => $p['page'], 'limit' => $p['limit']]);
    }

    public static function show(int $id): void {
        Auth::requireAuth();
        $order = Order::find($id);
        if (!$order) errorResponse('Pedido não encontrado.', 404);
        jsonResponse(['success' => true, 'data' => $order]);
    }

    public static function store(): void {
        Auth::requireAuth();
        requireMethod('POST');

        $data = getRequestBody();

        if (empty($data['items']) || !is_array($data['items'])) {
            errorResponse('Itens do pedido são obrigatórios.');
        }
        foreach ($data['items'] as $item) {
            if (empty($item['product_id']) || empty($item['quantity']) || empty($item['unit_price'])) {
                errorResponse('Cada item deve ter product_id, quantity e unit_price.');
            }
        }

        $data['admin_id'] = Auth::adminId();

        try {
            $id = Order::create($data);
            jsonResponse(['success' => true, 'message' => 'Pedido criado.', 'id' => $id], 201);
        } catch (RuntimeException $e) {
            errorResponse($e->getMessage(), 422);
        }
    }

    public static function updateStatus(int $id): void {
        Auth::requireAuth();
        requireMethod('PUT');

        $data   = getRequestBody();
        $status = $data['status'] ?? '';

        $valid = ['rascunho','confirmado','em_preparo','pronto','entregue','cancelado'];
        if (!in_array($status, $valid, true)) {
            errorResponse('Status inválido.');
        }

        try {
            Order::updateStatus($id, $status, Auth::adminId());
            jsonResponse(['success' => true, 'message' => 'Status atualizado.']);
        } catch (Throwable $e) {
            errorResponse($e->getMessage(), 500);
        }
    }

    public static function summary(): void {
        Auth::requireAuth();
        $summary = Order::todaySummary();
        jsonResponse(['success' => true, 'data' => $summary]);
    }

    public static function salesChart(): void {
        Auth::requireAuth();
        $from = $_GET['from'] ?? date('Y-m-d', strtotime('-29 days'));
        $to   = $_GET['to']   ?? date('Y-m-d');
        $data = Order::salesByDay($from, $to);
        jsonResponse(['success' => true, 'data' => $data]);
    }
}
