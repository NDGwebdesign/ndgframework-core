<?php

class View
{
    protected $view;
    protected $data = [];

    public function __construct($view, $data = [])
    {
        $this->view = $view;
        $this->data = $data;
    }

    public function with($key, $value)
    {
        $this->data[$key] = $value;
        return $this;
    }

    public function render()
    {
        $view = str_replace('.', '/', $this->view);

        extract($this->data);

        ob_start();
        require __DIR__."/../resources/view/$view.php";
        $html = ob_get_clean();

        return parseComponents($html);
    }

    public function __toString()
    {
        return $this->render();
    }

    public static function make($name){
        if (!$name) {
            echo "Please provide a view name.\n";
            return;
        }

        $fileName = __DIR__ . "/../resources/view/{$name}.php";

        if (!is_dir(dirname($fileName))) {
            mkdir(dirname($fileName), 0777, true);
        }
        //make route in web.php
        $webFile = __DIR__ . "/../routes/web.php";
        $route = "\$router->get('/{$name}', function () {
    return view('{$name}');
});\n";
        file_put_contents($webFile, $route, FILE_APPEND);

        file_put_contents($fileName, "<h1>This is the {$name} view</h1>");
        echo "View created: {$fileName}\n";
    }
}