<?php
include "core.php";
head();

if ($logged == 'No') {
    echo '<meta http-equiv="refresh" content="0;url=login">';
    exit;
}

$user_id = $rowu['id']; // Récupérer l'ID de l'utilisateur connecté

// --- Vérification du rôle de l'utilisateur connecté ---
$is_admin_or_editor = ($rowu['role'] == 'Admin' || $rowu['role'] == 'Editor');

if ($settings['sidebar_position'] == 'Left') {
	sidebar();
}
?>
    <div class="col-md-8 mb-3">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white"><i class="fas fa-pen-square"></i> My submitted articles</div>
            <div class="card-body">

<?php
// Préparation de la requête
$user_posts_query = mysqli_prepare($connect, "SELECT * FROM `posts` WHERE author_id = ? ORDER BY created_at DESC");
mysqli_stmt_bind_param($user_posts_query, "i", $user_id);
mysqli_stmt_execute($user_posts_query);
$user_posts_result = mysqli_stmt_get_result($user_posts_query);

if (mysqli_num_rows($user_posts_result) <= 0) {
    echo '<div class="alert alert-info">You have not submitted any articles yet.</div>';
} else {
    // Boucle d'affichage en mode "Carte"
    while ($post_row = mysqli_fetch_assoc($user_posts_result)) {
        
        // 1. Gestion de l'image (Placeholder ou Image réelle)
        $image = "";
        if($post_row['image'] != "") {
            $image = '<img src="' . htmlspecialchars($post_row['image']) . '" alt="' . htmlspecialchars($post_row['title']) . '" class="rounded-start" width="100%" height="100%" style="object-fit: cover;">';
        } else {
            $image = '<svg class="bd-placeholder-img rounded-start" width="100%" height="100%" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Placeholder: Thumbnail" preserveAspectRatio="xMidYMid slice" focusable="false">
            <title>No Image</title><rect width="100%" height="100%" fill="#55595c"/>
            <text x="37%" y="50%" fill="#eceeef" dy=".3em">No Image</text></svg>';
        }

        // 2. Gestion du statut (Badge)
        $status_badge = '';
        if ($post_row['active'] == 'Yes') {
            $status_badge = '<span class="badge bg-success ms-2" style="font-size: 0.7em;">Published</span>';
        } else if ($post_row['active'] == 'Pending') {
            $status_badge = '<span class="badge bg-info ms-2" style="font-size: 0.7em;">Pending</span>';
        } else {
            $status_badge = '<span class="badge bg-warning ms-2" style="font-size: 0.7em;">Draft</span>';
        }
        
        // 3. Bouton Edit conditionnel
        $edit_button = '';
        if ($is_admin_or_editor) {
            $edit_button = '
                <a href="admin/posts.php?edit-id=' . $post_row['id'] . '" class="btn btn-primary btn-sm">
                    <i class="fa fa-edit"></i> Edit
                </a>
            ';
        }

        // 4. Affichage de la Carte
        echo '
			<div class="card mb-3 shadow-sm">
			  <div class="row g-0">
				<div class="col-md-4">
                  <a href="post?name=' . htmlspecialchars($post_row['slug']) . '">
                    ' . $image . '
                  </a>
                </div>
				<div class="col-md-8">
				  <div class="card-body py-3">
					<div class="row mb-2">
                        <div class="col-md-9">
							<h5 class="card-title mb-0">
                                <a href="post?name=' . htmlspecialchars($post_row['slug']) . '" class="text-decoration-none text-dark fw-bold">' . htmlspecialchars($post_row['title']) . '</a>
                                ' . $status_badge . '
                            </h5>
						</div>
                        <div class="col-md-3 d-flex justify-content-end align-items-start">
                            ' . $edit_button . '
						</div>
					</div>
                    
                    <p class="card-text text-muted">' . short_text(strip_tags(html_entity_decode($post_row['content'])), 120) . '</p>
					
                    <p class="card-text">
                        <small class="text-muted">
                            <i class="far fa-calendar-alt"></i> Submitted on: ' . date($settings['date_format'], strtotime($post_row['created_at'])) . '
                        </small>
                    </p>
				  </div>
				</div>
			  </div>
			</div>			
	    ';
    }
}
mysqli_stmt_close($user_posts_query);
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