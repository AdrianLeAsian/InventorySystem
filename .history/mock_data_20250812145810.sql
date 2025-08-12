-- Mock Data for Inventory System

-- Clear existing data (optional, uncomment if you want to reset data each time)
-- DELETE FROM inventory_log;
-- DELETE FROM items;
-- DELETE FROM categories;
-- ALTER TABLE categories AUTO_INCREMENT = 1;
-- ALTER TABLE items AUTO_INCREMENT = 1;
-- ALTER TABLE inventory_log AUTO_INCREMENT = 1;

-- Insert Categories
INSERT INTO categories (name, created_at) VALUES
('Electronics', NOW()),
('Home Goods', NOW()),
('Office Supplies', NOW()),
('Books', NOW()),
('Apparel', NOW()),
('Food & Beverage', NOW());

-- Insert Items
INSERT INTO items (name, description, quantity, low_stock_threshold, category_id, created_at) VALUES
('Laptop Pro X', 'High-performance laptop', 15, 5, 1, NOW()),
('Wireless Mouse', 'Ergonomic wireless mouse', 50, 10, 1, NOW()),
('Smart Speaker', 'Voice-controlled smart speaker', 25, 8, 1, NOW()),
('Coffee Maker', '12-cup programmable coffee maker', 10, 3, 2, NOW()),
('Toaster Oven', 'Compact toaster oven', 8, 2, 2, NOW()),
('Desk Lamp LED', 'Adjustable LED desk lamp', 30, 7, 3, NOW()),
('Notebook A4', '200-page A4 notebook', 100, 20, 3, NOW()),
('The Great Novel', 'Bestselling fiction book', 40, 15, 4, NOW()),
('Coding Handbook', 'Comprehensive guide for developers', 20, 5, 4, NOW()),
('T-Shirt Cotton', 'Plain white cotton t-shirt', 75, 25, 5, NOW()),
('Jeans Slim Fit', 'Blue denim slim fit jeans', 35, 10, 5, NOW()),
('Organic Coffee Beans', '1kg bag of organic coffee beans', 60, 15, 6, NOW()),
('Energy Drink 250ml', 'High caffeine energy drink', 120, 30, 6, NOW()),
('Webcam HD', 'Full HD webcam with mic', 22, 6, 1, NOW()),
('External SSD 1TB', 'Portable 1TB SSD', 18, 4, 1, NOW()),
('Blender Pro', 'High-speed professional blender', 7, 2, 2, NOW()),
('Stapler Heavy Duty', 'Heavy duty office stapler', 45, 10, 3, NOW()),
('Pen Set Gel', 'Assorted gel pen set', 80, 20, 3, NOW()),
('Mystery Thriller', 'Gripping mystery novel', 30, 8, 4, NOW()),
('Hoodie Fleece', 'Comfortable fleece hoodie', 55, 18, 5, NOW());

-- Insert Inventory Log Data for Usage Trends (Daily, Weekly, Monthly, Yearly)
-- Daily movements (last 30 days)
INSERT INTO inventory_log (item_id, type, quantity_change, log_date) VALUES
(1, 'out', 2, DATE_SUB(CURDATE(), INTERVAL 1 DAY)),
(2, 'out', 5, DATE_SUB(CURDATE(), INTERVAL 1 DAY)),
(7, 'out', 10, DATE_SUB(CURDATE(), INTERVAL 1 DAY)),
(10, 'out', 8, DATE_SUB(CURDATE(), INTERVAL 1 DAY)),
(1, 'out', 1, DATE_SUB(CURDATE(), INTERVAL 2 DAY)),
(3, 'out', 3, DATE_SUB(CURDATE(), INTERVAL 2 DAY)),
(8, 'out', 5, DATE_SUB(CURDATE(), INTERVAL 2 DAY)),
(12, 'out', 10, DATE_SUB(CURDATE(), INTERVAL 2 DAY)),
(1, 'out', 3, DATE_SUB(CURDATE(), INTERVAL 5 DAY)),
(2, 'out', 7, DATE_SUB(CURDATE(), INTERVAL 5 DAY)),
(7, 'out', 15, DATE_SUB(CURDATE(), INTERVAL 5 DAY)),
(10, 'out', 12, DATE_SUB(CURDATE(), INTERVAL 5 DAY)),
(13, 'out', 20, DATE_SUB(CURDATE(), INTERVAL 5 DAY)),
(1, 'out', 1, DATE_SUB(CURDATE(), INTERVAL 10 DAY)),
(2, 'out', 2, DATE_SUB(CURDATE(), INTERVAL 10 DAY)),
(3, 'out', 1, DATE_SUB(CURDATE(), INTERVAL 10 DAY)),
(4, 'out', 1, DATE_SUB(CURDATE(), INTERVAL 10 DAY)),
(5, 'out', 1, DATE_SUB(CURDATE(), INTERVAL 10 DAY)),
(6, 'out', 2, DATE_SUB(CURDATE(), INTERVAL 10 DAY)),
(7, 'out', 5, DATE_SUB(CURDATE(), INTERVAL 10 DAY)),
(8, 'out', 3, DATE_SUB(CURDATE(), INTERVAL 10 DAY)),
(9, 'out', 1, DATE_SUB(CURDATE(), INTERVAL 10 DAY)),
(10, 'out', 5, DATE_SUB(CURDATE(), INTERVAL 10 DAY)),
(11, 'out', 2, DATE_SUB(CURDATE(), INTERVAL 10 DAY)),
(12, 'out', 8, DATE_SUB(CURDATE(), INTERVAL 10 DAY)),
(13, 'out', 15, DATE_SUB(CURDATE(), INTERVAL 10 DAY)),
(14, 'out', 1, DATE_SUB(CURDATE(), INTERVAL 10 DAY)),
(15, 'out', 1, DATE_SUB(CURDATE(), INTERVAL 10 DAY)),
(16, 'out', 1, DATE_SUB(CURDATE(), INTERVAL 10 DAY)),
(17, 'out', 2, DATE_SUB(CURDATE(), INTERVAL 10 DAY)),
(18, 'out', 5, DATE_SUB(CURDATE(), INTERVAL 10 DAY)),
(19, 'out', 3, DATE_SUB(CURDATE(), INTERVAL 10 DAY)),
(20, 'out', 5, DATE_SUB(CURDATE(), INTERVAL 10 DAY));

