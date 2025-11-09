<?php
// MODIFICATION : Utilisation de include_once pour éviter les erreurs de "redeclare"
include_once '../core.php'; 
// session_start() est déjà dans core.php

if (isset($_SESSION['sec-username'])) {
    $uname = $_SESSION['sec-username'];
    
    // Use prepared statement for session check
    $stmt = mysqli_prepare($connect, "SELECT * FROM `users` WHERE username=? AND (role='Admin' OR role='Editor')");
    mysqli_stmt_bind_param($stmt, "s", $uname);
    mysqli_stmt_execute($stmt);
    $suser = mysqli_stmt_get_result($stmt);
    $count = mysqli_num_rows($suser);
    mysqli_stmt_close($stmt);

    if ($count <= 0) {
        // --- MODIFICATION : Remplacement de <meta> par header() ---
        header("Location: " . $settings['site_url']);
        exit;
    }
    $user = mysqli_fetch_assoc($suser);
} else {
    // --- MODIFICATION : Remplacement de <meta> par header() ---
    header("Location: ../login");
    exit;
}

// --- NOUVEL AJOUT : Validation CSRF pour les actions GET ---
$csrf_token = $_SESSION['csrf_token'];
if (isset($_GET['delete-id']) || isset($_GET['up-id']) || isset($_GET['down-id']) || isset($_GET['delete_bgrimg']) || isset($_GET['unsubscribe']) || isset($_GET['approve-comment']) || isset($_GET['delete-comment'])) {
    validate_csrf_token_get();
}
// --- FIN AJOUT ---


if (basename($_SERVER['SCRIPT_NAME']) != 'add_post.php' 
 && basename($_SERVER['SCRIPT_NAME']) != 'posts.php' 
 && basename($_SERVER['SCRIPT_NAME']) != 'add_page.php' 
 && basename($_SERVER['SCRIPT_NAME']) != 'pages.php' 
 && basename($_SERVER['SCRIPT_NAME']) != 'add_widget.php' 
 && basename($_SERVER['SCRIPT_NAME']) != 'widgets.php' 
 && basename($_SERVER['SCRIPT_NAME']) != 'add_image.php' 
 && basename($_SERVER['SCRIPT_NAME']) != 'gallery.php'
 && basename($_SERVER['SCRIPT_NAME']) != 'settings.php'
 && basename($_SERVER['SCRIPT_NAME']) != 'newsletter.php') {
}

if ($user['role'] == "Editor" && 
		(
		 basename($_SERVER['SCRIPT_NAME']) != 'dashboard.php' &&
		 basename($_SERVER['SCRIPT_NAME']) != 'add_post.php' && 
		 basename($_SERVER['SCRIPT_NAME']) != 'posts.php' && 
		 basename($_SERVER['SCRIPT_NAME']) != 'add_image.php' && 
		 basename($_SERVER['SCRIPT_NAME']) != 'gallery.php' && 
		 basename($_SERVER['SCRIPT_NAME']) != 'upload_file.php' &&
		 basename($_SERVER['SCRIPT_NAME']) != 'files.php'
		)
	) {
    // --- MODIFICATION : Remplacement de <meta> par header() ---
    header("Location: dashboard.php");
    exit;
}

function byte_convert($size)
{
    if ($size < 1024)
        return $size . ' Byte';
    if ($size < 1048576)
        return sprintf("%4.2f KB", $size / 1024);
    if ($size < 1073741824)
        return sprintf("%4.2f MB", $size / 1048576);
    if ($size < 1099511627776)
        return sprintf("%4.2f GB", $size / 1073741824);
    else
        return sprintf("%4.2f TB", $size / 1073741824);
}

// Variable pour la page active (utilisée dans la sidebar)
$current_page = basename($_SERVER['SCRIPT_NAME']);

// --- NOUVEL AJOUT : Requêtes pour les badges ---
if ($user['role'] == "Admin") {
    // Compter les messages non lus
    $stmt_msg = mysqli_prepare($connect, "SELECT COUNT(id) AS count FROM messages WHERE viewed='No'");
    mysqli_stmt_execute($stmt_msg);
    $result_msg = mysqli_stmt_get_result($stmt_msg);
    $unread_messages_count = mysqli_fetch_assoc($result_msg)['count'];
    mysqli_stmt_close($stmt_msg);

    // Compter les commentaires en attente
    $stmt_comm = mysqli_prepare($connect, "SELECT COUNT(id) AS count FROM comments WHERE approved='No'");
    mysqli_stmt_execute($stmt_comm);
    $result_comm = mysqli_stmt_get_result($stmt_comm);
    $pending_comments_count = mysqli_fetch_assoc($result_comm)['count'];
    mysqli_stmt_close($stmt_comm);
} else {
    $unread_messages_count = 0;
    $pending_comments_count = 0;
}

