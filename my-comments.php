<?php
include "core.php";
head();

$user_id = $rowu['id'];

if ($logged == 'No') {
    echo '<meta http-equiv="refresh" content="0;url=login">';
    exit;
}

if (isset($_GET['delete-comment'])) {
    $id = (int)$_GET["delete-comment"];
    
    // Utiliser une requête préparée
    $stmt = mysqli_prepare($connect, "DELETE FROM `comments` WHERE user_id=? AND id=?");
    mysqli_stmt_bind_param($stmt, "ii", $user_id, $id); 
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    // Rediriger pour nettoyer l'URL
    echo '<meta http-equiv="refresh" content="0;url=my-comments.php">';
    exit;
}

if ($settings['sidebar_position'] == 'Left') {
	sidebar();
}
?>
    <div class="col-md-8 mb-3">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white"><i class="fa fa-comments"></i> My Comments</div>
            <div class="card-body">

<?php
$query = mysqli_query($connect, "SELECT * FROM comments WHERE user_id='$user_id' ORDER BY created_at DESC");
$count = mysqli_num_rows($query);
if ($count <= 0) {
    echo '<div class="alert alert-info">You have not written any comments yet.</div>';
} else {
    while ($comment = mysqli_fetch_array($query)) {
        
        // --- LOGIQUE D'AFFICHAGE DE RÉPONSE ---
        $reply_info = '';
        if ($comment['parent_id'] > 0) {
            
            $stmt_parent = mysqli_prepare($connect, "SELECT user_id, guest FROM `comments` WHERE id = ?");
            mysqli_stmt_bind_param($stmt_parent, "i", $comment['parent_id']);
            mysqli_stmt_execute($stmt_parent);
            $result_parent = mysqli_stmt_get_result($stmt_parent);
            
            if($parent = mysqli_fetch_assoc($result_parent)) {
                $parent_author_name = 'Guest';
                if ($parent['guest'] == 'Yes') {
                    $parent_author_name = $parent['user_id'];
                } else {
                    // Récupérer le nom d'utilisateur réel si c'est un membre
                    $stmt_parent_user = mysqli_prepare($connect, "SELECT username FROM `users` WHERE id = ? LIMIT 1");
                    mysqli_stmt_bind_param($stmt_parent_user, "i", $parent['user_id']);
                    mysqli_stmt_execute($stmt_parent_user);
                    $parent_user_result = mysqli_stmt_get_result($stmt_parent_user);
                    if ($parent_user_row = mysqli_fetch_assoc($parent_user_result)) {
                        $parent_author_name = $parent_user_row['username'];
                    }
                    mysqli_stmt_close($stmt_parent_user);
                }
                $reply_info = '<small class="text-muted d-block mb-2">
                                  <i class="fas fa-reply"></i> 
                                  En réponse à <b>' . htmlspecialchars($parent_author_name) . '</b>
                               </small>';
            }
            mysqli_stmt_close($stmt_parent);
        }
        // --- FIN LOGIQUE D'AFFICHAGE DE RÉPONSE ---
        
		echo '
			<div class="card mb-3 shadow-sm">
			  <div class="card-body p-3">
					<h6 class="card-title mb-1">
                        <i class="fas fa-newspaper text-muted"></i> On post: 
                        <a href="post?name=' . post_slug($comment['post_id']) . '#comment-' . $comment['id'] . '" class="text-primary">' . post_title($comment['post_id'])  . '</a>
					</h6>
                    ' . $reply_info . '
					
                    <div class="p-2 border rounded bg-light mb-3">
                        ' . format_comment_with_code(html_entity_decode($comment['comment'])) . '
                    </div>

					<div class="d-flex justify-content-between align-items-center">
                        <div>
                            <a href="edit-comment.php?id=' . $comment['id'] . '" class="btn btn-primary btn-sm me-2" title="To modify">
                                <i class="fa fa-edit"></i> Edit
                            </a>
                            <a href="?delete-comment=' . $comment['id']  . '" class="btn btn-danger btn-sm" title="Delete" onclick="return confirm(\'Are you sure you want to delete your comment?\');">
                                <i class="fa fa-trash"></i> Delete
                            </a>
                        </div>
                        
                        <div class="text-end">
                            <small class="text-muted d-block">
								<i class="far fa-calendar-alt"></i> ' . date($settings['date_format'] . ' H:i', strtotime($comment['created_at'])) . '
							</small>
                            <small class="text-muted">
								'; 
								if ($comment['approved'] == 'Yes') {
									echo '<span class="badge bg-success"><i class="fas fa-check"></i> Approved</span>';
								} else {
									echo '<span class="badge bg-secondary"><i class="fas fa-clock"></i> Pending</span>';
								}
								echo '
							</small>
						</div>
					</div>
			  </div>
			</div>			
	    ';
	}
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