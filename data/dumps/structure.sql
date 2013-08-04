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

CREATE TABLE IF NOT EXISTS `acl_resources_users_connections` (
    `connection_id` int(10) unsigned NOT NULL,
    `user_id` int(10) unsigned NOT NULL,
    `date_start` int(10) unsigned NOT NULL,
    `date_end` int(10) unsigned NOT NULL,
    `actions_limit` int(10) unsigned NOT NULL,
    `actions` int(10) unsigned NOT NULL,
    `actions_reset` int(10) unsigned NOT NULL,
    `actions_last_reset` int(10) unsigned NOT NULL,
    PRIMARY KEY (`connection_id`, `user_id`),
    FOREIGN KEY (connection_id) REFERENCES acl_resources_connections(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
