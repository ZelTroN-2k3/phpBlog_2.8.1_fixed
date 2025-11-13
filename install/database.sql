DROP TABLE IF EXISTS `settings`;
DROP TABLE IF EXISTS `albums`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `comments`;
DROP TABLE IF EXISTS `files`;
DROP TABLE IF EXISTS `gallery`;
DROP TABLE IF EXISTS `menu`;
DROP TABLE IF EXISTS `messages`;
DROP TABLE IF EXISTS `newsletter`;
DROP TABLE IF EXISTS `pages`;
DROP TABLE IF EXISTS `posts`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `widgets`;
DROP TABLE IF EXISTS `tags`;
DROP TABLE IF EXISTS `post_tags`;
DROP TABLE IF EXISTS `post_likes`;
DROP TABLE IF EXISTS `user_favorites`;
DROP TABLE IF EXISTS `rss_imports`;
DROP TABLE IF EXISTS `popups`;
--
-- Base de données : `localhost`
--

-- --------------------------------------------------------

--
-- Structure de la table `settings` 
--
CREATE TABLE `settings` (
  `id` int(11) NOT NULL DEFAULT 1,
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
  `maintenance_message` LONGTEXT COLLATE utf8mb4_unicode_ci NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `settings`
--
INSERT INTO `settings` (`id`, `site_url`, `sitename`, `description`, `email`, `gcaptcha_sitekey`, `gcaptcha_secretkey`, `head_customcode`, `head_customcode_enabled`, `facebook`, `instagram`, `twitter`, `youtube`, `linkedin`, `comments`, `rtl`, `date_format`, `layout`, `latestposts_bar`, `sidebar_position`, `posts_per_row`, `theme`, `background_image`, `posts_per_page`, `meta_title`, `favicon_url`, `apple_touch_icon_url`, `meta_author`, `meta_generator`, `meta_robots`, `sticky_header`, `maintenance_mode`, `maintenance_title`, `maintenance_message`) VALUES
(1, '', 'phpBlog', 'phpBlog Content Management System', '', '6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI', '6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe', 'IDwhLS0gR29vZ2xlIEFuYWx5dGljcyA0IChHQTQpIFRyYWNraW5nIENvZGUgLS0+DQogPHNjcmlwdCBhc3luYyBzcmM9Imh0dHBzOi8vd3d3Lmdvb2dsZXRhZ21hbmFnZXIuY29tL2d0YWcvanM/aWQ9Ry1YWFhYWFhYWFhYIj48L3NjcmlwdD4NCiA8c2NyaXB0Pg0KICAgd2luZG93LmRhdGFMYXllciA9IHdpbmRvdy5kYXRhTGF5ZXIgfHwgW107DQogICBmdW5jdGlvbiBndGFnKCl7ZGF0YUxheWVyLnB1c2goYXJndW1lbnRzKTt9DQogICBndGFnKCdqcycsIG5ldyBEYXRlKCkpOw0KICAgZ3RhZygnY29uZmlnJywgJ0ctWFhYWFhYWFhYWCcpOw0KIDwvc2NyaXB0Pg0KPCEtLSBSZXN0IG9mIHlvdXIgaGVhZCBjb250ZW50IC0tPg==', 'Off', '', '', '', '', '', 'guests', 'No', 'd.m.Y', 'Fixed', 'Enabled', 'Right', '3', 'Bootstrap 5', '', '4', 'phpBlog - Titre SEO', 'assets/img/favicon.png', 'assets/img/favicon.png', 'Antonov_WEB', 'phpBlog', 'index, follow, all', 'Off', 'Off', 'Site Under Maintenance', '<p>Our website is currently undergoing maintenance. We apologize for the inconvenience. We will be back soon!</p>');
-- --------------------------------------------------------

--
-- Structure de la table `albums`
--
CREATE TABLE `albums` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `albums`
--
INSERT INTO `albums` (`id`, `title`) VALUES
(1, 'General');

-- --------------------------------------------------------

--
-- Structure de la table `categories`
--
CREATE TABLE IF NOT EXISTS `categories` (
  `id` int(11) NOT NULL,
  `category` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL
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
CREATE TABLE IF NOT EXISTS `comments` (
  `id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `user_id` varchar(255) NOT NULL,
  `comment` varchar(1000) NOT NULL,
  `approved` varchar(3) NOT NULL DEFAULT 'Yes',
  `guest` varchar(3) NOT NULL,
  `parent_id` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `files`
--
CREATE TABLE IF NOT EXISTS `files` (
  `id` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `path` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `gallery`
--
CREATE TABLE IF NOT EXISTS `gallery` (
  `id` int(11) NOT NULL,
  `album_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `image` varchar(255) NOT NULL,
  `description` longtext NOT NULL,
  `active` varchar(3) NOT NULL DEFAULT 'Yes'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `menu`
--
CREATE TABLE IF NOT EXISTS `menu` (
  `id` int(11) NOT NULL,
  `page` varchar(255) NOT NULL,
  `path` varchar(255) NOT NULL,
  `fa_icon` varchar(255) NOT NULL,
  `active` varchar(3) NOT NULL DEFAULT 'Yes'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `menu`
--
INSERT INTO `menu` (`id`, `page`, `path`, `fa_icon`, `active`) VALUES
(1, 'Home', 'index', 'fa-home', 'Yes'),
(2, 'About', 'page?name=about', 'fa-info-circle', 'Yes'),
(3, 'Gallery', 'gallery', 'fa-images', 'Yes'),
(4, 'Posts', 'blog', 'fa-list', 'Yes'),
(5, 'Contact', 'contact', 'fa-envelope', 'Yes');

-- --------------------------------------------------------

--
-- Structure de la table `messages`
--
CREATE TABLE IF NOT EXISTS `messages` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `viewed` varchar(3) NOT NULL DEFAULT 'No',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `newsletter`
--
CREATE TABLE IF NOT EXISTS `newsletter` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `pages`
--
CREATE TABLE IF NOT EXISTS `pages` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `content` longtext NOT NULL,
  `active` varchar(3) NOT NULL DEFAULT 'Yes'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `pages`
--
INSERT INTO `pages` (`id`, `title`, `slug`, `content`, `active`) VALUES
(1, 'About', 'about', '<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Phasellus faucibus auctor nisl vitae fermentum. Vivamus diam risus, hendrerit id lobortis sed, commodo ut tellus. Nulla ultricies magna a libero auctor, id tincidunt elit vulputate. Nullam ut dictum tellus. In ut consequat velit. Vivamus lorem dui, cursus in turpis eget, congue adipiscing risus. Nullam sit amet lorem sed nisl scelerisque facilisis vel vel tellus. Curabitur euismod justo nec sapien viverra, id consectetur justo tincidunt.<br />\r\n<br />\r\nPellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Ut ultrices ornare enim sed mollis. Sed porttitor nulla ac purus hendrerit ultrices. Nullam sed diam quis turpis varius suscipit ut vel massa. Nulla nisi arcu, viverra ac nisl at, vulputate ornare lectus. Pellentesque eget velit dui. Maecenas mollis congue sem, nec fringilla ligula cursus quis. Phasellus euismod elementum rutrum. Morbi elementum mi in arcu dapibus sagittis. Aliquam fringilla neque sed dui lacinia interdum. Duis a odio dui. Proin rutrum nulla nulla, sed aliquam neque commodo sed. Proin diam urna, volutpat vel felis et, volutpat iaculis nisl.<br />\r\n<br />\r\nAenean sagittis egestas volutpat. Sed facilisis sagittis tempus. Donec ante magna, faucibus eu urna eu, suscipit porttitor justo. Vivamus dictum justo vel lectus pretium, sit amet tempor dui tempus. Aliquam et risus quam. Vivamus mattis elit sit amet sem condimentum dignissim. Nullam purus ipsum, vehicula non fringilla et, faucibus varius nisl. Fusce nec rhoncus felis, id interdum risus. Vestibulum vitae dignissim diam. Donec bibendum enim lacus, et placerat urna lobortis non. Phasellus adipiscing molestie lectus, at mattis metus malesuada sit amet. Maecenas in est pretium, tincidunt nisl cursus, accumsan mi. Sed elementum, diam et suscipit adipiscing, quam odio tempor nisl, nec suscipit orci lectus id arcu. Suspendisse potenti. Phasellus id euismod erat. Nulla ligula justo, pharetra a bibendum non, sodales et ipsum.</p>\r\n', 'Yes');

-- --------------------------------------------------------

--
-- Structure de la table `posts` 
--
CREATE TABLE IF NOT EXISTS `posts` (
  `id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `image` varchar(255) NOT NULL,
  `content` longtext NOT NULL,
  `imported_guid` varchar(255) DEFAULT NULL,
  `author_id` int(11) NOT NULL DEFAULT 1,
  `active` varchar(10) NOT NULL DEFAULT 'Draft',
  `featured` varchar(3) NOT NULL DEFAULT 'No',
  `download_link` varchar(255) DEFAULT NULL,
  `github_link` varchar(255) DEFAULT NULL,
  `publish_at` datetime NOT NULL DEFAULT current_timestamp(),
  `views` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `post_likes`
--
CREATE TABLE IF NOT EXISTS `post_likes` (
  `id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `session_id` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `post_tags`
--
CREATE TABLE IF NOT EXISTS `post_tags` (
  `id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `tag_id` int(11) NOT NULL
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
  `is_active` int(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Structure de la table `popups` 
--
CREATE TABLE `popups` (
  `id` int(11) NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` LONGTEXT COLLATE utf8mb4_unicode_ci NOT NULL, 
  `active` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'No',
  `display_pages` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'home',
  `show_once_per_session` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Yes',
  `delay_seconds` int(3) NOT NULL DEFAULT 2,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `tags`
--
CREATE TABLE IF NOT EXISTS `tags` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `users`
--
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `avatar` varchar(255) NOT NULL DEFAULT 'assets/img/avatar.png',
  `bio` text DEFAULT NULL,
  `role` varchar(255) NOT NULL DEFAULT 'User',
  `website` varchar(255) DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `user_favorites`
--
CREATE TABLE IF NOT EXISTS `user_favorites` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `widgets`
--
CREATE TABLE IF NOT EXISTS `widgets` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` mediumtext NOT NULL,
  `position` varchar(10) NOT NULL DEFAULT 'Sidebar',
  `active` varchar(3) NOT NULL DEFAULT 'Yes'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `widgets`
--
INSERT INTO `widgets` (`id`, `title`, `content`, `position`, `active`) VALUES
(1, 'Text Widget', '<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nullam ornare sem tempor massa volutpat, quis varius urna placerat. Aliquam erat volutpat. Suspendisse lorem odio, imperdiet ut elit vitae, dignissim pretium odio. </p>\r\n', 'Sidebar', 'Yes');

-- --------------------------------------------------------

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `settings` 
--
ALTER TABLE `settings`
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
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `files`
--
ALTER TABLE `files`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `gallery`
--
ALTER TABLE `gallery`
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
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `posts` 
--
ALTER TABLE `posts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `imported_guid_unique` (`imported_guid`);

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
-- Index pour la table `rss_imports` 
--
ALTER TABLE `rss_imports`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `popups` 
--
ALTER TABLE `popups`
  ADD PRIMARY KEY (`id`);
  
--
-- Index pour la table `tags`
--
ALTER TABLE `tags`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Index pour la table `users`
--
ALTER TABLE `users`
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
-- Index pour la table `widgets`
--
ALTER TABLE `widgets`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `albums`
--
ALTER TABLE `albums`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

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
-- AUTO_INCREMENT pour la table `files`
--
ALTER TABLE `files`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `gallery`
--
ALTER TABLE `gallery`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `menu`
--
ALTER TABLE `menu`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT pour la table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `newsletter`
--
ALTER TABLE `newsletter`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT pour la table `pages`
--
ALTER TABLE `pages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `posts`
--
ALTER TABLE `posts`
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
-- AUTO_INCREMENT pour la table `rss_imports` 
--
ALTER TABLE `rss_imports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `popups` 
--
ALTER TABLE `popups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `tags`
--
ALTER TABLE `tags`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

-- HIGH_PERF:

--
-- AUTO_INCREMENT pour la table `user_favorites`
--
ALTER TABLE `user_favorites`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `widgets`
--
ALTER TABLE `widgets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
COMMIT;