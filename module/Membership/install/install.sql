
INSERT INTO `module` (`name`, `type`, `active`, `version`, `vendor`, `vendor_email`, `description`, `dependences`) VALUES
('Membership', 'custom', 1, '1.0.0', 'eSASe', 'alexermashev@gmail.com', '', 'Payment');

SET @moduleId = (SELECT LAST_INSERT_ID());
SET @maxOrder = (SELECT `order` + 1 FROM `admin_menu` ORDER BY `order` DESC LIMIT 1);

INSERT INTO `admin_menu_category` (`name`, `module`, `icon`) VALUES
('Membership Levels', @moduleId, 'membership_menu_item.png');

SET @menuCategoryId = (SELECT LAST_INSERT_ID());
SET @menuPartId = (SELECT `id` from `admin_menu_part` where `name` = 'Modules');

INSERT INTO `admin_menu` (`name`, `controller`, `action`, `module`, `order`, `category`, `part`) VALUES
('List of membership levels', 'memberships-administration', 'list', @moduleId, @maxOrder, @menuCategoryId, @menuPartId),
('Settings', 'memberships-administration', 'settings', @moduleId, @maxOrder + 1, @menuCategoryId, @menuPartId);

INSERT INTO `acl_resource` (`resource`, `description`, `module`) VALUES
('memberships_administration_list', 'ACL - Viewing list of membership levels  in admin area', @moduleId),
('memberships_administration_add_role', 'ACL - Adding membership roles in admin area', @moduleId),
('memberships_administration_edit_role', 'ACL - Editing membership roles in admin area', @moduleId),
('memberships_administration_settings', 'ACL - Editing membership settings in admin area', @moduleId),
('memberships_administration_delete_roles', 'ACL - Deleting membership roles in admin area', @moduleId);

INSERT INTO `event` (`name`, `module`, `description`) VALUES
('add_membership_role', @moduleId, 'Event - Adding membership roles'),
('edit_membership_role', @moduleId, 'Event - Editing membership roles'),
('delete_membership_role', @moduleId, 'Event - Deleting membership roles'),
('delete_membership_conection', @moduleId, 'Event - Deleting membership connections'),
('activate_membership_conection', @moduleId, 'Event - Activating membership connections');

INSERT INTO `setting` (`name`, `label`, `description`, `type`, `required`, `order`, `category`, `module`, `language_sensitive`, `values_provider`, `check`, `check_message`) VALUES
('membership_image_width', 'Image width', '', 'integer', 1, 1, 1, @moduleId, 0, '', '', '');

SET @settingId = (SELECT LAST_INSERT_ID());
INSERT INTO `setting_value` (`setting_id`, `value`, `language`) VALUES
(@settingId,  '200', NULL);

INSERT INTO `setting` (`name`, `label`, `description`, `type`, `required`, `order`, `category`, `module`, `language_sensitive`, `values_provider`, `check`, `check_message`) VALUES
('membership_image_height', 'Image height', '', 'integer', 1, 2, 1, @moduleId, 0, '', '', '');

SET @settingId = (SELECT LAST_INSERT_ID());
INSERT INTO `setting_value` (`setting_id`, `value`, `language`) VALUES
(@settingId,  '200', NULL);

INSERT INTO `setting_category` (`name`, `module`) VALUES
('Email notifications', @moduleId);

SET @settingCategoryId = (SELECT LAST_INSERT_ID());

INSERT INTO `setting` (`name`, `label`, `description`, `type`, `required`, `order`, `category`, `module`, `language_sensitive`, `values_provider`, `check`, `check_message`) VALUES
('membership_expiring_send', 'Send notification about expiring membership levels', '', 'checkbox', 0, 3, @settingCategoryId, @moduleId, 0, '', '', '');
SET @settingId = (SELECT LAST_INSERT_ID());

INSERT INTO `setting_value` (`setting_id`, `value`, `language`) VALUES
(@settingId,  '1', NULL);

INSERT INTO `setting` (`name`, `label`, `description`, `type`, `required`, `order`, `category`, `module`, `language_sensitive`, `values_provider`, `check`, `check_message`) VALUES
('membership_expiring_send_title', 'An expiring membership level title', 'Expiring membership level email notification', 'notification_title', 1, 4, @settingCategoryId, @moduleId, 1, '', '', '');
SET @settingId = (SELECT LAST_INSERT_ID());

INSERT INTO `setting_value` (`setting_id`, `value`, `language`) VALUES
(@settingId,  'Your selected membership level will expire soon', NULL),
(@settingId,  'Выбранный вами уровень членства скоро истекает', 'ru');

INSERT INTO `setting` (`name`, `label`, `description`, `type`, `required`, `order`, `category`, `module`, `language_sensitive`, `values_provider`, `check`, `check_message`) VALUES
('membership_expiring_send_message', 'An expiring membership level message', '', 'notification_message', 1, 5, @settingCategoryId, @moduleId, 1, '', '', '');
SET @settingId = (SELECT LAST_INSERT_ID());

INSERT INTO `setting_value` (`setting_id`, `value`, `language`) VALUES
(@settingId,  '<p>Dear <b>__RealName__</b> your membership level - "__Role__" will expire at __ExpireDate__, do not forget to renew it or buy a new one</p>', NULL),
(@settingId,  '<p>Уважаемый(я) <b>__RealName__</b> ваш уровень членства - "__Role__" истекает __ExpireDate__, не забудьте продлить его или купить новый</p>', 'ru');

CREATE TABLE IF NOT EXISTS `membership_level` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `role_id` int(10) unsigned NOT NULL,
    `cost` float unsigned NOT NULL DEFAULT 0,
    `lifetime` int(10) unsigned NOT NULL,
    `expiration_notification` int(10) unsigned NOT NULL,
    `description` text NOT NULL,
    `language` varchar(2) DEFAULT NULL,
    `image` varchar(100) NOT NULL,
    PRIMARY KEY (`id`),
    KEY `cost` (`cost`),
    KEY `lifetime` (`lifetime`),
    KEY `expiration_notification` (`expiration_notification`),
    KEY `role` (`role_id`),
    FOREIGN KEY (`language`) REFERENCES `localization`(`language`)
        ON UPDATE CASCADE
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `membership_level_connection` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `user_id` int(10) unsigned NOT NULL,
    `membership_id` int(10) unsigned NOT NULL,
    `active` tinyint(1) unsigned NOT NULL,
    `expire_date` int(10) unsigned NOT NULL,
    `notify_date` int(10) unsigned NOT NULL,
    `notified` tinyint(1) NOT NULL,
    `expire_value` int(10) unsigned NOT NULL,
    `notify_value` int(10) unsigned NOT NULL,
    PRIMARY KEY (`id`),
    KEY `expire_date` (`active`, `expire_date`),
    KEY `notify_date` (`active`, `notify_date`, `notified`),
    FOREIGN KEY (`user_id`) REFERENCES `user`(`user_id`)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    FOREIGN KEY (`membership_id`) REFERENCES `membership_level`(`id`)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;