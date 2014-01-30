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
(1,  'xmlrpc_get_localizations', 'ACL - Getting site\'s localizations via XmlRpc', 1),
(2,  'xmlrpc_view_user_info', 'ACL - Getting user\'s info via XmlRpc', 2),
(3,  'xmlrpc_set_user_timezone', 'ACL - Editing user\'s timezone via XmlRpc', 2),
(4,  'modules_administration_index', 'ACL - Viewing site\'s modules in admin area', 1),
(5,  'settings_administration_index', 'ACL - Editing site\'s settings in admin area', 1),
(6,  'localizations_administration_index', 'ACL - Viewing site\'s localizations in admin area', 1),
(7,  'layouts_administration_index', 'ACL - Viewing site\'s layouts in admin area', 1),
(8,  'users_administration_list', 'ACL - Viewing users in admin area', 2),
(9,  'acl_administration_list', 'ACL - Viewing ACL roles in admin area', 1),
(10, 'acl_administration_add_role', 'ACL - Adding ACL roles in admin area', 1),
(11, 'acl_administration_delete_roles', 'ACL - Deleting ACL roles in admin area', 1),
(12, 'acl_administration_edit_role', 'ACL - Editing ACL roles in admin area', 1),
(13, 'acl_administration_browse_resources', 'ACL - Browsing ACL resources in admin area', 1),
(14, 'acl_administration_allow_resources', 'ACL - Allowing ACL resources in admin area', 1),
(15, 'acl_administration_disallow_resources', 'ACL - Disallowing ACL resources in admin area', 1),
(16, 'acl_administration_resource_settings', 'ACL - Editing ACL resources settings in admin area', 1),
(17, 'settings_administration_clear_cache', 'ACL - Clearing site\'s cache in admin area', 1),
(18, 'users_administration_approve', 'ACL - Approving users in admin area', 2),
(19, 'users_administration_disapprove', 'ACL - Disapproving users in admin area', 2),
(20, 'users_administration_delete', 'ACL - Deleting users in admin area', 2),
(21, 'users_administration_add_user', 'ACL - Adding users in admin area', 2),
(22, 'users_administration_settings', 'ACL - Editing users settings in admin area', 2),
(23, 'users_administration_edit_user', 'ACL - Editing users in admin area', 2);

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
    `nick_name` varchar(50) NOT NULL DEFAULT '',
    `slug` varchar(100) NOT NULL DEFAULT '',
    `status` enum('approved','disapproved') NOT NULL,
    `email` varchar(255) NOT NULL DEFAULT '',
    `password` varchar(40) NOT NULL DEFAULT '',
    `salt` varchar(10) NOT NULL DEFAULT '',
    `role` int(10) unsigned NOT NULL,
    `language` varchar(2) DEFAULT NULL,
    `time_zone` varchar(100) NOT NULL,
    `layout` varchar(50) DEFAULT NULL,
    `api_key` varchar(50) NOT NULL DEFAULT '',
    `api_secret` varchar(50) NOT NULL DEFAULT '',
    `registered` date NOT NULL,
    `activation_code` varchar(20) NOT NULL,
    `avatar` varchar(100) NOT NULL,
    PRIMARY KEY (`user_id`),
    UNIQUE KEY `nick_name` (`nick_name`),
    UNIQUE KEY `email` (`email`),
    UNIQUE KEY `api_key` (`api_key`),
    UNIQUE KEY `slug` (`slug`),
    KEY `status` (`status`),
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

INSERT INTO `users` (`user_id`, `nick_name`, `slug`, `status`, `email`, `password`, `salt`, `role`, `api_key`, `api_secret`) VALUES
(1, 'esase', 'esase', 'approved', 'alexermashev@gmail.com', 'a10487c11b57054ffefe4108f3657a13cdbf54cc', ',LtHh5Dz', 1, '123sAdsNms', 'Uyqqqx998');

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
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `connection_id` int(10) unsigned NOT NULL,
    `user_id` int(10) unsigned DEFAULT NULL,
    `actions` int(10) unsigned NOT NULL,
    `actions_last_reset` int(10) unsigned NOT NULL,
    PRIMARY KEY (`id`),
    KEY `actions_last_reset` (`actions_last_reset`),
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
(5, 'SEO', 1),
(6, 'Pagination', 1),
(7, 'Email notifications settings', 1),
(8, 'Main settings', 2),
(9, 'Email notifications', 2);