-- Weekly movements (last 12 weeks)
INSERT INTO inventory_log (item_id, type, quantity_change, log_date) VALUES
(1, 'out', 5, DATE_SUB(CURDATE(), INTERVAL 3 WEEK)),
(2, 'out', 10, DATE_SUB(CURDATE(), INTERVAL 3 WEEK)),
(7, 'out', 20, DATE_SUB(CURDATE(), INTERVAL 3 WEEK)),
(10, 'out', 15, DATE_SUB(CURDATE(), INTERVAL 3 WEEK)),
(1, 'out', 3, DATE_SUB(CURDATE(), INTERVAL 6 WEEK)),
(3, 'out', 8, DATE_SUB(CURDATE(), INTERVAL 6 WEEK)),
(8, 'out', 12, DATE_SUB(CURDATE(), INTERVAL 6 WEEK)),
(12, 'out', 25, DATE_SUB(CURDATE(), INTERVAL 6 WEEK)),
(1, 'out', 7, DATE_SUB(CURDATE(), INTERVAL 9 WEEK)),
(2, 'out', 15, DATE_SUB(CURDATE(), INTERVAL 9 WEEK)),
(7, 'out', 30, DATE_SUB(CURDATE(), INTERVAL 9 WEEK)),
(10, 'out', 20, DATE_SUB(CURDATE(), INTERVAL 9 WEEK));

-- Monthly movements (last 12 months)
INSERT INTO inventory_log (item_id, type, quantity_change, log_date) VALUES
(1, 'out', 10, DATE_SUB(CURDATE(), INTERVAL 2 MONTH)),
(2, 'out', 20, DATE_SUB(CURDATE(), INTERVAL 2 MONTH)),
(7, 'out', 40, DATE_SUB(CURDATE(), INTERVAL 2 MONTH)),
(10, 'out', 30, DATE_SUB(CURDATE(), INTERVAL 2 MONTH)),
(1, 'out', 8, DATE_SUB(CURDATE(), INTERVAL 5 MONTH)),
(3, 'out', 15, DATE_SUB(CURDATE(), INTERVAL 5 MONTH)),
(8, 'out', 25, DATE_SUB(CURDATE(), INTERVAL 5 MONTH)),
(12, 'out', 50, DATE_SUB(CURDATE(), INTERVAL 5 MONTH)),
(1, 'out', 12, DATE_SUB(CURDATE(), INTERVAL 8 MONTH)),
(2, 'out', 25, DATE_SUB(CURDATE(), INTERVAL 8 MONTH)),
(7, 'out', 50, DATE_SUB(CURDATE(), INTERVAL 8 MONTH)),
(10, 'out', 35, DATE_SUB(CURDATE(), INTERVAL 8 MONTH)),
(1, 'out', 15, DATE_SUB(CURDATE(), INTERVAL 11 MONTH)),
(2, 'out', 30, DATE_SUB(CURDATE(), INTERVAL 11 MONTH)),
(7, 'out', 60, DATE_SUB(CURDATE(), INTERVAL 11 MONTH)),
(10, 'out', 40, DATE_SUB(CURDATE(), INTERVAL 11 MONTH));

