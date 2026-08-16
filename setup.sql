-- Create the database and tables for the house listing application
CREATE DATABASE IF NOT EXISTS house_sale CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE house_sale;

CREATE TABLE IF NOT EXISTS houses (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(12,2) NOT NULL,
    location VARCHAR(255) NOT NULL,
    bedrooms INT UNSIGNED NOT NULL DEFAULT 0,
    bathrooms INT UNSIGNED NOT NULL DEFAULT 0,
    area INT UNSIGNED NOT NULL DEFAULT 0,
    status VARCHAR(32) NOT NULL DEFAULT 'Dostępne',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS house_images (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    house_id INT UNSIGNED NOT NULL,
    url VARCHAR(1000) NOT NULL,
    is_primary TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (house_id) REFERENCES houses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS carousel_images (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    image_url VARCHAR(1000) NOT NULL,
    caption VARCHAR(255) DEFAULT NULL,
    position INT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO houses (title, description, price, location, bedrooms, bathrooms, area)
VALUES
('Dom w Złotnikach', 'Przytulny dom jednorodzinny z dużym ogrodem i garażem. Dobra komunikacja do centrum miasta.', 890000, 'Złotniki', 4, 2, 180),
('Willa pod lasem', 'Zielona willa z tarasem, blisko ścieżek rowerowych i szkół. Wysoki standard wykończenia.', 1450000, 'Łódź', 5, 3, 320),
('Nowoczesny dom nad jeziorem', 'Duży dom z panoramicznym widokiem na jezioro i prywatnym dostępem do plaży.', 2050000, 'Mazury', 4, 3, 265),
('Mały dom gospodarczy', 'Ekonomiczny dom, idealny na pierwszy zakup lub jako inwestycja pod wynajem.', 560000, 'Białystok', 2, 1, 95),
('Dom z ogrodem', 'Rodzinny dom w spokojnej okolicy z dużym ogrodem i miejscem na letni grill.', 1180000, 'Gdańsk', 4, 2, 210);

-- If you are applying this to an existing DB, add the status column and set default values:
-- ALTER TABLE houses ADD COLUMN status VARCHAR(32) NOT NULL DEFAULT 'Dostępne';
-- UPDATE houses SET status = 'Dostępne' WHERE status IS NULL;

INSERT INTO house_images (house_id, url, is_primary)
VALUES
(1, 'https://images.unsplash.com/photo-1505693416388-ac5ce068fe85?auto=format&fit=crop&w=1000&q=80', 1),
(2, 'https://images.unsplash.com/photo-1494526585095-c41746248156?auto=format&fit=crop&w=1000&q=80', 1),
(3, 'https://images.unsplash.com/photo-1512917774080-9991f1c4c750?auto=format&fit=crop&w=1000&q=80', 1),
(4, 'https://images.unsplash.com/photo-1449844908441-8829872d2607?auto=format&fit=crop&w=1000&q=80', 1),
(5, 'https://images.unsplash.com/photo-1524758631624-e2822e304c36?auto=format&fit=crop&w=1000&q=80', 1);

INSERT INTO carousel_images (image_url, caption, position)
VALUES
('https://images.unsplash.com/photo-1494526585095-c41746248156?auto=format&fit=crop&w=1200&q=80', 'Ekskluzywne wnętrza dla rodzin', 1),
('https://images.unsplash.com/photo-1505693416388-ac5ce068fe85?auto=format&fit=crop&w=1200&q=80', 'Domy z pięknym ogrodem', 2),
('https://images.unsplash.com/photo-1512917774080-9991f1c4c750?auto=format&fit=crop&w=1200&q=80', 'Nowoczesne projekty z widokiem', 3),
('https://images.unsplash.com/photo-1449844908441-8829872d2607?auto=format&fit=crop&w=1200&q=80', 'Przytulne domy blisko natury', 4),
('https://images.unsplash.com/photo-1524758631624-e2822e304c36?auto=format&fit=crop&w=1200&q=80', 'Ekskluzywne przestrzenie do relaksu', 5);
