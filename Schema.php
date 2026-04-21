<?php

class Schema
{
    public static function create($table, callable $callback)
    {
        $blueprint = new Blueprint($table);
        $callback($blueprint);
        $blueprint->build();
    }

    public static function drop($table)
    {
        $pdo = Database::connect();
        $pdo->exec("DROP TABLE IF EXISTS `$table`");
        echo "Dropped table: $table\n";
    }
}
