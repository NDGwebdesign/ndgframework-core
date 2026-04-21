<?php

class Route
{
    protected static $router;

    public static function setRouter($router)
    {
        self::$router = $router;
    }

    public static function get($uri, $action)
    {
        return self::$router->add('GET', $uri, $action);
    }

    public static function post($uri, $action)
    {
        return self::$router->add('POST', $uri, $action);
    }
}
