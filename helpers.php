<?php

function view($view, $data = [])
{
    return new View($view, $data);
}

function redirect($path)
{
    header("Location: $path");
    exit;
}

function e($value)
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function app_name()
{
    return Env::get('APP_NAME', 'ndgframework');
}

function app_url($path = '')
{
    $base = rtrim(Env::get('APP_URL', 'http://localhost'), '/');
    $path = ltrim($path, '/');

    return $path === '' ? $base : $base . '/' . $path;
}

function csrf_field()
{
    return '<input type="hidden" name="_token" value="' . e(Csrf::token()) . '">';
}

function auth_user()
{
    return Auth::user();
}

function auth_check()
{
    return Auth::check();
}

function framework_project_root()
{
    $localRoot = dirname(__DIR__);
    if (is_dir($localRoot . '/resources') && is_dir($localRoot . '/routes')) {
        return $localRoot;
    }

    $vendorRoot = dirname(__DIR__, 3);
    if (is_dir($vendorRoot . '/resources') && is_dir($vendorRoot . '/routes')) {
        return $vendorRoot;
    }

    return getcwd();
}

function parseComponents($html)
{
    $resolveComponent = function ($component, $slot = '') {
        $root = framework_project_root();
        $layout = $root . "/resources/layouts/$component.php";
        $componentFile = $root . "/resources/components/$component.php";

        $file = file_exists($layout) ? $layout : $componentFile;

        if (!file_exists($file)) {
            return "<!-- component $component not found -->";
        }

        ob_start();
        require $file;
        return ob_get_clean();
    };

    $previous = null;

    while ($previous !== $html) {
        $previous = $html;

        $html = preg_replace_callback(
            '/<x-([a-zA-Z0-9\-]+)\s*\/\s*>/',
            function ($matches) use ($resolveComponent) {
                $component = $matches[1];
                return $resolveComponent($component);
            },
            $html
        );

        $html = preg_replace_callback(
            '/<x-([a-zA-Z0-9\-]+)>(.*?)<\/x-\1>/s',
            function ($matches) use ($resolveComponent) {
                $component = $matches[1];
                $slot = $matches[2];
                return $resolveComponent($component, $slot);
            },
            $html
        );
    }

    return $html;
}
function vite($file)
{
    $isLocal = Env::get('APP_ENV', 'local') === 'local';
    $root = framework_project_root();
    $hotFile = $root . '/public/hot';

    if ($isLocal && file_exists($hotFile)) {
        $devBase = trim((string) file_get_contents($hotFile));
        $devBase = $devBase !== '' ? rtrim($devBase, '/') : app_url();

        if (str_ends_with($file, '.js')) {
            return "<script type='module' src='{$devBase}/@vite/client'></script>\n"
                . "<script type='module' src='{$devBase}/{$file}'></script>";
        }

        return "<link rel='stylesheet' href='{$devBase}/{$file}'>";
    }

    $manifestPath = $root . '/public/build/.vite/manifest.json';

    if (!file_exists($manifestPath)) {
        return "<!-- vite manifest not found, run npm run build -->";
    }

    $manifest = json_decode(file_get_contents($manifestPath), true);

    if (!isset($manifest[$file])) {
        return "<!-- asset $file not found -->";
    }

    $asset = $manifest[$file]['file'];

    if (str_ends_with($asset, '.js')) {
        return "<script type='module' src='/build/{$asset}'></script>";
    }

    return "<link rel='stylesheet' href='/build/{$asset}'>";
}

