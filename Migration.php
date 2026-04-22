<?php

class Migration
{
    protected static function migrationsDir(): string
    {
        return rtrim(framework_project_root(), '/') . '/migrations/';
    }

    public static function make($name)
    {
        if (!$name) {
            echo "Please provide a migration name.\n";
            return;
        }

        $timestamp = date('Y_m_d_His');
        $migrationsDir = self::migrationsDir();
        $fileName = $migrationsDir . "{$timestamp}_{$name}.php";

        if (!is_dir($migrationsDir)) {
            mkdir($migrationsDir, 0777, true);
        }

        $className = str_replace(' ', '', ucwords(str_replace('_', ' ', $name)));

        $stub = <<<PHP
<?php

return new class {
    public function up() {
        Schema::create('$name', function(\$table) {
            \$table->id();
            \$table->string('name');
            \$table->timestamps();
        });
    }

    public function down() {
        Schema::drop('$name');
    }
};
PHP;


        file_put_contents($fileName, $stub);
        echo "Migration created: {$fileName}\n";
    }

    public static function migrate()
    {
        $pdo = Database::connect();

        // Maak migrations tabel aan als die niet bestaat
        $pdo->exec("CREATE TABLE IF NOT EXISTS migrations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            migration VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");

        $applied = $pdo->query("SELECT migration FROM migrations")->fetchAll(PDO::FETCH_COLUMN);

        foreach (glob(self::migrationsDir() . '*.php') as $file) {
            $migrationName = basename($file);

            if (in_array($migrationName, $applied)) continue;

            $migration = include $file;
            $migration->up();

            $stmt = $pdo->prepare("INSERT INTO migrations (migration) VALUES (?)");
            $stmt->execute([$migrationName]);

            echo "Migrated: {$migrationName}\n";
        }
    }

    public static function rollback()
    {
        $pdo = Database::connect();

        $stmt = $pdo->query("SELECT migration FROM migrations ORDER BY id DESC LIMIT 1");
        $lastMigration = $stmt->fetchColumn();

        if (!$lastMigration) {
            echo "No migrations to rollback.\n";
            return;
        }

        $migration = include self::migrationsDir() . $lastMigration;
        $migration->down();

        $stmt = $pdo->prepare("DELETE FROM migrations WHERE migration = ?");
        $stmt->execute([$lastMigration]);


        echo "Rolled back: {$lastMigration}\n";
    }
}
