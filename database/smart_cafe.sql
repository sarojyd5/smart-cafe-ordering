-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 25, 2026 at 04:08 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `smart_cafe`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_users`
--

CREATE TABLE `admin_users` (
  `admin_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admin_users`
--

INSERT INTO `admin_users` (`admin_id`, `username`, `password`, `created_at`) VALUES
(1, 'admin', '$2y$10$nu0C5h224PobeaAVmmFhF./YVrMAw1n6sf/zQfb8dTgrAo64I50cq', '2026-08-07 14:37:57');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`category_id`, `category_name`, `description`, `image`, `status`, `created_at`) VALUES
(1, 'Pizza', 'Freshly prepared pizzas', 'pizza.jpg', 'active', '2026-08-07 04:56:04'),
(2, 'Burger', 'Delicious burgers', 'burger.jpg', 'active', '2026-08-07 04:56:04'),
(3, 'Momo', 'Fresh steamed and fried momo', 'momo.jpg', 'active', '2026-08-07 04:56:04'),
(4, 'Coffee', 'Hot and cold coffee', 'coffee.jpg', 'active', '2026-08-07 04:56:04'),
(5, 'Drinks', 'Refreshing beverages', 'drinks.jpg', 'active', '2026-08-07 04:56:04'),
(6, 'Dessert', 'Sweet treats and desserts', 'dessert.jpg', 'active', '2026-08-07 04:56:04');

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `customer_id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL,
  `otp_code` varchar(255) DEFAULT NULL,
  `otp_expires` datetime DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `email_verified` tinyint(1) NOT NULL DEFAULT 0,
  `verification_token` varchar(255) DEFAULT NULL,
  `verification_expires` datetime DEFAULT NULL,
  `reset_otp_code` varchar(255) DEFAULT NULL,
  `reset_otp_expires` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`customer_id`, `full_name`, `email`, `phone`, `password`, `otp_code`, `otp_expires`, `status`, `created_at`, `email_verified`, `verification_token`, `verification_expires`, `reset_otp_code`, `reset_otp_expires`) VALUES
