<?php
include "core.php";
head();

if ($settings['sidebar_position'] == 'Left') {
	sidebar();
}
?>
            <div class="col-md-8 mb-3">

                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white"><i class="fas fa-search"></i> Search</div>
                    <div class="card-body">

<?php
if (isset($_GET['q'])) {
    $word = $_GET['q'];
    
    if (strlen($word) < 2) {
        echo '<div class="alert alert-warning">Enter at least 2 characters to search.</div>';
    } else {
        
        $search_word = '%' . $word . '%'; // Terme pour le LIKE

        // 1. Compter le nombre total de résultats avec une requête préparée
        $stmt_count = mysqli_prepare($connect, "SELECT COUNT(id) AS numrows FROM posts WHERE active='Yes' AND publish_at <= NOW() AND (title LIKE ? OR content LIKE ?)");
        mysqli_stmt_bind_param($stmt_count, "ss", $search_word, $search_word);
        mysqli_stmt_execute($stmt_count);
        $result_count = mysqli_stmt_get_result($stmt_count);
        $row_count    = mysqli_fetch_assoc($result_count);
        $numrows      = $row_count['numrows'];
        mysqli_stmt_close($stmt_count);

        if ($numrows == 0) {
            echo '<div class="alert alert-info">No results found for <b>"' . htmlspecialchars($word) . '"</b>.</div>';
        } else {
        
            echo '<div class="alert alert-success">' . $numrows . ' results found for <b>"' . htmlspecialchars($word) . '"</b></div>';

            $postsperpage = 8;

            $pageNum = 1;
            if (isset($_GET['page'])) {
                // S'assurer que pageNum est un entier
                $pageNum = (int)$_GET['page'];
            }
            // Vérifier que pageNum est valide
            if (!is_numeric($pageNum) || $pageNum < 1) {
                echo '<meta http-equiv="refresh" content="0; url=blog">';
                exit();
            }
            $rows = ($pageNum - 1) * $postsperpage;

            // 2. Récupérer les résultats paginés avec une requête préparée
            $stmt_results = mysqli_prepare($connect, "SELECT * FROM `posts` WHERE (title LIKE ? OR content LIKE ?) AND active='Yes' AND publish_at <= NOW() ORDER BY id DESC LIMIT ?, ?");
            // "ssii" -> string, string, integer, integer
            mysqli_stmt_bind_param($stmt_results, "ssii", $search_word, $search_word, $rows, $postsperpage);
            mysqli_stmt_execute($stmt_results);
            $run = mysqli_stmt_get_result($stmt_results);
            
            while ($row = mysqli_fetch_assoc($run)) {
                
                $image = "";
                if($row['image'] != "") {
                    // Utiliser htmlspecialchars pour les attributs alt
                    $image = '<img src="' . htmlspecialchars($row['image']) . '" alt="' . htmlspecialchars($row['title']) . '" class="rounded-start" width="100%" height="100%" style="object-fit: cover;">';
                } else {
                    $image = '<svg class="bd-placeholder-img rounded-start" width="100%" height="100%" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Placeholder: Thumbnail" preserveAspectRatio="xMidYMid slice" focusable="false">
                    <title>No Image</title><rect width="100%" height="100%" fill="#55595c"/>
                    <text x="37%" y="50%" fill="#eceeef" dy=".3em">No Image</text></svg>';
                }
                
                // Utiliser htmlspecialchars pour toutes les sorties
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
                                                        Posted by <b><i><i class="fas fa-user"></i> ' . post_author($row['author_id']) . '</i></b> on <b><i><i class="far fa-calendar-alt"></i> ' . date($settings['date_format'] . ' H:i', strtotime($row['created_at'])) . '</i></b>
                                                        
                                                        <span class="ms-3">
                                                            <b><i>' . get_reading_time($row['content']) . '</i></b>
                                                        </span>
                                                        </small>
                                                    <small class="text-muted">
                                                        <i class="fas fa-thumbs-up me-2"></i><b>' . get_post_like_count($row['id']) . '</b>
                                                        
                                                        <i class="fas fa-comments ms-3"></i>
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
            mysqli_stmt_close($stmt_results); // Fermer la requête préparée
            
            // 3. Pagination (le $numrows vient de la première requête)
            $maxPage = ceil($numrows / $postsperpage);
            
            $pagenums = '';
            
            echo '<center class="mt-4">';
            
            // Encoder le terme de recherche pour l'URL
            $safe_word = urlencode($word);
            
            // Ajout des boutons First/Previous
            if ($pageNum > 1) {
                $page     = $pageNum - 1;
                $previous = "<a href=\"?q=$safe_word&page=$page\" class='btn btn-outline-secondary m-1'><i class='fa fa-arrow-left'></i> Previous</a> ";
                $first = "<a href=\"?q=$safe_word&page=1\" class='btn btn-outline-secondary m-1'>First</a> ";
            } else {
                $previous = '';
                $first    = '';
            }
            
            echo $first . $previous;

            // Affichage des numéros de page
            for ($page = 1; $page <= $maxPage; $page++) {
                $active_class = ($page == $pageNum) ? 'btn-primary' : 'btn-outline-primary';
                $pagenums .= "<a href='?q=$safe_word&page=$page' class='btn $active_class m-1'>$page</a> ";
            }
            echo $pagenums;

            // Ajout des boutons Next/Last
            if ($pageNum < $maxPage) {
                $page = $pageNum + 1;
                $next = "<a href=\"?q=$safe_word&page=$page\" class='btn btn-outline-secondary m-1'><i class='fa fa-arrow-right'></i> Next</a> ";
                $last = "<a href=\"?q=$safe_word&page=$maxPage\" class='btn btn-outline-secondary m-1'>Last</a> ";
            } else {
                $next = '';
                $last = '';
            }
            
            echo $next . $last;
            
            echo '</center>';
        }
    }
} else {
    // Rediriger vers l'accueil si aucun terme de recherche n'est fourni
    echo '<meta http-equiv="refresh" content="0; url=' . $settings['site_url'] . '">';
    exit();
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