<?php

namespace App\Support;

/**
 * Vite hashed URLs from bootstrap/build-manifest.json, which lives outside the
 * docroot so the manifest is not web-accessible.
 */
final class BuildManifest {
    /** @var array<string, string>|null */
    private static ?array $assets = null;

    /**
     * @return array<string, string>
     */
    public static function assets(): array {
        if (self::$assets !== null) {
            return self::$assets;
        }

        $path = base_path('bootstrap/build-manifest.json');
        if (!is_file($path)) {
            return self::$assets = [];
        }

        $decoded = json_decode((string) file_get_contents($path), true);

        return self::$assets = is_array($decoded) ? $decoded : [];
    }
}