CREATE TABLE IF NOT EXISTS `settings` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `name` varchar(50) NOT NULL,
    `label` varchar(150) NOT NULL,
    `description` varchar(255) NOT NULL,
    `type` enum('text', 'integer', 'float', 'email', 'textarea', 'password', 'radio', 'select', 'multiselect', 'checkbox', 'multicheckbox', 'url', 'date', 'date_unixtime', 'htmlarea', 'notification_title', 'notification_message', 'system') NOT NULL,
    `required` tinyint(1) unsigned NOT NULL,
    `order` smallint(5) unsigned NOT NULL,
    `category` int(10) unsigned DEFAULT NULL,
    `module` int(10) unsigned NOT NULL,
    `language_sensitive` tinyint(1) NOT NULL DEFAULT '1',
    `values_provider` varchar(255) NOT NULL,
    `check` text NOT NULL,
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

INSERT INTO `settings` (`id`, `name`, `label`, `description`, `type`, `required`, `order`, `category`, `module`, `language_sensitive`, `values_provider`, `check`, `check_message`) VALUES
(1,  'application_generator', '', '', 'system', 0, 0, NULL, 1, 0, '', '', ''),
(2,  'application_generator_version', '', '', 'system', 0, 0, NULL, 1, 0, '', '', ''),
(3,  'application_site_name', 'Site name', 'This value will be visible in a browser title', 'text', 1, 1, 1, 1, 1, '', '', ''),
(4,  'application_site_email', 'Site email', '', 'email', 1, 1, 1, 1, 0, '', '', ''),
(5,  'application_meta_description', 'Meta description', '', 'text', 1, 3, 5, 1, 1, '', '', ''),
(6,  'application_meta_keywords', 'Meta keywords', '', 'text', 1, 4, 5, 1, 1, '', '', ''),
(7,  'application_js_cache', 'Enable js cache', '', 'checkbox', 0, 5, 2, 1, 0, '', '', ''),
(8,  'application_js_cache_gzip', 'Enable gzip for js cache', '', 'checkbox', 0, 6, 2, 1, 0, '', '', ''),
(9,  'application_css_cache', 'Enable css cache', '', 'checkbox', 0, 7, 2, 1, 0, '', '', ''),
(10, 'application_css_cache_gzip', 'Enable gzip for css cache', '', 'checkbox', 0, 8, 2, 1, 0, '', '', ''),
(11, 'application_captcha_width', 'Captcha width', '', 'integer', 1, 9, 3, 1, 0, '', '', ''),
(12, 'application_captcha_height', 'Captcha height', '', 'integer', 1, 10, 3, 1, 0, '', '', ''),
(13, 'application_captcha_dot_noise', 'Captcha dot noise level', '', 'integer', 1, 11, 3, 1, 0, '', '', ''),
(14, 'application_captcha_line_noise', 'Captcha line noise level', '', 'integer', 1, 12, 3, 1, 0, '', '', ''),
(15, 'application_calendar_min_year', 'Min year in calendar', '', 'integer', 1, 1, 4, 1, 0, '', 'return intval(''{value}'') >= 1902 and intval(''{value}'') <= 2037;', 'Year should be in range from 1902 to 2037'),
(16, 'application_calendar_max_year', 'Max year in calendar', '', 'integer', 1, 2, 4, 1, 0, '', 'return intval(''{value}'') >= 1902 and intval(''{value}'') <= 2037;', 'Year should be in range from 1902 to 2037'),
(17, 'application_default_date_format', 'Default date format', '', 'select', 1, 3, 1, 1, 1, '', '', ''),
(18, 'application_default_time_zone', 'Default time zone', '', 'select', 1, 4, 1, 1, 0, 'return array_combine(DateTimeZone::listIdentifiers(), DateTimeZone::listIdentifiers());', '', ''),
(19, 'application_per_page', 'Default per page value', '', 'integer', 1, 1, 6, 1, 0, '', 'return intval(''{value}'') > 0;', 'Default per page value should be greater than 0'),
(20, 'application_min_per_page_range', 'Min per page range', '', 'integer', 1, 2, 6, 1, 0, '', 'return intval(''{value}'') > 0;', 'Min per page range should be greater than 0'),
(21, 'application_max_per_page_range', 'Max per page range', '', 'integer', 1, 3, 6, 1, 0, '', 'return intval(''{value}'') > 0;', 'Max per page range should be greater than 0'),
(22, 'application_per_page_step', 'Per page step', '', 'integer', 1, 4, 6, 1, 0, '', 'return intval(''{value}'') > 0;', 'Per page step should be greater than 0'),
(23, 'application_page_range', 'Page range', '', 'integer', 1, 5, 6, 1, 0, '', 'return intval(''{value}'') > 0;', 'Page range should be greater than 0'),
(24, 'application_dynamic_cache', 'Dynamic cache engine', 'It used for caching  template paths, language translations, etc', 'select', 1, 1, 2, 1, 0, '', 'switch(''{value}'') {\r\n    case ''xcache'' :\r\n        return extension_loaded(''xcache'');\r\n    case ''wincache'' :\r\n        return extension_loaded(''wincache'');\r\n    case ''apc'' :\r\n        return (version_compare(''3.1.6'', phpversion(''apc'')) > 0) || !ini_get(''apc.enabled'') ? false : true;\r\n    default :\r\n        $v = (string) phpversion(''memcached'');\r\n        $extMemcachedMajorVersion = ($v !== '''') ? (int) $v[0] : 0;\r\n\r\n        return $extMemcachedMajorVersion < 1 ? false : true;\r\n}', 'Extension is not loaded'),
(25, 'application_dynamic_cache_life_time', 'Dynamic cache life time', '', 'integer', 1, 2, 2, 1, 0, '', '', ''),
(26, 'application_memcache_host', 'Memcache host', '', 'text', 1, 3, 2, 1, 0, '', '', ''),
(27, 'application_memcache_port', 'Memcache port', '', 'integer', 1, 4, 2, 1, 0, '', '', ''),
(28, 'notification_from', 'From', '', 'email', 1, 1, 7, 1, 0, '', '', ''),
(29, 'use_smtp', 'Use SMTP', '', 'checkbox', 0, 2, 7, 1, 0, '', '', ''),
(30, 'smtp_host', 'SMTP host', '', 'text', 0, 3, 7, 1, 0, '', '', ''),
(31, 'smtp_port', 'SMTP port', '', 'integer', 0, 4, 7, 1, 0, '', '', ''),
(32, 'smtp_user', 'SMTP user', '', 'text', 0, 5, 7, 1, 0, '', '', ''),
(33, 'smtp_password', 'SMTP password', '', 'text', 0, 6, 7, 1, 0, '', '', ''),
(34, 'user_nickname_min', 'User\'s min nickname length', '', 'integer', 1, 1, 8, 2, 0, '', 'return intval(''{value}'') > 0;', 'Value should be greater than 0'),
(35, 'user_nickname_max', 'User\'s max nickname length', '', 'integer', 1, 2, 8, 2, 0, '', 'return intval(''{value}'') > 0 && intval(''{value}'') <= 40;', 'Nickname should be greater than 0 and less or equal than 40'),
(36, 'user_approved_title', 'User approved title', 'An account approve notification', 'notification_title', 1, 1, 9, 2, 1, '', '', ''),
(37, 'user_approved_message', 'User approved message', '', 'notification_message', 1, 2, 9, 2, 1, '', '', ''),
(38, 'user_disapproved_title', 'User disapproved title', 'An account disapprove notification', 'notification_title', 1, 3, 9, 2, 1, '', '', ''),
(39, 'user_disapproved_message', 'User disapproved message', '', 'notification_message', 1, 4, 9, 2, 1, '', '', ''),
(40, 'user_deleted_title', 'User deleted title', 'An account delete notification', 'notification_title', 1, 6, 9, 2, 1, '', '', ''),
(41, 'user_deleted_message', 'User deleted message', '', 'notification_message', 1, 7, 9, 2, 1, '', '', ''),
(42, 'user_allow_register', 'Allow users register', '', 'checkbox', 0, 3, 8, 2, 0, '', '', ''),
(43, 'user_auto_confirm', 'Users auto confirm registrations', '', 'checkbox', 0, 4, 8, 2, 0, '', '', ''),
(44, 'user_email_confirmation_title', 'Email confirmation title', 'An account confirm email notification', 'notification_title', 1, 8, 9, 2, 1, '', '', ''),
(45, 'user_email_confirmation_message', 'Email confirmation message', '', 'notification_message', 1, 9, 9, 2, 1, '', '', ''),
(46, 'user_registered_send', 'Send notification about users registrations', '', 'checkbox', 0, 10, 9, 2, 0, '', '', ''),
(47, 'user_registered_title', 'Register a new user title', 'An account register email notification', 'notification_title', 1, 11, 9, 2, 1, '', '', ''),
(48, 'user_registered_message', 'Register a new user message', '', 'notification_message', 1, 12, 9, 2, 1, '', '', ''),
(49, 'user_deleted_send', 'Send notification about users deletions', '', 'checkbox', 0, 5, 9, 2, 0, '', '', ''),
(50, 'user_reset_password_title', 'Reset password confirmation title', 'An account confirm reset password notification', 'notification_title', 1, 13, 9, 2, 1, '', '', ''),
(51, 'user_reset_password_message', 'Reset password confirmation message', '', 'notification_message', 1, 14, 9, 2, 1, '', '', ''),
(52, 'user_password_reseted_title', 'Password reseted title', 'An account password reseted notification', 'notification_title', 1, 15, 9, 2, 1, '', '', ''),
(53, 'user_password_reseted_message', 'Password reseted message', '', 'notification_message', 1, 16, 9, 2, 1, '', '', '');

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
(4,  4,  'stuff@mysite.com', NULL),
(5,  5,  'Dream CMS', NULL),
(6,  6,  'php,dream cms,zend framework2', NULL),
(7,  7,  '1', NULL),
(8,  8,  '1', NULL),
(9,  9,  '1', NULL),
(10, 10, '1', NULL),
(11, 11, '220', NULL),
(12, 12, '88', NULL),
(13, 13, '40', NULL),
(14, 14, '4', NULL),
(15, 15, '1902', NULL),
(16, 16, '2037', NULL),
(17, 17, 'short', NULL),
(18, 18, 'UTC', NULL),
(19, 19, '10', NULL),
(20, 20, '10', NULL),
(21, 21, '100', NULL),
(22, 22, '10', NULL),
(23, 23, '10', NULL),
(24, 24, 'memcached', NULL),
(25, 25, '600', NULL),
(26, 26, 'localhost', NULL),
(27, 27, '11211', NULL),
(28, 28, 'no_reply@mysite.com', NULL),
(29, 34, '3', NULL),
(30, 35, '15', NULL),
(31, 36, 'Your profile is now active', NULL),
(32, 37, '<p><b>Dear {RealName}</b>,</p>\r\n<p>Your profile was reviewed and activated!</p>\r\n<p>Your E-mail: {Email}</p>', NULL),
(33, 36, 'Ваш профиль сейчас активен', 'ru'),
(34, 37, '<p><b>Уважаемый(я) {RealName}</b>,</p>\r\n<p>Ваш профиль был рассмотрен и активирован!</p>\r\n<p>Ваш адрес электронной почты: {Email}</p>', 'ru'),
(35, 38, 'Your profile is now deactived', NULL),
(36, 39, '<p><b>Dear {RealName}</b>,</p>\r\n<p>Your profile was deactivated!</p>\r\n<p>Please contact with support team.</p>\r\n<p>Your E-mail: {Email}</p>', NULL),
(37, 38, 'Ваш профиль сейчас дезактивирован', 'ru'),
(38, 39, '<p><b>Уважаемый(я) {RealName}</b>,</p>\r\n<p>Ваш профиль был дезактивирован!</p>\r\n<p>Пожалуйста, связаться с командой поддержки.</p>\r\n<p>Ваш адрес электронной почты: {Email}</p>', 'ru'),
(39, 40, 'Your profile deleted', NULL),
(40, 41, '<p><b>Dear {RealName}</b>,</p>\r\n<p>Your profile was deleted!</p>', NULL),
(41, 40, 'Ваш профиль удален', 'ru'),
(42, 41, '<p><b>Уважаемый(я) {RealName}</b>,</p>\r\n<p>Ваш профиль был удален!</p>', 'ru'),
(43, 42,  '1', NULL),
(44, 43,  '1', NULL),
(45, 44, 'Email confirmation request', NULL),
(46, 44, 'Запрос на подтверждение E-mail', 'ru'),
(47, 45, '<p><b>Dear {RealName}</b>,</p>\r\n<p>Thank you for registering at {SiteName}!</p>\r\n<p>Click to confirm your email: <a href="{ConfirmationLink}">{ConfirmationLink}</a></p>\r\n<p>Confirmation code: <b>{ConfCode}</b></p>', NULL),
(48, 45, '<p><b>Уважаемый(я) {RealName}</b>,</p>\r\n<p>Спасибо за регистрацию на {SiteName}!</p>\r\n<p>Нажмите, чтобы подтвердить адрес электронной почты: <a href="{ConfirmationLink}">{ConfirmationLink}</a></p>\r\n<p>Код подтверждения: <b>{ConfCode}</b></p>', 'ru'),
(49, 47, 'A new user registered', NULL),
(50, 47, 'Новый пользователь зарегистрирован', 'ru'),
(51, 48, '<p>The new user\'s name: <b>{RealName}</b></p>\r\n<p>The user\'s email: <b>{Email}</b></p>', NULL),
(52, 48, '<p>Имя нового пользователя: <b>{RealName}</b></p>\r\n<p>E-mail пользователя: <b>{Email}</b></p>', 'ru'),
(53, 46,  '1', NULL),
(54, 49,  '1', NULL),
(55, 50, 'Password reset confirmation request', NULL),
(56, 50, 'Запрос на подтверждение сброса пароля', 'ru'),
(57, 51, '<p><b>Dear {RealName}</b>,</p>\r\n<p>Click to confirm your password reset: <a href="{ConfirmationLink}">{ConfirmationLink}</a></p>\r\n<p>Confirmation code: <b>{ConfCode}</b></p>', NULL),
(58, 51, '<p><b>Уважаемый(я) {RealName}</b>,</p>\r\n<p>Нажмите, чтобы подтвердить свой сброс пароля: <a href="{ConfirmationLink}">{ConfirmationLink}</a></p>\r\n<p>Код подтверждения: <b>{ConfCode}</b></p>', 'ru'),
(59, 52, 'Your password was reset', NULL),
(60, 52, 'Ваш пароль был сброшен', 'ru'),
(61, 53, '<p><b>Dear {RealName}</b>,</p>\r\n<p>Now your new password is: <b>{Password}</b></p>', NULL),
(62, 53, '<p><b>Уважаемый(я) {RealName}</b>,</p>\r\n<p>Теперь ваш новый пароль: <b>{Password}</b></p>', 'ru');

CREATE TABLE IF NOT EXISTS `settings_predefined_values` (
    `setting_id` int(10) unsigned NOT NULL,
    `value` varchar(255) NOT NULL,
    PRIMARY KEY (`setting_id`, `value`),
    FOREIGN KEY (setting_id) REFERENCES settings(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `settings_predefined_values` (`setting_id`, `value`) VALUES
(17, 'full'),
(17, 'long'),
(17, 'medium'),
(17, 'short'),
(24, 'memcached'),
(24, 'apc'),
(24, 'xcache'),
(24, 'wincache');

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
(1,  'get_localizations_via_xmlrpc', 1, 'Event - Getting localizations via XmlRpc'),
(2,  'user_login', 2, 'Event - Users sign in'),
(3,  'user_login_failed', 2, 'Event - Users logins failed'),
(4,  'user_logout', 2, 'Event - Users sign out'),
(5,  'get_user_info_via_xmlrpc', 2, 'Event - Getting user\'s info via XmlRpc'),
(6,  'set_user_timezone_via_xmlrpc', 2, 'Event - Setting users timezones via XmlRpc'),
(7,  'change_settings', 1, 'Event - Editing settings'),
(8,  'delete_acl_role', 1, 'Event - Deleting ACL roles'),
(9,  'add_acl_role', 1, 'Event - Adding ACL roles'),
(10, 'edit_acl_role', 1, 'Event - Editing ACL roles'),
(11, 'allow_acl_resource', 1, 'Event - Allowing ACL resources'),
(12, 'disallow_acl_resource', 1, 'Event - Disallowing ACL resources'),
(13, 'edit_acl_resource_settings', 1, 'Event - Editing ACL resources settings'),
(14, 'clear_cache', 1, 'Event - Clearing site\'s cache'),
(15, 'user_disapprove', 2, 'Event - Disapproving users'),
(16, 'user_approve', 2, 'Event - Approving users'),
(17, 'user_delete', 2, 'Event - Deleting users'),
(18, 'user_add', 2, 'Event - Adding users'),
(19, 'user_edit', 2, 'Event - Editing users'),
(20, 'send_email_notification', 1, 'Event - Sending email notifications'),
(22, 'user_password_reset', 2, 'Event - Resetting users passwords'),
(23, 'user_password_reset_request', 2, 'Event - Requesting reset users passwords');

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
(5, 'Access Control List', 'acl-administration', 'list', 1, 5),
(6, 'Users', 'users-administration', 'list', 2, 6);