<?php
include "core.php";
head();

// Afficher la sidebar si configurée à gauche
if ($settings['sidebar_position'] == 'Left') {
    sidebar();
}

$slug = $_GET['name'];
if (empty($slug)) {
    echo '<meta http-equiv="refresh" content="0; url=blog">';
    exit();
}

// --- ÉTAPE 1 : Récupération SÉCURISÉE de la catégorie ---
// On utilise une requête préparée pour éviter les failles SQL
$stmt = mysqli_prepare($connect, "SELECT * FROM `categories` WHERE slug=?");
mysqli_stmt_bind_param($stmt, "s", $slug);
mysqli_stmt_execute($stmt);
$runq = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($runq) == 0) {
    // Si la catégorie n'existe pas, redirection
    echo '<meta http-equiv="refresh" content="0; url=blog">';
    exit();
}

$rw = mysqli_fetch_assoc($runq);
mysqli_stmt_close($stmt);

$category_id   = $rw['id'];
$category_name = $rw['category'];
$category_desc = $rw['description']; // Nouveau champ
$category_img  = $rw['image'];       // Nouveau champ
?>

<div class="col-md-8 mb-3">

    <div class="card mb-4 border-0 shadow-sm overflow-hidden">
        <?php if (!empty($category_img) && file_exists($category_img)): ?>
            <div style="height: 250px; overflow: hidden;">
                <img src="<?php echo $category_img; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($category_name); ?>" style="width: 100%; height: 100%; object-fit: cover; object-position: center;">
            </div>
        <?php endif; ?>
        
        <div class="card-body bg-primary text-white rounded-bottom">
            <h2 class="card-title mb-0"><i class="far fa-folder-open me-2"></i> <?php echo htmlspecialchars($category_name); ?></h2>
            <?php if (!empty($category_desc)): ?>
                <hr class="my-2" style="opacity: 0.3;">
                <p class="card-text" style="font-size: 1.05em; opacity: 0.95;">
                    <?php echo nl2br(htmlspecialchars($category_desc)); ?>
                </p>
            <?php endif; ?>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <h5 class="mb-0 text-muted">Latest articles in <b><?php echo htmlspecialchars($category_name); ?></b></h5>
        </div>
        <div class="card-body">

<?php
// Configuration de la pagination
$postsperpage = 8;
$pageNum = 1;

if (isset($_GET['page'])) {
    $pageNum = intval($_GET['page']); // Sécurité : force en entier
}
if ($pageNum < 1) { $pageNum = 1; }

$rows = ($pageNum - 1) * $postsperpage;

// --- ÉTAPE 4 : Récupération SÉCURISÉE des articles ---
$sql = "SELECT * FROM posts WHERE category_id=? AND active='Yes' AND publish_at <= NOW() ORDER BY id DESC LIMIT ?, ?";
$stmt = mysqli_prepare($connect, $sql);
mysqli_stmt_bind_param($stmt, "iii", $category_id, $rows, $postsperpage);
mysqli_stmt_execute($stmt);
$run = mysqli_stmt_get_result($stmt);
$count = mysqli_num_rows($run);

if ($count <= 0) {
    echo '<div class="alert alert-info">There are no articles published in this category at the moment.</div>';
} else {
    while ($row = mysqli_fetch_assoc($run)) {
        
        // Gestion de l'image de l'article
        $image_post = "";
        if($row['image'] != "") {
            $image_post = '<img src="' . htmlspecialchars($row['image']) . '" alt="' . htmlspecialchars($row['title']) . '" class="rounded-start" width="100%" height="200" style="object-fit: cover;">';
        } else {
            // Image par défaut (placeholder)
            $image_post = '<div class="bg-light d-flex align-items-center justify-content-center rounded-start" style="height: 200px; width: 100%; color: #ccc;">
                        <i class="fas fa-image fa-3x"></i>
                      </div>';
        }
        
        // Affichage de la carte Article
        echo '
        <div class="card mb-4 border-0 border-bottom pb-3">
            <div class="row g-0">
                <div class="col-md-4">
                    <a href="post?name=' . htmlspecialchars($row['slug']) . '">
                        '. $image_post .'
                    </a>
                </div>
                <div class="col-md-8">
                    <div class="card-body py-2">
                        <a href="post?name=' . htmlspecialchars($row['slug']) . '" class="text-decoration-none">
                            <h4 class="card-title text-primary mb-2">' . htmlspecialchars($row['title']) . '</h4>
                        </a>
                        
                        <div class="d-flex flex-wrap justify-content-between align-items-center mb-2 text-muted small">
                            <span>
                                <i class="fas fa-user me-1"></i> ' . post_author($row['author_id']) . ' &nbsp;&bull;&nbsp;
                                <i class="far fa-calendar-alt me-1"></i> ' . date($settings['date_format'], strtotime($row['created_at'])) . '
                            </span>
                            <span class="badge bg-light text-dark border">
                                <i class="fas fa-clock me-1"></i> ' . get_reading_time($row['content']) . '
                            </span>
                        </div>
                        
                        <p class="card-text text-secondary">' . htmlspecialchars(short_text(strip_tags(html_entity_decode($row['content'])), 180)) . '</p>
                        
                        <a href="post?name=' . htmlspecialchars($row['slug']) . '" class="btn btn-sm btn-outline-primary">
                            Lire la suite <i class="fas fa-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>';
    }
    
    // --- ÉTAPE 5 : Pagination ---
    $stmt_count = mysqli_prepare($connect, "SELECT COUNT(id) AS numrows FROM posts WHERE category_id=? AND active='Yes' AND publish_at <= NOW()");
    mysqli_stmt_bind_param($stmt_count, "i", $category_id);
    mysqli_stmt_execute($stmt_count);
    $result_count = mysqli_stmt_get_result($stmt_count);
    $row_count = mysqli_fetch_assoc($result_count);
    $numrows = $row_count['numrows'];
    $maxPage = ceil($numrows / $postsperpage);
    
    // Nettoyage des statements
    mysqli_stmt_close($stmt); 
    mysqli_stmt_close($stmt_count);

    // Affichage des boutons de pagination
    if ($maxPage > 1) {
        $safe_category_slug = urlencode($slug);

        echo '<nav aria-label="Page navigation" class="mt-5"><ul class="pagination justify-content-center">';
        
        // Bouton Précédent
        if ($pageNum > 1) {
            echo '<li class="page-item"><a class="page-link" href="?name='.$safe_category_slug.'&page='.($pageNum-1).'">&laquo; Previous</a></li>';
        } else {
             echo '<li class="page-item disabled"><span class="page-link">&laquo; Previous</span></li>';
        }

        // Numéros
        for ($page = 1; $page <= $maxPage; $page++) {
            $active = ($page == $pageNum) ? 'active' : '';
            echo '<li class="page-item '.$active.'"><a class="page-link" href="?name='.$safe_category_slug.'&page='.$page.'">'.$page.'</a></li>';
        }

        // Bouton Suivant
        if ($pageNum < $maxPage) {
            echo '<li class="page-item"><a class="page-link" href="?name='.$safe_category_slug.'&page='.($pageNum+1).'">Next &raquo;</a></li>';
        } else {
            echo '<li class="page-item disabled"><span class="page-link">Next &raquo;</span></li>';
        }
        
        echo '</ul></nav>';
    }
}
?>
        </div>
    </div>
</div>

<?php
// Afficher la sidebar si configurée à droite
if ($settings['sidebar_position'] == 'Right') {
    sidebar();
}
footer();
?>