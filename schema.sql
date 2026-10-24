-- SPELEC2A CRUD App schema
CREATE DATABASE IF NOT EXISTS php_crud_app
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE php_crud_app;

CREATE TABLE IF NOT EXISTS users (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name       VARCHAR(100) NOT NULL,
    email      VARCHAR(150) NOT NULL,
    phone      VARCHAR(20)  NULL,
    created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO users (name, email, phone) VALUES
    ('Juan Dela Cruz', 'juan@example.com', '09171234567'),
    ('Maria Santos',   'maria@example.com', NULL)
ON DUPLICATE KEY UPDATE name = VALUES(name);
