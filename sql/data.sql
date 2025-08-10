SET FOREIGN_KEY_CHECKS = 0;

TRUNCATE TABLE `targets`;
TRUNCATE TABLE `order_items`;
TRUNCATE TABLE `orders`;
TRUNCATE TABLE `products`;
TRUNCATE TABLE `customers`;
TRUNCATE TABLE `territories`;
TRUNCATE TABLE `upazilas`;
TRUNCATE TABLE `districts`;
TRUNCATE TABLE `divisions`;
TRUNCATE TABLE `users`;

SET FOREIGN_KEY_CHECKS = 1;

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `avatar`, `division_id`, `district_id`, `upazila_id`, `territory_id`) VALUES
(1, 'Md Mahmudur Rahman', 'mahmud@example.com', '$2y$10$jZ.Y.3d5J.E9wZ3sW8c7A.r.i.U5yS2sL6k3F.G5fH4D5eG6hJ7kI', 'SR', 'uploads/avatars/1.png', 1, 1, 1, 1),
(2, 'Md Omar Faruk', 'omar@example.com', '$2y$10$jZ.Y.3d5J.E9wZ3sW8c7A.r.i.U5yS2sL6k3F.G5fH4D5eG6hJ7kI', 'SR', 'uploads/avatars/default-avatar.jpg', 1, 1, 1, 2),
(3, 'Faiaz Hasan', 'faiaz@example.com', '$2y$10$jZ.Y.3d5J.E9wZ3sW8c7A.r.i.U5yS2sL6k3F.G5fH4D5eG6hJ7kI', 'TSM', 'uploads/avatars/default-avatar.jpg', 1, 1, 1, NULL),
(4, 'Abdullah Al Noman Rafi', 'rafi@example.com', '$2y$10$jZ.Y.3d5J.E9wZ3sW8c7A.r.i.U5yS2sL6k3F.G5fH4D5eG6hJ7kI', 'ASM', 'uploads/avatars/default-avatar.jpg', 1, 1, NULL, NULL),
(5, 'Sadia Afrin', 'sadia@example.com', '$2y$10$jZ.Y.3d5J.E9wZ3sW8c7A.r.i.U5yS2sL6k3F.G5fH4D5eG6hJ7kI', 'DSM', 'uploads/avatars/default-avatar.jpg', 1, NULL, NULL, NULL),
(6, 'Nusrat Jahan', 'nusrat@example.com', '$2y$10$jZ.Y.3d5J.E9wZ3sW8c7A.r.i.U5yS2sL6k3F.G5fH4D5eG6hJ7kI', 'NSM', 'uploads/avatars/default-avatar.jpg', NULL, NULL, NULL, NULL),
(7, 'Kamrul Hasan', 'kamrul@example.com', '$2y$10$jZ.Y.3d5J.E9wZ3sW8c7A.r.i.U5yS2sL6k3F.G5fH4D5eG6hJ7kI', 'HOM', 'uploads/avatars/default-avatar.jpg', NULL, NULL, NULL, NULL);

INSERT INTO `divisions` (`id`, `name`) VALUES (1, 'Dhaka');
INSERT INTO `districts` (`id`, `name`, `division_id`) VALUES (1, 'Dhaka', 1);
INSERT INTO `upazilas` (`id`, `name`, `district_id`) VALUES (1, 'Dhanmondi', 1);
INSERT INTO `territories` (`id`, `name`, `upazila_id`) VALUES (1, 'Zigatola', 1), (2, 'Hazaribagh', 1);

INSERT INTO `customers` (`id`, `name`, `phone`, `email`, `address`, `assigned_sales_rep_id`, `territory_id`) VALUES
(1, 'Rahim Store', '01712345678', 'rahim@store.com', '123 Zigatola, Dhaka', 1, 1),
(2, 'Karim General Store', '01812345678', 'karim@store.com', '456 Zigatola, Dhaka', 1, 1),
(3, 'Salam Brothers', '01912345678', 'salam@brothers.com', '789 Hazaribagh, Dhaka', 2, 2),
(4, 'Dhaka Traders', '01612345678', 'dhaka@traders.com', '101 Hazaribagh, Dhaka', 2, 2);

INSERT INTO `products` (`id`, `name`, `price`) VALUES
(1, 'ACI Pure Salt', 40.00),
(2, 'Fresh Refined Sugar', 80.00),
(3, 'Rupchanda Soyabean Oil', 850.00),
(4, 'Pusti Atta', 110.00);

INSERT INTO `orders` (`id`, `customer_id`, `sales_rep_id`, `order_date`, `status`) VALUES
(1, 1, 1, '2024-07-20 10:30:00', 'Completed'),
(2, 2, 1, '2024-07-21 11:00:00', 'Pending'),
(3, 3, 2, '2024-07-21 14:00:00', 'Completed');

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `price`) VALUES
(1, 1, 1, 5, 40.00),
(2, 1, 3, 1, 850.00),
(3, 2, 2, 10, 80.00),
(4, 3, 4, 8, 110.00);

INSERT INTO `targets` (`id`, `user_id`, `month`, `target_amount`, `achieved_amount`) VALUES
(1, 1, '2024-07-01', 50000.00, 35000.00),
(2, 2, '2024-07-01', 40000.00, 42000.00);

