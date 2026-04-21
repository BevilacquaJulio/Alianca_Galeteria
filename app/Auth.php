<?php

class Auth {

    public static function start(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_set_cookie_params([
                'lifetime' => SESSION_LIFETIME,
                'path'     => '/',
                'secure'   => false,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            session_start();
        }
    }

    public static function login(string $email, string $password): array {
        $ip = self::getIp();

        if (self::isBlocked($ip, $email)) {
            return ['success' => false, 'message' => 'Muitas tentativas. Tente novamente em ' . LOGIN_BLOCK_MINUTES . ' minutos.'];
        }

        $pdo = Database::get();
        $stmt = $pdo->prepare('SELECT id, name, email, password_hash, role, active FROM admins WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $admin = $stmt->fetch();

        if (!$admin || !$admin['active'] || !password_verify($password, $admin['password_hash'])) {
            $pdo->prepare('INSERT INTO login_attempts (ip, email) VALUES (?, ?)')->execute([$ip, $email]);
            return ['success' => false, 'message' => 'Credenciais inválidas.'];
        }

        $pdo->prepare('DELETE FROM login_attempts WHERE ip = ? AND email = ?')->execute([$ip, $email]);

        session_regenerate_id(true);
        $_SESSION['admin_id']   = $admin['id'];
        $_SESSION['admin_name'] = $admin['name'];
        $_SESSION['admin_role'] = $admin['role'];
        $_SESSION['logged_in']  = true;
        $_SESSION['login_time'] = time();
        $_SESSION['csrf_token'] = self::generateCsrf();

        return ['success' => true, 'admin' => ['id' => $admin['id'], 'name' => $admin['name'], 'role' => $admin['role']]];
    }

    public static function logout(): void {
        self::start();
        $_SESSION = [];
        session_destroy();
    }

    public static function check(): bool {
        self::start();
        if (empty($_SESSION['logged_in']) || empty($_SESSION['admin_id'])) {
            return false;
        }
        if ((time() - ($_SESSION['login_time'] ?? 0)) > SESSION_LIFETIME) {
            self::logout();
            return false;
        }
        return true;
    }

    public static function requireAuth(): void {
        if (!self::check()) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Não autorizado.']);
            exit;
        }
    }

    public static function adminId(): int {
        return (int)($_SESSION['admin_id'] ?? 0);
    }

    public static function adminName(): string {
        return $_SESSION['admin_name'] ?? '';
    }

    public static function adminRole(): string {
        return $_SESSION['admin_role'] ?? '';
    }

    public static function generateCsrf(): string {
        return bin2hex(random_bytes(32));
    }

    public static function getCsrf(): string {
        self::start();
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = self::generateCsrf();
        }
        return $_SESSION['csrf_token'];
    }

    public static function verifyCsrf(string $token): bool {
        return hash_equals($_SESSION['csrf_token'] ?? '', $token);
    }

    private static function isBlocked(string $ip, string $email): bool {
        $pdo = Database::get();
        $since = date('Y-m-d H:i:s', strtotime('-' . LOGIN_BLOCK_MINUTES . ' minutes'));
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM login_attempts WHERE ip = ? AND email = ? AND attempted_at >= ?'
        );
        $stmt->execute([$ip, $email, $since]);
        return (int)$stmt->fetchColumn() >= MAX_LOGIN_ATTEMPTS;
    }

    private static function getIp(): string {
        return $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}
