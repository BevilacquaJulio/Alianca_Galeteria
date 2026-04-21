<?php

class CustomerController {

    public static function index(): void {
        Auth::requireAuth();
        $p       = paginate($_GET);
        $filters = [
            'search' => $_GET['search'] ?? '',
            'active' => 1,
            'limit'  => $p['limit'],
            'offset' => $p['offset'],
        ];
        if (!$filters['search']) unset($filters['search']);

        $customers = Customer::all($filters);
        $total     = Customer::count($filters);
        jsonResponse(['success' => true, 'data' => $customers, 'total' => $total]);
    }

    public static function show(int $id): void {
        Auth::requireAuth();
        $customer = Customer::find($id);
        if (!$customer) errorResponse('Cliente não encontrado.', 404);

        $customer['orders']      = Customer::history($id);
        $customer['total_spent'] = Customer::totalSpent($id);
        $customer['upsells']     = UpsellToken::allByCustomer($id);

        jsonResponse(['success' => true, 'data' => $customer]);
    }

    public static function store(): void {
        Auth::requireAuth();
        requireMethod('POST');
        $data = getRequestBody();

        if (empty($data['name'])) errorResponse('Nome é obrigatório.');
        if (!empty($data['email']) && !validateEmail($data['email'])) errorResponse('E-mail inválido.');

        $data['name'] = sanitize($data['name']);
        $data['email'] = !empty($data['email']) ? sanitizeEmail($data['email']) : null;

        if ($data['email']) {
            $pdo  = Database::get();
            $stmt = $pdo->prepare('SELECT id FROM customers WHERE email = ? LIMIT 1');
            $stmt->execute([$data['email']]);
            if ($stmt->fetch()) errorResponse('E-mail já cadastrado.', 409);
        }

        $id = Customer::create($data);
        jsonResponse(['success' => true, 'message' => 'Cliente criado.', 'id' => $id], 201);
    }

    public static function update(int $id): void {
        Auth::requireAuth();
        requireMethod('PUT');
        $data = getRequestBody();

        if (empty($data['name'])) errorResponse('Nome é obrigatório.');
        if (!empty($data['email']) && !validateEmail($data['email'])) errorResponse('E-mail inválido.');
        if (!Customer::find($id)) errorResponse('Cliente não encontrado.', 404);

        $data['name'] = sanitize($data['name']);

        Customer::update($id, $data);
        jsonResponse(['success' => true, 'message' => 'Cliente atualizado.']);
    }

    public static function destroy(int $id): void {
        Auth::requireAuth();
        requireMethod('DELETE');

        if (!Customer::find($id)) errorResponse('Cliente não encontrado.', 404);

        Customer::delete($id);
        jsonResponse(['success' => true, 'message' => 'Cliente desativado.']);
    }
}
