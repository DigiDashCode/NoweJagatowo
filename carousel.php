<?php
$carouselImages = [];
try {
    $stmt = $pdo->query('SELECT image_url, caption FROM carousel_images ORDER BY position ASC');
    $carouselImages = $stmt->fetchAll();
} catch (Exception $e) {
    $carouselImages = [
        [
            'image_url' => 'https://images.unsplash.com/photo-1494526585095-c41746248156?auto=format&fit=crop&w=1200&q=80',
            'caption' => 'Ekskluzywne wnętrza dla rodzin',
        ],
        [
            'image_url' => 'https://images.unsplash.com/photo-1505693416388-ac5ce068fe85?auto=format&fit=crop&w=1200&q=80',
            'caption' => 'Domy z pięknym ogrodem',
        ],
        [
            'image_url' => 'https://images.unsplash.com/photo-1512917774080-9991f1c4c750?auto=format&fit=crop&w=1200&q=80',
            'caption' => 'Nowoczesne projekty z widokiem',
        ],
        [
            'image_url' => 'https://images.unsplash.com/photo-1449844908441-8829872d2607?auto=format&fit=crop&w=1200&q=80',
            'caption' => 'Przytulne domy blisko natury',
        ],
        [
            'image_url' => 'https://images.unsplash.com/photo-1524758631624-e2822e304c36?auto=format&fit=crop&w=1200&q=80',
            'caption' => 'Ekskluzywne przestrzenie do relaksu',
        ],
    ];
}

if (empty($carouselImages)) {
    return;
}
?>
<div class="carousel" aria-label="Galeria domów">
    <div class="carousel-track">
        <?php foreach ($carouselImages as $index => $item): ?>
            <div class="carousel-slide<?php echo $index === 0 ? ' active' : ''; ?>">
                <img src="<?php echo htmlspecialchars($item['image_url'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($item['caption'] ?? 'Dom', ENT_QUOTES, 'UTF-8'); ?>">
                <?php if (!empty($item['caption'])): ?>
                    <div class="carousel-caption"><?php echo htmlspecialchars($item['caption'], ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
    <div class="carousel-dots" role="tablist"></div>
</div>
