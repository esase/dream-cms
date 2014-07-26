
INSERT INTO `application_module` (`name`, `type`, `status`, `version`, `vendor`, `vendor_email`, `description`, `dependences`) VALUES
('Payment', 'custom', 'active', '0.9.0', 'eSASe', 'alexermashev@gmail.com', '', '');

SET @moduleId = (SELECT LAST_INSERT_ID());
SET @maxOrder = (SELECT `order` + 1 FROM `application_admin_menu` ORDER BY `order` DESC LIMIT 1);

INSERT INTO `application_admin_menu_category` (`name`, `module`, `icon`) VALUES
('Payments', @moduleId, 'payment_menu_item.png');

SET @menuCategoryId = (SELECT LAST_INSERT_ID());
SET @menuPartId = (SELECT `id` from `application_admin_menu_part` where `name` = 'Modules');

INSERT INTO `application_admin_menu` (`name`, `controller`, `action`, `module`, `order`, `category`, `part`) VALUES
('List of transactions', 'payments-administration', 'list', @moduleId, @maxOrder, @menuCategoryId, @menuPartId),
('Currencies', 'payments-administration', 'currencies', @moduleId, @maxOrder + 1, @menuCategoryId, @menuPartId),
('Discount coupons', 'payments-administration', 'coupons', @moduleId, @maxOrder + 2, @menuCategoryId, @menuPartId),
('Settings', 'payments-administration', 'settings', @moduleId, @maxOrder + 3, @menuCategoryId, @menuPartId);

SET @maxOrder = IFNULL((SELECT `order` + 1 FROM `user_menu` ORDER BY `order` DESC LIMIT 1), 1);
INSERT INTO `user_menu` (`name`, `controller`, `action`, `module`, `order`, `check`) VALUES
('List of transactions', 'payments', 'list', @moduleId, @maxOrder, '');

INSERT INTO `application_acl_resource` (`resource`, `description`, `module`) VALUES
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
('payments_administration_settings', 'ACL - Editing payments settings in admin area', @moduleId),
('payments_administration_view_transaction_details', 'ACL - Viewing payments transactions details in admin area', @moduleId),
('payments_administration_view_transaction_items', 'ACL - Viewing payments transactions items in admin area', @moduleId),
('payments_administration_delete_transactions', 'ACL - Deleting payments transactions in admin area', @moduleId),
('payments_administration_activate_transactions', 'ACL - Activating payments transactions in admin area', @moduleId);

INSERT INTO `application_event` (`name`, `module`, `description`) VALUES
('add_payment_currency', @moduleId, 'Event - Adding payment currencies'),
('edit_payment_currency', @moduleId, 'Event - Editing payment currencies'),
('delete_payment_currency', @moduleId, 'Event - Deleting payment currencies'),
('edit_exchange_rates', @moduleId, 'Event - Editing exchange rates'),
('delete_discount_coupon', @moduleId, 'Event - Deleting discount coupons'),
('add_discount_coupon', @moduleId, 'Event - Adding discount coupons'),
('edit_discount_coupon', @moduleId, 'Event - Editing discount coupons'),
('activate_discount_coupon', @moduleId, 'Event - Activating discount coupons'),
('deactivate_discount_coupon', @moduleId, 'Event - Deactivating discount coupons'),
('add_item_to_shopping_cart', @moduleId, 'Event - Adding items to the shopping cart'),
('delete_item_from_shopping_cart', @moduleId, 'Event - Deleting items from the shopping cart'),
('edit_item_into_shopping_cart', @moduleId, 'Event - Editing items into the shopping cart'),
('add_payment_transaction', @moduleId, 'Event - Adding payment transactions'),
('delete_payment_transaction', @moduleId, 'Event - Deleting payment transactions'),
('activate_payment_transaction', @moduleId, 'Event - Activating payment transactions'),
('mark_deleted_payment_items', @moduleId, 'Event - Marking as deleted shopping cart and transactions items'),
('edit_payment_items', @moduleId, 'Event - Editing shopping cart and transactions items'),
('hide_payment_transaction', @moduleId, 'Event - Hiding payment transactions');

