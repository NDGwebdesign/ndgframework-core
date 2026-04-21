<?php

class Env
{
    public static function load($path)
    {
        if (!file_exists($path)) return;

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            if (str_starts_with(trim($line), '#')) continue;

            [$key, $value] = explode('=', $line, 2);

            $_ENV[$key] = trim($value);
            putenv("$key=$value");
        }
    }

    public static function get($key, $default = null)
    {
        return $_ENV[$key] ?? getenv($key) ?? $default;
    }
}
