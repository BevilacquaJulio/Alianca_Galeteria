<?php

class ProductController {

    public static function index(): void {
        $filters = [
            'category_id' => $_GET['category_id'] ?? '',
            'active'      => isset($_GET['active']) ? (int)$_GET['active'] : null,
            'search'      => $_GET['search'] ?? '',
        ];
        if ($filters['active'] === null) unset($filters['active']);

        jsonResponse(['success' => true, 'data' => Product::all($filters)]);
    }

    public static function publicIndex(): void {
        $filters = ['active' => 1];
        if (!empty($_GET['category_id'])) $filters['category_id'] = (int)$_GET['category_id'];
        if (!empty($_GET['search']))      $filters['search']       = $_GET['search'];

        $products   = Product::all($filters);
        $categories = Product::categories();
        jsonResponse(['success' => true, 'data' => $products, 'categories' => $categories]);
    }

    public static function show(int $id): void {
        $product = Product::find($id);
        if (!$product) errorResponse('Produto não encontrado.', 404);
        jsonResponse(['success' => true, 'data' => $product]);
    }

    public static function store(): void {
        Auth::requireAuth();
        requireMethod('POST');
        $data = getRequestBody();

        if (empty($data['name']))        errorResponse('Nome é obrigatório.');
        if (empty($data['category_id'])) errorResponse('Categoria é obrigatória.');
        if (!isset($data['price']) || (float)$data['price'] <= 0) errorResponse('Preço inválido.');

        $data['name']        = sanitize($data['name']);
        $data['description'] = sanitize($data['description'] ?? '');

        $id = Product::create($data);
        jsonResponse(['success' => true, 'message' => 'Produto criado.', 'id' => $id], 201);
    }

    public static function update(int $id): void {
        Auth::requireAuth();
        requireMethod('PUT');
        $data = getRequestBody();

        if (empty($data['name']))        errorResponse('Nome é obrigatório.');
        if (empty($data['category_id'])) errorResponse('Categoria é obrigatória.');
        if (!isset($data['price']) || (float)$data['price'] <= 0) errorResponse('Preço inválido.');

        if (!Product::find($id)) errorResponse('Produto não encontrado.', 404);

        $data['name']        = sanitize($data['name']);
        $data['description'] = sanitize($data['description'] ?? '');

        Product::update($id, $data);
        jsonResponse(['success' => true, 'message' => 'Produto atualizado.']);
    }

    public static function destroy(int $id): void {
        Auth::requireAuth();
        requireMethod('DELETE');

        if (!Product::find($id)) errorResponse('Produto não encontrado.', 404);

        Product::delete($id);
        jsonResponse(['success' => true, 'message' => 'Produto desativado.']);
    }

    public static function categories(): void {
        jsonResponse(['success' => true, 'data' => Product::categories()]);
    }
}
