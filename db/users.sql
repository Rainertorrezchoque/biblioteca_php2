-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 01-08-2025 a las 02:56:06
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `biblioteca1`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `dui` varchar(20) DEFAULT NULL,
  `nie` varchar(20) DEFAULT NULL,
  `name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(50) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `type` tinyint(4) NOT NULL COMMENT '0=admin,1=bibliotecario,2=docente,3=estudiante,4=personal,5=visitante',
  `status` tinyint(4) DEFAULT 1 COMMENT '0=inactivo,1=activo',
  `section_id` int(11) DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `users`
--

INSERT INTO `users` (`id`, `dui`, `nie`, `name`, `last_name`, `email`, `email_verified_at`, `phone`, `password`, `type`, `status`, `section_id`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, '12345678-9', 'ABCDEF12345', 'Juan', 'Perez', 'juan.perez@example.com', NULL, '5555-5555', '$2y$10$V.ovRnkhEQgI6YhM/4synu0ZXGe.8Jl3wajUm5ZNzbqImP.kGP2tW', 5, 0, NULL, NULL, '2025-07-31 21:23:34', '2025-07-31 22:22:27'),
(4, '7501802', '', 'rainer', 'torrez', 'rainertorrez@academy.edu', NULL, '72172951', '$2y$10$zalKGJia92MbavRvhgA4Y.X0Z2BKthFfr13M0BuLN3XhNSx6ZQi.i', 0, 1, NULL, NULL, '2025-07-31 21:49:26', '2025-07-31 22:48:16'),
(5, '12121212', 'asdasdasd', 'javier', 'torrez', 'javier@academy.edu', NULL, '60310813', '$2y$10$IM/G4aXsCALoeoAuBTDWD.fchURsjdgQjBAZMpd55sTR2ySIAsMLG', 1, 0, NULL, NULL, '2025-07-31 22:53:53', '2025-07-31 22:54:16'),
(7, '85236974', 'qwerfdsa', 'edgar', 'vivar', 'edgar@academy.edu', NULL, '45612387', '$2y$10$cIVjsLBrS.5ZFrJqhORGzeQwnFspLzpsgxvBOQ9CgJpjC99ikwIsi', 2, 0, NULL, NULL, '2025-07-31 23:04:12', '2025-07-31 23:04:12');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `dui` (`dui`),
  ADD UNIQUE KEY `nie` (`nie`),
  ADD KEY `section_id` (`section_id`),
  ADD KEY `idx_users_type` (`type`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
