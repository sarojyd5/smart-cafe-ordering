CREATE DATABASE IF NOT EXISTS smart_cafe
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE smart_cafe;

-- =========================================
-- CUSTOMERS
-- =========================================
CREATE TABLE customers (
    customer_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(20) NOT NULL,
    password VARCHAR(255) NOT NULL,
    status ENUM('active','inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =========================================
-- CATEGORIES
-- =========================================
CREATE TABLE categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL UNIQUE,
    description VARCHAR(255),
    image VARCHAR(255),
    status ENUM('active','inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =========================================
-- FOOD ITEMS
-- =========================================
CREATE TABLE foods (
    food_id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    food_name VARCHAR(150) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    image VARCHAR(255),
    preparation_time INT DEFAULT 20,
    availability ENUM('available','unavailable') DEFAULT 'available',
    featured TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (category_id)
        REFERENCES categories(category_id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE

) ENGINE=InnoDB;


-- =========================================
-- INSERT CATEGORIES
-- =========================================
INSERT INTO categories
(category_name, description, image)
VALUES
('Pizza', 'Freshly prepared pizzas', 'pizza.jpg'),
('Burger', 'Delicious burgers', 'burger.jpg'),
('Momo', 'Fresh steamed and fried momo', 'momo.jpg'),
('Coffee', 'Hot and cold coffee', 'coffee.jpg'),
('Drinks', 'Refreshing beverages', 'drinks.jpg'),
('Dessert', 'Sweet treats and desserts', 'dessert.jpg');

-- =========================================
-- INSERT SAMPLE FOODS
-- =========================================
INSERT INTO foods
(category_id, food_name, description, price, image, preparation_time, featured)
VALUES
(1, 'Chicken Pizza',
 'Delicious chicken pizza with fresh vegetables and cheese.',
 450.00, 'chicken-pizza.jpg', 25, 1),

(1, 'Margherita Pizza',
 'Classic pizza with tomato sauce, mozzarella and herbs.',
 350.00, 'margherita-pizza.jpg', 20, 1),

(2, 'Chicken Burger',
 'Juicy chicken burger with fresh vegetables and special sauce.',
 350.00, 'chicken-burger.jpg', 15, 1),

(2, 'Cheese Burger',
 'Classic burger with cheese, vegetables and special sauce.',
 400.00, 'cheese-burger.jpg', 15, 0),

(3, 'Chicken Momo',
 'Steamed chicken momo served with spicy chutney.',
 180.00, 'chicken-momo.jpg', 20, 1),

(3, 'Fried Momo',
 'Crispy fried momo served with special chutney.',
 220.00, 'fried-momo.jpg', 20, 0),

(4, 'Cappuccino',
 'Rich and creamy freshly prepared cappuccino.',
 180.00, 'cappuccino.jpg', 10, 1),

(4, 'Cold Coffee',
 'Chilled creamy coffee with a smooth taste.',
 220.00, 'cold-coffee.jpg', 10, 0),

(5, 'Fresh Lemonade',
 'Refreshing homemade lemonade.',
 120.00, 'lemonade.jpg', 5, 0),

(5, 'Cold Drink',
 'Chilled refreshing soft drink.',
 100.00, 'cold-drink.jpg', 2, 0),

(6, 'Chocolate Brownie',
 'Soft and delicious chocolate brownie.',
 180.00, 'brownie.jpg', 10, 1),

(6, 'Chocolate Cake',
 'Rich chocolate cake with creamy topping.',
 250.00, 'chocolate-cake.jpg', 10, 0);