SET @maxOrder = IFNULL((SELECT `order` + 1 FROM `application_injection` where `position` = 'head' ORDER BY `order` DESC LIMIT 1), 1);
INSERT INTO `application_injection` (`position`, `patrial`, `module`, `order`) VALUES
('head', 'payment/patrial/shopping-cart-init', @moduleId, @maxOrder);

SET @maxOrder = IFNULL((SELECT `order` + 1 FROM `application_injection` where `position` = 'body' ORDER BY `order` DESC LIMIT 1), 1);
INSERT INTO `application_injection` (`position`, `patrial`, `module`, `order`) VALUES
('before-menu', 'payment/patrial/shopping-cart', @moduleId, @maxOrder);

INSERT INTO `application_setting` (`name`, `label`, `description`, `type`, `required`, `order`, `category`, `module`, `language_sensitive`, `values_provider`, `check`, `check_message`) VALUES
('payment_shopping_cart_session_time', 'The shopping cart\'s ID lifetime in seconds', '', 'integer', 1, 1, 1, @moduleId, 0, '', 'return intval(''__value__'') > 0;', 'Value should be greater than 0');

SET @settingId = (SELECT LAST_INSERT_ID());
INSERT INTO `application_setting_value` (`setting_id`, `value`, `language`) VALUES
(@settingId,  '7776000', NULL);

INSERT INTO `application_setting` (`name`, `label`, `description`, `type`, `required`, `order`, `category`, `module`, `language_sensitive`, `values_provider`, `check`, `check_message`) VALUES
('payment_clearing_time', 'Time of clearing shopping cart and not paid transactions in seconds', '', 'integer', 1, 2, 1, @moduleId, 0, '', 'return intval(''__value__'') > 0;', 'Value should be greater than 0');

SET @settingId = (SELECT LAST_INSERT_ID());
INSERT INTO `application_setting_value` (`setting_id`, `value`, `language`) VALUES
(@settingId,  '432000', NULL);

INSERT INTO `application_setting` (`name`, `label`, `description`, `type`, `required`, `order`, `category`, `module`, `language_sensitive`, `values_provider`, `check`, `check_message`) VALUES
('payment_type_rounding', 'Type rounding of prices', '', 'select', 1, 3, 1, @moduleId, 0, '', '', '');

SET @settingId = (SELECT LAST_INSERT_ID());
INSERT INTO `application_setting_predefined_value` (`setting_id`, `value`) VALUES
(@settingId,  'type_round'),
(@settingId,  'type_ceil'),
(@settingId,  'type_floor'),
(@settingId,  'type_none');

INSERT INTO `application_setting_value` (`setting_id`, `value`, `language`) VALUES
(@settingId,  'type_round', NULL);

INSERT INTO `application_setting_category` (`name`, `module`) VALUES
('Email notifications', @moduleId);

SET @settingCategoryId = (SELECT LAST_INSERT_ID());

INSERT INTO `application_setting` (`name`, `label`, `description`, `type`, `required`, `order`, `category`, `module`, `language_sensitive`, `values_provider`, `check`, `check_message`) VALUES
('payment_transaction_add', 'Send notification about new payment transactions', '', 'checkbox', 0, 4, @settingCategoryId, @moduleId, 0, '', '', '');
SET @settingId = (SELECT LAST_INSERT_ID());

INSERT INTO `application_setting_value` (`setting_id`, `value`, `language`) VALUES
(@settingId,  '1', NULL);

INSERT INTO `application_setting` (`name`, `label`, `description`, `type`, `required`, `order`, `category`, `module`, `language_sensitive`, `values_provider`, `check`, `check_message`) VALUES
('payment_transaction_add_title', 'Add a new payment transaction title', 'Add a payment transaction email notification', 'notification_title', 1, 5, @settingCategoryId, @moduleId, 1, '', '', '');
SET @settingId = (SELECT LAST_INSERT_ID());

INSERT INTO `application_setting_value` (`setting_id`, `value`, `language`) VALUES
(@settingId,  'A new payment transaction has been added', NULL),
(@settingId,  'Добавлена новая платежная операция', 'ru');

