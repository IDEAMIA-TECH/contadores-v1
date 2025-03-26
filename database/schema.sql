-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Mar 26, 2025 at 01:30 PM
-- Server version: 10.5.28-MariaDB
-- PHP Version: 8.3.19

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ideamiadev_contadores`
--

-- --------------------------------------------------------

--
-- Table structure for table `accountants_test`
--

CREATE TABLE `accountants_test` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `accountants_test`
--

INSERT INTO `accountants_test` (`id`, `user_id`, `created_at`, `updated_at`) VALUES
(1, 1, '2025-03-19 14:31:57', '2025-03-19 14:31:57');

-- --------------------------------------------------------

--
-- Table structure for table `accountant_clients`
--

CREATE TABLE `accountant_clients` (
  `accountant_id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `accountant_clients`
--

INSERT INTO `accountant_clients` (`accountant_id`, `client_id`, `created_at`) VALUES
(1, 2, '2025-03-19 11:45:16'),
(1, 3, '2025-03-19 12:16:25');

-- --------------------------------------------------------

--
-- Table structure for table `accountant_clients_test`
--

CREATE TABLE `accountant_clients_test` (
  `id` int(11) NOT NULL,
  `accountant_id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `accountant_clients_test`
--

INSERT INTO `accountant_clients_test` (`id`, `accountant_id`, `client_id`, `created_at`) VALUES
(1, 1, 1, '2025-03-19 14:31:57');

-- --------------------------------------------------------

--
-- Table structure for table `clients`
--

CREATE TABLE `clients` (
  `id` int(11) NOT NULL,
  `rfc` varchar(13) NOT NULL,
  `business_name` varchar(255) NOT NULL,
  `legal_name` varchar(255) DEFAULT NULL,
  `fiscal_regime` varchar(3) NOT NULL,
  `street` varchar(255) NOT NULL,
  `exterior_number` varchar(20) NOT NULL,
  `interior_number` varchar(20) DEFAULT NULL,
  `neighborhood` varchar(255) NOT NULL,
  `city` varchar(255) NOT NULL,
  `state` varchar(255) NOT NULL,
  `zip_code` varchar(5) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `csf_path` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `cer_path` varchar(255) DEFAULT NULL COMMENT 'Ruta al archivo .cer del SAT',
  `key_path` varchar(255) DEFAULT NULL COMMENT 'Ruta al archivo .key del SAT',
  `key_password` varchar(255) DEFAULT NULL COMMENT 'Contraseña de la llave privada'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `clients`
--

INSERT INTO `clients` (`id`, `rfc`, `business_name`, `legal_name`, `fiscal_regime`, `street`, `exterior_number`, `interior_number`, `neighborhood`, `city`, `state`, `zip_code`, `email`, `phone`, `csf_path`, `status`, `created_at`, `updated_at`, `cer_path`, `key_path`, `key_password`) VALUES
(2, 'PACJ851217Q84', 'JORGEALBERTO PLASCENCIA CORREA', 'JORGEALBERTO PLASCENCIA CORREA', '605', 'LAGODEPATZCUARO', '100', '83', 'OTRA', 'ELMARQUES', 'QU', '76246', 'JORGE@IDEAMIA.COM.MX', '3316129810', '67db029505125_Constancia de situación fiscal 13-03-2024.pdf', 'active', '2025-03-19 17:45:16', '2025-03-21 22:56:32', 'sat/sat_cer_67ddeea05318f.cer', 'sat/sat_key_67ddeea0531c5.key', '90emfsilr9OBj9xukzAiPQ=='),
(3, 'CLI230502FT0', 'CLICKMIX', 'CLICKMIX', '601', 'CALLE SIERRA GORDA', '20', '', 'BOSQUES DE ACUEDUCTO', 'QUERETARO', 'QUERETARO', '76020', 'admin@clickmix.com.mx', '52 442 172 5990', '', 'active', '2025-03-19 18:16:25', '2025-03-19 18:16:25', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `clients_test`
--

CREATE TABLE `clients_test` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `rfc` varchar(13) NOT NULL,
  `business_name` varchar(150) NOT NULL,
  `legal_name` varchar(150) NOT NULL,
  `fiscal_regime` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `clients_test`
--

INSERT INTO `clients_test` (`id`, `user_id`, `rfc`, `business_name`, `legal_name`, `fiscal_regime`, `address`, `phone`, `email`, `status`, `created_at`, `updated_at`) VALUES
(1, NULL, 'BASE123456ABC', 'Empresa Base SA de CV', 'Empresa Base', '601 - General de Ley', 'Calle Base 123', '5555555555', 'base@test.com', 'active', '2025-03-19 14:31:57', '2025-03-19 14:31:57');

-- --------------------------------------------------------

--
-- Table structure for table `client_contacts`
--

CREATE TABLE `client_contacts` (
  `id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `contact_name` varchar(255) DEFAULT NULL,
  `contact_email` varchar(255) DEFAULT NULL,
  `contact_phone` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `client_contacts`
--

INSERT INTO `client_contacts` (`id`, `client_id`, `contact_name`, `contact_email`, `contact_phone`, `created_at`, `updated_at`) VALUES
(2, 2, 'JORGEALBERTO PLASCENCIA CORREA', 'JORGE@IDEAMIA.COM.MX', '3316129810', '2025-03-19 17:45:16', '2025-03-19 17:45:16'),
(3, 3, 'Juan Manuel', 'admin@clickmix.com.mx', '52 442 172 5990', '2025-03-19 18:16:25', '2025-03-19 18:16:25');

-- --------------------------------------------------------

--
-- Table structure for table `client_contacts_test`
--

CREATE TABLE `client_contacts_test` (
  `id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `client_contacts_test`
--

INSERT INTO `client_contacts_test` (`id`, `client_id`, `name`, `email`, `phone`, `created_at`) VALUES
(1, 1, 'Contacto Base', 'contacto@test.com', '5555555555', '2025-03-19 14:31:57');

-- --------------------------------------------------------

--
-- Table structure for table `client_documents`
--

CREATE TABLE `client_documents` (
  `id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `client_documents`
--

INSERT INTO `client_documents` (`id`, `client_id`, `type`, `file_path`, `created_at`, `updated_at`) VALUES
(1, 2, 'csf', '67db029505125_Constancia de situación fiscal 13-03-2024.pdf', '2025-03-19 17:45:16', '2025-03-19 17:45:16');

-- --------------------------------------------------------

--
-- Table structure for table `client_xmls`
--

CREATE TABLE `client_xmls` (
  `id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `xml_path` varchar(255) NOT NULL,
  `uuid` varchar(36) NOT NULL,
  `serie` varchar(50) DEFAULT NULL,
  `folio` varchar(50) DEFAULT NULL,
  `fecha` datetime NOT NULL,
  `fecha_timbrado` datetime NOT NULL,
  `subtotal` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `tipo_comprobante` varchar(10) NOT NULL,
  `forma_pago` varchar(10) DEFAULT NULL,
  `metodo_pago` varchar(10) DEFAULT NULL,
  `moneda` varchar(5) NOT NULL DEFAULT 'MXN',
  `lugar_expedicion` varchar(10) NOT NULL,
  `emisor_rfc` varchar(13) NOT NULL,
  `emisor_nombre` varchar(255) NOT NULL,
  `emisor_regimen_fiscal` varchar(10) NOT NULL,
  `receptor_rfc` varchar(13) NOT NULL,
  `receptor_nombre` varchar(255) NOT NULL,
  `receptor_regimen_fiscal` varchar(10) NOT NULL,
  `receptor_domicilio_fiscal` varchar(10) NOT NULL,
  `receptor_uso_cfdi` varchar(10) NOT NULL,
  `total_impuestos_trasladados` decimal(12,2) NOT NULL DEFAULT 0.00,
  `impuesto` varchar(10) DEFAULT NULL,
  `tasa_o_cuota` decimal(8,6) DEFAULT NULL,
  `tipo_factor` varchar(10) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `client_xmls`
--
--------------

--
-- Table structure for table `facturas`
--

CREATE TABLE `facturas` (
  `id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `uuid` varchar(36) NOT NULL,
  `fecha` datetime NOT NULL,
  `emisor_rfc` varchar(13) NOT NULL,
  `emisor_nombre` varchar(255) NOT NULL,
  `receptor_rfc` varchar(13) NOT NULL,
  `receptor_nombre` varchar(255) NOT NULL,
  `total` decimal(12,2) NOT NULL,
  `tipo` enum('emitidas','recibidas') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sat_download_requests`
--

CREATE TABLE `sat_download_requests` (
  `id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `request_id` varchar(100) NOT NULL,
  `request_type` enum('metadata','cfdi') NOT NULL,
  `document_type` enum('issued','received') NOT NULL,
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `status` enum('REQUESTED','PROCESSING','READY_TO_DOWNLOAD','COMPLETED','ERROR') NOT NULL,
  `packages_count` int(11) DEFAULT 0,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `sat_download_requests`
--

INSERT INTO `sat_download_requests` (`id`, `client_id`, `request_id`, `request_type`, `document_type`, `start_date`, `end_date`, `status`, `packages_count`, `created_at`, `updated_at`) VALUES
(1, 2, 'bec4f908-b95c-4bb5-b25e-598c7932130e', 'cfdi', 'issued', '2025-01-01 00:00:00', '2025-01-31 12:00:00', 'REQUESTED', 0, '2025-03-21 21:36:07', '2025-03-21 21:36:07'),
(2, 2, '929ec657-5a3a-49ce-87b1-5293592ca974', 'metadata', 'issued', '2025-01-01 00:00:00', '2025-01-31 12:00:00', 'REQUESTED', 0, '2025-03-21 21:41:17', '2025-03-21 21:41:17'),
(3, 2, '54fb401e-60c1-4386-9f86-f0d60f5b8062', 'cfdi', 'issued', '2025-02-01 00:00:00', '2025-02-27 12:00:00', 'REQUESTED', 0, '2025-03-22 09:03:30', '2025-03-22 09:03:30'),
(4, 2, 'd835972c-33fc-415d-af17-2609f14e8be4', 'cfdi', 'issued', '2025-02-01 00:00:00', '2025-02-27 12:00:00', 'REQUESTED', 0, '2025-03-22 09:07:45', '2025-03-22 09:07:45');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `role` enum('admin','contador') NOT NULL DEFAULT 'contador',
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `last_login` datetime DEFAULT NULL,
  `reset_token` varchar(64) DEFAULT NULL,
  `reset_token_expiry` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `email`, `role`, `status`, `last_login`, `reset_token`, `reset_token_expiry`, `created_at`, `updated_at`) VALUES
(1, 'admin', '$argon2id$v=19$m=65536,t=4,p=1$OHRNRW54Q0hBcmEwN2g1NQ$moPfQt8jLa9oju3eZx58pDJnVxmc+9lVJOQ6VzkrvQ4', 'jorge@ideamia.com.mx', 'admin', 'active', '2025-03-26 13:07:51', NULL, NULL, '2025-03-19 09:14:17', '2025-03-26 13:07:51'),
(2, 'contador_test', '$argon2id$v=19$m=65536,t=4,p=1$UHhlVFM3WDhwQldNMndldg$y+cYwtbwCFpqjW801GosAcCsZNL98BG33HUwwKgg0Gs', 'contador@test.com', 'contador', 'active', NULL, NULL, NULL, '2025-03-19 09:14:17', '2025-03-19 10:06:39');

-- --------------------------------------------------------

--
-- Table structure for table `users_test`
--

CREATE TABLE `users_test` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `role` enum('admin','contador','cliente') NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users_test`
--

INSERT INTO `users_test` (`id`, `username`, `password`, `email`, `role`, `status`, `last_login`, `created_at`, `updated_at`) VALUES
(1, 'contador_test', '$argon2id$v=19$m=65536,t=4,p=1$dS9nUExTck0wbC5XUmprNA$qWV+xQ7Nzavngp32Ofwlf0d08GoO21P3VxOW5F/3DBU', 'contador@test.com', 'contador', 'active', NULL, '2025-03-19 14:31:57', '2025-03-19 14:31:57');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `accountants_test`
--
ALTER TABLE `accountants_test`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `accountant_clients`
--
ALTER TABLE `accountant_clients`
  ADD PRIMARY KEY (`accountant_id`,`client_id`),
  ADD KEY `client_id` (`client_id`);

--
-- Indexes for table `accountant_clients_test`
--
ALTER TABLE `accountant_clients_test`
  ADD PRIMARY KEY (`id`),
  ADD KEY `accountant_id` (`accountant_id`),
  ADD KEY `client_id` (`client_id`);

--
-- Indexes for table `clients`
--
ALTER TABLE `clients`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_rfc` (`rfc`);

--
-- Indexes for table `clients_test`
--
ALTER TABLE `clients_test`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `rfc` (`rfc`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `client_contacts`
--
ALTER TABLE `client_contacts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_client_id` (`client_id`);

--
-- Indexes for table `client_contacts_test`
--
ALTER TABLE `client_contacts_test`
  ADD PRIMARY KEY (`id`),
  ADD KEY `client_id` (`client_id`);

--
-- Indexes for table `client_documents`
--
ALTER TABLE `client_documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `client_id` (`client_id`);

--
-- Indexes for table `client_xmls`
--
ALTER TABLE `client_xmls`
  ADD PRIMARY KEY (`id`),
  ADD KEY `client_id` (`client_id`),
  ADD KEY `uuid` (`uuid`),
  ADD KEY `fecha` (`fecha`);

--
-- Indexes for table `facturas`
--
ALTER TABLE `facturas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uuid` (`uuid`),
  ADD KEY `client_id` (`client_id`);

--
-- Indexes for table `sat_download_requests`
--
ALTER TABLE `sat_download_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `client_id` (`client_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `users_test`
--
ALTER TABLE `users_test`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `accountants_test`
--
ALTER TABLE `accountants_test`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `accountant_clients_test`
--
ALTER TABLE `accountant_clients_test`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `clients`
--
ALTER TABLE `clients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `clients_test`
--
ALTER TABLE `clients_test`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `client_contacts`
--
ALTER TABLE `client_contacts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `client_contacts_test`
--
ALTER TABLE `client_contacts_test`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `client_documents`
--
ALTER TABLE `client_documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `client_xmls`
--
ALTER TABLE `client_xmls`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=110;

--
-- AUTO_INCREMENT for table `facturas`
--
ALTER TABLE `facturas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sat_download_requests`
--
ALTER TABLE `sat_download_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users_test`
--
ALTER TABLE `users_test`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `accountants_test`
--
ALTER TABLE `accountants_test`
  ADD CONSTRAINT `accountants_test_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users_test` (`id`);

--
-- Constraints for table `accountant_clients`
--
ALTER TABLE `accountant_clients`
  ADD CONSTRAINT `accountant_clients_ibfk_1` FOREIGN KEY (`accountant_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `accountant_clients_ibfk_2` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `accountant_clients_test`
--
ALTER TABLE `accountant_clients_test`
  ADD CONSTRAINT `accountant_clients_test_ibfk_1` FOREIGN KEY (`accountant_id`) REFERENCES `users_test` (`id`),
  ADD CONSTRAINT `accountant_clients_test_ibfk_2` FOREIGN KEY (`client_id`) REFERENCES `clients_test` (`id`);

--
-- Constraints for table `clients_test`
--
ALTER TABLE `clients_test`
  ADD CONSTRAINT `clients_test_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users_test` (`id`);

--
-- Constraints for table `client_contacts`
--
ALTER TABLE `client_contacts`
  ADD CONSTRAINT `client_contacts_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `client_contacts_test`
--
ALTER TABLE `client_contacts_test`
  ADD CONSTRAINT `client_contacts_test_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients_test` (`id`);

--
-- Constraints for table `client_documents`
--
ALTER TABLE `client_documents`
  ADD CONSTRAINT `client_documents_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `client_xmls`
--
ALTER TABLE `client_xmls`
  ADD CONSTRAINT `client_xmls_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `facturas`
--
ALTER TABLE `facturas`
  ADD CONSTRAINT `facturas_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sat_download_requests`
--
ALTER TABLE `sat_download_requests`
  ADD CONSTRAINT `sat_download_requests_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
