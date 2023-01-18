-- phpMyAdmin SQL Dump
-- version 4.9.5deb2
-- https://www.phpmyadmin.net/
--
-- Хост: localhost
-- Час створення: Січ 18 2023 р., 17:03
-- Версія сервера: 8.0.31-0ubuntu0.20.04.2
-- Версія PHP: 7.4.3

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База даних: `avaElectr`
--

-- --------------------------------------------------------

--
-- Структура таблиці `messages`
--

CREATE TABLE `messages` (
  `id` int NOT NULL,
  `text` text COLLATE utf8mb4_general_ci NOT NULL,
  `type` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `lastRequestsEspId` bigint NOT NULL,
  `subscriberId` bigint NOT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп даних таблиці `messages`
--

INSERT INTO `messages` (`id`, `text`, `type`, `lastRequestsEspId`, `subscriberId`, `created`) VALUES
(1, 'Світло увімкнено у 16:47', 'message', 2695, 228, '2023-01-18 14:49:00');

-- --------------------------------------------------------

--
-- Структура таблиці `requestsFromEsp`
--

CREATE TABLE `requestsFromEsp` (
  `id` int NOT NULL,
  `baro` text COLLATE utf8mb4_general_ci,
  `temp` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп даних таблиці `requestsFromEsp`
--

INSERT INTO `requestsFromEsp` (`id`, `baro`, `temp`, `created`) VALUES
(1041, '1010.60', '20.05', '2023-01-14 20:50:36');

-- --------------------------------------------------------

--
-- Структура таблиці `subscribers`
--

CREATE TABLE `subscribers` (
  `id` int NOT NULL,
  `first_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `last_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `telegramId` bigint NOT NULL,
  `status` int NOT NULL,
  `options` json NOT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп даних таблиці `subscribers`
--

INSERT INTO `subscribers` (`id`, `first_name`, `last_name`, `telegramId`, `status`, `options`, `created`) VALUES
(223, 'Dmitry', 'Dudarev', 464928895, 1, '[1]', '2023-01-08 11:58:51');

-- --------------------------------------------------------

--
-- Структура таблиці `updates`
--

CREATE TABLE `updates` (
  `id` int NOT NULL,
  `update_id` int NOT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп даних таблиці `updates`
--

INSERT INTO `updates` (`id`, `update_id`, `created`) VALUES
(86, 521601903, '2023-01-18 14:49:41');

--
-- Індекси збережених таблиць
--

--
-- Індекси таблиці `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`);

--
-- Індекси таблиці `requestsFromEsp`
--
ALTER TABLE `requestsFromEsp`
  ADD PRIMARY KEY (`id`);

--
-- Індекси таблиці `subscribers`
--
ALTER TABLE `subscribers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `telegramId` (`telegramId`);

--
-- Індекси таблиці `updates`
--
ALTER TABLE `updates`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT для збережених таблиць
--

--
-- AUTO_INCREMENT для таблиці `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=420;

--
-- AUTO_INCREMENT для таблиці `requestsFromEsp`
--
ALTER TABLE `requestsFromEsp`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2704;

--
-- AUTO_INCREMENT для таблиці `subscribers`
--
ALTER TABLE `subscribers`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=229;

--
-- AUTO_INCREMENT для таблиці `updates`
--
ALTER TABLE `updates`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=87;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;