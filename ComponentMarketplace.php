<?php

class ComponentMarketplace
{
    private string $apiUrl;
    private string $targetDirectory;

    public function __construct(string $apiUrl, string $targetDirectory)
    {
        $this->apiUrl = $apiUrl;
        $this->targetDirectory = rtrim($targetDirectory, '/\\');
    }

    public function fetchComponents(): array
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => "Accept: application/json\r\nUser-Agent: NDG-CLI/1.0",
                'timeout' => 20,
                'ignore_errors' => true,
            ],
        ]);

        $response = @file_get_contents($this->apiUrl, false, $context);

        if ($response === false) {
            throw new RuntimeException('Kan API niet bereiken: ' . $this->apiUrl);
        }

        $statusCode = $this->extractStatusCode($http_response_header ?? []);

        if ($statusCode < 200 || $statusCode >= 300) {
            throw new RuntimeException('API gaf HTTP ' . $statusCode . '. Response: ' . $response);
        }

        $decoded = json_decode($response, true);

        if (!is_array($decoded)) {
            throw new RuntimeException('API response is geen geldige JSON.');
        }

        $components = $decoded['components'] ?? [];

        if (!is_array($components)) {
            throw new RuntimeException('API response mist een geldige components-array.');
        }

        return $components;
    }

    public function search(string $query): array
    {
        $components = $this->fetchComponents();
        $query = mb_strtolower(trim($query));

        if ($query === '') {
            return $components;
        }

        return array_values(array_filter($components, function ($component) use ($query) {
            if (!is_array($component)) {
                return false;
            }

            $name = mb_strtolower((string) ($component['name'] ?? ''));
            $description = mb_strtolower((string) ($component['description'] ?? ''));

            return str_contains($name, $query) || str_contains($description, $query);
        }));
    }

    public function install(string $componentName): string
    {
        $components = $this->fetchComponents();

        $selected = null;
        foreach ($components as $component) {
            if (!is_array($component)) {
                continue;
            }

            if (($component['name'] ?? null) === $componentName) {
                $selected = $component;
                break;
            }
        }

        if ($selected === null) {
            throw new RuntimeException('Component niet gevonden: ' . $componentName);
        }

        $downloadUrl = $selected['download'] ?? null;

        if (!$downloadUrl || !is_string($downloadUrl)) {
            throw new RuntimeException('Component mist een geldige download-url.');
        }

        $fileContent = @file_get_contents($downloadUrl);

        if ($fileContent === false) {
            throw new RuntimeException('Download mislukt: ' . $downloadUrl);
        }

        if (!is_dir($this->targetDirectory) && !mkdir($this->targetDirectory, 0777, true) && !is_dir($this->targetDirectory)) {
            throw new RuntimeException('Kan map niet aanmaken: ' . $this->targetDirectory);
        }

        $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '', $componentName) ?: 'component';
        $targetPath = $this->targetDirectory . DIRECTORY_SEPARATOR . $safeName . '.php';

        if (file_put_contents($targetPath, $fileContent) === false) {
            throw new RuntimeException('Kan component niet opslaan op: ' . $targetPath);
        }

        return $targetPath;
    }

    private function extractStatusCode(array $headers): int
    {
        if (!isset($headers[0])) {
            return 0;
        }

        if (preg_match('/\s(\d{3})\s/', $headers[0], $matches)) {
            return (int) $matches[1];
        }

        return 0;
    }
}