(22, 'Saroj Kumar Yadav', 'ydsaroj2062@gmail.com', '9810899601', '$2y$10$9H/H4V0pPRYaIXxBwrJkLecbh9VPCinBCfQpZFWbySEvEpw3gqikq', NULL, NULL, 'active', '2026-08-21 04:00:18', 1, NULL, NULL, NULL, NULL),
(23, 'Saroj Kumar Yadav', 'ydsaroj0530@gmail.com', '9810899601', '$2y$10$DXwdOzHOYJZlYdZCKfDzSehqfQmO/Ys7Os3A79W8clG2Qey2cKlNu', NULL, NULL, 'active', '2026-08-21 07:37:56', 1, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `foods`
--

CREATE TABLE `foods` (
  `food_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `food_name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `preparation_time` int(11) DEFAULT 20,
  `availability` enum('available','unavailable') DEFAULT 'available',
  `featured` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `foods`
--

INSERT INTO `foods` (`food_id`, `category_id`, `food_name`, `description`, `price`, `image`, `preparation_time`, `availability`, `featured`, `created_at`) VALUES
(1, 1, 'Chicken Pizza', 'Delicious chicken pizza with fresh vegetables and cheese.', 450.00, '1786467382_6a7b5436eef1f.jpg', 25, 'available', 1, '2026-08-07 04:56:04'),
(2, 1, 'Margherita Pizza', 'Classic pizza with tomato sauce, mozzarella and herbs.', 350.00, '1786467237_6a7b53a5c8f75.jpg', 20, 'available', 1, '2026-08-07 04:56:04'),
(3, 2, 'Chicken Burger', 'Juicy chicken burger with fresh vegetables and special sauce.', 350.00, '1786467088_6a7b5310d695a.jpg', 15, 'available', 1, '2026-08-07 04:56:04'),
(4, 2, 'Cheese Burger', 'Classic burger with cheese, vegetables and special sauce.', 400.00, '1786466965_6a7b52950b0a2.jpg', 15, 'available', 0, '2026-08-07 04:56:04'),
(5, 3, 'Chicken Momo', 'Steamed chicken momo served with spicy chutney.', 180.00, '1786466767_6a7b51cf753c6.jpg', 20, 'available', 1, '2026-08-07 04:56:04'),
(6, 3, 'Fried Momo', 'Crispy fried momo served with special chutney.', 220.00, '1786466673_6a7b517184da9.png', 20, 'available', 0, '2026-08-07 04:56:04'),
(7, 4, 'Cappuccino', 'Rich and creamy freshly prepared cappuccino.', 180.00, '1786466294_6a7b4ff6bed11.jpg', 10, 'available', 1, '2026-08-07 04:56:04'),
(8, 4, 'Cold Coffee', 'Chilled creamy coffee with a smooth taste.', 220.00, '1786466210_6a7b4fa2d204f.jpg', 10, 'available', 0, '2026-08-07 04:56:04'),
(9, 5, 'Fresh Lemonade', 'Refreshing homemade lemonade.', 120.00, '1786466033_6a7b4ef1ee3da.jpg', 5, 'available', 0, '2026-08-07 04:56:04'),
(10, 5, 'Cold Drink', 'Chilled refreshing soft drink.', 100.00, '1786465232_6a7b4bd082204.jpg', 2, 'available', 0, '2026-08-07 04:56:04'),
(11, 6, 'Chocolate Brownie', 'Soft and delicious chocolate brownie.', 180.00, '1786465867_6a7b4e4b1d94b.jpg', 10, 'available', 1, '2026-08-07 04:56:04'),
(12, 6, 'Chocolate Cake', 'Rich chocolate cake with creamy topping.', 250.00, '1786465460_6a7b4cb4ec186.jpg', 10, 'unavailable', 1, '2026-08-07 04:56:04');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `customer_name` varchar(100) NOT NULL,
  `customer_phone` varchar(20) NOT NULL,
  `delivery_address` text NOT NULL,
  `order_note` text DEFAULT NULL,
  `subtotal` decimal(10,2) NOT NULL DEFAULT 0.00,
  `delivery_charge` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `payment_method` varchar(30) NOT NULL DEFAULT 'Cash on Delivery',
  `payment_status` varchar(30) NOT NULL DEFAULT 'Pending',
  `payment_reference` varchar(255) DEFAULT NULL,
  `payment_gateway` varchar(30) DEFAULT NULL,
  `order_status` varchar(30) NOT NULL DEFAULT 'Pending',
  `order_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`order_id`, `customer_id`, `customer_name`, `customer_phone`, `delivery_address`, `order_note`, `subtotal`, `delivery_charge`, `total_amount`, `payment_method`, `payment_status`, `payment_reference`, `payment_gateway`, `order_status`, `order_date`) VALUES
(22, 22, 'Saroj Kumar Yadav', '9810899601', 'Balkumari', '', 450.00, 50.00, 500.00, 'Cash on Delivery', 'Pending', NULL, 'COD', 'Out for Delivery', '2026-08-21 04:01:22'),
(23, 23, 'Saroj Kumar Yadav', '9810899601', 'balkumari', '', 1530.00, 50.00, 1580.00, 'Cash on Delivery', 'Pending', NULL, 'COD', 'Pending', '2026-08-21 07:42:54');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `order_item_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `food_id` int(11) NOT NULL,
  `food_name` varchar(150) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `total_price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_items`
--

--Table structure for payment  
CREATE TABLE payment (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    payment_method VARCHAR(30) NOT NULL,
    payment_status VARCHAR(30) NOT NULL DEFAULT 'Pending',
    payment_reference VARCHAR(255) DEFAULT NULL,
    payment_gateway VARCHAR(30) DEFAULT NULL,
    payment_amount DECIMAL(10,2) NOT NULL,
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (order_id)
        REFERENCES orders(order_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);


INSERT INTO `order_items` (`order_item_id`, `order_id`, `food_id`, `food_name`, `quantity`, `price`, `total_price`) VALUES
(24, 22, 1, 'Chicken Pizza', 1, 450.00, 450.00),
(25, 23, 1, 'Chicken Pizza', 3, 450.00, 1350.00),
(26, 23, 5, 'Chicken Momo', 1, 180.00, 180.00);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_users`
--
ALTER TABLE `admin_users`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`category_id`),
  ADD UNIQUE KEY `category_name` (`category_name`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`customer_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `foods`
--
ALTER TABLE `foods`
  ADD PRIMARY KEY (`food_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`order_item_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `food_id` (`food_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_users`
--
ALTER TABLE `admin_users`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `customer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `foods`
--
ALTER TABLE `foods`
  MODIFY `food_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `order_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `foods`
--
ALTER TABLE `foods`
  ADD CONSTRAINT `foods_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`) ON UPDATE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`food_id`) REFERENCES `foods` (`food_id`) ON DELETE CASCADE;
COMMIT;

ALTER TABLE orders
ADD latitude DECIMAL(10,8) NULL,
ADD longitude DECIMAL(11,8) NULL;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
