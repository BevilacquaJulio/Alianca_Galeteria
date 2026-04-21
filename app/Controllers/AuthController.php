<?php

class AuthController {

    public static function login(): void {
        requireMethod('POST');
        $data     = getRequestBody();
        $email    = sanitizeEmail($data['email'] ?? '');
        $password = $data['password'] ?? '';

        if (!$email || !$password) {
            errorResponse('E-mail e senha são obrigatórios.');
        }

        $result = Auth::login($email, $password);

        if (!$result['success']) {
            errorResponse($result['message'], 401);
        }

        jsonResponse([
            'success' => true,
            'admin'   => $result['admin'],
            'csrf'    => Auth::getCsrf(),
        ]);
    }

    public static function logout(): void {
        Auth::logout();
        jsonResponse(['success' => true]);
    }

    public static function me(): void {
        Auth::requireAuth();
        jsonResponse([
            'success' => true,
            'admin'   => [
                'id'   => Auth::adminId(),
                'name' => Auth::adminName(),
                'role' => Auth::adminRole(),
            ],
            'csrf' => Auth::getCsrf(),
        ]);
    }

    public static function changePassword(): void {
        Auth::requireAuth();
        requireMethod('POST');
        $data    = getRequestBody();
        $current = $data['current_password'] ?? '';
        $new     = $data['new_password'] ?? '';

        if (strlen($new) < 6) {
            errorResponse('A nova senha deve ter pelo menos 6 caracteres.');
        }

        $pdo  = Database::get();
        $stmt = $pdo->prepare('SELECT password_hash FROM admins WHERE id = ?');
        $stmt->execute([Auth::adminId()]);
        $row = $stmt->fetch();

        if (!$row || !password_verify($current, $row['password_hash'])) {
            errorResponse('Senha atual incorreta.', 403);
        }

        $hash = password_hash($new, PASSWORD_BCRYPT, ['cost' => 12]);
        $pdo->prepare('UPDATE admins SET password_hash = ? WHERE id = ?')->execute([$hash, Auth::adminId()]);

        jsonResponse(['success' => true, 'message' => 'Senha alterada com sucesso.']);
    }
}
