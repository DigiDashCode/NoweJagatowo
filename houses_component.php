<?php
// Expected variables when including:
// $houses (array), $showFilter (bool), $admin (bool),
// $minPrice, $maxPrice, $currentPage, $totalPages, $baseUrl

if (!isset($houses)) $houses = [];
if (!isset($showFilter)) $showFilter = false;
if (!isset($admin)) $admin = false;
if (!isset($currentPage)) $currentPage = 1;
if (!isset($totalPages)) $totalPages = 1;
if (!isset($baseUrl)) $baseUrl = basename(__FILE__);

?>
<section class="filter-panel" <?php if (!$showFilter) echo 'style="display:none;"'; ?>>
<?php if ($showFilter): ?>
    <h2>Filtr cen</h2>
    <form method="get" action="<?php echo htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8'); ?>">
        <div class="filter-row">
            <label>Min cena:<input type="number" name="min_price" min="0" step="any" placeholder="0" value="<?php echo htmlspecialchars($minPrice ?? ''); ?>" oninvalid="this.setCustomValidity('Podaj poprawną wartość (liczba dodatnia)')" oninput="this.setCustomValidity('')"></label>
            <label>Max cena:<input type="number" name="max_price" min="0" step="any" placeholder="0" value="<?php echo htmlspecialchars($maxPrice ?? ''); ?>" oninvalid="this.setCustomValidity('Podaj poprawną wartość (liczba dodatnia)')" oninput="this.setCustomValidity('')"></label>
            <button type="submit">Pokaż</button>
        </div>
    </form>
<?php endif; ?>
</section>

<section id="results">
    <div class="grid">
        <?php if (empty($houses)): ?>
            <p>Brak domów w podanym przedziale cenowym.</p>
        <?php else: ?>
            <?php foreach ($houses as $index => $house): ?>
                <?php $isFirstHouse = $index === 0 && !$admin; ?>
                <div class="card-item">
                    <a class="card<?php echo $isFirstHouse ? ' house-card-bouncy' : ''; ?>" href="detail.php?id=<?php echo $house['id']; ?>" <?php echo $isFirstHouse ? 'data-bouncy-card="true"' : ''; ?>>
                        <img src="<?php echo htmlspecialchars(imageUrl($house['image_url']), ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($house['title'], ENT_QUOTES, 'UTF-8'); ?>">
                        <div class="card-content">
                            <h3><?php echo htmlspecialchars($house['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
                            <p class="price"><?php echo formatPrice($house['price']); ?></p>
                            <p class="location"><?php echo htmlspecialchars($house['location'], ENT_QUOTES, 'UTF-8'); ?></p>
                        </div>
                    </a>
                    <?php if ($admin): ?>
                        <div class="card-admin-actions">
                            <a class="admin-action-link" href="admin.php?action=edit&id=<?php echo $house['id']; ?>">Edytuj</a>
                            <form class="admin-delete-form" method="post">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="delete_house_id" value="<?php echo $house['id']; ?>">
                                <button type="submit" class="admin-action-button">Usuń</button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>

<?php if ($totalPages > 1): ?>
    <nav class="pagination">
        <?php
        $queryBase = [];
        if (isset($minPrice) && $minPrice !== null && $minPrice !== '') $queryBase['min_price'] = $minPrice;
        if (isset($maxPrice) && $maxPrice !== null && $maxPrice !== '') $queryBase['max_price'] = $maxPrice;
        for ($i = 1; $i <= $totalPages; $i++):
            $query = $queryBase;
            $query['page'] = $i;
            $url = $baseUrl . '?' . http_build_query($query);
        ?>
            <a class="page-link<?php echo $i === $currentPage ? ' active' : ''; ?>" href="<?php echo htmlspecialchars($url, ENT_QUOTES, 'UTF-8'); ?>"><?php echo $i; ?></a>
        <?php endfor; ?>
    </nav>
<?php endif; ?>