-- Yearly movements (last 5 years)
INSERT INTO inventory_log (item_id, type, quantity_change, log_date) VALUES
(1, 'out', 50, DATE_SUB(CURDATE(), INTERVAL 1 YEAR)),
(2, 'out', 100, DATE_SUB(CURDATE(), INTERVAL 1 YEAR)),
(7, 'out', 200, DATE_SUB(CURDATE(), INTERVAL 1 YEAR)),
(10, 'out', 150, DATE_SUB(CURDATE(), INTERVAL 1 YEAR)),
(1, 'out', 40, DATE_SUB(CURDATE(), INTERVAL 3 YEAR)),
(3, 'out', 80, DATE_SUB(CURDATE(), INTERVAL 3 YEAR)),
(8, 'out', 120, DATE_SUB(CURDATE(), INTERVAL 3 YEAR)),
(12, 'out', 250, DATE_SUB(CURDATE(), INTERVAL 3 YEAR)),
(1, 'out', 60, DATE_SUB(CURDATE(), INTERVAL 5 YEAR)),
(2, 'out', 120, DATE_SUB(CURDATE(), INTERVAL 5 YEAR)),
(7, 'out', 250, DATE_SUB(CURDATE(), INTERVAL 5 YEAR)),
(10, 'out', 180, DATE_SUB(CURDATE(), INTERVAL 5 YEAR));

-- Example 'in' movements (to show mixed log types)
INSERT INTO inventory_log (item_id, type, quantity_change, log_date) VALUES
-- Daily 'in' movements
(1, 'in', 5, DATE_SUB(CURDATE(), INTERVAL 1 DAY)),
(2, 'in', 10, DATE_SUB(CURDATE(), INTERVAL 1 DAY)),
(3, 'in', 3, DATE_SUB(CURDATE(), INTERVAL 2 DAY)),
(4, 'in', 2, DATE_SUB(CURDATE(), INTERVAL 3 DAY)),
(5, 'in', 5, DATE_SUB(CURDATE(), INTERVAL 4 DAY)),
(6, 'in', 8, DATE_SUB(CURDATE(), INTERVAL 5 DAY)),
(7, 'in', 12, DATE_SUB(CURDATE(), INTERVAL 6 DAY)),
(8, 'in', 7, DATE_SUB(CURDATE(), INTERVAL 7 DAY)),
(9, 'in', 4, DATE_SUB(CURDATE(), INTERVAL 8 DAY)),
(10, 'in', 15, DATE_SUB(CURDATE(), INTERVAL 9 DAY)),
(11, 'in', 6, DATE_SUB(CURDATE(), INTERVAL 10 DAY)),
(12, 'in', 10, DATE_SUB(CURDATE(), INTERVAL 11 DAY)),
(13, 'in', 25, DATE_SUB(CURDATE(), INTERVAL 12 DAY)),
(14, 'in', 3, DATE_SUB(CURDATE(), INTERVAL 13 DAY)),
(15, 'in', 2, DATE_SUB(CURDATE(), INTERVAL 14 DAY)),
(16, 'in', 1, DATE_SUB(CURDATE(), INTERVAL 15 DAY)),
(17, 'in', 5, DATE_SUB(CURDATE(), INTERVAL 16 DAY)),
(18, 'in', 10, DATE_SUB(CURDATE(), INTERVAL 17 DAY)),
(19, 'in', 8, DATE_SUB(CURDATE(), INTERVAL 18 DAY)),
(20, 'in', 12, DATE_SUB(CURDATE(), INTERVAL 19 DAY)),

-- Weekly 'in' movements
(1, 'in', 10, DATE_SUB(CURDATE(), INTERVAL 2 WEEK)),
(2, 'in', 15, DATE_SUB(CURDATE(), INTERVAL 4 WEEK)),
(7, 'in', 25, DATE_SUB(CURDATE(), INTERVAL 6 WEEK)),
(10, 'in', 20, DATE_SUB(CURDATE(), INTERVAL 8 WEEK)),
(15, 'in', 8, DATE_SUB(CURDATE(), INTERVAL 10 WEEK)),
(20, 'in', 18, DATE_SUB(CURDATE(), INTERVAL 12 WEEK)),

-- Monthly 'in' movements
(1, 'in', 20, DATE_SUB(CURDATE(), INTERVAL 1 MONTH)),
(2, 'in', 30, DATE_SUB(CURDATE(), INTERVAL 3 MONTH)),
(7, 'in', 50, DATE_SUB(CURDATE(), INTERVAL 5 MONTH)),
(10, 'in', 40, DATE_SUB(CURDATE(), INTERVAL 7 MONTH)),
(15, 'in', 15, DATE_SUB(CURDATE(), INTERVAL 9 MONTH)),
(20, 'in', 25, DATE_SUB(CURDATE(), INTERVAL 11 MONTH)),

-- Yearly 'in' movements
(1, 'in', 80, DATE_SUB(CURDATE(), INTERVAL 1 YEAR)),
(2, 'in', 150, DATE_SUB(CURDATE(), INTERVAL 2 YEAR)),
(7, 'in', 300, DATE_SUB(CURDATE(), INTERVAL 3 YEAR)),
(10, 'in', 200, DATE_SUB(CURDATE(), INTERVAL 4 YEAR)),
(15, 'in', 50, DATE_SUB(CURDATE(), INTERVAL 5 YEAR));
