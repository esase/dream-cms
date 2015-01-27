SET sql_mode='STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE';

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `application_module_depend`;
DROP TABLE IF EXISTS `acl_resource`;
DROP TABLE IF EXISTS `acl_resource_action_track`;
DROP TABLE IF EXISTS `acl_resource_connection`;
DROP TABLE IF EXISTS `acl_resource_connection_setting`;
DROP TABLE IF EXISTS `acl_role`;
DROP TABLE IF EXISTS `application_admin_menu`;
DROP TABLE IF EXISTS `application_admin_menu_category`;
DROP TABLE IF EXISTS `application_admin_menu_part`;
DROP TABLE IF EXISTS `application_email_queue`;
DROP TABLE IF EXISTS `application_event`;
DROP TABLE IF EXISTS `application_module`;
DROP TABLE IF EXISTS `application_setting`;
DROP TABLE IF EXISTS `application_setting_category`;
DROP TABLE IF EXISTS `application_setting_predefined_value`;
DROP TABLE IF EXISTS `application_setting_value`;
DROP TABLE IF EXISTS `application_time_zone`;
DROP TABLE IF EXISTS `layout_list`;
DROP TABLE IF EXISTS `localization_list`;
DROP TABLE IF EXISTS `page_layout`;
DROP TABLE IF EXISTS `page_structure`;
DROP TABLE IF EXISTS `page_system`;
DROP TABLE IF EXISTS `page_system_page_depend`;
DROP TABLE IF EXISTS `page_system_widget_depend`;
DROP TABLE IF EXISTS `page_system_widget_hidden`;
DROP TABLE IF EXISTS `page_visibility`;
DROP TABLE IF EXISTS `page_widget`;
DROP TABLE IF EXISTS `page_widget_connection`;
DROP TABLE IF EXISTS `page_widget_depend`;
DROP TABLE IF EXISTS `page_widget_layout`;
DROP TABLE IF EXISTS `page_widget_page_depend`;
DROP TABLE IF EXISTS `page_widget_position`;
DROP TABLE IF EXISTS `page_widget_position_connection`;
DROP TABLE IF EXISTS `page_widget_setting`;
DROP TABLE IF EXISTS `page_widget_setting_category`;
DROP TABLE IF EXISTS `page_widget_setting_predefined_value`;
DROP TABLE IF EXISTS `page_widget_setting_value`;
DROP TABLE IF EXISTS `page_widget_visibility`;
DROP TABLE IF EXISTS `user_list`;
DROP TABLE IF EXISTS `xmlrpc_class`;
DROP TABLE IF EXISTS `page_widget_setting_default_value`;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE `application_email_queue` (
    `id` SMALLINT(5) UNSIGNED NOT NULL AUTO_INCREMENT,
    `email` VARCHAR(50) NOT NULL,
    `title` TEXT NOT NULL,
    `message` TEXT NOT NULL,
    `created` INT(10) UNSIGNED NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE `application_time_zone` (
    `id` SMALLINT(5) UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` varchar(150) NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

INSERT INTO `application_time_zone` (`id`, `name`) VALUES
(1,  'Africa/Abidjan'),
(2,  'Africa/Accra'),
(3,  'Africa/Addis_Ababa'),
(4,  'Africa/Algiers'),
(5,  'Africa/Asmara'),
(6,  'Africa/Bamako'),
(7,  'Africa/Bangui'),
(8,  'Africa/Banjul'),
(9,  'Africa/Bissau'),
(10, 'Africa/Blantyre'),
(11, 'Africa/Brazzaville'),
(12, 'Africa/Bujumbura'),
(13, 'Africa/Cairo'),
(14, 'Africa/Casablanca'),
(15, 'Africa/Ceuta'),
(16, 'Africa/Conakry'),
(17, 'Africa/Dakar'),
(18, 'Africa/Dar_es_Salaam'),
(19, 'Africa/Djibouti'),
(20, 'Africa/Douala'),
(21, 'Africa/El_Aaiun'),
(22, 'Africa/Freetown'),
(23, 'Africa/Gaborone'),
(24, 'Africa/Harare'),
(25, 'Africa/Johannesburg'),
(26, 'Africa/Juba'),
(27, 'Africa/Kampala'),
(28, 'Africa/Khartoum'),
(29, 'Africa/Kigali'),
(30, 'Africa/Kinshasa'),
(31, 'Africa/Lagos'),
(32, 'Africa/Libreville'),
(33, 'Africa/Lome'),
(34, 'Africa/Luanda'),
(35, 'Africa/Lubumbashi'),
(36, 'Africa/Lusaka'),
(37, 'Africa/Malabo'),
(38, 'Africa/Maputo'),
(39, 'Africa/Maseru'),
(40, 'Africa/Mbabane'),
(41, 'Africa/Mogadishu'),
(42, 'Africa/Monrovia'),
(43, 'Africa/Nairobi'),
(44, 'Africa/Ndjamena'),
(45, 'Africa/Niamey'),
(46, 'Africa/Nouakchott'),
(47, 'Africa/Ouagadougou'),
(48, 'Africa/Porto-Novo'),
(49, 'Africa/Sao_Tome'),
(50, 'Africa/Tripoli'),
(51, 'Africa/Tunis'),
(52, 'Africa/Windhoek'),
(53, 'America/Adak'),
(54, 'America/Anchorage'),
(55, 'America/Anguilla'),
(56, 'America/Antigua'),
(57, 'America/Araguaina'),
(58, 'America/Argentina/Buenos_Aires'),
(59, 'America/Argentina/Catamarca'),
(60, 'America/Argentina/Cordoba'),
(61, 'America/Argentina/Jujuy'),
(62, 'America/Argentina/La_Rioja'),
(63, 'America/Argentina/Mendoza'),
(64, 'America/Argentina/Rio_Gallegos'),
(65, 'America/Argentina/Salta'),
(66, 'America/Argentina/San_Juan'),
(67, 'America/Argentina/San_Luis'),
(68, 'America/Argentina/Tucuman'),
(69, 'America/Argentina/Ushuaia'),
(70, 'America/Aruba'),
(71, 'America/Asuncion'),
(72, 'America/Atikokan'),
(73, 'America/Bahia'),
(74, 'America/Bahia_Banderas'),
(75, 'America/Barbados'),
(76, 'America/Belem'),
(77, 'America/Belize'),
(78, 'America/Blanc-Sablon'),
(79, 'America/Boa_Vista'),
(80, 'America/Bogota'),
(81, 'America/Boise'),
(82, 'America/Cambridge_Bay'),
(83, 'America/Campo_Grande'),
(84, 'America/Cancun'),
(85, 'America/Caracas'),
(86, 'America/Cayenne'),
(87, 'America/Cayman'),
(88, 'America/Chicago'),
(89, 'America/Chihuahua'),
(90, 'America/Costa_Rica'),
(91, 'America/Creston'),
(92, 'America/Cuiaba'),
(93, 'America/Curacao'),
(94, 'America/Danmarkshavn'),
(95, 'America/Dawson'),
(96, 'America/Dawson_Creek'),
(97, 'America/Denver'),
(98, 'America/Detroit'),
(99, 'America/Dominica'),
(100, 'America/Edmonton'),
(101, 'America/Eirunepe'),
(102, 'America/El_Salvador'),
(103, 'America/Fortaleza'),
(104, 'America/Glace_Bay'),
(105, 'America/Godthab'),
(106, 'America/Goose_Bay'),
(107, 'America/Grand_Turk'),
(108, 'America/Grenada'),
(109, 'America/Guadeloupe'),
(110, 'America/Guatemala'),
(111, 'America/Guayaquil'),
(112, 'America/Guyana'),
(113, 'America/Halifax'),
(114, 'America/Havana'),
(115, 'America/Hermosillo'),
(116, 'America/Indiana/Indianapolis'),
(117, 'America/Indiana/Knox'),
(118, 'America/Indiana/Marengo'),
(119, 'America/Indiana/Petersburg'),
(120, 'America/Indiana/Tell_City'),
(121, 'America/Indiana/Vevay'),
(122, 'America/Indiana/Vincennes'),
(123, 'America/Indiana/Winamac'),
(124, 'America/Inuvik'),
(125, 'America/Iqaluit'),
(126, 'America/Jamaica'),
(127, 'America/Juneau'),
(128, 'America/Kentucky/Louisville'),
(129, 'America/Kentucky/Monticello'),
(130, 'America/Kralendijk'),
(131, 'America/La_Paz'),
(132, 'America/Lima'),
(133, 'America/Los_Angeles'),
(134, 'America/Lower_Princes'),
(135, 'America/Maceio'),
(136, 'America/Managua'),
(137, 'America/Manaus'),
(138, 'America/Marigot'),
(139, 'America/Martinique'),
(140, 'America/Matamoros'),
(141, 'America/Mazatlan'),
(142, 'America/Menominee'),
(143, 'America/Merida'),
(144, 'America/Metlakatla'),
(145, 'America/Mexico_City'),
(146, 'America/Miquelon'),
(147, 'America/Moncton'),
(148, 'America/Monterrey'),
(149, 'America/Montevideo'),
(150, 'America/Montreal'),
(151, 'America/Montserrat'),
(152, 'America/Nassau'),
(153, 'America/New_York'),
(154, 'America/Nipigon'),
(155, 'America/Nome'),
(156, 'America/Noronha'),
(157, 'America/North_Dakota/Beulah'),
(158, 'America/North_Dakota/Center'),
(159, 'America/North_Dakota/New_Salem'),
(160, 'America/Ojinaga'),
(161, 'America/Panama'),
(162, 'America/Pangnirtung'),
(163, 'America/Paramaribo'),
(164, 'America/Phoenix'),
(165, 'America/Port-au-Prince'),
(166, 'America/Port_of_Spain'),
(167, 'America/Porto_Velho'),
(168, 'America/Puerto_Rico'),
(169, 'America/Rainy_River'),
(170, 'America/Rankin_Inlet'),
(171, 'America/Recife'),
(172, 'America/Regina'),
(173, 'America/Resolute'),
(174, 'America/Rio_Branco'),
(175, 'America/Santa_Isabel'),
(176, 'America/Santarem'),
(177, 'America/Santiago'),
(178, 'America/Santo_Domingo'),
(179, 'America/Sao_Paulo'),
(180, 'America/Scoresbysund'),
(181, 'America/Shiprock'),
(182, 'America/Sitka'),
(183, 'America/St_Barthelemy'),
(184, 'America/St_Johns'),
(185, 'America/St_Kitts'),
(186, 'America/St_Lucia'),
(187, 'America/St_Thomas'),
(188, 'America/St_Vincent'),
(189, 'America/Swift_Current'),
(190, 'America/Tegucigalpa'),
(191, 'America/Thule'),
(192, 'America/Thunder_Bay'),
(193, 'America/Tijuana'),
(194, 'America/Toronto'),
(195, 'America/Tortola'),
(196, 'America/Vancouver'),
(197, 'America/Whitehorse'),
(198, 'America/Winnipeg'),
(199, 'America/Yakutat'),
(200, 'America/Yellowknife'),
(201, 'Antarctica/Casey'),
(202, 'Antarctica/Davis'),
(203, 'Antarctica/DumontDUrville'),
(204, 'Antarctica/Macquarie'),
(205, 'Antarctica/Mawson'),
(206, 'Antarctica/McMurdo'),
(207, 'Antarctica/Palmer'),
(208, 'Antarctica/Rothera'),
(209, 'Antarctica/South_Pole'),
(210, 'Antarctica/Syowa'),
(211, 'Antarctica/Vostok'),
(212, 'Arctic/Longyearbyen'),
(213, 'Asia/Aden'),
(214, 'Asia/Almaty'),
(215, 'Asia/Amman'),
(216, 'Asia/Anadyr'),
(217, 'Asia/Aqtau'),
(218, 'Asia/Aqtobe'),
(219, 'Asia/Ashgabat'),
(220, 'Asia/Baghdad'),
(221, 'Asia/Bahrain'),
(222, 'Asia/Baku'),
(223, 'Asia/Bangkok'),
(224, 'Asia/Beirut'),
(225, 'Asia/Bishkek'),
(226, 'Asia/Brunei'),
(227, 'Asia/Choibalsan'),
(228, 'Asia/Chongqing'),
(229, 'Asia/Colombo'),
(230, 'Asia/Damascus'),
(231, 'Asia/Dhaka'),
(232, 'Asia/Dili'),
(233, 'Asia/Dubai'),
(234, 'Asia/Dushanbe'),
(235, 'Asia/Gaza'),
(236, 'Asia/Harbin'),
(237, 'Asia/Hebron'),
(238, 'Asia/Ho_Chi_Minh'),
(239, 'Asia/Hong_Kong'),
(240, 'Asia/Hovd'),
(241, 'Asia/Irkutsk'),
(242, 'Asia/Jakarta'),
(243, 'Asia/Jayapura'),
(244, 'Asia/Jerusalem'),
(245, 'Asia/Kabul'),
(246, 'Asia/Kamchatka'),
(247, 'Asia/Karachi'),
(248, 'Asia/Kashgar'),
(249, 'Asia/Kathmandu'),
(250, 'Asia/Kolkata'),
(251, 'Asia/Krasnoyarsk'),
(252, 'Asia/Kuala_Lumpur'),
(253, 'Asia/Kuching'),
(254, 'Asia/Kuwait'),
(255, 'Asia/Macau'),
(256, 'Asia/Magadan'),
(257, 'Asia/Makassar'),
(258, 'Asia/Manila'),
(259, 'Asia/Muscat'),
(260, 'Asia/Nicosia'),
(261, 'Asia/Novokuznetsk'),
(262, 'Asia/Novosibirsk'),
(263, 'Asia/Omsk'),
(264, 'Asia/Oral'),
(265, 'Asia/Phnom_Penh'),
(266, 'Asia/Pontianak'),
(267, 'Asia/Pyongyang'),
(268, 'Asia/Qatar'),
(269, 'Asia/Qyzylorda'),
(270, 'Asia/Rangoon'),
(271, 'Asia/Riyadh'),
(272, 'Asia/Sakhalin'),
(273, 'Asia/Samarkand'),
(274, 'Asia/Seoul'),
(275, 'Asia/Shanghai'),
(276, 'Asia/Singapore'),
(277, 'Asia/Taipei'),
(278, 'Asia/Tashkent'),
(279, 'Asia/Tbilisi'),
(280, 'Asia/Tehran'),
(281, 'Asia/Thimphu'),
(282, 'Asia/Tokyo'),
(283, 'Asia/Ulaanbaatar'),
(284, 'Asia/Urumqi'),
(285, 'Asia/Vientiane'),
(286, 'Asia/Vladivostok'),
(287, 'Asia/Yakutsk'),
(288, 'Asia/Yekaterinburg'),
(289, 'Asia/Yerevan'),
(290, 'Atlantic/Azores'),
(291, 'Atlantic/Bermuda'),
(292, 'Atlantic/Canary'),
(293, 'Atlantic/Cape_Verde'),
(294, 'Atlantic/Faroe'),
(295, 'Atlantic/Madeira'),
(296, 'Atlantic/Reykjavik'),
(297, 'Atlantic/South_Georgia'),
(298, 'Atlantic/St_Helena'),
(299, 'Atlantic/Stanley'),
(300, 'Australia/Adelaide'),
(301, 'Australia/Brisbane'),
(302, 'Australia/Broken_Hill'),
(303, 'Australia/Currie'),
(304, 'Australia/Darwin'),
(305, 'Australia/Eucla'),
(306, 'Australia/Hobart'),
(307, 'Australia/Lindeman'),
(308, 'Australia/Lord_Howe'),
(309, 'Australia/Melbourne'),
(310, 'Australia/Perth'),
(311, 'Australia/Sydney'),
(312, 'Europe/Amsterdam'),
(313, 'Europe/Andorra'),
(314, 'Europe/Athens'),
(315, 'Europe/Belgrade'),
(316, 'Europe/Berlin'),
(317, 'Europe/Bratislava'),
(318, 'Europe/Brussels'),
(319, 'Europe/Bucharest'),
(320, 'Europe/Budapest'),
(321, 'Europe/Chisinau'),
(322, 'Europe/Copenhagen'),
(323, 'Europe/Dublin'),
(324, 'Europe/Gibraltar'),
(325, 'Europe/Guernsey'),
(326, 'Europe/Helsinki'),
(327, 'Europe/Isle_of_Man'),
(328, 'Europe/Istanbul'),
(329, 'Europe/Jersey'),
(330, 'Europe/Kaliningrad'),
(331, 'Europe/Kiev'),
(332, 'Europe/Lisbon'),
(333, 'Europe/Ljubljana'),
(334, 'Europe/London'),
(335, 'Europe/Luxembourg'),
(336, 'Europe/Madrid'),
(337, 'Europe/Malta'),
(338, 'Europe/Mariehamn'),
(339, 'Europe/Minsk'),
(340, 'Europe/Monaco'),
(341, 'Europe/Moscow'),
(342, 'Europe/Oslo'),
(343, 'Europe/Paris'),
(344, 'Europe/Podgorica'),
(345, 'Europe/Prague'),
(346, 'Europe/Riga'),
(347, 'Europe/Rome'),
(348, 'Europe/Samara'),
(349, 'Europe/San_Marino'),
(350, 'Europe/Sarajevo'),
(351, 'Europe/Simferopol'),
(352, 'Europe/Skopje'),
(353, 'Europe/Sofia'),
(354, 'Europe/Stockholm'),
(355, 'Europe/Tallinn'),
(356, 'Europe/Tirane'),
(357, 'Europe/Uzhgorod'),
(358, 'Europe/Vaduz'),
(359, 'Europe/Vatican'),
(360, 'Europe/Vienna'),
(361, 'Europe/Vilnius'),
(362, 'Europe/Volgograd'),
(363, 'Europe/Warsaw'),
(364, 'Europe/Zagreb'),
(365, 'Europe/Zaporozhye'),
(366, 'Europe/Zurich'),
(367, 'Indian/Antananarivo'),
(368, 'Indian/Chagos'),
(369, 'Indian/Christmas'),
(370, 'Indian/Cocos'),
(371, 'Indian/Comoro'),
(372, 'Indian/Kerguelen'),
(373, 'Indian/Mahe'),
(374, 'Indian/Maldives'),
(375, 'Indian/Mauritius'),
(376, 'Indian/Mayotte'),
(377, 'Indian/Reunion'),
(378, 'Pacific/Apia'),
(379, 'Pacific/Auckland'),
(380, 'Pacific/Chatham'),
(381, 'Pacific/Chuuk'),
(382, 'Pacific/Easter'),
(383, 'Pacific/Efate'),
(384, 'Pacific/Enderbury'),
(385, 'Pacific/Fakaofo'),
(386, 'Pacific/Fiji'),
(387, 'Pacific/Funafuti'),
(388, 'Pacific/Galapagos'),
(389, 'Pacific/Gambier'),
(390, 'Pacific/Guadalcanal'),
(391, 'Pacific/Guam'),
(392, 'Pacific/Honolulu'),
(393, 'Pacific/Johnston'),
(394, 'Pacific/Kiritimati'),
(395, 'Pacific/Kosrae'),
(396, 'Pacific/Kwajalein'),
(397, 'Pacific/Majuro'),
(398, 'Pacific/Marquesas'),
(399, 'Pacific/Midway'),
(400, 'Pacific/Nauru'),
(401, 'Pacific/Niue'),
(402, 'Pacific/Norfolk'),
(403, 'Pacific/Noumea'),
(404, 'Pacific/Pago_Pago'),
(405, 'Pacific/Palau'),
(406, 'Pacific/Pitcairn'),
(407, 'Pacific/Pohnpei'),
(408, 'Pacific/Port_Moresby'),
(409, 'Pacific/Rarotonga'),
(410, 'Pacific/Saipan'),
(411, 'Pacific/Tahiti'),
(412, 'Pacific/Tarawa'),
(413, 'Pacific/Tongatapu'),
(414, 'Pacific/Wake'),
(415, 'Pacific/Wallis'),
(416, 'UTC');

CREATE TABLE `application_module` (
    `id` SMALLINT(5) UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(50) NOT NULL,
    `type` ENUM('system','custom') NOT NULL,
    `status` ENUM('active','not_active') NOT NULL,
    `version` VARCHAR(20) NOT NULL,
    `vendor` VARCHAR(50) NOT NULL,
    `vendor_email` VARCHAR(50) NOT NULL,
    `description` VARCHAR(255) DEFAULT NULL,
    `layout_path` VARCHAR(50) DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `type` (`type`, `status`),
    UNIQUE `name` (`name`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

INSERT INTO `application_module` (`id`, `name`, `type`, `status`, `version`, `vendor`, `vendor_email`, `description`, `layout_path`) VALUES
(1, 'Application', 'system', 'active', '__cms_version_value__', 'eSASe', 'alexermashev@gmail.com', 'Appliction module description', 'application'),
(2, 'User', 'system', 'active', '__cms_version_value__', 'eSASe', 'alexermashev@gmail.com', 'User module description', 'user'),
(3, 'XmlRpc', 'system', 'active', '__cms_version_value__', 'eSASe', 'alexermashev@gmail.com', 'XmlRpc module description', null),
(4, 'FileManager', 'system', 'active', '__cms_version_value__', 'eSASe', 'alexermashev@gmail.com', 'File manager module description', 'filemanager'),
(5, 'Page', 'system', 'active', '__cms_version_value__', 'eSASe', 'alexermashev@gmail.com', 'Page module description', 'page'),
(6, 'Layout', 'system', 'active', '__cms_version_value__', 'eSASe', 'alexermashev@gmail.com', 'Layout module description', 'layout'),
(7, 'Localization', 'system', 'active', '__cms_version_value__', 'eSASe', 'alexermashev@gmail.com', 'Localization module description', 'localization'),
(8, 'Acl', 'system', 'active', '__cms_version_value__', 'eSASe', 'alexermashev@gmail.com', 'Acl module description', 'acl');

CREATE TABLE `application_module_depend` (
    `id` SMALLINT(5) UNSIGNED NOT NULL AUTO_INCREMENT,
    `module_id` SMALLINT(5) UNSIGNED NOT NULL,
    `depend_module_id` SMALLINT(5) UNSIGNED NOT NULL,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`module_id`) REFERENCES `application_module`(`id`)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    FOREIGN KEY (`depend_module_id`) REFERENCES `application_module`(`id`)
        ON UPDATE CASCADE
        ON DELETE CASCADE    
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE `xmlrpc_class` (
    `namespace` VARCHAR(20) NOT NULL,
    `path` VARCHAR(50) NOT NULL,
    `module` SMALLINT(5) UNSIGNED NOT NULL,
    PRIMARY KEY (`namespace`, `path`, `module`),
    FOREIGN KEY (`module`) REFERENCES `application_module`(`id`)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `xmlrpc_class` (`namespace`, `path`, `module`) VALUES
('localization', 'Localization\\XmlRpc\\LocalizationHandler', 1),
('user', 'User\\XmlRpc\\UserHandler', 2);

CREATE TABLE `localization_list` (
    `language` CHAR(2) NOT NULL,
    `locale` CHAR(5) NOT NULL,
    `description` VARCHAR(50) NOT NULL,
    `default` TINYINT(1) UNSIGNED NULL,
    `direction` ENUM('rtl','ltr') NOT NULL,
    PRIMARY KEY (`language`),
    KEY `default` (`default`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `localization_list` (`language`, `locale`, `description`, `default`, `direction`) VALUES
('en', 'en_US', 'English', 1, 'ltr'),
('ru', 'ru_RU', 'Русский', NULL, 'ltr');

CREATE TABLE `layout_list` (
    `id` SMALLINT(5) UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(50) NOT NULL,
    `type` ENUM('system','custom') NOT NULL,
    `status` ENUM('active','not_active') NOT NULL,
    `title` VARCHAR(50) NOT NULL,
    `description` VARCHAR(255) NOT NULL,
    `version` VARCHAR(20) NOT NULL,
    `vendor` VARCHAR(50) NOT NULL,
    `vendor_email` VARCHAR(50) NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY (`name`),
    KEY `type` (`type`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `layout_list` (`name`, `type`, `status`, `title`, `description`, `version`, `vendor`, `vendor_email`) VALUES
('base', 'system', 'active', 'Base layout', 'Default base layout', '__cms_version_value__', 'eSASe', 'alexermashev@gmail.com');

CREATE TABLE `acl_role` (
    `id` SMALLINT(5) UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(50) NOT NULL,
    `type` ENUM('system','custom') NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

INSERT INTO `acl_role` (`id`, `name`, `type`) VALUES
(1, 'admin', 'system'),
(2, 'guest', 'system'),
(3, 'member', 'system');

CREATE TABLE `acl_resource` (
    `id` SMALLINT(5) UNSIGNED NOT NULL AUTO_INCREMENT,
    `resource` VARCHAR(100) NOT NULL,
    `description` VARCHAR(150) NOT NULL,
    `module` SMALLINT(5) UNSIGNED NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE(`resource`),
    FOREIGN KEY (`module`) REFERENCES `application_module`(`id`)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

INSERT INTO `acl_resource` (`id`, `resource`, `description`, `module`) VALUES
(1,  'xmlrpc_get_localizations', 'ACL - Getting site\'s localizations via XmlRpc', 7),
(2,  'xmlrpc_view_user_info', 'ACL - Getting user\'s info via XmlRpc', 2),
(3,  'xmlrpc_set_user_timezone', 'ACL - Editing user\'s timezone via XmlRpc', 2),
(5,  'settings_administration_index', 'ACL - Editing site\'s settings in admin area', 1),
(8,  'users_administration_list', 'ACL - Viewing users in admin area', 2),
(9,  'acl_administration_list', 'ACL - Viewing ACL roles in admin area', 8),
(10, 'acl_administration_add_role', 'ACL - Adding ACL roles in admin area', 8),
(11, 'acl_administration_delete_roles', 'ACL - Deleting ACL roles in admin area', 8),
(12, 'acl_administration_edit_role', 'ACL - Editing ACL roles in admin area', 8),
(13, 'acl_administration_browse_resources', 'ACL - Browsing ACL resources in admin area',8),
(14, 'acl_administration_allow_resources', 'ACL - Allowing ACL resources in admin area', 8),
(15, 'acl_administration_disallow_resources', 'ACL - Disallowing ACL resources in admin area', 8),
(16, 'acl_administration_resource_settings', 'ACL - Editing ACL resources settings in admin area', 8),
(17, 'settings_administration_clear_cache', 'ACL - Clearing site\'s cache in admin area', 1),
(18, 'users_administration_approve', 'ACL - Approving users in admin area', 2),
(19, 'users_administration_disapprove', 'ACL - Disapproving users in admin area', 2),
(20, 'users_administration_delete', 'ACL - Deleting users in admin area', 2),
(21, 'users_administration_add_user', 'ACL - Adding users in admin area', 2),
(22, 'users_administration_settings', 'ACL - Editing users settings in admin area', 2),
(23, 'users_administration_edit_user', 'ACL - Editing users in admin area', 2),
(24, 'application_use_js', 'ACL - Using js in forms', 1),
(25, 'users_administration_edit_role', 'ACL - Editing users roles in admin area', 2),
(26, 'users_administration_browse_acl_resources', 'ACL - Browsing allowed users ACL resources in admin area', 2),
(27, 'users_administration_acl_resource_settings', 'ACL - Editing allowed users ACL resources settings in admin area', 2),
(28, 'files_manager_administration_list', 'ACL - Viewing files and dirs in admin area', 4),
(29, 'files_manager_administration_settings', 'ACL - Editing file manager settings in admin area', 4),
(30, 'files_manager_embedded_list', 'ACL - Viewing files and dirs in embedded mode', 4),
(31, 'files_manager_administration_delete', 'ACL - Deleting files and dirs in admin area', 4),
(32, 'files_manager_embedded_delete', 'ACL - Deleting files and dirs in embedded mode', 4),
(33, 'files_manager_embedded_add_directory', 'ACL - Adding directories in embedded mode', 4),
(34, 'files_manager_administration_add_directory', 'ACL - Adding directories in admin area', 4),
(35, 'files_manager_embedded_add_file', 'ACL - Adding files in embedded mode', 4),
(36, 'files_manager_administration_add_file', 'ACL - Adding files in admin area', 4),
(37, 'files_manager_embedded_edit', 'ACL - Editing files and dirs in embedded mode', 4),
(38, 'files_manager_administration_edit', 'ACL - Editing files and dirs in admin area', 4),
(39, 'users_view_profile', 'ACL - Viewing users profiles', 2),
(40, 'pages_administration_list', 'ACL - Viewing pages in admin area', 5),
(41, 'pages_administration_ajax_view_dependent_pages', 'ACL - Viewing dependent pages in admin area', 5),
(42, 'pages_administration_delete_pages', 'ACL - Deleting pages in admin area', 5),
(43, 'pages_administration_system_pages', 'ACL - Viewing system pages in admin area', 5),
(45, 'pages_administration_add_system_pages', 'ACL - Adding system pages in admin area', 5),
(46, 'pages_administration_add_custom_page', 'ACL - Adding custom pages in admin area', 5),
(47, 'pages_administration_edit_page', 'ACL - Editing pages in admin area', 5),
(48, 'pages_administration_ajax_add_widget', 'ACL - Adding widgets on pages in admin area', 5),
(49, 'pages_administration_browse_widgets', 'ACL - Browsing widgets in admin area', 5),
(50, 'pages_administration_ajax_change_widget_position', 'ACL - Changing widgets positions on pages in admin area', 5),
(51, 'pages_administration_ajax_change_page_layout', 'ACL - Changing page layout on the widgets page in admin area', 5),
(52, 'pages_administration_ajax_delete_widget', 'ACL - Deleting widgets on pages in admin area', 5),
(53, 'pages_administration_ajax_view_dependent_widgets', 'ACL - Viewing dependent widgets in admin area', 5),
(54, 'pages_administration_edit_widget_settings', 'ACL - Editing widgets settings in admin area', 5),
(55, 'pages_administration_settings', 'ACL - Editing pages settings in admin area', 5),
(56, 'modules_administration_list_installed', 'ACL - Viewing installed modules in admin area', 1),
(57, 'modules_administration_list_not_installed', 'ACL - Viewing not installed modules in admin area', 1),
(58, 'modules_administration_upload', 'ACL - Uploading new modules in admin area', 1),
(59, 'modules_administration_ajax_view_module_description', 'ACL - Viewing modules description in admin area', 1),
(60, 'modules_administration_ajax_view_dependent_modules', 'ACL - Viewing dependent modules in admin area', 1),
(61, 'modules_administration_ajax_view_module_system_requirements', 'ACL - Viewing modules system requirements in admin area', 1),
(62, 'modules_administration_install', 'ACL - Installing modules in admin area', 1),
(63, 'modules_administration_uninstall', 'ACL - Uninstalling modules in admin area', 1),
(64, 'modules_administration_activate', 'ACL - Activating modules in admin area', 1),
(65, 'modules_administration_deactivate', 'ACL - Deactivating modules in admin area', 1),
(66, 'modules_administration_upload_updates', 'ACL - Uploading  updates of modules in admin area', 1),
(67, 'modules_administration_delete', 'ACL - Deleting modules in admin area', 1);

CREATE TABLE `acl_resource_connection` (
    `id` MEDIUMINT(8) UNSIGNED NOT NULL AUTO_INCREMENT,
    `role` SMALLINT(5) UNSIGNED NOT NULL,
    `resource` SMALLINT(5) UNSIGNED NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY (`role`, `resource`),
    FOREIGN KEY (`role`) REFERENCES `acl_role`(`id`)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    FOREIGN KEY (`resource`) REFERENCES `acl_resource`(`id`)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

INSERT INTO `acl_resource_connection` (`id`, `role`, `resource`) VALUES
(1,  3, 39),
(2,  2, 39);

CREATE TABLE `user_list` (
    `user_id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `nick_name` VARCHAR(50) NOT NULL DEFAULT '',
    `slug` VARCHAR(100) NOT NULL DEFAULT '',
    `status` ENUM('approved','disapproved') NOT NULL,
    `email` VARCHAR(50) NOT NULL DEFAULT '',
    `phone` VARCHAR(50) DEFAULT NULL,
    `first_name` VARCHAR(100) DEFAULT NULL,
    `last_name` VARCHAR(100) DEFAULT NULL,
    `address` VARCHAR(100) DEFAULT NULL,
    `password` VARCHAR(40) NOT NULL DEFAULT '',
    `salt` VARCHAR(20) NOT NULL DEFAULT '',
    `role` SMALLINT(5) UNSIGNED DEFAULT NULL,
    `language` CHAR(2) DEFAULT NULL,
    `time_zone` SMALLINT(5) UNSIGNED DEFAULT NULL,
    `layout` SMALLINT(5) UNSIGNED DEFAULT NULL,
    `api_key` VARCHAR(50) NOT NULL DEFAULT '',
    `api_secret` VARCHAR(50) NOT NULL DEFAULT '',
    `registered` INT(10) UNSIGNED DEFAULT NULL,
    `activation_code` VARCHAR(20) DEFAULT NULL,
    `avatar` VARCHAR(100) DEFAULT NULL,
    `date_edited` DATE DEFAULT NULL,
    PRIMARY KEY (`user_id`),
    UNIQUE KEY `nick_name` (`nick_name`),
    UNIQUE KEY `email` (`email`),
    UNIQUE KEY `api_key` (`api_key`),
    UNIQUE KEY `slug` (`slug`),
    KEY `status` (`status`),
    FOREIGN KEY (`role`) REFERENCES `acl_role`(`id`)
        ON UPDATE CASCADE
        ON DELETE SET NULL,
    FOREIGN KEY (`language`) REFERENCES `localization_list`(`language`)
        ON UPDATE CASCADE
        ON DELETE SET NULL,
    FOREIGN KEY (`time_zone`) REFERENCES `application_time_zone`(`id`)
        ON UPDATE CASCADE
        ON DELETE SET NULL,
    FOREIGN KEY (`layout`) REFERENCES `layout_list`(`id`)
        ON UPDATE CASCADE
        ON DELETE SET NULL       
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

INSERT INTO `user_list` (`user_id`, `nick_name`, `slug`, `status`, `email`, `password`, `salt`, `role`, `api_key`, `api_secret`, `registered`) VALUES
(1, '__admin_nick_name_value__', '__admin_nick_name_slug_value__', 'approved', '__admin_email_value__', '__admin_password_value__', '__admin_password_salt_value__', 1, '__admin_api_key_value__', '__admin_api_secret_value__', '__admin_registered_value__');

CREATE TABLE `acl_resource_connection_setting` (
    `id` MEDIUMINT(8) UNSIGNED NOT NULL AUTO_INCREMENT,
    `connection_id` MEDIUMINT(8) UNSIGNED NOT NULL,
    `user_id` INT(10) UNSIGNED DEFAULT NULL,
    `date_start` INT(10) UNSIGNED DEFAULT NULL,
    `date_end` INT(10) UNSIGNED DEFAULT NULL,
    `actions_limit` MEDIUMINT(5) UNSIGNED DEFAULT NULL,
    `actions_reset` MEDIUMINT(5) UNSIGNED DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `setting` (`connection_id`, `user_id`),
    FOREIGN KEY (`connection_id`) REFERENCES `acl_resource_connection`(`id`)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `user_list`(`user_id`)
      ON UPDATE CASCADE
      ON DELETE CASCADE  
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `acl_resource_action_track` (
    `id` MEDIUMINT(8) UNSIGNED NOT NULL AUTO_INCREMENT,
    `connection_id` MEDIUMINT(8) UNSIGNED NOT NULL,
    `user_id` INT(10) UNSIGNED DEFAULT NULL,
    `actions` INT(10) UNSIGNED NOT NULL,
    `actions_last_reset` INT(10) UNSIGNED NOT NULL,
    PRIMARY KEY (`id`),
    KEY `actions_last_reset` (`actions_last_reset`),
    FOREIGN KEY (`connection_id`) REFERENCES `acl_resource_connection`(`id`)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `user_list`(`user_id`)
      ON UPDATE CASCADE
      ON DELETE CASCADE  
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `application_setting_category` (
    `id` SMALLINT(5) UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(50) NOT NULL DEFAULT '',
    `module` SMALLINT(5) UNSIGNED NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `category` (`name`, `module`),
    FOREIGN KEY (`module`) REFERENCES `application_module`(`id`)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `application_setting_category` (`id`, `name`, `module`) VALUES
(1,   'Main settings', 1),
(2,   'Cache', 1),
(3,   'Captcha', 1),
(4,   'Calendar', 1),
(5,   'SEO', 1),
(6,   'Pagination', 1),
(7,   'Email notifications settings', 1),
(8,   'Main settings', 2),
(9,   'Email notifications', 2),
(10,  'Avatar', 2),
(11,  'Errors logging', 1),
(12,  'Main settings', 4),
(14,  'Filters', 4),
(15,  'Embedded mode', 4),
(16,  'View images', 4),
(17,  'Localization', 1),
(18,  'Main settings', 5),
(19,  'Navigation', 5),
(20,  'Xml map', 5),
(21,  'Visibility settings', 5),
(22,  'Widgets', 5),
(23,  'Site disabling', 1);

CREATE TABLE `application_setting` (
    `id` SMALLINT(5) UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(50) NOT NULL,
    `label` VARCHAR(150) DEFAULT NULL,
    `description` VARCHAR(255) DEFAULT NULL,
    `description_helper` TEXT DEFAULT NULL,
    `type` ENUM('text', 'integer', 'float', 'email', 'textarea', 'password', 'radio', 'select', 'multiselect', 'checkbox', 'multicheckbox', 'url', 'date', 'date_unixtime', 'htmlarea', 'notification_title', 'notification_message', 'system') NOT NULL,
    `required` TINYINT(1) UNSIGNED DEFAULT NULL,
    `order` SMALLINT(5) DEFAULT NULL,
    `category` SMALLINT(5) UNSIGNED DEFAULT NULL,
    `module` SMALLINT(5) UNSIGNED NOT NULL,
    `language_sensitive` TINYINT(1) NULL DEFAULT '1',
    `values_provider` VARCHAR(255) DEFAULT NULL,
    `check` TEXT DEFAULT NULL,
    `check_message` VARCHAR(150) DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `name` (`name`),
    FOREIGN KEY (`category`) REFERENCES `application_setting_category`(`id`)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    FOREIGN KEY (`module`) REFERENCES `application_module`(`id`)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

INSERT INTO `application_setting` (`id`, `name`, `label`, `description`, `type`, `required`, `order`, `category`, `module`, `language_sensitive`, `values_provider`, `check`, `check_message`) VALUES
(1,  'application_generator', NULL, NULL, 'system', NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL),
(2,  'application_generator_version', NULL, NULL, 'system', NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL),
(3,  'application_site_name', 'Site name', 'This value will be visible in a browser title', 'text', 1, 1, 1, 1, 1, NULL, NULL, NULL),
(4,  'application_site_email', 'Site email', NULL, 'email', 1, 1, 1, 1, NULL, NULL, NULL, NULL),
(5,  'application_meta_description', 'Meta description', NULL, 'text', NULL, 3, 5, 1, 1, NULL, NULL, NULL),
(6,  'application_meta_keywords', 'Meta keywords', NULL, 'text', NULL, 4, 5, 1, 1, NULL, NULL, NULL),
(7,  'application_js_cache', 'Enable js cache', NULL, 'checkbox', NULL, 5, 2, 1, NULL, NULL, NULL, NULL),
(8,  'application_js_cache_gzip', 'Enable gzip for js cache', NULL, 'checkbox', NULL, 6, 2, 1, NULL, NULL, NULL, NULL),
(9,  'application_css_cache', 'Enable css cache', NULL, 'checkbox', NULL, 7, 2, 1, NULL, NULL, NULL, NULL),
(10, 'application_css_cache_gzip', 'Enable gzip for css cache', NULL, 'checkbox', NULL, 8, 2, 1, NULL, NULL, NULL, NULL),
(11, 'application_captcha_width', 'Captcha width', NULL, 'integer', 1, 9, 3, 1, NULL, NULL, 'return intval(''__value__'') > 0;', 'Value should be greater than 0'),
(12, 'application_captcha_height', 'Captcha height', NULL, 'integer', 1, 10, 3, 1, NULL, NULL, 'return intval(''__value__'') > 0;', 'Value should be greater than 0'),
(13, 'application_captcha_dot_noise', 'Captcha dot noise level', NULL, 'integer', 1, 11, 3, 1, NULL, NULL, 'return intval(''__value__'') > 0;', 'Value should be greater than 0'),
(14, 'application_captcha_line_noise', 'Captcha line noise level', NULL, 'integer', 1, 12, 3, 1, NULL, NULL, 'return intval(''__value__'') > 0;', 'Value should be greater than 0'),
(15, 'application_calendar_min_year', 'Min year in calendar', NULL, 'integer', 1, 1, 4, 1, NULL, NULL, 'return intval(''__value__'') >= 1902 and intval(''__value__'') <= 2037;', 'Year should be in range from 1902 to 2037'),
(16, 'application_calendar_max_year', 'Max year in calendar', NULL, 'integer', 1, 2, 4, 1, NULL, NULL, 'return intval(''__value__'') >= 1902 and intval(''__value__'') <= 2037;', 'Year should be in range from 1902 to 2037'),
(17, 'application_default_date_format', 'Default date format', NULL, 'select', 1, 3, 1, 1, 1, NULL, NULL, NULL),
(18, 'application_default_time_zone', 'Default time zone', NULL, 'select', 1, 4, 1, 1, NULL, '$timeZones = Application\\Service\\ApplicationTimeZone::getTimeZones(); return array_combine($timeZones, $timeZones);', NULL, NULL),
(19, 'application_per_page', 'Default per page value', NULL, 'integer', 1, 1, 6, 1, NULL, NULL, 'return intval(''__value__'') > 0;', 'Default per page value should be greater than 0'),
(20, 'application_min_per_page_range', 'Min per page range', NULL, 'integer', 1, 2, 6, 1, NULL, NULL, 'return intval(''__value__'') > 0;', 'Min per page range should be greater than 0'),
(21, 'application_max_per_page_range', 'Max per page range', NULL, 'integer', 1, 3, 6, 1, NULL, NULL, 'return intval(''__value__'') > 0;', 'Max per page range should be greater than 0'),
(22, 'application_per_page_step', 'Per page step', NULL, 'integer', 1, 4, 6, 1, NULL, NULL, 'return intval(''__value__'') > 0;', 'Per page step should be greater than 0'),
(23, 'application_page_range', 'Page range', NULL, 'integer', 1, 5, 6, 1, NULL, NULL, 'return intval(''__value__'') > 0;', 'Page range should be greater than 0'),
(24, 'application_dynamic_cache', 'Dynamic cache engine', 'It used for caching  template paths, language translations, etc', 'select', NULL, 1, 2, 1, NULL, NULL, 'switch(''__value__'') {\r\n    case ''xcache'' :\r\n        return extension_loaded(''xcache'');\r\n    case ''wincache'' :\r\n        return extension_loaded(''wincache'');\r\n    case ''apc'' :\r\n        return (version_compare(''3.1.6'', phpversion(''apc'')) > 0) || !ini_get(''apc.enabled'') ? false : true;\r\n    default :\r\n        $v = (string) phpversion(''memcached'');\r\n        $extMemcachedMajorVersion = ($v !== '''') ? (int) $v[0] : 0;\r\n\r\n        return $extMemcachedMajorVersion < 1 ? false : true;\r\n}', 'Extension is not loaded'),
(25, 'application_dynamic_cache_life_time', 'Dynamic cache life time', NULL, 'integer', 1, 2, 2, 1, NULL, NULL, 'return intval(''__value__'') > 0;', 'Value should be greater than 0'),
(26, 'application_memcache_host', 'Memcache host', NULL, 'text', NULL, 3, 2, 1, NULL, NULL, NULL, NULL),
(27, 'application_memcache_port', 'Memcache port', NULL, 'integer', NULL, 4, 2, 1, NULL, NULL, NULL, NULL),
(28, 'application_notification_from', 'From', NULL, 'email', 1, 1, 7, 1, NULL, NULL, NULL, NULL),
(29, 'application_use_smtp', 'Use SMTP', NULL, 'checkbox', NULL, 2, 7, 1, NULL, NULL, NULL, NULL),
(30, 'application_smtp_host', 'SMTP host', NULL, 'text', NULL, 3, 7, 1, NULL, NULL, NULL, NULL),
(31, 'application_smtp_port', 'SMTP port', NULL, 'integer', NULL, 4, 7, 1, NULL, NULL, NULL, NULL),
(32, 'application_smtp_user', 'SMTP user', NULL, 'text', NULL, 5, 7, 1, NULL, NULL, NULL, NULL),
(33, 'application_smtp_password', 'SMTP password', NULL, 'text', NULL, 6, 7, 1, NULL, NULL, NULL, NULL),
(34, 'user_nickname_min', 'User\'s min nickname length', NULL, 'integer', 1, 1, 8, 2, NULL, NULL, 'return intval(''__value__'') > 0;', 'Value should be greater than 0'),
(35, 'user_nickname_max', 'User\'s max nickname length', NULL, 'integer', 1, 2, 8, 2, NULL, NULL, 'return intval(''__value__'') > 0 && intval(''__value__'') <= 40;', 'Nickname should be greater than 0 and less or equal than 40'),
(36, 'user_approved_title', 'User approved title', 'An account approve notification', 'notification_title', 1, 1, 9, 2, 1, NULL, NULL, NULL),
(37, 'user_approved_message', 'User approved message', NULL, 'notification_message', 1, 2, 9, 2, 1, NULL, NULL, NULL),
(38, 'user_disapproved_title', 'User disapproved title', 'An account disapprove notification', 'notification_title', 1, 3, 9, 2, 1, NULL, NULL, NULL),
(39, 'user_disapproved_message', 'User disapproved message', NULL, 'notification_message', 1, 4, 9, 2, 1, NULL, NULL, NULL),
(40, 'user_deleted_title', 'User deleted title', 'An account delete notification', 'notification_title', 1, 6, 9, 2, 1, NULL, NULL, NULL),
(41, 'user_deleted_message', 'User deleted message', NULL, 'notification_message', 1, 7, 9, 2, 1, NULL, NULL, NULL),
(42, 'user_allow_register', 'Allow users register', NULL, 'checkbox', NULL, 3, 8, 2, NULL, NULL, NULL, NULL),
(43, 'user_auto_confirm', 'Users auto confirm registrations', NULL, 'checkbox', NULL, 4, 8, 2, NULL, NULL, NULL, NULL),
(44, 'user_email_confirmation_title', 'Email confirmation title', 'An account confirm email notification', 'notification_title', 1, 8, 9, 2, 1, NULL, NULL, NULL),
(45, 'user_email_confirmation_message', 'Email confirmation message', NULL, 'notification_message', 1, 9, 9, 2, 1, NULL, NULL, NULL),
(46, 'user_registered_send', 'Send notification about users registrations', NULL, 'checkbox', NULL, 10, 9, 2, NULL, NULL, NULL, NULL),
(47, 'user_registered_title', 'Register a new user title', 'An account register email notification', 'notification_title', 1, 11, 9, 2, 1, NULL, NULL, NULL),
(48, 'user_registered_message', 'Register a new user message', NULL, 'notification_message', 1, 12, 9, 2, 1, NULL, NULL, NULL),
(49, 'user_deleted_send', 'Send notification about users deletions', NULL, 'checkbox', NULL, 5, 9, 2, NULL, NULL, NULL, NULL),
(50, 'user_reset_password_title', 'Reset password confirmation title', 'An account confirm reset password notification', 'notification_title', 1, 13, 9, 2, 1, NULL, NULL, NULL),
(51, 'user_reset_password_message', 'Reset password confirmation message', NULL, 'notification_message', 1, 14, 9, 2, 1, NULL, NULL, NULL),
(52, 'user_password_reseted_title', 'Password reseted title', 'An account password reseted notification', 'notification_title', 1, 15, 9, 2, 1, NULL, NULL, NULL),
(53, 'user_password_reseted_message', 'Password reseted message', NULL, 'notification_message', 1, 16, 9, 2, 1, NULL, NULL, NULL),
(54, 'user_avatar_width', 'Avatar width', NULL, 'integer', 1, 1, 10, 2, NULL, NULL, 'return intval(''__value__'') > 0;', 'Value should be greater than 0'),
(55, 'user_avatar_height', 'Avatar height', NULL, 'integer', 1, 2, 10, 2, NULL, NULL, 'return intval(''__value__'') > 0;', 'Value should be greater than 0'),
(56, 'user_thumbnail_width', 'Thumbnail width', NULL, 'integer', 1, 3, 10, 2, NULL, NULL, 'return intval(''__value__'') > 0;', 'Value should be greater than 0'),
(57, 'user_thumbnail_height', 'Thumbnail height', NULL, 'integer', 1, 4, 10, 2, NULL, NULL, 'return intval(''__value__'') > 0;', 'Value should be greater than 0'),
(58, 'application_errors_notification_email', 'Errors notification email', NULL, 'email', NULL, 1, 11, 1, NULL, NULL, NULL, NULL),
(59, 'application_error_notification_title', 'Error notification title', 'An error email notification', 'notification_title', 1, 2, 11, 1, 1, NULL, NULL, NULL),
(60, 'application_error_notification_message', 'Error notification message', NULL, 'notification_message', 1, 3, 11, 1, 1, NULL, NULL, NULL),
(61, 'file_manager_allowed_extensions', 'Allowed file extensions', 'You should separate values by a comma', 'textarea', 1, 1, 12, 4, NULL, NULL, NULL, NULL),
(62, 'file_manager_allowed_size', 'Allowed file size', 'You should enter size in bytes', 'integer', 1, 2, 12, 4, NULL, NULL, 'return intval(''__value__'') > 0;', 'Value should be greater than 0'),
(64, 'file_manager_file_name_length', 'The maximum length of the file name and directory', NULL, 'integer', 1, 2, 12, 4, NULL, NULL, 'return intval(''__value__'') > 0;', 'Value should be greater than 0'),
(65, 'file_manager_image_extensions', 'Image extensions', 'It helps to filter only images files. You should separate values by a comma', 'textarea', 1, 1, 14, 4, NULL, NULL, NULL, NULL),
(66, 'file_manager_media_extensions', 'Media extensions', 'It helps to filter only media files. You should separate values by a comma', 'textarea', 1, 2, 14, 4, NULL, NULL, NULL, NULL),
(67, 'file_manager_window_width', 'Window width', NULL, 'integer', 1, 1, 15, 4, NULL, NULL, 'return intval(''__value__'') > 0;', 'Value should be greater than 0'),
(68, 'file_manager_window_height', 'Window height', NULL, 'integer', 1, 2, 15, 4, NULL, NULL, 'return intval(''__value__'') > 0;', 'Value should be greater than 0'),
(69, 'file_manager_window_image_width', 'Window width', NULL, 'integer', 1, 1, 16, 4, NULL, NULL, 'return intval(''__value__'') > 0;', 'Value should be greater than 0'),
(70, 'file_manager_window_image_height', 'Window height', NULL, 'integer', 1, 2, 16, 4, NULL, NULL, 'return intval(''__value__'') > 0;', 'Value should be greater than 0'),
(71, 'user_session_time', 'User\'s session time in seconds', 'This used when users select an option - "remember me"', 'integer', 1, 5, 8, 2, NULL, NULL, 'return intval(''__value__'') > 0;', 'Value should be greater than 0'),
(72, 'application_localization_cookie_time', 'Localization\'s cookie time', 'The storage time of the selected language', 'integer', 1, 1, 17, 1, NULL, NULL, 'return intval(''__value__'') > 0;', 'Value should be greater than 0'),
(73, 'user_role_edited_send', 'Send notifications about editing users roles', NULL, 'checkbox', NULL, 17, 9, 2, NULL, NULL, NULL, NULL),
(74, 'user_role_edited_title', 'User role edited title', 'An account role edited notification', 'notification_title', 1, 18, 9, 2, 1, NULL, NULL, NULL),
(75, 'user_role_edited_message', 'User role edited message', NULL, 'notification_message', 1, 18, 9, 2, 1, NULL, NULL, NULL),
(76, 'page_footer_menu_max_rows', 'Max rows in footer menu per column', NULL, 'integer', 1, 7, 19, 5, NULL, NULL, 'return intval(''__value__'') > 0;', 'Value should be greater than 0'),
(77, 'application_smtp_ssl', 'SMTP SSL', NULL, 'select', NULL, 6, 7, 1, NULL, NULL, NULL, NULL),
(78, 'application_notifications_count', 'Count of notifications sent per time', NULL, 'integer', 1, 1, 7, 1, NULL, NULL, 'return intval(''__value__'') > 0;', 'Value should be greater than 0'),
(79, 'application_smtp_login', 'SMTP login type', NULL, 'select', NULL, 7, 7, 1, NULL, NULL, NULL, NULL),
(80, 'page_new_pages_active', 'Default all new pages are active', NULL, 'checkbox', NULL, 1, 18, 5, NULL, NULL, NULL, NULL),
(81, 'page_new_pages_layout', 'Default pages layout', NULL, 'select', 1, 2, 18, 5, NULL, 'return Page\\Service\\Page::getPageLayouts();', NULL, NULL),
(82, 'page_new_pages_in_main_menu', 'Default show all new pages in the main menu', NULL, 'checkbox', NULL, 1, 19, 5, NULL, NULL, NULL, NULL),
(83, 'page_new_pages_in_site_map', 'Default show all new pages in the site map', NULL, 'checkbox', NULL, 2, 19, 5, NULL, NULL, NULL, NULL),
(84, 'page_new_pages_in_footer_menu', 'Default show all new pages in the footer menu', NULL, 'checkbox', NULL, 3, 19, 5, NULL, NULL, NULL, NULL),
(85, 'page_new_pages_footer_menu_order', 'Default order in the footer menu', NULL, 'integer', NULL, 4, 19, 5, NULL, NULL, NULL, NULL),
(86, 'page_new_pages_in_user_menu', 'Default show all new pages in the user menu', NULL, 'checkbox', NULL, 5, 19, 5, NULL, NULL, NULL, NULL),
(87, 'page_new_pages_user_menu_order', 'Default order in the user menu', NULL, 'integer', NULL, 6, 19, 5, NULL, NULL, NULL, NULL),
(88, 'page_new_pages_in_xml_map', 'Default show all new pages in the XML map', NULL, 'checkbox', NULL, 1, 20, 5, NULL, NULL, NULL, NULL),
(89, 'page_new_pages_xml_map_update', 'Default new pages update frequency', NULL, 'select', 1, 2, 20, 5, NULL, NULL, NULL, NULL),
(90, 'page_new_pages_xml_map_priority', 'Default new pages priority', 'Xml map priority description', 'float', 1, 3, 20, 5, NULL, NULL, '$value =  (float) Localization\\Utility\\LocalizationLocale::convertFromLocalizedValue(''__value__'', ''float''); return $value >= 0 && $value <= 1;', 'Enter a correct priority value'),
(91, 'page_new_pages_hidden_for', 'Default new pages are hidden for', NULL, 'multicheckbox', NULL, 1, 21, 5, NULL, 'return Acl\\Service\\Acl::getAclRoles(false, true);', NULL, NULL),
(92, 'page_new_widgets_layout', 'Default widgets layout', NULL, 'select', NULL, 1, 22, 5, NULL, 'return Page\\Service\\Page::getWidgetLayouts();', NULL, NULL),
(93, 'application_disable_site', 'Disable the site', 'Disable your website front-end and display a message to your visitors while still allowing back-end access', 'checkbox', NULL, 23, 23, 1, 1, NULL, NULL, NULL),
(94, 'application_disable_site_message', 'Message', NULL, 'htmlarea', 1, 24, 23, 1, 1, NULL, NULL, NULL),
(95, 'application_disable_site_acl', 'Allowed ACL roles', 'Members included in the list of allowed ACL roles can view the site', 'multiselect', NULL, 25, 23, 1, 1, 'return Acl\\Service\\Acl::getAclRoles(false, true);', NULL, NULL),
(96, 'application_disable_site_ip', 'Allowed IPs', 'IP list separated by commas', 'textarea', NULL, 26, 23, 1, 1, NULL, NULL, NULL);

CREATE TABLE `application_setting_value` (
    `id` SMALLINT(5) UNSIGNED NOT NULL AUTO_INCREMENT,
    `setting_id` SMALLINT(5) UNSIGNED NOT NULL,
    `value` TEXT NOT NULL,
    `language` CHAR(2) DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `setting` (`setting_id`, `language`),
    FOREIGN KEY (`setting_id`) REFERENCES `application_setting`(`id`)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    FOREIGN KEY (`language`) REFERENCES `localization_list`(`language`)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `application_setting_value` (`id`, `setting_id`, `value`, `language`) VALUES
(1,  1,  '__cms_name_value__', NULL),
(2,  2,  '__cms_version_value__', NULL),
(3,  3,  '__cms_name_value__', NULL),
(4,  4,  '__site_email_value__', NULL),
(5,  5,  '', NULL),
(6,  6,  '', NULL),
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
(24, 24, '__dynamic_cache_value__', NULL),
(25, 25, '1800', NULL),
(26, 26, '__memcache_host_value__', NULL),
(27, 27, '__memcache_port_value__', NULL),
(28, 28, 'no_reply@mysite.com', NULL),
(29, 34, '3', NULL),
(30, 35, '15', NULL),
(31, 36, 'Your profile is now active', NULL),
(32, 37, '<p><b>Dear __RealName__</b>,</p>\r\n<p>Your profile was reviewed and activated!</p>\r\n<p>Your E-mail: __Email__</p>', NULL),
(33, 36, 'Ваш профиль сейчас активен', 'ru'),
(34, 37, '<p><b>Уважаемый(я) __RealName__</b>,</p>\r\n<p>Ваш профиль был рассмотрен и активирован!</p>\r\n<p>Ваш адрес электронной почты: __Email__</p>', 'ru'),
(35, 38, 'Your profile is now deactived', NULL),
(36, 39, '<p><b>Dear __RealName__</b>,</p>\r\n<p>Your profile was deactivated!</p>\r\n<p>Please contact with support team.</p>\r\n<p>Your E-mail: __Email__</p>', NULL),
(37, 38, 'Ваш профиль сейчас дезактивирован', 'ru'),
(38, 39, '<p><b>Уважаемый(я) __RealName__</b>,</p>\r\n<p>Ваш профиль был дезактивирован!</p>\r\n<p>Пожалуйста, свяжитесь с командой поддержки.</p>\r\n<p>Ваш адрес электронной почты: __Email__</p>', 'ru'),
(39, 40, 'Your profile deleted', NULL),
(40, 41, '<p><b>Dear __RealName__</b>,</p>\r\n<p>Your profile was deleted!</p>', NULL),
(41, 40, 'Ваш профиль удален', 'ru'),
(42, 41, '<p><b>Уважаемый(я) __RealName__</b>,</p>\r\n<p>Ваш профиль был удален!</p>', 'ru'),
(43, 42,  '1', NULL),
(44, 43,  '1', NULL),
(45, 44, 'Email confirmation request', NULL),
(46, 44, 'Запрос на подтверждение E-mail', 'ru'),
(47, 45, '<p><b>Dear __RealName__</b>,</p>\r\n<p>Thank you for registering at __SiteName__!</p>\r\n<p>Click to confirm your email: <a href="__ConfirmationLink__">__ConfirmationLink__</a></p>\r\n<p>Confirmation code: <b>__ConfCode__</b></p>', NULL),
(48, 45, '<p><b>Уважаемый(я) __RealName__</b>,</p>\r\n<p>Спасибо за регистрацию на __SiteName__!</p>\r\n<p>Нажмите, чтобы подтвердить адрес электронной почты: <a href="__ConfirmationLink__">__ConfirmationLink__</a></p>\r\n<p>Код подтверждения: <b>__ConfCode__</b></p>', 'ru'),
(49, 47, 'A new user registered', NULL),
(50, 47, 'Новый пользователь зарегистрирован', 'ru'),
(51, 48, '<p>The new user\'s name: <b>__RealName__</b></p>\r\n<p>The user\'s email: <b>__Email__</b></p>', NULL),
(52, 48, '<p>Имя нового пользователя: <b>__RealName__</b></p>\r\n<p>E-mail пользователя: <b>__Email__</b></p>', 'ru'),
(53, 46,  '1', NULL),
(54, 49,  '1', NULL),
(55, 50, 'Password reset confirmation request', NULL),
(56, 50, 'Запрос на подтверждение сброса пароля', 'ru'),
(57, 51, '<p><b>Dear __RealName__</b>,</p>\r\n<p>Click to confirm your password reset: <a href="__ConfirmationLink__">__ConfirmationLink__</a></p>\r\n<p>Confirmation code: <b>__ConfCode__</b></p>', NULL),
(58, 51, '<p><b>Уважаемый(я) __RealName__</b>,</p>\r\n<p>Нажмите, чтобы подтвердить свой сброс пароля: <a href="__ConfirmationLink__">__ConfirmationLink__</a></p>\r\n<p>Код подтверждения: <b>__ConfCode__</b></p>', 'ru'),
(59, 52, 'Your password was reset', NULL),
(60, 52, 'Ваш пароль был сброшен', 'ru'),
(61, 53, '<p><b>Dear __RealName__</b>,</p>\r\n<p>Now your new password is: <b>__Password__</b></p>', NULL),
(62, 53, '<p><b>Уважаемый(я) __RealName__</b>,</p>\r\n<p>Теперь ваш новый пароль: <b>__Password__</b></p>', 'ru'),
(63, 54,  '200', NULL),
(64, 55,  '200', NULL),
(65, 56,  '100', NULL),
(66, 57,  '100', NULL),
(67, 59, 'An error occurred', NULL),
(68, 59, 'Произошла ошибка', 'ru'),
(69, 60, '<p><b>Error description:</b></p>\r\n<p>__ErrorDescription__</p>', NULL),
(70, 60, '<p><b>Описание ошибки:</b></p>\r\n<p>__ErrorDescription__</p>', 'ru'),
(71, 61, 'bmp,gif,jpg,png,mp3,wav,wma,3g2,3gp,avi,flv,mov,mp4,mpg,swf,vob,wmv,zip,rar,txt,doc,docx,pdf', NULL),
(72, 62, '2097152', NULL),
(74, 64, '20', NULL),
(75, 65, 'bmp,gif,jpg,png', NULL),
(76, 66, 'mp3,wav,wma,3g2,3gp,avi,flv,mov,mp4,mpg,swf,vob,wmv', NULL),
(77, 67, '1000', NULL),
(78, 68, '500', NULL),
(79, 69, '500', NULL),
(80, 70, '300', NULL),
(81, 71, '7776000', NULL),
(82, 72, '6912000', NULL),
(83, 73, '1', NULL),
(84, 74, 'Your role was edited', NULL),
(85, 74, 'Ваша роль была отредактирована', 'ru'),
(86, 75, '<p><b>Dear __RealName__</b>,</p>\r\n<p>Now your role on the site is: <b>__Role__</b></p>', NULL),
(87, 75, '<p><b>Уважаемый(я) __RealName__</b>,</p>\r\n<p>Теперь ваша роль на сайте: <b>__Role__</b></p>', 'ru'),
(88, 76, '5', NULL),
(89, 77, 'tls', NULL),
(90, 78, '10', NULL),
(91, 31, '587', NULL),
(92, 79, 'plain', NULL),
(93, 80, '1', NULL),
(94, 81, '1', NULL),
(95, 82, '1', NULL),
(96, 83, '1', NULL),
(97, 85, '100', NULL),
(98, 87, '100', NULL),
(99, 88, '1', NULL),
(100, 89, 'weekly', NULL),
(101, 90, '0.5', NULL),
(102, 92, '1', NULL),
(103, 46,  '1', NULL),
(104, 94,  '<p style="text-align: center;">Website is currently unavailable.</p>', NULL),
(105, 94,  '<p style="text-align: center;">Веб-сайт в настоящее время недоступен.</p>', 'ru');

CREATE TABLE `application_setting_predefined_value` (
    `setting_id` SMALLINT(5) UNSIGNED NOT NULL,
    `value` VARCHAR(255) NOT NULL,
    PRIMARY KEY (`setting_id`, `value`),
    FOREIGN KEY (`setting_id`) REFERENCES `application_setting`(`id`)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `application_setting_predefined_value` (`setting_id`, `value`) VALUES
(17, 'full'),
(17, 'long'),
(17, 'medium'),
(17, 'short'),
(24, 'memcached'),
(24, 'apc'),
(24, 'xcache'),
(24, 'wincache'),
(77, 'tls'),
(77, 'ssl'),
(79, 'login'),
(79, 'smtp'),
(79, 'plain'),
(79, 'crammd5'),
(89, 'always'),
(89, 'hourly'),
(89, 'daily'),
(89, 'weekly'),
(89, 'monthly'),
(89, 'yearly'),
(89, 'never');

CREATE TABLE `application_event` (
    `id` SMALLINT(5) UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `module` SMALLINT(5) UNSIGNED NOT NULL,
    `description` VARCHAR(100) NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `name` (`name`),
    FOREIGN KEY (`module`) REFERENCES `application_module`(`id`)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

INSERT INTO `application_event` (`id`, `name`, `module`, `description`) VALUES
(1,  'localization_get_localizations_via_xmlrpc', 7, 'Event - Getting localizations via XmlRpc'),
(2,  'user_login', 2, 'Event - Users sign in'),
(3,  'user_login_failed', 2, 'Event - Users logins failed'),
(4,  'user_logout', 2, 'Event - Users sign out'),
(5,  'user_get_info_via_xmlrpc', 2, 'Event - Getting user\'s info via XmlRpc'),
(6,  'user_set_timezone_via_xmlrpc', 2, 'Event - Setting users timezones via XmlRpc'),
(7,  'application_change_settings', 1, 'Event - Editing settings'),
(8,  'acl_delete_role', 1, 'Event - Deleting ACL roles'),
(9,  'acl_add_role', 1, 'Event - Adding ACL roles'),
(10, 'acl_edit_role', 1, 'Event - Editing ACL roles'),
(11, 'acl_allow_resource', 1, 'Event - Allowing ACL resources'),
(12, 'acl_disallow_resource', 1, 'Event - Disallowing ACL resources'),
(13, 'acl_edit_resource_settings', 1, 'Event - Editing ACL resources settings'),
(14, 'application_clear_cache', 1, 'Event - Clearing site\'s cache'),
(15, 'user_disapprove', 2, 'Event - Disapproving users'),
(16, 'user_approve', 2, 'Event - Approving users'),
(17, 'user_delete', 2, 'Event - Deleting users'),
(18, 'user_add', 2, 'Event - Adding users'),
(19, 'user_edit', 2, 'Event - Editing users'),
(20, 'application_send_email_notification', 1, 'Event - Sending email notifications'),
(21, 'user_reset_password', 2, 'Event - Resetting users passwords'),
(22, 'user_reset_password_request', 2, 'Event - Requesting reset users passwords'),
(23, 'user_edit_role', 2, 'Event - Editing users roles'),
(24, 'file_manager_delete_file', 4, 'Event - Deleting files'),
(25, 'file_manager_add_directory', 4, 'Event - Adding directories'),
(26, 'file_manager_delete_directory', 4, 'Event - Deleting directories'),
(27, 'file_manager_add_file', 4, 'Event - Adding files'),
(28, 'file_manager_edit_file', 4, 'Event - Editing files'),
(29, 'file_manager_edit_directory', 4, 'Event - Editing directories'),
(30, 'page_show', 5, 'Event - Showing pages'),
(31, 'user_get_info', 2, 'Event - Getting user\'s info'),
(32, 'page_delete', 5, 'Event - Deleting pages'),
(33, 'page_add', 5, 'Event - Adding pages'),
(34, 'page_edit', 5, 'Event - Editing pages'),
(35, 'page_widget_add', 5, 'Event - Adding widgets'),
(36, 'page_widget_change_position', 5, 'Event - Changing widgets positions'),
(37, 'page_widget_delete', 5, 'Event - Deleting widgets'),
(38, 'page_widget_edit_settings', 5, 'Event - Editing widgets settings'),
(39, 'application_install_custom_module', 1, 'Event - Installing custom modules'),
(40, 'application_uninstall_custom_module', 1, 'Event - Uninstalling custom modules'),
(41, 'application_activate_custom_module', 1, 'Event - Activating custom modules'),
(42, 'application_deactivate_custom_module', 1, 'Event - Deactivating custom modules'),
(43, 'application_upload_custom_module', 1, 'Event - Uploading custom modules'),
(44, 'application_upload_module_updates', 1, 'Event - Uploading modules updates'),
(45, 'application_delete_custom_module', 1, 'Event - Deleting custom modules'),
(46, 'layout_delete', 6, 'Event - Deleting layouts'),
(47, 'localization_delete', 7, 'Event - Deleting localizations');

CREATE TABLE `application_admin_menu_part` (
    `id` SMALLINT(5) UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(50) NOT NULL,
    `module` SMALLINT(5) UNSIGNED NOT NULL,
    `icon` VARCHAR(100) NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `name` (`name`),
    FOREIGN KEY (`module`) REFERENCES `application_module`(`id`)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

INSERT INTO `application_admin_menu_part` (`id`, `name`, `module`, `icon`) VALUES
(1, 'System', 1, 'system_menu.png'),
(2, 'Pages', 5, 'page_menu.png'),
(3, 'Modules', 1, 'module_menu.png');

CREATE TABLE `application_admin_menu_category` (
    `id` SMALLINT(5) UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(50) NOT NULL,
    `module` SMALLINT(5) UNSIGNED NOT NULL,
    `icon` VARCHAR(100) NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `name` (`name`),
    FOREIGN KEY (`module`) REFERENCES `application_module`(`id`)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

INSERT INTO `application_admin_menu_category` (`id`, `name`, `module`, `icon`) VALUES
(1, 'Site settings', 1, 'setting_menu_item.png'),
(2, 'Access Control List', 8, 'acl_menu_item.png'),
(3, 'Users', 2, 'user_group_menu_item.png'),
(4, 'Files manager', 4, 'file_manager_menu_item.png'),
(5, 'Pages management', 5, 'page_menu_item.png'),
(6, 'Modules', 1, 'module_menu_item.png');

CREATE TABLE `application_admin_menu` (
    `id` SMALLINT(5) UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(50) NOT NULL,
    `controller` VARCHAR(50) NOT NULL,
    `action` VARCHAR(50) NOT NULL,
    `module` SMALLINT(5) UNSIGNED NOT NULL,
    `order` SMALLINT(5) NOT NULL DEFAULT '0',
    `category` SMALLINT(5) UNSIGNED NOT NULL,
    `part` SMALLINT(5) UNSIGNED NOT NULL,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`module`) REFERENCES `application_module`(`id`)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    FOREIGN KEY (`category`) REFERENCES `application_admin_menu_category`(`id`)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    FOREIGN KEY (`part`) REFERENCES `application_admin_menu_part`(`id`)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

INSERT INTO `application_admin_menu` (`id`, `name`, `controller`, `action`, `module`, `order`, `category`, `part`) VALUES
(1,  'List of settings', 'settings-administration', 'index', 1, 1, 1, 1),
(2,  'Clear cache', 'settings-administration', 'clear-cache', 1, 2, 1, 1),
(3,  'List of roles', 'acl-administration', 'list', 1, 3, 2, 1),
(4,  'List of users', 'users-administration', 'list', 2, 4, 3, 1),
(5,  'List of settings', 'users-administration', 'settings', 2, 5, 3, 1),
(6,  'List of files', 'files-manager-administration', 'list', 4, 6, 4, 1),
(7,  'List of settings', 'files-manager-administration', 'settings', 4, 7, 4, 1),
(8,  'List of pages', 'pages-administration', 'list', 5, 8, 5, 2),
(9,  'List of settings', 'pages-administration', 'settings', 5, 9, 5, 2),
(10, 'List of installed modules', 'modules-administration', 'list-installed', 1, 8, 6, 1),
(11, 'List of not installed modules', 'modules-administration', 'list-not-installed', 1, 9, 6, 1),
(12, 'Upload updates', 'modules-administration', 'upload-updates', 1, 10, 6, 1);

CREATE TABLE `page_widget_position` (
    `id` SMALLINT(5) UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(50) NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `page_widget_position` (`id`, `name`) VALUES
(1, 'head'),
(2, 'body'),
(3, 'footer'),
(4, 'content-left'),
(5, 'content-right'),
(6, 'content-top'),
(7, 'content-bottom'),
(8, 'content-middle'),
(9, 'logo');

CREATE TABLE `page_layout` (
    `id` SMALLINT(5) UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `title` VARCHAR(150) NOT NULL,
    `default_position` SMALLINT(5) UNSIGNED NOT NULL,
    `image` VARCHAR(100) NOT NULL,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`default_position`) REFERENCES `page_widget_position`(`id`)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `page_layout` (`id`, `name`, `title`, `default_position`, `image`) VALUES
(1,  'layout-1-column', '1 column', 8, 'layout-1-column.png'),
(2,  'layout-2-columns-l33-r66', '2 columns (left 33%, right 66%)', 4, 'layout-2-columns-l33-r66.png'),
(3,  'layout-2-columns-l66-r33', '2 columns (left 66%, right 33%)', 4, 'layout-2-columns-l66-r33.png'),
(4,  'layout-2-columns-l50-r50', '2 columns (left 50%, right 50%)', 4, 'layout-2-columns-l50-r50.png'),
(5,  'layout-3-columns-l33-m33-r33', '3 columns (left 33%, middle 33%, right 33%)', 4, 'layout-3-columns-l33-m33-r33.png'),
(6,  'layout-top-area-below-2-columns-l33-r66', 'top area and 2 columns below (left 33%, right 66%)', 4, 'layout-top-area-below-2-columns-l33-r66.png'),
(7,  'layout-top-area-below-2-columns-l66-r33', 'top area and 2 columns below (left 66%, right 33%)', 4, 'layout-top-area-below-2-columns-l66-r33.png'),
(8,  'layout-top-area-below-2-columns-l50-r50', 'top area and 2 columns below (left 50%, right 50%)', 4, 'layout-top-area-below-2-columns-l50-r50.png'),
(9,  'layout-top-area-below-3-columns-l33-m33-r33', 'top area and 3 columns below (left 33%, middle 33%, right 33%)', 4, 'layout-top-area-below-3-columns-l33-m33-r33.png'),
(10, 'layout-top-bottom-areas-between-2-columns-l50-r50', 'top and bottom areas and 2 columns between them (left 50%, right 50%)', 4, 'layout-top-bottom-areas-between-2-columns-l50-r50.png'),
(11, 'layout-2-columns-l50-r50-below-bottom-area', '2 columns (left 50%, right 50%) below bottom area', 4, 'layout-2-columns-l50-r50-below-bottom-area.png');

CREATE TABLE `page_widget_position_connection` (
    `id` SMALLINT(5) UNSIGNED NOT NULL AUTO_INCREMENT,
    `position_id` SMALLINT(5) UNSIGNED NOT NULL,
    `layout_id` SMALLINT(5) UNSIGNED NOT NULL,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`position_id`) REFERENCES `page_widget_position`(`id`)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    FOREIGN KEY (`layout_id`) REFERENCES `page_layout`(`id`)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `page_widget_position_connection` (`id`, `position_id`, `layout_id`) VALUES
(1,  8, 1),
(2,  4, 2),
(3,  5, 2),
(4,  4, 3),
(5,  5, 3),
(6,  4, 4),
(7,  5, 4),
(8,  4, 5),
(9,  8, 5),
(10, 5, 5),
(11, 4, 6),
(12, 5, 6),
(13, 6, 6),
(14, 4, 7),
(15, 5, 7),
(16, 6, 7),
(17, 4, 8),
(18, 5, 8),
(19, 6, 8),
(20, 4, 9),
(21, 8, 9),
(22, 5, 9),
(23, 6, 9),
(24, 4, 10),
(25, 5, 10),
(26, 6, 10),
(27, 7, 10),
(28, 4, 11),
(29, 5, 11),
(30, 7, 11);

CREATE TABLE `page_system` (
    `id` SMALLINT(5) UNSIGNED NOT NULL AUTO_INCREMENT,
    `slug` VARCHAR(100) NOT NULL,
    `title` VARCHAR(50) NOT NULL,
    `module` SMALLINT(5) UNSIGNED NOT NULL,
    `forced_visibility` TINYINT(1) UNSIGNED DEFAULT NULL,
    `disable_user_menu` TINYINT(1) UNSIGNED DEFAULT NULL,
    `disable_menu` TINYINT(1) UNSIGNED DEFAULT NULL,
    `disable_site_map` TINYINT(1) UNSIGNED DEFAULT NULL,
    `disable_footer_menu` TINYINT(1) UNSIGNED DEFAULT NULL,
    `privacy` VARCHAR(50) DEFAULT NULL,
    `disable_seo` TINYINT(1) UNSIGNED DEFAULT NULL,
    `disable_xml_map` TINYINT(1) UNSIGNED DEFAULT NULL,
    `pages_provider` VARCHAR(50) DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE (`slug`),
    FOREIGN KEY (`module`) REFERENCES `application_module`(`id`)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `page_system` (`id`, `slug`, `title`, `module`, `disable_menu`, `privacy`, `forced_visibility`, `disable_user_menu`, `disable_site_map`, `disable_footer_menu`, `disable_seo`, `disable_xml_map`, `pages_provider`) VALUES
(1,  'home', 'Home page', 5, 1, NULL, NULL, 1, 1, NULL, NULL, NULL, NULL),
(2,  'login', 'Login', 2, NULL, 'User\\PagePrivacy\\UserLoginPrivacy', 1, 1, NULL, NULL, NULL, NULL, NULL),
(3,  'user-register', 'Register', 2, NULL, 'User\\PagePrivacy\\UserRegisterPrivacy', 1, 1, NULL, NULL, NULL, NULL, NULL),
(4,  'user-forgot', 'Account recovery', 2, NULL, 'User\\PagePrivacy\\UserForgotPrivacy', 1, 1, NULL, NULL, NULL, NULL, NULL),
(5,  'user-activate', 'User activate', 2, 1, 'User\\PagePrivacy\\UserActivatePrivacy', 1, 1, 1, 1, 1, 1, NULL),
(6,  'user-password-reset', 'Password reset', 2, 1, 'User\\PagePrivacy\\UserPasswordResetPrivacy', 1, 1, 1, 1, 1, 1, NULL),
(7,  'dashboard', 'User dashboard', 2, NULL, 'User\\PagePrivacy\\UserDashboardPrivacy', 1, NULL, NULL, NULL, 1, 1, NULL),
(8,  'user-delete', 'Delete your account', 2, NULL, 'User\\PagePrivacy\\UserDeletePrivacy', 1, NULL, NULL, NULL, 1, 1, NULL),
(9,  'sitemap', 'Sitemap', 5, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(10, 'user', 'User profile', 2, 1, 'User\\PagePrivacy\\UserViewPrivacy', NULL, 1, NULL, 1, 1, NULL, 'User\\PageProvider\\UserPageProvider'),
(11, 'user-edit', 'Edit account', 2, NULL, 'User\\PagePrivacy\\UserEditPrivacy', 1, NULL, NULL, NULL, 1, 1, NULL),
(12, '404', '404 error', 5, 1, 'Page\\PagePrivacy\\Page404Privacy', 1, 1, 1, 1, 1, 1, NULL),
(13, 'contact-form', 'Contact form', 5, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

CREATE TABLE `page_widget` (
    `id` SMALLINT(5) UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(50) NOT NULL,
    `module` SMALLINT(5) UNSIGNED NOT NULL,
    `type` ENUM('system','public') NOT NULL,
    `description` VARCHAR(100) NOT NULL,
    `duplicate` TINYINT(1) UNSIGNED DEFAULT NULL,
    `forced_visibility` TINYINT(1) UNSIGNED DEFAULT NULL,
    `depend_page_id` SMALLINT(5) UNSIGNED DEFAULT NULL,
    `allow_cache` TINYINT(1) UNSIGNED DEFAULT NULL,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`module`) REFERENCES `application_module`(`id`)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    FOREIGN KEY (`depend_page_id`) REFERENCES `page_system`(`id`)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `page_widget` (`id`, `name`, `module`, `type`, `description`, `duplicate`, `forced_visibility`, `depend_page_id`, `allow_cache`) VALUES
(1,  'pageHtmlWidget', 5, 'public', 'Html', 1, NULL, NULL, 1),
(2,  'userLoginWidget', 2, 'public', 'Login', NULL, 1, NULL, NULL),
(3,  'userRegisterWidget', 2, 'public', 'Register', NULL, 1, NULL, NULL),
(4,  'userActivateWidget', 2, 'public', 'User activate', NULL, 1, NULL, NULL),
(5,  'userForgotWidget', 2, 'public', 'Account recovery', NULL, 1, NULL, NULL),
(6,  'userPasswordResetWidget', 2, 'public', 'Password reset', NULL, 1, NULL, NULL),
(7,  'userDeleteWidget', 2, 'public', 'Account delete', NULL, 1, NULL, NULL),
(8,  'pageSiteMapWidget', 5, 'public', 'Sitemap', NULL, NULL, NULL, 1),
(9,  'userInfoWidget', 2, 'public', 'Account info', NULL, 1, NULL, NULL),
(10, 'userAvatarWidget', 2, 'public', 'Account avatar', NULL, 1, NULL, NULL),
(11, 'userDashboardWidget', 2, 'public', 'User dashboard', NULL, 1, NULL, NULL),
(12, 'userDashboardUserInfoWidget', 2, 'public', 'Account info', NULL, 1, NULL, NULL),
(13, 'userEditWidget', 2, 'public', 'Account edit', NULL, 1, NULL, NULL),
(14, 'userDashboardAdministrationWidget', 2, 'public', 'Administration', NULL, 1, NULL, NULL),
(15, 'pageContactFormWidget', 5, 'public', 'Contact form', NULL, NULL, NULL, NULL),
(16, 'pageSidebarMenuWidget', 5, 'public', 'Sidebar menu', NULL, NULL, NULL, 1),
(17, 'pageShareButtonsWidget', 5, 'public', 'Share buttons', NULL, NULL, NULL, 1);

CREATE TABLE `page_system_page_depend` (
    `id` SMALLINT(5) UNSIGNED NOT NULL AUTO_INCREMENT,
    `page_id` SMALLINT(5) UNSIGNED NOT NULL,
    `depend_page_id` SMALLINT(5) UNSIGNED NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `page_depend` (`page_id`, `depend_page_id`),
    FOREIGN KEY (`page_id`) REFERENCES `page_system`(`id`)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    FOREIGN KEY (`depend_page_id`) REFERENCES `page_system`(`id`)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `page_system_page_depend` (`id`, `page_id`, `depend_page_id`) VALUES
(1, 2, 1),
(2, 3, 1),
(3, 4, 1),
(4, 4, 6),
(5, 5, 1),
(6, 6, 1),
(7, 7, 1),
(8, 8, 1),
(9, 9, 1),
(10, 10, 1),
(11, 11, 1),
(12, 12, 1),
(13, 13, 1);

CREATE TABLE `page_system_widget_hidden` (
    `id` SMALLINT(5) UNSIGNED NOT NULL AUTO_INCREMENT,
    `page_id` SMALLINT(5) UNSIGNED NOT NULL,
    `widget_id` SMALLINT(5) UNSIGNED NOT NULL,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`page_id`) REFERENCES `page_system`(`id`)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    FOREIGN KEY (`widget_id`) REFERENCES `page_widget`(`id`)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `page_system_widget_hidden` (`id`, `page_id`, `widget_id`) VALUES
(1,   7,  2),
(2,   7,  3),
(3,   8,  2),
(4,   8,  3),
(5,   8,  13),
(6,   8,  8),
(7,   10,  13),
(8,   11,  2),
(9,   11,  3),
(10,  6,  13),
(11,  3,  13),
(12,  4,  13),
(13,  5,  2),
(14,  5,  3),
(15,  5,  13),
(16,  2,  13),
(17,  12,  13),
(18,  12,  2),
(19,  12,  3);

CREATE TABLE `page_system_widget_depend` (
    `id` SMALLINT(5) UNSIGNED NOT NULL AUTO_INCREMENT,
    `page_id` SMALLINT(5) UNSIGNED NOT NULL,
    `widget_id` SMALLINT(5) UNSIGNED NOT NULL,
    `order` SMALLINT(5) NOT NULL DEFAULT '0',
    PRIMARY KEY (`id`),
    FOREIGN KEY (`page_id`) REFERENCES `page_system`(`id`)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    FOREIGN KEY (`widget_id`) REFERENCES `page_widget`(`id`)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `page_system_widget_depend` (`id`, `page_id`, `widget_id`, `order`) VALUES
(1,  2,  2,  1),
(2,  3,  3,  1),
(3,  4,  5,  1),
(4,  5,  4,  1),
(5,  6,  6,  1),
(6,  7,  11, 1),
(7,  8,  7,  1),
(8,  9,  8,  1),
(9,  7,  12, 1),
(10, 7,  14, 2),
(11, 11, 13, 1),
(12, 10, 9, 2),
(13, 10, 10, 1),
(14, 13, 15, 1);

CREATE TABLE `page_widget_page_depend` (
    `id` SMALLINT(5) UNSIGNED NOT NULL AUTO_INCREMENT,
    `page_id` SMALLINT(5) UNSIGNED NOT NULL,
    `widget_id` SMALLINT(5) UNSIGNED NOT NULL,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`page_id`) REFERENCES `page_system`(`id`)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    FOREIGN KEY (`widget_id`) REFERENCES `page_widget`(`id`)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `page_widget_page_depend` (`id`, `page_id`, `widget_id`) VALUES
(1, 5, 4),
(2, 4, 5),
(3, 6, 6),
(4, 8, 7),
(5, 10, 9),
(6, 10, 10),
(7, 7, 11),
(8, 7, 12),
(9, 7, 14);

CREATE TABLE `page_widget_depend` (
    `id` SMALLINT(5) UNSIGNED NOT NULL AUTO_INCREMENT,
    `widget_id` SMALLINT(5) UNSIGNED NOT NULL,
    `depend_widget_id` SMALLINT(5) UNSIGNED NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `widget_depend` (`widget_id`, `depend_widget_id`),
    FOREIGN KEY (`widget_id`) REFERENCES `page_widget`(`id`)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    FOREIGN KEY (`depend_widget_id`) REFERENCES `page_widget`(`id`)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `page_structure` (
    `id` SMALLINT(5) UNSIGNED NOT NULL AUTO_INCREMENT,
    `slug` VARCHAR(100) NOT NULL,
    `title` VARCHAR(50) DEFAULT NULL,
    `meta_description` VARCHAR(150) DEFAULT NULL,
    `meta_keywords` VARCHAR(150) DEFAULT NULL,
    `meta_robots` VARCHAR(50) DEFAULT NULL,
    `module` SMALLINT(5) UNSIGNED NOT NULL,
    `user_menu` TINYINT(1) UNSIGNED DEFAULT NULL,
    `user_menu_order` SMALLINT(5) NOT NULL DEFAULT '0',
    `menu` TINYINT(1) UNSIGNED DEFAULT NULL,
    `site_map` TINYINT(1) UNSIGNED DEFAULT NULL,
    `xml_map` TINYINT(1) UNSIGNED DEFAULT NULL,
    `xml_map_update` ENUM('always', 'hourly', 'daily', 'weekly', 'monthly', 'yearly', 'never') NOT NULL DEFAULT 'weekly',
    `xml_map_priority` DECIMAL(2,1) NOT NULL DEFAULT '0.5',
    `footer_menu` TINYINT(1) UNSIGNED DEFAULT NULL,
    `footer_menu_order` SMALLINT(5) NOT NULL DEFAULT '0',
    `active` TINYINT(1) UNSIGNED NULL  DEFAULT '1', 
    `type` ENUM('system','custom') NOT NULL,
    `language` CHAR(2) NOT NULL,
    `layout` SMALLINT(5) UNSIGNED NOT NULL,
    `redirect_url` VARCHAR(255) DEFAULT NULL,
    `left_key` INT(10) NOT NULL DEFAULT '0',
    `right_key` INT(10) NOT NULL DEFAULT '0',
    `level` INT(10) NOT NULL DEFAULT '0',
    `parent_id` SMALLINT(5) UNSIGNED DEFAULT NULL,
    `system_page` SMALLINT(5) UNSIGNED DEFAULT NULL,
    `date_edited` DATE DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE `slug` (`slug`, `language`),
    KEY `node` (`left_key`, `right_key`, `language`, `active`, `level`),
    KEY `footer_menu` (`footer_menu`),
    KEY `user_menu` (`user_menu`),
    KEY `parent_id` (`language`, `parent_id`),
    KEY `active` (`active`),
    KEY `redirect_url` (`redirect_url`),
    FOREIGN KEY (`module`) REFERENCES `application_module`(`id`)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    FOREIGN KEY (`language`) REFERENCES `localization_list`(`language`)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    FOREIGN KEY (`layout`) REFERENCES `page_layout`(`id`)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    FOREIGN KEY (`system_page`) REFERENCES `page_system`(`id`)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `page_visibility` (
    `id` SMALLINT(5) UNSIGNED NOT NULL AUTO_INCREMENT,
    `page_id` SMALLINT(5) UNSIGNED NOT NULL,
    `hidden` SMALLINT(5) UNSIGNED NULL,
    PRIMARY KEY (`id`),
    UNIQUE `page_id` (`page_id`, `hidden`),
    FOREIGN KEY (`hidden`) REFERENCES `acl_role`(`id`)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    FOREIGN KEY (`page_id`) REFERENCES `page_structure`(`id`)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `page_widget_layout` (
    `id` SMALLINT(5) UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(30) NOT NULL,
    `title` VARCHAR(50) NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `page_widget_layout` (`id`, `name`, `title`) VALUES
(1, 'panel', 'Panel');

CREATE TABLE `page_widget_connection` (
    `id` SMALLINT(5) UNSIGNED NOT NULL AUTO_INCREMENT,
    `title` VARCHAR(50) DEFAULT NULL,
    `page_id` SMALLINT(5) UNSIGNED NULL,
    `widget_id` SMALLINT(5) UNSIGNED NOT NULL,
    `position_id` SMALLINT(5) UNSIGNED NOT NULL,
    `layout` SMALLINT(5) UNSIGNED DEFAULT NULL,
    `order` SMALLINT(5) NOT NULL DEFAULT '0',
    `cache_ttl` INT(10) UNSIGNED NOT NULL DEFAULT '0',
    PRIMARY KEY (`id`),
    KEY `position` (`page_id`, `position_id`, `order`),
    FOREIGN KEY (`page_id`) REFERENCES `page_structure`(`id`)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    FOREIGN KEY (`widget_id`) REFERENCES `page_widget`(`id`)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    FOREIGN KEY (`position_id`) REFERENCES `page_widget_position`(`id`)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    FOREIGN KEY (`layout`) REFERENCES `page_widget_layout`(`id`)
        ON UPDATE CASCADE
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `page_widget_setting_category` (
    `id` SMALLINT(5) UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(50) NOT NULL DEFAULT '',
    `module` SMALLINT(5) UNSIGNED NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `category` (`name`, `module`),
    FOREIGN KEY (`module`) REFERENCES `application_module`(`id`)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `page_widget_setting_category` (`id`, `name`, `module`) VALUES
(1,   'Main settings', 5);

CREATE TABLE `page_widget_setting` (
    `id` SMALLINT(5) UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(50) NOT NULL,
    `widget` SMALLINT(5) UNSIGNED NOT NULL,
    `label` VARCHAR(150) DEFAULT NULL,
    `description` VARCHAR(255) DEFAULT NULL,
    `description_helper` TEXT DEFAULT NULL,
    `type` ENUM('text', 'integer', 'float', 'email', 'textarea', 'password', 'radio', 'select', 'multiselect', 'checkbox', 'multicheckbox', 'url', 'date', 'date_unixtime', 'htmlarea', 'notification_title', 'notification_message') NOT NULL,
    `required` TINYINT(1) UNSIGNED DEFAULT NULL,
    `order` SMALLINT(5) NOT NULL DEFAULT '0',
    `category` SMALLINT(5) UNSIGNED DEFAULT NULL,
    `values_provider` VARCHAR(255) DEFAULT NULL,
    `check` TEXT DEFAULT NULL,
    `check_message` VARCHAR(150) DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `name` (`name`, `widget`),
    FOREIGN KEY (`category`) REFERENCES `page_widget_setting_category`(`id`)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    FOREIGN KEY (`widget`) REFERENCES `page_widget`(`id`)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

INSERT INTO `page_widget_setting` (`id`, `name`, `widget`, `label`, `type`, `required`, `order`, `category`, `description`) VALUES
(1, 'page_html_content', 1, 'Html content', 'htmlarea', NULL, 2, 1, NULL),
(2, 'page_contact_form_email', 15, 'Email', 'email', 1, 1, 1, 'Email address which uses for receiving messages from the contact form'),
(3, 'page_contact_form_title', 15, 'Message title', 'notification_title', 1, 1, 1, 'Contact form notification'),
(4, 'page_contact_form_message', 15, 'Message', 'notification_message', 1, 1, 1, NULL),
(5, 'page_contact_form_captcha', 15, 'Show captcha', 'checkbox', NULL, 2, 1, NULL),
(6, 'page_sidebar_menu_type', 16, 'Type of the sidebar menu', 'select', 1, 1, 1, NULL),
(7, 'page_sidebar_menu_show_dynamic', 16, 'Show dynamic pages', 'checkbox', NULL, 2, 1, NULL),
(8, 'page_share_buttons_visible_list', 17, 'Visible share buttons list', 'multiselect', 1, 1, 1, NULL),
(9, 'page_share_buttons_show_extra', 17, 'Show extra share buttons', 'checkbox', NULL, 2, 1, NULL);

CREATE TABLE `page_widget_setting_predefined_value` (
    `setting_id` SMALLINT(5) UNSIGNED NOT NULL,
    `value` VARCHAR(255) NOT NULL,
    PRIMARY KEY (`setting_id`, `value`),
    FOREIGN KEY (`setting_id`) REFERENCES `page_widget_setting`(`id`)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `page_widget_setting_predefined_value` (`setting_id`, `value`) VALUES
(6, 'sidebar_menu_subpages'),
(6, 'sidebar_menu_current_level'),
(8, 'share_aim'),
(8, 'share_aol_mail'),
(8, 'share_allvoices'),
(8, 'share_amazon_wish_list'),
(8, 'share_app_net'),
(8, 'share_arto'),
(8, 'share_baidu'),
(8, 'share_bebo'),
(8, 'share_bibsonomy'),
(8, 'share_bitty_browser'),
(8, 'share_blinklist'),
(8, 'share_blogmarks'),
(8, 'share_blogger_post'),
(8, 'share_bookmarks_fr'),
(8, 'share_box_net'),
(8, 'share_buddymarks'),
(8, 'share_buffer'),
(8, 'share_care2_news'),
(8, 'share_citeulike'),
(8, 'share_dzone'),
(8, 'share_delicious'),
(8, 'share_design_float'),
(8, 'share_diaspora'),
(8, 'share_digg'),
(8, 'share_diigo'),
(8, 'share_email'),
(8, 'share_evernote'),
(8, 'share_facebook'),
(8, 'share_fark'),
(8, 'share_flipboard'),
(8, 'share_folkd'),
(8, 'share_friendfeed'),
(8, 'share_funp'),
(8, 'share_google_bookmarks'),
(8, 'share_google_gmail'),
(8, 'share_google_plus'),
(8, 'share_hacker_news'),
(8, 'share_hatena'),
(8, 'share_instapaper'),
(8, 'share_jamespot'),
(8, 'share_jumptags'),
(8, 'share_kakao'),
(8, 'share_khabbr'),
(8, 'share_kindle_it'),
(8, 'share_line'),
(8, 'share_linkagogo'),
(8, 'share_linkatopia'),
(8, 'share_linkedin'),
(8, 'share_livejournal'),
(8, 'share_mail_ru'),
(8, 'share_mendeley'),
(8, 'share_meneame'),
(8, 'share_mixi'),
(8, 'share_myspace'),
(8, 'share_netlog'),
(8, 'share_netvouz'),
(8, 'share_newstrust'),
(8, 'share_newsvine'),
(8, 'share_nowpublic'),
(8, 'share_odnoklassniki'),
(8, 'share_oknotizie'),
(8, 'share_orkut'),
(8, 'share_outlook_com'),
(8, 'share_phonefavs'),
(8, 'share_pinboard'),
(8, 'share_pinterest'),
(8, 'share_plurk'),
(8, 'share_pocket'),
(8, 'share_print'),
(8, 'share_printfriendly'),
(8, 'share_protopage_bookmarks'),
(8, 'share_pusha'),
(8, 'share_qzone'),
(8, 'share_reddit'),
(8, 'share_rediff'),
(8, 'share_segnalo'),
(8, 'share_sina_weibo'),
(8, 'share_sitejot'),
(8, 'share_slashdot'),
(8, 'share_springpad'),
(8, 'share_startaid'),
(8, 'share_stumbleupon'),
(8, 'share_stumpedia'),
(8, 'share_symbaloo_feeds'),
(8, 'share_technotizie'),
(8, 'share_tuenti'),
(8, 'share_tumblr'),
(8, 'share_twiddla'),
(8, 'share_twitter'),
(8, 'share_typepad_post'),
(8, 'share_vk'),
(8, 'share_viadeo'),
(8, 'share_wanelo'),
(8, 'share_webnews'),
(8, 'share_whatsapp'),
(8, 'share_wists'),
(8, 'share_wordpress'),
(8, 'share_wykop'),
(8, 'share_xing'),
(8, 'share_xerpi'),
(8, 'share_yahoo_bookmarks'),
(8, 'share_yahoo_mail'),
(8, 'share_yahoo_messenger'),
(8, 'share_yoolink'),
(8, 'share_youmob'),
(8, 'share_yummly');

CREATE TABLE `page_widget_setting_value` (
    `id` SMALLINT(5) UNSIGNED NOT NULL AUTO_INCREMENT,
    `setting_id` SMALLINT(5) UNSIGNED NOT NULL,
    `value` TEXT NOT NULL,
    `widget_connection` SMALLINT(5) UNSIGNED NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `setting` (`setting_id`, `widget_connection`),
    FOREIGN KEY (`setting_id`) REFERENCES `page_widget_setting`(`id`)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    FOREIGN KEY (`widget_connection`) REFERENCES `page_widget_connection`(`id`)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `page_widget_setting_default_value` (
    `id` SMALLINT(5) UNSIGNED NOT NULL AUTO_INCREMENT,
    `setting_id` SMALLINT(5) UNSIGNED NOT NULL,
    `value` TEXT NOT NULL,
    `language` CHAR(2) DEFAULT NULL,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`setting_id`) REFERENCES `page_widget_setting`(`id`)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    FOREIGN KEY (`language`) REFERENCES `localization_list`(`language`)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `page_widget_setting_default_value` (`id`, `setting_id`, `value`, `language`) VALUES
(1, 2, '__site_email_value__', NULL),
(2, 3, 'A message from the contact form', NULL),
(3, 3, 'Сообщение из контактной формы', 'ru'),
(4, 4, '<p><b>User name:</b> __RealName__</p>\r\n<p><b>Email:</b> __Email__</p>\r\n<p><b>Phone:</b> __Phone__</p>\r\n<br /><p>__Message__</p>', NULL),
(5, 4, '<p><b>Имя пользователя:</b> __RealName__</p>\r\n<p><b>Email:</b> __Email__</p>\r\n<p><b>Телефон:</b> __Phone__</p>\r\n<br /><p>__Message__</p>', 'ru'),
(6, 5, '1', NULL),
(7, 6, 'sidebar_menu_subpages', NULL),
(8, 8, 'share_facebook;share_twitter;share_google_plus', NULL),
(9, 9, '1', NULL);

CREATE TABLE `page_widget_visibility` (
    `id` SMALLINT(5) UNSIGNED NOT NULL AUTO_INCREMENT,
    `widget_connection` SMALLINT(5) UNSIGNED NOT NULL,
    `hidden` SMALLINT(5) UNSIGNED NULL,
    PRIMARY KEY (`id`),
    UNIQUE `widget` (`widget_connection`, `hidden`),
    FOREIGN KEY (`hidden`) REFERENCES `acl_role`(`id`)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    FOREIGN KEY (`widget_connection`) REFERENCES `page_widget_connection`(`id`)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;