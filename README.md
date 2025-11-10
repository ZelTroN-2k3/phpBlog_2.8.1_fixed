# phpBlog v2.4
phpBlog - Multi-Purpose CMS for Blogs, Portals, Magazines & More

## About phpBlog

phpBlog is a **multi-purpose Content Management System (CMS)** designed to be clean, simple, lightweight, responsive and user-friendly. It can be used for a wide variety of websites including:

* **Blogs** - Personal or professional blogging platforms
* **Portals** - Community and information portals
* **Company Websites** - Corporate and business websites
* **Agency Websites** - Creative and marketing agency sites
* **Magazines** - Online magazines and publications
* **Newspapers** - News and media websites
* **And many more** - Any content-driven website

With its comprehensive feature set and modern architecture, phpBlog provides all the tools you need to create engaging, secure, and performant websites.

## Améliorations (Version 2.8.1)

Cette version du phpBlog 2.4 a été largement améliorée pour inclure des fonctionnalités modernes, des correctifs de sécurité critiques et des optimisations de performance majeures.

---

### 🚀 Fonctionnalités (Base v2.8.1)

* **Système de Tags Complet :** Ajout d'un système de tags (mots-clés).
    * Intégration de **Tagify** dans l'administration pour une saisie facile des tags (`admin/add_post.php`, `admin/posts.php`).
    * Affichage des tags cliquables sur les articles (`post.php`).
    * Nouvelle page `tag.php` pour lister tous les articles associés à un tag spécifique.
    * Ajout d'un widget \"Nuage de Tags Populaires\" dans la barre latérale (`core.php`).

* **Gestion avancée du menu :**
    * Ajout d'un statut \"Publiée\" / \"Brouillon\" indépendant pour chaque élément du menu (`admin/menu_editor.php`, `admin/add_menu.php`, `database.sql`).
    * Ajout d'onglets de filtrage (Tous / Publiées / Brouillons) dans les sections Pages et Menus de l'administration pour une meilleure organisation.
    * Le statut d'une page (Publiée/Brouillon) est désormais synchronisé avec l'élément de menu correspondant lors de sa création ou de sa modification (`admin/pages.php`, `admin/add_page.php`).

* **Refonte du Profil Utilisateur :**
    * L'avatar par défaut est désormais géré via CSS pour un affichage plus propre (`core.php`).
    * L'en-tête du profil affiche un aperçu de l'avatar (`profile.php`).
    * La taille des avatars est contrôlée en CSS pour éviter les déformations de layout (`phpblog.css`).
    * La suppression de l'avatar personnel réinitialise correctement l'avatar par défaut (`profile.php`).

* **Améliorations du Système de Commentaires :**
    * Le système de commentaires a été entièrement reconstruit pour permettre les **réponses imbriquées (threading)** (`post.php`, `core.php`).
    * Ajout de la **soumission de commentaires en AJAX** (JavaScript) pour une expérience utilisateur instantanée, sans rechargement de page (`ajax_submit_comment.php`, `phpblog.js`).
    * Ajout d'un bouton "Répondre" (`post.php`, `core.php`).
    * Ajout d'un bouton "Modifier" pour ses propres commentaires (`core.php`, `edit-comment.php`).
    * Ajout de **Badges Utilisateur** automatiques (Pipette, Actif, Loyal, Vétéran) basés sur le nombre de commentaires postés (`core.php`).
    * Amélioration de la sécurité lors de l'affichage des commentaires (`core.php`).

* **Optimisations des Requêtes (N+1) :**
    * Optimisation majeure des requêtes sur la barre latérale (`core.php`) : les requêtes pour le comptage des articles par catégorie et les commentaires récents ne sont exécutées qu'une seule fois, au lieu d'une fois par élément (problème N+1).
    * Optimisation des requêtes sur la page d'accueil (`index.php`) et de blog (`blog.php`).

* **Mode Sombre (Dark Mode) :**
    * Ajout d'un sélecteur de thème (Clair/Sombre) persistant (`core.php`, `phpblog.js`).
    * Le site respecte la préférence système de l'utilisateur (prefers-color-scheme).
    * Chargement du thème sans "flash" (FOUC) grâce à un script dans le `<head>`.

