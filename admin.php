<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

$googleMapsApiKey = defined('GOOGLE_MAPS_API_KEY') ? GOOGLE_MAPS_API_KEY : '';

session_start();
$message = '';
$messageType = 'success';
$editingHouse = null;
$editingImages = [];

if (isset($_SESSION['admin_message'])) {
    $message = $_SESSION['admin_message'];
    $messageType = $_SESSION['admin_message_type'] ?? 'success';
    unset($_SESSION['admin_message'], $_SESSION['admin_message_type']);
}

function is_admin_authenticated() {
    return isset($_SESSION['admin_authenticated']) && $_SESSION['admin_authenticated'] === true && time() < ($_SESSION['admin_expires'] ?? 0);
}

function normalizeImagePathFromUrl($url) {
    if ($url === '') {
        return '';
    }

    $cleanUrl = trim($url);
    if (strpos($cleanUrl, 'http://') === 0 || strpos($cleanUrl, 'https://') === 0) {
        return '';
    }

    return __DIR__ . '/' . ltrim($cleanUrl, '/');
}

function deleteUploadedImageFile($url) {
    $filePath = normalizeImagePathFromUrl($url);
    if ($filePath !== '' && file_exists($filePath)) {
        @unlink($filePath);
    }
}

function saveUploadedHouseImage() {
    if (!isset($_FILES['primary_image_file']) || !is_array($_FILES['primary_image_file'])) {
        return null;
    }

    $file = $_FILES['primary_image_file'];
    if ($file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Błąd przesyłania pliku zdjęcia.');
    }

    if (!is_uploaded_file($file['tmp_name'])) {
        throw new RuntimeException('Nieprawidłowy plik zdjęcia.');
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (strpos($mimeType, 'image/') !== 0) {
        throw new RuntimeException('Plik musi być obrazem.');
    }

    if ($file['size'] <= 0 || $file['size'] > 5 * 1024 * 1024) {
        throw new RuntimeException('Plik zdjęcia musi mieć rozmiar do 5 MB.');
    }

    $uploadDir = __DIR__ . '/uploads/houses';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '-', basename($file['name']));
    if ($safeName === '' || $safeName === '.') {
        $safeName = 'house-image-' . time() . '.jpg';
    }

    $destination = $uploadDir . '/' . time() . '-' . $safeName;
    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new RuntimeException('Nie udało się zapisać zdjęcia na serwerze.');
    }

    return 'uploads/houses/' . basename($destination);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'create';

    // login action: set session
    if ($action === 'login') {
        $password = $_POST['admin_password'] ?? '';
        if ($password === ADMIN_PASSWORD) {
            $_SESSION['admin_authenticated'] = true;
            $_SESSION['admin_expires'] = time() + 20 * 60; // 20 minutes
            $messageType = 'success';
            $message = 'Zalogowano pomyślnie. Sesja wygasa za 20 minut.';
        } else {
            $messageType = 'error';
            $message = 'Nieprawidłowe hasło administratora.';
        }
    } else {
        // All other actions require an authenticated session
        if (!is_admin_authenticated()) {
            $messageType = 'error';
            $message = 'Musisz się zalogować, aby wykonać tę akcję.';
        } else {
            if ($action === 'delete' && isset($_POST['delete_house_id'])) {
                $houseId = intval($_POST['delete_house_id']);
                try {
                    $pdo->beginTransaction();
                    $stmt = $pdo->prepare('DELETE FROM house_images WHERE house_id = ?');
                    $stmt->execute([$houseId]);
                    $stmt = $pdo->prepare('DELETE FROM houses WHERE id = ?');
                    $stmt->execute([$houseId]);
                    $pdo->commit();
                    $messageType = 'success';
                    $message = 'Dom został usunięty.';
                } catch (Exception $e) {
                    $pdo->rollBack();
                    error_log('admin.php delete error: ' . $e->getMessage());
                    $messageType = 'error';
                    $message = 'Błąd podczas usuwania domu.';
                }
            } elseif ($action === 'update' && isset($_POST['house_id'])) {
                $houseId = intval($_POST['house_id']);
                $title = trim($_POST['title'] ?? '');
                $description = trim($_POST['description'] ?? '');
                $price = floatval($_POST['price'] ?? 0);
                $PowierzchniaUzytkowa = str_replace(',', '.', str_replace(' ', '', $_POST['PowierzchniaUżytkowa'] ?? '0'));
                $PowierzchniaDzialki = str_replace(',', '.', str_replace(' ', '', $_POST['PowierzchniaDziałki'] ?? '0'));
                $LiczbaPokoi = intval($_POST['LiczbaPokoi'] ?? 0);
                $CenaOdPowU = str_replace(',', '.', str_replace(' ', '', $_POST['CenaOdPowierzchniUżytkowejBrutto'] ?? '0'));
                $CenaZaM2 = str_replace(',', '.', str_replace(' ', '', $_POST['CenaZaM2Brutto'] ?? '0'));
                $location = trim($_POST['location'] ?? '');
                $area = intval($_POST['area'] ?? 0);
                $status = trim($_POST['status'] ?? 'Dostępne');
                $latitude = isset($_POST['latitude']) && $_POST['latitude'] !== '' ? (float)$_POST['latitude'] : null;
                $longitude = isset($_POST['longitude']) && $_POST['longitude'] !== '' ? (float)$_POST['longitude'] : null;
                $primaryImage = trim($_POST['primary_image_url'] ?? '');
                $imageUrls = array_filter(array_map('trim', explode(',', $_POST['image_urls'] ?? '')));

                try {
                    $uploadedImageUrl = saveUploadedHouseImage();
                    if ($uploadedImageUrl !== null) {
                        $primaryImage = $uploadedImageUrl;
                    }
                } catch (RuntimeException $e) {
                    $messageType = 'error';
                    $message = $e->getMessage();
                }

                if ($title === '' || $price <= 0 || $location === '' || $primaryImage === '') {
                    $messageType = 'error';
                    $message = 'Wypełnij pola: tytuł, cena, lokalizacja i główny obraz.';
                } else {
                    try {
                        $pdo->beginTransaction();
                        $status = trim($_POST['status'] ?? 'Dostępne');
                        $allowed = statusOptions(true);
                        if (!in_array($status, $allowed, true)) $status = 'Dostępne';

                        $stmt = $pdo->prepare('UPDATE houses SET title = ?, description = ?, price = ?, location = ?, area = ?, status = ?, PowierzchniaUżytkowa = ?, PowierzchniaDziałki = ?, LiczbaPokoi = ?, CenaOdPowierzchniUżytkowejBrutto = ?, CenaZaM2Brutto = ?, latitude = ?, longitude = ? WHERE id = ?');
                        $stmt->execute([$title, $description, $price, $location, $area, $status, $PowierzchniaUzytkowa, $PowierzchniaDzialki, $LiczbaPokoi, $CenaOdPowU, $CenaZaM2, $latitude, $longitude, $houseId]);

                        $oldImageStmt = $pdo->prepare('SELECT url FROM house_images WHERE house_id = ? AND is_primary = 1 LIMIT 1');
                        $oldImageStmt->execute([$houseId]);
                        $oldPrimaryImage = $oldImageStmt->fetchColumn();

                        $stmt = $pdo->prepare('DELETE FROM house_images WHERE house_id = ?');
                        $stmt->execute([$houseId]);

                        if ($oldPrimaryImage && $oldPrimaryImage !== $primaryImage && strpos($oldPrimaryImage, 'http://') !== 0 && strpos($oldPrimaryImage, 'https://') !== 0) {
                            deleteUploadedImageFile($oldPrimaryImage);
                        }

                        $stmt = $pdo->prepare('INSERT INTO house_images (house_id, url, is_primary) VALUES (?, ?, ?)');
                        $stmt->execute([$houseId, $primaryImage, 1]);
                        foreach ($imageUrls as $url) {
                            if ($url !== '') {
                                $stmt->execute([$houseId, $url, 0]);
                            }
                        }

                        $pdo->commit();
                        $_SESSION['admin_message'] = 'Dom został zaktualizowany.';
                        $_SESSION['admin_message_type'] = 'success';
                        header('Location: admin.php');
                        exit;
                    } catch (Exception $e) {
                        $pdo->rollBack();
                        error_log('admin.php update error: ' . $e->getMessage());
                        $messageType = 'error';
                        $message = 'Błąd podczas aktualizacji domu.';
                    }
                }
            } else {
                $title = trim($_POST['title'] ?? '');
                $description = trim($_POST['description'] ?? '');
                $price = floatval($_POST['price'] ?? 0);
                $location = trim($_POST['location'] ?? '');
                $area = intval($_POST['area'] ?? 0);
                $status = trim($_POST['status'] ?? 'Dostępne');
                $latitude = isset($_POST['latitude']) && $_POST['latitude'] !== '' ? (float)$_POST['latitude'] : null;
                $longitude = isset($_POST['longitude']) && $_POST['longitude'] !== '' ? (float)$_POST['longitude'] : null;
                $primaryImage = trim($_POST['primary_image_url'] ?? '');
                $imageUrls = array_filter(array_map('trim', explode(',', $_POST['image_urls'] ?? '')));

                try {
                    $uploadedImageUrl = saveUploadedHouseImage();
                    if ($uploadedImageUrl !== null) {
                        $primaryImage = $uploadedImageUrl;
                    }
                } catch (RuntimeException $e) {
                    $messageType = 'error';
                    $message = $e->getMessage();
                }

                $allowed = statusOptions(true);
                if (!in_array($status, $allowed, true)) $status = 'Dostępne';

                if ($title === '' || $price <= 0 || $location === '' || $primaryImage === '') {
                    $messageType = 'error';
                    $message = 'Wypełnij pola: tytuł, cena, lokalizacja i główny obraz.';
                } else {
                    try {
                        $pdo->beginTransaction();
                        $stmt = $pdo->prepare('INSERT INTO houses (title, description, price, location, area, status, PowierzchniaUżytkowa, PowierzchniaDziałki, LiczbaPokoi, CenaOdPowierzchniUżytkowejBrutto, CenaZaM2Brutto, latitude, longitude) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
                        $stmt->execute([$title, $description, $price, $location, $area, $status, $PowierzchniaUzytkowa, $PowierzchniaDzialki, $LiczbaPokoi, $CenaOdPowU, $CenaZaM2, $latitude, $longitude]);
                        $houseId = $pdo->lastInsertId();

                        $stmt = $pdo->prepare('INSERT INTO house_images (house_id, url, is_primary) VALUES (?, ?, ?)');
                        $stmt->execute([$houseId, $primaryImage, 1]);

                        foreach ($imageUrls as $url) {
                            if ($url !== '') {
                                $stmt->execute([$houseId, $url, 0]);
                            }
                        }

                        $pdo->commit();
                        $_SESSION['admin_message'] = 'Dom został dodany pomyślnie.';
                        $_SESSION['admin_message_type'] = 'success';
                        header('Location: admin.php');
                        exit;
                    } catch (Exception $e) {
                        $pdo->rollBack();
                        error_log('admin.php insert error: ' . $e->getMessage());
                        $messageType = 'error';
                        $message = 'Wystąpił błąd bazy danych.';
                    }
                }
            }
        }
    }
}

