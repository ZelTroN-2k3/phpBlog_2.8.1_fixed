
-- --------------------------------------------------------
-- SUPPRESSION DES TABLES EXISTANTES (Nettoyage)
-- --------------------------------------------------------

DROP TABLE IF EXISTS `ad_clicks`;
DROP TABLE IF EXISTS `ads`;
DROP TABLE IF EXISTS `albums`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `comments`;
DROP TABLE IF EXISTS `faqs`;
DROP TABLE IF EXISTS `files`;
DROP TABLE IF EXISTS `footer_pages`;
DROP TABLE IF EXISTS `gallery`;
DROP TABLE IF EXISTS `mega_menus`;
DROP TABLE IF EXISTS `menu`;
DROP TABLE IF EXISTS `messages`;
DROP TABLE IF EXISTS `newsletter`;
DROP TABLE IF EXISTS `pages`;
DROP TABLE IF EXISTS `poll_options`;
DROP TABLE IF EXISTS `poll_voters`;
DROP TABLE IF EXISTS `polls`;
DROP TABLE IF EXISTS `popups`;
DROP TABLE IF EXISTS `post_likes`;
DROP TABLE IF EXISTS `post_tags`;
DROP TABLE IF EXISTS `posts`;
DROP TABLE IF EXISTS `quiz_attempts`;
DROP TABLE IF EXISTS `quiz_options`;
DROP TABLE IF EXISTS `quiz_questions`;
DROP TABLE IF EXISTS `quizzes`;
DROP TABLE IF EXISTS `rss_imports`;
DROP TABLE IF EXISTS `settings`;
DROP TABLE IF EXISTS `slides`;
DROP TABLE IF EXISTS `tags`;
DROP TABLE IF EXISTS `testimonials`;
DROP TABLE IF EXISTS `user_favorites`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `widgets`;
DROP TABLE IF EXISTS `bans`;

-- --------------------------------------------------------
-- Base de données : `localhost`
-- --------------------------------------------------------

--
-- Structure de la table `ad_clicks`
--