* **Qualité de Code et Sécurité :**
    * Remplacement de toutes les requêtes `mysql_*` (obsolètes et supprimées) par `mysqli_*` avec **requêtes préparées** pour prévenir les injections SQL.
    * Implémentation de **jetons Anti-CSRF** sur tous les formulaires (`core.php`, `login.php`, `profile.php`, etc.).
    * Nettoyage et sécurisation de toutes les variables `$_POST` et `$_GET` (`core.php`).
    * **HTML Purifier :** Intégration de la bibliothèque HTML Purifier pour nettoyer en profondeur tout le contenu HTML généré par les utilisateurs (articles, commentaires, widgets) et prévenir les attaques XSS (`core.php`, `post.php`).
    * **Content Security Policy (CSP) :** Ajout d'en-têtes CSP (via `config_settings.php`) pour une protection accrue contre le XSS et l'injection de contenu.

* **Connexion Sociale (OAuth) :**
    * Ajout de la possibilité de s'inscrire et de se connecter via **Google**.
    * Intégration de la bibliothèque `Hybridauth` (via Composer) pour gérer l'authentification OAuth2 sécurisée.
    * Création automatique d'un compte utilisateur si l'adresse e-mail Google n'existe pas dans la base de données (`social_callback.php`).

* **Synchronisation des Avatars :**
    * L'avatar du profil Google de l'utilisateur est automatiquement récupéré et défini comme avatar sur le site lors de l'inscription ou de la connexion (`social_callback.php`).
    * L'avatar est mis à jour à chaque nouvelle connexion pour refléter les changements effectués sur Google.

---

### 🛡️ Sécurité (Base v2.8.1)

* **Prévention des Injections SQL :** Migration complète vers les requêtes préparées `mysqli`.
* **Protection XSS :**
    * Utilisation de `htmlspecialchars()` sur toutes les sorties (`echo`) non-HTML.
    * Implémentation de **HTML Purifier** pour tout le contenu riche (articles, commentaires, widgets).
* **Protection CSRF :** Ajout de jetons anti-CSRF sur tous les formulaires sensibles (connexion, inscription, profil, commentaires, admin).
* **Rate Limiting (Anti-Brute Force) :** Ajout d'un blocage de connexion pendant 5 minutes après 5 échecs pour empêcher les attaques par force brute.
* **Sécurité des Mots de Passe (Base v2.4+) :** Le stockage des mots de passe a été migré de `sha256` (obsolète) vers les fonctions PHP modernes et sécurisées `password_hash()` et `password_verify()` (`login.php`, `profile.php`, `install/done.php`).

---

### ⚡️ Performance et Optimisation (Base v2.8.1)

* **Correction des Requêtes N+1 :** Optimisation majeure des requêtes SQL dans la barre latérale et le tableau de bord pour réduire drastiquement le nombre d'appels à la base de données.
    * La liste des catégories et le comptage des articles sont désormais effectués en **1 seule requête** (au lieu de N+1) (`core.php`).
    * La liste des commentaires récents (sidebar et dashboard) récupère les auteurs et les articles en **1 seule requête** (au lieu de 2N+1) (`core.php`, `admin/dashboard.php`).

---

### ✨ Engagement des Utilisateurs

Ces fonctionnalités ont été ajoutées pour augmenter l'engagement des utilisateurs et améliorer l'expérience de lecture et de rédaction.

* **Système de Favoris :** Les utilisateurs connectés peuvent enregistrer des articles dans une liste personnelle (`my-favorites.php`) via un bouton AJAX sur la page de l'article (`post.php`).
* **Profils Auteurs Publics :** Une nouvelle page `author.php` affiche la biographie et tous les articles d'un auteur. Les noms d'auteurs sur le site sont désormais cliquables.
* **Badges de Commentaires :** Un système de "gamification" qui affiche des badges (ex: "Pipelette", "Actif", "Fidèle") à côté du nom des utilisateurs en fonction de leur nombre de commentaires (`core.php`).

