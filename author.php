<?php
include "core.php";
// require_once 'vendor/htmlpurifier/library/HTMLPurifier.auto.php';

head();

if ($settings['sidebar_position'] == 'Left') {
	sidebar();
}

// 1. Récupérer le nom de l'auteur depuis l'URL
$username = $_GET['username'] ?? '';
if (empty($username)) {
    echo '<meta http-equiv="refresh" content="0; url=blog.php">';
    exit();
}

// 2. Récupérer les informations de l'auteur
$stmt_author = mysqli_prepare($connect, "SELECT * FROM `users` WHERE username = ? LIMIT 1");
mysqli_stmt_bind_param($stmt_author, "s", $username);
mysqli_stmt_execute($stmt_author);
$result_author = mysqli_stmt_get_result($stmt_author);

if (mysqli_num_rows($result_author) == 0) {
    // Auteur non trouvé
    echo '<meta http-equiv="refresh" content="0; url=blog.php">';
    exit();
}

$author = mysqli_fetch_assoc($result_author);
$author_id = $author['id'];
$author_name = htmlspecialchars($author['username']);
$author_avatar = htmlspecialchars($author['avatar']);

// --- NOUVELLE LOGIQUE ---
// Initialiser le purificateur
$config = HTMLPurifier_Config::createDefault();
$purifier = new HTMLPurifier($config);

// Nettoyer la biographie
$author_bio = $purifier->purify(html_entity_decode($author['bio'] ?? ''));

// Déterminer le badge pour le rôle
$role_badge = '';
if ($author['role'] == 'Admin') {
    $role_badge = '<span class="badge bg-danger ms-2">Admin</span>';
} elseif ($author['role'] == 'Editor') {
    $role_badge = '<span class="badge bg-success ms-2">Editor</span>';
} else {
    $role_badge = '<span class="badge bg-primary ms-2">User</span>';
}
// --- FIN NOUVELLE LOGIQUE ---

// --- Récupérer les nouveaux champs ---
$author_website = htmlspecialchars($author['website'] ?? '');
$author_location = htmlspecialchars($author['location'] ?? '');
// --- FIN MODIFICATION ---
mysqli_stmt_close($stmt_author);
?>

<div class="col-md-8 mb-3">

    <div class="card shadow-sm mb-3">
        <div class="card-body">
        <div class="row g-0">
            <div class="col-md-3 d-flex justify-content-center align-items-center p-3">
                <img src="<?php echo $author_avatar; ?>" class="img-fluid rounded-circle shadow-lg" alt="<?php echo $author_name; ?>" style="width: 150px; height: 150px; object-fit: cover;">
            </div>
                <div class="col-md-9">
                    <h4 class="card-title text-primary"><i class="fas fa-user-circle"></i> <?php echo $author_name; ?><?php echo $role_badge; ?></h4>
                    
                    <div class="text-muted mb-2">
                        <?php if (!empty($author_location)): ?>
                            <span>
                                <i class="fas fa-map-marker-alt"></i> 
                                <?php echo $author_location; ?>
                            </span>
                        <?php endif; ?>
                        
                        <?php if (!empty($author_website)): ?>
                            <span class="ms-3">
                                <i class="fas fa-globe"></i> 
                                <a href="<?php echo $author_website; ?>" target="_blank" rel="noopener noreferrer">
                                    Visit the website
                                </a>
                            </span>
                        <?php endif; ?>
                    </div>
                    <hr>
                    <p class="card-text">
                        <label for="bio"><i class="fa fa-info-circle"></i> Biography:</label><br>
                        <?php 
                        if (!empty($author_bio)) {
                            echo $author_bio;
                        } else {
                            echo '<i>This user has not written a biography yet.</i>';
                        }
                        ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white"><i class="far fa-file-alt"></i> Items from <?php echo $author_name; ?></div>
        <div class="card-body">

<?php
// 5. Logique de pagination (identique à blog.php mais filtrée par author_id)
$postsperpage = 8;
$pageNum = 1;
if (isset($_GET['page'])) {
    $pageNum = (int)$_GET['page'];
}
if (!is_numeric($pageNum) || $pageNum < 1) {
    echo '<meta http-equiv="refresh" content="0; url=blog.php">';
    exit();
}
$rows = ($pageNum - 1) * $postsperpage;

// 6. Compter le total des articles pour la pagination
$stmt_count = mysqli_prepare($connect, "SELECT COUNT(id) AS numrows FROM posts WHERE author_id=? AND active='Yes' AND publish_at <= NOW()");
mysqli_stmt_bind_param($stmt_count, "i", $author_id);
mysqli_stmt_execute($stmt_count);
$result_count = mysqli_stmt_get_result($stmt_count);
$row_count = mysqli_fetch_assoc($result_count);
$numrows = $row_count['numrows'];
mysqli_stmt_close($stmt_count);

