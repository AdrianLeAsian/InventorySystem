CREATE DATABASE IF NOT EXISTS db_inventory;
USE db_inventory;

CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL
);

CREATE TABLE locations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL
);

CREATE TABLE items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    category_id INT NOT NULL,
    location_id INT NOT NULL,
    current_stock INT DEFAULT 0,
    unit VARCHAR(30) NOT NULL,
    low_stock INT DEFAULT 0,
    max_stock INT DEFAULT 0,
    is_perishable BOOLEAN DEFAULT 0,
    expiry_date DATE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
    FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE CASCADE
);

-- Add indexes for query optimization
CREATE INDEX idx_items_expiry_date ON items (expiry_date);
CREATE INDEX idx_items_current_stock ON items (current_stock);
CREATE INDEX idx_items_category_id ON items (category_id);
CREATE INDEX idx_items_location_id ON items (location_id);

CREATE TABLE logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,
    action VARCHAR(255) NOT NULL,
    date_time DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE
);

CREATE TABLE item_batches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,
    expiry_date DATE,
    quantity INT DEFAULT 0,
    FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE
);

-- Users table for authentication
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(50) DEFAULT 'user' NOT NULL

);

-- Table for import history
CREATE TABLE IF NOT EXISTS import_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    import_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    user_id INT,
    file_name VARCHAR(255) NOT NULL,
    status VARCHAR(50) NOT NULL, -- e.g., 'success', 'failure', 'partial_success'
    summary TEXT, -- e.g., 'Imported 10 items, 2 locations, 5 logs. Skipped 1 row due to error.'
    errors JSON, -- Store detailed errors as JSON
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);
