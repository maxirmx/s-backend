-- phpMyAdmin SQL Dump
-- version 5.1.1
-- https://www.phpmyadmin.net/
--
-- Хост: swc3778270.mysql
-- Время создания: Авг 21 2023 г., 15:32
-- Версия сервера: 8.0.22-13
-- Версия PHP: 7.4.20

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `swc3778270_tracker`
--

-- --------------------------------------------------------

--
-- Структура таблицы `links`
--
-- Создание: Авг 21 2023 г., 15:12
--

DROP TABLE IF EXISTS `links`;
CREATE TABLE `links` (
  `id` int NOT NULL,
  `link` varchar(256) CHARACTER SET utf8 COLLATE utf8_unicode_520_ci NOT NULL,
  `expire` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_520_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `organizations`
--
-- Создание: Авг 21 2023 г., 15:10
-- Последнее обновление: Авг 18 2023 г., 16:47
--

DROP TABLE IF EXISTS `organizations`;
CREATE TABLE `organizations` (
  `id` int NOT NULL,
  `name` varchar(64) COLLATE utf8_unicode_520_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_520_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `shipments`
--
-- Создание: Авг 21 2023 г., 15:10
-- Последнее обновление: Авг 20 2023 г., 16:23
--

DROP TABLE IF EXISTS `shipments`;
CREATE TABLE `shipments` (
  `id` int NOT NULL,
  `number` varchar(16) COLLATE utf8_unicode_520_ci NOT NULL,
  `dest` varchar(64) COLLATE utf8_unicode_520_ci NOT NULL,
  `ddate` date NOT NULL,
  `userId` int NOT NULL DEFAULT '-2',
  `orgId` int NOT NULL DEFAULT '-2'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_520_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `statuses`
--
-- Создание: Авг 21 2023 г., 15:10
-- Последнее обновление: Авг 20 2023 г., 16:23
--

DROP TABLE IF EXISTS `statuses`;
CREATE TABLE `statuses` (
  `id` int NOT NULL,
  `shipmentNumber` varchar(16) COLLATE utf8_unicode_520_ci NOT NULL,
  `status` int NOT NULL,
  `date` date NOT NULL,
  `location` varchar(64) CHARACTER SET utf8 COLLATE utf8_unicode_520_ci NOT NULL,
  `comment` varchar(128) CHARACTER SET utf8 COLLATE utf8_unicode_520_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_520_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `users`
--
-- Создание: Авг 21 2023 г., 15:10
-- Последнее обновление: Авг 20 2023 г., 16:19
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int NOT NULL,
  `firstName` varchar(64) CHARACTER SET utf8 COLLATE utf8_unicode_520_ci NOT NULL,
  `lastName` varchar(64) CHARACTER SET utf8 COLLATE utf8_unicode_520_ci NOT NULL,
  `patronimic` varchar(64) COLLATE utf8_unicode_520_ci NOT NULL,
  `email` varchar(64) COLLATE utf8_unicode_520_ci NOT NULL,
  `orgId` int NOT NULL,
  `password` varchar(64) COLLATE utf8_unicode_520_ci NOT NULL,
  `isEnabled` tinyint(1) NOT NULL,
  `isManager` tinyint(1) NOT NULL,
  `isAdmin` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_520_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `versions`
--
-- Создание: Авг 21 2023 г., 15:11
-- Последнее обновление: Авг 21 2023 г., 15:31
--

DROP TABLE IF EXISTS `versions`;
CREATE TABLE `versions` (
  `id` int NOT NULL,
  `version` varchar(16) COLLATE utf8_unicode_520_ci NOT NULL,
  `date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_520_ci;

--
-- Дамп данных таблицы `versions`
--

INSERT INTO `versions` (`id`, `version`, `date`) VALUES
(1, '0.1.3', '2023-08-21');

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `links`
--
ALTER TABLE `links`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `link_link_idx` (`link`(128)) USING BTREE,
  ADD KEY `link_expire_idx` (`expire`);

--
-- Индексы таблицы `organizations`
--
ALTER TABLE `organizations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `organizations_name_idx` (`name`);

--
-- Индексы таблицы `shipments`
--
ALTER TABLE `shipments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `shipments_number_idx` (`number`) USING BTREE,
  ADD KEY `shipments_userid_idx` (`userId`),
  ADD KEY `shipments_orgid_idx` (`orgId`) USING BTREE;

--
-- Индексы таблицы `statuses`
--
ALTER TABLE `statuses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `statuses_shipmentNumber_idx` (`shipmentNumber`) USING BTREE;

--
-- Индексы таблицы `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_email_idx` (`email`);

--
-- Индексы таблицы `versions`
--
ALTER TABLE `versions`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `links`
--
ALTER TABLE `links`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `organizations`
--
ALTER TABLE `organizations`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `shipments`
--
ALTER TABLE `shipments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `statuses`
--
ALTER TABLE `statuses`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `versions`
--
ALTER TABLE `versions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
