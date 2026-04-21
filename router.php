<?php

class Router
{
    protected $routes = [];

    public function get($uri, $action)
    {
        $this->routes['GET'][$uri] = $action;
    }

    public function post($uri, $action)
    {
        $this->routes['POST'][$uri] = $action;
    }

    public function dispatch($uri, $method)
    {
        $action = $this->routes[$method][$uri] ?? null;

        if (!$action) {
            http_response_code(404);
            include __DIR__ . '/../resources/error/404.php';
            return;
        }

        if ($action instanceof Closure) {
            echo $action();
            return;
        }

        [$controller, $method] = $action;

        if (!class_exists($controller)) {
            throw new Exception("Controller $controller not found");
        }

        $controller = new $controller;

        if (!method_exists($controller, $method)) {
            throw new Exception("Method $method not found");
        }

        $response = $controller->$method();

        echo $response;
    }
}