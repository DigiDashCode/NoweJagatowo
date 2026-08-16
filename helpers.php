<?php
function formatPrice($value) {
    return number_format($value, 0, ',', ' ') . ' zł';
}

function imageUrl($url) {
    return $url ?: 'https://images.unsplash.com/photo-1505693416388-ac5ce068fe85?auto=format&fit=crop&w=1000&q=80';
}

?>
