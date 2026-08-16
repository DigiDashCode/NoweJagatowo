$(document).ready(function () {
    const $housesGrid = $('#housesGrid');
    const $filterForm = $('#filterForm');

    const houses = [
        {
            title: 'Dom w Złotnikach',
            price: 890000,
            location: 'Złotniki',
            image_url: 'https://images.unsplash.com/photo-1505693416388-ac5ce068fe85?auto=format&fit=crop&w=800&q=80',
        },
        {
            title: 'Willa pod lasem',
            price: 1450000,
            location: 'Łódź',
            image_url: 'https://images.unsplash.com/photo-1494526585095-c41746248156?auto=format&fit=crop&w=800&q=80',
        },
        {
            title: 'Nowoczesny dom nad jeziorem',
            price: 2050000,
            location: 'Mazury',
            image_url: 'https://images.unsplash.com/photo-1512917774080-9991f1c4c750?auto=format&fit=crop&w=800&q=80',
        },
        {
            title: 'Mały dom gospodarczy',
            price: 560000,
            location: 'Białystok',
            image_url: 'https://images.unsplash.com/photo-1449844908441-8829872d2607?auto=format&fit=crop&w=800&q=80',
        },
        {
            title: 'Dom z ogrodem',
            price: 1180000,
            location: 'Gdańsk',
            image_url: 'https://images.unsplash.com/photo-1505693416388-ac5ce068fe85?auto=format&fit=crop&w=800&q=80',
        },
    ];

    function formatPrice(value) {
        return value.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ') + ' zł';
    }

    function loadHouses() {
        const minPrice = parseInt($('[name="min_price"]').val(), 10);
        const maxPrice = parseInt($('[name="max_price"]').val(), 10);

        const filtered = houses.filter(function (house) {
            if (!isNaN(minPrice) && house.price < minPrice) {
                return false;
            }
            if (!isNaN(maxPrice) && house.price > maxPrice) {
                return false;
            }
            return true;
        });

        $housesGrid.empty();
        if (!filtered.length) {
            $housesGrid.append('<p>Brak domów w podanym przedziale cenowym.</p>');
            return;
        }

        filtered.forEach(function (house, index) {
            const html = `
                <a class="card" href="detail.php?id=${index + 1}">
                    <img src="${house.image_url}" alt="${house.title}">
                    <div class="card-content">
                        <h3>${house.title}</h3>
                        <p class="price">${formatPrice(house.price)}</p>
                        <p class="location">${house.location}</p>
                    </div>
                </a>
            `;
            $housesGrid.append(html);
        });
    }

    $filterForm.on('submit', function (event) {
        event.preventDefault();
        loadHouses();
    });

    loadHouses();
});
