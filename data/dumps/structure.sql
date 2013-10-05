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
    KEY `type` (`type`, `active`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

INSERT INTO `modules` (`id`, `name`, `type`, `active`, `version`, `vendor`, `vendor_email`, `description`, `dependences`) VALUES
(1, 'Application', 'system', 1, '0.9', 'eSASe', 'alexermashev@gmail.com', 'Core module, make the first application initialization', ''),
(2, 'Users', 'system', 1, '0.9', 'eSASe', 'alexermashev@gmail.com', 'Allows to users logon and logoff ', '');

CREATE TABLE IF NOT EXISTS `localizations` (
    `language` varchar(2) NOT NULL,
    `locale` varchar(5) NOT NULL,
    `default` tinyint(1) unsigned NOT NULL,
    PRIMARY KEY (`language`),
    KEY `default` (`default`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `localizations` (`language`, `locale`, `default`) VALUES
('en', 'en_US', 1),
('ru', 'ru_RU', 0);

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
    `module` int(10) unsigned NOT NULL,
    PRIMARY KEY (`id`),
    FOREIGN KEY (module) REFERENCES modules(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

INSERT INTO `acl_resources` (`id`, `resource`, `module`) VALUES
(1, 'application administration', 1);

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
    PRIMARY KEY (`user_id`),
    UNIQUE KEY `nick_name` (`nick_name`),
    UNIQUE KEY `email` (`email`),
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

INSERT INTO `users` (`user_id`, `nick_name`, `email`, `password`, `salt`, `role`) VALUES
(1, 'esase', 'alexermashev@gmail.com', 'a10487c11b57054ffefe4108f3657a13cdbf54cc', ',LtHh5Dz', 1);

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

CREATE TABLE IF NOT EXISTS `settings` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `name` varchar(50) NOT NULL,
    `label` varchar(150) NOT NULL,
    `desc` varchar(255) NOT NULL,
    `type` enum('date','text','textarea','html_textarea','checkbox','select','radio', 'system') NOT NULL,
    `required` tinyint(1) unsigned NOT NULL,
    `check` enum('integer','float_number','string','email','url') NOT NULL,
    `order` smallint(5) unsigned NOT NULL,
    `category` int(10) unsigned DEFAULT NULL,
    `module` int(10) unsigned NOT NULL,
    `language_sensitive` tinyint(1) NOT NULL DEFAULT '1',
    PRIMARY KEY (`id`),
    UNIQUE KEY `name` (`name`),
    FOREIGN KEY (category) REFERENCES settings_categories(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    FOREIGN KEY (module) REFERENCES modules(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

INSERT INTO `settings` (`id`, `name`, `label`, `desc`, `type`, `required`, `check`, `order`, `category`, `module`, `language_sensitive`) VALUES
(1, 'application_generator', '', '', 'system', 0, '', 0, NULL, 1, 0),
(2, 'application_generator_version', '', '', 'system', 0, '', 0, NULL, 1, 0),
(3, 'application_site_name', 'Site name', '', 'text', 1, '', 1, NULL, 1, 1),
(4, 'application_site_email', 'Site email', '', 'text', 1, '', 2, NULL, 1, 0),
(5, 'application_meta_description', 'Meta description', '', 'text', 1, '', 3, NULL, 1, 1),
(6, 'application_meta_keywords', 'Meta keywords', '', 'text', 1, '', 4, NULL, 1, 1),
(7, 'application_js_cache', 'Js cache', '', 'checkbox', 0, '', 5, NULL, 1, 0),
(8, 'application_js_cache_gzip', 'Enable gzip for js cache', '', 'checkbox', 0, '', 6, NULL, 1, 0),
(9, 'application_css_cache', 'Css cache', '', 'checkbox', 0, '', 7, NULL, 1, 0),
(10, 'application_css_cache_gzip', 'Enable gzip for css cache', '', 'checkbox', 0, '', 8, NULL, 1, 0);

CREATE TABLE IF NOT EXISTS `settings_values` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `setting_id` int(10) unsigned NOT NULL,
    `value` varchar(255) NOT NULL,
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
(4,  4,  '', NULL),
(5,  5,  'Dream CMS', NULL),
(6,  6,  'php,dream cms,zend framework2', NULL),
(7,  7,  1, NULL),
(8,  8,  1, NULL),
(9,  9,  1, NULL),
(10, 10, 1, NULL);

CREATE TABLE IF NOT EXISTS `settings_predefined_values` (
    `setting_id` int(10) unsigned NOT NULL,
    `value` varchar(255) NOT NULL,
    PRIMARY KEY (`setting_id`, `value`),
    FOREIGN KEY (setting_id) REFERENCES settings(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

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
(1, 'user.login', 2, 'User login desc'),
(2, 'user.login.failed', 2, 'User login failed desc'),
(3, 'user.logout', 2, 'User logout desc');