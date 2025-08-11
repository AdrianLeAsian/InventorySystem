USE db_inventory;

-- Categories
INSERT INTO categories (name) VALUES ('Meat'), ('Vegetables'), ('Drinks'), ('Desserts');

-- Locations
INSERT INTO locations (name) VALUES ('Main Kitchen'), ('Cold Storage'), ('Pantry'), ('Bar');

-- Items
INSERT INTO items (name, category_id, location_id, current_stock, unit, low_stock, max_stock, is_perishable)
VALUES
('Beef Bulgogi', 1, 1, 50, 'kg', 10, 100, 1),
('Kimchi', 2, 2, 30, 'kg', 5, 50, 1),
('Soju', 3, 4, 80, 'bottle', 20, 200, 0),
('Ice Cream', 4, 3, 40, 'tub', 8, 60, 1),
('Rice', 2, 1, 100, 'kg', 20, 300, 0);

-- Item Batches (for perishables)
INSERT INTO item_batches (item_id, expiry_date, quantity) VALUES
(1, '2025-08-20', 20),
(1, '2025-08-25', 30),
(2, '2025-08-15', 10),
(2, '2025-08-22', 20),
(4, '2025-08-18', 15),
(4, '2025-08-30', 25);
