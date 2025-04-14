-- Supprimer les tables existantes
DROP TABLE IF EXISTS candidature;
DROP TABLE IF EXISTS offre_emploi;

-- Recréer la table offre_emploi selon la structure de la base de données db
CREATE TABLE offre_emploi (
  id INT AUTO_INCREMENT NOT NULL,
  title VARCHAR(200) NOT NULL,
  description TEXT NOT NULL,
  location VARCHAR(100) NOT NULL,
  PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;

-- Recréer la table candidature selon la structure de la base de données db
CREATE TABLE candidature (
  id INT AUTO_INCREMENT NOT NULL,
  candidat_id INT NOT NULL,
  offre_emploi_id INT NOT NULL,
  cv VARCHAR(255) NOT NULL,
  reference VARCHAR(8) DEFAULT NULL,
  status VARCHAR(50) DEFAULT 'En cours',
  PRIMARY KEY(id),
  CONSTRAINT FK_CANDIDATURE_CANDIDAT FOREIGN KEY (candidat_id) REFERENCES candidat (id) ON DELETE CASCADE,
  CONSTRAINT FK_CANDIDATURE_OFFRE FOREIGN KEY (offre_emploi_id) REFERENCES offre_emploi (id) ON DELETE CASCADE
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;

-- Créer la table candidat si elle n'existe pas déjà
CREATE TABLE IF NOT EXISTS candidat (
  id INT AUTO_INCREMENT NOT NULL,
  last_name VARCHAR(100) NOT NULL,
  first_name VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL,
  phone VARCHAR(20) NOT NULL,
  PRIMARY KEY(id),
  UNIQUE KEY (email),
  UNIQUE KEY (phone)
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;

-- Insérer quelques données de test dans la table offre_emploi
INSERT INTO offre_emploi (title, description, location) VALUES 
('Développeur PHP Symfony', 'Nous recherchons un développeur PHP Symfony expérimenté pour rejoindre notre équipe.', 'Paris'),
('Chef de projet IT', 'Nous recherchons un chef de projet IT pour gérer nos projets de développement.', 'Lyon'),
('Développeur Frontend React', 'Nous recherchons un développeur Frontend React pour notre équipe.', 'Marseille');

-- Insérer quelques données de test dans la table candidat
INSERT INTO candidat (last_name, first_name, email, phone) VALUES 
('Dupont', 'Jean', 'jean.dupont@example.com', '0612345678'),
('Martin', 'Sophie', 'sophie.martin@example.com', '0687654321'),
('Durand', 'Pierre', 'pierre.durand@example.com', '0654321987');