CREATE TABLE `ad_clicks` (
  `id` int(11) NOT NULL,
  `ad_id` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `clicked_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Structure de la table `ads`
--

CREATE TABLE `ads` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL COMMENT 'Name to help you find your way',
  `ad_size` enum('728x90','970x90','468x60','234x60','300x250','300x600','150x150','custom') NOT NULL DEFAULT '300x250',
  `image_url` varchar(255) NOT NULL,
  `link_url` varchar(255) DEFAULT '#',
  `active` enum('Yes','No') NOT NULL DEFAULT 'Yes',
  `clicks` int(11) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Déchargement des données de la table `ads`
--

INSERT INTO `ads` (`id`, `name`, `ad_size`, `image_url`, `link_url`, `active`, `clicks`, `created_at`) VALUES
(1, 'Winter Sale', '728x90', 'uploads/ads/ad_691c4b01726db.jpg', 'http://localhost/phpBlog', 'No', 0, '2025-01-01 12:00:00'),
(2, 'Winter Sale', '300x250', 'uploads/ads/ad_691c4ef6959e5.jpg', 'http://localhost/phpBlog', 'No', 0, '2025-01-01 12:00:00'),
(3, 'Winter Sale', '468x60', 'uploads/ads/ad_691c4f77da9f7.jpg', 'http://localhost/phpBlog', 'No', 0, '2025-01-01 12:00:00'),
(4, 'Winter Sale', '300x600', 'uploads/ads/ad_691c512210a01.jpg', 'http://localhost/phpBlog', 'No', 0, '2025-01-01 12:00:00');

-- --------------------------------------------------------

--
-- Structure de la table `albums`
--

CREATE TABLE `albums` (
  `id` int(11) NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `category` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `categories`
--

INSERT INTO `categories` (`id`, `category`, `slug`) VALUES
(1, 'Site News', 'site-news');

-- --------------------------------------------------------

--
-- Structure de la table `comments`
--

CREATE TABLE `comments` (
  `id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `user_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `comment` varchar(1000) COLLATE utf8mb4_unicode_ci NOT NULL,
  `approved` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Yes',
  `guest` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL,
  `parent_id` int(11) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `faqs`
--

CREATE TABLE `faqs` (
  `id` int(11) NOT NULL,
  `question` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `answer` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `active` enum('Yes','No') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Yes',
  `position_order` int(11) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `files`
--

CREATE TABLE `files` (
  `id` int(11) NOT NULL,
  `filename` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `footer_pages`
--

CREATE TABLE `footer_pages` (
  `id` int(11) NOT NULL,
  `page_key` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Unique key (e.g., legal, contact)',
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` longtext COLLATE utf8mb4_unicode_ci,
  `active` enum('Yes','No') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Yes'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `footer_pages`
--

INSERT INTO `footer_pages` (`id`, `page_key`, `title`, `content`, `active`) VALUES
(1, 'legal', 'Legal Information', '<div class=\"d-flex gap-2 justify-content-start flex-wrap\"> <a href=\"legal-notice\" class=\"btn btn-outline-light btn-sm\" title=\"legal-notice\">\r\n        <i class=\"fas fa-balance-scale fa-lg text-info\"></i> <span class=\"small\">Legal Notice</span>\r\n    </a>\r\n    <a href=\"privacy-policy\" class=\"btn btn-outline-light btn-sm\" title=\"privacy-policy\">\r\n        <i class=\"fas fa-user-shield fa-lg text-info\"></i> <span class=\"small\">Privacy Policy</span>\r\n    </a>\r\n</div>', 'Yes'),
(2, 'contact_methods', 'Contact Methods', '<p>Please write your contact information here...</p>', 'Yes'),
(3, 'most_viewed', 'Viewed Pages', '<p>Write a text or links to your popular pages here...</p>', 'Yes'),
(4, 'cta_buttons', 'Call-to-Action', '<p>Write your call-to-action buttons here (e.g., Newsletter, Contact)...</p>', 'Yes'),
(5, 'trust_badges', 'Signs of Trust', '<div class=\"d-flex gap-2 justify-content-start flex-wrap\"> <a href=\"http://validator.w3.org/check?uri=referer\" target=\"_blank\" class=\"btn btn-outline-light btn-sm\" title=\"Valid HTML5 code\">\r\n        <i class=\"fab fa-html5 fa-lg text-warning\"></i> <span class=\"small\">Validated HTML</span>\r\n    </a>\r\n    <a href=\"http://jigsaw.w3.org/css-validator/check/referer\" target=\"_blank\" class=\"btn btn-outline-light btn-sm\" title=\"Valid CSS3 code\">\r\n        <i class=\"fab fa-css3-alt fa-lg text-info\"></i> <span class=\"small\">Valid CSS</span>\r\n    </a>\r\n    \r\n    <a href=\"#\" class=\"btn btn-outline-light btn-sm\" title=\"Secure HTTPS Connection\">\r\n        <i class=\"fas fa-lock fa-lg text-success\"></i> <span class=\"small\">HTTPS Secure</span>\r\n    </a>\r\n\r\n    <a href=\"#\" class=\"btn btn-outline-light btn-sm\" title=\"Design Responsive\">\r\n        <i class=\"fas fa-mobile-alt fa-lg text-info\"></i> <span class=\"small\">Responsive</span>\r\n    </a>\r\n</div>', 'Yes');

-- --------------------------------------------------------

--
-- Structure de la table `gallery`
--

CREATE TABLE `gallery` (
  `id` int(11) NOT NULL,
  `album_id` int(11) NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `image` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `active` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Yes'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `mega_menus`
--

CREATE TABLE `mega_menus` (
  `id` int(11) NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Internal name for the administration',
  `trigger_text` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Text displayed in the menu bar',
  `trigger_icon` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'fa-bars' COMMENT 'FontAwesome icon',
  `trigger_link` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '#' COMMENT 'Link when clicking on the parent',
  `col_1_title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'Explore',
  `col_1_content` longtext COLLATE utf8mb4_unicode_ci COMMENT 'HTML content or Links',
  `col_2_title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'Categories',
  `col_2_type` enum('categories','custom','none') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'categories',
  `col_2_content` longtext COLLATE utf8mb4_unicode_ci COMMENT 'If custom type',
  `col_3_title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'Newest',
  `col_3_type` enum('latest_posts','custom','none') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'latest_posts',
  `col_3_content` longtext COLLATE utf8mb4_unicode_ci COMMENT 'If custom type',
  `position_order` int(11) NOT NULL DEFAULT '0',
  `active` enum('Yes','No') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Yes',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `mega_menus`
--

INSERT INTO `mega_menus` (`id`, `name`, `trigger_text`, `trigger_icon`, `trigger_link`, `col_1_title`, `col_1_content`, `col_2_title`, `col_2_type`, `col_2_content`, `col_3_title`, `col_3_type`, `col_3_content`, `position_order`, `active`, `created_at`) VALUES
(1, 'News', 'Blog', 'fa-bars', '#', 'Explore', '', 'Categories', 'categories', '', 'Newest', 'latest_posts', '', 0, 'No', '2025-01-01 12:00:00');

-- --------------------------------------------------------

--
-- Structure de la table `menu`
--

CREATE TABLE `menu` (
  `id` int(11) NOT NULL,
  `page` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fa_icon` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `active` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Yes'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `menu`
--

INSERT INTO `menu` (`id`, `page`, `path`, `fa_icon`, `active`) VALUES
(1, 'Home', 'index', 'fa-home', 'Yes'),
(2, 'About', 'about', 'fa-info-circle', 'Yes'),
(3, 'Gallery', 'gallery', 'fa-images', 'Yes'),
(4, 'Posts', 'blog', 'fa-list', 'Yes'),
(5, 'Contact', 'contact', 'fa-envelope', 'Yes'),
(6, 'FAQ', 'faq', 'fa-question-circle', 'Yes'),
(7, 'Quiz', 'quiz', 'fas fa-graduation-cap', 'Yes'),
(8, 'Info', 'page?name=about', 'fa-info-circle', 'No');

-- --------------------------------------------------------

--
-- Structure de la table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `viewed` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'No',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `newsletter`
--

CREATE TABLE `newsletter` (
  `id` int(11) NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `pages`
--

CREATE TABLE `pages` (
  `id` int(11) NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `active` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Yes'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `pages`
--

INSERT INTO `pages` (`id`, `title`, `slug`, `content`, `active`) VALUES
(1, 'About', 'about', '<p><br></p>', 'Yes');

-- --------------------------------------------------------

--
-- Structure de la table `poll_options`
--

CREATE TABLE `poll_options` (
  `id` int(11) NOT NULL,
  `poll_id` int(11) NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `votes` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `poll_voters`
--

CREATE TABLE `poll_voters` (
  `id` int(11) NOT NULL,
  `poll_id` int(11) NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `voted_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `polls`
--

CREATE TABLE `polls` (
  `id` int(11) NOT NULL,
  `question` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `active` enum('Yes','No') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Yes',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `popups`
--

CREATE TABLE `popups` (
  `id` int(11) NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `active` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'No',
  `display_pages` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'home',
  `show_once_per_session` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Yes',
  `delay_seconds` int(3) NOT NULL DEFAULT '2',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `post_likes`
--

CREATE TABLE `post_likes` (
  `id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `session_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `post_tags`
--

CREATE TABLE `post_tags` (
  `id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `tag_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `posts`
--

CREATE TABLE `posts` (
  `id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `image` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `imported_guid` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `author_id` int(11) NOT NULL DEFAULT '1',
  `active` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Draft',
  `featured` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'No',
  `download_link` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `github_link` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `publish_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `views` int(11) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `quiz_attempts`
--

CREATE TABLE `quiz_attempts` (
  `id` int(11) NOT NULL,
  `quiz_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `score` int(11) NOT NULL COMMENT 'Score as a percentage (e.g., 80)',
  `time_seconds` int(11) NOT NULL COMMENT 'Total time in seconds',
  `attempt_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `quiz_options`
--

CREATE TABLE `quiz_options` (
  `id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_correct` enum('Yes','No') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'No'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `quiz_questions`
--

CREATE TABLE `quiz_questions` (
  `id` int(11) NOT NULL,
  `quiz_id` int(11) DEFAULT NULL,
  `question` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `explanation` longtext COLLATE utf8mb4_unicode_ci,
  `active` enum('Yes','No') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Yes',
  `position_order` int(11) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `quizzes`
--

CREATE TABLE `quizzes` (
  `id` int(11) NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `difficulty` enum('FACILE','NORMAL','DIFFICILE','EXPERT') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'NORMAL',
  `active` enum('Yes','No') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Yes',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `rss_imports`
--

CREATE TABLE `rss_imports` (
  `id` int(11) NOT NULL,
  `feed_url` varchar(255) NOT NULL,
  `import_as_user_id` int(11) NOT NULL,
  `import_as_category_id` int(11) NOT NULL,
  `last_import_time` datetime DEFAULT NULL,
  `is_active` int(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Structure de la table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL DEFAULT '1',
  `site_url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sitename` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `gcaptcha_sitekey` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `gcaptcha_secretkey` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `head_customcode` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `head_customcode_enabled` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Off',
  `facebook` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `instagram` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `twitter` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `youtube` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `linkedin` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `comments` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `rtl` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_format` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `layout` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `latestposts_bar` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sidebar_position` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `posts_per_row` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `theme` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `background_image` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `posts_per_page` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `meta_title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `favicon_url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `apple_touch_icon_url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `meta_author` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `meta_generator` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `meta_robots` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sticky_header` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Off',
  `maintenance_mode` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Off',
  `maintenance_title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `maintenance_message` text COLLATE utf8mb4_unicode_ci,
  `homepage_slider` enum('Featured','Custom') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Featured' COMMENT 'Choice between (Featured) articles or a (Custom) slider.',
  `google_maps_code` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `site_logo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `maintenance_image` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ban_bg_image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'default.jpg'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `settings`
INSERT INTO `settings` (`id`, `site_url`, `sitename`, `description`, `email`, `gcaptcha_sitekey`, `gcaptcha_secretkey`, `head_customcode`, `head_customcode_enabled`, `facebook`, `instagram`, `twitter`, `youtube`, `linkedin`, `comments`, `rtl`, `date_format`, `layout`, `latestposts_bar`, `sidebar_position`, `posts_per_row`, `theme`, `background_image`, `posts_per_page`, `meta_title`, `favicon_url`, `apple_touch_icon_url`, `meta_author`, `meta_generator`, `meta_robots`, `sticky_header`, `maintenance_mode`, `maintenance_title`, `maintenance_message`, `homepage_slider`, `google_maps_code`, `site_logo`, `maintenance_image`) VALUES
(1, 'http://localhost/phpBlog', 'PHP-Blog', 'phpBlog Content Management System', 'admin@example.com', '6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI', '6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe', 'IDwhLS0gR29vZ2xlIEFuYWx5dGljcyA0IChHQTQpIFRyYWNraW5nIENvZGUgLS0+DQogPHNjcmlwdCBhc3luYyBzcmM9Imh0dHBzOi8vd3d3Lmdvb2dsZXRhZ21hbmFnZXIuY29tL2d0YWcvanM/aWQ9Ry1YWFhYWFhYWFhYIj48L3NjcmlwdD4NCiA8c2NyaXB0Pg0KICAgd2luZG93LmRhdGFMYXllciA9IHdpbmRvdy5kYXRhTGF5ZXIgfHwgW107DQogICBmdW5jdGlvbiBndGFnKCl7ZGF0YUxheWVyLnB1c2goYXJndW1lbnRzKTt9DQogICBndGFnKCdqcycsIG5ldyBEYXRlKCkpOw0KICAgZ3RhZygnY29uZmlnJywgJ0ctWFhYWFhYWFhYWCcpOw0KIDwvc2NyaXB0Pg0KPCEtLSBSZXN0IG9mIHlvdXIgaGVhZCBjb250ZW50IC0tPg==', 'Off', '', '', '', '', '', 'guests', 'No', 'd F Y', 'Fixed', 'Enabled', 'Right', '2', 'Bootstrap 5', '', '4', 'phpBlog - Titre SEO', 'assets/img/favicon.png', 'assets/img/favicon.png', 'Antonov_WEB', 'phpBlog', 'index, follow, all', 'Off', 'Off', 'Site Under Maintenance', '<p>Our website is currently undergoing maintenance. We apologize for the inconvenience. We will be back soon!</p>', 'Featured', 'PGlmcmFtZSBzcmM9Imh0dHBzOi8vd3d3Lmdvb2dsZS5jb20vbWFwcy9lbWJlZD9wYj0hMW0xOCExbTEyITFtMyExZDI2MTcuMTA4MjAzNDg0ODA5ITJkMzEuMzg3NTAxMjc2NzYyNzghM2Q0OS4wMDg1MjYzOTAxMjg4OCEybTMhMWYwITJmMCEzZjAhM20yITFpMTAyNCEyaTc2OCE0ZjEzLjEhM20zITFtMiExczB4NDBkMTc3OWU5NTEwYjM5MyUzQTB4YWQyN2YwZTRkOTVmOWNjYiEyc0xlbmluYSUyMFN0JTJDJTIwMzUlMkMlMjBTaHBvbGElMkMlMjBDaGVya2FzJiMzOTtrYSUyMG9ibGFzdCUyQyUyMFVrcmFpbmUlMkMlMjAyMDYwMCE1ZTAhM20yITFzZnIhMnNmciE0djE3NjM1NjkyNTk5ODIhNW0yITFzZnIhMnNmciIgd2lkdGg9IjYwMCIgaGVpZ2h0PSI0NTAiIHN0eWxlPSJib3JkZXI6MDsiIGFsbG93ZnVsbHNjcmVlbj0iIiBsb2FkaW5nPSJsYXp5IiByZWZlcnJlcnBvbGljeT0ibm8tcmVmZXJyZXItd2hlbi1kb3duZ3JhZGUiPjwvaWZyYW1lPg==', NULL, 'assets/img/maintenance.jpg');

-- --------------------------------------------------------

--
-- Structure de la table `slides`
--

CREATE TABLE `slides` (
  `id` int(11) NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `image_url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `link_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '#',
  `position_order` int(11) NOT NULL DEFAULT '0',
  `active` enum('Yes','No') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Yes',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `tags`
--

CREATE TABLE `tags` (
  `id` int(11) NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `testimonials`
--

CREATE TABLE `testimonials` (
  `id` int(11) NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `position` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Ex: CEO of TechCorp',
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `avatar` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `active` enum('Yes','No','Pending') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Pending',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `user_favorites`
--

CREATE TABLE `user_favorites` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `avatar` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'assets/img/avatar.png',
  `bio` text COLLATE utf8mb4_unicode_ci,
  `role` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'User',
  `website` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `location` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `widgets`
--

CREATE TABLE `widgets` (
  `id` int(11) NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `widget_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'html',
  `content` mediumtext COLLATE utf8mb4_unicode_ci,
  `config_data` text COLLATE utf8mb4_unicode_ci,
  `position` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Sidebar',
  `active` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Yes'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `widgets`
INSERT INTO `widgets` (`id`, `title`, `widget_type`, `content`, `config_data`, `position`, `active`) VALUES
(1, 'Text Widget', 'html', '<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nullam ornare sem tempor massa volutpat, quis varius urna placerat. Aliquam erat volutpat. Suspendisse lorem odio, imperdiet ut elit vitae, dignissim pretium odio. </p>\r\n', NULL, 'Sidebar', 'Yes'),
(2, 'Quiz Leaderboard (Top 10)', 'quiz_leaderboard', NULL, NULL, 'Sidebar', 'No'),
(3, 'FAQ Leaderboard', 'faq_leaderboard', NULL, NULL, 'Sidebar', 'No'),
(4, 'Slider Testimonials', 'testimonials', NULL, NULL, 'Sidebar', 'No');

-- --------------------------------------------------------

--
-- Structure de la table `bans`
--
CREATE TABLE `bans` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ban_type` enum('ip','username','email','user_agent') NOT NULL DEFAULT 'ip',
  `ban_value` varchar(255) NOT NULL,
  `reason` text,
  `active` enum('Yes','No') NOT NULL DEFAULT 'Yes',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- --------------------------------------------------------
-- Index pour les tables déchargées
-- --------------------------------------------------------

--
-- Index pour la table `ad_clicks`
--
ALTER TABLE `ad_clicks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_check` (`ad_id`,`ip_address`);

--
-- Index pour la table `ads`
--
ALTER TABLE `ads`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `albums`
--
ALTER TABLE `albums`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `post_id` (`post_id`),
  ADD KEY `parent_id` (`parent_id`);

--
-- Index pour la table `faqs`
--
ALTER TABLE `faqs`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `files`
--
ALTER TABLE `files`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `footer_pages`
--
ALTER TABLE `footer_pages`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `page_key` (`page_key`);

--
-- Index pour la table `gallery`
--
ALTER TABLE `gallery`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `mega_menus`
--
ALTER TABLE `mega_menus`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `menu`
--
ALTER TABLE `menu`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `newsletter`
--
ALTER TABLE `newsletter`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `pages`
--
ALTER TABLE `pages`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Index pour la table `poll_options`
--
ALTER TABLE `poll_options`
  ADD PRIMARY KEY (`id`),
  ADD KEY `poll_id` (`poll_id`);

--
-- Index pour la table `poll_voters`
--
ALTER TABLE `poll_voters`
  ADD PRIMARY KEY (`id`),
  ADD KEY `poll_ip` (`poll_id`,`ip_address`);

--
-- Index pour la table `polls`
--
ALTER TABLE `polls`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `popups`
--
ALTER TABLE `popups`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `post_likes`
--
ALTER TABLE `post_likes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_like` (`post_id`,`user_id`),
  ADD UNIQUE KEY `session_like` (`post_id`,`session_id`(191)),
  ADD KEY `post_id` (`post_id`);

--
-- Index pour la table `post_tags`
--
ALTER TABLE `post_tags`
  ADD PRIMARY KEY (`id`),
  ADD KEY `post_id` (`post_id`),
  ADD KEY `tag_id` (`tag_id`);

--
-- Index pour la table `posts`
--
ALTER TABLE `posts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD UNIQUE KEY `imported_guid_unique` (`imported_guid`);

--
-- Index pour la table `quiz_attempts`
--
ALTER TABLE `quiz_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `quiz_id` (`quiz_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Index pour la table `quiz_options`
--
ALTER TABLE `quiz_options`
  ADD PRIMARY KEY (`id`),
  ADD KEY `question_id` (`question_id`);

--
-- Index pour la table `quiz_questions`
--
ALTER TABLE `quiz_questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `quiz_id` (`quiz_id`);

--
-- Index pour la table `quizzes`
--
ALTER TABLE `quizzes`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `rss_imports`
--
ALTER TABLE `rss_imports`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `slides`
--
ALTER TABLE `slides`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `tags`
--
ALTER TABLE `tags`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Index pour la table `testimonials`
--
ALTER TABLE `testimonials`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `user_favorites`
--
ALTER TABLE `user_favorites`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_favorite_post` (`user_id`,`post_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `post_id` (`post_id`);

--
-- Index pour la table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Index pour la table `widgets`
--
ALTER TABLE `widgets`
  ADD PRIMARY KEY (`id`);

-- --------------------------------------------------------
-- AUTO_INCREMENT pour les tables déchargées
-- --------------------------------------------------------

--
-- AUTO_INCREMENT pour la table `ad_clicks`
--
ALTER TABLE `ad_clicks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `ads`
--
ALTER TABLE `ads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT pour la table `albums`
--
ALTER TABLE `albums`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `comments`
--
ALTER TABLE `comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `faqs`
--
ALTER TABLE `faqs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `files`
--
ALTER TABLE `files`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `footer_pages`
--
ALTER TABLE `footer_pages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT pour la table `gallery`
--
ALTER TABLE `gallery`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `mega_menus`
--
ALTER TABLE `mega_menus`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `menu`
--
ALTER TABLE `menu`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT pour la table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `newsletter`
--
ALTER TABLE `newsletter`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `pages`
--
ALTER TABLE `pages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `poll_options`
--
ALTER TABLE `poll_options`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `poll_voters`
--
ALTER TABLE `poll_voters`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `polls`
--
ALTER TABLE `polls`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `popups`
--
ALTER TABLE `popups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `post_likes`
--
ALTER TABLE `post_likes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `post_tags`
--
ALTER TABLE `post_tags`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `posts`
--
ALTER TABLE `posts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `quiz_attempts`
--
ALTER TABLE `quiz_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `quiz_options`
--
ALTER TABLE `quiz_options`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `quiz_questions`
--
ALTER TABLE `quiz_questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `quizzes`
--
ALTER TABLE `quizzes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `rss_imports`
--
ALTER TABLE `rss_imports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `slides`
--
ALTER TABLE `slides`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `tags`
--
ALTER TABLE `tags`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `testimonials`
--
ALTER TABLE `testimonials`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `user_favorites`
--
ALTER TABLE `user_favorites`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT pour la table `widgets`
--
ALTER TABLE `widgets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
COMMIT;
