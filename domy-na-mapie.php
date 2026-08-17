<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

$googleMapsApiKey = defined('GOOGLE_MAPS_API_KEY') ? GOOGLE_MAPS_API_KEY : '';

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

if ($status !== null) {
    $where[] = 'h.status = :status';
    $params[':status'] = $status;
}

$where[] = "h.status != 'Nieaktywne'";
$where[] = 'h.latitude IS NOT NULL AND h.longitude IS NOT NULL';
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$houses = [];
try {
    $stmt = $pdo->prepare(
        "SELECT h.id, h.title, h.description, h.price, h.location, h.status,
                h.latitude, h.longitude,
                COALESCE(hi.url, 'https://images.unsplash.com/photo-1505693416388-ac5ce068fe85?auto=format&fit=crop&w=1000&q=80') AS image_url
         FROM houses h
         LEFT JOIN house_images hi ON hi.house_id = h.id AND hi.is_primary = 1
         $whereSql
         ORDER BY h.created_at DESC"
    );

    foreach ($params as $key => $value) {
        $type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
        $stmt->bindValue($key, $value, $type);
    }

    $stmt->execute();
    $houses = $stmt->fetchAll();
} catch (Exception $e) {
    error_log('domy-na-mapie.php query error: ' . $e->getMessage());
    $houses = [];
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Domy na Mapie</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        #map {
            width: 100%;
            height: 520px;
            border-radius: 16px;
            border: 1px solid var(--border);
            overflow: hidden;
            background: #eae5d6;
        }
        .map-page {
            max-width: 1100px;
            margin: 20px auto;
            padding: 24px;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(83, 88, 76, 0.08);
        }
        .map-filter {
            margin-bottom: 20px;
        }
        .map-status {
            margin-top: 16px;
            color: var(--muted);
            font-size: 0.95rem;
        }
        .gm-style-iw a {
            color: var(--primary) !important;
            text-decoration: none;
            font-weight: 700;
        }
        .map-details-link {
            display: inline-block;
            margin-top: 8px;
            padding: 8px 12px;
            border-radius: 999px;
            background: var(--primary);
            color: #fff !important;
            text-decoration: none;
        }
        .map-details-link:hover {
            background: var(--primary-dark);
        }
    </style>