INSERT INTO `application_setting` (`name`, `label`, `description`, `type`, `required`, `order`, `category`, `module`, `language_sensitive`, `values_provider`, `check`, `check_message`) VALUES
('payment_transaction_add_message', 'Add a new payment transaction message', '', 'notification_message', 1, 6, @settingCategoryId, @moduleId, 1, '', '', '');
SET @settingId = (SELECT LAST_INSERT_ID());

INSERT INTO `application_setting_value` (`setting_id`, `value`, `language`) VALUES
(@settingId,  '<p><b>__FirstName__ __LastName__ (__Email__)</b> has added a new payment transaction with id: <b>__Id__</b></p>', NULL),
(@settingId,  '<p><b>__LastName__  __FirstName__ (__Email__)</b> добавил(а) новую платежную операцию с идентификатором: <b>__Id__</b></p>', 'ru');

INSERT INTO `application_setting` (`name`, `label`, `description`, `type`, `required`, `order`, `category`, `module`, `language_sensitive`, `values_provider`, `check`, `check_message`) VALUES
('payment_transaction_paid', 'Send notification about paid payment transactions', '', 'checkbox', 0, 7, @settingCategoryId, @moduleId, 0, '', '', '');
SET @settingId = (SELECT LAST_INSERT_ID());

INSERT INTO `application_setting_value` (`setting_id`, `value`, `language`) VALUES
(@settingId,  '1', NULL);

INSERT INTO `application_setting` (`name`, `label`, `description`, `type`, `required`, `order`, `category`, `module`, `language_sensitive`, `values_provider`, `check`, `check_message`) VALUES
('payment_transaction_paid_title', 'Paid payment transaction title', 'Paid payment transaction email notification', 'notification_title', 1, 8, @settingCategoryId, @moduleId, 1, '', '', '');
SET @settingId = (SELECT LAST_INSERT_ID());

INSERT INTO `application_setting_value` (`setting_id`, `value`, `language`) VALUES
(@settingId,  'A payment transaction has been paid', NULL),
(@settingId,  'Платежная операция оплачена', 'ru');

INSERT INTO `application_setting` (`name`, `label`, `description`, `type`, `required`, `order`, `category`, `module`, `language_sensitive`, `values_provider`, `check`, `check_message`) VALUES
('payment_transaction_paid_message', 'Paid payment transaction message', '', 'notification_message', 1, 9, @settingCategoryId, @moduleId, 1, '', '', '');
SET @settingId = (SELECT LAST_INSERT_ID());

INSERT INTO `application_setting_value` (`setting_id`, `value`, `language`) VALUES
(@settingId,  '<p><b>__FirstName__ __LastName__ (__Email__)</b> has paid the payment transaction with id: <b>__Id__</b></p>', NULL),
(@settingId,  '<p><b>__LastName__  __FirstName__ (__Email__)</b> оплатил(а) платежную операцию с идентификатором: <b>__Id__</b></p>', 'ru');


INSERT INTO `application_setting` (`name`, `label`, `description`, `type`, `required`, `order`, `category`, `module`, `language_sensitive`, `values_provider`, `check`, `check_message`) VALUES
('payment_transaction_paid_users', 'Send notification about paid payment transactions to users', '', 'checkbox', 0, 10, @settingCategoryId, @moduleId, 0, '', '', '');
SET @settingId = (SELECT LAST_INSERT_ID());

INSERT INTO `application_setting_value` (`setting_id`, `value`, `language`) VALUES
(@settingId,  '1', NULL);

INSERT INTO `application_setting` (`name`, `label`, `description`, `type`, `required`, `order`, `category`, `module`, `language_sensitive`, `values_provider`, `check`, `check_message`) VALUES
('payment_transaction_paid_users_title', 'Paid users payment transactions title', 'Paid users payment transaction email notification', 'notification_title', 1, 11, @settingCategoryId, @moduleId, 1, '', '', '');
SET @settingId = (SELECT LAST_INSERT_ID());

INSERT INTO `application_setting_value` (`setting_id`, `value`, `language`) VALUES
(@settingId,  'Your payment transaction has been paid', NULL),
(@settingId,  'Ваша платежная операция была оплачена', 'ru');

