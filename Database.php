<?php

class Database
{
    private static $pdo;

    public static function connect()
    {
        if (self::$pdo) return self::$pdo;

        $dsn = sprintf(
            "mysql:host=%s;dbname=%s;port=%s;charset=utf8mb4",
            Env::get('DB_HOST'),
            Env::get('DB_NAME'),
            Env::get('DB_PORT')
        );

        self::$pdo = new PDO(
            $dsn,
            Env::get('DB_USER'),
            Env::get('DB_PASS'),
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );

        return self::$pdo;
    }
}
