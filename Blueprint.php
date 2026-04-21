<?php

class Blueprint
{
    protected $pdo;
    protected $table;
    protected $columns = [];

    public function __construct($table)
    {
        $this->pdo = Database::connect();
        $this->table = $table;
    }

    public function id()
    {
        $this->columns[] = "`id` INT AUTO_INCREMENT PRIMARY KEY";
        return $this;
    }

    public function string($name, $length = 255)
    {
        $this->columns[] = "`$name` VARCHAR($length)";
        return $this;
    }

    public function text($name)
    {
        $this->columns[] = "`$name` TEXT";
        return $this;
    }

    public function integer($name)
    {
        $this->columns[] = "`$name` INT";
        return $this;
    }

    public function boolean($name)
    {
        $this->columns[] = "`$name` TINYINT(1)";
        return $this;
    }

    public function timestamps()
    {
        $this->columns[] = "`created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP";
        $this->columns[] = "`updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP";
        return $this;
    }

    public function unique($column)
    {
        $this->columns[] = "UNIQUE (`$column`)";
        return $this;
    }

    public function build()
    {
        $sql = "CREATE TABLE IF NOT EXISTS `{$this->table}` ("
             . implode(", ", $this->columns)
             . ") ENGINE=InnoDB";

        $this->pdo->exec($sql);
        echo "Created table: {$this->table}\n";
    }
}
