<?php
declare(strict_types=1);

function request_method(): string
{
    return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
}

function require_method(string $method): void
{
    if (request_method() !== strtoupper($method)) {
        error_response('Method not allowed', 405);
    }
}

function get_json_body(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === false || trim($raw) === '') {
        return [];
    }

    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        error_response('Invalid JSON body', 400);
    }

    return $decoded;
}

function param_string(array $src, string $key, int $maxLen = 200): ?string
{
    if (!array_key_exists($key, $src)) {
        return null;
    }
    $val = trim((string)$src[$key]);
    if ($val === '') {
        return null;
    }
    if (mb_strlen($val) > $maxLen) {
        error_response('Invalid parameter: ' . $key, 400);
    }
    return $val;
}

function param_int(array $src, string $key, ?int $min = null, ?int $max = null): ?int
{
    if (!array_key_exists($key, $src)) {
        return null;
    }
    if ($src[$key] === '' || $src[$key] === null) {
        return null;
    }

    if (is_int($src[$key])) {
        $val = $src[$key];
    } else {
        if (!is_numeric($src[$key]) || (string)(int)$src[$key] !== (string)trim((string)$src[$key])) {
            error_response('Invalid parameter: ' . $key, 400);
        }
        $val = (int)$src[$key];
    }

    if ($min !== null && $val < $min) {
        error_response('Invalid parameter: ' . $key, 400);
    }
    if ($max !== null && $val > $max) {
        error_response('Invalid parameter: ' . $key, 400);
    }

    return $val;
}

function param_float(array $src, string $key, ?float $min = null, ?float $max = null): ?float
{
    if (!array_key_exists($key, $src)) {
        return null;
    }
    if ($src[$key] === '' || $src[$key] === null) {
        return null;
    }

    if (!is_numeric($src[$key])) {
        error_response('Invalid parameter: ' . $key, 400);
    }

    $val = (float)$src[$key];

    if ($min !== null && $val < $min) {
        error_response('Invalid parameter: ' . $key, 400);
    }
    if ($max !== null && $val > $max) {
        error_response('Invalid parameter: ' . $key, 400);
    }

    return $val;
}

function client_ip(): string
{
    // Intentionally do not trust X-Forwarded-For unless your infra guarantees it.
    return (string)($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
}
