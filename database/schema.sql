CREATE DATABASE IF NOT EXISTS `alianca_galeteria`
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `alianca_galeteria`;

CREATE TABLE `admins` (
  `id`            INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `name`          VARCHAR(120)     NOT NULL,
  `email`         VARCHAR(180)     NOT NULL UNIQUE,
  `password_hash` VARCHAR(255)     NOT NULL,
  `role`          ENUM('admin','manager','operator') NOT NULL DEFAULT 'operator',
  `active`        TINYINT(1)       NOT NULL DEFAULT 1,
  `created_at`    DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `login_attempts` (
  `id`           INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `ip`           VARCHAR(45)      NOT NULL,
  `email`        VARCHAR(180)     NOT NULL,
  `attempted_at` DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_ip_email` (`ip`, `email`),
  INDEX `idx_attempted_at` (`attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `categories` (
  `id`         INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `name`       VARCHAR(100)     NOT NULL,
  `slug`       VARCHAR(100)     NOT NULL UNIQUE,
  `active`     TINYINT(1)       NOT NULL DEFAULT 1,
  `sort_order` INT              NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `products` (
  `id`          INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `category_id` INT UNSIGNED     NOT NULL,
  `name`        VARCHAR(200)     NOT NULL,
  `description` TEXT,
  `price`       DECIMAL(10,2)    NOT NULL,
  `image_url`   VARCHAR(500),
  `active`      TINYINT(1)       NOT NULL DEFAULT 1,
  `featured`    TINYINT(1)       NOT NULL DEFAULT 0,
  `created_at`  DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `stock` (
  `id`           INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `product_id`   INT UNSIGNED     NOT NULL UNIQUE,
  `quantity`     INT              NOT NULL DEFAULT 0,
  `min_quantity` INT              NOT NULL DEFAULT 5,
  `unit`         VARCHAR(20)      NOT NULL DEFAULT 'un',
  `updated_at`   DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `stock_movements` (
  `id`         INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `product_id` INT UNSIGNED     NOT NULL,
  `type`       ENUM('in','out','adjustment','return') NOT NULL,
  `quantity`   INT              NOT NULL,
  `reason`     VARCHAR(255),
  `order_id`   INT UNSIGNED,
  `admin_id`   INT UNSIGNED,
  `created_at` DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_product_id` (`product_id`),
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `customers` (
  `id`         INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `name`       VARCHAR(150)     NOT NULL,
  `email`      VARCHAR(180)     UNIQUE,
  `phone`      VARCHAR(20),
  `cpf`        VARCHAR(14),
  `address`    TEXT,
  `notes`      TEXT,
  `active`     TINYINT(1)       NOT NULL DEFAULT 1,
  `created_at` DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `orders` (
  `id`              INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `customer_id`     INT UNSIGNED,
  `admin_id`        INT UNSIGNED,
  `status`          ENUM('rascunho','confirmado','em_preparo','pronto','entregue','cancelado') NOT NULL DEFAULT 'rascunho',
  `total`           DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `discount`        DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `notes`           TEXT,
  `upsell_token_id` INT UNSIGNED,
  `created_at`      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_customer_id` (`customer_id`),
  INDEX `idx_status` (`status`),
  INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `order_items` (
  `id`         INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `order_id`   INT UNSIGNED     NOT NULL,
  `product_id` INT UNSIGNED     NOT NULL,
  `quantity`   INT              NOT NULL,
  `unit_price` DECIMAL(10,2)    NOT NULL,
  `subtotal`   DECIMAL(10,2)    NOT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`order_id`)   REFERENCES `orders`(`id`)   ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `upsell_tokens` (
  `id`               INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `customer_id`      INT UNSIGNED  NOT NULL,
  `token_hash`       VARCHAR(64)   NOT NULL UNIQUE,
  `discount_percent` INT           NOT NULL DEFAULT 10,
  `product_id`       INT UNSIGNED,
  `message`          TEXT,
  `expires_at`       DATETIME      NOT NULL,
  `used_at`          DATETIME,
  `created_at`       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_token_hash` (`token_hash`),
  FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `raffles` (
  `id`                 INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `type`               ENUM('weekly','monthly') NOT NULL,
  `period_label`       VARCHAR(50)   NOT NULL,
  `winner_order_id`    INT UNSIGNED,
  `winner_customer_id` INT UNSIGNED,
  `winner_name`        VARCHAR(150),
  `participants_count` INT           NOT NULL DEFAULT 0,
  `simulated_date`     DATE,
  `drawn_at`           DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_type_period` (`type`, `period_label`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `raffle_participants` (
  `id`          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `raffle_id`   INT UNSIGNED  NOT NULL,
  `order_id`    INT UNSIGNED  NOT NULL,
  `customer_id` INT UNSIGNED  NOT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`raffle_id`) REFERENCES `raffles`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
