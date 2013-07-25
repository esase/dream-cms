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

CREATE TABLE IF NOT EXISTS `acl_roles` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `name` varchar(50) NOT NULL,
    `system` tinyint(1) NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

INSERT INTO `acl_roles` (`id`, `name`, `system`) VALUES
(1, 'admin', 1),
(2, 'guest', 1),
(3, 'member', 1);

CREATE TABLE IF NOT EXISTS `users` (
    `user_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `nick_name` varchar(255) NOT NULL DEFAULT '',
    `email` varchar(255) NOT NULL DEFAULT '',
    `password` varchar(40) NOT NULL DEFAULT '',
    `salt` varchar(10) NOT NULL DEFAULT '',
    `role` int(10) unsigned NOT NULL,
    PRIMARY KEY (`user_id`),
    UNIQUE KEY `nick_name` (`nick_name`),
    UNIQUE KEY `email` (`email`),
    FOREIGN KEY (role) REFERENCES acl_roles(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

INSERT INTO `users` (`user_id`, `nick_name`, `email`, `password`, `salt`, `role`) VALUES
(1, 'esase', 'alexermashev@gmail.com', 'a10487c11b57054ffefe4108f3657a13cdbf54cc', ',LtHh5Dz', 1);
