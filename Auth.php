<?php

class Auth
{
    protected static ?array $cachedUser = null;

    public static function user(): ?array
    {
        if (!isset($_SESSION['auth_user_id'])) {
            return null;
        }

        if (self::$cachedUser !== null) {
            return self::$cachedUser;
        }

        $user = User::find((int) $_SESSION['auth_user_id']);

        if (!$user) {
            self::logout();
            return null;
        }

        self::$cachedUser = $user;

        return $user;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function login(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION['auth_user_id'] = (int) $user['id'];
        self::$cachedUser = $user;
    }

    public static function attempt(string $email, string $password): bool
    {
        $user = User::findByEmail($email);

        if (!$user) {
            return false;
        }

        if (!password_verify($password, $user['password'])) {
            return false;
        }

        self::login($user);

        return true;
    }

    public static function logout(): void
    {
        self::$cachedUser = null;

        unset($_SESSION['auth_user_id']);

        session_regenerate_id(true);
    }
}
