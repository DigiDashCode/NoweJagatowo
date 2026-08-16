<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
session_start();
$message = '';
$messageType = 'success';
$editingHouse = null;
$editingImages = [];

function is_admin_authenticated() {
    return isset($_SESSION['admin_authenticated']) && $_SESSION['admin_authenticated'] === true && time() < ($_SESSION['admin_expires'] ?? 0);
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
                $location = trim($_POST['location'] ?? '');
                $bedrooms = intval($_POST['bedrooms'] ?? 0);
                $bathrooms = intval($_POST['bathrooms'] ?? 0);
                $area = intval($_POST['area'] ?? 0);
                $status = trim($_POST['status'] ?? 'Dostępne');
                $primaryImage = trim($_POST['primary_image_url'] ?? '');
                $imageUrls = array_filter(array_map('trim', explode(',', $_POST['image_urls'] ?? '')));

                if ($title === '' || $price <= 0 || $location === '' || $primaryImage === '') {
                    $messageType = 'error';
                    $message = 'Wypełnij pola: tytuł, cena, lokalizacja i główny obraz.';
                } else {
                    try {
                        $pdo->beginTransaction();
                        $status = trim($_POST['status'] ?? 'Dostępne');
                        $allowed = statusOptions(true);
                        if (!in_array($status, $allowed, true)) $status = 'Dostępne';

                        $stmt = $pdo->prepare('UPDATE houses SET title = ?, description = ?, price = ?, location = ?, bedrooms = ?, bathrooms = ?, area = ?, status = ? WHERE id = ?');
                        $stmt->execute([$title, $description, $price, $location, $bedrooms, $bathrooms, $area, $status, $houseId]);

                        $stmt = $pdo->prepare('DELETE FROM house_images WHERE house_id = ?');
                        $stmt->execute([$houseId]);

                        $stmt = $pdo->prepare('INSERT INTO house_images (house_id, url, is_primary) VALUES (?, ?, ?)');
                        $stmt->execute([$houseId, $primaryImage, 1]);
                        foreach ($imageUrls as $url) {
                            if ($url !== '') {
                                $stmt->execute([$houseId, $url, 0]);
                            }
                        }

                        $pdo->commit();
                        $messageType = 'success';
                        $message = 'Dom został zaktualizowany.';
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
                $bedrooms = intval($_POST['bedrooms'] ?? 0);
                $bathrooms = intval($_POST['bathrooms'] ?? 0);
                $area = intval($_POST['area'] ?? 0);
                $primaryImage = trim($_POST['primary_image_url'] ?? '');
                $imageUrls = array_filter(array_map('trim', explode(',', $_POST['image_urls'] ?? '')));

                $allowed = statusOptions(true);
                if (!in_array($status, $allowed, true)) $status = 'Dostępne';

                if ($title === '' || $price <= 0 || $location === '' || $primaryImage === '') {
                    $messageType = 'error';
                    $message = 'Wypełnij pola: tytuł, cena, lokalizacja i główny obraz.';
                } else {
                    try {
                        $pdo->beginTransaction();
                        $stmt = $pdo->prepare('INSERT INTO houses (title, description, price, location, bedrooms, bathrooms, area, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
                        $stmt->execute([$title, $description, $price, $location, $bedrooms, $bathrooms, $area, $status]);
                        $houseId = $pdo->lastInsertId();

                        $stmt = $pdo->prepare('INSERT INTO house_images (house_id, url, is_primary) VALUES (?, ?, ?)');
                        $stmt->execute([$houseId, $primaryImage, 1]);

                        foreach ($imageUrls as $url) {
                            if ($url !== '') {
                                $stmt->execute([$houseId, $url, 0]);
                            }
                        }

                        $pdo->commit();
                        $messageType = 'success';
                        $message = 'Dom został dodany pomyślnie.';
                        $_POST = [];
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
    $stmt = $pdo->prepare('SELECT h.id, h.title, h.description, h.price, h.location, h.bedrooms, h.bathrooms, h.area, h.status, COALESCE(hi.url, \'https://images.unsplash.com/photo-1505693416388-ac5ce068fe85?auto=format&fit=crop&w=1000&q=80\') AS primary_image_url
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
            <form method="post">
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
                <label>Sypialnie:<input type="number" name="bedrooms" min="0" value="<?php echo editValue('bedrooms', '0'); ?>" oninvalid="this.setCustomValidity('Podaj poprawną liczbę sypialni (0 lub więcej)')" oninput="this.setCustomValidity('')"></label>
                <label>Łazienki:<input type="number" name="bathrooms" min="0" value="<?php echo editValue('bathrooms', '0'); ?>" oninvalid="this.setCustomValidity('Podaj poprawną liczbę łazienek (0 lub więcej)')" oninput="this.setCustomValidity('')"></label>
                <label>Powierzchnia (m2):<input type="number" name="area" min="0" value="<?php echo editValue('area', '0'); ?>" oninvalid="this.setCustomValidity('Podaj poprawny metraż (0 lub więcej)')" oninput="this.setCustomValidity('')"></label>
                <label>Główny obraz (URL):<input type="url" name="primary_image_url" value="<?php echo editValue('primary_image_url'); ?>" required oninvalid="this.setCustomValidity('Podaj poprawny adres URL obrazu')" oninput="this.setCustomValidity('')"></label>
                <label>Dodatkowe obrazy (URL, oddzielone przecinkami):<textarea name="image_urls"><?php echo $editingHouse ? editImages() : old('image_urls'); ?></textarea></label>
                <button type="submit"><?php echo $editingHouse ? 'Zaktualizuj dom' : 'Dodaj dom'; ?></button>
                <?php if ($editingHouse): ?>
                    <a class="button-link" href="admin.php">Anuluj edycję</a>
                <?php endif; ?>
            </form>

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
</body>
</html>
