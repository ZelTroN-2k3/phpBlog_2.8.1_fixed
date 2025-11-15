<?php
include "header.php";

// --- LOGIQUE DE MODÉRATION RAPIDE (COMMENTAIRES) ---
if ($user['role'] == "Admin") {
    
    // Gérer l'approbation
    if (isset($_GET['approve-comment'])) {
        validate_csrf_token_get(); // Valider le token
        $comment_id = (int)$_GET['approve-comment'];
        
        $stmt_approve = mysqli_prepare($connect, "UPDATE `comments` SET approved='Yes' WHERE id=?");
        mysqli_stmt_bind_param($stmt_approve, "i", $comment_id);
        mysqli_stmt_execute($stmt_approve);
        mysqli_stmt_close($stmt_approve);
        
        echo '<meta http-equiv="refresh" content="0; url=dashboard.php">'; 
        exit;
    }
    
    // Gérer la suppression
    if (isset($_GET['delete-comment'])) {
        validate_csrf_token_get(); // Valider le token
        $comment_id = (int)$_GET['delete-comment'];
        
        $stmt_delete = mysqli_prepare($connect, "DELETE FROM `comments` WHERE id=?");
        mysqli_stmt_bind_param($stmt_delete, "i", $comment_id);
        mysqli_stmt_execute($stmt_delete);
        mysqli_stmt_close($stmt_delete);
        
        echo '<meta http-equiv="refresh" content="0; url=dashboard.php">';
        exit;
    }
    // --- FIN LOGIQUE DE MODÉRATION RAPIDE (COMMENTAIRES) ---
}

// --- LOGIQUE : MODÉRATION RAPIDE (ARTICLES) ---
if ($user['role'] == "Admin") {   
    // Gérer l'approbation d'un article
    if (isset($_GET['approve-post'])) {
        validate_csrf_token_get();
        $post_id = (int)$_GET['approve-post'];
        
        $stmt_approve = mysqli_prepare($connect, "UPDATE `posts` SET active='Yes' WHERE id=?");
        mysqli_stmt_bind_param($stmt_approve, "i", $post_id);
        mysqli_stmt_execute($stmt_approve);
        mysqli_stmt_close($stmt_approve);
        
        echo '<meta http-equiv="refresh" content="0; url=dashboard.php">'; 
        exit;
    }
    
    // Gérer le rejet (suppression) d'un article
    if (isset($_GET['reject-post'])) {
        validate_csrf_token_get();
        $post_id = (int)$_GET['reject-post'];
        
        // 1. Supprimer les commentaires associés
        $stmt_del_comm = mysqli_prepare($connect, "DELETE FROM `comments` WHERE post_id=?");
        mysqli_stmt_bind_param($stmt_del_comm, "i", $post_id);
        mysqli_stmt_execute($stmt_del_comm);
        mysqli_stmt_close($stmt_del_comm);
        
        // 2. Supprimer les tags associés
        $stmt_del_tags = mysqli_prepare($connect, "DELETE FROM `post_tags` WHERE post_id=?");
        mysqli_stmt_bind_param($stmt_del_tags, "i", $post_id);
        mysqli_stmt_execute($stmt_del_tags);
        mysqli_stmt_close($stmt_del_tags);
        
        // 3. Supprimer les "j'aime" associés
        $stmt_del_likes = mysqli_prepare($connect, "DELETE FROM `post_likes` WHERE post_id=?");
        mysqli_stmt_bind_param($stmt_del_likes, "i", $post_id);
        mysqli_stmt_execute($stmt_del_likes);
        mysqli_stmt_close($stmt_del_likes);
        
        // 4. Supprimer les favoris associés
        $stmt_del_favs = mysqli_prepare($connect, "DELETE FROM `user_favorites` WHERE post_id=?");
        mysqli_stmt_bind_param($stmt_del_favs, "i", $post_id);
        mysqli_stmt_execute($stmt_del_favs);
        mysqli_stmt_close($stmt_del_favs);
        
        // 5. Finalement, supprimer l'article
        $stmt_del_post = mysqli_prepare($connect, "DELETE FROM `posts` WHERE id=?");
        mysqli_stmt_bind_param($stmt_del_post, "i", $post_id);
        mysqli_stmt_execute($stmt_del_post);
        mysqli_stmt_close($stmt_del_post);
        
        echo '<meta http-equiv="refresh" content="0; url=dashboard.php">';
        exit;
    }
    // --- FIN LOGIQUE : MODÉRATION RAPIDE (ARTICLES) ---
}

// --- LOGIQUE : MODÉRATION RAPIDE (TÉMOIGNAGES) ---
if ($user['role'] == "Admin") {
    
    // Gérer l'approbation
    if (isset($_GET['approve-testimonial'])) {
        validate_csrf_token_get();
        $testi_id = (int)$_GET['approve-testimonial'];
        
        $stmt_approve_t = mysqli_prepare($connect, "UPDATE `testimonials` SET active='Yes' WHERE id=?");
        mysqli_stmt_bind_param($stmt_approve_t, "i", $testi_id);
        mysqli_stmt_execute($stmt_approve_t);
        mysqli_stmt_close($stmt_approve_t);
        
        echo '<meta http-equiv="refresh" content="0; url=dashboard.php">'; 
        exit;
    }
    
    // Gérer la suppression
    if (isset($_GET['delete-testimonial'])) {
        validate_csrf_token_get();
        $testi_id = (int)$_GET['delete-testimonial'];
        
        $stmt_delete_t = mysqli_prepare($connect, "DELETE FROM `testimonials` WHERE id=?");
        mysqli_stmt_bind_param($stmt_delete_t, "i", $testi_id);
        mysqli_stmt_execute($stmt_delete_t);
        mysqli_stmt_close($stmt_delete_t);
        
        echo '<meta http-equiv="refresh" content="0; url=dashboard.php">';
        exit;
    }
    // --- FIN LOGIQUE DE MODÉRATION RAPIDE (TÉMOIGNAGES) ---
}

// -----------------------------------
// --- FIN DE LA LOGIQUE ---
// -----------------------------------


// Variable de version (comme dans core.php)
$phpblog_version = "2.9.4"; 

