-- Mock data for categories
INSERT INTO categories (name) VALUES
('Electronics'),
('Food'),
('Beverages'),
('Office Supplies'),
('Cleaning Supplies');

-- Mock data for locations
INSERT INTO locations (name) VALUES
('Warehouse A'),
('Warehouse B'),
('Retail Store'),
('Office Storage'),
('Kitchen Pantry');

-- Mock data for items
INSERT INTO items (name, category_id, location_id, current_stock, unit, low_stock, max_stock, is_perishable, expiry_date) VALUES
('Laptop', 1, 1, 50, 'pcs', 10, 100, 0, NULL),
('Milk', 2, 5, 200, 'liters', 50, 300, 1, '2025-08-20'),
('Coffee', 3, 5, 150, 'bags', 30, 200, 0, NULL),
('Printer Paper', 4, 4, 300, 'reams', 50, 500, 0, NULL),
('Dish Soap', 5, 1, 100, 'bottles', 20, 150, 0, NULL),
('Bread', 2, 5, 80, 'loaves', 20, 100, 1, '2025-08-15'),
('Juice', 3, 5, 120, 'bottles', 25, 180, 1, '2025-09-01'),
('Pens', 4, 4, 500, 'pcs', 100, 700, 0, NULL),
('Monitor', 1, 1, 30, 'pcs', 5, 50, 0, NULL),
('Cleaning Wipes', 5, 1, 75, 'packs', 15, 100, 0, NULL),
('Yogurt', 2, 5, 5, 'cups', 10, 50, 1, '2025-08-10'), -- Expired item (ID 11)
('Batteries AA', 1, 1, 8, 'packs', 10, 100, 0, NULL), -- Low stock item (ID 12)
('Fresh Produce', 2, 5, 15, 'kg', 10, 50, 1, '2025-08-13'); -- Nearly expired item (ID 13)

-- Mock data for item_batches
INSERT INTO item_batches (item_id, expiry_date, quantity) VALUES
(2, '2025-08-20', 100),
(2, '2025-08-25', 100),
(6, '2025-08-15', 40),
(6, '2025-08-16', 40),
(7, '2025-09-01', 60),
(7, '2025-09-05', 60),
(1, '2026-01-01', 25), -- Laptop batch
(3, '2025-12-31', 75), -- Coffee batch
(4, '2027-06-30', 150), -- Printer Paper batch
(5, '2026-03-15', 50), -- Dish Soap batch
(9, '2026-02-28', 15), -- Monitor batch
(10, '2025-11-30', 30), -- Cleaning Wipes batch
(11, '2025-08-10', 5), -- Expired Yogurt batch
(13, '2025-08-13', 15); -- Nearly expired Fresh Produce batch

-- Mock data for logs
INSERT INTO logs (item_id, action, category, date_time) VALUES
(1, 'Added 50 pcs', 'Stock In', '2025-08-01 10:00:00'),
(2, 'Added 200 liters', 'Stock In', '2025-08-01 10:05:00'),
(3, 'Added 150 bags', 'Stock In', '2025-08-01 10:10:00'),
(1, 'Removed 5 pcs', 'Stock Out', '2025-08-02 11:00:00'),
(2, 'Removed 10 liters', 'Stock Out', '2025-08-02 11:15:00'),
(4, 'Added 300 reams', 'Stock In', '2025-08-03 09:00:00'),
(5, 'Added 100 bottles', 'Stock In', '2025-08-03 09:30:00'),
(6, 'Added 80 loaves', 'Stock In', '2025-08-04 14:00:00'),
(7, 'Added 120 bottles', 'Stock In', '2025-08-04 14:30:00'),
(8, 'Added 500 pcs', 'Stock In', '2025-08-05 10:00:00'),
(9, 'Added 30 pcs', 'Stock In', '2025-08-05 10:15:00'),
(10, 'Added 75 packs', 'Stock In', '2025-08-05 10:30:00'),
(2, 'Expired 5 liters', 'Expired', '2025-08-21 08:00:00'),
(6, 'Expired 10 loaves', 'Expired', '2025-08-16 09:00:00'),
(11, 'Added 5 cups', 'Stock In', '2025-08-09 12:00:00'), -- Yogurt initial stock
(12, 'Added 8 packs', 'Stock In', '2025-08-09 12:05:00'), -- Batteries initial stock
(13, 'Added 15 kg', 'Stock In', '2025-08-10 13:00:00'); -- Fresh Produce initial stock