INSERT INTO `application_setting` (`name`, `label`, `description`, `type`, `required`, `order`, `category`, `module`, `language_sensitive`, `values_provider`, `check`, `check_message`) VALUES
('payment_transaction_paid_users_message', 'Paid users payment transactions message', '', 'notification_message', 1, 12, @settingCategoryId, @moduleId, 1, '', '', '');
SET @settingId = (SELECT LAST_INSERT_ID());

INSERT INTO `application_setting_value` (`setting_id`, `value`, `language`) VALUES
(@settingId,  '<p>You have paid the payment transaction with id: <b>__Id__</b> via the <b>__PaymentType__</b></p>', NULL),
(@settingId,  '<p>Вы оплатили платежную операцию с идентификатором: <b>__Id__</b> через <b>__PaymentType__</b></p>', 'ru');

INSERT INTO `application_setting_category` (`name`, `module`) VALUES
('Payment transactions messages', @moduleId);

SET @settingCategoryId = (SELECT LAST_INSERT_ID());

INSERT INTO `application_setting` (`name`, `label`, `description`, `type`, `required`, `order`, `category`, `module`, `language_sensitive`, `values_provider`, `check`, `check_message`) VALUES
('payment_transaction_successful_message', 'Successful payment transaction\'s message', '', 'htmlarea', 1, 13, @settingCategoryId, @moduleId, 1, '', '', '');
SET @settingId = (SELECT LAST_INSERT_ID());

INSERT INTO `application_setting_value` (`setting_id`, `value`, `language`) VALUES
(@settingId,  '<p>Your payment transaction has been processed successfully!</p>', NULL),
(@settingId,  '<p>Ваша платежная операция была успешно обработана!</p>', 'ru');

INSERT INTO `application_setting` (`name`, `label`, `description`, `type`, `required`, `order`, `category`, `module`, `language_sensitive`, `values_provider`, `check`, `check_message`) VALUES
('payment_transaction_unsuccessful_message', 'Unsuccessful payment transaction\'s message', '', 'htmlarea', 1, 14, @settingCategoryId, @moduleId, 1, '', '', '');
SET @settingId = (SELECT LAST_INSERT_ID());

INSERT INTO `application_setting_value` (`setting_id`, `value`, `language`) VALUES
(@settingId,  '<p>Your payment transaction has not  been processed successfully. If you have any questions contact with our support please.</p>', NULL),
(@settingId,  '<p>Ваша платежная операция не была успешно обработана. Если у вас возникли вопросы, свяжитесь с нашей поддержкой пожалуйста.</p>', 'ru');

INSERT INTO `application_setting_category` (`name`, `module`) VALUES
('Cash', @moduleId);

SET @settingCategoryId = (SELECT LAST_INSERT_ID());

INSERT INTO `application_setting` (`name`, `label`, `description`, `type`, `required`, `order`, `category`, `module`, `language_sensitive`, `values_provider`, `check`, `check_message`) VALUES
('payment_cash_enable', 'Enable cash', '', 'checkbox', 0, 15, @settingCategoryId, @moduleId, 0, '', '', '');
SET @settingId = (SELECT LAST_INSERT_ID());

INSERT INTO `application_setting_value` (`setting_id`, `value`, `language`) VALUES
(@settingId,  '1', NULL);

INSERT INTO `application_setting` (`name`, `label`, `description`, `type`, `required`, `order`, `category`, `module`, `language_sensitive`, `values_provider`, `check`, `check_message`) VALUES
('payment_cash_description', 'Cash description', 'This description will be available on the payment page', 'htmlarea', 1, 16, @settingCategoryId, @moduleId, 1, '', '', '');
SET @settingId = (SELECT LAST_INSERT_ID());

INSERT INTO `application_setting_value` (`setting_id`, `value`, `language`) VALUES
(@settingId,  'Your description here (where and how users can buy selected items by cash)', NULL),
(@settingId,  'Ваше описание здесь (где и как пользователи могут купить выбранные товары наличными)', 'ru');