// ------------------------------------------------------------
// --- REQUÊTES POUR LES STATISTIQUES EXPLOITABLES ---
// ------------------------------------------------------------

// 1. Cartes de statistiques
$query_posts_published = mysqli_query($connect, "SELECT COUNT(id) AS count FROM posts WHERE active='Yes'");
$count_posts_published = mysqli_fetch_assoc($query_posts_published)['count'];

$query_posts_drafts = mysqli_query($connect, "SELECT COUNT(id) AS count FROM posts WHERE active='Draft'");
$count_posts_drafts = mysqli_fetch_assoc($query_posts_drafts)['count'];

$count_comments_pending = 0;
if ($user['role'] == "Admin") {
    $query_comments_pending = mysqli_query($connect, "SELECT COUNT(id) AS count FROM comments WHERE approved='No'");
    $count_comments_pending = mysqli_fetch_assoc($query_comments_pending)['count'];
}

$count_posts_pending = 0;
if ($user['role'] == "Admin") {
    $stmt_posts_pending = mysqli_prepare($connect, "SELECT COUNT(id) AS count FROM posts WHERE active='Pending'");
    mysqli_stmt_execute($stmt_posts_pending);
    $result_posts_pending = mysqli_stmt_get_result($stmt_posts_pending);
    $count_posts_pending = mysqli_fetch_assoc($result_posts_pending)['count'];
    mysqli_stmt_close($stmt_posts_pending);
}

$count_messages_unread = 0;
if ($user['role'] == "Admin") {
    $query_messages_unread = mysqli_query($connect, "SELECT COUNT(id) AS count FROM messages WHERE viewed = 'No'");
    $count_messages_unread = mysqli_fetch_assoc($query_messages_unread)['count'];
}

$query_total_users = mysqli_query($connect, "SELECT COUNT(id) AS count FROM users");
$count_total_users = mysqli_fetch_assoc($query_total_users)['count'];
$query_messages_total = mysqli_query($connect, "SELECT COUNT(id) AS count FROM messages");
$count_messages_total = mysqli_fetch_assoc($query_messages_total)['count'];

// 2. Graphique Top 5 Articles
$query_top_posts = mysqli_query($connect, "SELECT title, views FROM posts WHERE active='Yes' AND views > 0 ORDER BY views DESC LIMIT 5");
$chart_top_posts_titles = [];
$chart_top_posts_views = [];
while ($row = mysqli_fetch_assoc($query_top_posts)) {
    $chart_top_posts_titles[] = short_text($row['title'], 30); 
    $chart_top_posts_views[] = $row['views'];
}
$chart_top_posts_labels_json = json_encode($chart_top_posts_titles);
$chart_top_posts_data_json = json_encode($chart_top_posts_views);