</head>
<body>
    <header>
        <div class="top-menu">
            <a class="menu-item" href="index.php">Domy</a>
            <a class="menu-item active" href="domy-na-mapie.php">Domy na Mapie</a>
            <a class="menu-item" href="kontakt.php">Kontakt</a>
        </div>
    </header>

    <main class="map-page">
        <section class="map-filter">
            <h2>Domy na Mapie</h2>
            <form method="get" action="domy-na-mapie.php">
                <div class="filter-row">
                    <label>
                        Min cena:
                        <input type="number" name="min_price" min="0" step="any" value="<?php echo htmlspecialchars((string)($minPrice ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="0">
                    </label>
                    <label>
                        Max cena:
                        <input type="number" name="max_price" min="0" step="any" value="<?php echo htmlspecialchars((string)($maxPrice ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="0">
                    </label>
                    <label>
                        Status:
                        <select name="status">
                            <option value="">Wszystkie</option>
                            <?php foreach (statusOptions(false) as $statusOption): ?>
                                <option value="<?php echo htmlspecialchars($statusOption, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $status === $statusOption ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($statusOption, ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <div>
                        <button type="submit">Pokaż</button>
                        <a class="button-link" href="domy-na-mapie.php">Resetuj filtry</a>
                    </div>
                </div>
            </form>
            <p class="map-status">Liczba aktywnych punktów na mapie: <?php echo count($houses); ?></p>
        </section>

        <div id="map" aria-label="Mapa z domami"></div>
    </main>

    <script>
        const GOOGLE_MAPS_API_KEY = <?php echo json_encode($googleMapsApiKey, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
        const houses = <?php echo json_encode(array_map(function ($house) {
            return [
                'id' => (int)$house['id'],
                'title' => (string)$house['title'],
                'description' => (string)($house['description'] ?? ''),
                'location' => (string)($house['location'] ?? ''),
                'price' => (float)$house['price'],
                'status' => (string)($house['status'] ?? ''),
                'latitude' => (float)$house['latitude'],
                'longitude' => (float)$house['longitude'],
                'image_url' => (string)($house['image_url'] ?? ''),
            ];
        }, $houses), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;

        function escapeHtml(value) {
            return String(value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/\"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function initMap() {
            const mapElement = document.getElementById('map');
            if (!mapElement) {
                return;
            }

            const validHouses = houses.filter(function (house) {
                return Number.isFinite(house.latitude) && Number.isFinite(house.longitude);
            });

            const defaultCenter = { lat: 52.2297, lng: 21.0122 };
            const center = validHouses.length > 0
                ? { lat: validHouses[0].latitude, lng: validHouses[0].longitude }
                : defaultCenter;

            const map = new google.maps.Map(mapElement, {
                center: center,
                zoom: validHouses.length > 0 ? 8 : 6,
                mapTypeControl: false,
                streetViewControl: false,
                fullscreenControl: true,
                zoomControl: true
            });

            if (validHouses.length === 0) {
                const info = new google.maps.InfoWindow({
                    content: '<div><strong>Brak domów z zapisanymi współrzędnymi.</strong><br>Dodaj latitude/longitude w panelu administratora.</div>'
                });
                info.open(map, new google.maps.Marker({
                    map: map,
                    position: center,
                    title: 'Brak danych'
                }));
                return;
            }

            const bounds = new google.maps.LatLngBounds();

            validHouses.forEach(function (house) {
                const position = { lat: house.latitude, lng: house.longitude };
                bounds.extend(position);

                const marker = new google.maps.Marker({
                    position: position,
                    map: map,
                    title: house.title
                });

                const descriptionText = (house.description || '').replace(/\s+/g, ' ').trim();
                const shortDescription = descriptionText.length > 140
                    ? descriptionText.substring(0, 137) + '...'
                    : descriptionText;
                const imageUrl = (house.image_url || '').trim();
                const imageHtml = imageUrl
                    ? '<img src="' + escapeHtml(imageUrl) + '" alt="' + escapeHtml(house.title) + '" style="width:100%;height:120px;object-fit:cover;border-radius:10px;margin:8px 0 10px;display:block;">'
                    : '';

                const infoWindowContent = [
                    '<div style="max-width:260px;">',
                    imageHtml,
                    '<strong>' + escapeHtml(house.title) + '</strong><br>',
                    '<span>' + escapeHtml(house.location) + '</span><br>',
                    '<span>' + escapeHtml(house.status) + '</span><br>',
                    '<span>' + Number(house.price).toLocaleString('pl-PL', { maximumFractionDigits: 0 }) + ' zł</span><br>',
                    (shortDescription ? '<span>' + escapeHtml(shortDescription) + '</span><br>' : ''),
                    '<a class="map-details-link" href="detail.php?id=' + encodeURIComponent(String(house.id)) + '">Zobacz szczegóły</a>',
                    '</div>'
                ].join('');

                const infoWindow = new google.maps.InfoWindow({
                    content: infoWindowContent
                });

                marker.addListener('click', function () {
                    infoWindow.open(map, marker);
                });
            });

            if (validHouses.length > 1) {
                map.fitBounds(bounds);
            }
        }

        const GOOGLE_MAPS_KEY_IS_PLACEHOLDER = !GOOGLE_MAPS_API_KEY || GOOGLE_MAPS_API_KEY.includes('YOUR_GOOGLE_MAPS_API_KEY');

        if (GOOGLE_MAPS_KEY_IS_PLACEHOLDER) {
            document.getElementById('map').innerHTML = '<div style="display:flex;align-items:center;justify-content:center;height:100%;padding:24px;color:#a03a2b;font-weight:600;text-align:center;">Ustaw poprawny klucz Google Maps API w zmiennej GOOGLE_MAPS_API_KEY na tej stronie.</div>';
        } else if (typeof google !== 'undefined' && google.maps) {
            initMap();
        } else {
            const script = document.createElement('script');
            script.src = 'https://maps.googleapis.com/maps/api/js?key=' + encodeURIComponent(GOOGLE_MAPS_API_KEY) + '&callback=initMap';
            script.async = true;
            script.defer = true;
            document.head.appendChild(script);
        }
    </script>
</body>
</html>
