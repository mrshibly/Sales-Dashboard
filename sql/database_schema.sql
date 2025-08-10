-- Adminer 4.8.1 MySQL 5.7.33 dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

DROP TABLE IF EXISTS `customers`;
CREATE TABLE `customers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `phone` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `address` varchar(255) NOT NULL,
  `assigned_sales_rep_id` int(11) NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `territory_id` INT(11) NULL,
  PRIMARY KEY (`id`),
  KEY `assigned_sales_rep_id` (`assigned_sales_rep_id`),
  KEY `territory_id` (`territory_id`),
  CONSTRAINT `customers_ibfk_1` FOREIGN KEY (`assigned_sales_rep_id`) REFERENCES `users` (`id`),
  CONSTRAINT `customers_ibfk_2` FOREIGN KEY (`territory_id`) REFERENCES `territories` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `orders`;
CREATE TABLE `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `sales_rep_id` int(11) NOT NULL,
  `order_date` datetime NOT NULL,
  `status` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `customer_id` (`customer_id`),
  KEY `sales_rep_id` (`sales_rep_id`),
  CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`sales_rep_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `order_items`;
CREATE TABLE `order_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
  CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `products`;
CREATE TABLE `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `targets`;
CREATE TABLE `targets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `month` date NOT NULL,
  `target_amount` decimal(10,2) NOT NULL,
  `achieved_amount` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `targets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(255) NOT NULL,
  `reports_to` int(11) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `firebase_uid` varchar(255) DEFAULT NULL UNIQUE,
  `division_id` INT(11) NULL,
  `district_id` INT(11) NULL,
  `upazila_id` INT(11) NULL,
  `territory_id` INT(11) NULL,
  PRIMARY KEY (`id`),
  KEY `reports_to` (`reports_to`),
  KEY `division_id` (`division_id`),
  KEY `district_id` (`district_id`),
  KEY `upazila_id` (`upazila_id`),
  KEY `territory_id` (`territory_id`),
  CONSTRAINT `users_ibfk_1` FOREIGN KEY (`reports_to`) REFERENCES `users` (`id`),
  CONSTRAINT `users_ibfk_2` FOREIGN KEY (`division_id`) REFERENCES `divisions` (`id`),
  CONSTRAINT `users_ibfk_3` FOREIGN KEY (`district_id`) REFERENCES `districts` (`id`),
  CONSTRAINT `users_ibfk_4` FOREIGN KEY (`upazila_id`) REFERENCES `upazilas` (`id`),
  CONSTRAINT `users_ibfk_5` FOREIGN KEY (`territory_id`) REFERENCES `territories` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- New Geographic Tables
DROP TABLE IF EXISTS `territories`;
CREATE TABLE `territories` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `upazila_id` INT(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `upazila_id` (`upazila_id`),
  CONSTRAINT `territories_ibfk_1` FOREIGN KEY (`upazila_id`) REFERENCES `upazilas` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `upazilas`;
CREATE TABLE `upazilas` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `district_id` INT(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `district_id` (`district_id`),
  CONSTRAINT `upazilas_ibfk_1` FOREIGN KEY (`district_id`) REFERENCES `districts` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `districts`;
CREATE TABLE `districts` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `division_id` INT(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `division_id` (`division_id`),
  CONSTRAINT `districts_ibfk_1` FOREIGN KEY (`division_id`) REFERENCES `divisions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `divisions`;
CREATE TABLE `divisions` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- 2024-07-29 11:55:16