<?php

require_once __DIR__ . '/../includes/db.php';

function admin_boot(): void
{
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');

    if (PHP_VERSION_ID >= 70300) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/admin',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    } else {
        session_set_cookie_params(0, '/admin; samesite=Lax', '', $secure, true);
    }

    session_name('bk_admin');
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    if (!isset($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }

    // Opportunistic job: publish listings that are due.
    publish_due_listings(db());
}

function e($v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrf_token(): string
{
    return (string)($_SESSION['csrf'] ?? '');
}

function csrf_require(): void
{
    $token = $_POST['csrf'] ?? '';
    if (!$token || !hash_equals(csrf_token(), (string)$token)) {
        http_response_code(400);
        echo 'Bad CSRF token.';
        exit;
    }
}

function flash_set(string $type, string $msg): void
{
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

function flash_get(): ?array
{
    if (empty($_SESSION['flash'])) return null;
    $f = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $f;
}

function current_admin(): ?array
{
    if (empty($_SESSION['admin_user_id'])) return null;

    $pdo = db();
    $stmt = $pdo->prepare('SELECT id, username, is_active FROM admin_users WHERE id = ?');
    $stmt->execute([$_SESSION['admin_user_id']]);
    $row = $stmt->fetch();
    if (!$row || (int)$row['is_active'] !== 1) return null;

    return $row;
}

function require_admin(): array
{
    $admin = current_admin();
    if ($admin) return $admin;

    $next = $_SERVER['REQUEST_URI'] ?? '/admin/dashboard.php';
    header('Location: /admin/login.php?next=' . urlencode($next));
    exit;
}

function int_param($v, int $default = 0): int
{
    if ($v === null || $v === '') return $default;
    $i = (int)$v;
    return $i;
}

function page_param(string $key = 'page'): int
{
    $p = int_param($_GET[$key] ?? 1, 1);
    if ($p < 1) $p = 1;
    return $p;
}

function per_page_param(int $default = 20, int $max = 100): int
{
    $pp = int_param($_GET['per_page'] ?? $default, $default);
    if ($pp < 1) $pp = 1;
    if ($pp > $max) $pp = $max;
    return $pp;
}

function pagination_offset(int $page, int $perPage): int
{
    return ($page - 1) * $perPage;
}

function build_url(string $path, array $params = []): string
{
    $qs = http_build_query($params);
    if ($qs === '') return $path;
    return $path . '?' . $qs;
}

function resolve_storage_path(string $relativePath): ?string
{
    $relativePath = ltrim($relativePath, '/');
    if ($relativePath === '' || strpos($relativePath, "\0") !== false) return null;
    if (preg_match('#(^|/)\.\.(?:/|$)#', $relativePath)) return null;

    $base = storage_base();
    $baseReal = realpath($base);
    if ($baseReal === false) return null;

    $abs = $baseReal . DIRECTORY_SEPARATOR . $relativePath;

    $dirReal = realpath(dirname($abs));
    if ($dirReal === false) return null;

    $prefix = rtrim($baseReal, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    if (strpos(rtrim($dirReal, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR, $prefix) !== 0) return null;

    return $abs;
}

function render_pagination(string $path, int $page, int $perPage, int $rowCount, array $extraParams = []): void
{
    $hasPrev = $page > 1;
    $hasNext = $rowCount === $perPage;

    if (!$hasPrev && !$hasNext) return;

    echo '<nav class="pagination" aria-label="Pagination">';

    if ($hasPrev) {
        $params = array_merge($extraParams, ['page' => $page - 1, 'per_page' => $perPage]);
        echo '<a class="btn small ghost" href="' . e(build_url($path, $params)) . '">Prev</a>';
    } else {
        echo '<span class="btn small ghost disabled">Prev</span>';
    }

    echo '<span class="page">Page ' . e($page) . '</span>';

    if ($hasNext) {
        $params = array_merge($extraParams, ['page' => $page + 1, 'per_page' => $perPage]);
        echo '<a class="btn small ghost" href="' . e(build_url($path, $params)) . '">Next</a>';
    } else {
        echo '<span class="btn small ghost disabled">Next</span>';
    }

    echo '</nav>';
}

function publish_due_listings(PDO $pdo): void
{
    $pdo->exec("UPDATE listings SET status = 'published' WHERE status = 'approved' AND publish_at IS NOT NULL AND publish_at <= NOW()");
}

admin_boot();
