CREATE DATABASE naura_cofe;
USE naura_cofe;

-- Users Table
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'kasir') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default users
INSERT INTO users (username, email, password, role) VALUES 
('admin', 'admin@nauracofe.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('kasir', 'kasir@nauracofe.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'kasir');

-- Categories Table
CREATE TABLE categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) UNIQUE NOT NULL,
    icon VARCHAR(50) DEFAULT 'fa-tag'
);

INSERT INTO categories (name, icon) VALUES 
('makanan', 'fa-utensils'),
('minuman', 'fa-mug-hot'),
('snack', 'fa-cookie');

-- Products Table
CREATE TABLE products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    category_id INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    stock INT DEFAULT 0,
    image VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id)
);

INSERT INTO products (name, category_id, price, stock, image) VALUES 
('Espresso', 2, 15000.00, 100, 'https://picsum.photos/seed/espresso/200/200'),
('Cappuccino', 2, 20000.00, 100, 'https://picsum.photos/seed/cappuccino/200/200'),
('Latte', 2, 22000.00, 100, 'https://picsum.photos/seed/latte/200/200'),
('Americano', 2, 18000.00, 100, 'https://picsum.photos/seed/americano/200/200'),
('Croissant', 1, 25000.00, 50, 'https://picsum.photos/seed/croissant/200/200'),
('Sandwich', 1, 35000.00, 30, 'https://picsum.photos/seed/sandwich/200/200'),
('Donut', 3, 15000.00, 60, 'https://picsum.photos/seed/donut/200/200'),
('Muffin', 3, 18000.00, 40, 'https://picsum.photos/seed/muffin/200/200'),
('Cake Slice', 3, 30000.00, 25, 'https://picsum.photos/seed/cake/200/200'),
('Pasta', 1, 45000.00, 20, 'https://picsum.photos/seed/pasta/200/200'),
('Salad', 1, 30000.00, 15, 'https://picsum.photos/seed/salad/200/200'),
('Ice Tea', 2, 12000.00, 100, 'https://picsum.photos/seed/icetea/200/200');

-- Transactions Table
CREATE TABLE transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    transaction_code VARCHAR(50) UNIQUE NOT NULL,
    user_id INT NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('cash', 'qris') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Transaction Items Table
CREATE TABLE transaction_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    transaction_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (transaction_id) REFERENCES transactions(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
);