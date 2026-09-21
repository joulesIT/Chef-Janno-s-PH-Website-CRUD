CREATE DATABASE IF NOT EXISTS chef_jannos CHARACTER SET utf8mb4;
USE chef_jannos;
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    email VARCHAR(120) UNIQUE,
    phone VARCHAR(30),
    password VARCHAR(255),
    demo_password VARCHAR(100),
    role VARCHAR(15) DEFAULT 'client',
    created TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    category VARCHAR(50),
    description TEXT,
    price DECIMAL(8,2),
    image VARCHAR(120),
    stock INT DEFAULT 50,
    low_stock INT DEFAULT 10,
    available TINYINT DEFAULT 1
);
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_no INT DEFAULT 0,
    order_date DATE NULL,
    user_id INT NULL,
    customer_name VARCHAR(100) NULL,
    source VARCHAR(15) DEFAULT 'online',
    order_type VARCHAR(20),
    table_ref VARCHAR(30),
    special_requests TEXT,
    allergies TEXT,
    total DECIMAL(8,2),
    status VARCHAR(15) DEFAULT 'pending',
    payment_status VARCHAR(10) DEFAULT 'unpaid',
    created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_day(order_date,order_no)
);
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT,
    item_id INT,
    name VARCHAR(100),
    qty INT,
    price DECIMAL(8,2)
);
CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNIQUE,
    amount DECIMAL(8,2),
    tendered DECIMAL(8,2),
    method VARCHAR(20),
    reference VARCHAR(60),
    cashier_id INT,
    paid_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE stock_movements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT,
    qty INT,
    order_id INT,
    moved_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE gallery (
    id INT AUTO_INCREMENT PRIMARY KEY,
    image VARCHAR(120),
    caption VARCHAR(150)
);
CREATE TABLE posters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    image VARCHAR(120),
    title VARCHAR(150),
    active TINYINT DEFAULT 1,
    created TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNIQUE,
    user_id INT,
    rating INT,
    comment TEXT,
    approved TINYINT DEFAULT 0,
    created TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
INSERT INTO items (name, category, description, price, stock, low_stock) VALUES
('Classic Janno Burger','Burgers','Fresh-to-order beef patty, cheese, lettuce, tomato, house sauce.',99,40,10),

