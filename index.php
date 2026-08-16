<?php
require_once __DIR__ . '/db.php';

$minPrice = isset($_GET['min_price']) && $_GET['min_price'] !== '' ? intval($_GET['min_price']) : null;
$maxPrice = isset($_GET['max_price']) && $_GET['max_price'] !== '' ? intval($_GET['max_price']) : null;
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
$sql = "SELECT h.id, h.title, h.location, h.price, COALESCE(hi.url, '') AS image_url
        FROM houses h
        LEFT JOIN house_images hi ON hi.house_id = h.id AND hi.is_primary = 1
        $whereSql
        ORDER BY h.created_at DESC";
$stmt = $pdo->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value, PDO::PARAM_INT);
}
$stmt->execute();
$houses = $stmt->fetchAll();

function formatPrice($value) {
    return number_format($value, 0, ',', ' ') . ' zł';
}

function imageUrl($url) {
    return $url ?: 'https://images.unsplash.com/photo-1505693416388-ac5ce068fe85?auto=format&fit=crop&w=1000&q=80';
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

    <section class="filter-panel">
        <h2>Filtr cen</h2>
        <form method="get">
            <div class="filter-row">
                <label>Min cena:<input type="number" name="min_price" min="0" step="1000" placeholder="0" value="<?php echo htmlspecialchars($minPrice ?? ''); ?>"></label>
                <label>Max cena:<input type="number" name="max_price" min="0" step="1000" placeholder="0" value="<?php echo htmlspecialchars($maxPrice ?? ''); ?>"></label>
                <button type="submit">Pokaż</button>
            </div>
        </form>
    </section>

    <section id="results">
        <div class="grid">
            <?php if (empty($houses)): ?>
                <p>Brak domów w podanym przedziale cenowym.</p>
            <?php else: ?>
                <?php foreach ($houses as $index => $house): ?>
                    <?php $isFirstHouse = $index === 0; ?>
                    <a class="card<?php echo $isFirstHouse ? ' house-card-bouncy' : ''; ?>"
                       href="detail.php?id=<?php echo $house['id']; ?>"
                       <?php echo $isFirstHouse ? 'data-bouncy-card="true"' : ''; ?>>
                        <img src="<?php echo htmlspecialchars(imageUrl($house['image_url']), ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($house['title'], ENT_QUOTES, 'UTF-8'); ?>">
                        <div class="card-content">
                            <h3><?php echo htmlspecialchars($house['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
                            <p class="price"><?php echo formatPrice($house['price']); ?></p>
                            <p class="location"><?php echo htmlspecialchars($house['location'], ENT_QUOTES, 'UTF-8'); ?></p>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>
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
