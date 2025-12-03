-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : dim. 30 nov. 2025 à 23:52
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `tableau_lego`
--

DELIMITER $$
--
-- Procédures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `get_stock_brique` (IN `p_id_brique` INT)   BEGIN
    SELECT 
        id_brique,
        reference,
        description,
        couleur,
        prix,
        stock_actuel
    FROM vue_stock_briques
    WHERE id_brique = p_id_brique;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `get_stock_faible` ()   BEGIN
    SELECT * FROM vue_stock_faible;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `get_stock_global` ()   BEGIN
    SELECT * FROM vue_stock_briques;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Structure de la table `brique`
--

CREATE TABLE `brique` (
  `id_brique` int(11) NOT NULL,
  `reference` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `couleur` varchar(50) DEFAULT NULL,
  `prix` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `brique`
--

INSERT INTO `brique` (`id_brique`, `reference`, `description`, `couleur`, `prix`) VALUES
(1, 'BR001', 'Brique 2x2 classique', 'rouge', 0.50),
(2, 'BR002', 'Brique 2x4', 'bleu', 0.75),
(3, 'BR003', 'Brique 1x2 spéciale', 'vert', 0.30),
(4, 'BR004', 'Brique 2x2 coins arrondis', 'jaune', 0.55),
(5, 'BR005', 'Brique 2x6 renforcée', 'noir', 0.95),
(6, 'BR006', 'Brique transparente 2x2', 'transparent', 0.80),
(7, 'BR007', 'Brique angle 90°', 'blanc', 0.60),
(8, 'BR008', 'Brique plate 1x4', 'gris', 0.40),
(9, 'BR009', 'Brique plate 2x2', 'noir', 0.45),
(10, 'BR010', 'Brique technique avec axe', 'gris', 1.20),
(11, 'BR011', 'Brique décorée', 'rouge', 1.00);

-- --------------------------------------------------------

--
-- Structure de la table `client`
--

CREATE TABLE `client` (
  `id_client` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `adresse` varchar(200) NOT NULL,
  `code_postale` varchar(10) NOT NULL,
  `ville` varchar(100) NOT NULL,
  `telephone` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `client`
--

INSERT INTO `client` (`id_client`, `nom`, `prenom`, `email`, `adresse`, `code_postale`, `ville`, `telephone`) VALUES
(1, 'Dupont', 'Jean', 'jean.dupont@example.com', '12 rue des Lilas', '75001', 'Paris', '0123456789'),
(2, 'Martin', 'Sophie', 'sophie.martin@example.com', '34 avenue Victor Hugo', '69002', 'Lyon', '0987654321');

-- --------------------------------------------------------

--
-- Structure de la table `commande`
--

CREATE TABLE `commande` (
  `id_commande` int(11) NOT NULL,
  `date_commande` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(50) NOT NULL,
  `montant_total` decimal(12,2) DEFAULT NULL,
  `id_client` int(11) NOT NULL,
  `id_image` int(11) NOT NULL,
  `id_pavage` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `commande`
--

INSERT INTO `commande` (`id_commande`, `date_commande`, `status`, `montant_total`, `id_client`, `id_image`, `id_pavage`) VALUES
(1, '2025-11-24 07:12:20', 'En cours', 8.75, 1, 1, 1),
(2, '2025-11-24 07:12:20', 'Terminée', 6.00, 2, 2, 2);

--
-- Déclencheurs `commande`
--
DELIMITER $$
CREATE TRIGGER `tg_commande_after_update_facture_auto` AFTER UPDATE ON `commande` FOR EACH ROW BEGIN
    DECLARE v_exists INT DEFAULT 0;

    -- on émet la facture automatiquement si on passe à 'Terminée'
    IF OLD.status <> 'Terminée' AND NEW.status = 'Terminée' THEN
        SELECT COUNT(*) INTO v_exists FROM facture WHERE id_commande = NEW.id_commande;
        IF v_exists = 0 THEN
            INSERT INTO facture (id_commande, nom, prenom, email, adresse, code_postale, ville, telephone, TVA, montant_total, validation)
            SELECT NEW.id_commande, c.nom, c.prenom, c.email, c.adresse, c.code_postale, c.ville, c.telephone, 20.00, NEW.montant_total, 1
            FROM client c
            WHERE c.id_client = NEW.id_client;
            -- NB : l'insertion avec validation=1 déclenchera tg_facture_after_insert qui fera les vérifs et insérera mouvement_stock
        END IF;
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `tg_commande_before_delete` BEFORE DELETE ON `commande` FOR EACH ROW BEGIN
    IF (SELECT COUNT(*) FROM facture f WHERE f.id_commande = OLD.id_commande AND f.validation = 1) > 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Suppression interdite : commande déjà facturée/validée.';
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `tg_commande_before_update` BEFORE UPDATE ON `commande` FOR EACH ROW BEGIN
    -- Si une facture validée existe pour cette commande, empêcher la modification
    IF (SELECT COUNT(*) FROM facture f WHERE f.id_commande = OLD.id_commande AND f.validation = 1) > 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Modification interdite : commande déjà facturée/validée.';
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Structure de la table `facture`
--

CREATE TABLE `facture` (
  `id_facture` int(11) NOT NULL,
  `numero_facture` varchar(100) NOT NULL,
  `date_emission` timestamp NOT NULL DEFAULT current_timestamp(),
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `adresse` varchar(200) NOT NULL,
  `code_postale` varchar(10) NOT NULL,
  `ville` varchar(100) NOT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `TVA` decimal(5,2) DEFAULT NULL,
  `montant_total` decimal(12,2) DEFAULT NULL,
  `id_commande` int(11) NOT NULL,
  `validation` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `facture`
--

INSERT INTO `facture` (`id_facture`, `numero_facture`, `date_emission`, `nom`, `prenom`, `email`, `adresse`, `code_postale`, `ville`, `telephone`, `TVA`, `montant_total`, `id_commande`, `validation`) VALUES
(1, 'FA20250001', '2025-11-24 07:12:21', 'Dupont', 'Jean', 'jean.dupont@example.com', '12 rue des Lilas', '75001', 'Paris', '0123456789', 20.00, 10.75, 1, 1),
(2, 'FA20250002', '2025-11-24 07:12:21', 'Martin', 'Sophie', 'sophie.martin@example.com', '34 avenue Victor Hugo', '69002', 'Lyon', '0987654321', 20.00, 6.00, 2, 1);

--
-- Déclencheurs `facture`
--
DELIMITER $$
CREATE TRIGGER `tg_facture_after_insert` AFTER INSERT ON `facture` FOR EACH ROW BEGIN
    DECLARE missing INT DEFAULT 0;

    IF NEW.validation = 1 THEN

        SELECT COUNT(*) INTO missing
        FROM (
            SELECT lc.id_brique, SUM(lc.quantite) AS qte, vs.stock_actuel
            FROM ligne_commande lc
            JOIN vue_stock_briques vs ON lc.id_brique = vs.id_brique
            WHERE lc.id_commande = NEW.id_commande
            GROUP BY lc.id_brique, vs.stock_actuel
            HAVING SUM(lc.quantite) > vs.stock_actuel
        ) AS t;

        IF missing > 0 THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Stock insuffisant pour valider cette facture';
        END IF;

        INSERT INTO mouvement_stock (id_brique, type, quantite, id_commande, commentaire)
        SELECT id_brique, 'sortie', quantite, id_commande, 'Commande validée'
        FROM ligne_commande
        WHERE id_commande = NEW.id_commande;

    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `tg_facture_after_update` AFTER UPDATE ON `facture` FOR EACH ROW BEGIN
    DECLARE missing INT DEFAULT 0;

    IF OLD.validation = 0 AND NEW.validation = 1 THEN
        SELECT COUNT(*) INTO missing FROM (
        SELECT lc.id_brique, SUM(lc.quantite) AS qte, vs.stock_actuel
        FROM ligne_commande lc
        JOIN vue_stock_briques vs ON lc.id_brique = vs.id_brique
        WHERE lc.id_commande = NEW.id_commande
        GROUP BY lc.id_brique, vs.stock_actuel
        HAVING SUM(lc.quantite) > vs.stock_actuel
    ) AS t;


        IF missing > 0 THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Stock insuffisant pour valider cette facture.';
        END IF;

        INSERT INTO mouvement_stock (id_brique, type, quantite, id_commande, commentaire)
        SELECT id_brique, 'sortie', quantite, id_commande, 'Commande validée'
        FROM ligne_commande
        WHERE id_commande = NEW.id_commande;
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `tg_no_delete_facture` BEFORE DELETE ON `facture` FOR EACH ROW BEGIN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Suppression d’une facture interdite.';
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `tg_no_update_facture_validee` BEFORE UPDATE ON `facture` FOR EACH ROW BEGIN
    IF OLD.validation = 1 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Modification interdite : facture déjà validée.';
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `tg_no_update_numero_facture` BEFORE UPDATE ON `facture` FOR EACH ROW BEGIN
    IF NEW.numero_facture <> OLD.numero_facture THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Interdiction de modifier le numéro de facture.';
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `tg_numero_facture` BEFORE INSERT ON `facture` FOR EACH ROW BEGIN
    DECLARE v_count INT;
    SET NEW.date_emission = NOW();
    SELECT COUNT(*) + 1 INTO v_count
    FROM facture
    WHERE YEAR(date_emission) = YEAR(NOW());
    SET NEW.numero_facture = CONCAT('FA', YEAR(NOW()), LPAD(v_count,4,'0'));
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Structure de la table `imagesource`
--

CREATE TABLE `imagesource` (
  `id_image` int(11) NOT NULL,
  `chemin_fichier` varchar(300) DEFAULT NULL,
  `blob_image` longblob DEFAULT NULL,
  `date_ajout` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `imagesource`
--

INSERT INTO `imagesource` (`id_image`, `chemin_fichier`, `blob_image`, `date_ajout`) VALUES
(1, 'images/lego1.png', NULL, '2025-11-24 07:12:20'),
(2, 'images/lego2.png', NULL, '2025-11-24 07:12:20');

--
-- Déclencheurs `imagesource`
--
DELIMITER $$
CREATE TRIGGER `tg_imagesource_before_delete` BEFORE DELETE ON `imagesource` FOR EACH ROW BEGIN
    IF (SELECT COUNT(*) FROM commande c JOIN facture f ON c.id_commande = f.id_commande
        WHERE c.id_image = OLD.id_image AND f.validation = 1) > 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Suppression interdite : image source liée à commande validée.';
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `tg_imagesource_before_update` BEFORE UPDATE ON `imagesource` FOR EACH ROW BEGIN
    IF (SELECT COUNT(*) FROM commande c JOIN facture f ON c.id_commande = f.id_commande
        WHERE (c.id_image = OLD.id_image OR c.id_pavage = OLD.id_image) AND f.validation = 1) > 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Modification interdite : image source liée à commande validée.';
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Structure de la table `ligne_commande`
--

CREATE TABLE `ligne_commande` (
  `id_ligne` int(11) NOT NULL,
  `prix_unitaire` decimal(10,2) NOT NULL,
  `quantite` int(11) NOT NULL CHECK (`quantite` > 0),
  `id_commande` int(11) NOT NULL,
  `id_brique` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `ligne_commande`
--

INSERT INTO `ligne_commande` (`id_ligne`, `prix_unitaire`, `quantite`, `id_commande`, `id_brique`) VALUES
(1, 0.50, 10, 1, 1),
(2, 0.75, 5, 1, 2),
(3, 0.30, 20, 2, 3);

--
-- Déclencheurs `ligne_commande`
--
DELIMITER $$
CREATE TRIGGER `tg_ligne_before_delete` BEFORE DELETE ON `ligne_commande` FOR EACH ROW BEGIN
    IF (SELECT COUNT(*) FROM facture f WHERE f.id_commande = OLD.id_commande AND f.validation = 1) > 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Suppression interdite : commande associée déjà validée.';
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `tg_ligne_before_update` BEFORE UPDATE ON `ligne_commande` FOR EACH ROW BEGIN
    IF (SELECT COUNT(*) FROM facture f WHERE f.id_commande = OLD.id_commande AND f.validation = 1) > 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Modification interdite : commande associée déjà validée.';
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Structure de la table `mouvement_stock`
--

CREATE TABLE `mouvement_stock` (
  `id_mouvement` int(11) NOT NULL,
  `id_brique` int(11) NOT NULL,
  `type` enum('entrée','sortie') NOT NULL,
  `quantite` int(11) NOT NULL CHECK (`quantite` > 0),
  `date_mouvement` timestamp NOT NULL DEFAULT current_timestamp(),
  `id_commande` int(11) DEFAULT NULL,
  `commentaire` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `mouvement_stock`
--

INSERT INTO `mouvement_stock` (`id_mouvement`, `id_brique`, `type`, `quantite`, `date_mouvement`, `id_commande`, `commentaire`) VALUES
(1, 1, 'entrée', 5, '2025-11-24 08:14:32', NULL, 'Ajout de 5 brique'),
(2, 1, 'entrée', 50, '2025-11-30 22:18:21', NULL, 'Stock initial'),
(3, 2, 'entrée', 40, '2025-11-30 22:18:21', NULL, 'Stock initial'),
(4, 3, 'entrée', 100, '2025-11-30 22:18:21', NULL, 'Stock initial'),
(5, 4, 'entrée', 30, '2025-11-30 22:18:21', NULL, 'Stock initial'),
(6, 5, 'entrée', 25, '2025-11-30 22:18:21', NULL, 'Stock initial'),
(7, 6, 'entrée', 20, '2025-11-30 22:18:21', NULL, 'Stock initial'),
(8, 7, 'entrée', 15, '2025-11-30 22:18:21', NULL, 'Stock initial'),
(9, 8, 'entrée', 10, '2025-11-30 22:18:21', NULL, 'Stock initial'),
(10, 9, 'entrée', 8, '2025-11-30 22:18:21', NULL, 'Stock initial'),
(11, 10, 'entrée', 5, '2025-11-30 22:18:21', NULL, 'Stock initial'),
(12, 11, 'entrée', 12, '2025-11-30 22:18:21', NULL, 'Stock initial'),
(13, 1, 'sortie', 15, '2025-11-30 22:18:31', NULL, 'Vente cliente DUPONT'),
(14, 2, 'sortie', 10, '2025-11-30 22:18:31', NULL, 'Commande spéciale'),
(15, 3, 'sortie', 35, '2025-11-30 22:18:31', NULL, 'Vente en ligne'),
(16, 4, 'sortie', 5, '2025-11-30 22:18:31', NULL, 'Commande boutique'),
(17, 5, 'sortie', 10, '2025-11-30 22:18:31', NULL, 'Vente pro'),
(18, 6, 'sortie', 15, '2025-11-30 22:18:31', NULL, 'Casse'),
(19, 7, 'sortie', 3, '2025-11-30 22:18:31', NULL, 'Test'),
(20, 9, 'sortie', 2, '2025-11-30 22:18:31', NULL, 'Usage interne'),
(21, 10, 'sortie', 2, '2025-11-30 22:18:31', NULL, 'Prototype');

--
-- Déclencheurs `mouvement_stock`
--
DELIMITER $$
CREATE TRIGGER `tg_blocage_sortie_stock` BEFORE INSERT ON `mouvement_stock` FOR EACH ROW BEGIN
    DECLARE v_stock INT;

    IF NEW.type = 'sortie' THEN

        SELECT stock_actuel INTO v_stock
        FROM vue_stock_briques
        WHERE id_brique = NEW.id_brique;

        IF v_stock < NEW.quantite THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Stock insuffisant pour cette sortie';
        END IF;

    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Structure de la table `pavage`
--

CREATE TABLE `pavage` (
  `id_pavage` int(11) NOT NULL,
  `chemin_fichier` varchar(300) DEFAULT NULL,
  `blob_pavage` longblob DEFAULT NULL,
  `date_generation` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `pavage`
--

INSERT INTO `pavage` (`id_pavage`, `chemin_fichier`, `blob_pavage`, `date_generation`) VALUES
(1, 'pavage/pavage1.png', NULL, '2025-11-24 07:12:20'),
(2, 'pavage/pavage2.png', NULL, '2025-11-24 07:12:20');

--
-- Déclencheurs `pavage`
--
DELIMITER $$
CREATE TRIGGER `tg_pavage_before_delete` BEFORE DELETE ON `pavage` FOR EACH ROW BEGIN
    IF (SELECT COUNT(*) FROM commande c JOIN facture f ON c.id_commande = f.id_commande
        WHERE c.id_pavage = OLD.id_pavage AND f.validation = 1) > 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Suppression interdite : pavage lié à commande validée.';
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `tg_pavage_before_update` BEFORE UPDATE ON `pavage` FOR EACH ROW BEGIN
    IF (SELECT COUNT(*) FROM commande c JOIN facture f ON c.id_commande = f.id_commande
        WHERE c.id_pavage = OLD.id_pavage AND f.validation = 1) > 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Modification interdite : pavage lié à commande validée.';
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Doublure de structure pour la vue `vue_stock_briques`
-- (Voir ci-dessous la vue réelle)
--
CREATE TABLE `vue_stock_briques` (
`id_brique` int(11)
,`reference` varchar(100)
,`description` text
,`couleur` varchar(50)
,`prix` decimal(10,2)
,`stock_actuel` decimal(33,0)
);

-- --------------------------------------------------------

--
-- Doublure de structure pour la vue `vue_stock_faible`
-- (Voir ci-dessous la vue réelle)
--
CREATE TABLE `vue_stock_faible` (
`id_brique` int(11)
,`reference` varchar(100)
,`description` text
,`couleur` varchar(50)
,`prix` decimal(10,2)
,`stock_actuel` decimal(33,0)
);

-- --------------------------------------------------------

--
-- Structure de la vue `vue_stock_briques`
--
DROP TABLE IF EXISTS `vue_stock_briques`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vue_stock_briques`  AS SELECT `b`.`id_brique` AS `id_brique`, `b`.`reference` AS `reference`, `b`.`description` AS `description`, `b`.`couleur` AS `couleur`, `b`.`prix` AS `prix`, coalesce(sum(case when `m`.`type` = 'entrée' then `m`.`quantite` when `m`.`type` = 'sortie' then -`m`.`quantite` else 0 end),0) AS `stock_actuel` FROM (`brique` `b` left join `mouvement_stock` `m` on(`b`.`id_brique` = `m`.`id_brique`)) GROUP BY `b`.`id_brique`, `b`.`reference`, `b`.`description`, `b`.`couleur`, `b`.`prix` ;

-- --------------------------------------------------------

--
-- Structure de la vue `vue_stock_faible`
--
DROP TABLE IF EXISTS `vue_stock_faible`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vue_stock_faible`  AS SELECT `vue_stock_briques`.`id_brique` AS `id_brique`, `vue_stock_briques`.`reference` AS `reference`, `vue_stock_briques`.`description` AS `description`, `vue_stock_briques`.`couleur` AS `couleur`, `vue_stock_briques`.`prix` AS `prix`, `vue_stock_briques`.`stock_actuel` AS `stock_actuel` FROM `vue_stock_briques` WHERE `vue_stock_briques`.`stock_actuel` < 10 ;

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `brique`
--
ALTER TABLE `brique`
  ADD PRIMARY KEY (`id_brique`);

--
-- Index pour la table `client`
--
ALTER TABLE `client`
  ADD PRIMARY KEY (`id_client`);

--
-- Index pour la table `commande`
--
ALTER TABLE `commande`
  ADD PRIMARY KEY (`id_commande`),
  ADD KEY `fk_commande_client` (`id_client`),
  ADD KEY `fk_commande_image` (`id_image`),
  ADD KEY `fk_commande_pavage` (`id_pavage`);

--
-- Index pour la table `facture`
--
ALTER TABLE `facture`
  ADD PRIMARY KEY (`id_facture`),
  ADD UNIQUE KEY `numero_facture` (`numero_facture`),
  ADD UNIQUE KEY `id_commande` (`id_commande`);

--
-- Index pour la table `imagesource`
--
ALTER TABLE `imagesource`
  ADD PRIMARY KEY (`id_image`);

--
-- Index pour la table `ligne_commande`
--
ALTER TABLE `ligne_commande`
  ADD PRIMARY KEY (`id_ligne`),
  ADD KEY `fk_ligne_commande_commande` (`id_commande`),
  ADD KEY `fk_ligne_commande_brique` (`id_brique`);

--
-- Index pour la table `mouvement_stock`
--
ALTER TABLE `mouvement_stock`
  ADD PRIMARY KEY (`id_mouvement`),
  ADD KEY `id_brique` (`id_brique`),
  ADD KEY `id_commande` (`id_commande`);

--
-- Index pour la table `pavage`
--
ALTER TABLE `pavage`
  ADD PRIMARY KEY (`id_pavage`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `brique`
--
ALTER TABLE `brique`
  MODIFY `id_brique` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT pour la table `client`
--
ALTER TABLE `client`
  MODIFY `id_client` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `commande`
--
ALTER TABLE `commande`
  MODIFY `id_commande` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `facture`
--
ALTER TABLE `facture`
  MODIFY `id_facture` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `imagesource`
--
ALTER TABLE `imagesource`
  MODIFY `id_image` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `ligne_commande`
--
ALTER TABLE `ligne_commande`
  MODIFY `id_ligne` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `mouvement_stock`
--
ALTER TABLE `mouvement_stock`
  MODIFY `id_mouvement` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT pour la table `pavage`
--
ALTER TABLE `pavage`
  MODIFY `id_pavage` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `commande`
--
ALTER TABLE `commande`
  ADD CONSTRAINT `fk_commande_client` FOREIGN KEY (`id_client`) REFERENCES `client` (`id_client`),
  ADD CONSTRAINT `fk_commande_image` FOREIGN KEY (`id_image`) REFERENCES `imagesource` (`id_image`),
  ADD CONSTRAINT `fk_commande_pavage` FOREIGN KEY (`id_pavage`) REFERENCES `pavage` (`id_pavage`);

--
-- Contraintes pour la table `facture`
--
ALTER TABLE `facture`
  ADD CONSTRAINT `fk_facture_commande` FOREIGN KEY (`id_commande`) REFERENCES `commande` (`id_commande`);

--
-- Contraintes pour la table `ligne_commande`
--
ALTER TABLE `ligne_commande`
  ADD CONSTRAINT `fk_ligne_commande_brique` FOREIGN KEY (`id_brique`) REFERENCES `brique` (`id_brique`),
  ADD CONSTRAINT `fk_ligne_commande_commande` FOREIGN KEY (`id_commande`) REFERENCES `commande` (`id_commande`);

--
-- Contraintes pour la table `mouvement_stock`
--
ALTER TABLE `mouvement_stock`
  ADD CONSTRAINT `mouvement_stock_ibfk_1` FOREIGN KEY (`id_brique`) REFERENCES `brique` (`id_brique`),
  ADD CONSTRAINT `mouvement_stock_ibfk_2` FOREIGN KEY (`id_commande`) REFERENCES `commande` (`id_commande`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