// --- NOUVEL AJOUT : Compter tous les articles ---
$total_posts_count = 0;
$stmt_total_posts = mysqli_prepare($connect, "SELECT COUNT(id) AS count FROM posts");
mysqli_stmt_execute($stmt_total_posts);
$result_total_posts = mysqli_stmt_get_result($stmt_total_posts);
$total_posts_count = mysqli_fetch_assoc($result_total_posts)['count'];
mysqli_stmt_close($stmt_total_posts);
// --- FIN AJOUT ---

// --- NOUVEAUX COMPTAGES POUR LES BADGES ---

// Total Users (Admin only)
$total_users_count = 0;
if ($user['role'] == "Admin") {
    $user_count_query = mysqli_query($connect, "SELECT COUNT(id) AS count FROM `users`");
    $total_users_count = mysqli_fetch_assoc($user_count_query)['count'];
}

// Total Pages (Admin only)
$total_pages_count = 0;
if ($user['role'] == "Admin") {
    $page_count_query = mysqli_query($connect, "SELECT COUNT(id) AS count FROM `pages`");
    $total_pages_count = mysqli_fetch_assoc($page_count_query)['count'];
}

// Total Categories (Admin only)
$total_categories_count = 0;
if ($user['role'] == "Admin") {
    $cat_count_query = mysqli_query($connect, "SELECT COUNT(id) AS count FROM `categories`");
    $total_categories_count = mysqli_fetch_assoc($cat_count_query)['count'];
}

// Total Widgets (Admin only)
$total_widgets_count = 0;
if ($user['role'] == "Admin") {
    $widget_count_query = mysqli_query($connect, "SELECT COUNT(id) AS count FROM `widgets`");
    $total_widgets_count = mysqli_fetch_assoc($widget_count_query)['count'];
}

// Total Subscribers (Admin only)
$total_subscribers_count = 0;
if ($user['role'] == "Admin") {
    $sub_count_query = mysqli_query($connect, "SELECT COUNT(id) AS count FROM `newsletter`");
    $total_subscribers_count = mysqli_fetch_assoc($sub_count_query)['count'];
}

// Total Images (All roles)
$img_count_query = mysqli_query($connect, "SELECT COUNT(id) AS count FROM `gallery`");
$total_images_count = mysqli_fetch_assoc($img_count_query)['count'];

// Total Albums (Admin only)
$total_albums_count = 0;
if ($user['role'] == "Admin") {
    $album_count_query = mysqli_query($connect, "SELECT COUNT(id) AS count FROM `albums`");
    $total_albums_count = mysqli_fetch_assoc($album_count_query)['count'];
}

// Total Files (All roles)
$file_count_query = mysqli_query($connect, "SELECT COUNT(id) AS count FROM `files`");
$total_files_count = mysqli_fetch_assoc($file_count_query)['count'];

// --- NOUVEAUX COMPTAGES POUR LES BADGES (MENU) ---
$menu_published_count = 0;
$menu_draft_count = 0;
if ($user['role'] == "Admin") {
    // Compte les menus publiés
    $menu_pub_query = mysqli_query($connect, "SELECT COUNT(id) AS count FROM `menu` WHERE active='Yes'");
    $menu_published_count = mysqli_fetch_assoc($menu_pub_query)['count'];
    
    // Compte les menus en brouillon
    $menu_draft_query = mysqli_query($connect, "SELECT COUNT(id) AS count FROM `menu` WHERE active='No'");
    $menu_draft_count = mysqli_fetch_assoc($menu_draft_query)['count'];
}

// --- NOUVEAUX COMPTAGES POUR LES WIDGETS ---
$widget_active_count = 0;
$widget_inactive_count = 0;
if ($user['role'] == "Admin") {
    // Compte les widgets actifs
    $widget_active_query = mysqli_query($connect, "SELECT COUNT(id) AS count FROM `widgets` WHERE active='Yes'");
    $widget_active_count = mysqli_fetch_assoc($widget_active_query)['count'];
    
    // Compte les widgets inactifs
    $widget_inactive_query = mysqli_query($connect, "SELECT COUNT(id) AS count FROM `widgets` WHERE active='No'");
    $widget_inactive_count = mysqli_fetch_assoc($widget_inactive_query)['count'];
}

