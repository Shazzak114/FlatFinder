<?php

require_once __DIR__ . '/bootstrap.php';

if (current_admin()) {
    header('Location: /admin/dashboard.php');
    exit;
}

header('Location: /admin/login.php');
exit;
