
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
('memberships_administration_settings', 'ACL - Editing membership settings in admin area', @moduleId);

INSERT INTO `event` (`name`, `module`, `description`) VALUES
('add_membership_role', @moduleId, 'Event - Adding membership roles'),
('edit_membership_role', @moduleId, 'Event - Editing membership roles'),
('delete_membership_role', @moduleId, 'Event - Deleting membership roles');

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

CREATE TABLE IF NOT EXISTS `membership_level` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `role_id` int(10) unsigned NOT NULL,
    `cost` float unsigned NOT NULL DEFAULT 0,
    `lifetime` int(10) unsigned NOT NULL,
    `description` text NOT NULL,
    `language` varchar(2) DEFAULT NULL,
    `image` varchar(100) NOT NULL,
    PRIMARY KEY (`id`),
    KEY `cost` (`cost`),
    KEY `lifetime` (`lifetime`),
    KEY `role` (`role_id`),
    FOREIGN KEY (language) REFERENCES localization(language)
        ON UPDATE CASCADE
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;