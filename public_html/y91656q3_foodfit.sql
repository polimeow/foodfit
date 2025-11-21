-- phpMyAdmin SQL Dump
-- version 4.9.7
-- https://www.phpmyadmin.net/
--
-- Хост: localhost
-- Время создания: Ноя 21 2025 г., 14:31
-- Версия сервера: 8.0.34-26-beget-1-1
-- Версия PHP: 5.6.40

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `y91656q3_foodfit`
--

-- --------------------------------------------------------

--
-- Структура таблицы `admin_users`
--
-- Создание: Ноя 14 2025 г., 07:44
--

DROP TABLE IF EXISTS `admin_users`;
CREATE TABLE `admin_users` (
  `id` int NOT NULL,
  `username` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('admin','manager') DEFAULT 'manager',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- Дамп данных таблицы `admin_users`
--

INSERT INTO `admin_users` (`id`, `username`, `password_hash`, `role`, `is_active`, `created_at`) VALUES
(11, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 1, '2025-11-14 07:50:58'),
(12, 'manager', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'manager', 1, '2025-11-14 07:50:58'),
(13, 'test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'manager', 1, '2025-11-14 07:50:58');

-- --------------------------------------------------------

--
-- Структура таблицы `favorites`
--
-- Создание: Ноя 14 2025 г., 10:02
--

DROP TABLE IF EXISTS `favorites`;
CREATE TABLE `favorites` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `meal_plan_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- Дамп данных таблицы `favorites`
--

INSERT INTO `favorites` (`id`, `user_id`, `meal_plan_id`, `created_at`) VALUES
(1, 1, 2, '2025-11-14 10:26:41');

-- --------------------------------------------------------

--
-- Структура таблицы `meal_plans`
--
-- Создание: Ноя 14 2025 г., 10:11
--

DROP TABLE IF EXISTS `meal_plans`;
CREATE TABLE `meal_plans` (
  `id` int NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text,
  `calories` int NOT NULL,
  `goal_id` int DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `image_url` varchar(500) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `average_rating` decimal(3,2) DEFAULT '0.00',
  `reviews_count` int DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- Дамп данных таблицы `meal_plans`
--

INSERT INTO `meal_plans` (`id`, `title`, `description`, `calories`, `goal_id`, `price`, `image_url`, `is_active`, `created_at`, `average_rating`, `reviews_count`) VALUES
(1, 'Фитнес-минимум', 'Базовая программа для похудения с оптимальным балансом БЖУ', 1200, 1, '2500.00', NULL, 1, '2025-11-14 07:44:19', '0.00', 0),
(2, 'Стандарт похудение', 'Эффективная программа для комфортного снижения веса', 1500, 1, '2800.00', NULL, 1, '2025-11-14 07:44:19', '0.00', 0),
(3, 'Поддержание формы', 'Сбалансированное питание для сохранения идеального веса', 1800, 2, '3000.00', NULL, 1, '2025-11-14 07:44:19', '0.00', 0),
(4, 'Массонабор премиум', 'Высококалорийная программа для качественного набора массы', 2500, 3, '3500.00', NULL, 1, '2025-11-14 07:44:19', '0.00', 0),
(5, 'Экспресс-похудение', 'Интенсивная программа для быстрого снижения веса', 1000, 1, '2700.00', NULL, 1, '2025-11-14 07:44:19', '0.00', 0);

-- --------------------------------------------------------

--
-- Структура таблицы `nutrition_goals`
--
-- Создание: Ноя 14 2025 г., 07:44
--

DROP TABLE IF EXISTS `nutrition_goals`;
CREATE TABLE `nutrition_goals` (
  `id` int NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- Дамп данных таблицы `nutrition_goals`
--

INSERT INTO `nutrition_goals` (`id`, `name`, `description`, `created_at`) VALUES
(1, 'похудение', 'Программы для снижения веса с дефицитом калорий', '2025-11-14 07:44:19'),
(2, 'поддержание', 'Сбалансированные программы для поддержания текущего веса', '2025-11-14 07:44:19'),
(3, 'набор массы', 'Программы с профицитом калорий для набора мышечной массы', '2025-11-14 07:44:19');

-- --------------------------------------------------------

--
-- Структура таблицы `orders`
--
-- Создание: Ноя 14 2025 г., 09:30
-- Последнее обновление: Ноя 18 2025 г., 09:57
--

DROP TABLE IF EXISTS `orders`;
CREATE TABLE `orders` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `status` enum('pending','confirmed','preparing','delivering','delivered','cancelled') DEFAULT 'pending',
  `delivery_address` text NOT NULL,
  `delivery_date` date NOT NULL,
  `delivery_interval` varchar(100) NOT NULL,
  `customer_notes` text,
  `admin_notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- Дамп данных таблицы `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `total_amount`, `status`, `delivery_address`, `delivery_date`, `delivery_interval`, `customer_notes`, `admin_notes`, `created_at`) VALUES
(1, 1, '2800.00', 'delivered', 'мвав', '2025-11-15', '15:00-18:00', '', NULL, '2025-11-14 08:36:58'),
(2, 1, '2800.00', 'delivered', 'мвав', '2025-11-15', '12:00-15:00', '', NULL, '2025-11-14 08:37:52');

-- --------------------------------------------------------

--
-- Структура таблицы `order_items`
--
-- Создание: Ноя 14 2025 г., 07:44
--

DROP TABLE IF EXISTS `order_items`;
CREATE TABLE `order_items` (
  `id` int NOT NULL,
  `order_id` int NOT NULL,
  `meal_plan_id` int NOT NULL,
  `day_of_week` tinyint NOT NULL COMMENT '1-7 (Понедельник-Воскресенье)',
  `quantity` int DEFAULT '1',
  `price` decimal(10,2) NOT NULL COMMENT 'Цена на момент заказа',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- Дамп данных таблицы `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `meal_plan_id`, `day_of_week`, `quantity`, `price`, `created_at`) VALUES
(1, 1, 2, 1, 1, '2800.00', '2025-11-14 08:36:58'),
(2, 2, 2, 1, 1, '2800.00', '2025-11-14 08:37:52');

-- --------------------------------------------------------

--
-- Структура таблицы `promo_codes`
--
-- Создание: Ноя 14 2025 г., 07:44
--

DROP TABLE IF EXISTS `promo_codes`;
CREATE TABLE `promo_codes` (
  `id` int NOT NULL,
  `code` varchar(50) NOT NULL,
  `discount_type` enum('percentage','fixed') DEFAULT 'percentage',
  `discount_value` decimal(10,2) NOT NULL,
  `min_order_amount` decimal(10,2) DEFAULT '0.00',
  `usage_limit` int DEFAULT NULL,
  `used_count` int DEFAULT '0',
  `valid_from` date DEFAULT NULL,
  `valid_until` date DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- Дамп данных таблицы `promo_codes`
--

INSERT INTO `promo_codes` (`id`, `code`, `discount_type`, `discount_value`, `min_order_amount`, `usage_limit`, `used_count`, `valid_from`, `valid_until`, `is_active`, `created_at`) VALUES
(1, 'WELCOME10', 'percentage', '10.00', '2000.00', 100, 0, NULL, '2025-12-14', 1, '2025-11-14 07:44:19'),
(2, 'FIRSTORDER', 'fixed', '500.00', '3000.00', 50, 0, NULL, '2026-01-13', 1, '2025-11-14 07:44:19'),
(3, 'SUMMER15', 'percentage', '15.00', '4000.00', NULL, 0, NULL, '2026-02-12', 1, '2025-11-14 07:44:19');

-- --------------------------------------------------------

--
-- Структура таблицы `reviews`
--
-- Создание: Ноя 14 2025 г., 10:11
--

DROP TABLE IF EXISTS `reviews`;
CREATE TABLE `reviews` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `meal_plan_id` int NOT NULL,
  `rating` tinyint NOT NULL,
  `comment` text,
  `is_approved` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ;

-- --------------------------------------------------------

--
-- Структура таблицы `used_promo_codes`
--
-- Создание: Ноя 14 2025 г., 07:44
--

DROP TABLE IF EXISTS `used_promo_codes`;
CREATE TABLE `used_promo_codes` (
  `id` int NOT NULL,
  `promo_code_id` int NOT NULL,
  `order_id` int NOT NULL,
  `user_id` int NOT NULL,
  `discount_amount` decimal(10,2) NOT NULL,
  `used_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Структура таблицы `users`
--
-- Создание: Ноя 14 2025 г., 07:44
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `name` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `delivery_address` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- Дамп данных таблицы `users`
--

INSERT INTO `users` (`id`, `email`, `password_hash`, `name`, `phone`, `delivery_address`, `created_at`) VALUES
(1, 'polina.00.vorob@mail.ru', '$2y$10$O8gNDvU/Na0mA3oGP.eYLeM1JfayL1xpBeXPY4IkZLEPrEPHcVsKC', 'Воробьёва Полина Сергеевна', '', '', '2025-11-14 08:31:46');

-- --------------------------------------------------------

--
-- Структура таблицы `user_behavior`
--
-- Создание: Ноя 14 2025 г., 10:29
--

DROP TABLE IF EXISTS `user_behavior`;
CREATE TABLE `user_behavior` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `meal_plan_id` int NOT NULL,
  `action` enum('view','add_to_cart','purchase','favorite') NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `admin_users`
--
ALTER TABLE `admin_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Индексы таблицы `favorites`
--
ALTER TABLE `favorites`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_favorite` (`user_id`,`meal_plan_id`),
  ADD KEY `meal_plan_id` (`meal_plan_id`);

--
-- Индексы таблицы `meal_plans`
--
ALTER TABLE `meal_plans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `goal_id` (`goal_id`);

--
-- Индексы таблицы `nutrition_goals`
--
ALTER TABLE `nutrition_goals`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Индексы таблицы `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `meal_plan_id` (`meal_plan_id`);

--
-- Индексы таблицы `promo_codes`
--
ALTER TABLE `promo_codes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Индексы таблицы `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `meal_plan_id` (`meal_plan_id`);

--
-- Индексы таблицы `used_promo_codes`
--
ALTER TABLE `used_promo_codes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `promo_code_id` (`promo_code_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Индексы таблицы `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Индексы таблицы `user_behavior`
--
ALTER TABLE `user_behavior`
  ADD PRIMARY KEY (`id`),
  ADD KEY `meal_plan_id` (`meal_plan_id`),
  ADD KEY `idx_user_behavior` (`user_id`,`action`,`created_at`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `admin_users`
--
ALTER TABLE `admin_users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT для таблицы `favorites`
--
ALTER TABLE `favorites`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT для таблицы `meal_plans`
--
ALTER TABLE `meal_plans`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT для таблицы `nutrition_goals`
--
ALTER TABLE `nutrition_goals`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT для таблицы `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT для таблицы `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT для таблицы `promo_codes`
--
ALTER TABLE `promo_codes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT для таблицы `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `used_promo_codes`
--
ALTER TABLE `used_promo_codes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT для таблицы `user_behavior`
--
ALTER TABLE `user_behavior`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `favorites`
--
ALTER TABLE `favorites`
  ADD CONSTRAINT `favorites_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `favorites_ibfk_2` FOREIGN KEY (`meal_plan_id`) REFERENCES `meal_plans` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `meal_plans`
--
ALTER TABLE `meal_plans`
  ADD CONSTRAINT `meal_plans_ibfk_1` FOREIGN KEY (`goal_id`) REFERENCES `nutrition_goals` (`id`);

--
-- Ограничения внешнего ключа таблицы `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`meal_plan_id`) REFERENCES `meal_plans` (`id`);

--
-- Ограничения внешнего ключа таблицы `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`meal_plan_id`) REFERENCES `meal_plans` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `used_promo_codes`
--
ALTER TABLE `used_promo_codes`
  ADD CONSTRAINT `used_promo_codes_ibfk_1` FOREIGN KEY (`promo_code_id`) REFERENCES `promo_codes` (`id`),
  ADD CONSTRAINT `used_promo_codes_ibfk_2` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
  ADD CONSTRAINT `used_promo_codes_ibfk_3` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Ограничения внешнего ключа таблицы `user_behavior`
--
ALTER TABLE `user_behavior`
  ADD CONSTRAINT `user_behavior_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_behavior_ibfk_2` FOREIGN KEY (`meal_plan_id`) REFERENCES `meal_plans` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
