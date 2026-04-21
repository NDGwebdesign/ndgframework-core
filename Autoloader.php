<?php

class Autoloader
{
    public static function register()
    {
        spl_autoload_register(function ($class) {

            $folders = [
                __DIR__.'/../app/Controllers/',
                __DIR__.'/../app/Models/',
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