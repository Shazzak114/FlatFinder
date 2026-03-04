<?php
declare(strict_types=1);

require_once __DIR__ . '/utils/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

function get_param(string $key, $default = null) {
    return $_GET[$key] ?? $default;
}

// Bounds filtering: accept either bbox=south,west,north,east or separate params.
$bbox = get_param('bbox');
if (is_string($bbox) && $bbox !== '') {
    $parts = array_map('trim', explode(',', $bbox));
    if (count($parts) === 4) {
        $south = (float)$parts[0];
        $west = (float)$parts[1];
        $north = (float)$parts[2];
        $east = (float)$parts[3];
    }
}

if (!isset($south)) {
    $south = (float)get_param('south', -90);
    $west = (float)get_param('west', -180);
    $north = (float)get_param('north', 90);
    $east = (float)get_param('east', 180);
}

$cats = [];
foreach (['category', 'categories', 'category_key'] as $k) {
    if (!isset($_GET[$k])) continue;
    $v = $_GET[$k];
    $cats = is_array($v) ? $v : [$v];
    break;
}
$cats = array_values(array_filter(array_map('strval', $cats)));

$q = trim((string)get_param('q', ''));
$minPriceRaw = get_param('min_price');
$maxPriceRaw = get_param('max_price');
$minPrice = ($minPriceRaw === null || $minPriceRaw === '') ? null : (int)$minPriceRaw;
$maxPrice = ($maxPriceRaw === null || $maxPriceRaw === '') ? null : (int)$maxPriceRaw;

$limit = (int)(get_param('limit', 250));
if ($limit < 1) $limit = 1;
if ($limit > 500) $limit = 500;

$where = [
    "status = 'published'",
    'is_hidden = 0',
    'lat IS NOT NULL',
    'lng IS NOT NULL',
    'lat BETWEEN :south AND :north',
    'lng BETWEEN :west AND :east',
];
$params = [
    ':south' => $south,
    ':north' => $north,
    ':west' => $west,
    ':east' => $east,
];

if ($cats) {
    $in = [];
    foreach ($cats as $i => $cat) {
        $ph = ':cat' . $i;
        $in[] = $ph;
        $params[$ph] = $cat;
    }
    $where[] = 'category_key IN (' . implode(',', $in) . ')';
}

if ($minPrice !== null) {
    $where[] = 'price >= :min_price';
    $params[':min_price'] = $minPrice;
}

if ($maxPrice !== null) {
    $where[] = 'price <= :max_price';
    $params[':max_price'] = $maxPrice;
}

if ($q !== '') {
    $where[] = '(title LIKE :q OR area LIKE :q OR address LIKE :q)';
    $params[':q'] = '%' . $q . '%';
}

$sql = "
    SELECT
        id,
        title,
        price,
        category_key,
        lat,
        lng,
        area,
        address,
        LEFT(COALESCE(description, ''), 140) AS short_description
    FROM listings
    WHERE " . implode(' AND ', $where) . "
    ORDER BY created_at DESC
    LIMIT :limit
";

try {
    $stmt = $pdo->prepare($sql);
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    json_response(['listings' => $stmt->fetchAll()]);
} catch (Throwable $t) {
    // Fail closed but keep response JSON.
    error_response('Unable to load listings', 500);
}