INSERT INTO `application_setting_category` (`name`, `module`) VALUES
('RBK Money', @moduleId);

SET @settingCategoryId = (SELECT LAST_INSERT_ID());

INSERT INTO `application_setting` (`name`, `label`, `description`, `type`, `required`, `order`, `category`, `module`, `language_sensitive`, `values_provider`, `check`, `check_message`) VALUES
('payment_rbk_money_enable', 'Enable RBK Money', '', 'checkbox', 0, 17, @settingCategoryId, @moduleId, 0, '', '', '');
SET @settingId = (SELECT LAST_INSERT_ID());

INSERT INTO `application_setting_value` (`setting_id`, `value`, `language`) VALUES
(@settingId,  '0', NULL);

INSERT INTO `application_setting` (`name`, `label`, `description`, `type`, `required`, `order`, `category`, `module`, `language_sensitive`, `values_provider`, `check`, `check_message`) VALUES
('payment_rbk_money_description', 'RBK Money description', 'This description will be available on the payment page', 'htmlarea', 1, 18, @settingCategoryId, @moduleId, 1, '', '', '');
SET @settingId = (SELECT LAST_INSERT_ID());

INSERT INTO `application_setting_value` (`setting_id`, `value`, `language`) VALUES
(@settingId,  'Your description here (where and how users can buy selected items by RBK Money)', NULL),
(@settingId,  'Ваше описание здесь (где и как пользователи могут купить выбранные товары с помощью RBK Money)', 'ru');

INSERT INTO `application_setting` (`name`, `label`, `description`, `type`, `required`, `order`, `category`, `module`, `language_sensitive`, `values_provider`, `check`, `check_message`) VALUES
('payment_rbk_money_title', 'RBK Money title', 'This title will be available on the RBK Money payment page', 'text', 1, 19, @settingCategoryId, @moduleId, 1, '', '', '');
SET @settingId = (SELECT LAST_INSERT_ID());

INSERT INTO `application_setting_value` (`setting_id`, `value`, `language`) VALUES
(@settingId,  'Pay for selected items and services', NULL),
(@settingId,  'Купить выбранные товары и услуги', 'ru');

INSERT INTO `application_setting` (`name`, `label`, `description_helper`, `type`, `required`, `order`, `category`, `module`, `language_sensitive`, `values_provider`, `check`, `check_message`) VALUES
('payment_rbk_eshop_id', 'Shop ID', '$serviceManager = Application\\Service\\Service::getServiceManager();\r\n$url = $serviceManager->get(''viewhelpermanager'')->get(''url'');\r\n$translate = $serviceManager->get(''viewhelpermanager'')->get(''translate'');\r\n\r\n$label  = $translate(''Set these links into your RBK Money account:'');\r\n$label .= ''<br />'';\r\n$label .= $translate(''Success URL'') . '': '' . $url(''application'', array(''controller'' => ''payments'', ''action'' => ''success''), array(''force_canonical'' => true));\r\n$label .= ''<br />'';\r\n$label .= $translate(''Fail URL'') . '': '' . $url(''application'', array(''controller'' => ''payments'', ''action'' => ''error''), array(''force_canonical'' => true));\r\n$label .= ''<br />'';\r\n$label .= $translate(''Callback URL'') . '': '' . $url(''application'', array(''controller'' => ''payments'', ''action'' => ''process'', ''slug'' => ''rbk-money''), array(''force_canonical'' => true));\r\n$label .= ''<br />'';\r\n$label .= ''<br />'';\r\n$label .= $translate(''Also set these options into your RBK Money account:'');\r\n$label .= ''<br />'';\r\n$label .= $translate(''HTTP method'') . '': POST'';\r\n$label .= ''<br />'';\r\n$label .= $translate(''Control signature'') . '': MD5'';\r\n\r\nreturn $label;', 'text', 1, 20, @settingCategoryId, @moduleId, 0, '', '', '');
SET @settingId = (SELECT LAST_INSERT_ID());

INSERT INTO `application_setting_value` (`setting_id`, `value`, `language`) VALUES
(@settingId,  'xxxx', NULL);

