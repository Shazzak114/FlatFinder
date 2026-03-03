<?php
declare(strict_types=1);

function cache_dir(string $namespace): string
{
    $base = rtrim(storage_base(), '/');
    $dir = $base . '/cache/' . $namespace;
    if (!is_dir($dir)) {
        mkdir($dir, 0770, true);
    }
    return $dir;
}

function cache_key(string $namespace, array $parts): string
{
    $payload = json_encode($parts, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    return $namespace . ':' . hash('sha256', $payload === false ? '' : $payload);
}

function cache_get(string $namespace, string $key, int $ttlSeconds): ?array
{
    $dir = cache_dir($namespace);
    $file = $dir . '/' . hash('sha256', $key) . '.json';

    if (!is_file($file)) {
        return null;
    }

    $mtime = filemtime($file);
    if ($mtime === false || (time() - $mtime) > $ttlSeconds) {
        return null;
    }

    $raw = file_get_contents($file);
    if ($raw === false) {
        return null;
    }

    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : null;
}

function cache_put(string $namespace, string $key, array $value): void
{
    $dir = cache_dir($namespace);
    $file = $dir . '/' . hash('sha256', $key) . '.json';
    $tmp = $file . '.' . bin2hex(random_bytes(6)) . '.tmp';

    $raw = json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($raw === false) {
        return;
    }

    file_put_contents($tmp, $raw, LOCK_EX);
    rename($tmp, $file);
}
