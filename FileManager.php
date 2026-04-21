<?php

class FileManager
{
    public static function read(string $path): string
    {
        self::assertReadable($path);

        $contents = file_get_contents($path);

        if ($contents === false) {
            throw new RuntimeException('Kan bestand niet lezen: ' . $path);
        }

        return $contents;
    }

    public static function lines(string $path): array
    {
        $contents = self::read($path);

        if ($contents === '') {
            return [];
        }

        return preg_split('/\R/', rtrim($contents, "\r\n")) ?: [];
    }

    public static function write(string $path, string $contents): void
    {
        self::ensureDirectory($path);

        if (file_put_contents($path, $contents, LOCK_EX) === false) {
            throw new RuntimeException('Kan bestand niet schrijven: ' . $path);
        }
    }

    public static function append(string $path, string $contents): void
    {
        self::ensureDirectory($path);

        if (file_put_contents($path, $contents, FILE_APPEND | LOCK_EX) === false) {
            throw new RuntimeException('Kan bestand niet aanvullen: ' . $path);
        }
    }

    public static function replace(string $path, string $search, string $replace): int
    {
        $contents = self::read($path);
        $count = 0;
        $updated = str_replace($search, $replace, $contents, $count);

        if ($count === 0) {
            return 0;
        }

        self::write($path, $updated);

        return $count;
    }

    public static function insertAtLine(string $path, int $lineNumber, string $content): void
    {
        $lines = self::lines($path);
        $index = max(0, min($lineNumber - 1, count($lines)));
        array_splice($lines, $index, 0, [$content]);

        self::write($path, self::joinLines($lines));
    }

    public static function insertBefore(string $path, string $needle, string $content): bool
    {
        return self::insertRelativeToMatch($path, $needle, $content, 'before');
    }

    public static function insertAfter(string $path, string $needle, string $content): bool
    {
        return self::insertRelativeToMatch($path, $needle, $content, 'after');
    }

    public static function readCsv(string $path, string $delimiter = ',', string $enclosure = '"', string $escape = '\\'): array
    {
        self::assertReadable($path);

        $handle = fopen($path, 'r');

        if ($handle === false) {
            throw new RuntimeException('Kan CSV niet openen: ' . $path);
        }

        try {
            $rows = [];

            while (($row = fgetcsv($handle, 0, $delimiter, $enclosure, $escape)) !== false) {
                if ($row === [null]) {
                    continue;
                }

                $rows[] = $row;
            }

            return $rows;
        } finally {
            fclose($handle);
        }
    }

    public static function readCsvAssoc(string $path, string $delimiter = ',', string $enclosure = '"', string $escape = '\\'): array
    {
        $rows = self::readCsv($path, $delimiter, $enclosure, $escape);

        if ($rows === []) {
            return [];
        }

        $header = array_map('trim', array_shift($rows));

        return array_values(array_filter(array_map(function (array $row) use ($header) {
            $row = array_pad($row, count($header), null);

            return array_combine($header, array_slice($row, 0, count($header))) ?: [];
        }, $rows)));
    }

    public static function writeCsv(string $path, array $rows, string $delimiter = ',', string $enclosure = '"', string $escape = '\\'): void
    {
        self::ensureDirectory($path);

        $handle = fopen($path, 'w');

        if ($handle === false) {
            throw new RuntimeException('Kan CSV niet schrijven: ' . $path);
        }

        try {
            foreach ($rows as $row) {
                if (!is_array($row)) {
                    throw new InvalidArgumentException('CSV-rijen moeten arrays zijn.');
                }

                fputcsv($handle, $row, $delimiter, $enclosure, $escape);
            }
        } finally {
            fclose($handle);
        }
    }

    public static function appendCsvRow(string $path, array $row, string $delimiter = ',', string $enclosure = '"', string $escape = '\\'): void
    {
        self::ensureDirectory($path);

        $handle = fopen($path, 'a');

        if ($handle === false) {
            throw new RuntimeException('Kan CSV niet openen voor aanvullen: ' . $path);
        }

        try {
            if (fputcsv($handle, $row, $delimiter, $enclosure, $escape) === false) {
                throw new RuntimeException('Kan CSV-rij niet toevoegen: ' . $path);
            }
        } finally {
            fclose($handle);
        }
    }

    private static function insertRelativeToMatch(string $path, string $needle, string $content, string $position): bool
    {
        $lines = self::lines($path);

        foreach ($lines as $index => $line) {
            if (strpos($line, $needle) === false) {
                continue;
            }

            $insertIndex = $position === 'before' ? $index : $index + 1;
            array_splice($lines, $insertIndex, 0, [$content]);

            self::write($path, self::joinLines($lines));

            return true;
        }

        return false;
    }

    private static function ensureDirectory(string $path): void
    {
        $directory = dirname($path);

        if ($directory === '.' || is_dir($directory)) {
            return;
        }

        if (!mkdir($directory, 0777, true) && !is_dir($directory)) {
            throw new RuntimeException('Kan map niet aanmaken: ' . $directory);
        }
    }

    private static function assertReadable(string $path): void
    {
        if (!is_file($path) || !is_readable($path)) {
            throw new RuntimeException('Bestand niet leesbaar: ' . $path);
        }
    }

    private static function joinLines(array $lines): string
    {
        return implode(PHP_EOL, $lines) . PHP_EOL;
    }
}