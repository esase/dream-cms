
INSERT INTO `module` (`name`, `type`, `active`, `version`, `vendor`, `vendor_email`, `description`, `dependences`) VALUES
('Payment', 'custom', 1, '0.9.0', 'eSASe', 'alexermashev@gmail.com', '', '');

SET @moduleId = (SELECT LAST_INSERT_ID());
SET @maxOrder = (SELECT `order` + 1 FROM `admin_menu` ORDER BY `order` DESC LIMIT 1);

INSERT INTO `admin_menu_category` (`name`, `module`) VALUES
('Payments', @moduleId);

SET @menuCategoryId = (SELECT LAST_INSERT_ID());

INSERT INTO `admin_menu` (`name`, `controller`, `action`, `module`, `order`, `category`) VALUES
('List of transactions', 'payments-administration', 'list', @moduleId, @maxOrder, @menuCategoryId),
('Currencies', 'payments-administration', 'currencies', @moduleId, @maxOrder + 1, @menuCategoryId);

INSERT INTO `acl_resource` (`resource`, `description`, `module`) VALUES
('payments_administration_list', 'ACL - Viewing payment transactions in admin area', @moduleId),
('payments_administration_currencies', 'ACL - Viewing payment currencies in admin area', @moduleId),
('payments_administration_add_currency', 'ACL - Adding payment currencies in admin area', @moduleId),
('payments_administration_edit_currency', 'ACL - Editing payment currencies in admin area', @moduleId),
('payments_administration_delete_currencies', 'ACL - Deleting payment currencies in admin area', @moduleId),
('payments_administration_edit_exchange_rates', 'ACL - Editing exchange rates in admin area', @moduleId);

INSERT INTO `event` (`name`, `module`, `description`) VALUES
('add_payment_currency', @moduleId, 'Event - Adding payment currencies'),
('edit_payment_currency', @moduleId, 'Event - Editing payment currencies'),
('delete_payment_currency', @moduleId, 'Event - Deleting payment currencies'),
('edit_exchange_rates', @moduleId, 'Event - Editing exchange rates');

CREATE TABLE IF NOT EXISTS `payment_module` (
    `module` int(10) unsigned NOT NULL,
    `update_event` varchar(50) NOT NULL,
    `delete_event` varchar(50) NOT NULL,
    `countable` tinyint(1) NOT NULL,
    PRIMARY KEY (`module`),
    FOREIGN KEY (module) REFERENCES module(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `payment_currency` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `code` varchar(3) NOT NULL,
    `name` varchar(50) NOT NULL,
    `primary_currency` tinyint(1) NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `code` (`code`),
    KEY `primary_currency` (`primary_currency`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

INSERT INTO `payment_currency` (`id`, `code`, `name`, `primary_currency`) VALUES
(1, 'RUB', 'Rubles', 1),
(2, 'USD', 'Dollars USA', 0),
(3, 'EUR', 'Euro', 0);

CREATE TABLE IF NOT EXISTS `payment_exchange_rate` (
    `rate` float unsigned NOT NULL,
    `currency` int(10) unsigned NOT NULL,
    PRIMARY KEY (`rate`, `currency`),
    FOREIGN KEY (currency) REFERENCES payment_currency(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

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

CREATE TABLE IF NOT EXISTS `payment_discount_cupon` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `slug` varchar(20) NOT NULL DEFAULT '',
    `discount` int(10) unsigned DEFAULT NULL,
    `activated` tinyint(1) NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `payment_transaction` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `user_id` int(10) unsigned NOT NULL,
    `date` date NOT NULL,
    `paid` tinyint(1) NOT NULL,
    `currency` int(10) unsigned NOT NULL,
    `payment_type` int(10) unsigned DEFAULT NULL,
    `discount_cupon` int(10) unsigned DEFAULT NULL,
    `total_amount` float unsigned DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `paid` (`paid`),
    FOREIGN KEY (user_id) REFERENCES user(user_id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    FOREIGN KEY (currency) REFERENCES payment_currency(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    FOREIGN KEY (payment_type) REFERENCES payment_type(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    FOREIGN KEY (discount_cupon) REFERENCES payment_discount_cupon(id)
        ON UPDATE CASCADE
        ON DELETE SET NULL    
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `payment_transaction_item` (
    `transaction_id` int(10) unsigned NOT NULL,
    `object_id` int(10) unsigned NOT NULL,
    `module` int(10) unsigned NOT NULL,
    `title` varchar(100) NOT NULL,
    `amount` float unsigned DEFAULT NULL,
    `discount` float unsigned DEFAULT NULL,
    `count` int(10) unsigned DEFAULT NULL,
    PRIMARY KEY (`object_id`, `module`, `transaction_id`),
    FOREIGN KEY (transaction_id) REFERENCES payment_transaction(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    FOREIGN KEY (module) REFERENCES payment_module(module)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;