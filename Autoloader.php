<?php

class Autoloader
{
    public static function register()
    {
        spl_autoload_register(function ($class) {

            $basePath = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 2);

            $folders = [
                $basePath.'/app/Controllers/',
                $basePath.'/app/Models/',
                $basePath.'/app/Services/',
                $basePath.'/app/Support/',
                $basePath.'/core/',
                __DIR__.'/'
            ];

            foreach ($folders as $folder) {

                $file = $folder.$class.'.php';

                if (file_exists($file)) {
                    require $file;
                    return;
                }
            }
        });
    }
}