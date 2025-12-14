-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : sam. 29 nov. 2025 à 03:46
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `plan_lego`
--

-- --------------------------------------------------------

--
-- Doublure de structure pour la vue `day_count`
-- (Voir ci-dessous la vue réelle)
--
CREATE TABLE `day_count` (
`id` int(10) unsigned
,`day_count` double
);

-- --------------------------------------------------------

--
-- Structure de la table `images`
--

CREATE TABLE `images` (
  `id` int(11) UNSIGNED NOT NULL,
  `image` blob NOT NULL,
  `date_upload` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `invoices`
--

CREATE TABLE `invoices` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_order` char(14) NOT NULL,
  `date_created` timestamp NOT NULL DEFAULT current_timestamp(),
  `total` decimal(10,2) NOT NULL,
  `locked` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déclencheurs `invoices`
--
DELIMITER $$
CREATE TRIGGER `trg_lock_invoice` BEFORE UPDATE ON `invoices` FOR EACH ROW BEGIN
    IF OLD.locked = TRUE THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Invoice is locked and cannot be modified.';
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Doublure de structure pour la vue `ms_since_midnight`
-- (Voir ci-dessous la vue réelle)
--
CREATE TABLE `ms_since_midnight` (
`id` int(10) unsigned
,`ms_since_midnight` double
);

-- --------------------------------------------------------

--
-- Structure de la table `orders`
--

CREATE TABLE `orders` (
  `num` char(14) NOT NULL,
  `date_order` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `statut` enum('Draft','Pending','Validated','Shipped','Delivered','Canceled') NOT NULL DEFAULT 'Draft',
  `id_image` int(11) UNSIGNED NOT NULL,
  `selection` enum('1','2','3') NOT NULL,
  `pavage` text NOT NULL,
  `sum` decimal(10,2) NOT NULL,
  `adress` varchar(255) NOT NULL,
  `id_user` int(11) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déclencheurs `orders`
--
DELIMITER $$
CREATE TRIGGER `trg_generate_invoice` AFTER UPDATE ON `orders` FOR EACH ROW BEGIN
    IF NEW.statut = 'Validated' AND OLD.statut <> 'Validated' THEN
        
        INSERT INTO invoices (id_order, total)
        SELECT NEW.num, NEW.sum;
    
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_lock_validated_orders` BEFORE UPDATE ON `orders` FOR EACH ROW BEGIN
    IF OLD.statut = 'Validated' AND NEW.statut NOT IN ('Shipped','Delivered') THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'This order is validated and cannot be modified.';
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_order_stock` AFTER UPDATE ON `orders` FOR EACH ROW BEGIN
    IF NEW.statut = 'Expédiée' AND OLD.statut <> 'Expédiée' THEN
        UPDATE pieces p
        JOIN order_pieces op ON op.id_piece = p.id
        SET p.stock = p.stock - op.quantity
        WHERE op.id_order = NEW.num;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Structure de la table `order_pieces`
--

CREATE TABLE `order_pieces` (
  `id_order` char(14) NOT NULL,
  `id_piece` int(10) UNSIGNED NOT NULL,
  `quantity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déclencheurs `order_pieces`
--
DELIMITER $$
CREATE TRIGGER `trg_lock_order_pieces_delete` BEFORE DELETE ON `order_pieces` FOR EACH ROW BEGIN
    IF (SELECT statut FROM orders WHERE num = OLD.id_order) = 'Validated' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Order is validated and cannot be deleted.';
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_lock_order_pieces_insert` BEFORE INSERT ON `order_pieces` FOR EACH ROW BEGIN
    IF (SELECT statut FROM orders WHERE num = NEW.id_order) = 'Validated' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Order is validated and cannot be modified.';
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_lock_order_pieces_update` BEFORE UPDATE ON `order_pieces` FOR EACH ROW BEGIN
    IF (SELECT statut FROM orders WHERE num = OLD.id_order) = 'Validated' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Order is validated and cannot be modified.';
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_update_order_sum` AFTER INSERT ON `order_pieces` FOR EACH ROW BEGIN
    UPDATE orders 
    SET `sum` = (
        SELECT SUM(op.quantity * p.unit_price)
        FROM order_pieces op
        JOIN pieces p ON op.id_piece = p.id
        WHERE op.id_order = NEW.id_order
    )
    WHERE num = NEW.id_order;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Structure de la table `pieces`
--

CREATE TABLE `pieces` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(20) NOT NULL,
  `width` int(11) NOT NULL,
  `height` int(11) NOT NULL,
  `unit_price` decimal(6,2) NOT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `hole_positions` varchar(32) DEFAULT NULL,
  `color` char(7) DEFAULT NULL,
  `serial_number` binary(16) NOT NULL,
  `authenticity_signature` binary(64) DEFAULT NULL,
  `authenticity_verified` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `pieces`
--

INSERT INTO `pieces` (`id`, `name`, `width`, `height`, `unit_price`, `stock`, `hole_positions`, `color`, `serial_number`, `authenticity_signature`, `authenticity_verified`) VALUES
(1, '3-3-0268', 3, 3, 0.50, 100, '0268', '#E54A3B', 0x00f40a000001a2b3c4d5e6f70809aa55, 0x0895bc61390712b354e8d7bf5cbbf2b38549ff7a06a717a0016a2d4923c3322a654a430d7d2ce0cc131836df889e0077d60f84c3e79bd8a0ff7ef111afb14600, NULL);

-- --------------------------------------------------------

--
-- Doublure de structure pour la vue `random_hex`
-- (Voir ci-dessous la vue réelle)
--
CREATE TABLE `random_hex` (
`id` int(10) unsigned
,`random_hex` varchar(20)
);

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

CREATE TABLE `users` (
  `id` int(11) UNSIGNED NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`) VALUES