// --- NOUVEAUX COMPTAGES POUR LES POSTS (STATUTS) ---

// 1. Publiés (Actifs et date de publication passée)
$posts_published_query = mysqli_query($connect, "SELECT COUNT(id) AS count FROM `posts` WHERE active='Yes' AND publish_at <= NOW()");
$posts_published_count = mysqli_fetch_assoc($posts_published_query)['count'];

// 2. Planifiés (Actifs mais date de publication future)
$posts_scheduled_query = mysqli_query($connect, "SELECT COUNT(id) AS count FROM `posts` WHERE active='Yes' AND publish_at > NOW()");
$posts_scheduled_count = mysqli_fetch_assoc($posts_scheduled_query)['count'];

// 3. Brouillons (Inactifs)
$posts_draft_query = mysqli_query($connect, "SELECT COUNT(id) AS count FROM `posts` WHERE active='No'");
$posts_draft_count = mysqli_fetch_assoc($posts_draft_query)['count'];

// 4. En vedette (Actifs et publiés)
$posts_featured_query = mysqli_query($connect, "SELECT COUNT(id) AS count FROM `posts` WHERE active='Yes' AND featured='Yes' AND publish_at <= NOW()");
$posts_featured_count = mysqli_fetch_assoc($posts_featured_query)['count'];
// --- FIN COMPTAGES POSTS ---

// --- FIN DES NOUVEAUX COMPTAGES ---

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>phpBlog - Admin Panel</title>
    <META NAME="ROBOTS" CONTENT="NOINDEX, NOFOLLOW">
    <meta name="author" content="Antonov_WEB" />
    <link rel="shortcut icon" href="../assets/img/favicon.png" />

    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <link rel="stylesheet" href="assets/adminlte/plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="assets/adminlte/dist/css/adminlte.min.css">
    
    <link rel="stylesheet" href="assets/adminlte/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="assets/adminlte/plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
    
    <link rel="stylesheet" href="assets/adminlte/plugins/summernote/summernote-bs4.min.css">
    
    <link href="https://cdn.jsdelivr.net/npm/@yaireo/tagify/dist/tagify.css" rel="stylesheet" type="text/css" />
    
    <script src="assets/adminlte/plugins/jquery/jquery.min.js"></script>

    <style>
        /* Correction pour que la table s'affiche correctement */
        .dataTables_wrapper .row:first-child {
            padding-top: 0.85em;
        }
        .dashboard-member-activity-avatar {
          width: 64px;
          height: 64px;
          border-radius: 50%;
          object-fit: cover;
        }
        /* Style pour Tagify */
        .tagify{
            --tag-bg: #007bff;
            --tag-text-color: #ffffff;
            border: 1px solid #ced4da;
        }
        .tagify__input{
            font-size: 1rem;
            line-height: 1.5;
        }
    </style>
