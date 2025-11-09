<?php
include "core.php";
head();

if ($logged == 'No') {
    echo '<meta http-equiv="refresh" content="0;url=login">';
    exit;
}

$user_id = $rowu['id']; // Récupérer l'ID de l'utilisateur connecté

if ($settings['sidebar_position'] == 'Left') {
	sidebar();
}
?>
    <div class="col-md-8 mb-3">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white"><i class="fas fa-pen-square"></i> My submitted articles</div>
            <div class="card-body">

<?php
// --- Logique copiée de profile.php ---
$user_posts_query = mysqli_prepare($connect, "SELECT * FROM `posts` WHERE author_id = ? ORDER BY created_at DESC");
mysqli_stmt_bind_param($user_posts_query, "i", $user_id);
mysqli_stmt_execute($user_posts_query);
$user_posts_result = mysqli_stmt_get_result($user_posts_query);
?>
                <div id="submitted-posts">
                    <?php if (mysqli_num_rows($user_posts_result) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($post_row = mysqli_fetch_assoc($user_posts_result)): ?>
                                        <tr>
                                            <td>
                                                <a href="post.php?name=<?php echo htmlspecialchars($post_row['slug']); ?>">
                                                    <?php echo htmlspecialchars($post_row['title']); ?>
                                                </a>
                                            </td>
                                            <td><?php echo date($settings['date_format'], strtotime($post_row['created_at'])); ?></td>
                                            <td>
                                                <?php
                                                if ($post_row['active'] == 'Yes') {
                                                    echo '<span class="badge bg-success">Published</span>';
                                                } else if ($post_row['active'] == 'Pending') {
                                                    echo '<span class="badge bg-info">Pending</span>';
                                                } else {
                                                    // Supposant que 'No' ou autre signifie Brouillon
                                                    echo '<span class="badge bg-warning">Draft</span>'; 
                                                }
                                                ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p>You have not submitted any articles yet.</p>
                    <?php endif; ?>
                </div>
            <?php mysqli_stmt_close($user_posts_query); ?>
            
            </div>
        </div>
    </div>
<?php
if ($settings['sidebar_position'] == 'Right') {
	sidebar();
}
footer();
?>