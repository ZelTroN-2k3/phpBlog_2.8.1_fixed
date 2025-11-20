# Journal des modifications (Changelog)

Tous les changements notables apportés à ce projet seront documentés dans ce fichier.

Le format est basé sur [Keep a Changelog](https://keepachangelog.com/fr/1.0.0/).

## [v3.0.1] - Version actuelle
Cette version se concentre sur la stabilité, la sécurité du processus de déconnexion et des améliorations de l'interface d'administration.

### Ajouté
- Nouvelle interface dans l'administration pour personnaliser l'image d'arrière-plan de la page publique "Banni".

### Modifié
- Optimisation du tableau de bord (Dashboard) : le widget "Raccourcis" est désormais replié par défaut pour un affichage initial plus épuré.

### Corrigé
- **Critique** : Refonte complète du système de déconnexion (`logout.php`). Correction des problèmes de redirection (pages blanches ou noires) survenant sur certains serveurs de production en raison de l'envoi prématuré d'en-têtes.

## [v3.0.0]
Introduction d'un système d'installation automatisé pour faciliter le déploiement du CMS.

### Ajouté
- Nouvel assistant d'installation (Wizard) situé dans le dossier `/install`, permettant une configuration graphique de la base de données et du compte administrateur initial.

## [v2.5.0]
Ajout de fonctionnalités de modération des utilisateurs.

### Ajouté
- Système de bannissement des utilisateurs. Les administrateurs peuvent désormais bannir un utilisateur, l'empêchant de se connecter.
- Page publique spécifique pour les utilisateurs bannis.

## [v2.2.0]
Amélioration de la gestion des médias.

### Ajouté
- Nouvelle "Médiathèque" dans l'administration pour visualiser et gérer tous les fichiers uploadés sur le serveur.

## [v2.1.1]
Correctifs mineurs d'interface.

### Corrigé
- Ajustements divers sur les liens du tableau de bord et le bouton "Voir le site".

## [v2.1.0]
Extension des capacités de personnalisation du site.

### Ajouté
- **Gestionnaire de Menu** : Outil en "drag-and-drop" pour organiser facilement le menu de navigation principal du site.
- **Gestionnaire de Widgets** : Interface permettant d'activer ou de désactiver les éléments affichés dans la barre latérale (sidebar).

## [v2.0.0] - Refonte Majeure
Cette version marque une rupture importante avec le code initial du tutoriel, introduisant une interface moderne et une sécurité renforcée.

### Modifié
- **Interface Admin** : Remplacement complet de l'ancienne interface par le template **AdminLTE 3**, offrant un design responsive et professionnel.
- **Éditeur de texte** : Remplacement de CKEditor par **Summernote** pour une édition de contenu plus fluide.
- **Tableaux de données** : Intégration de **DataTables** pour améliorer l'affichage, le tri et la recherche dans toutes les listes (articles, utilisateurs, etc.).

### Sécurité
- Refonte significative de la sécurité globale :
    * Mise en place du hachage sécurisé des mots de passe.
    * Protection systématique contre les injections SQL (utilisation de requêtes préparées).
    * Protection contre les failles XSS.

## [v1.0.0] - Version Initiale
Version stable issue du tutoriel Udemy de base.

### Ajouté
- Fonctionnalités CRUD (Créer, Lire, Mettre à jour, Supprimer) de base pour :
    * Les articles de blog.
    * Les catégories.
    * Les utilisateurs.
- Système de commentaires simple.
- Partie front-office basique pour afficher le blog.