<?php
declare(strict_types=1);

function current_user_id(): ?int
{
    $uid = $_SESSION['user_id'] ?? null;
    if ($uid === null || $uid === '') {
        return null;
    }
    if (!is_int($uid)) {
        if (!is_numeric($uid)) {
            return null;
        }
        $uid = (int)$uid;
    }
    return $uid > 0 ? $uid : null;
}

function is_logged_in(): bool
{
    return current_user_id() !== null;
}

function require_login(): int
{
    $uid = current_user_id();
    if ($uid === null) {
        error_response('Authentication required', 401);
    }
    return $uid;
}
