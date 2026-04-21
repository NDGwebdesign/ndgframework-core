<?php

class Model
{
    protected static $table;

    public static function getAllRecords(): array
    {
        $pdo = Database::connect();
        $table = static::getTableName();

        $stmt = $pdo->query("SELECT * FROM {$table}");

        return $stmt->fetchAll();
    }

    public static function findRecordById($id, string $idColumn = 'id'): ?array
    {
        $pdo = Database::connect();
        $table = static::getTableName();
        $idColumn = static::quoteIdentifier($idColumn);

        $stmt = $pdo->prepare("SELECT * FROM {$table} WHERE {$idColumn} = ? LIMIT 1");
        $stmt->execute([$id]);

        $record = $stmt->fetch();

        return $record ?: null;
    }

    public static function addRecord(array $data)
    {
        if (empty($data)) {
            throw new InvalidArgumentException('addRecord verwacht minimaal 1 veld.');
        }

        $pdo = Database::connect();
        $table = static::getTableName();

        $keys = array_keys($data);
        $values = array_values($data);

        $columns = implode(',', array_map([static::class, 'quoteIdentifier'], $keys));
        $placeholders = implode(',', array_fill(0, count($keys), '?'));

        $stmt = $pdo->prepare(
            "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})"
        );

        $stmt->execute($values);

        return $pdo->lastInsertId();
    }

    public static function updateRecord($id, array $data, string $idColumn = 'id'): bool
    {
        if (empty($data)) {
            return false;
        }

        $pdo = Database::connect();
        $table = static::getTableName();
        $idColumn = static::quoteIdentifier($idColumn);

        $setClauses = [];
        $values = [];

        foreach ($data as $column => $value) {
            $setClauses[] = static::quoteIdentifier((string) $column) . ' = ?';
            $values[] = $value;
        }

        $values[] = $id;

        $sql = "UPDATE {$table} SET " . implode(', ', $setClauses) . " WHERE {$idColumn} = ?";
        $stmt = $pdo->prepare($sql);

        return $stmt->execute($values);
    }

    public static function deleteRecord($id, string $idColumn = 'id'): bool
    {
        $pdo = Database::connect();
        $table = static::getTableName();
        $idColumn = static::quoteIdentifier($idColumn);

        $stmt = $pdo->prepare("DELETE FROM {$table} WHERE {$idColumn} = ?");

        return $stmt->execute([$id]);
    }

    public static function all(): array
    {
        return static::getAllRecords();
    }

    public static function find($id): ?array
    {
        return static::findRecordById($id);
    }

    public static function create($data)
    {
        return static::addRecord($data);
    }

    protected static function getTableName(): string
    {
        if (empty(static::$table)) {
            throw new RuntimeException('Model table niet ingesteld.');
        }

        return static::quoteIdentifier(static::$table);
    }

    protected static function quoteIdentifier(string $identifier): string
    {
        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $identifier)) {
            throw new InvalidArgumentException("Ongeldige SQL identifier: {$identifier}");
        }

        return "`{$identifier}`";
    }
}