---

### 🎨 Interface Utilisateur (UI/UX)

* **Mode Sombre (Dark Mode) :** Un bouton de bascule (lune/soleil) a été ajouté à la barre de navigation. Le site respecte la préférence système de l'utilisateur (clair/sombre) et sauvegarde le choix dans le `localStorage` du navigateur (`core.php`, `assets/css/phpblog.css`).

---

### 🔧 Administration (Tableau de bord)

Le tableau de bord a été modernisé pour être plus utile et visuel.

* **Statistiques Exploitables :** Remplacement de l'ancienne liste de statistiques par des cartes d'action rapide (Articles Publiés, Ébauches, Commentaires en attente, Messages non lus) (`admin/dashboard.php`).
* **Graphique des Vues :** Ajout d'un graphique à barres (Chart.js) affichant les 5 articles les plus populaires en fonction de leurs vues (`admin/dashboard.php`, `admin/header.php`).
* **Aperçu Rapide :** Ajout d'un widget affichant la version du blog, le nombre total d'utilisateurs et le thème actif (`admin/dashboard.php`).
* **Création d'Utilisateurs :** Les administrateurs peuvent désormais créer de nouveaux utilisateurs (Admin, Éditeur, Utilisateur) directement depuis le panneau d'administration (`admin/add_user.php`, `admin/users.php`).
* **Système d'Ébauches (Drafts) :** Les administrateurs peuvent désormais enregistrer des articles en tant que "Ébauche", "Publié" ou "Inactif", améliorant le flux de travail de rédaction (`admin/add_post.php`, `admin/posts.php`).
* **Temps de Lecture Estimé :** Affiche une estimation du temps de lecture (ex: "Lecture : 4 min") sur toutes les listes d'articles et les pages d'articles (`core.php`, `index.php`, `blog.php`, etc.).

---

### 🔒 Sécurité (Renforcement)

Des mesures de sécurité critiques ont été ajoutées pour protéger le site et ses utilisateurs.

* **Protection CSRF (Cross-Site Request Forgery) :** Tous les formulaires (publics et admin) ainsi que toutes les actions de suppression/modification (liens GET) sont désormais protégés par des jetons de session uniques (`core.php`, `admin/header.php`, et tous les fichiers de formulaire).
* **Limitation des Tentatives de Connexion :** Le formulaire de connexion (`login.php`) bloque désormais les tentatives de connexion pendant 5 minutes après 5 échecs pour empêcher les attaques par force brute.
* **Sécurité des Mots de Passe (Base v2.4+) :** Le stockage des mots de passe a été migré de `sha256` (obsolète) vers les fonctions PHP modernes et sécurisées `password_hash()` et `password_verify()` (`login.php`, `profile.php`, `install/done.php`).

---

### 🐞 Corrections de Bugs

* **Correction du Menu Public :** Le menu principal du site (`core.php`) n'affiche désormais que les éléments ayant le statut \"Publiée\", masquant ainsi les brouillons.
* **Correction Layout Admin :** Correction d'un bug de mise en page dans `admin/users.php` qui provoquait un affichage incorrect du footer (balises `<section>` et `<div>` manquantes).
* **Correction Avatars Admin :** Correction d'un bug d'affichage où les avatars d'utilisateurs de grande taille déformaient le widget \"Recent Comments\" dans le tableau de bord (`admin/header.php`).
* **Correction Marquee :** Correction d'une faute de frappe (`&;`) dans la barre de défilement \"Latest Posts\" (`core.php`).
* **Correction Page Recherche :** Correction d'un bug d'affichage (HTML échappé) sur la page de recherche `search.php` lors de l'affichage du nom de l'auteur.
* **Correction Erreur Fatale :** Correction d'une erreur `Fatal error: Cannot redeclare short_text()` dans `admin/header.php` lors de l'inclusion de `core.php`.
* **Correction Installation :** Correction d'une erreur de chemin (`config.php`) lors du processus d'installation (`install/done.php`).