</head>
<body class="hold-transition sidebar-mini">
<div class="wrapper">

    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
            </li>
            <li class="nav-item d-none d-sm-inline-block">
                <a href="<?php echo $settings['site_url']; ?>" class="nav-link" target="_blank"><i class="fas fa-eye"></i> Visit Site</a>
            </li>
        </ul>

        <ul class="navbar-nav ml-auto">
            <li class="nav-item">
                <a class="nav-link" href="../logout" role="button">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </li>
        </ul>
    </nav>
    <aside class="main-sidebar sidebar-dark-primary elevation-4">
        <a href="dashboard.php" class="brand-link">
            <i class="fas fa-toolbox brand-image img-circle elevation-3" style="opacity: .8; padding-left: 10px; padding-top: 10px;"></i>
            <span class="brand-text font-weight-light">phpBlog Admin</span>
        </a>

        <div class="sidebar">
            <div class="user-panel mt-3 pb-3 mb-3 d-flex">
                <div class="image">
                    <img src="../<?php echo htmlspecialchars($user['avatar']); ?>" class="img-circle elevation-2" alt="User Image">
                </div>
                <div class="info">
                    <a href="../profile" target="_blank" class="d-block"><?php echo htmlspecialchars($user['username']); ?></a>
                </div>
            </div>

            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                    
                    <li class="nav-item">
                        <a href="dashboard.php" class="nav-link <?php if ($current_page == 'dashboard.php') echo 'active'; ?>">
                            <i class="nav-icon fas fa-tachometer-alt"></i>
                            <p>Dashboard</p>
                        </a>
                    </li>

                    <?php if ($user['role'] == "Admin"): ?>
                    <?php
                    // --- GROUPE APPARENCE ---
                    $appearance_pages = ['menu_editor.php', 'add_menu.php', 'widgets.php', 'add_widget.php'];
                    $is_appearance_open = in_array($current_page, $appearance_pages);
                    ?>
                    <li class="nav-item <?php if ($is_appearance_open) echo 'menu-is-opening menu-open'; ?>">
                        <a href="#" class="nav-link <?php if ($is_appearance_open) echo 'active'; ?>">
                            <i class="nav-icon fas fa-paint-brush"></i>
                            <p>
                                Appearance
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="menu_editor.php" class="nav-link <?php if (in_array($current_page, ['menu_editor.php', 'add_menu.php'])) echo 'active'; ?>">
                                    <i class="nav-icon fas fa-bars"></i>
                                    <p>
                                        Menu Editor
                                        <span class="badge badge-success right"><?php echo $menu_published_count; ?></span>
                                        
                                        <?php if ($menu_draft_count > 0): ?>
                                        <span class="badge badge-warning right" style="margin-right: 0rem;"><?php echo $menu_draft_count; ?></span>
                                        <?php endif; ?>
                                    </p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="widgets.php" class="nav-link <?php if (in_array($current_page, ['widgets.php', 'add_widget.php'])) echo 'active'; ?>">
                                    <i class="nav-icon fas fa-puzzle-piece"></i>
                                    <p>
                                        Widgets
                                        <span class="badge badge-success right"><?php echo $widget_active_count; ?></span>
                                        
                                        <?php if ($widget_inactive_count > 0): ?>
                                        <span class="badge badge-warning right" style="margin-right: 0rem;"><?php echo $widget_inactive_count; ?></span>
                                        <?php endif; ?>
                                    </p>
                                </a>
                            </li>
                        </ul>
                    </li>

                    <?php
                    // --- GROUPE SITE ---
                    $site_pages = ['settings.php', 'messages.php', 'read_message.php', 'users.php', 'add_user.php', 'newsletter.php'];
                    $is_site_open = in_array($current_page, $site_pages);
                    ?>
                    <li class="nav-item <?php if ($is_site_open) echo 'menu-is-opening menu-open'; ?>">
                        <a href="#" class="nav-link <?php if ($is_site_open) echo 'active'; ?>">
                            <i class="nav-icon fas fa-cogs"></i>
                            <p>
                                Site
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="settings.php" class="nav-link <?php if ($current_page == 'settings.php') echo 'active'; ?>">
                                    <i class="nav-icon fas fa-cogs"></i>
                                    <p>Site Settings</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="messages.php" class="nav-link <?php if (in_array($current_page, ['messages.php', 'read_message.php'])) echo 'active'; ?>">
                                    <i class="nav-icon fas fa-envelope"></i>
                                    <p>Messages
                                        <?php if ($unread_messages > 0): ?>
                                        <span class="badge badge-danger right"><?php echo $unread_messages; ?></span>
                                        <?php endif; ?>
                                    </p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="users.php" class="nav-link <?php if (in_array($current_page, ['users.php', 'add_user.php'])) echo 'active'; ?>">
                                    <i class="nav-icon fas fa-users"></i>
                                    <p>Users <span class="badge badge-info right"><?php echo $total_users_count; ?></span></p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="newsletter.php" class="nav-link <?php if ($current_page == 'newsletter.php') echo 'active'; ?>">
                                    <i class="nav-icon fas fa-at"></i>
                                    <p>Newsletter <span class="badge badge-info right"><?php echo $total_subscribers_count; ?></span></p>
                                </a>
                            </li>
                        </ul>
                    </li>
                    <?php endif; ?>
                    
                    <li class="nav-header">CONTENT</li>
                    
                    <?php
                    // --- GROUPE POSTS ---
                    $posts_pages = ['add_post.php', 'posts.php', 'categories.php', 'add_category.php', 'comments.php'];
                    $is_posts_open = in_array($current_page, $posts_pages);
                    ?>
                    <li class="nav-item <?php if ($is_posts_open) echo 'menu-is-opening menu-open'; ?>">
                        <a href="#" class="nav-link <?php if ($is_posts_open) echo 'active'; ?>">
                            <i class="nav-icon fas fa-pen-square"></i>
                            <p>
                                Blog
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="add_post.php" class="nav-link <?php if ($current_page == 'add_post.php') echo 'active'; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Add Post</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="posts.php" class="nav-link <?php if ($current_page == 'posts.php') echo 'active'; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>
                                        All Posts
                                        
                                        <span class="badge badge-success right"><?php echo $posts_published_count; ?></span>
                                        
                                        <?php if ($posts_draft_count > 0): ?>
                                        <span class="badge badge-warning right" style="margin-right: 0.8rem;"><?php echo $posts_draft_count; ?></span>
                                        <?php endif; ?>
                                        
                                        <?php if ($posts_featured_count > 0): ?>
                                        <span class="badge badge-danger right" style="margin-right: 4rem;"><?php echo $posts_featured_count; ?></span>
                                        <?php endif; ?>

                                        <?php if ($posts_scheduled_count > 0): ?>
                                        <span class="badge badge-info right" style="margin-right: 5.8rem;"><?php echo $posts_scheduled_count; ?></span>
                                        <?php endif; ?>                                        
                                    </p>
                                </a>
                            </li>
                            <?php if ($user['role'] == "Admin"): ?>
                            <li class="nav-item">
                                <a href="categories.php" class="nav-link <?php if (in_array($current_page, ['categories.php', 'add_category.php'])) echo 'active'; ?>">
                                    <i class="nav-icon fas fa-list-alt"></i>
                                    <p>Categories <span class="badge badge-info right"><?php echo $total_categories_count; ?></span></p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="comments.php" class="nav-link <?php if ($current_page == 'comments.php') echo 'active'; ?>">
                                    <i class="nav-icon fas fa-comments"></i>
                                    <p>Comments
                                        <?php if ($pending_comments_count > 0): ?>
                                        <span class="badge badge-warning right"><?php echo $pending_comments_count; ?></span>
                                        <?php endif; ?>
                                    </p>
                                </a>
                            </li>
                            <?php endif; ?>
                        </ul>

                    <?php if ($user['role'] == "Admin"): ?>
                    <?php
                    // --- GROUPE PAGES ---
                    $pages_pages = ['add_page.php', 'pages.php'];
                    $is_pages_open = in_array($current_page, $pages_pages);
                    ?>
                    <li class="nav-item <?php if ($is_pages_open) echo 'menu-is-opening menu-open'; ?>">
                        <a href="#" class="nav-link <?php if ($is_pages_open) echo 'active'; ?>">
                            <i class="nav-icon fas fa-file-alt"></i>
                            <p>
                                Pages
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="add_page.php" class="nav-link <?php if ($current_page == 'add_page.php') echo 'active'; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Add Page</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="pages.php" class="nav-link <?php if (in_array($current_page, ['pages.php', 'add_page.php'])) echo 'active'; ?>">
                                    <i class="nav-icon fas fa-file-alt"></i>
                                    <p>All Pages <span class="badge badge-info right"><?php echo $total_pages_count; ?></span></p>
                                </a>
                            </li>
                        </ul>
                    </li>
                    <?php endif; ?>

                    <?php
                    // --- GROUPE GALLERY ---
                    $gallery_pages = ['add_image.php', 'gallery.php', 'albums.php', 'add_album.php'];
                    $is_gallery_open = in_array($current_page, $gallery_pages);
                    ?>
                    <li class="nav-item <?php if ($is_gallery_open) echo 'menu-is-opening menu-open'; ?>">
                        <a href="#" class="nav-link <?php if ($is_gallery_open) echo 'active'; ?>">
                            <i class="nav-icon fas fa-images"></i>
                            <p>
                                Gallery
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="add_image.php" class="nav-link <?php if ($current_page == 'add_image.php') echo 'active'; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Add Image</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="gallery.php" class="nav-link <?php if ($current_page == 'gallery.php') echo 'active'; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>All Images <span class="badge badge-info right"><?php echo $total_images_count; ?></span></p>
                                </a>
                            </li>
                            <?php if ($user['role'] == "Admin"): ?>
                            <li class="nav-item">
                                <a href="albums.php" class="nav-link <?php if (in_array($current_page, ['albums.php', 'add_album.php'])) echo 'active'; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Albums <span class="badge badge-info right"><?php echo $total_albums_count; ?></span></p>
                                </a>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </li>

                    <li class="nav-item">
                        <a href="files.php" class="nav-link <?php if (in_array($current_page, ['files.php', 'upload_file.php'])) echo 'active'; ?>">
                            <i class="nav-icon fas fa-folder-open"></i>
                            <p>Files</p>
                        </a>
                    </li>
                    
                </ul>
            </nav>
            </div>
        </aside>

    <div class="content-wrapper">