// 3. Graphique des publications par mois (12 derniers mois)
$query_posts_per_month = mysqli_query($connect, "
    SELECT 
        DATE_FORMAT(publish_at, '%Y-%m') AS post_month,
        COUNT(id) AS post_count
    FROM posts
    WHERE publish_at > DATE_SUB(NOW(), INTERVAL 12 MONTH) AND active = 'Yes'
    GROUP BY post_month
    ORDER BY post_month ASC
    LIMIT 12
");
$chart_months_labels = [];
$chart_months_data = [];
while ($row_month = mysqli_fetch_assoc($query_posts_per_month)) {
    $chart_months_labels[] = $row_month['post_month'];
    $chart_months_data[] = $row_month['post_count'];
}
$chart_months_labels_json = json_encode($chart_months_labels);
$chart_months_data_json = json_encode($chart_months_data);

// 4. Graphique des articles par catégorie
$query_cats = mysqli_query($connect, "
    SELECT c.category, COUNT(p.id) AS post_count
    FROM categories c
    LEFT JOIN posts p ON c.id = p.category_id AND p.active = 'Yes'
    GROUP BY c.id
    HAVING post_count > 0
    ORDER BY post_count DESC
");
$chart_cat_labels = [];
$chart_cat_data = [];
while ($row_cat = mysqli_fetch_assoc($query_cats)) {
    $chart_cat_labels[] = $row_cat['category'];
    $chart_cat_data[] = $row_cat['post_count'];
}
$chart_cat_labels_json = json_encode($chart_cat_labels);
$chart_cat_data_json = json_encode($chart_cat_data);

// 5. Informations Système
/*$php_version = phpversion();
$db_version_query = mysqli_query($connect, "SELECT VERSION() as version");
$db_version = mysqli_fetch_assoc($db_version_query)['version'];
$max_upload = ini_get('upload_max_filesize');
// Infos Serveur
$server_domain = $_SERVER['SERVER_NAME'];
// Utilise SERVER_ADDR si disponible, sinon tente un gethostbyname
$server_ip = $_SERVER['SERVER_ADDR'] ?? gethostbyname($_SERVER['SERVER_NAME']); 
$server_os = php_uname('s');
$server_software = $_SERVER['SERVER_SOFTWARE'];
$server_port = $_SERVER['SERVER_PORT'];
// Infos PHP étendues
$php_memory_limit = ini_get('memory_limit');
$php_max_execution_time = ini_get('max_execution_time');
// Vérifier les extensions requises (selon le README)
$curl_status = extension_loaded('curl');
$gd_status = extension_loaded('gd');
$mbstring_status = extension_loaded('mbstring');
*/

// 6. Widget "Contenu en un coup d'œil"
$query_pages_count = mysqli_query($connect, "SELECT COUNT(id) AS count FROM pages");
$count_pages = mysqli_fetch_assoc($query_pages_count)['count'];

$query_comments_total = mysqli_query($connect, "SELECT COUNT(id) AS count FROM comments");
$count_comments_total = mysqli_fetch_assoc($query_comments_total)['count'];

$query_categories_count = mysqli_query($connect, "SELECT COUNT(id) AS count FROM categories");
$count_categories = mysqli_fetch_assoc($query_categories_count)['count'];

$query_tags_count = mysqli_query($connect, "SELECT COUNT(id) AS count FROM tags");
$count_tags = mysqli_fetch_assoc($query_tags_count)['count'];

// 7. "Derniers utilisateurs"
$query_latest_users = mysqli_query($connect, "SELECT id, username, avatar, bio, email, role, location FROM users ORDER BY id DESC LIMIT 5");
// 8. Auteurs les plus actifs (Top 5)
$query_top_authors = mysqli_query($connect, "
    SELECT u.username, COUNT(p.id) AS post_count
    FROM posts p
    JOIN users u ON p.author_id = u.id
    WHERE p.active = 'Yes' AND p.publish_at <= NOW()
    GROUP BY p.author_id
    ORDER BY post_count DESC
    LIMIT 5
");
$chart_authors_labels = [];
$chart_authors_data = [];
while ($row_author = mysqli_fetch_assoc($query_top_authors)) {
    $chart_authors_labels[] = $row_author['username'];
    $chart_authors_data[] = $row_author['post_count'];
}
$chart_authors_labels_json = json_encode($chart_authors_labels);
$chart_authors_data_json = json_encode($chart_authors_data);

// 9. Articles en attente ---
if ($user['role'] == "Admin") {
    $query_pending_posts = mysqli_query($connect, "
        SELECT p.*, u.username AS author_name, u.avatar AS author_avatar
        FROM `posts` p
        LEFT JOIN `users` u ON p.author_id = u.id
        WHERE p.active = 'Pending'
        ORDER BY p.created_at DESC
        LIMIT 5
    ");
    $posts_pending_count = mysqli_num_rows($query_pending_posts);
} else {
    $query_pending_posts = false;
    $posts_pending_count = 0;
}

// 10. compteur des modules ---
$count_testi_pending = 0;
$count_testi_total = 0;
$count_polls_total = 0;
$count_slides_total = 0;
$count_faq_total = 0;

// Témoignages
$q_testi = mysqli_query($connect, "SELECT active, COUNT(id) as count FROM testimonials GROUP BY active");
while ($r_testi = mysqli_fetch_assoc($q_testi)) {
    if ($r_testi['active'] == 'Pending') $count_testi_pending = $r_testi['count'];
    $count_testi_total += $r_testi['count'];
}
// Sondages
$q_polls = mysqli_query($connect, "SELECT COUNT(id) as count FROM polls");
$count_polls_total = mysqli_fetch_assoc($q_polls)['count'];
// Slides
$q_slides = mysqli_query($connect, "SELECT COUNT(id) as count FROM slides");
$count_slides_total = mysqli_fetch_assoc($q_slides)['count'];
// FAQ
$q_faq = mysqli_query($connect, "SELECT COUNT(id) as count FROM faqs");
$count_faq_total = mysqli_fetch_assoc($q_faq)['count'];


// 11. Informations de Sauvegarde (Déplacé depuis le HTML)
$backup_count = 0;
$last_backup_date = 'Never';
if ($user['role'] == "Admin") {
    $backup_dir = '../backup-database/';
    $backup_files = glob($backup_dir . "*.sql");
    $backup_count = count($backup_files);

    if ($backup_count > 0) {
        // Trier pour trouver le plus récent
        usort($backup_files, function($a, $b) { return filemtime($b) - filemtime($a); });
        $last_backup_date = date("d M Y, H:i", filemtime($backup_files[0]));
    }
}
// --- FIN AJOUT ---

// ------------------------------------------------------------
// --- FIN DES REQUÊTES ---
// ------------------------------------------------------------
?>


<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Dashboard</h1>
            </div><div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="#">Home</a></li>
                    <li class="breadcrumb-item active">Dashboard</li>
                </ol>
            </div>
        </div>
    </div>
</div>
<section class="content">
    <div class="container-fluid">
        
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title">Shortcuts</h3>
            </div>
            <div class="card-body text-center">
                <a href="add_post.php" class="btn btn-app bg-primary"><i class="fas fa-edit"></i> Write Post</a>
                <?php if ($user['role'] == "Admin"): ?>
                <a href="settings.php" class="btn btn-app bg-secondary"><i class="fas fa-cogs"></i> Settings</a>
                <a href="messages.php" class="btn btn-app bg-info"><i class="fas fa-envelope"></i> Messages</a>
                <a href="menu_editor.php" class="btn btn-app bg-secondary"><i class="fas fa-bars"></i> Menu</a>
                <a href="add_page.php" class="btn btn-app bg-primary"><i class="fas fa-file-alt"></i> Add Page</a>
                <?php endif; ?>
                <a href="add_image.php" class="btn btn-app bg-success"><i class="fas fa-camera-retro"></i> Add Image</a>
                <?php if ($user['role'] == "Admin"): ?>
                <a href="widgets.php" class="btn btn-app bg-secondary"><i class="fas fa-archive"></i> Widgets</a>
                <a href="add_user.php" class="btn btn-app bg-warning"><i class="fas fa-user-plus"></i> Add User</a>
                <?php endif; ?>
                <a href="upload_file.php" class="btn btn-app bg-success"><i class="fas fa-upload"></i> Upload File</a>
                <a href="<?php echo $settings['site_url']; ?>" class="btn btn-app bg-info"><i class="fas fa-eye"></i> Visit Site</a>
            </div>
        </div>

        <div class="row">
            <div class="col-12 col-sm-6 col-md-2">
                <a href="posts.php" style="color: inherit; text-decoration: none;">
                    <div class="info-box">
                        <span class="info-box-icon bg-success elevation-1"><i class="fas fa-check-circle"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Published Articles</span>
                            <span class="info-box-number"><?php echo $count_posts_published; ?></span>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-12 col-sm-6 col-md-2">
                <a href="posts.php?status=draft" style="color: inherit; text-decoration: none;">
                    <div class="info-box mb-3">
                        <span class="info-box-icon bg-warning elevation-1"><i class="fas fa-pencil-alt"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Drafts</span>
                            <span class="info-box-number"><?php echo $count_posts_drafts; ?></span>
                        </div>
                    </div>
                </a>
            </div>
            
            <?php if ($user['role'] == "Admin"): ?>
            <div class="col-12 col-sm-6 col-md-2">
                <a href="comments.php?status=pending" style="color: inherit; text-decoration: none;">
                    <div class="info-box mb-3">
                        <span class="info-box-icon bg-info elevation-1"><i class="fas fa-comments"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Pending Comments</span>
                            <span class="info-box-number"><?php echo $count_comments_pending; ?></span>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-12 col-sm-6 col-md-2">
                <a href="posts.php?status=pending" style="color: inherit; text-decoration: none;">
                    <div class="info-box mb-3">
                        <span class="info-box-icon bg-info elevation-1"><i class="fas fa-clock"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Pending Articles</span>
                            <span class="info-box-number"><?php echo $count_posts_pending; ?></span>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-12 col-sm-6 col-md-2">
                <a href="messages.php" style="color: inherit; text-decoration: none;">
                    <div class="info-box mb-3">
                        <span class="info-box-icon bg-danger elevation-1"><i class="fas fa-envelope"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Unread Messages</span>
                            <span class="info-box-number"><?php echo $count_messages_unread; ?></span>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-12 col-sm-6 col-md-2">
                <a href="testimonials.php" style="color: inherit; text-decoration: none;">
                    <div class="info-box mb-3">
                        <span class="info-box-icon bg-warning elevation-1"><i class="fas fa-star"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Testimonials</span>
                            <span class="info-box-number"><?php echo $count_testi_total; ?> 
                                <?php if($count_testi_pending > 0) echo "<small class='badge bg-danger'>{$count_testi_pending} new</small>"; ?>
                            </span>
                        </div>
                    </div>
                </a>
            </div>
            
            <div class="col-12 col-sm-6 col-md-2">
                <a href="polls.php" style="color: inherit; text-decoration: none;">
                    <div class="info-box mb-3">
                        <span class="info-box-icon bg-purple elevation-1"><i class="fas fa-poll"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Total Polls</span>
                            <span class="info-box-number"><?php echo $count_polls_total; ?></span>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-12 col-sm-6 col-md-2">
                <a href="slides.php" style="color: inherit; text-decoration: none;">
                    <div class="info-box mb-3">
                        <span class="info-box-icon bg-primary elevation-1"><i class="fas fa-images"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Total Slides</span>
                            <span class="info-box-number"><?php echo $count_slides_total; ?></span>
                        </div>
                    </div>
                </a>
            </div>
            
            <div class="col-12 col-sm-6 col-md-2">
                <a href="faq.php" style="color: inherit; text-decoration: none;">
                    <div class="info-box mb-3">
                        <span class="info-box-icon bg-info elevation-1"><i class="fas fa-question-circle"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">FAQ</span>
                            <span class="info-box-number"><?php echo $count_faq_total; ?></span>
                        </div>
                    </div>
                </a>
            </div>

        </div>
            <?php endif; ?>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card card-success">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-chart-bar"></i> Top 5 Most Viewed Articles</h3>
                    </div>
                    <div class="card-body">
                        <?php if (empty($chart_top_posts_titles)): ?>
                            <div class="alert alert-info">Not enough data to display a chart yet.</div>
                        <?php else: ?>
                            <canvas id="popularPostsChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card card-purple">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-user-edit"></i> Top 5 Most Active Authors</h3>
                    </div>
                    <div class="card-body">
                        <?php if (empty($chart_authors_labels)): ?>
                            <div class="alert alert-info">Not enough data to display a chart yet.</div>
                        <?php else: ?>
                            <canvas id="activeAuthorsChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-chart-line"></i> Publications (Last 12 Months)</h3>
                    </div>
                    <div class="card-body">
                         <?php if (empty($chart_months_labels)): ?>
                            <div class="alert alert-info">No data available for this chart yet.</div>
                        <?php else: ?>
                            <canvas id="postsPerMonthChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card card-info">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-chart-pie"></i> Category Distribution</h3>
                    </div>
                    <div class="card-body">
                        <?php if (empty($chart_cat_labels)): ?>
                            <div class="alert alert-info">No articles have been categorized yet.</div>
                        <?php else: ?>
                            <canvas id="postsPerCategoryChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <?php if ($user['role'] == "Admin"): ?>

                <div class="card card-warning">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-star"></i> Latest Pending Testimonials</h3>
                    </div>
                    <div class="card-body p-0" style="max-height: 400px; overflow-y: auto;">
                        <ul class="products-list product-list-in-card pl-2 pr-2">
                            <?php
                            $q_t = mysqli_query($connect, "SELECT * FROM testimonials WHERE active='Pending' ORDER BY id DESC LIMIT 10");
                            if(mysqli_num_rows($q_t) == 0):
                                echo '<li class="item text-center text-muted p-3">No pending testimonials.</li>';
                            else:
                                while($row_t = mysqli_fetch_assoc($q_t)):
                                    $avatar = !empty($row_t['avatar']) ? '../'.$row_t['avatar'] : '../assets/img/avatar.png';
                            ?>
                            <li class="item">
                                <div class="product-img">
                                    <img src="<?php echo $avatar; ?>" alt="Avatar" class="img-circle" style="width: 50px; height: 50px; object-fit: cover;">
                                </div>
                                <div class="product-info">
                                    <span class="product-title">
                                        <?php echo htmlspecialchars($row_t['name']); ?>
                                        <small class="badge badge-secondary float-right"><?php echo date('d M Y', strtotime($row_t['created_at'])); ?></small>
                                    </span>
                                    <span class="product-description">
                                        "<?php echo emoticons(htmlspecialchars(substr($row_t['content'], 0, 100))); ?>..."
                                    </span>
                                    <div class="mt-2">
                                        <a href="?approve-testimonial=<?php echo $row_t['id']; ?>&token=<?php echo $_SESSION['csrf_token']; ?>" class="btn btn-xs btn-success"><i class="fas fa-check"></i> Approve</a>
                                        <a href="?delete-testimonial=<?php echo $row_t['id']; ?>&token=<?php echo $_SESSION['csrf_token']; ?>" class="btn btn-xs btn-danger" onclick="return confirm('Delete?');"><i class="fas fa-trash"></i> Delete</a>
                                        <a href="testimonials.php" class="btn btn-xs btn-default float-right">View All</a>
                                    </div>
                                </div>
                            </li>
                            <?php endwhile; endif; ?>
                        </ul>
                    </div>
                </div>

                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-user-clock"></i> Latest Registered Users</h3>
                    </div>
                    <div class="card-body p-0">
                        <ul class="products-list product-list-in-card pl-2 pr-2">
                        <?php
                        if (mysqli_num_rows($query_latest_users) == 0) {
                            echo '<li class="list-group-item">No users found.</li>';
                        } else {
                            while ($row_user = mysqli_fetch_assoc($query_latest_users)) {
                                
                                // --- Logique Avatar (inchangée) ---
                                $avatar_url_raw = $row_user['avatar'];
                                $avatar_path = '';
                                $auth_badge = '';
                                
                                if (strpos($avatar_url_raw, 'http://') === 0 || strpos($avatar_url_raw, 'https://') === 0) {
                                    $avatar_path = htmlspecialchars($avatar_url_raw);
                                    $auth_badge = '<span class="badge bg-danger" style="font-size: 0.7em;"><i class="fab fa-google"></i> Google</span>';
                                } else {
                                    $avatar_path = '../' . htmlspecialchars($avatar_url_raw);
                                    $auth_badge = '<span class="badge bg-secondary" style="font-size: 0.7em;"><i class="fas fa-key"></i> Normal</span>';
                                }
                                
                                // --- AJOUT : Logique pour le badge Rôle ---
                                $role_badge = '';
                                if ($row_user['role'] == 'Admin') {
                                    $role_badge = '<span class="badge bg-success" style="font-size: 0.7em;"><i class="fas fa-user-shield"></i> Admin</span>';
                                } elseif ($row_user['role'] == 'Editor') {
                                    $role_badge = '<span class="badge bg-primary" style="font-size: 0.7em;"><i class="fas fa-user-edit"></i> Editor</span>';
                                } else {
                                    $role_badge = '<span class="badge bg-info" style="font-size: 0.7em;"><i class="fas fa-user"></i> User</span>';
                                }
                                // --- FIN AJOUT ---
                        ?>
                            <li class="item">
                                <div class="product-img">
                                    <img src="<?php echo $avatar_path; ?>" alt="Avatar" class="img-size-50 img-circle">
                                </div>
                                
                                <div class="product-info">
                                
                                    <div class="float-right text-right">
                                        <a href="users.php?edit-id=<?php echo $row_user['id']; ?>" class="btn btn-secondary btn-xs">
                                            <i class="fa fa-edit"></i> Manage
                                        </a>
                                        <div class="mt-1">
                                            <?php echo $auth_badge; ?>
                                            <?php echo $role_badge; // ?>
                                        </div>
                                    </div>

                                    <a href="users.php?edit-id=<?php echo $row_user['id']; ?>" class="product-title" style="padding-right: 70px;"> 
                                        <?php echo htmlspecialchars($row_user['username']); ?>
                                    </a>

                                    <div class="product-description text-muted" style="font-size: 0.85em; margin-bottom: 2px; padding-right: 70px;">
                                        <i class="fas fa-envelope fa-fw mr-1" title="Email"></i> <?php echo htmlspecialchars($row_user['email']); ?>
                                        <?php if (!empty($row_user['location'])): ?>
                                            <br><i class="fas fa-map-marker-alt fa-fw mr-1" title="Location"></i> <?php echo htmlspecialchars($row_user['location']); ?>
                                        <?php endif; ?>
                                    </div>
                                    <span class="product-description text-muted" style="font-size: 0.85em; display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; padding-right: 70px;"> 
                                        <?php
                                        if (!empty($row_user['bio'])) {
                                            $clean_bio = strip_tags(html_entity_decode($row_user['bio']));
                                            echo '<i>' . htmlspecialchars(short_text($clean_bio, 60)) . '</i>'; // Limite à 60 caractères
                                        } else {
                                            echo '<i>No biography available.</i>';
                                        }
                                        ?>
                                    </span>

                                </div>
                            </li>
                        <?php
                            }
                        }
                        ?>
                        </ul>
                    </div>
                    <div class="card-footer text-center">
                        <a href="users.php">View all users</a>
                    </div>
                </div>

                <div class="card card-success" id="moderation">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-gavel"></i> Quick Moderation (Comments)</h3>
                    </div>
                    
                    <?php
                    // NOUVELLE REQUÊTE : Cibler uniquement les commentaires en attente
                    $query_pending = mysqli_query($connect, "
                        SELECT c.*, p.title AS post_title, p.slug AS post_slug,
                               u.username AS user_username, u.avatar AS user_avatar
                        FROM `comments` c
                        JOIN `posts` p ON c.post_id = p.id
                        LEFT JOIN `users` u ON c.user_id = u.id AND c.guest = 'No'
                        WHERE c.approved = 'No'
                        ORDER BY c.id DESC
                        LIMIT 10
                    ");
                    $cmnts_pending = mysqli_num_rows($query_pending);
                    
                    if ($cmnts_pending == "0"): 
                    ?>
                        <div class="card-body"> 
                            <div class="alert alert-default text-center m-0 p-3">No comments pending.</div>
                        </div>
                    <?php else: ?>
                        <div class="card-body p-0" style="max-height: 300px; overflow-y: auto;">
                            <ul class="products-list product-list-in-card pl-2 pr-2">
                            <?php
                            while ($row = mysqli_fetch_assoc($query_pending)) {
                                $post_title = $row['post_title'] ?: 'N/A';
                                $avatar = 'assets/img/avatar.png'; 
                                $author_name = 'Guest'; 
                                if ($row['guest'] == 'Yes') {
                                    $author_name = $row['user_id'];
                                } else if ($row['user_username']) {
                                    $avatar = $row['user_avatar'];
                                    $author_name = $row['user_username'];
                                }
                                
                                // --- CORRECTION BUG AVATAR GOOGLE ---
                                $avatar_path = $avatar;
                                if (strpos($avatar, 'http://') !== 0 && strpos($avatar, 'https://') !== 0) {
                                    $avatar_path = '../' . htmlspecialchars($avatar);
                                }
                                // --- FIN CORRECTION ---
                            ?>
                                <li class="item">
                                    <div class="product-img">
                                        <img src="<?php echo htmlspecialchars($avatar_path); ?>" alt="Avatar" class="img-size-50 img-circle">
                                    </div>
                                    <div class="product-info">
                                        <span class="product-title">
                                            <?php echo htmlspecialchars($author_name); ?>
                                            <?php if ($row['guest'] == "Yes") echo '<span class="badge badge-info float-right"><i class="fas fa-user"></i> Guest</span>'; ?>
                                        </span>
                                        <span class="product-description">
                                            Sur: <a href="../post?name=<?php echo htmlspecialchars($row['post_slug']); ?>#comment-<?php echo $row['id']; ?>" target="_blank"><?php echo htmlspecialchars(short_text($post_title, 40)); ?></a>
                                        </span>
                                        <p class="mt-1 mb-1 text-muted"><?php echo htmlspecialchars(short_text(html_entity_decode($row['comment']), 100)); ?></p>
                                        <div>
                                            <a href="?approve-comment=<?php echo $row['id']; ?>&token=<?php echo $csrf_token; ?>" class="btn btn-success btn-xs"><i class="fas fa-check"></i> Approve</a>
                                            <a href="?delete-comment=<?php echo $row['id']; ?>&token=<?php echo $csrf_token; ?>" class="btn btn-danger btn-xs"><i class="fas fa-trash"></i> Delete</a>
                                            <a href="comments.php?edit-id=<?php echo $row['id']; ?>" class="btn btn-secondary btn-xs"><i class="fas fa-edit"></i> Edit</a>
                                        </div>
                                    </div>
                                </li>
                            <?php
                            }
                            ?>
                            </ul>
                        </div>
                        <div class="card-footer text-center">
                            <a href="comments.php" class="uppercase">View all comments</a>
                        </div>
                    <?php endif; // End if ($cmnts_pending > 0) ?>
                </div>
                
                <div class="card card-info" id="moderation-posts">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-file-signature"></i> Quick Moderation (Posts)</h3>
                    </div>
                    
                    <?php if ($posts_pending_count == 0): ?>
                        <div class="card-body"> 
                            <div class="alert alert-default text-center m-0 p-3">No posts pending.</div>
                        </div>
                    <?php else: ?>
                        <div class="card-body p-0" style="max-height: 300px; overflow-y: auto;">
                            <ul class="products-list product-list-in-card pl-2 pr-2">
                            <?php
                            while ($row_post = mysqli_fetch_assoc($query_pending_posts)) {
                                $post_title = $row_post['title'];
                                $post_slug = $row_post['slug'];
                                $post_id = $row_post['id'];
                                
                                // Gestion avatar auteur
                                $avatar = 'assets/img/avatar.png'; 
                                $author_name = 'N/A';
                                if ($row_post['author_name']) {
                                    $avatar = $row_post['author_avatar'];
                                    $author_name = $row_post['author_name'];
                                }
                                
                                // Correction bug avatar Google
                                $avatar_path = $avatar;
                                if (strpos($avatar, 'http://') !== 0 && strpos($avatar, 'https://') !== 0) {
                                    $avatar_path = '../' . htmlspecialchars($avatar);
                                }
                            ?>
                                <li class="item">
                                    <div class="product-img">
                                        <img src="<?php echo htmlspecialchars($avatar_path); ?>" alt="Avatar" class="img-size-50 img-circle">
                                    </div>
                                    <div class="product-info">
                                        <span class="product-title">
                                            <?php echo htmlspecialchars($author_name); ?>
                                        </span>
                                        <span class="product-description">
                                            Article: <a href="../post?name=<?php echo htmlspecialchars($post_slug); ?>" target="_blank"><?php echo htmlspecialchars(short_text($post_title, 40)); ?></a>
                                        </span>
                                        <p class="mt-1 mb-1 text-muted"><?php echo htmlspecialchars(short_text(strip_tags(html_entity_decode($row_post['content'])), 100)); ?></p>
                                        <div>
                                            <a href="?approve-post=<?php echo $post_id; ?>&token=<?php echo $csrf_token; ?>" class="btn btn-success btn-xs"><i class="fas fa-check"></i> Approve</a>
                                            <a href="?reject-post=<?php echo $post_id; ?>&token=<?php echo $csrf_token; ?>" class="btn btn-danger btn-xs" onclick="return confirm('Are you sure you want to reject (delete) this post?');"><i class="fas fa-trash"></i> Reject</a>
                                            <a href="posts.php?edit-id=<?php echo $post_id; ?>" class="btn btn-secondary btn-xs"><i class="fas fa-edit"></i> Edit</a>
                                        </div>
                                    </div>
                                </li>
                            <?php
                            }
                            ?>
                            </ul>
                        </div>
                        <div class="card-footer text-center">
                            <a href="posts.php" class="uppercase">View all posts</a>
                        </div>
                    <?php endif; ?>
                </div>

                <?php else: // Si c'est un Éditeur, afficher les commentaires récents ?>
                <div class="card">
                    <div class="card-header"><h3 class="card-title">Recent Comments</h3></div>
                    <div class="card-body">
                        <?php
                        $query_editor = mysqli_query($connect, "
                            SELECT c.*, p.title AS post_title,
                                   u.username AS user_username, u.avatar AS user_avatar
                            FROM `comments` c
                            JOIN `posts` p ON c.post_id = p.id
                            LEFT JOIN `users` u ON c.user_id = u.id AND c.guest = 'No'
                            ORDER BY c.id DESC
                            LIMIT 4
                        ");
                        if (mysqli_num_rows($query_editor) == 0) {
                            echo '<div class="alert alert-info">There are no posted comments.</div>';
                        } else {
                            while ($row = mysqli_fetch_assoc($query_editor)) {
                                $avatar = 'assets/img/avatar.png'; $author_name = 'Guest';
                                if ($row['guest'] == 'Yes') { $author_name = 'Guest'; }
                                else if ($row['user_username']) { $avatar = $row['user_avatar']; $author_name = $row['user_username']; }
                                
                                // --- CORRECTION BUG AVATAR GOOGLE ---
                                $avatar_path = $avatar;
                                if (strpos($avatar, 'http://') !== 0 && strpos($avatar, 'https://') !== 0) {
                                    $avatar_path = '../' . htmlspecialchars($avatar);
                                }
                                // --- FIN CORRECTION ---
                        ?>
                        <div class="row mb-2">
                            <div class="product-img">
                                <img src="<?php echo htmlspecialchars($avatar_path); ?>" alt="Avatar" class="img-size-50 img-circle">
                            </div>
                            <div class="col-md-10">
                                <span class="blue"><strong><?php echo htmlspecialchars($author_name); ?></strong> le <?php echo date($settings['date_format'], strtotime($row['created_at'])); ?></span><br />
                                <?php if ($row['approved'] == "Yes") echo '<strong>Status:</strong> <span class="badge bg-success">Approved</span>'; else echo '<strong>Status:</strong> <span class="badge bg-warning">Pending</span>'; ?>
                                <p><?php echo htmlspecialchars(short_text(html_entity_decode($row['comment']), 100)); ?></p>
                            </div>
                        </div>
                        <?php
                            }
                        }
                        ?>
                    </div>
                </div>

                <?php endif; // Fin du if ($user['role'] == "Admin") ?>
            </div>
            
            <div class="col-md-6">

                <div class="card card-secondary">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-database"></i> Content at a Glance</h3>
                    </div>
                    <div class="card-body">
                        
                        <div class="row">
                            <div class="col-lg-3 col-6">
                                <div class="small-box bg-success">
                                    <div class="inner">
                                        <h3><?php echo $count_posts_published; ?></h3>
                                        <p>Articles Publiés</p>
                                    </div>
                                    <div class="icon"><i class="fas fa-file-alt"></i></div>
                                    <a href="<?php echo $posts_link_smart; ?>" class="small-box-footer">Gérer <i class="fas fa-arrow-circle-right"></i></a>
                                </div>
                            </div>
                            <div class="col-lg-3 col-6">
                                <div class="small-box bg-info">
                                    <div class="inner">
                                        <h3><?php echo $count_pages; ?></h3>
                                        <p>Pages</p>
                                    </div>
                                    <div class="icon"><i class="fas fa-file-alt"></i></div>
                                    <a href="pages.php" class="small-box-footer">Gérer <i class="fas fa-arrow-circle-right"></i></a>
                                </div>
                            </div>
                            <div class="col-lg-3 col-6">
                                <div class="small-box bg-secondary">
                                    <div class="inner">
                                        <h3><?php echo $count_categories; ?></h3>
                                        <p>Catégories</p>
                                    </div>
                                    <div class="icon"><i class="fas fa-list-ol"></i></div>
                                    <a href="categories.php" class="small-box-footer">Gérer <i class="fas fa-arrow-circle-right"></i></a>
                                </div>
                            </div>
                            <div class="col-lg-3 col-6">
                                <div class="small-box bg-secondary">
                                    <div class="inner">
                                        <h3><?php echo $count_tags; ?></h3>
                                        <p>Tags</p>
                                    </div>
                                    <div class="icon"><i class="fas fa-tags"></i></div>
                                    <a href="posts.php" class="small-box-footer">Gérer <i class="fas fa-arrow-circle-right"></i></a>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-lg-3 col-6">
                                <?php $comment_link = ($count_comments_pending > 0) ? "comments.php?status=pending" : "comments.php"; ?>
                                <div class="small-box bg-danger">
                                    <div class="inner">
                                        <h3><?php echo $count_comments_total; ?></h3>
                                        <p>Commentaires</p>
                                    </div>
                                    <div class="icon"><i class="fas fa-comments"></i></div>
                                    <a href="<?php echo $comment_link; ?>" class="small-box-footer">Gérer <i class="fas fa-arrow-circle-right"></i></a>
                                </div>
                            </div>
                             <div class="col-lg-3 col-6">
                                <?php $testi_link = ($count_testi_pending > 0) ? "testimonials.php" : "testimonials.php"; ?>
                                <div class="small-box bg-warning">
                                    <div class="inner">
                                        <h3><?php echo $count_testi_total; ?></h3>
                                        <p>Témoignages</p>
                                    </div>
                                    <div class="icon"><i class="fas fa-star"></i></div>
                                    <a href="<?php echo $testi_link; ?>" class="small-box-footer">Gérer <i class="fas fa-arrow-circle-right"></i></a>
                                </div>
                            </div>
                            <div class="col-lg-3 col-6">
                                <div class="small-box bg-danger">
                                    <div class="inner">
                                        <h3><?php echo $count_messages_total; ?></h3>
                                        <p>Messages</p>
                                    </div>
                                    <div class="icon"><i class="fas fa-envelope"></i></div>
                                    <a href="messages.php" class="small-box-footer">Gérer <i class="fas fa-arrow-circle-right"></i></a>
                                </div>
                            </div>
                            <div class="col-lg-3 col-6">
                                <div class="small-box bg-dark">
                                    <div class="inner">
                                        <h3><?php echo $count_total_users; ?></h3>
                                        <p>Utilisateurs</p>
                                    </div>
                                    <div class="icon"><i class="fas fa-users"></i></div>
                                    <a href="users.php" class="small-box-footer">Gérer <i class="fas fa-arrow-circle-right"></i></a>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                             <div class="col-lg-3 col-6">
                                <div class="small-box bg-purple">
                                    <div class="inner">
                                        <h3><?php echo $count_polls_total; ?></h3>
                                        <p>Sondages</p>
                                    </div>
                                    <div class="icon"><i class="fas fa-poll"></i></div>
                                    <a href="polls.php" class="small-box-footer">Gérer <i class="fas fa-arrow-circle-right"></i></a>
                                </div>
                            </div>
                            <div class="col-lg-3 col-6">
                                <div class="small-box bg-primary">
                                    <div class="inner">
                                        <h3><?php echo $count_slides_total; ?></h3>
                                        <p>Slider</p>
                                    </div>
                                    <div class="icon"><i class="fas fa-images"></i></div>
                                    <a href="slides.php" class="small-box-footer">Gérer <i class="fas fa-arrow-circle-right"></i></a>
                                </div>
                            </div>
                            <div class="col-lg-3 col-6">
                                <div class="small-box bg-info">
                                    <div class="inner">
                                        <h3><?php echo $count_faq_total; ?></h3>
                                        <p>FAQ</p>
                                    </div>
                                    <div class="icon"><i class="fas fa-question-circle"></i></div>
                                    <a href="faq.php" class="small-box-footer">Gérer <i class="fas fa-arrow-circle-right"></i></a>
                                </div>
                            </div>
                            <div class="col-lg-3 col-6">
                                <div class="small-box bg-primary">
                                    <div class="inner">
                                        <h3><?php echo $backup_count; ?></h3>
                                        <p>Sauvegardes BDD</p>
                                    </div>
                                    <div class="icon"><i class="fas fa-database"></i></div>
                                    <a href="backup.php" class="small-box-footer">Gérer <i class="fas fa-arrow-circle-right"></i></a>
                                </div>
                            </div>
                        </div>
                        
                        <?php if ($user['role'] == "Admin"): ?>
                        <hr class="my-2">
                        <p class="card-text mb-0">
                            <small class="text-muted">
                                You have <span class="badge bg-warning text-dark"><?php echo $count_posts_drafts; ?></span> draft(s), 
                                <a href="#moderation"><span class="badge bg-info"><?php echo $count_comments_pending; ?></span> comment(s)</a> and
                                <a href="#moderation-posts"><span class="badge bg-info"><?php echo $count_posts_pending; ?></span> post(s)</a> pending.
                            </small>
                        </p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- div class="card card-outline card-info">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-server"></i> System Information</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item d-flex justify-content-between align-items-center border-0 px-0">
                                        Server Domain
                                    </li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item d-flex justify-content-between align-items-center border-0 px-0">
                                        phpBlog Version
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div -->
                
                <?php if ($user['role'] == "Admin"): ?>
                <div class="card card-secondary">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-shield-alt"></i> System Health</h3>
                    </div>
                    <div class="card-body">
                        <strong>Database Backups</strong>
                        <p class="text-muted">
                            Total files: <span class="badge bg-primary"><?php echo $backup_count; ?></span><br>
                            Last backup: <span class="text-<?php echo ($last_backup_date == 'Never' ? 'danger' : 'success'); ?>"><?php echo $last_backup_date; ?></span>
                        </p>
                        <a href="backup.php" class="btn btn-sm btn-primary"><i class="fas fa-download"></i> Manage Backups</a>
                    </div>
                </div>
                

                <?php endif; ?>
            </div>
        </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', (event) => {
    
    // --- GRAPHIQUE BARRES (Top 5) ---
    const ctxBar = document.getElementById('popularPostsChart');
    if (ctxBar) {
        const postLabels = <?php echo $chart_top_posts_labels_json; ?>;
        const postData = <?php echo $chart_top_posts_data_json; ?>;

        new Chart(ctxBar.getContext('2d'), {
            type: 'bar',
            data: {
                labels: postLabels,
                datasets: [{
                    label: 'Views',
                    data: postData,
                    backgroundColor: [
                        'rgba(40, 167, 69, 0.7)', // success
                        'rgba(0, 123, 255, 0.7)', // primary
                        'rgba(23, 162, 184, 0.7)', // info
                        'rgba(255, 193, 7, 0.7)',  // warning
                        'rgba(220, 53, 69, 0.7)'   // danger
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
                plugins: { legend: { display: false } }
            }
        });
    }

    // --- GRAPHIQUE LIGNES (Publications par mois) ---
    const ctxLine = document.getElementById('postsPerMonthChart');
    if (ctxLine) {
        const monthLabels = <?php echo $chart_months_labels_json; ?>;
        const monthData = <?php echo $chart_months_data_json; ?>;
        
        new Chart(ctxLine.getContext('2d'), {
            type: 'line',
            data: {
                labels: monthLabels,
                datasets: [{
                    label: 'Posts',
                    data: monthData,
                    fill: true,
                    backgroundColor: 'rgba(0, 123, 255, 0.2)',
                    borderColor: 'rgba(0, 123, 255, 1)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
                plugins: { legend: { display: false } }
            }
        });
    }

    // --- GRAPHIQUE PIE (Catégories) ---
    const ctxPie = document.getElementById('postsPerCategoryChart');
    if (ctxPie) {
        const catLabels = <?php echo $chart_cat_labels_json; ?>;
        const catData = <?php echo $chart_cat_data_json; ?>;
        
        new Chart(ctxPie.getContext('2d'), {
            type: 'pie',
            data: {
                labels: catLabels,
                datasets: [{
                    label: 'Posts',
                    data: catData,
                    backgroundColor: [ 
                        'rgba(0, 123, 255, 0.7)', // primary
                        'rgba(40, 167, 69, 0.7)',  // success
                        'rgba(255, 193, 7, 0.7)',  // warning
                        'rgba(220, 53, 69, 0.7)',  // danger
                        'rgba(23, 162, 184, 0.7)', // info
                        'rgba(108, 117, 125, 0.7)' // secondary
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom', 
                    }
                }
            }
        });
    }

    // --- GRAPHIQUE BARRES (Auteurs Actifs) ---
    const ctxBarAuthors = document.getElementById('activeAuthorsChart');
    if (ctxBarAuthors) {
        const authorLabels = <?php echo $chart_authors_labels_json; ?>;
        const authorData = <?php echo $chart_authors_data_json; ?>;

        new Chart(ctxBarAuthors.getContext('2d'), {
            type: 'bar', // Bar chart, as requested
            data: {
                labels: authorLabels,
                datasets: [{
                    label: 'Published Articles',
                    data: authorData,
                    backgroundColor: [ // Using purple palette
                        'rgba(102, 51, 153, 0.7)',
                        'rgba(111, 66, 193, 0.7)',
                        'rgba(120, 81, 233, 0.7)',
                        'rgba(130, 97, 255, 0.7)',
                        'rgba(140, 112, 255, 0.7)'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: { 
                    y: { 
                        beginAtZero: true, 
                        ticks: { 
                            precision: 0 // Assure des nombres entiers (on ne peut pas avoir 0.5 article)
                        } 
                    } 
                },
                plugins: { legend: { display: false } } // Pas besoin de légende pour un seul set de données
            }
        });
    }

});
</script>

 <?php
include "footer.php";
?>