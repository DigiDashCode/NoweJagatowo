<?php
function formatPrice($value) {
    return number_format($value, 0, ',', ' ') . ' zł';
}

function imageUrl($url) {
    return $url ?: 'https://images.unsplash.com/photo-1505693416388-ac5ce068fe85?auto=format&fit=crop&w=1000&q=80';
}

function statusBadgeClass($status) {
    switch ($status) {
        case 'Sprzedane':
            return 'status-sprzedane';
        case 'Rezerwacja':
            return 'status-rezerwacja';
        case 'Dostępne':
            return 'status-dostepne';
        case 'Nieaktywne':
            return 'status-nieaktywne';
        default:
            return 'status-default';
    }
}

function statusOptions($includeNieaktywne = false) {
    $opts = ['Sprzedane', 'Rezerwacja', 'Dostępne'];
    if ($includeNieaktywne) $opts[] = 'Nieaktywne';
    return $opts;
}

function formatPriceDecimals($value) {
    return number_format((float)$value, 2, ',', ' ') . ' zł';
}

?>