if (isset($_GET['action'], $_GET['id']) && $_GET['action'] === 'edit') {
    $editId = intval($_GET['id']);
    $stmt = $pdo->prepare('SELECT h.id, h.title, h.description, h.price, h.location, h.area, h.status, h.latitude, h.longitude, h.PowierzchniaUżytkowa, h.PowierzchniaDziałki, h.LiczbaPokoi, h.CenaOdPowierzchniUżytkowejBrutto, h.CenaZaM2Brutto, COALESCE(hi.url, \'https://images.unsplash.com/photo-1505693416388-ac5ce068fe85?auto=format&fit=crop&w=1000&q=80\') AS primary_image_url
                           FROM houses h
                           LEFT JOIN house_images hi ON hi.house_id = h.id AND hi.is_primary = 1
                           WHERE h.id = ?');
    $stmt->execute([$editId]);
    $editingHouse = $stmt->fetch();

    if ($editingHouse) {
        $stmt = $pdo->prepare('SELECT url FROM house_images WHERE house_id = ? AND is_primary = 0 ORDER BY created_at ASC');
        $stmt->execute([$editId]);
        $editingImages = array_column($stmt->fetchAll(), 'url');
    }
}

// Read optional price filters for admin list
$minPrice = isset($_GET['min_price']) && $_GET['min_price'] !== '' ? intval($_GET['min_price']) : null;
$maxPrice = isset($_GET['max_price']) && $_GET['max_price'] !== '' ? intval($_GET['max_price']) : null;
$status = isset($_GET['status']) && $_GET['status'] !== '' ? $_GET['status'] : null;

// Treat 0/unset combinations as no filter (same rules as index)
if ($minPrice === 0 && $maxPrice === 0) {
    $minPrice = null;
    $maxPrice = null;
}
if ($minPrice === null && $maxPrice === 0) {
    $maxPrice = null;
}

// Paginated fetch for admin list with optional filters
$houses = [];
$perPage = 20;
$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($currentPage - 1) * $perPage;
try {
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
    $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $countSql = 'SELECT COUNT(*) FROM houses h ' . $whereSql;
    $countStmt = $pdo->prepare($countSql);
    foreach ($params as $key => $value) {
        $type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
        $countStmt->bindValue($key, $value, $type);
    }
    $countStmt->execute();
    $total = (int)$countStmt->fetchColumn();
    $totalPages = $total > 0 ? (int)ceil($total / $perPage) : 1;

    $stmtSql = "SELECT h.id, h.title, h.location, h.price, h.status, COALESCE(hi.url, 'https://images.unsplash.com/photo-1505693416388-ac5ce068fe85?auto=format&fit=crop&w=1000&q=80') AS image_url
                             FROM houses h
                         LEFT JOIN house_images hi ON hi.house_id = h.id AND hi.is_primary = 1
                         $whereSql
                         ORDER BY h.created_at DESC
                         LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($stmtSql);
    foreach ($params as $key => $value) {
        $type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
        $stmt->bindValue($key, $value, $type);
    }
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $houses = $stmt->fetchAll();
} catch (Exception $e) {
    if (!$message) {
        error_log('admin.php fetch houses error: ' . $e->getMessage());
        $messageType = 'error';
        $message = 'Nie można pobrać listy domów.';
    }
}

function old($field, $default = '') {
    return htmlspecialchars($_POST[$field] ?? $default, ENT_QUOTES, 'UTF-8');
}

function editValue($field, $default = '') {
    global $editingHouse;
    if ($editingHouse && isset($editingHouse[$field])) {
        return htmlspecialchars($editingHouse[$field], ENT_QUOTES, 'UTF-8');
    }
    return old($field, $default);
}

function editImages() {
    global $editingImages;
    return htmlspecialchars(implode(', ', $editingImages), ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel administratora</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header>
        <div class="top-menu">
            <a class="menu-item" href="index.php">Domy</a>
            <a class="menu-item" href="domy-na-mapie.php">Domy na Mapie</a>
            <a class="menu-item" href="kontakt.php">Kontakt</a>
        </div>
        
    </header>

    <main class="admin-form">
        <?php if ($message): ?>
            <div class="message <?php echo $messageType === 'error' ? 'error' : 'success'; ?>">
                <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <?php if (!is_admin_authenticated()): ?>
            <form method="post">
                <input type="hidden" name="action" value="login">
                <label>Hasło administratora:<input type="password" name="admin_password" required oninvalid="this.setCustomValidity('Podaj hasło administratora')" oninput="this.setCustomValidity('')"></label>
                <button type="submit">Zaloguj</button>
            </form>
        <?php else: ?>
            <?php $editorOpen = $editingHouse !== null; ?>
            <div class="admin-editor-tile <?php echo $editorOpen ? 'is-open' : 'is-collapsed'; ?>" data-admin-editor-tile>
                <button type="button" class="admin-toggle-button" data-admin-toggle>
                    <span class="admin-toggle-label"><?php echo $editingHouse ? 'Edytuj dom' : 'Dodaj nowy dom'; ?></span>
                    <span class="admin-toggle-icon" aria-hidden="true">▾</span>
                </button>

                <div class="admin-editor-content">
                    <form method="post" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="<?php echo $editingHouse ? 'update' : 'create'; ?>">
                        <?php if ($editingHouse): ?>
                            <input type="hidden" name="house_id" value="<?php echo $editingHouse['id']; ?>">
                        <?php endif; ?>
                        <label>Tytuł:<input type="text" name="title" value="<?php echo editValue('title'); ?>" required oninvalid="this.setCustomValidity('Podaj tytuł')" oninput="this.setCustomValidity('')"></label>
                        <label>Opis:<textarea name="description"><?php echo editValue('description'); ?></textarea></label>
                        <label>Cena:<input type="number" name="price" min="0" step="0.01" value="<?php echo editValue('price'); ?>" required oninvalid="this.setCustomValidity('Podaj poprawną cenę (liczba nieujemna)')" oninput="this.setCustomValidity('')"></label>
                        <label>Status:
                            <select name="status" required>
                                <?php foreach (statusOptions(true) as $st):
                                    $sel = '';
                                    if ($editingHouse && isset($editingHouse['status']) && $editingHouse['status'] === $st) {
                                        $sel = 'selected';
                                    } elseif (!$editingHouse && $st === 'Dostępne') {
                                        $sel = 'selected';
                                    }
                                ?>
                                    <option value="<?php echo htmlspecialchars($st); ?>" <?php echo $sel; ?>><?php echo htmlspecialchars($st); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label>Lokalizacja:<input type="text" name="location" value="<?php echo editValue('location'); ?>" required oninvalid="this.setCustomValidity('Podaj lokalizację')" oninput="this.setCustomValidity('')"></label>
                        <label>Szerokość geograficzna (latitude):<input type="number" name="latitude" step="any" value="<?php echo editValue('latitude', ''); ?>" placeholder="np. 54.352025"></label>
                        <label>Długość geograficzna (longitude):<input type="number" name="longitude" step="any" value="<?php echo editValue('longitude', ''); ?>" placeholder="np. 18.646638"></label>

                        <div class="map-editor">
                            <div class="map-editor-header">
                                <strong>Wskaż lokalizację na mapie</strong>
                            </div>
                            <div id="adminMap" class="map-editor-map" aria-label="Mapa do ustawiania współrzędnych"></div>
                            <p class="map-editor-note">Kliknij na mapie, aby ustawić współrzędne albo wpisz je ręcznie powyżej. Wystarczy podać poprawny klucz Google Maps API w skrypcie.</p>
                        </div>
                        <!-- bedrooms and bathrooms removed as requested -->
                        <label>Powierzchnia (m2):<input type="number" name="area" min="0" value="<?php echo editValue('area', '0'); ?>" oninvalid="this.setCustomValidity('Podaj poprawny metraż (0 lub więcej)')" oninput="this.setCustomValidity('')"></label>
                        <label>Pow. użytkowa (m2):<input type="text" name="PowierzchniaUżytkowa" value="<?php echo editValue('PowierzchniaUżytkowa', '0'); ?>"></label>
                        <label>Pow. działki (m2):<input type="text" name="PowierzchniaDziałki" value="<?php echo editValue('PowierzchniaDziałki', '0'); ?>"></label>
                        <label>Liczba pokoi:<input type="number" name="LiczbaPokoi" min="0" value="<?php echo editValue('LiczbaPokoi', '0'); ?>"></label>
                        <label>Cena od pow. użytk. (brutto):<input type="text" name="CenaOdPowierzchniUżytkowejBrutto" value="<?php echo editValue('CenaOdPowierzchniUżytkowejBrutto', '0'); ?>"></label>
                        <label>Cena za m2 (brutto):<input type="text" name="CenaZaM2Brutto" value="<?php echo editValue('CenaZaM2Brutto', '0'); ?>"></label>
                        <label>Główny obraz (URL lub plik):
                            <input type="url" name="primary_image_url" value="<?php echo editValue('primary_image_url'); ?>" placeholder="https://... albo zostaw puste, jeśli wrzucasz plik poniżej" oninvalid="this.setCustomValidity('Podaj poprawny adres URL obrazu, jeśli używasz URL')" oninput="this.setCustomValidity('')">
                        </label>
                        <label>Wgraj główny obraz z komputera:
                            <input type="file" name="primary_image_file" accept="image/*">
                        </label>
                        <div id="selected-image-preview" style="display:none; margin-top:10px;">
                            <strong>Podgląd wybranego zdjęcia:</strong><br>
                            <img id="selected-image-preview-img" src="" alt="Podgląd wybranego zdjęcia" style="max-width:220px;max-height:140px;object-fit:cover;border-radius:10px;margin-top:8px;">
                        </div>
                        <?php if ($editingHouse && !empty($editingHouse['primary_image_url'])): ?>
                            <div class="current-image-preview" style="margin-top:12px;">
                                <strong>Aktualne zdjęcie:</strong><br>
                                <img src="<?php echo htmlspecialchars($editingHouse['primary_image_url'], ENT_QUOTES, 'UTF-8'); ?>" alt="Aktualne zdjęcie domu" style="max-width:220px;max-height:140px;object-fit:cover;border-radius:10px;margin-top:8px;">
                            </div>
                        <?php endif; ?>
                        <label>Dodatkowe obrazy (URL, oddzielone przecinkami):<textarea name="image_urls"><?php echo $editingHouse ? editImages() : old('image_urls'); ?></textarea></label>
                        <button type="submit"><?php echo $editingHouse ? 'Zaktualizuj dom' : 'Dodaj dom'; ?></button>
                        <?php if ($editingHouse): ?>
                            <a class="button-link" href="admin.php">Anuluj edycję</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <?php
                // Render the shared houses component for admin (show filters)
                $showFilter = true;
                $admin = true;
                $minPrice = $minPrice ?? null;
                $maxPrice = $maxPrice ?? null;
                $currentPage = $currentPage ?? 1;
                $totalPages = $totalPages ?? 1;
                $baseUrl = 'admin.php';
                include __DIR__ . '/houses_component.php';
            ?>
        <?php endif; ?>
    </main>

    <?php if (is_admin_authenticated()):
        $ms = (int)(($_SESSION['admin_expires'] ?? 0) - time()) * 1000;
        if ($ms < 0) $ms = 0;
    ?>
    <script>
        setTimeout(function(){ location.reload(); }, <?php echo $ms; ?>);
    </script>
    <?php endif; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const editorTile = document.querySelector('[data-admin-editor-tile]');
            const editorToggleButton = document.querySelector('[data-admin-toggle]');

            if (editorTile && editorToggleButton) {
                const applyEditorState = function (isOpen) {
                    editorTile.classList.toggle('is-open', isOpen);
                    editorTile.classList.toggle('is-collapsed', !isOpen);
                    editorToggleButton.textContent = isOpen ? 'Zamknij formularz' : 'Dodaj nowy dom';
                };

                editorToggleButton.addEventListener('click', function () {
                    const isOpen = !editorTile.classList.contains('is-open');
                    applyEditorState(isOpen);
                });

                if (editorTile.classList.contains('is-open')) {
                    applyEditorState(true);
                } else {
                    applyEditorState(false);
                }
            }

            const latInput = document.querySelector('input[name="latitude"]');
            const lngInput = document.querySelector('input[name="longitude"]');
            const mapElement = document.getElementById('adminMap');
            const uploadInput = document.querySelector('input[name="primary_image_file"]');
            const uploadPreview = document.getElementById('selected-image-preview');
            const uploadPreviewImg = document.getElementById('selected-image-preview-img');

            if (uploadInput && uploadPreview && uploadPreviewImg) {
                uploadInput.addEventListener('change', function () {
                    const file = this.files && this.files[0];
                    if (!file) {
                        uploadPreview.style.display = 'none';
                        uploadPreviewImg.src = '';
                        return;
                    }

                    if (!file.type.startsWith('image/')) {
                        uploadPreview.style.display = 'none';
                        uploadPreviewImg.src = '';
                        return;
                    }

                    const reader = new FileReader();
                    reader.onload = function (event) {
                        uploadPreviewImg.src = event.target.result;
                        uploadPreview.style.display = 'block';
                    };
                    reader.readAsDataURL(file);
                });
            }

            if (!latInput || !lngInput || !mapElement) {
                return;
            }

            const GOOGLE_MAPS_API_KEY = <?php echo json_encode($googleMapsApiKey, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
            const GOOGLE_MAPS_KEY_IS_PLACEHOLDER = !GOOGLE_MAPS_API_KEY || GOOGLE_MAPS_API_KEY.includes('YOUR_GOOGLE_MAPS_API_KEY') || GOOGLE_MAPS_API_KEY.includes('YOUR_GOOGLE_MAPS_API_KEY');

            function showMapFallback(message) {
                mapElement.innerHTML = '<div class="map-editor-fallback">' + message + '</div>';
            }

            function readCoordinates() {
                const lat = parseFloat(latInput.value);
                const lng = parseFloat(lngInput.value);

                if (Number.isFinite(lat) && Number.isFinite(lng)) {
                    return { lat, lng };
                }

                return { lat: 52.2297, lng: 21.0122 };
            }

            function normalizeLatLng(position) {
                if (!position) {
                    return null;
                }

                if (typeof position.lat === 'function' && typeof position.lng === 'function') {
                    return { lat: position.lat(), lng: position.lng() };
                }

                if (Number.isFinite(position.lat) && Number.isFinite(position.lng)) {
                    return { lat: position.lat, lng: position.lng };
                }

                return null;
            }

            function updateInputsFromMarker(position) {
                const normalized = normalizeLatLng(position);
                if (!normalized) {
                    return;
                }

                latInput.value = Number(normalized.lat).toFixed(8);
                lngInput.value = Number(normalized.lng).toFixed(8);
            }

            function refreshMapMarker(map, marker, position) {
                if (!marker) {
                    return;
                }

                const normalized = normalizeLatLng(position);
                if (!normalized) {
                    return;
                }

                marker.setPosition(normalized);
                map.panTo(normalized);
            }

            function initAdminMap() {
                if (typeof google === 'undefined' || !google.maps) {
                    showMapFallback('Google Maps API nie jest dostępne. Ustaw poprawny klucz API w pliku admin.php.');
                    return;
                }

                const center = readCoordinates();
                const map = new google.maps.Map(mapElement, {
                    center: center,
                    zoom: 10,
                    mapTypeControl: false,
                    streetViewControl: false,
                    fullscreenControl: true,
                    zoomControl: true
                });

                let marker = new google.maps.Marker({
                    map: map,
                    position: center,
                    draggable: true,
                    title: 'Położenie nieruchomości'
                });

                marker.addListener('dragend', function (event) {
                    updateInputsFromMarker(event.latLng);
                });

                map.addListener('click', function (event) {
                    const position = event.latLng;
                    refreshMapMarker(map, marker, position);
                    updateInputsFromMarker(position);
                });

                function syncFromInputs() {
                    const value = readCoordinates();
                    if (value.lat === 52.2297 && value.lng === 21.0122) {
                        return;
                    }
                    refreshMapMarker(map, marker, value);
                }

                latInput.addEventListener('input', syncFromInputs);
                lngInput.addEventListener('input', syncFromInputs);
            }

            if (GOOGLE_MAPS_KEY_IS_PLACEHOLDER) {
                showMapFallback('Dodaj realny klucz Google Maps API w admin.php, aby aktywować mapę do kliknięcia.');
                return;
            }

            if (typeof google !== 'undefined' && google.maps) {
                initAdminMap();
                return;
            }

            const script = document.createElement('script');
            script.src = 'https://maps.googleapis.com/maps/api/js?key=' + encodeURIComponent(GOOGLE_MAPS_API_KEY) + '&callback=adminMapEditorCallback';
            script.async = true;
            script.defer = true;
            window.adminMapEditorCallback = initAdminMap;
            document.head.appendChild(script);
        });
    </script>
</body>
</html>