if ($numrows == 0) {
    echo '<div class="alert alert-info">This author has not yet published any articles.</div>';
} else {
    // 7. Récupérer les articles paginés
    $stmt_posts = mysqli_prepare($connect, "SELECT * FROM posts WHERE author_id=? AND active='Yes' AND publish_at <= NOW() ORDER BY id DESC LIMIT ?, ?");
    mysqli_stmt_bind_param($stmt_posts, "iii", $author_id, $rows, $postsperpage);
    mysqli_stmt_execute($stmt_posts);
    $run = mysqli_stmt_get_result($stmt_posts);

    while ($row = mysqli_fetch_assoc($run)) {
        
        $image = "";
        if($row['image'] != "") {
            $image = '<img src="' . htmlspecialchars($row['image']) . '" alt="' . htmlspecialchars($row['title']) . '" class="rounded-start" width="100%" height="100%" style="object-fit: cover;">';
        } else {
            $image = '<svg class="bd-placeholder-img rounded-start" width="100%" height="100%" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Placeholder: Thumbnail" preserveAspectRatio="xMidYMid slice" focusable="false">
            <title>No Image</title><rect width="100%" height="100%" fill="#55595c"/>
            <text x="37%" y="50%" fill="#eceeef" dy=".3em">No Image</text></svg>';
        }
        
        echo '
                        <div class="card mb-3 border-0 border-bottom">
                            <div class="row g-0">
								<div class="col-md-4">
									<a href="post?name=' . htmlspecialchars($row['slug']) . '">
										'. $image .'
									</a>
								</div>
								<div class="col-md-8">
									<div class="card-body py-3">
										<div class="d-flex justify-content-between align-items-start row">
											<div class="col-md-9">
												<a href="post?name=' . htmlspecialchars($row['slug']) . '" class="text-decoration-none">
													<h5 class="card-title text-primary">' . htmlspecialchars($row['title']) . '</h5>
												</a>
											</div>
											<div class="col-md-3 text-end">
												<a href="category?name=' . htmlspecialchars(post_categoryslug($row['category_id'])) . '">
													<span class="badge bg-secondary">' . htmlspecialchars(post_category($row['category_id'])) . '</span>
												</a>
											</div>
										</div>
										
										<div class="d-flex justify-content-between align-items-center mb-2">
											<small class="text-muted">
												Posté le <b><i><i class="far fa-calendar-alt"></i> ' . date($settings['date_format'] . ' H:i', strtotime($row['created_at'])) . '</i></b>
                                                
                                                <span class="ms-3">
                                                    <b><i>' . get_reading_time($row['content']) . '</i></b>
                                                </span>
											</small>
                                            <small class="text-muted"><i class="fas fa-comments"></i>
												<a href="post?name=' . htmlspecialchars($row['slug']) . '#comments" class="blog-comments text-decoration-none"><b>' . post_commentscount($row['id']) . '</b></a>
											</small>
										</div>
										
										<p class="card-text">' . htmlspecialchars(short_text(strip_tags(html_entity_decode($row['content'])), 200)) . '</p>
                                        
                                        <a href="post?name=' . htmlspecialchars($row['slug']) . '" class="btn btn-sm btn-outline-primary mt-2">
									        Read more
								        </a>
									</div>
								</div>
							</div>
						</div>
';
    }
    mysqli_stmt_close($stmt_posts);
    
    // 8. Afficher la pagination
    $maxPage = ceil($numrows / $postsperpage);
    $pagenums = '';
    $safe_username = urlencode($username); // Encoder le nom pour l'URL
    
    echo '<center class="mt-4">';
    
    // Ajout des boutons First/Previous
    if ($pageNum > 1) {
        $page     = $pageNum - 1;
        $previous = "<a href=\"?username=$safe_username&page=$page\" class='btn btn-outline-secondary m-1'><i class='fa fa-arrow-left'></i> Previous</a> ";
        $first = "<a href=\"?username=$safe_username&page=1\" class='btn btn-outline-secondary m-1'>First</a> ";
    } else {
        $previous = '';
        $first    = '';
    }
    
    echo $first . $previous;

    // Affichage des numéros de page
    for ($page = 1; $page <= $maxPage; $page++) {
        $active_class = ($page == $pageNum) ? 'btn-primary' : 'btn-outline-primary';
        $pagenums .= "<a href='?username=$safe_username&page=$page' class='btn $active_class m-1'>$page</a> ";
    }
    echo $pagenums;

    // Ajout des boutons Next/Last
    if ($pageNum < $maxPage) {
        $page = $pageNum + 1;
        $next = "<a href=\"?username=$safe_username&page=$page\" class='btn btn-outline-secondary m-1'><i class='fa fa-arrow-right'></i> Next</a> ";
        $last = "<a href=\"?username=$safe_username&page=$maxPage\" class='btn btn-outline-secondary m-1'>Last</a> ";
    } else {
        $next = '';
        $last = '';
    }
    
    echo $next . $last;
    
    echo '</center>';
}
?>

        </div>
    </div>
</div>

<?php
if ($settings['sidebar_position'] == 'Right') {
	sidebar();
}
footer();
?>