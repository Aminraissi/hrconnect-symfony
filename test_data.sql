-- Insertion d'offres d'emploi
INSERT INTO offre_emploi (titre, type_contrat, localisation, salaire, description, profil_recherche, avantages, is_active, date_publication) 
VALUES ('Développeur PHP Symfony', 'CDI', 'Paris', '45000-55000€', 'Nous recherchons un développeur PHP Symfony expérimenté pour rejoindre notre équipe.', 'Expérience de 3 ans minimum en PHP et Symfony', 'Tickets restaurant, mutuelle, télétravail partiel', 1, NOW());

INSERT INTO offre_emploi (titre, type_contrat, localisation, salaire, description, profil_recherche, avantages, is_active, date_publication) 
VALUES ('Chef de projet IT', 'CDI', 'Lyon', '50000-60000€', 'Nous recherchons un chef de projet IT pour gérer nos projets de développement.', 'Expérience de 5 ans minimum en gestion de projet IT', 'Tickets restaurant, mutuelle, télétravail partiel', 1, NOW());

INSERT INTO offre_emploi (titre, type_contrat, localisation, salaire, description, profil_recherche, avantages, is_active, date_publication) 
VALUES ('Développeur Frontend React', 'CDD', 'Marseille', '40000-45000€', 'Nous recherchons un développeur Frontend React pour notre équipe.', 'Expérience de 2 ans minimum en React', 'Tickets restaurant, mutuelle', 1, NOW());
