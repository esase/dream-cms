
INSERT INTO `module` (`name`, `type`, `active`, `version`, `vendor`, `vendor_email`, `description`, `dependences`) VALUES
('Payment', 'custom', 1, '0.9.0', 'eSASe', 'alexermashev@gmail.com', '', '');

SET @moduleId = (SELECT LAST_INSERT_ID());
SET @maxOrder = (SELECT `order` + 1 FROM `admin_menu` ORDER BY `order` DESC LIMIT 1);

INSERT INTO `admin_menu_category` (`name`, `module`) VALUES
('Payments', @moduleId);

SET @menuCategoryId = (SELECT LAST_INSERT_ID());

INSERT INTO `admin_menu` (`name`, `controller`, `action`, `module`, `order`, `category`) VALUES
('List of transactions', 'payments-administration', 'list', @moduleId, @maxOrder, @menuCategoryId),
('Currencies', 'payments-administration', 'currencies', @moduleId, @maxOrder + 1, @menuCategoryId),
('Discount coupons', 'payments-administration', 'coupons', @moduleId, @maxOrder + 2, @menuCategoryId),
('Settings', 'payments-administration', 'settings', @moduleId, @maxOrder + 3, @menuCategoryId);

INSERT INTO `acl_resource` (`resource`, `description`, `module`) VALUES
('payments_administration_list', 'ACL - Viewing payment transactions in admin area', @moduleId),
('payments_administration_currencies', 'ACL - Viewing payment currencies in admin area', @moduleId),
('payments_administration_add_currency', 'ACL - Adding payment currencies in admin area', @moduleId),
('payments_administration_edit_currency', 'ACL - Editing payment currencies in admin area', @moduleId),
('payments_administration_delete_currencies', 'ACL - Deleting payment currencies in admin area', @moduleId),
('payments_administration_edit_exchange_rates', 'ACL - Editing exchange rates in admin area', @moduleId),
('payments_administration_coupons', 'ACL - Viewing discount coupons in admin area', @moduleId),
('payments_administration_delete_coupons', 'ACL - Deleting discount coupons in admin area', @moduleId),
('payments_administration_add_coupon', 'ACL - Adding discount coupons in admin area', @moduleId),
('payments_administration_edit_coupon', 'ACL - Editing discount coupons in admin area', @moduleId),
('payments_administration_settings', 'ACL - Editing payments settings in admin area', @moduleId);

INSERT INTO `event` (`name`, `module`, `description`) VALUES
('add_payment_currency', @moduleId, 'Event - Adding payment currencies'),
('edit_payment_currency', @moduleId, 'Event - Editing payment currencies'),
('delete_payment_currency', @moduleId, 'Event - Deleting payment currencies'),
('edit_exchange_rates', @moduleId, 'Event - Editing exchange rates'),
('delete_discount_coupon', @moduleId, 'Event - Deleting discount coupons'),
('add_discount_coupon', @moduleId, 'Event - Adding discount coupons'),
('edit_discount_coupon', @moduleId, 'Event - Editing discount coupons'),
('add_item_to_shopping_cart', @moduleId, 'Event - Adding items to the shopping cart');

SET @maxOrder = IFNULL((SELECT `order` + 1 FROM `injection` where `position` = 'head' ORDER BY `order` DESC LIMIT 1), 1);
INSERT INTO `injection` (`position`, `patrial`, `module`, `order`) VALUES
('head', 'payment/patrial/shopping_cart_init', @moduleId, @maxOrder);

SET @maxOrder = IFNULL((SELECT `order` + 1 FROM `injection` where `position` = 'body' ORDER BY `order` DESC LIMIT 1), 1);
INSERT INTO `injection` (`position`, `patrial`, `module`, `order`) VALUES
('body', 'payment/patrial/shopping_cart', @moduleId, @maxOrder);

INSERT INTO `setting` (`name`, `label`, `description`, `type`, `required`, `order`, `category`, `module`, `language_sensitive`, `values_provider`, `check`, `check_message`) VALUES
('payment_shopping_cart_session_time', 'The shopping cart\'s ID lifetime in seconds', '', 'integer', 1, 1, 1, @moduleId, 0, '', 'return intval(''__value__'') > 0;', 'Value should be greater than 0');

SET @settingId = (SELECT LAST_INSERT_ID());
INSERT INTO `setting_value` (`setting_id`, `value`, `language`) VALUES
(@settingId,  '7776000', NULL);

INSERT INTO `setting` (`name`, `label`, `description`, `type`, `required`, `order`, `category`, `module`, `language_sensitive`, `values_provider`, `check`, `check_message`) VALUES
('payment_clearing_time', 'Time of clearing shopping cart and not paid transactions in seconds', '', 'integer', 1, 2, 1, @moduleId, 0, '', 'return intval(''__value__'') > 0;', 'Value should be greater than 0');

