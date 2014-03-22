
INSERT INTO `module` (`name`, `type`, `active`, `version`, `vendor`, `vendor_email`, `description`, `dependences`) VALUES
('Payment', 'custom', 1, '0.9.0', 'eSASe', 'alexermashev@gmail.com', '', '');

SET @moduleId = (SELECT LAST_INSERT_ID());
SET @maxOrder = (SELECT `order` + 1 FROM `admin_menu` ORDER BY `order` DESC LIMIT 1);

INSERT INTO `admin_menu` (`name`, `controller`, `action`, `module`, `order`) VALUES
('Payments', 'payments-administration', 'list', @moduleId, @maxOrder);

INSERT INTO `acl_resource` (`resource`, `description`, `module`) VALUES
('payments_administration_list', 'ACL - Viewing payment transactions in admin area', @moduleId),
('payments_administration_currencies', 'ACL - Viewing payment currencies in admin area', @moduleId);

CREATE TABLE IF NOT EXISTS `payment_module` (
    `module` int(10) unsigned NOT NULL,
    `update_event` varchar(50) NOT NULL,
    `delete_event` varchar(50) NOT NULL,
    `countable` tinyint(1) NOT NULL,
    FOREIGN KEY (module) REFERENCES module(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `payment_currency` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `code` varchar(3) NOT NULL,
    `name` varchar(50) NOT NULL,
    `active` tinyint(1) NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `code` (`code`),
    KEY `active` (`active`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

INSERT INTO `payment_currency` (`id`, `code`, `name`) VALUES
(1, 'RUB', 'Rubles'),
(2, 'USD', 'Dollars USA'),
(3, 'EUR', 'Euro');

CREATE TABLE IF NOT EXISTS `payment_type` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `name` varchar(100) NOT NULL,
    `description` varchar(50) NOT NULL,
    PRIMARY KEY (`id`)  
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `payment_type` (`id`, `name`, `description`) VALUES
(1, 'robokassa', 'Robokassa'),
(2, 'yandex-money', 'Yandex money'),
(3, 'cash', 'Cash');

CREATE TABLE IF NOT EXISTS `payment_transaction` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `user_id` int(10) unsigned NOT NULL,
    `date` date NOT NULL,
    `paid` tinyint(1) NOT NULL,
    `currency` varchar(3) NOT NULL,
    `payment_type` int(10) unsigned DEFAULT NULL,
    PRIMARY KEY (`id`),
    FOREIGN KEY (user_id) REFERENCES user(user_id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    FOREIGN KEY (currency) REFERENCES payment_currency(code)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    FOREIGN KEY (payment_type) REFERENCES payment_type(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `payment_transaction_item` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `transaction_id` int(10) unsigned NOT NULL,
    `object_id` int(10) unsigned NOT NULL,
    `module` int(10) unsigned NOT NULL,
    `title` varchar(100) NOT NULL,
    `amount` int(10) unsigned DEFAULT NULL,
    `discount` int(10) unsigned DEFAULT NULL,
    `count` int(10) unsigned DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `transaction` (`object_id`, `module`, `transaction_id`),
    FOREIGN KEY (transaction_id) REFERENCES payment_transaction(user_id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    FOREIGN KEY (module) REFERENCES payment_module(module)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;