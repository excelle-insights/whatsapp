<?php
declare(strict_types=1);

namespace ExcelleInsights\WhatsApp\Config;

use ExcelleInsights\WhatsApp\Support\EnvLoader;

final class WhatsAppConfig
{
    private static bool $booted = false;

    private static function boot(): void
    {
        if (!self::$booted) {
            EnvLoader::load();
            self::$booted = true;
        }
    }

    public static function graphApi(): string
    {
        self::boot();
        return self::env('WHATSAPP_GRAPH_API');
    }

    public static function apiVersion(): string
    {
        self::boot();
        return self::env('WHATSAPP_API_VERSION');
    }

    public static function appId(): string
    {
        self::boot();
        return self::env('WHATSAPP_APP_ID');
    }

    public static function appSecret(): string
    {
        self::boot();
        return self::env('WHATSAPP_APP_SECRET');
    }

    public static function redirectUri(): string
    {
        self::boot();
        return self::env('WHATSAPP_REDIRECT_URI');
    }

    public static function tablePrefix(): string
    {
        self::boot();
        return $_ENV['WHATSAPP_TABLE_PREFIX'] ?? 'whatsapp';
    }

    private static function env(string $key): string
    {
        $value = $_ENV[$key] ?? getenv($key) ?? null;

        if (!$value) {
            throw new \RuntimeException("Missing WhatsApp env variable: {$key}");
        }

        return $value;
    }
}