SET @settingId = (SELECT LAST_INSERT_ID());
INSERT INTO `setting_value` (`setting_id`, `value`, `language`) VALUES
(@settingId,  '432000', NULL);

CREATE TABLE IF NOT EXISTS `payment_module` (
    `module` int(10) unsigned NOT NULL,
    `update_event` varchar(50) NOT NULL,
    `delete_event` varchar(50) NOT NULL,
    `view_controller` varchar(50) NOT NULL,
    `view_action` varchar(50) NOT NULL,
    `countable` tinyint(1) NOT NULL,
    `must_login` tinyint(1) unsigned NOT NULL,
    `handler` varchar(100) NOT NULL,
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
(1, 'RUR', 'Rubles', 1),
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
    `slug` varchar(50) NOT NULL DEFAULT '',
    `discount` float unsigned NOT NULL DEFAULT 0,
    `activated` tinyint(1) NOT NULL,
    `date_start` int(10) unsigned NOT NULL,
    `date_end` int(10) unsigned NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `slug` (`slug`),
    KEY `discount` (`discount`),
    KEY `activated` (`activated`),
    KEY `date_start` (`date_start`),
    KEY `date_end` (`date_end`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `payment_transaction` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `user_id` int(10) unsigned DEFAULT NULL,
    `user_name` varchar(50) NOT NULL DEFAULT '',
    `user_phone` varchar(255) NOT NULL DEFAULT '',
    `user_email` varchar(255) NOT NULL DEFAULT '',
    `date` date NOT NULL,
    `paid` tinyint(1) NOT NULL,
    `currency` int(10) unsigned NOT NULL,
    `payment_type` int(10) unsigned DEFAULT NULL,
    `discount_cupon` int(10) unsigned DEFAULT NULL,
    `clear_date` int(10) unsigned NOT NULL,
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
    `slug` varchar(100) NOT NULL,
    `cost` float unsigned NOT NULL DEFAULT 0,
    `discount` float unsigned NOT NULL DEFAULT 0,
    `count` int(10) unsigned NOT NULL DEFAULT 0,
    `deleted` tinyint(1) NOT NULL DEFAULT 0,
    `available` tinyint(1) NOT NULL DEFAULT 1,
    `count_limit` tinyint(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (`object_id`, `module`, `transaction_id`),
    FOREIGN KEY (transaction_id) REFERENCES payment_transaction(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    FOREIGN KEY (module) REFERENCES payment_module(module)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `payment_shopping_cart` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `object_id` int(10) unsigned NOT NULL,
    `module` int(10) unsigned NOT NULL,
    `title` varchar(100) NOT NULL,
    `slug` varchar(100) NOT NULL,
    `cost` float unsigned NOT NULL DEFAULT 0,
    `discount` float unsigned NOT NULL DEFAULT 0,
    `count` int(10) unsigned NOT NULL DEFAULT 0,
    `shopping_cart_id` varchar(32) NOT NULL,
    `available` tinyint(1) NOT NULL DEFAULT 1,
    `count_limit` tinyint(1) NOT NULL DEFAULT 0,
    `clear_date` int(10) unsigned NOT NULL,
    `deleted` tinyint(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY (`object_id`, `module`, `shopping_cart_id`),
    KEY `available` (`available`,`count_limit`,`deleted`,`shopping_cart_id`),
    FOREIGN KEY (module) REFERENCES payment_module(module)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/* TEST DATA */
INSERT INTO `module` (`name`, `type`, `active`, `version`, `vendor`, `vendor_email`, `description`, `dependences`) VALUES
('Membership', 'custom', 1, '0.9.0', 'eSASe', 'alexermashev@gmail.com', '', '');

SET @moduleId = (SELECT LAST_INSERT_ID());

INSERT INTO `payment_module` (`update_event`, `delete_event`, `countable`, `must_login`, `module`, `handler`) VALUES
('membership_edit_event', 'membership_delete_event', 0, 1, @moduleId, 'Example\\Payment\\MembershipHandler');

INSERT INTO `module` (`name`, `type`, `active`, `version`, `vendor`, `vendor_email`, `description`, `dependences`) VALUES
('Shop', 'custom', 1, '0.9.0', 'eSASe', 'alexermashev@gmail.com', '', '');

SET @moduleId = (SELECT LAST_INSERT_ID());

INSERT INTO `payment_module` (`update_event`, `delete_event`, `countable`, `must_login`, `module`, `handler`) VALUES
('shop_edit_event', 'shop_delete_event', 1, 0, @moduleId, 'Example\\Payment\\ShopHandler');