(1, 'Humariu', 'rayan.essaidi2006@gmail.com', '$2y$10$7KEOLIdjo0ihQNpM4M4B4uWdnQEpinivXDc1pSgPWMxC2XNKLiK7C');

-- --------------------------------------------------------

--
-- Structure de la vue `day_count`
--
DROP TABLE IF EXISTS `day_count`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `day_count`  AS SELECT `pieces`.`id` AS `id`, conv(hex(substr(`pieces`.`serial_number`,1,2)),16,10) + 0 AS `day_count` FROM `pieces` ;

-- --------------------------------------------------------

--
-- Structure de la vue `ms_since_midnight`
--
DROP TABLE IF EXISTS `ms_since_midnight`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `ms_since_midnight`  AS SELECT `pieces`.`id` AS `id`, conv(hex(substr(`pieces`.`serial_number`,3,4)),16,10) + 0 AS `ms_since_midnight` FROM `pieces` ;

-- --------------------------------------------------------

--
-- Structure de la vue `random_hex`
--
DROP TABLE IF EXISTS `random_hex`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `random_hex`  AS SELECT `pieces`.`id` AS `id`, hex(substr(`pieces`.`serial_number`,7,10)) AS `random_hex` FROM `pieces` ;

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `images`
--
ALTER TABLE `images`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `invoices`
--
ALTER TABLE `invoices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_invoice_order` (`id_order`);

--
-- Index pour la table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`num`),
  ADD KEY `fk_image` (`id_image`),
  ADD KEY `fk_user` (`id_user`);

--
-- Index pour la table `order_pieces`
--
ALTER TABLE `order_pieces`
  ADD PRIMARY KEY (`id_order`,`id_piece`),
  ADD KEY `fk_order_piece_piece` (`id_piece`);

--
-- Index pour la table `pieces`
--
ALTER TABLE `pieces`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD UNIQUE KEY `serial_number` (`serial_number`),
  ADD KEY `idx_pieces_color` (`color`),
  ADD KEY `idx_pieces_holes` (`hole_positions`);

--
-- Index pour la table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `filled_username` (`username`) USING BTREE;

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `images`
--
ALTER TABLE `images`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `invoices`
--
ALTER TABLE `invoices`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `pieces`
--
ALTER TABLE `pieces`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT pour la table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `invoices`
--
ALTER TABLE `invoices`
  ADD CONSTRAINT `fk_invoice_order` FOREIGN KEY (`id_order`) REFERENCES `orders` (`num`) ON DELETE CASCADE;

--
-- Contraintes pour la table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_image` FOREIGN KEY (`id_image`) REFERENCES `images` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_user` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`) ON UPDATE CASCADE;

--
-- Contraintes pour la table `order_pieces`
--
ALTER TABLE `order_pieces`
  ADD CONSTRAINT `fk_order_piece_order` FOREIGN KEY (`id_order`) REFERENCES `orders` (`num`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_order_piece_piece` FOREIGN KEY (`id_piece`) REFERENCES `pieces` (`id`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
