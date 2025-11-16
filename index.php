<?php
include "core.php";
head();

if ($settings['sidebar_position'] == 'Left') {
	sidebar();
}
?>
	<div class="col-md-8 mb-3">
<?php
$mt3_i = ""; // Variable pour la marge, utilisée plus bas

// --- LOGIQUE DE SÉLECTION DU SLIDER ---
// Vérifier le réglage admin
if ($settings['homepage_slider'] == 'Custom') {

    // --- A. SLIDER PERSONNALISÉ (Table 'slides') ---
    
    // S'assurer que HTMLPurifier est chargé (pour les descriptions)
    $purifier = get_purifier();
    
    $run_slides = mysqli_query($connect, "SELECT * FROM slides WHERE active='Yes' ORDER BY position_order ASC");
    $count_slides = mysqli_num_rows($run_slides);
    
    if ($count_slides > 0) {
        $i = 0;
        $mt3_i = "mt-3"; // On active la marge
?>
        <div id="carouselCustom" class="col-md-12 carousel slide mb-3 shadow-lg" data-bs-ride="carousel">
            <div class="carousel-indicators">
            <?php
            for ($j = 0; $j < $count_slides; $j++) {
                $active_ind = ($j == 0) ? 'class="active" aria-current="true"' : '';
                echo '<button type="button" data-bs-target="#carouselCustom" data-bs-slide-to="' . $j . '" ' . $active_ind . '></button>';
            }
            ?>
            </div>
            
            <div class="carousel-inner rounded">
            <?php
            while ($slide = mysqli_fetch_assoc($run_slides)) {
                $active_item = ($i == 0) ? ' active' : '';
                $image_path = htmlspecialchars($slide['image_url']);
                $link_url = htmlspecialchars($slide['link_url']);
            ?>
                <div class="carousel-item <?php echo $active_item; ?>">
                    <a href="<?php echo $link_url; ?>">
                        <img src="<?php echo $image_path; ?>" alt="<?php echo htmlspecialchars($slide['title']); ?>" class="d-block w-100" height="400" style="object-fit: cover;">
                    </a>
                    
                    <?php if (!empty($slide['title']) || !empty($slide['description'])): ?>
                    <div class="carousel-caption d-md-block" style="background: rgba(0,0,0,0.5); padding: 10px; border-radius: 5px;">
                        
                        <?php if (!empty($slide['title'])): ?>
                            <h5>
                                <a href="<?php echo $link_url; ?>" class="text-light text-decoration-none"><?php echo htmlspecialchars($slide['title']); ?></a>
                            </h5>
                        <?php endif; ?>
                        
                        <?php if (!empty($slide['description'])): ?>
                            <div class_ ="d-none d-md-block text-light">
                                <?php echo $purifier->purify($slide['description']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    
                </div>
            <?php
                $i++;
            }
            ?>
            </div>
            
            <button class="carousel-control-prev" type="button" data-bs-target="#carouselCustom" data-bs-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#carouselCustom" data-bs-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
            </button>
        </div>
<?php
    } // Fin if $count_slides > 0

} else {

    // --- B. SLIDER DES ARTICLES (Comportement par défaut 'Featured') ---
    
    // (C'est votre code original, juste collé ici)
    $run = mysqli_query($connect, "SELECT * FROM posts WHERE active='Yes' AND featured='Yes' AND publish_at <= NOW() ORDER BY id DESC");
    $count = mysqli_num_rows($run);
    if ($count > 0) {
        $i = 0;
        $mt3_i = "mt-3";
?>
<div id="carouselExampleCaptions" class="col-md-12 carousel slide mb-3 shadow-lg" data-bs-ride="carousel">
	<div class="carousel-indicators">
<?php
    while ($row = mysqli_fetch_assoc($run)) {
        $active1 = "";
        if ($i == 0) {
            $active1 = 'class="active" aria-current="true"';
        }
        
        echo '<button type="button" data-bs-target="#carouselExampleCaptions" data-bs-slide-to="' . $i . '" '. $active1 .' aria-label="' . htmlspecialchars($row['title']) . '"></button>
        ';
        
        $i++;
    }
?>
	</div>
	<div class="carousel-inner rounded">
<?php
    $j = 0;
    $run2 = mysqli_query($connect, "SELECT * FROM posts WHERE active='Yes' AND featured='Yes' AND publish_at <= NOW() ORDER BY id DESC");
    while ($row2 = mysqli_fetch_assoc($run2)) {
        $active = "";
        if ($j == 0) {
            $active = " active";
        }
        
        $image = "";
        if($row2['image'] != "") {
            $image = '<img src="' . htmlspecialchars($row2['image']) . '" alt="' . htmlspecialchars($row2['title']) . '" class="d-block w-100" height="400" style="object-fit: cover;">';
        } else {
            $image = '<svg class="bd-placeholder-img bd-placeholder-img-lg d-block w-100" height="400" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="No Image" preserveAspectRatio="xMidYMid slice" focusable="false">
						<title>' . htmlspecialchars($row2['title']) . '</title>
						<rect width="100%" height="100%" fill="#555"></rect>
						<text x="45%" y="50%" fill="black" dy=".3em">No Image</text></svg>';
        }

        echo '
        <div class="carousel-item'. $active .'">
            <a href="post?name=' . htmlspecialchars($row2['slug']) . '">' . $image . '</a>
            <div class="carousel-caption d-md-block" style="background: rgba(0,0,0,0.5); padding: 10px;">
                <h5>
					<a href="post?name=' . htmlspecialchars($row2['slug']) . '" class="text-light text-decoration-none">' . htmlspecialchars($row2['title']) . '</a>
				</h5>
				<p class="text-light">
					<i class="fas fa-calendar"></i> ' . date($settings['date_format'] . ' H:i', strtotime($row2['created_at'])) . '
				</p>
            </div>
        </div>
        ';
        
        $j++;
    }
?>
	</div>
  
	<button class="carousel-control-prev" type="button" data-bs-target="#carouselExampleCaptions" data-bs-slide="prev">
		<span class="carousel-control-prev-icon" aria-hidden="true"></span>
		<span class="visually-hidden">Previous</span>
	</button>
	<button class="carousel-control-next" type="button" data-bs-target="#carouselExampleCaptions" data-bs-slide="next">
		<span class="carousel-control-next-icon" aria-hidden="true"></span>
		<span class="visually-hidden">Next</span>
	</button>
</div>
<?php
    } // Fin if $count > 0
} // Fin du else (slider featured)
?>
            <div class="row <?php echo $mt3_i; ?>">
                <h5><i class="fa fa-list"></i> Recent Posts</h5>
<?php
// Récupère la limite depuis les paramètres de l'administration
$limit_posts = (int)$settings['posts_per_page'];
$run = mysqli_query($connect, "SELECT * FROM posts WHERE active='Yes' AND publish_at <= NOW() ORDER BY id DESC LIMIT $limit_posts");
$count = mysqli_num_rows($run);
if ($count <= 0) {
    echo '<p>There are no published posts</p>';
} else {
    while ($row = mysqli_fetch_assoc($run)) {
        
        $image = "";
        if($row['image'] != "") {
            // 1. Si une image existe dans la base de données
            $image = '<img src="' . htmlspecialchars($row['image']) . '" alt="' . htmlspecialchars($row['title']) . '" class="card-img-top" width="100%" height="208em" style="object-fit: cover;"/>';
        } else {
            // 2. Sinon, afficher l'image "No Image"
            $image = '<svg class="bd-placeholder-img card-img-top" width="100%" height="13em" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Placeholder: Thumbnail" preserveAspectRatio="xMidYMid slice" focusable="false">
            <title>No Image</title><rect width="100%" height="100%" fill="#55595c"/>
            <text x="40%" y="50%" fill="#eceeef" dy=".3em">No Image</text></svg>';
        }
        
        echo '
                    <div class="';
if ($settings['posts_per_row'] == 3) {
	echo 'col-md-4';
} else {
	echo 'col-md-6';
}
echo ' mb-3"> 
                        <div class="card shadow-sm h-100 d-flex flex-column">
                            <a href="post?name=' . htmlspecialchars($row['slug']) . '">
                                '. $image .'
                            </a>
                            <div class="card-body d-flex flex-column flex-grow-1">
                                <a href="post?name=' . htmlspecialchars($row['slug']) . '" class="text-decoration-none"><h6 class="card-title text-primary">' . htmlspecialchars($row['title']) . '</h6></a>
                                
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div>
                                        <small class="text-muted d-block"> <i class="far fa-calendar-alt"></i> ' . date($settings['date_format'] . ' H:i', strtotime($row['created_at'])) . '
                                        </small>
                                        <small class="text-muted d-block">
                                            ' . get_reading_time($row['content']) . '
                                        </small>
                                        </div>
                                    <div class="text-end">
                                        <small class="me-2 text-muted"><i class="fas fa-comments"></i> 
                                            <a href="post?name=' . htmlspecialchars($row['slug']) . '#comments" class="blog-comments text-decoration-none">
                                                <strong>' . post_commentscount($row['id']) . '</strong>
                                            </a>
                                        </small>
                                        <small class="text-muted"><i class="fas fa-thumbs-up"></i> 
                                            <strong>' . get_post_like_count($row['id']) . '</strong>
                                        </small>
                                    </div>
                                </div>
								<div class="d-flex justify-content-between align-items-center mb-2">
                                    <a href="category?name=' . post_categoryslug($row['category_id']) . '" class="text-decoration-none">
										<span class="badge bg-secondary">' . post_category($row['category_id']) . '</span>
									</a>
                                </div>

                                <p class="card-text mt-2">' . short_text(strip_tags(html_entity_decode($row['content'])), 100) . '</p>

								<a href="post?name=' . htmlspecialchars($row['slug']) . '" class="btn btn-sm btn-primary col-12 mt-auto">
									Read more
								</a>
                            </div>
                        </div>
                    </div>
';
    }
}
?>
            </div>
            <a href="blog" class="btn btn-primary col-12 mt-3">
				<i class="fas fa-arrow-alt-circle-right"></i> All posts
			</a>
<!-- Affichage des témoignages -->
<?php
    $q_testi = mysqli_query($connect, "SELECT * FROM testimonials WHERE active='Yes' ORDER BY id DESC");
    if (mysqli_num_rows($q_testi) > 0) {
    ?>
    <div class="card mb-3 mt-4 shadow-sm border-0">
        <div class="card-body bg-light rounded text-center p-4">
            <h4 class="mb-4 text-primary"><i class="fas fa-quote-left"></i> Testimonials</h4>
            
            <div id="carouselTestimonials" class="carousel carousel-dark slide" data-bs-ride="carousel">
                <div class="carousel-inner">
                    <?php
                    $t_count = 0;
                    while ($row_t = mysqli_fetch_assoc($q_testi)) {
                        $active_t = ($t_count == 0) ? 'active' : '';
                        // Gestion de l'avatar avec fallback
                        $avatar_t = !empty($row_t['avatar']) ? htmlspecialchars($row_t['avatar']) : 'assets/img/avatar.png';
                    ?>
                    <div class="carousel-item <?php echo $active_t; ?>">
                        <img src="<?php echo $avatar_t; ?>" class="rounded-circle shadow-sm mb-2" width="80" height="80" style="object-fit:cover;" alt="User Avatar">
                        <h5 class="fw-bold mb-0"><?php echo htmlspecialchars($row_t['name']); ?></h5>
                        
                        <?php if(!empty($row_t['position'])): ?>
                            <p class="text-muted small mb-3"><?php echo htmlspecialchars($row_t['position']); ?></p>
                        <?php else: ?>
                            <br>
                        <?php endif; ?>
                        
                        <div class="row justify-content-center">
                            <div class="col-md-10">
                                <p class="fst-italic text-secondary">
                                    "<?php echo emoticons(nl2br(htmlspecialchars($row_t['content']))); ?>"
                                </p>
                            </div>
                        </div>
                    </div>
                    <?php
                        $t_count++;
                    }
                    ?>
                </div>
                
                <button class="carousel-control-prev" type="button" data-bs-target="#carouselTestimonials" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Previous</span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#carouselTestimonials" data-bs-slide="next">
                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Next</span>
                </button>
            </div>
        </div>
    </div>
    <?php
    }
    ?>
        </div>
<?php
if ($settings['sidebar_position'] == 'Right') {
	sidebar();
}
footer();
?>