<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

$minPrice = isset($_GET['min_price']) && $_GET['min_price'] !== '' ? intval($_GET['min_price']) : null;
$maxPrice = isset($_GET['max_price']) && $_GET['max_price'] !== '' ? intval($_GET['max_price']) : null;

// If the user explicitly sets both min and max to 0, treat it as no filter selected
if ($minPrice === 0 && $maxPrice === 0) {
    $minPrice = null;
    $maxPrice = null;
}

// If min is not set (null) but max is explicitly 0, treat as no filter selected
if ($minPrice === null && $maxPrice === 0) {
    $maxPrice = null;
}

$where = [];
$params = [];

if ($minPrice !== null) {
    $where[] = 'h.price >= :min_price';
    $params[':min_price'] = $minPrice;
}

if ($maxPrice !== null) {
    $where[] = 'h.price <= :max_price';
    $params[':max_price'] = $maxPrice;
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Pagination
$perPage = 20;
$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($currentPage - 1) * $perPage;

try {
    $countSql = "SELECT COUNT(*) FROM houses h $whereSql";
    $countStmt = $pdo->prepare($countSql);
    foreach ($params as $key => $value) {
        $countStmt->bindValue($key, $value, PDO::PARAM_INT);
    }
    $countStmt->execute();
    $total = (int)$countStmt->fetchColumn();
    $totalPages = $total > 0 ? (int)ceil($total / $perPage) : 1;

    $sql = "SELECT h.id, h.title, h.location, h.price, COALESCE(hi.url, '') AS image_url
            FROM houses h
            LEFT JOIN house_images hi ON hi.house_id = h.id AND hi.is_primary = 1
            $whereSql
            ORDER BY h.created_at DESC
            LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value, PDO::PARAM_INT);
    }
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $houses = $stmt->fetchAll();
} catch (Exception $e) {
    $houses = [];
    $totalPages = 1;
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Domy</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header>
        <div class="top-menu">
            <a class="menu-item active" href="index.php">Domy</a>
            <a class="menu-item" href="kontakt.php">Kontakt</a>
        </div>
        
    </header>

    <?php include __DIR__ . '/carousel.php'; ?>

    <?php
    // Render filter, tiles and pagination via reusable component
    $showFilter = true;
    $admin = false;
    $currentPage = $currentPage ?? 1;
    $totalPages = $totalPages ?? 1;
    $baseUrl = 'index.php';
    include __DIR__ . '/houses_component.php';
    ?>
    <script src="carousel.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const firstCard = document.querySelector('[data-bouncy-card="true"]');
            if (!firstCard) {
                return;
            }

            let x = 0;
            let y = 0;
            let vx = 0;
            let vy = 0;
            let animationFrame = null;
            let isAnimated = false;

            function randomBetween(min, max) {
                return Math.random() * (max - min) + min;
            }

            function startBounce() {
                if (isAnimated) {
                    return;
                }

                isAnimated = true;
                firstCard.classList.add('is-floating');

                const rect = firstCard.getBoundingClientRect();
                x = Math.max(16, Math.min(window.innerWidth - rect.width - 16, rect.left));
                y = Math.max(16, Math.min(window.innerHeight - rect.height - 16, rect.top));
                firstCard.style.left = x + 'px';
                firstCard.style.top = y + 'px';
                firstCard.style.width = rect.width + 'px';
                firstCard.style.height = rect.height + 'px';

                vx = randomBetween(2, 4) * (Math.random() > 0.5 ? 1 : -1);
                vy = randomBetween(2, 4) * (Math.random() > 0.5 ? 1 : -1);

                function tick() {
                    x += vx;
                    y += vy;

                    const maxLeft = 16;
                    const maxTop = 16;
                    const maxRight = window.innerWidth - firstCard.offsetWidth - 16;
                    const maxBottom = window.innerHeight - firstCard.offsetHeight - 16;

                    if (x <= maxLeft || x >= maxRight) {
                        vx *= -1;
                        x = Math.min(Math.max(x, maxLeft), maxRight);
                    }

                    if (y <= maxTop || y >= maxBottom) {
                        vy *= -1;
                        y = Math.min(Math.max(y, maxTop), maxBottom);
                    }

                    firstCard.style.left = x + 'px';
                    firstCard.style.top = y + 'px';
                    animationFrame = window.requestAnimationFrame(tick);
                }

                if (animationFrame) {
                    window.cancelAnimationFrame(animationFrame);
                }

                animationFrame = window.requestAnimationFrame(tick);
            }

            firstCard.addEventListener('click', function () {
            });

            window.addEventListener('resize', function () {
                const rect = firstCard.getBoundingClientRect();
                const maxLeft = 16;
                const maxTop = 16;
                const maxRight = window.innerWidth - rect.width - 16;
                const maxBottom = window.innerHeight - rect.height - 16;

                x = Math.min(Math.max(x, maxLeft), maxRight);
                y = Math.min(Math.max(y, maxTop), maxBottom);
                firstCard.style.left = x + 'px';
                firstCard.style.top = y + 'px';
            });
        });
    </script>
</body>
</html>
