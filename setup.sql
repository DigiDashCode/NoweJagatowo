-- Create the database and tables for the house listing application
CREATE DATABASE IF NOT EXISTS house_sale CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE house_sale;

-- Disable foreign key checks during seed inserts to avoid ordering issues
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS houses (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(12,2) NOT NULL,
    location VARCHAR(255) NOT NULL,
    area INT UNSIGNED NOT NULL DEFAULT 0,
    PowierzchniaUżytkowa DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    PowierzchniaDziałki DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    LiczbaPokoi INT UNSIGNED NOT NULL DEFAULT 0,
    CenaOdPowierzchniUżytkowejBrutto DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    CenaZaM2Brutto DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    status VARCHAR(32) NOT NULL DEFAULT 'Dostępne',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- New fields for detailed listing
-- PowierzchniaUżytkowa: DECIMAL(10,2)
-- PowierzchniaDziałki: DECIMAL(10,2)
-- LiczbaPokoi: INT UNSIGNED
-- CenaOdPowierzchniUżytkowejBrutto: DECIMAL(14,2)
-- CenaZaM2Brutto: DECIMAL(12,2)

-- If applying to an existing database, run:
-- ALTER TABLE houses ADD COLUMN PowierzchniaUżytkowa DECIMAL(10,2) DEFAULT 0;
-- ALTER TABLE houses ADD COLUMN PowierzchniaDziałki DECIMAL(10,2) DEFAULT 0;
-- ALTER TABLE houses ADD COLUMN LiczbaPokoi INT UNSIGNED DEFAULT 0;
-- ALTER TABLE houses ADD COLUMN CenaOdPowierzchniUżytkowejBrutto DECIMAL(14,2) DEFAULT 0;
-- ALTER TABLE houses ADD COLUMN CenaZaM2Brutto DECIMAL(12,2) DEFAULT 0;
-- UPDATE houses SET PowierzchniaUżytkowa = 0 WHERE PowierzchniaUżytkowa IS NULL;
-- UPDATE houses SET PowierzchniaDziałki = 0 WHERE PowierzchniaDziałki IS NULL;
-- UPDATE houses SET LiczbaPokoi = 0 WHERE LiczbaPokoi IS NULL;
-- UPDATE houses SET CenaOdPowierzchniUżytkowejBrutto = 0 WHERE CenaOdPowierzchniUżytkowejBrutto IS NULL;
-- UPDATE houses SET CenaZaM2Brutto = 0 WHERE CenaZaM2Brutto IS NULL;

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

INSERT INTO houses (title, description, price, location, area, status, PowierzchniaUżytkowa, PowierzchniaDziałki, LiczbaPokoi, CenaOdPowierzchniUżytkowejBrutto, CenaZaM2Brutto)
VALUES
('Dom w Złotnikach', 'Przytulny dom jednorodzinny z dużym ogrodem i garażem. Dobra komunikacja do centrum miasta.', 890000.00, 'Złotniki', 180, 'Dostępne', 149.50, 800.00, 4, 890000.00, 5956.52),
('Willa pod lasem', 'Zielona willa z tarasem, blisko ścieżek rowerowych i szkół. Wysoki standard wykończenia.', 1450000.00, 'Łódź', 320, 'Dostępne', 285.00, 1200.00, 6, 1450000.00, 5087.72),
('Nowoczesny dom nad jeziorem', 'Duży dom z panoramicznym widokiem na jezioro i prywatnym dostępem do plaży.', 2050000.00, 'Mazury', 265, 'Rezerwacja', 240.00, 1500.00, 5, 2050000.00, 8541.67),
('Mały dom gospodarczy', 'Ekonomiczny dom, idealny na pierwszy zakup lub jako inwestycja pod wynajem.', 560000.00, 'Białystok', 95, 'Dostępne', 78.50, 300.00, 2, 560000.00, 7133.76),
('Dom z ogrodem', 'Rodzinny dom w spokojnej okolicy z dużym ogrodem i miejscem na letni grill.', 1180000.00, 'Gdańsk', 210, 'Sprzedane', 180.00, 1000.00, 4, 1180000.00, 6555.56),
('Skromny domek miejskie', 'Praktyczny domek w centrum, blisko komunikacji miejskiej i sklepów.', 420000.00, 'Kraków', 70, 'Dostępne', 65.00, 120.00, 2, 420000.00, 6461.54),
('Nowoczesna willa z basenem', 'Luksusowa willa z basenem i dużym ogrodem, wysoki standard.', 3250000.00, 'Warszawa', 450, 'Dostępne', 380.00, 1600.00, 7, 3250000.00, 8552.63),
('Dom typu bungalow', 'Parterowy dom idealny dla seniorów, duży ogród.', 760000.00, 'Poznań', 150, 'Dostępne', 130.00, 600.00, 3, 760000.00, 5846.15),
('Rezydencja nad rzeką', 'Ekskluzywna rezydencja z prywatnym nabrzeżem.', 4850000.00, 'Wrocław', 780, 'Dostępne', 620.00, 2500.00, 9, 4850000.00, 7822.58),
('Dom w górach', 'Chata z widokiem na góry, idealna jako druga rezydencja.', 980000.00, 'Zakopane', 200, 'Dostępne', 160.00, 900.00, 4, 980000.00, 6125.00),
('Mieszkanie z ogródkiem', 'Przytulne mieszkanie z niewielkim ogródkiem.', 360000.00, 'Szczecin', 60, 'Dostępne', 55.00, 80.00, 2, 360000.00, 6545.45),
('Dom energooszczędny', 'Nowoczesny dom z instalacjami energooszczędnymi.', 1320000.00, 'Lublin', 220, 'Rezerwacja', 190.00, 700.00, 5, 1320000.00, 6947.37),
('Domek na wsi', 'Spokojna okolica, duża działka rolna.', 680000.00, 'Łódź', 140, 'Dostępne', 120.00, 2000.00, 3, 680000.00, 5666.67),
('Nowe osiedle', 'Domy w zabudowie bliźniaczej, nowoczesne rozwiązania.', 750000.00, 'Gdynia', 125, 'Dostępne', 110.00, 250.00, 3, 750000.00, 6818.18),
('Dom z potencjałem', 'Nieruchomość do remontu z dużym potencjałem inwestycyjnym.', 490000.00, 'Opole', 160, 'Dostępne', 140.00, 700.00, 3, 490000.00, 3500.00),
('Kamienica po remoncie', 'Odnowiona kamienica z kilkoma lokalami mieszkalnymi.', 2150000.00, 'Katowice', 900, 'Dostępne', 720.00, 400.00, 12, 2150000.00, 2986.11),
('Dom ekologiczny', 'Zielone rozwiązania, panele słoneczne i ogród warzywny.', 940000.00, 'Poznań', 175, 'Dostępne', 150.00, 450.00, 4, 940000.00, 6266.67),
('Willetta miejska', 'Elegancka willa w zabudowie miejskiej.', 1280000.00, 'Warszawa', 280, 'Dostępne', 200.00, 320.00, 5, 1280000.00, 6400.00),
('Domek nad stawem', 'Mały domek rekreacyjny z dostępem do stawu.', 330000.00, 'Bory Tucholskie', 85, 'Dostępne', 75.00, 600.00, 2, 330000.00, 4400.00),
('Nowe apartamenty', 'Kompleks apartamentów z udogodnieniami.', 2100000.00, 'Sopot', 1500, 'Nieaktywne', 1400.00, 200.00, 20, 2100000.00, 1500.00),
('Dom rodzinny', 'Przestronny dom rodzinny z placem zabaw dla dzieci.', 890000.00, 'Kielce', 240, 'Dostępne', 200.00, 900.00, 5, 890000.00, 4450.00);

-- If you are applying this to an existing DB, add the status column and set default values:
-- ALTER TABLE houses ADD COLUMN status VARCHAR(32) NOT NULL DEFAULT 'Dostępne';
-- UPDATE houses SET status = 'Dostępne' WHERE status IS NULL;

-- Insert house images using house title lookup to ensure correct house_id
INSERT INTO house_images (house_id, url, is_primary)
SELECT id, 'https://images.unsplash.com/photo-1505693416388-ac5ce068fe85?auto=format&fit=crop&w=1000&q=80', 1 FROM houses WHERE title = 'Dom w Złotnikach' LIMIT 1;
INSERT INTO house_images (house_id, url, is_primary)
SELECT id, 'https://images.unsplash.com/photo-1494526585095-c41746248156?auto=format&fit=crop&w=1000&q=80', 1 FROM houses WHERE title = 'Willa pod lasem' LIMIT 1;
INSERT INTO house_images (house_id, url, is_primary)
SELECT id, 'https://images.unsplash.com/photo-1512917774080-9991f1c4c750?auto=format&fit=crop&w=1000&q=80', 1 FROM houses WHERE title = 'Nowoczesny dom nad jeziorem' LIMIT 1;
INSERT INTO house_images (house_id, url, is_primary)
SELECT id, 'https://images.unsplash.com/photo-1449844908441-8829872d2607?auto=format&fit=crop&w=1000&q=80', 1 FROM houses WHERE title = 'Mały dom gospodarczy' LIMIT 1;
INSERT INTO house_images (house_id, url, is_primary)
SELECT id, 'https://images.unsplash.com/photo-1524758631624-e2822e304c36?auto=format&fit=crop&w=1000&q=80', 1 FROM houses WHERE title = 'Dom z ogrodem' LIMIT 1;
INSERT INTO house_images (house_id, url, is_primary)
SELECT id, 'https://images.unsplash.com/photo-1560185127-6ec8c4b68f5b?auto=format&fit=crop&w=1000&q=80', 1 FROM houses WHERE title = 'Skromny domek miejskie' LIMIT 1;
INSERT INTO house_images (house_id, url, is_primary)
SELECT id, 'https://images.unsplash.com/photo-1505692794403-76f9b6d3d8c3?auto=format&fit=crop&w=1000&q=80', 1 FROM houses WHERE title = 'Nowoczesna willa z basenem' LIMIT 1;
INSERT INTO house_images (house_id, url, is_primary)
SELECT id, 'https://images.unsplash.com/photo-1505691938895-1758d7feb511?auto=format&fit=crop&w=1000&q=80', 1 FROM houses WHERE title = 'Dom typu bungalow' LIMIT 1;
INSERT INTO house_images (house_id, url, is_primary)
SELECT id, 'https://images.unsplash.com/photo-1475855581690-80accde3ae59?auto=format&fit=crop&w=1000&q=80', 1 FROM houses WHERE title = 'Rezydencja nad rzeką' LIMIT 1;
INSERT INTO house_images (house_id, url, is_primary)
SELECT id, 'https://images.unsplash.com/photo-1490578474895-699cd4e2cf59?auto=format&fit=crop&w=1000&q=80', 1 FROM houses WHERE title = 'Dom w górach' LIMIT 1;
INSERT INTO house_images (house_id, url, is_primary)
SELECT id, 'https://images.unsplash.com/photo-1472224371017-08207f84aaae?auto=format&fit=crop&w=1000&q=80', 1 FROM houses WHERE title = 'Mieszkanie z ogródkiem' LIMIT 1;
INSERT INTO house_images (house_id, url, is_primary)
SELECT id, 'https://images.unsplash.com/photo-1455763916899-e8b50eca9967?auto=format&fit=crop&w=1000&q=80', 1 FROM houses WHERE title = 'Dom energooszczędny' LIMIT 1;
INSERT INTO house_images (house_id, url, is_primary)
SELECT id, 'https://images.unsplash.com/photo-1467269204594-9661b134dd2b?auto=format&fit=crop&w=1000&q=80', 1 FROM houses WHERE title = 'Domek na wsi' LIMIT 1;
INSERT INTO house_images (house_id, url, is_primary)
SELECT id, 'https://images.unsplash.com/photo-1449844882338-4b6f7b7b3b3c?auto=format&fit=crop&w=1000&q=80', 1 FROM houses WHERE title = 'Nowe osiedle' LIMIT 1;
INSERT INTO house_images (house_id, url, is_primary)
SELECT id, 'https://images.unsplash.com/photo-1484154218962-a197022b5858?auto=format&fit=crop&w=1000&q=80', 1 FROM houses WHERE title = 'Dom z potencjałem' LIMIT 1;
INSERT INTO house_images (house_id, url, is_primary)
SELECT id, 'https://images.unsplash.com/photo-1505693416388-9f1b6f9f1f1a?auto=format&fit=crop&w=1000&q=80', 1 FROM houses WHERE title = 'Kamienica po remoncie' LIMIT 1;
INSERT INTO house_images (house_id, url, is_primary)
SELECT id, 'https://images.unsplash.com/photo-1499951360447-b19be8fe80f5?auto=format&fit=crop&w=1000&q=80', 1 FROM houses WHERE title = 'Dom ekologiczny' LIMIT 1;
INSERT INTO house_images (house_id, url, is_primary)
SELECT id, 'https://images.unsplash.com/photo-1507089947368-19c1da9775ae?auto=format&fit=crop&w=1000&q=80', 1 FROM houses WHERE title = 'Willetta miejska' LIMIT 1;
INSERT INTO house_images (house_id, url, is_primary)
SELECT id, 'https://images.unsplash.com/photo-1481770499092-6c97a7f43baf?auto=format&fit=crop&w=1000&q=80', 1 FROM houses WHERE title = 'Domek nad stawem' LIMIT 1;
INSERT INTO house_images (house_id, url, is_primary)
SELECT id, 'https://images.unsplash.com/photo-1499955085172-a104c9463ece?auto=format&fit=crop&w=1000&q=80', 1 FROM houses WHERE title = 'Nowe apartamenty' LIMIT 1;
INSERT INTO house_images (house_id, url, is_primary)
SELECT id, 'https://images.unsplash.com/photo-1505693416388-2b2b2b2b2b2b?auto=format&fit=crop&w=1000&q=80', 1 FROM houses WHERE title = 'Dom rodzinny' LIMIT 1;

INSERT INTO carousel_images (image_url, caption, position)
VALUES
('https://images.unsplash.com/photo-1494526585095-c41746248156?auto=format&fit=crop&w=1200&q=80', 'Ekskluzywne wnętrza dla rodzin', 1),
('https://images.unsplash.com/photo-1505693416388-ac5ce068fe85?auto=format&fit=crop&w=1200&q=80', 'Domy z pięknym ogrodem', 2),
('https://images.unsplash.com/photo-1512917774080-9991f1c4c750?auto=format&fit=crop&w=1200&q=80', 'Nowoczesne projekty z widokiem', 3),
('https://images.unsplash.com/photo-1449844908441-8829872d2607?auto=format&fit=crop&w=1200&q=80', 'Przytulne domy blisko natury', 4),
('https://images.unsplash.com/photo-1524758631624-e2822e304c36?auto=format&fit=crop&w=1200&q=80', 'Ekskluzywne przestrzenie do relaksu', 5);

-- Re-enable foreign key checks now that seed data is inserted
SET FOREIGN_KEY_CHECKS = 1;
