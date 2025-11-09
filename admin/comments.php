<?php
include "header.php";

if (isset($_GET['delete-id'])) {
    $id    = (int) $_GET["delete-id"];
    
    // Use prepared statement for DELETE
    $stmt = mysqli_prepare($connect, "DELETE FROM `comments` WHERE id=?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    // Rediriger pour nettoyer l'URL
    echo '<meta http-equiv="refresh" content="0; url=comments.php">';
    exit;
}
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0"><i class="fas fa-comments"></i> Comments</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="posts.php">Posts</a></li>
                    <li class="breadcrumb-item active">Comments</li>
                </ol>
            </div>
        </div>
    </div>
</div>
<section class="content">
    <div class="container-fluid">

<?php
if (isset($_GET['edit-id'])) {
    $id  = (int) $_GET["edit-id"];

    // Use prepared statement for SELECT
    $stmt = mysqli_prepare($connect, "SELECT * FROM `comments` WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $sql = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($sql);
    mysqli_stmt_close($stmt);

    if (empty($id) || !$row) {
        echo '<meta http-equiv="refresh" content="0; url=comments.php">';
        exit;
    }
    
    $author_name = $row['user_id'];
    $avatar = 'assets/img/avatar.png'; // Valeur par défaut
    
    if ($row['guest'] == 'Yes') {
        $avatar = 'assets/img/avatar.png';
        $author_name = $row['user_id']; // Le nom de l'invité
    } else {
        // Use prepared statement to get user info
        $stmt_user = mysqli_prepare($connect, "SELECT * FROM `users` WHERE id=? LIMIT 1");
        mysqli_stmt_bind_param($stmt_user, "i", $author_name);
        mysqli_stmt_execute($stmt_user);
        $querych = mysqli_stmt_get_result($stmt_user);
        if (mysqli_num_rows($querych) > 0) {
            $rowch = mysqli_fetch_assoc($querych);
            $avatar = $rowch['avatar'];
            $author_name = $rowch['username'];
        }
        mysqli_stmt_close($stmt_user);
    }
    
    if (isset($_POST['submit'])) {
        // --- NOUVEL AJOUT : Validation CSRF ---
        validate_csrf_token();
        // --- FIN AJOUT ---
        
        $approved = $_POST['approved'];

        // Use prepared statement for UPDATE
        $stmt_update = mysqli_prepare($connect, "UPDATE comments SET approved=? WHERE id=?");
        mysqli_stmt_bind_param($stmt_update, "si", $approved, $id);
        mysqli_stmt_execute($stmt_update);
        mysqli_stmt_close($stmt_update);
        
        echo '<meta http-equiv="refresh" content="0; url=comments.php">';
    }
?>
            <div class="card card-primary card-outline mb-3">
              <div class="card-header">
                  <h3 class="card-title">Edit Comment</h3>
              </div>         
                <form action="" method="post">
                <div class="card-body">
					<input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <div class="form-group">
						<label>Author</label><br />
						<input class="form-control" name="author" type="text" value="<?php
    echo htmlspecialchars($author_name); // Prevent XSS
?>" disabled>
					</div>
					<div class="form-group">
                        <label>Avatar</label><br />
                        <?php
                        // --- DÉBUT CORRECTION BUG AVATAR GOOGLE ---
                        $avatar_path = $avatar;
                        if (strpos($avatar, 'http://') !== 0 && strpos($avatar, 'https://') !== 0) {
                            $avatar_path = '../' . htmlspecialchars($avatar);
                        }
                        // --- FIN CORRECTION BUG AVATAR GOOGLE ---
                        ?>
						<img src="<?php echo htmlspecialchars($avatar_path); ?>" width="50px" height="50px" class="img-circle elevation-2 mb-2" /><br />
					</div>
					<div class="form-group">
						<label>Approved</label>
						<select class="form-control" name="approved" required>
							<option value="Yes" <?php
    if ($row['approved'] == "Yes") {
        echo 'selected';
    }
?>>Yes</option>
							<option value="No" <?php
    if ($row['approved'] == "No") {
        echo 'selected';
    }
?>>No</option>
						</select>
					</div>
					<div class="form-group">
						<label>Comment (Read Only)</label>
						<textarea name="comment" class="form-control" rows="6" disabled><?php
    echo htmlspecialchars($row['comment']); // Prevent XSS
?></textarea>
					</div>
                </div>
                <div class="card-footer">
					<input type="submit" class="btn btn-primary" name="submit" value="Update" />
                    <a href="comments.php" class="btn btn-secondary">Annuler</a>
                </div>
				</form>
            </div>
<?php
}
?>
			
			<div class="card">
                <div class="card-header">
                    <h3 class="card-title">Comments List</h3>
                </div>         
                <div class="card-body">

                    <table class="table table-bordered table-hover" id="dt-basic" style="width:100%">
                        <thead>
                        <tr>
                            <th>Author</th>
                            <th>Date</th>
                            <th>Approved</th>
                            <th>Post</th>
                            <th>Comment</th>
                            <th>Actions</th>
                        </tr>
                        </thead>
                        <tbody>
<?php
$sql    = "SELECT * FROM comments ORDER BY id DESC";
$result = mysqli_query($connect, $sql);
while ($row = mysqli_fetch_assoc($result)) {
    $author_name = $row['user_id'];
    $badge  = '';
    if ($row['guest'] == 'Yes') {
        $avatar = 'assets/img/avatar.png';
        $author_name = $row['user_id']; // Utilise le nom de l'invité
        $badge  = ' <span class="badge bg-info"><i class="fas fa-user"></i> Guest</span>';
        
    } else {
        $stmt_user = mysqli_prepare($connect, "SELECT * FROM `users` WHERE id=? LIMIT 1");
        mysqli_stmt_bind_param($stmt_user, "i", $author_name);
        mysqli_stmt_execute($stmt_user);
        $querych = mysqli_stmt_get_result($stmt_user);
        if (mysqli_num_rows($querych) > 0) {
            $rowch = mysqli_fetch_assoc($querych);
            $avatar = $rowch['avatar'];
            $author_name = $rowch['username'];
            
            // Logique de badge de rôle manquante ajoutée ici
            if ($rowch['role'] == 'Admin') {
                $badge = ' <span class="badge bg-danger">Admin</span>';
            } elseif ($rowch['role'] == 'Editor') {
                $badge = ' <span class="badge bg-success">Editor</span>';
            } else {
                $badge = ' <span class="badge bg-primary">User</span>';
            }
        }
        mysqli_stmt_close($stmt_user);
    }

    $post_title = 'N/A';
    $post_id = $row['post_id'];
    $stmt_post = mysqli_prepare($connect, "SELECT title FROM `posts` WHERE id=?");
    mysqli_stmt_bind_param($stmt_post, "i", $post_id);
    mysqli_stmt_execute($stmt_post);
    $runq2 = mysqli_stmt_get_result($stmt_post);
    if($sql2 = mysqli_fetch_assoc($runq2)){
        $post_title = $sql2['title'];
    }
    mysqli_stmt_close($stmt_post);

    // --- DÉBUT CORRECTION BUG AVATAR GOOGLE ---
    $avatar_path = $avatar;
    if (strpos($avatar, 'http://') !== 0 && strpos($avatar, 'https://') !== 0) {
        $avatar_path = '../' . htmlspecialchars($avatar);
    }
    // --- FIN CORRECTION BUG AVATAR GOOGLE ---

    echo '
                <tr>
	                <td><img src="' . htmlspecialchars($avatar_path) . '" width="45px" height="45px" class="img-circle elevation-2" /> ' . htmlspecialchars($author_name) . '' . $badge . '</td>
	                <td data-sort="' . strtotime($row['created_at']) . '">' . date($settings['date_format'] . ' H:i', strtotime($row['created_at'])) . '</td>
					<td>';
	if($row['approved'] == "Yes") {
		echo '<span class="badge bg-success">Yes</span>';
	} else {
		echo '<span class="badge bg-danger">No</span>';
	}
	echo '</td>';
    echo '              <td>' . htmlspecialchars($post_title) . '</td>
					<td>' . htmlspecialchars(short_text($row['comment'], 50)) . '</td>
					<td>
					    <a href="?edit-id=' . $row['id'] . '" title="View / Edit" class="btn btn-primary btn-sm"><i class="fa fa-edit"></i> View / Edit</a>
						<a href="?delete-id=' . $row['id'] . '&token=' . $csrf_token . '" class="btn btn-danger btn-sm" onclick="return confirm(\'Are you sure you want to delete this comment?\');"><i class="fa fa-trash"></i> Delete</a>
					</td>
                </tr>
';
}
?>
                        </tbody>
                    </table>
                </div>
            </div>
	</section>
<script>
$(document).ready(function() {
    // Activation de DataTables avec ordre par défaut descendant (colonne 1: Date)
	$('#dt-basic').DataTable({
        "responsive": true,
        "lengthChange": false, 
        "autoWidth": false,
		"order": [[ 1, "desc" ]] 
	});
});
</script>
<?php
include "footer.php";
?>