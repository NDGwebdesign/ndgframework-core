<?php

class RouteDefinition
{
    public string $method;
    public string $uri;
    public $action;
    public ?string $name = null;
    protected $router;

    public function __construct($method, $uri, $action, $router)
    {
        $this->method = $method;
        $this->uri = $uri;
        $this->action = $action;
        $this->router = $router;
    }

    public function name($name)
    {
        $this->name = $name;
        return $this;
    }
}
