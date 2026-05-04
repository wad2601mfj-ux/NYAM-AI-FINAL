-- CREATE DATABASE nyam_ai;
-- USE nyam_ai;

CREATE TABLE `products` (
  `id` varchar(100) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `price` double DEFAULT NULL,
  `origPrice` double DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `sold` int(11) DEFAULT 0,
  `image` text DEFAULT NULL,
  `sellerName` varchar(255) DEFAULT NULL,
  `timestamp` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `chats` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `buyerId` varchar(255) DEFAULT NULL,
  `sender` varchar(50) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `timestamp` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `buyer_offers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `buyerId` varchar(255) DEFAULT NULL,
  `product_id` varchar(100) DEFAULT NULL,
  `product_title` varchar(255) DEFAULT NULL,
  `product_price` double DEFAULT NULL,
  `product_image` text DEFAULT NULL,
  `product_category` varchar(100) DEFAULT NULL,
  `sellerName` varchar(255) DEFAULT NULL,
  `timestamp` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `buyer_activities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `buyerId` varchar(255) DEFAULT NULL,
  `activity_type` varchar(255) DEFAULT NULL,
  `product_name` varchar(255) DEFAULT NULL,
  `sellerName` varchar(255) DEFAULT NULL,
  `timestamp` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `buyerId` varchar(255) DEFAULT NULL,
  `items` text DEFAULT NULL,
  `total` double DEFAULT NULL,
  `address` text DEFAULT NULL,
  `sellerName` varchar(255) DEFAULT NULL,
  `rating` int(11) DEFAULT 0,
  `timestamp` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