INSERT INTO `application_setting` (`name`, `label`, `description`, `type`, `required`, `order`, `category`, `module`, `language_sensitive`, `values_provider`, `check`, `check_message`) VALUES
('payment_rbk_account', 'Account ID', '', 'text', 1, 21, @settingCategoryId, @moduleId, 0, '', '', '');
SET @settingId = (SELECT LAST_INSERT_ID());

INSERT INTO `application_setting_value` (`setting_id`, `value`, `language`) VALUES
(@settingId,  'RUxxxx', NULL);

INSERT INTO `application_setting` (`name`, `label`, `description`, `type`, `required`, `order`, `category`, `module`, `language_sensitive`, `values_provider`, `check`, `check_message`) VALUES
('payment_rbk_secret', 'Secret key', '', 'text', 1, 22, @settingCategoryId, @moduleId, 0, '', '', '');
SET @settingId = (SELECT LAST_INSERT_ID());

INSERT INTO `application_setting_value` (`setting_id`, `value`, `language`) VALUES
(@settingId,  'xxxx', NULL);

CREATE TABLE IF NOT EXISTS `payment_module` (
    `module` SMALLINT(5) UNSIGNED NOT NULL,
    `update_event` VARCHAR(50) NOT NULL,
    `delete_event` VARCHAR(50) NOT NULL,
    `view_controller` VARCHAR(50) NOT NULL,
    `view_action` VARCHAR(50) NOT NULL,
    `view_check` VARCHAR(255) NOT NULL,
    `countable` TINYINT(1) NOT NULL,
    `multi_costs` TINYINT(1) NOT NULL,
    `extra_options` TINYINT(1) NOT NULL,
    `must_login` TINYINT(1) UNSIGNED NOT NULL,
    `handler` VARCHAR(100) NOT NULL,
    PRIMARY KEY (`module`),
    FOREIGN KEY (`module`) REFERENCES `application_module`(`id`)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `payment_currency` (
    `id` TINYINT(3) UNSIGNED NOT NULL AUTO_INCREMENT,
    `code` CHAR(3) NOT NULL,
    `name` VARCHAR(50) NOT NULL,
    `primary_currency` TINYINT(1) NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `code` (`code`),
    KEY `primary_currency` (`primary_currency`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

INSERT INTO `payment_currency` (`id`, `code`, `name`, `primary_currency`) VALUES
(1, 'RUR', 'Rubles', 1),
(2, 'USD', 'Dollars USA', 0),
(3, 'EUR', 'Euro', 0);

CREATE TABLE IF NOT EXISTS `payment_exchange_rate` (
    `rate` DECIMAL(10,2) UNSIGNED NOT NULL,
    `currency` TINYINT(3) UNSIGNED NOT NULL,
    PRIMARY KEY (`rate`, `currency`),
    FOREIGN KEY (`currency`) REFERENCES `payment_currency`(`id`)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `payment_type` (
    `id` TINYINT(3) UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(30) NOT NULL,
    `description` VARCHAR(100) NOT NULL,
    `enable_option` VARCHAR(50) NOT NULL,
    `handler` VARCHAR(50) NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `payment_type` (`id`, `name`, `description`, `enable_option`, `handler`) VALUES
(1, 'cash', 'Cash', 'payment_cash_enable', 'Payment\\Type\\Cash'),
(2, 'rbk-money', 'RBK Money', 'payment_rbk_money_enable', 'Payment\\Type\\RBKMoney');

CREATE TABLE IF NOT EXISTS `payment_discount_cupon` (
    `id` SMALLINT(5) UNSIGNED NOT NULL AUTO_INCREMENT,
    `slug` VARCHAR(50) NOT NULL DEFAULT '',
    `discount` DECIMAL(10,2) UNSIGNED NOT NULL DEFAULT 0,
    `used` TINYINT(1) NOT NULL,
    `date_start` INT(10) UNSIGNED NOT NULL,
    `date_end` INT(10) UNSIGNED NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `slug` (`slug`),
    KEY `discount` (`discount`),
    KEY `used` (`used`),
    KEY `date_start` (`date_start`),
    KEY `date_end` (`date_end`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `payment_transaction` (
    `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `slug` VARCHAR(50) NOT NULL DEFAULT '',
    `user_id` INT(10) UNSIGNED DEFAULT NULL,
    `first_name` VARCHAR(100) NOT NULL DEFAULT '',
    `last_name` VARCHAR(100) NOT NULL DEFAULT '',
    `email` VARCHAR(50) NOT NULL DEFAULT '',
    `phone` VARCHAR(50) NOT NULL DEFAULT '',
    `address` VARCHAR(100) NOT NULL DEFAULT '',
    `date` INT(10) UNSIGNED NOT NULL,
    `paid` TINYINT(1) NOT NULL,
    `currency` TINYINT(3) UNSIGNED NOT NULL,
    `payment_type` TINYINT(3) UNSIGNED DEFAULT NULL,
    `comments` text NOT NULL DEFAULT '',
    `discount_cupon` SMALLINT(5) UNSIGNED DEFAULT NULL,
    `amount` DECIMAL(10,2) NOT NULL DEFAULT '0',
    `user_hidden` TINYINT(1) NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `slug` (`slug`),
    KEY `paid` (`paid`),
    KEY `email` (`email`),
    KEY `date` (`date`),
    KEY `user_hidden` (`user_id`, `user_hidden`),
    FOREIGN KEY (`user_id`) REFERENCES `user_list`(`user_id`)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    FOREIGN KEY (`currency`) REFERENCES `payment_currency`(`id`)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    FOREIGN KEY (`payment_type`) REFERENCES `payment_type`(`id`)
        ON UPDATE CASCADE
        ON DELETE SET NULL,
    FOREIGN KEY (`discount_cupon`) REFERENCES `payment_discount_cupon`(`id`)
        ON UPDATE CASCADE
        ON DELETE SET NULL    
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `payment_transaction_item` (
    `transaction_id` INT(10) UNSIGNED NOT NULL,
    `object_id` INT(10) UNSIGNED NOT NULL,
    `module` SMALLINT(5) UNSIGNED NOT NULL,
    `title` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(100) NOT NULL,
    `cost` DECIMAL(10,2) UNSIGNED NOT NULL DEFAULT 0,
    `discount` DECIMAL(10,2) UNSIGNED NOT NULL DEFAULT 0,
    `count` SMALLINT(5) UNSIGNED NOT NULL DEFAULT 0,
    `deleted` TINYINT(1) NOT NULL DEFAULT 0,
    `active` TINYINT(1) NOT NULL DEFAULT 1,
    `available` TINYINT(1) NOT NULL DEFAULT 1,
    `extra_options` text NOT NULL DEFAULT '',
    PRIMARY KEY (`object_id`, `module`, `transaction_id`),
    FOREIGN KEY (`transaction_id`) REFERENCES `payment_transaction`(`id`)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    FOREIGN KEY (`module`) REFERENCES `payment_module`(`module`)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `payment_shopping_cart` (
    `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `object_id` INT(10) UNSIGNED NOT NULL,
    `module` SMALLINT(5) UNSIGNED NOT NULL,
    `title` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(100) NOT NULL,
    `cost` DECIMAL(10,2) UNSIGNED NOT NULL DEFAULT 0,
    `discount` DECIMAL(10,2) UNSIGNED NOT NULL DEFAULT 0,
    `count` SMALLINT(5) UNSIGNED NOT NULL DEFAULT 0,
    `shopping_cart_id` CHAR(32) NOT NULL,
    `active` TINYINT(1) NOT NULL DEFAULT 1,
    `available` TINYINT(1) NOT NULL DEFAULT 1,
    `date` INT(10) UNSIGNED NOT NULL,
    `deleted` TINYINT(1) NOT NULL DEFAULT 0,
    `extra_options` text NOT NULL DEFAULT '',
    PRIMARY KEY (`id`),
    UNIQUE KEY (`object_id`, `module`, `shopping_cart_id`),
    KEY `available` (`active`,`available`,`deleted`,`shopping_cart_id`),
    KEY `date` (`date`),
    FOREIGN KEY (`module`) REFERENCES `payment_module`(`module`)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;