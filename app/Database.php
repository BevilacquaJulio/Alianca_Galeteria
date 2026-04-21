<?php

class Database {
    private static ?PDO $instance = null;

    public static function get(): PDO {
        if (self::$instance === null) {
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=%s',
                DB_HOST, DB_NAME, DB_CHARSET
            );
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            try {
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
            } catch (PDOException $e) {
                http_response_code(503);
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Falha na conexão com o banco de dados.']);
                exit;
            }
        }
        return self::$instance;
    }

    public static function beginTransaction(): void {
        self::get()->beginTransaction();
    }

    public static function commit(): void {
        self::get()->commit();
    }

    public static function rollback(): void {
        if (self::get()->inTransaction()) {
            self::get()->rollBack();
        }
    }
}
