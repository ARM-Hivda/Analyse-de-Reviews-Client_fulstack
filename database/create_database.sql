-- Script SQL pour créer la base de données MySQL
-- Exécutez ce script dans MySQL avant de lancer les migrations

CREATE DATABASE IF NOT EXISTS `review_analyzer` 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

-- Optionnel : Créer un utilisateur dédié (si vous ne voulez pas utiliser root)
-- CREATE USER IF NOT EXISTS 'review_user'@'localhost' IDENTIFIED BY 'review_password';
-- GRANT ALL PRIVILEGES ON `review_analyzer`.* TO 'review_user'@'localhost';
-- FLUSH PRIVILEGES;


