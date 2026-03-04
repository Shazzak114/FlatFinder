<?php
declare(strict_types=1);

function normalize_text(string $s): string
{
    $s = mb_strtolower($s);
    $s = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $s) ?? $s;
    $s = trim(preg_replace('/\s+/u', ' ', $s) ?? $s);
    return $s;
}

function find_duplicate_listings(PDO $pdo, array $listing, int $daysBack = 30): array
{
    $title = normalize_text((string)($listing['title'] ?? ''));
    $price = isset($listing['price']) ? (int)$listing['price'] : null;
    $lat = isset($listing['lat']) ? (float)$listing['lat'] : null;
    $lng = isset($listing['lng']) ? (float)$listing['lng'] : null;

    if ($title === '' || $price === null || $lat === null || $lng === null) {
        return [];
    }

    // Simple heuristic: near-identical title + price + close geo within ~1km.
    $latDelta = 0.01;
    $lngDelta = 0.01;
    $priceDelta = (int)max(10, round($price * 0.05));

    $sql = "
        SELECT
            id,
            user_id,
            title,
            price,
            lat,
            lng,
            created_at
        FROM listings
        WHERE created_at >= (NOW() - INTERVAL :days DAY)
          AND id <> :id
          AND lat BETWEEN :lat_min AND :lat_max
          AND lng BETWEEN :lng_min AND :lng_max
          AND price BETWEEN :price_min AND :price_max
          AND title_normalized = :title_norm
        LIMIT 20
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':days' => $daysBack,
        ':id' => (int)($listing['id'] ?? 0),
        ':lat_min' => $lat - $latDelta,
        ':lat_max' => $lat + $latDelta,
        ':lng_min' => $lng - $lngDelta,
        ':lng_max' => $lng + $lngDelta,
        ':price_min' => $price - $priceDelta,
        ':price_max' => $price + $priceDelta,
        ':title_norm' => $title,
    ]);

    return $stmt->fetchAll();
}

function suspicious_duplicate_image_hashes(PDO $pdo, int $listingId, int $minDistinctListings = 3): array
{
    // Detect same image_hash used by many listings (often spam/duplicates).
    $sql = "
        SELECT
            li.image_hash,
            COUNT(DISTINCT li.listing_id) AS listing_count
        FROM listing_images li
        WHERE li.image_hash IS NOT NULL
          AND li.image_hash <> ''
          AND li.image_hash IN (
              SELECT image_hash
              FROM listing_images
              WHERE listing_id = :listing_id
          )
        GROUP BY li.image_hash
        HAVING COUNT(DISTINCT li.listing_id) >= :min_listings
        ORDER BY listing_count DESC
        LIMIT 50
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':listing_id' => $listingId,
        ':min_listings' => $minDistinctListings,
    ]);

    return $stmt->fetchAll();
}
