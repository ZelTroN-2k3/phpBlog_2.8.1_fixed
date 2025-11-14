# phpBlog v2.9.4 (Édition Complète)
phpBlog - News, Blog & Magazine CMS

## Vue d'ensemble

Cette version **v2.9.4** est l'aboutissement de la refonte du CMS. Elle transforme le blog en une plateforme professionnelle, riche, interactive et sécurisée, dotée d'outils d'engagement et de maintenance avancés.

L'ajout final est un **Gestionnaire de Slider Personnalisé**, permettant à l'administrateur de basculer entre un slider marketing (diapositives personnalisées) et le slider dynamique des articles en vedette.

---

### 🌟 Nouveautés Exclusives (v2.9.4)

#### 🖼️ Gestionnaire de Slider d'Accueil (Nouveau)
Flexibilité totale pour votre page d'accueil :
* **Double Mode :** L'administrateur peut choisir via les Réglages (`admin/settings.php`) quel slider afficher :
    1.  **Articles en Vedette (Défaut) :** Affiche automatiquement les articles marqués "featured".
    2.  **Slider Personnalisé (Nouveau) :** Affiche des diapositives marketing créées manuellement.
* **Gestion Admin :** Un module complet (`admin/slides.php`) permet de créer, modifier, ordonner et supprimer des diapositives personnalisées (Image, Titre, Description HTML, Lien).

#### 🛠️ Outils Système & Maintenance
* **💾 Gestionnaire de Sauvegarde (Backup) :**
    * **1-Click Backup :** Génère et sauvegarde un fichier `.sql` complet dans un dossier sécurisé (`/backup-database/`) sur le serveur.
    * **Gestionnaire d'Historique :** Affiche la liste de toutes les sauvegardes (date, taille) avec des options pour **Télécharger** ou **Supprimer**.

#### 📊 Modules d'Interaction & UGC
* **💬 Gestionnaire de Témoignages (Testimonials) :**
    * **Soumission Frontend :** Les utilisateurs connectés peuvent soumettre leurs propres témoignages via leur menu profil.
    * **Flux de Modération Admin :** Les soumissions reçoivent le statut **"Pending"** et peuvent être approuvées en 1 clic.
    * **Affichage :** Slider Bootstrap sur la page d'accueil.
* **🗳️ Système de Sondages (Polls) :**
    * Widget Sidebar avec vote AJAX et affichage des résultats.
    * Protection anti-spam par IP et Cookies.
* **❓ Foire Aux Questions (FAQ) :**
    * Page publique dédiée (`faq.php`) avec interface en accordéon.

---

### 🚀 Fonctionnalités Majeures (v2.9.1)

* **🎨 Mega Menu "Next-Gen" :** Navigation 100% responsive avec 3 colonnes et affichage des derniers articles.
* **📢 Importateur RSS Auto :** Auto-blogging avec détection d'images et anti-doublons.
* **🔔 Gestionnaire de Popups :** Fenêtres modales marketing ciblées.
* **🚧 Mode Maintenance :** Page d'attente personnalisable avec accès administrateur préservé.

---

### 🛡️ Sécurité Renforcée

* **Protection Totale :** Anti-CSRF (Tous formulaires), Anti-XSS (HTMLPurifier), Anti-SQLi (Requêtes préparées).
* **Authentification :** Protection Brute Force (Blocage temporaire) et hachage `password_hash()`.
* **Installation :** Séparation des e-mails (Site vs Admin).

---

### 📋 Prérequis
* PHP 7.4 ou supérieur (8.0+ recommandé)
* Extension PHP `mysqli`, `mbstring`, `curl`, `gd`
* Apache avec `mod_rewrite` activé

---

### 🔄 Instructions de Mise à Jour (SQL)

Si vous mettez à jour depuis la v2.9.1 vers la **v2.9.4**, exécutez ces requêtes :

```sql
-- 1. Table des Sondages (Polls)
CREATE TABLE `polls` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `question` varchar(255) NOT NULL,
  `active` enum('Yes','No') NOT NULL DEFAULT 'Yes',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
);

-- 2. Options des Sondages
CREATE TABLE `poll_options` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `poll_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `votes` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `poll_id` (`poll_id`)
);

-- 3. Votants (Anti-Doublon)
CREATE TABLE `poll_voters` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `poll_id` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `voted_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `poll_ip` (`poll_id`, `ip_address`)
);

-- 4. Témoignages (Testimonials)
CREATE TABLE `testimonials` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `position` varchar(255) DEFAULT NULL,
  `content` TEXT NOT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `active` enum('Yes','No') NOT NULL DEFAULT 'Yes',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
);

-- 5. FAQ
CREATE TABLE `faqs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `question` varchar(255) NOT NULL,
  `answer` LONGTEXT NOT NULL,
  `active` enum('Yes','No') NOT NULL DEFAULT 'Yes',
  `position_order` int(11) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
);

-- 6. MODIFICATION V2.9.3 (Modération des témoignages)
ALTER TABLE `testimonials` MODIFY COLUMN `active` ENUM('Yes', 'No', 'Pending') NOT NULL DEFAULT 'Pending';

-- 7. NOUVEAUTÉS V2.9.4 (Slider Personnalisé)
CREATE TABLE `slides` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) DEFAULT NULL,
  `description` TEXT DEFAULT NULL,
  `image_url` varchar(255) NOT NULL,
  `link_url` varchar(255) DEFAULT '#',
  `position_order` int(11) NOT NULL DEFAULT 0,
  `active` enum('Yes','No') NOT NULL DEFAULT 'Yes',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
);

ALTER TABLE `settings`
ADD COLUMN `homepage_slider` ENUM('Featured', 'Custom') NOT NULL DEFAULT 'Featured' COMMENT 'Choix entre articles (Featured) ou slider perso (Custom)';
