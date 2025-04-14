-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : lun. 14 avr. 2025 à 21:17
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
-- Base de données : `hrconnect`
--

-- --------------------------------------------------------

--
-- Structure de la table `absence`
--

CREATE TABLE `absence` (
  `id` int(11) NOT NULL,
  `employe_id` int(11) NOT NULL,
  `motif` enum('MALADIE','CONGE','AUTRE') NOT NULL,
  `justificatif` text DEFAULT NULL,
  `remarque` text DEFAULT NULL,
  `date_enregistrement` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `absence`
--

INSERT INTO `absence` (`id`, `employe_id`, `motif`, `justificatif`, `remarque`, `date_enregistrement`) VALUES
(2, 2, 'AUTRE', 'C:\\Users\\Haythem\\Downloads\\Certificat medical.pdf', ' b bhvghvg', '2025-03-05 08:45:52');

-- --------------------------------------------------------

--
-- Structure de la table `admin`
--

CREATE TABLE `admin` (
  `id` int(11) NOT NULL,
  `first_name` varchar(255) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `candidat`
--

CREATE TABLE `candidat` (
  `id` int(11) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `candidat`
--

INSERT INTO `candidat` (`id`, `last_name`, `first_name`, `email`, `phone`) VALUES
(4, 'Aymen', 'Falten', 'aymen@gmail.com', '20123123'),
(5, 'Salim', 'Mejri', 'Salim@gmail.com', '20123124'),
(6, 'Salah', 'Mejri', 'salah@gmail.com', '20123125'),
(7, 'amine', 'raissi', 'aminraissi43@gmail.com', '96200228'),
(8, 'test', 'test', 'haithemdridiweb@gmail.com', '29647262'),
(9, 'testuser', 'testuser', 'testuser@gmail.com', '29175235'),
(11, 'azanzhanh', 'zahazha', 'azaza@gmail.com', '29647261'),
(12, 'ANZA', 'JZAKJZAJ', 'AZNAZ@aaz.aza', '29647241'),
(13, 'amine', 'amine', 'aminraissi54@gmail.com', '29647263');

-- --------------------------------------------------------

--
-- Structure de la table `candidature`
--

CREATE TABLE `candidature` (
  `id` int(11) NOT NULL,
  `candidat_id` int(11) NOT NULL,
  `offre_emploi_id` int(11) NOT NULL,
  `cv` varchar(255) NOT NULL,
  `reference` varchar(8) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'En cours'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `demande_conge`
--

CREATE TABLE `demande_conge` (
  `id` int(11) NOT NULL,
  `employe_id` int(11) NOT NULL,
  `typeConge` enum('MALADIE','ANNUEL','MATERNITE','PATERNITE','FORMATION') NOT NULL,
  `dateDebut` date NOT NULL,
  `dateFin` date NOT NULL,
  `statut` enum('EN_ATTENTE','ACCEPTEE','REFUSEE') DEFAULT 'EN_ATTENTE'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `demande_conge`
--

INSERT INTO `demande_conge` (`id`, `employe_id`, `typeConge`, `dateDebut`, `dateFin`, `statut`) VALUES
(2, 18, 'ANNUEL', '2025-03-14', '2025-03-15', 'EN_ATTENTE'),
(3, 18, 'ANNUEL', '2025-03-14', '2025-03-15', 'ACCEPTEE'),
(4, 18, 'MALADIE', '2025-03-14', '2025-03-21', 'EN_ATTENTE'),
(6, 1, 'MALADIE', '2025-03-04', '2025-03-04', 'ACCEPTEE'),
(7, 1, 'MALADIE', '2025-03-05', '2025-03-05', 'ACCEPTEE'),
(9, 2, 'MALADIE', '2025-03-05', '2025-03-13', 'REFUSEE');

-- --------------------------------------------------------

--
-- Structure de la table `employe`
--

CREATE TABLE `employe` (
  `id` int(11) NOT NULL,
  `cin` int(8) DEFAULT NULL,
  `nom` varchar(255) NOT NULL,
  `prenom` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `hiring_date` date DEFAULT NULL,
  `soldeConges` int(11) DEFAULT 0,
  `solde_conges` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `employe`
--

INSERT INTO `employe` (`id`, `cin`, `nom`, `prenom`, `email`, `password`, `hiring_date`, `soldeConges`, `solde_conges`) VALUES
(1, 0, 'haythem', 'dridi', 'haithemdridiweb@gmail.com', 'haithemdridiweb@gmail.com', '0000-00-00', 0, NULL),
(2, 23456789, 'Mohamed', 'Ali', 'mohamed.ali@gmail.com', 'hashed_password2', '2024-06-10', 10, NULL),
(3, 34567890, 'Sana', 'Ben Ammar', 'sana.benammar@gmail.com', 'hashed_password3', '2023-09-25', 15, NULL),
(4, 45678901, 'Khaled', 'Trabelsi', 'khaled.trabelsi@gmail.com', 'hashed_password4', '2022-04-15', 20, NULL),
(5, 56789012, 'Nour', 'Mejri', 'nour.mejri@gmail.com', 'hashed_password5', '2021-12-05', 30, NULL),
(18, 0, 'Haythem', 'Haythem', 'haithemdridiweb@gmail.com', 'haithemdridiweb@gmail.com', '2025-02-18', 0, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `formateurs`
--

CREATE TABLE `formateurs` (
  `id` int(11) NOT NULL,
  `first_name` varchar(255) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `formateurs`
--

INSERT INTO `formateurs` (`id`, `first_name`, `last_name`, `email`, `password`) VALUES
(2, 'Haythem', 'Dridi', 'haithemdridiweb@gmail.com', 'haithemdridiweb@gmail.com'),
(3, 'Amine', 'Raisi', 'amine@gmail.com', 'amine@gmail.com'),
(4, 'Ala', 'Ben Terdayt', 'ala@gmail.com', 'ala');

-- --------------------------------------------------------

--
-- Structure de la table `formations`
--

CREATE TABLE `formations` (
  `id` int(11) NOT NULL,
  `formateur_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `image` varchar(255) NOT NULL,
  `description` varchar(255) NOT NULL,
  `is_online` tinyint(1) NOT NULL,
  `place` varchar(255) DEFAULT NULL,
  `lat` double DEFAULT NULL,
  `lng` double DEFAULT NULL,
  `available_for_employee` tinyint(1) NOT NULL,
  `available_for_intern` tinyint(1) NOT NULL,
  `start_date` datetime NOT NULL,
  `end_date` datetime DEFAULT NULL,
  `price` double DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `formations`
--

INSERT INTO `formations` (`id`, `formateur_id`, `title`, `image`, `description`, `is_online`, `place`, `lat`, `lng`, `available_for_employee`, `available_for_intern`, `start_date`, `end_date`, `price`) VALUES
(31, 2, 'Formation JavaFx', 'https://i.ibb.co/DfZDzwss/9ad51a8f934a.png', 'Formation JavaFx', 1, '', 0, 0, 1, 1, '2025-02-20 22:54:37', '2025-02-21 22:54:37', 59),
(41, 2, 'Test formation payante', 'https://i.ibb.co/Zzqw2Dk3/59d345242af9.png', 'Test formation payante', 0, 'Esprit bloc I,J,K, Cebalat, Tunisia', 36.9010594, 10.190243, 1, 1, '2025-03-05 07:53:48', '2025-03-06 06:53:48', 9.99),
(47, 2, 'formation php', 'https://i.ibb.co/wFCSSvrh/d150e2216999.png', 'php', 0, 'Esprit School of Business, Cebalat, Tunisia', 36.89923520000001, 10.189445, 1, 1, '2025-03-06 09:02:49', '2025-03-06 09:02:49', 12),
(48, 2, 'tesssst', 'https://i.ibb.co/wFCSSvrh/d150e2216999.png', 'jdj', 0, 'Esprit School of Business, Cebalat, Tunisia', 36.89923520000001, 10.189445, 1, 1, '2025-03-06 09:17:19', NULL, 10),
(49, 2, 'eyyey&yz', 'https://i.ibb.co/DfZDzwss/9ad51a8f934a.png', 'zyzyzy', 0, 'Bardo, Tunisia', 36.80840260000001, 10.1283163, 1, 1, '2025-03-06 09:21:19', NULL, 0);

-- --------------------------------------------------------

--
-- Structure de la table `formation_participation`
--

CREATE TABLE `formation_participation` (
  `formation_id` int(11) NOT NULL,
  `employe_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `formation_participation`
--

INSERT INTO `formation_participation` (`formation_id`, `employe_id`) VALUES
(31, 18);

-- --------------------------------------------------------

--
-- Structure de la table `historique_candidatures`
--

CREATE TABLE `historique_candidatures` (
  `reference` varchar(8) NOT NULL,
  `status` varchar(50) DEFAULT NULL,
  `date_modification` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `historique_candidatures`
--

INSERT INTO `historique_candidatures` (`reference`, `status`, `date_modification`) VALUES
('CAN16236', 'acceptée', '2025-02-26 10:15:39'),
('CAN19892', 'accepted', '2025-03-04 21:08:55'),
('CAN29810', 'En cours', '2025-03-04 23:04:57'),
('CAN47108', 'En cours', '2025-03-04 23:22:28'),
('CAN90879', 'accepted', '2025-03-05 08:31:31'),
('CAN90990', 'accepted', '2025-03-04 23:01:54');

-- --------------------------------------------------------

--
-- Structure de la table `hr`
--

CREATE TABLE `hr` (
  `id` int(11) NOT NULL,
  `first_name` varchar(255) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `offre_emploi`
--

CREATE TABLE `offre_emploi` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text NOT NULL,
  `location` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `offre_emploi`
--

INSERT INTO `offre_emploi` (`id`, `title`, `description`, `location`) VALUES
(7, 'azbahzbahbhbh', 'bzhabzhab', 'hbzahbz'),
(8, 'bonjir', '', '');

-- --------------------------------------------------------

--
-- Structure de la table `quiz`
--

CREATE TABLE `quiz` (
  `id` int(11) NOT NULL,
  `formation_id` int(11) NOT NULL,
  `question` varchar(255) NOT NULL,
  `reponse1` varchar(255) NOT NULL,
  `reponse2` varchar(255) DEFAULT NULL,
  `reponse3` varchar(255) DEFAULT NULL,
  `num_reponse_correct` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `quiz`
--

INSERT INTO `quiz` (`id`, `formation_id`, `question`, `reponse1`, `reponse2`, `reponse3`, `num_reponse_correct`) VALUES
(16, 31, 'Quelle classe est utilisée pour créer une fenêtre en JavaFX !?', 'JFrame', 'Stage', 'Window', 2),
(17, 31, 'Quel est le langage utilisé pour styliser une interface JavaFX ?', 'CSS', 'XML', 'JavaScript', 1),
(18, 31, 'Quelle méthode est utilisée pour lancer une application JavaFX ?', 'launch', 'start', 'run', 1),
(23, 31, 'aabc', '1', '2', '3', 1),
(24, 31, 'hello', '5', '5', '5', 1),
(25, 31, 'question', 'rep', 'rep2', 'rep3', 3);

-- --------------------------------------------------------

--
-- Structure de la table `quiz_reponses`
--

CREATE TABLE `quiz_reponses` (
  `employe_id` int(11) NOT NULL,
  `quiz_id` int(11) NOT NULL,
  `num_reponse` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `quiz_reponses`
--

INSERT INTO `quiz_reponses` (`employe_id`, `quiz_id`, `num_reponse`) VALUES
(18, 16, 2),
(18, 17, 1),
(18, 18, 2);

-- --------------------------------------------------------

--
-- Structure de la table `reclamation`
--

CREATE TABLE `reclamation` (
  `id` int(11) NOT NULL,
  `employee_name` varchar(100) NOT NULL,
  `type` enum('Workplace Harassment','Salary Issue','Working Conditions','Other') NOT NULL,
  `description` text NOT NULL,
  `date_of_submission` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('Pending','In Progress','Resolved','Rejected') DEFAULT 'Pending',
  `priority` enum('Low','Medium','High') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `reset_password_request`
--

CREATE TABLE `reset_password_request` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `selector` varchar(255) NOT NULL,
  `hashed_token` varchar(255) NOT NULL,
  `requested_at` datetime NOT NULL,
  `expires_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `stagaires`
--

CREATE TABLE `stagaires` (
  `id` int(11) NOT NULL,
  `first_name` varchar(255) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `debut_stage` date NOT NULL,
  `fin_stage` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `ticket_reclamation`
--

CREATE TABLE `ticket_reclamation` (
  `id` int(11) NOT NULL,
  `reclamation_id` int(11) NOT NULL,
  `hr_staff_name` varchar(100) NOT NULL,
  `response_message` text DEFAULT NULL,
  `date_of_response` timestamp NOT NULL DEFAULT current_timestamp(),
  `action_taken` text DEFAULT NULL,
  `resolution_status` enum('Resolved','Escalated','Closed') DEFAULT 'Escalated'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `user`
--

CREATE TABLE `user` (
  `cin` int(11) NOT NULL,
  `tel` int(11) NOT NULL,
  `nom` varchar(255) NOT NULL,
  `prenom` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `roles` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`roles`)),
  `id` int(11) NOT NULL,
  `otp` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `user`
--

INSERT INTO `user` (`cin`, `tel`, `nom`, `prenom`, `email`, `password`, `roles`, `id`, `otp`) VALUES
(10123124, 29175235, 'haythem', 'dridi', 'haithemdridiweb@gmail.com', '$2y$13$qZprh6m99NjqKPzE.sgbOuQk1O/6YWGSZY14FJX55tRdXmo.AjQU2', '[\"ROLE_ADMIN\"]', 13, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `valider_conge`
--

CREATE TABLE `valider_conge` (
  `id` int(11) NOT NULL,
  `demande_id` int(11) NOT NULL,
  `statut` enum('EN_ATTENTE','ACCEPTEE','REFUSEE') NOT NULL,
  `commentaire` text DEFAULT NULL,
  `dateValidation` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `valider_conge`
--

INSERT INTO `valider_conge` (`id`, `demande_id`, `statut`, `commentaire`, `dateValidation`) VALUES
(1, 3, 'ACCEPTEE', 'accepter', '2025-03-04'),
(2, 2, 'ACCEPTEE', NULL, '2025-03-04'),
(3, 2, 'EN_ATTENTE', '100', '2025-03-04'),
(4, 4, 'EN_ATTENTE', 'test', '2025-03-04'),
(5, 7, 'ACCEPTEE', '1', '2025-03-05'),
(6, 6, 'ACCEPTEE', 'pl', '2025-03-05'),
(7, 7, 'EN_ATTENTE', '1', '2025-03-05'),
(8, 7, 'ACCEPTEE', '1', '2025-03-05'),
(9, 9, 'REFUSEE', 'hjhjg', '2025-03-05');

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `absence`
--
ALTER TABLE `absence`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employe_id` (`employe_id`);

--
-- Index pour la table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `candidat`
--
ALTER TABLE `candidat`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `phone` (`phone`);

--
-- Index pour la table `candidature`
--
ALTER TABLE `candidature`
  ADD PRIMARY KEY (`id`),
  ADD KEY `candidat_id` (`candidat_id`),
  ADD KEY `offre_emploi_id` (`offre_emploi_id`);

--
-- Index pour la table `demande_conge`
--
ALTER TABLE `demande_conge`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employe_id` (`employe_id`);

--
-- Index pour la table `employe`
--
ALTER TABLE `employe`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `formateurs`
--
ALTER TABLE `formateurs`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `formations`
--
ALTER TABLE `formations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_formateur` (`formateur_id`);

--
-- Index pour la table `formation_participation`
--
ALTER TABLE `formation_participation`
  ADD KEY `fk_formation` (`formation_id`),
  ADD KEY `fk_employe` (`employe_id`);

--
-- Index pour la table `historique_candidatures`
--
ALTER TABLE `historique_candidatures`
  ADD PRIMARY KEY (`reference`);

--
-- Index pour la table `hr`
--
ALTER TABLE `hr`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `offre_emploi`
--
ALTER TABLE `offre_emploi`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `quiz`
--
ALTER TABLE `quiz`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_quiz_formation` (`formation_id`);

--
-- Index pour la table `quiz_reponses`
--
ALTER TABLE `quiz_reponses`
  ADD KEY `fk_quiz_reponses_employe` (`employe_id`),
  ADD KEY `fk_quiz_reponses_quiz` (`quiz_id`);

--
-- Index pour la table `stagaires`
--
ALTER TABLE `stagaires`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `valider_conge`
--
ALTER TABLE `valider_conge`
  ADD PRIMARY KEY (`id`),
  ADD KEY `demande_id` (`demande_id`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `absence`
--
ALTER TABLE `absence`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `candidat`
--
ALTER TABLE `candidat`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT pour la table `candidature`
--
ALTER TABLE `candidature`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT pour la table `demande_conge`
--
ALTER TABLE `demande_conge`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT pour la table `employe`
--
ALTER TABLE `employe`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT pour la table `formateurs`
--
ALTER TABLE `formateurs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pour la table `formations`
--
ALTER TABLE `formations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=60;

--
-- AUTO_INCREMENT pour la table `hr`
--
ALTER TABLE `hr`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `offre_emploi`
--
ALTER TABLE `offre_emploi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT pour la table `quiz`
--
ALTER TABLE `quiz`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT pour la table `stagaires`
--
ALTER TABLE `stagaires`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `user`
--
ALTER TABLE `user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT pour la table `valider_conge`
--
ALTER TABLE `valider_conge`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `absence`
--
ALTER TABLE `absence`
  ADD CONSTRAINT `absence_ibfk_1` FOREIGN KEY (`employe_id`) REFERENCES `employe` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `candidature`
--
ALTER TABLE `candidature`
  ADD CONSTRAINT `candidature_ibfk_1` FOREIGN KEY (`candidat_id`) REFERENCES `candidat` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `candidature_ibfk_2` FOREIGN KEY (`offre_emploi_id`) REFERENCES `offre_emploi` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `demande_conge`
--
ALTER TABLE `demande_conge`
  ADD CONSTRAINT `demande_conge_ibfk_1` FOREIGN KEY (`employe_id`) REFERENCES `employe` (`id`);

--
-- Contraintes pour la table `formations`
--
ALTER TABLE `formations`
  ADD CONSTRAINT `fk_formateur` FOREIGN KEY (`formateur_id`) REFERENCES `formateurs` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `formation_participation`
--
ALTER TABLE `formation_participation`
  ADD CONSTRAINT `fk_employe` FOREIGN KEY (`employe_id`) REFERENCES `employe` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_formation` FOREIGN KEY (`formation_id`) REFERENCES `formations` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `quiz`
--
ALTER TABLE `quiz`
  ADD CONSTRAINT `fk_quiz_formation` FOREIGN KEY (`formation_id`) REFERENCES `formations` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `quiz_reponses`
--
ALTER TABLE `quiz_reponses`
  ADD CONSTRAINT `fk_quiz_reponses_employe` FOREIGN KEY (`employe_id`) REFERENCES `employe` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_quiz_reponses_quiz` FOREIGN KEY (`quiz_id`) REFERENCES `quiz` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `valider_conge`
--
ALTER TABLE `valider_conge`
  ADD CONSTRAINT `valider_conge_ibfk_1` FOREIGN KEY (`demande_id`) REFERENCES `demande_conge` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
