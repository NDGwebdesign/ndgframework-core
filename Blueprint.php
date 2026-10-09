<?php

class Blueprint
{
    protected $pdo;
    protected $table;
    protected $columns = [];
    protected $columnNames = [];

    public function __construct($table)
    {
        $this->pdo = Database::connect();
        $this->table = $table;
    }

    public function id()
    {
        $this->columns[] = "`id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY";
        $this->columnNames[] = 'id';
        return $this;
    }

    public function string($name, $length = 255)
    {
        $this->columns[] = "`$name` VARCHAR($length)";
        $this->columnNames[] = $name;
        return new ColumnDefinition($this, count($this->columns) - 1);
    }

    public function text($name)
    {
        $this->columns[] = "`$name` TEXT";
        $this->columnNames[] = $name;
        return new ColumnDefinition($this, count($this->columns) - 1);
    }

    public function integer($name)
    {
        $this->columns[] = "`$name` INT";
        $this->columnNames[] = $name;
        return new ColumnDefinition($this, count($this->columns) - 1);
    }

    public function foreignId($name)
    {
        $this->columns[] = "`$name` INT UNSIGNED";
        $this->columnNames[] = $name;
        return new ColumnDefinition($this, count($this->columns) - 1);
    }

    public function foreign($column, $references, $on, $onDelete = null)
    {
        $constraint = "FOREIGN KEY (`$column`) REFERENCES `$on`(`$references`)";
        if ($onDelete) {
            $constraint .= " ON DELETE $onDelete";
        }
        $this->columns[] = $constraint;
        $this->columnNames[] = null;
        return $this;
    }

    public function boolean($name)
    {
        $this->columns[] = "`$name` TINYINT(1)";
        $this->columnNames[] = $name;
        return new ColumnDefinition($this, count($this->columns) - 1);
    }

    public function datetime($name)
    {
        $this->columns[] = "`$name` DATETIME";
        $this->columnNames[] = $name;
        return new ColumnDefinition($this, count($this->columns) - 1);
    }

    public function timestamps()
    {
        $this->columns[] = "`created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP";
        $this->columnNames[] = 'created_at';
        $this->columns[] = "`updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP";
        $this->columnNames[] = 'updated_at';
        return $this;
    }

    public function columnName($index)
    {
        return $this->columnNames[$index];
    }

    public function makeNullable($index)
    {
        $this->columns[$index] .= ' NULL';
    }

    public function setDefault($index, $value)
    {
        $default = is_string($value) ? "'" . addslashes($value) . "'" : (int) $value;
        $this->columns[$index] .= " DEFAULT $default";
    }

    public function setForeignKeyDelete($column, $action)
    {
        foreach ($this->columns as $index => $definition) {
            if (str_contains($definition, "FOREIGN KEY (`$column`)")) {
                $this->columns[$index] .= " ON DELETE $action";
                return;
            }
        }
    }

    public function unique($column = null)
    {
        $column ??= end($this->columnNames);
        $columns = is_array($column) ? $column : [$column];
        $quotedColumns = implode(', ', array_map(fn($name) => "`$name`", $columns));
        $this->columns[] = "UNIQUE ($quotedColumns)";
        $this->columnNames[] = null;
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

class ColumnDefinition
{
    public function __construct(protected Blueprint $blueprint, protected int $index) {}

    public function nullable()
    {
        $this->blueprint->makeNullable($this->index);
        return $this;
    }

    public function default($value)
    {
        $this->blueprint->setDefault($this->index, $value);
        return $this;
    }

    public function constrained($table = null)
    {
        $table ??= str_ends_with($this->blueprint->columnName($this->index), '_id')
            ? substr($this->blueprint->columnName($this->index), 0, -3) . 's'
            : $this->blueprint->columnName($this->index);
        $this->blueprint->foreign(
            $this->blueprint->columnName($this->index),
            'id',
            $table
        );
        return $this;
    }

    public function onDelete($action)
    {
        $this->blueprint->setForeignKeyDelete($this->blueprint->columnName($this->index), $action);
        return $this;
    }

    public function unique()
    {
        $this->blueprint->unique($this->blueprint->columnName($this->index));
        return $this;
    }
}
