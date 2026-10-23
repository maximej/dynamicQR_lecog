<?php
namespace App\Controllers;

/**
 * Stores anonymous redirect counters and custom slug mappings outside the web root.
 * No IP address, user agent, referrer, cookie, or other visitor identifier is stored.
 */
class QrDataStore
{
    private string $directory;
    private string $statsFile;
    private string $slugsFile;

    private const RESERVED_SLUGS = [
        'admin', 'api', 'config', 'controllers', 'create', 'delete-qr',
        'edit-redirect', 'generate', 'index', 'list', 'login', 'logout',
        'public', 'qrcodes', 'redirect', 'renderer', 'userlist', 'vendor',
        'webmail', 'well-known',
    ];

    public function __construct(?string $directory = null)
    {
        $this->directory = $directory ?: dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'private' . DIRECTORY_SEPARATOR . 'qr-data';
        $this->statsFile = $this->directory . DIRECTORY_SEPARATOR . 'stats.json';
        $this->slugsFile = $this->directory . DIRECTORY_SEPARATOR . 'slugs.json';
    }

    public static function normalizeSlug(string $slug): string
    {
        return strtolower(trim($slug));
    }

    public static function validateSlug(string $slug): void
    {
        if (!preg_match('/^[a-z0-9][a-z0-9-]{2,63}$/', $slug)) {
            throw new \InvalidArgumentException('The custom URL must contain 3 to 64 lowercase letters, numbers, or hyphens.');
        }

        if (str_ends_with($slug, '-') || in_array($slug, self::RESERVED_SLUGS, true)) {
            throw new \InvalidArgumentException('This custom URL is reserved. Please choose another one.');
        }
    }

    public function registerSlug(string $slug, string $qrId): void
    {
        self::validateSlug($slug);
        $this->updateJson($this->slugsFile, function (array $slugs) use ($slug, $qrId): array {
            if (isset($slugs[$slug]) && $slugs[$slug] !== $qrId) {
                throw new \RuntimeException('This custom URL is already in use.');
            }
            $slugs[$slug] = $qrId;
            return $slugs;
        });
    }

    public function resolveSlug(string $slug): ?string
    {
        $slug = self::normalizeSlug($slug);
        $slugs = $this->readJson($this->slugsFile);
        return isset($slugs[$slug]) && is_string($slugs[$slug]) ? $slugs[$slug] : null;
    }

    public function releaseSlug(string $slug, string $qrId): void
    {
        $this->updateJson($this->slugsFile, function (array $slugs) use ($slug, $qrId): array {
            if (($slugs[$slug] ?? null) === $qrId) {
                unset($slugs[$slug]);
            }
            return $slugs;
        });
    }

    public function recordView(string $qrId): array
    {
        $updated = [];
        $this->updateJson($this->statsFile, function (array $stats) use ($qrId, &$updated): array {
            $current = isset($stats[$qrId]) && is_array($stats[$qrId]) ? $stats[$qrId] : [];
            $updated = [
                'views' => max(0, (int)($current['views'] ?? 0)) + 1,
                'last_viewed_at' => gmdate('Y-m-d\TH:i:s\Z'),
            ];
            $stats[$qrId] = $updated;
            return $stats;
        });
        return $updated;
    }

    public function getStatsForIds(array $qrIds): array
    {
        $allStats = $this->readJson($this->statsFile);
        $result = [];
        foreach ($qrIds as $qrId) {
            $entry = isset($allStats[$qrId]) && is_array($allStats[$qrId]) ? $allStats[$qrId] : [];
            $result[$qrId] = [
                'views' => max(0, (int)($entry['views'] ?? 0)),
                'last_viewed_at' => is_string($entry['last_viewed_at'] ?? null) ? $entry['last_viewed_at'] : null,
            ];
        }
        return $result;
    }

    public function deleteQrData(string $qrId): void
    {
        $this->updateJson($this->statsFile, function (array $stats) use ($qrId): array {
            unset($stats[$qrId]);
            return $stats;
        });
        $this->updateJson($this->slugsFile, function (array $slugs) use ($qrId): array {
            return array_filter($slugs, static fn($mappedId): bool => $mappedId !== $qrId);
        });
    }

    private function ensureDirectory(): void
    {
        if (!is_dir($this->directory) && !mkdir($this->directory, 0700, true) && !is_dir($this->directory)) {
            throw new \RuntimeException('Unable to create the private QR data directory.');
        }
    }

    private function readJson(string $path): array
    {
        if (!is_file($path)) {
            return [];
        }
        $handle = fopen($path, 'r');
        if ($handle === false) {
            throw new \RuntimeException('Unable to read private QR data.');
        }
        try {
            if (!flock($handle, LOCK_SH)) {
                throw new \RuntimeException('Unable to lock private QR data.');
            }
            $contents = stream_get_contents($handle);
            flock($handle, LOCK_UN);
        } finally {
            fclose($handle);
        }
        return $this->decodeJson($contents ?: '');
    }

    private function updateJson(string $path, callable $callback): void
    {
        $this->ensureDirectory();
        $handle = fopen($path, 'c+');
        if ($handle === false) {
            throw new \RuntimeException('Unable to open private QR data.');
        }
        @chmod($path, 0600);
        try {
            if (!flock($handle, LOCK_EX)) {
                throw new \RuntimeException('Unable to lock private QR data.');
            }
            rewind($handle);
            $data = $this->decodeJson(stream_get_contents($handle) ?: '');
            $updated = $callback($data);
            $json = json_encode($updated, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . PHP_EOL;
            rewind($handle);
            if (!ftruncate($handle, 0) || fwrite($handle, $json) === false || !fflush($handle)) {
                throw new \RuntimeException('Unable to save private QR data.');
            }
            flock($handle, LOCK_UN);
        } finally {
            fclose($handle);
        }
    }

    private function decodeJson(string $contents): array
    {
        if (trim($contents) === '') {
            return [];
        }
        $decoded = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($decoded)) {
            throw new \RuntimeException('Invalid private QR data format.');
        }
        return $decoded;
    }
}
