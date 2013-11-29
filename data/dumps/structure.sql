CREATE TABLE IF NOT EXISTS `modules` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `name` varchar(100) NOT NULL,
    `type` enum('system','custom') NOT NULL,
    `active` tinyint(1) unsigned NOT NULL,
    `version` varchar(10) NOT NULL,
    `vendor` varchar(100) NOT NULL,
    `vendor_email` varchar(100) NOT NULL,
    `description` text NOT NULL,
    `dependences` varchar(255) NOT NULL,
    PRIMARY KEY (`id`),
    KEY `type` (`type`, `active`),
    UNIQUE `name` (`name`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

INSERT INTO `modules` (`id`, `name`, `type`, `active`, `version`, `vendor`, `vendor_email`, `description`, `dependences`) VALUES
(1, 'Application', 'system', 1, '0.9', 'eSASe', 'alexermashev@gmail.com', 'Core module, make the first application initialization', ''),
(2, 'Users', 'system', 1, '0.9', 'eSASe', 'alexermashev@gmail.com', 'Allows to users logon and logoff ', ''),
(3, 'XmlRpc', 'system', 1, '0.9', 'eSASe', 'alexermashev@gmail.com', 'Allows to use web services via XmlRpc server', '');

CREATE TABLE IF NOT EXISTS `xmlrpc_classes` (
    `namespace` varchar(50) NOT NULL,
    `path` varchar(100) NOT NULL,
    `module` int(10) unsigned NOT NULL,
    PRIMARY KEY (`namespace`, `path`, `module`),
    FOREIGN KEY (module) REFERENCES modules(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `xmlrpc_classes` (`namespace`, `path`, `module`) VALUES
('application', 'Application\\XmlRpc\\Handler', 1),
('users', 'Users\\XmlRpc\\Handler', 2);

CREATE TABLE IF NOT EXISTS `localizations` (
    `language` varchar(2) NOT NULL,
    `locale` varchar(5) NOT NULL,
    `description` varchar(50) NOT NULL,
    `default` tinyint(1) unsigned NOT NULL,
    `direction` enum('rtl','ltr') NOT NULL,
    PRIMARY KEY (`language`),
    KEY `default` (`default`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `localizations` (`language`, `locale`, `description`, `default`, `direction`) VALUES
('en', 'en_US', 'English', 1, 'ltr'),
('ru', 'ru_RU', 'Русский', 0, 'ltr');

CREATE TABLE IF NOT EXISTS `layouts` (
    `name` varchar(50) NOT NULL,
    `type` enum('system','custom') NOT NULL,
    `active` tinyint(1) unsigned NOT NULL,
    `title` varchar(150) NOT NULL,
    `description` text NOT NULL,
    `version` varchar(100) NOT NULL,
    `vendor` varchar(100) NOT NULL,
    `vendor_email` varchar(1000) NOT NULL,
    PRIMARY KEY (`name`),
    KEY `type` (`type`, `active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `layouts` (`name`, `type`, `active`, `title`, `description`, `version`, `vendor`, `vendor_email`) VALUES
('base', 'system', 1, 'Base layout', 'Default base layout', '0.9', 'eSASe', 'alexermashev@gmail.com');

CREATE TABLE IF NOT EXISTS `acl_roles` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `name` varchar(50) NOT NULL,
    `type` enum('system','custom') NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

INSERT INTO `acl_roles` (`id`, `name`, `type`) VALUES
(1, 'admin', 'system'),
(2, 'guest', 'system'),
(3, 'member', 'system');

CREATE TABLE IF NOT EXISTS `acl_resources` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `resource` varchar(50) NOT NULL,
    `description` varchar(150) NOT NULL,
    `module` int(10) unsigned NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE(`resource`),
    FOREIGN KEY (module) REFERENCES modules(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

INSERT INTO `acl_resources` (`id`, `resource`, `description`, `module`) VALUES
(1, 'modules_administration_index', 'ACL - Possibility to view modules in administration panel', 1),
(2, 'settings_administration_index', 'ACL - Possibility to change site settings in administration panel', 1),
(3, 'localizations_administration_index', 'ACL - Possibility to view site localizations in administration panel', 1),
(4, 'layouts_administration_index', 'ACL - Possibility to view site layouts in administration panel', 1),
(5, 'acl_administration_index', 'ACL - Possibility to view site ACL in administration panel', 1),
(6, 'xmlrpc_get_localizations', 'ACL - Possibility to get site localizations via XmlRpc', 1),
(7, 'users_administration_index', 'ACL - Possibility to view users in administration panel', 2),
(8, 'xmlrpc_view_user_info', 'ACL - Possibility to view user\'s info via XmlRpc', 2),
(9, 'xmlrpc_set_user_timezone', 'ACL - Possibility to change user\'s timezone via XmlRpc', 2);

CREATE TABLE IF NOT EXISTS `acl_resources_connections` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `role` int(10) unsigned NOT NULL,
    `resource` int(10) unsigned NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY (`role`, `resource`),
    FOREIGN KEY (role) REFERENCES acl_roles(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    FOREIGN KEY (resource) REFERENCES acl_resources(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `users` (
    `user_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `nick_name` varchar(255) NOT NULL DEFAULT '',
    `email` varchar(255) NOT NULL DEFAULT '',
    `password` varchar(40) NOT NULL DEFAULT '',
    `salt` varchar(10) NOT NULL DEFAULT '',
    `role` int(10) unsigned NOT NULL,
    `language` varchar(2) DEFAULT NULL,
    `time_zone` varchar(100) NOT NULL,
    `layout` varchar(50) DEFAULT NULL,
    `api_key` varchar(50) NOT NULL DEFAULT '',
    `api_secret` varchar(50) NOT NULL DEFAULT '',
    PRIMARY KEY (`user_id`),
    UNIQUE KEY `nick_name` (`nick_name`),
    UNIQUE KEY `email` (`email`),
    UNIQUE KEY `api_key` (`api_key`),
    FOREIGN KEY (role) REFERENCES acl_roles(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    FOREIGN KEY (language) REFERENCES localizations(language)
        ON UPDATE CASCADE
        ON DELETE SET NULL,
    FOREIGN KEY (layout) REFERENCES layouts(name)
        ON UPDATE CASCADE
        ON DELETE SET NULL       
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

INSERT INTO `users` (`user_id`, `nick_name`, `email`, `password`, `salt`, `role`, `api_key`, `api_secret`) VALUES
(1, 'esase', 'alexermashev@gmail.com', 'a10487c11b57054ffefe4108f3657a13cdbf54cc', ',LtHh5Dz', 1, '123sAdsNms', 'Uyqqqx998');

CREATE TABLE IF NOT EXISTS `acl_resources_connections_settings` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `connection_id` int(10) unsigned NOT NULL,
    `user_id` int(10) unsigned DEFAULT NULL,
    `date_start` int(10) unsigned NOT NULL,
    `date_end` int(10) unsigned NOT NULL,
    `actions_limit` int(10) unsigned NOT NULL,
    `actions_reset` int(10) unsigned NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `setting` (`connection_id`, `user_id`),
    FOREIGN KEY (connection_id) REFERENCES acl_resources_connections(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
      ON UPDATE CASCADE
      ON DELETE CASCADE  
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `acl_resources_actions_track` (
    `connection_id` int(10) unsigned NOT NULL,
    `user_id` int(10) unsigned DEFAULT NULL,
    `actions` int(10) unsigned NOT NULL,
    `actions_last_reset` int(10) unsigned NOT NULL,
    PRIMARY KEY (`connection_id`, `user_id`),
    FOREIGN KEY (connection_id) REFERENCES acl_resources_connections(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
      ON UPDATE CASCADE
      ON DELETE CASCADE  
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `settings_categories` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL DEFAULT '',
    `module` int(10) unsigned NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `category` (`name`, `module`),
    FOREIGN KEY (module) REFERENCES modules(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `settings_categories` (`id`, `name`, `module`) VALUES
(1, 'Main settings', 1),
(2, 'Cache', 1),
(3, 'Captcha', 1),
(4, 'Calendar', 1),
(5, 'SEO', 1);

CREATE TABLE IF NOT EXISTS `settings` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `name` varchar(50) NOT NULL,
    `label` varchar(150) NOT NULL,
    `type` enum('text', 'integer', 'float', 'email', 'textarea', 'password', 'radio', 'select', 'multiselect', 'checkbox', 'multicheckbox', 'url', 'date', 'date_unixtime', 'htmlarea', 'system') NOT NULL,
    `required` tinyint(1) unsigned NOT NULL,
    `order` smallint(5) unsigned NOT NULL,
    `category` int(10) unsigned DEFAULT NULL,
    `module` int(10) unsigned NOT NULL,
    `language_sensitive` tinyint(1) NOT NULL DEFAULT '1',
    `values_provider` varchar(255) NOT NULL,
    `check` varchar(255) NOT NULL,
    `check_message` varchar(150) NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `name` (`name`),
    FOREIGN KEY (category) REFERENCES settings_categories(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    FOREIGN KEY (module) REFERENCES modules(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

INSERT INTO `settings` (`id`, `name`, `label`, `type`, `required`, `order`, `category`, `module`, `language_sensitive`, `values_provider`, `check`, `check_message`) VALUES
(1, 'application_generator', '', 'system', 0, 0, NULL, 1, 0, '', '', ''),
(2, 'application_generator_version', '', 'system', 0, 0, NULL, 1, 0, '', '', ''),
(3, 'application_site_name', 'Site name', 'text', 1, 1, 1, 1, 1, '', '', ''),
(4, 'application_site_email', 'Site email', 'email', 1, 1, 1, 1, 0, '', '', ''),
(5, 'application_meta_description', 'Meta description', 'text', 1, 3, 5, 1, 1, '', '', ''),
(6, 'application_meta_keywords', 'Meta keywords', 'text', 1, 4, 5, 1, 1, '', '', ''),
(7, 'application_js_cache', 'Enable js cache', 'checkbox', 0, 5, 2, 1, 0, '', '', ''),
(8, 'application_js_cache_gzip', 'Enable gzip for js cache', 'checkbox', 0, 6, 2, 1, 0, '', '', ''),
(9, 'application_css_cache', 'Enable css cache', 'checkbox', 0, 7, 2, 1, 0, '', '', ''),
(10, 'application_css_cache_gzip', 'Enable gzip for css cache', 'checkbox', 0, 8, 2, 1, 0, '', '', ''),
(11, 'application_captcha_width', 'Captcha width', 'integer', 1, 9, 3, 1, 0, '', '', ''),
(12, 'application_captcha_height', 'Captcha height', 'integer', 1, 10, 3, 1, 0, '', '', ''),
(13, 'application_captcha_dot_noise', 'Captcha dot noise level', 'integer', 1, 11, 3, 1, 0, '', '', ''),
(14, 'application_captcha_line_noise', 'Captcha line noise level', 'integer', 1, 12, 3, 1, 0, '', '', ''),
(15, 'application_calendar_min_year', 'Min year in calendar', 'integer', 1, 1, 4, 1, 0, '', 'return intval(''{value}'') >= 1902 and intval(''{value}'') <= 2037;', 'Year should be in range from 1902 to 2037'),
(16, 'application_calendar_max_year', 'Max year in calendar', 'integer', 1, 2, 4, 1, 0, '', 'return intval(''{value}'') >= 1902 and intval(''{value}'') <= 2037;', 'Year should be in range from 1902 to 2037'),
(17, 'application_default_date_format', 'Default date format', 'select', 1, 3, 1, 1, 1, '', '', ''),
(18, 'application_default_time_zone', 'Default time zone', 'select', 1, 4, 1, 1, 0, 'return array_combine(DateTimeZone::listIdentifiers(), DateTimeZone::listIdentifiers());', '', '');

CREATE TABLE IF NOT EXISTS `settings_values` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `setting_id` int(10) unsigned NOT NULL,
    `value` text NOT NULL,
    `language` varchar(2) DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `setting` (`setting_id`, `language`),
    FOREIGN KEY (setting_id) REFERENCES settings(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    FOREIGN KEY (language) REFERENCES localizations(language)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `settings_values` (`id`, `setting_id`, `value`, `language`) VALUES
(1,  1,  'Dream CMS', NULL),
(2,  2,  '0.9.0', NULL),
(3,  3,  'Dream CMS demo site', NULL),
(4,  4,  'my_site@mail.com', NULL),
(5,  5,  'Dream CMS', NULL),
(6,  6,  'php,dream cms,zend framework2', NULL),
(7,  7,  1, NULL),
(8,  8,  1, NULL),
(9,  9,  1, NULL),
(10, 10, 1, NULL),
(11, 11, 220, NULL),
(12, 12, 88, NULL),
(13, 13, 40, NULL),
(14, 14, 4, NULL),
(15, 15, '1902', NULL),
(16, 16, '2037', NULL),
(17, 17, 'SHORT', NULL),
(18, 18, 'UTC', NULL);

CREATE TABLE IF NOT EXISTS `settings_predefined_values` (
    `setting_id` int(10) unsigned NOT NULL,
    `value` varchar(255) NOT NULL,
    PRIMARY KEY (`setting_id`, `value`),
    FOREIGN KEY (setting_id) REFERENCES settings(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `settings_predefined_values` (`setting_id`, `value`) VALUES
(17, 'FULL'),
(17, 'LONG'),
(17, 'MEDIUM'),
(17, 'SHORT');

CREATE TABLE IF NOT EXISTS `events` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `name` varchar(150) NOT NULL,
    `module` int(10) unsigned NOT NULL,
    `description` varchar(255) NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `name` (`name`),
    FOREIGN KEY (module) REFERENCES modules(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

INSERT INTO `events` (`id`, `name`, `module`, `description`) VALUES
(1, 'get_localizations_via_xmlrpc', 1, 'Event - Get localizations via XmlRpc'),
(2, 'user_login', 2, 'Event - User login'),
(3, 'user_login_failed', 2, 'Event - User login failed'),
(4, 'user_logout', 2, 'Event - User logout'),
(5, 'get_user_info_via_xmlrpc', 2, 'Event - Get user\'s info via XmlRpc'),
(6, 'set_user_timezone_via_xmlrpc', 2, 'Event - Set user\'s timezone via XmlRpc'),
(7, 'change_settings', 1, 'Event - Settings change');

CREATE TABLE IF NOT EXISTS `admin_menu` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `name` varchar(150) NOT NULL,
    `controller` varchar(255) NOT NULL,
    `action` varchar(255) NOT NULL,
    `module` int(10) unsigned NOT NULL,
    `order` int(10) unsigned NOT NULL,
    PRIMARY KEY (`id`),
    FOREIGN KEY (module) REFERENCES modules(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

INSERT INTO `admin_menu` (`id`, `name`, `controller`, `action`, `module`, `order`) VALUES
(1, 'Modules', 'modules-administration', 'index', 1, 1),
(2, 'Site settings', 'settings-administration', 'index', 1, 2),
(3, 'Localizations', 'localizations-administration', 'index', 1, 3),
(4, 'Layouts', 'layouts-administration', 'index', 1, 4),
(5, 'Access Control List', 'acl-administration', 'index', 1, 5),
(6, 'Users', 'users-administration', 'index', 2, 6);