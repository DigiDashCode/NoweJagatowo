<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$house = null;

if ($id > 0) {
    $stmt = $pdo->prepare(
        "SELECT h.id, h.title, h.description, h.price, h.location, h.area, h.status,
                h.PowierzchniaUżytkowa, h.PowierzchniaDziałki, h.LiczbaPokoi, h.CenaOdPowierzchniUżytkowejBrutto, h.CenaZaM2Brutto,
                h.created_at,
                COALESCE(hi.url, 'https://images.unsplash.com/photo-1505693416388-ac5ce068fe85?auto=format&fit=crop&w=1000&q=80') AS image_url
         FROM houses h
         LEFT JOIN house_images hi ON hi.house_id = h.id AND hi.is_primary = 1
         WHERE h.id = :id"
    );
    $stmt->execute([':id' => $id]);
    $house = $stmt->fetch();
    if ($house) {
        $imgStmt = $pdo->prepare('SELECT url, is_primary FROM house_images WHERE house_id = ? ORDER BY is_primary DESC, created_at ASC');
        $imgStmt->execute([$id]);
        $allImages = $imgStmt->fetchAll();
    } else {
        $allImages = [];
    }
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $house ? htmlspecialchars($house['title']) : 'Szczegóły domu'; ?></title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header>
        <div class="top-menu">
            <a class="menu-item" href="index.php">Domy</a>
            <a class="menu-item" href="kontakt.php">Kontakt</a>
        </div>
        
    </header>
    <main class="detail-page">
        <?php if (!$house): ?>
            <p>Nie znaleziono domu o podanym identyfikatorze.</p>
            <p><a href="index.php">Powrót do listy domów</a></p>
        <?php else: ?>
            <div class="detail-card">
                <img src="<?php echo htmlspecialchars($house['image_url'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($house['title'], ENT_QUOTES, 'UTF-8'); ?>">
                <div class="detail-content">
                    <h1><?php echo htmlspecialchars($house['title'], ENT_QUOTES, 'UTF-8'); ?></h1>
                    <p class="detail-location"><?php echo htmlspecialchars($house['location'], ENT_QUOTES, 'UTF-8'); ?></p>
                    <!-- ID and created_at hidden per request -->
                    <?php if (isset($house['status'])): ?>
                        <span class="status-badge <?php echo statusBadgeClass($house['status']); ?>"><?php echo htmlspecialchars($house['status']); ?></span>
                    <?php endif; ?>
                    <p class="detail-price"><?php echo number_format($house['price'], 0, ',', ' ') . ' zł'; ?></p>
                    <p><?php echo nl2br(htmlspecialchars($house['description'], ENT_QUOTES, 'UTF-8')); ?></p>
                    <p><strong>Metraż:</strong> <?php echo htmlspecialchars($house['area'], ENT_QUOTES, 'UTF-8'); ?> m²</p>
                    <p><strong>Powierzchnia użytkowa:</strong> <?php echo htmlspecialchars(number_format($house['PowierzchniaUżytkowa'], 2, ',', ' '), ENT_QUOTES, 'UTF-8'); ?> m²</p>
                    <p><strong>Powierzchnia działki:</strong> <?php echo htmlspecialchars(number_format($house['PowierzchniaDziałki'], 2, ',', ' '), ENT_QUOTES, 'UTF-8'); ?> m²</p>
                    <p><strong>Liczba pokoi:</strong> <?php echo htmlspecialchars($house['LiczbaPokoi'], ENT_QUOTES, 'UTF-8'); ?></p>
                    <p><strong>Cena od pow. użytk. (brutto):</strong> <?php echo formatPriceDecimals($house['CenaOdPowierzchniUżytkowejBrutto']); ?></p>
                    <p><strong>Cena za m2 (brutto):</strong> <?php echo formatPriceDecimals($house['CenaZaM2Brutto']); ?></p>
                    <!-- bedrooms and bathrooms removed from details per request -->
                    <?php if (!empty($allImages)): ?>
                        <div class="image-gallery">
                            <h4>Galeria zdjęć</h4>
                            <?php foreach ($allImages as $img): ?>
                                <img class="thumb" src="<?php echo htmlspecialchars($img['url'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($house['title'], ENT_QUOTES, 'UTF-8'); ?>">
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <p><a class="back-link" href="index.php">« Powrót do listy domów</a></p>
                </div>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>
