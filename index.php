<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

if (isset($_GET['ajax']) && $_GET['ajax'] === '1') {
    $minPrice = isset($_GET['min_price']) && $_GET['min_price'] !== '' ? intval($_GET['min_price']) : null;
    $maxPrice = isset($_GET['max_price']) && $_GET['max_price'] !== '' ? intval($_GET['max_price']) : null;
    $status = isset($_GET['status']) && $_GET['status'] !== '' ? $_GET['status'] : null;

    if ($minPrice === 0 && $maxPrice === 0) {
        $minPrice = null;
        $maxPrice = null;
    }

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

    $where[] = "h.status != 'Nieaktywne'";

    if ($status !== null) {
        $where[] = 'h.status = :status';
        $params[':status'] = $status;
    }

    $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
    $perPage = 20;
    $currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $offset = ($currentPage - 1) * $perPage;

    try {
        $countSql = "SELECT COUNT(*) FROM houses h $whereSql";
        $countStmt = $pdo->prepare($countSql);
        foreach ($params as $key => $value) {
            $type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
            $countStmt->bindValue($key, $value, $type);
        }
        $countStmt->execute();
        $total = (int)$countStmt->fetchColumn();
        $totalPages = $total > 0 ? (int)ceil($total / $perPage) : 1;

        $sql = "SELECT h.id, h.title, h.location, h.price, h.status, COALESCE(hi.url, '') AS image_url
                FROM houses h
                LEFT JOIN house_images hi ON hi.house_id = h.id AND hi.is_primary = 1
                $whereSql
                ORDER BY h.created_at DESC
                LIMIT :limit OFFSET :offset";
        $stmt = $pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmt->bindValue($key, $value, $type);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $houses = $stmt->fetchAll();
    } catch (Exception $e) {
        $houses = [];
        $totalPages = 1;
    }

    $showFilter = true;
    $admin = false;
    $baseUrl = 'index.php';
    include __DIR__ . '/houses_component.php';
    exit;
}

$minPrice = isset($_GET['min_price']) && $_GET['min_price'] !== '' ? intval($_GET['min_price']) : null;
$maxPrice = isset($_GET['max_price']) && $_GET['max_price'] !== '' ? intval($_GET['max_price']) : null;
$status = isset($_GET['status']) && $_GET['status'] !== '' ? $_GET['status'] : null;

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

// Exclude inactive from public listing unless explicitly requested (public doesn't offer Nieaktywne)
$where[] = "h.status != 'Nieaktywne'";

if ($status !== null) {
    $where[] = 'h.status = :status';
    $params[':status'] = $status;
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
        $type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
        $countStmt->bindValue($key, $value, $type);
    }
    $countStmt->execute();
    $total = (int)$countStmt->fetchColumn();
    $totalPages = $total > 0 ? (int)ceil($total / $perPage) : 1;

    $sql = "SELECT h.id, h.title, h.location, h.price, h.status, COALESCE(hi.url, '') AS image_url
            FROM houses h
            LEFT JOIN house_images hi ON hi.house_id = h.id AND hi.is_primary = 1
            $whereSql
            ORDER BY h.created_at DESC
            LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        $type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
        $stmt->bindValue($key, $value, $type);
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header>
        <div class="top-menu">
            <a class="menu-item active" href="index.php">Domy</a>
            <a class="menu-item" href="domy-na-mapie.php">Domy na Mapie</a>
            <a class="menu-item" href="kontakt.php">Kontakt</a>
        </div>
        
    </header>

    <?php include __DIR__ . '/carousel.php'; ?>

    <div id="listing-root">
        <?php
        // Render filter, tiles and pagination via reusable component
        $showFilter = true;
        $admin = false;
        $currentPage = $currentPage ?? 1;
        $totalPages = $totalPages ?? 1;
        $baseUrl = 'index.php';
        include __DIR__ . '/houses_component.php';
        ?>
    </div>
    <script src="carousel.js"></script>
    <script>
        function bindListingAjax() {
            const root = document.getElementById('listing-root');
            if (!root) {
                return;
            }

            const form = root.querySelector('.filter-panel form');
            if (form) {
                form.addEventListener('submit', function (event) {
                    event.preventDefault();
                    const params = new URLSearchParams(new FormData(form));
                    params.set('ajax', '1');
                    const requestUrl = new URL(window.location.href);
                    requestUrl.search = params.toString();
                    fetch(requestUrl.toString(), {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(function (response) { return response.text(); })
                    .then(function (html) {
                        root.innerHTML = html;
                        bindListingAjax();
                    })
                    .catch(function () {
                        window.location.href = form.action + '?' + params.toString();
                    });
                });
            }

            root.querySelectorAll('.page-link').forEach(function (link) {
                link.addEventListener('click', function (event) {
                    event.preventDefault();
                    const requestUrl = new URL(link.href);
                    requestUrl.searchParams.set('ajax', '1');
                    fetch(requestUrl.toString(), {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(function (response) { return response.text(); })
                    .then(function (html) {
                        root.innerHTML = html;
                        bindListingAjax();
                    })
                    .catch(function () {
                        window.location.href = link.href;
                    });
                });
            });
        }

        document.addEventListener('DOMContentLoaded', function () {
            bindListingAjax();
        });
    </script>
</body>
</html>
