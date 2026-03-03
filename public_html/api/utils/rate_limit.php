<?php
declare(strict_types=1);

function rate_limit_dir(): string
{
    $base = rtrim(storage_base(), '/');
    $dir = $base . '/ratelimit';
    if (!is_dir($dir)) {
        mkdir($dir, 0770, true);
    }
    return $dir;
}

function rate_limit_or_429(string $bucket, int $maxRequests, int $windowSeconds, ?float $minIntervalSeconds = null): void
{
    $dir = rate_limit_dir();
    $file = $dir . '/' . hash('sha256', $bucket) . '.json';

    $now = microtime(true);

    $fh = fopen($file, 'c+');
    if ($fh === false) {
        // If we cannot persist, fail open rather than DoS legitimate traffic.
        return;
    }

    try {
        flock($fh, LOCK_EX);
        $raw = stream_get_contents($fh);
        $state = [];
        if ($raw !== false && trim($raw) !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $state = $decoded;
            }
        }

        $last = isset($state['last']) ? (float)$state['last'] : null;
        if ($minIntervalSeconds !== null && $last !== null && ($now - $last) < $minIntervalSeconds) {
            error_response('Too many requests', 429);
        }

        $events = [];
        if (isset($state['events']) && is_array($state['events'])) {
            foreach ($state['events'] as $t) {
                $tf = (float)$t;
                if (($now - $tf) <= $windowSeconds) {
                    $events[] = $tf;
                }
            }
        }

        if (count($events) >= $maxRequests) {
            error_response('Too many requests', 429);
        }

        $events[] = $now;
        $state = [
            'last' => $now,
            'events' => $events,
        ];

        $out = json_encode($state, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($out !== false) {
            ftruncate($fh, 0);
            rewind($fh);
            fwrite($fh, $out);
        }
    } finally {
        flock($fh, LOCK_UN);
        fclose($fh);
    }
}
