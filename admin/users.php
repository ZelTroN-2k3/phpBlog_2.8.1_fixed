<?php
include "header.php";

if (isset($_GET['delete-id'])) {
    // Valider le jeton CSRF
    validate_csrf_token_get(); //
    
    $id    = (int) $_GET["delete-id"];
    
    // Use prepared statements for DELETE (comments)
    $stmt = mysqli_prepare($connect, "DELETE FROM `comments` WHERE user_id=? AND guest='No'");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    // Use prepared statements for DELETE (user)
    $stmt = mysqli_prepare($connect, "DELETE FROM `users` WHERE id=?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    // Rediriger pour nettoyer l'URL
    echo '<meta http-equiv="refresh" content="0; url=users.php">';
    exit;
}
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0"><i class="fas fa-users"></i> Users</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                    <li class="breadcrumb-item active">Users</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<?php
if (isset($_GET['edit-id'])) {
    $id  = (int) $_GET["edit-id"];

    // --- MODIFICATION : Remplacement de mysqli_query par une requête préparée ---
    $stmt_select = mysqli_prepare($connect, "SELECT * FROM `users` WHERE id = ?");
    mysqli_stmt_bind_param($stmt_select, "i", $id);
    mysqli_stmt_execute($stmt_select);
    $sql = mysqli_stmt_get_result($stmt_select);
    $row = mysqli_fetch_assoc($sql);
    mysqli_stmt_close($stmt_select);
    // --- FIN DE LA MODIFICATION ---
    
    if (!$row) {
        echo '<meta http-equiv="refresh" content="0; url=users.php">';
        exit;
    }
    
    if (isset($_POST['edit'])) {
        // Valider le jeton CSRF
        validate_csrf_token(); //
            
        $username = $_POST['username'];
        $email    = $_POST['email'];
        $role     = $_POST['role'];
        $avatar   = $_POST['avatar'];
        
        // Requête préparée pour la mise à jour
        $stmt = mysqli_prepare($connect, "UPDATE `users` SET username=?, email=?, role=?, avatar=? WHERE id=?");
        mysqli_stmt_bind_param($stmt, "ssssi", $username, $email, $role, $avatar, $id);
        
        if (mysqli_stmt_execute($stmt)) {
            echo '<div class="alert alert-success">User successfully updated.</div>';
        } else {
            echo '<div class="alert alert-danger">Error updating user.</div>';
        }
        mysqli_stmt_close($stmt);
        echo '<meta http-equiv="refresh" content="2; url=users.php">';
    }
?>
<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-edit"></i> Edit User</h3>
                    </div>
                    <div class="card-body">
                        <form action="" method="post">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>"> <div class="form-group">
                                <label>Username</label>
                                <input type="text" name="username" class="form-control" value="<?php
    echo htmlspecialchars($row['username']);
?>" required>
                            </div>
                            <div class="form-group">
                                <label>E-Mail Address</label>
                                <input type="email" name="email" class="form-control" value="<?php
    echo htmlspecialchars($row['email']);
?>" required>
                            </div>
                            <div class="form-group">
                                <label>Role</label>
                                <select name="role" class="form-control" required>
                                    <option value="Admin" <?php
    if ($row['role'] == 'Admin') {
        echo 'selected';
    }
?>>Admin</option>
                                    <option value="Editor" <?php
    if ($row['role'] == 'Editor') {
        echo 'selected';
    }
?>>Editor</option>
                                    <option value="User" <?php
    if ($row['role'] == 'User') {
        echo 'selected';
    }
?>>User</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Avatar</label>
                                <input type="text" name="avatar" class="form-control" value="<?php
    echo htmlspecialchars($row['avatar']);
?>" required>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" name="edit" class="btn btn-primary">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
<?php
}
?>

    <section class="content">
      <div class="container-fluid">
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-users"></i> Users</h3>
					<div class="float-sm-right">
						<a href="add_user.php" class="btn btn-success"><i class="fa fa-plus"></i> Add User</a>
					</div>
                </div>
                <div class="card-body">
                    <table id="dt-basic" class="table table-striped table-bordered">
                          <thead>
                            <tr>
                                <th>Username</th>
								<th>E-Mail Address</th>
								<th>Role</th>
                                <th>Actions</th>
                            </tr>
                          </thead>
                          <tbody>
<?php
$run = mysqli_query($connect, "SELECT * FROM `users` ORDER BY `id` ASC");
while ($row = mysqli_fetch_assoc($run)) {
    
    $badge = '';
    if($row['role'] == 'Admin') {
        $badge = '<span class="badge bg-danger">Administrator</span>';
    } elseif($row['role'] == 'Editor') {
        $badge = '<span class="badge bg-warning">Editor</span>';
    } else {
        $badge = '<span class="badge bg-info">User</span>';
    }
    
    // --- DÉBUT DE LA CORRECTION DU BUG AVATAR ---
    $avatar_url_raw = $row['avatar'];
    $avatar_path = ''; // Initialiser le chemin
    
    // Vérifier si l'avatar est une URL absolue (Google) ou un chemin local
    if (strpos($avatar_url_raw, 'http://') === 0 || strpos($avatar_url_raw, 'https://') === 0) {
        // C'est une URL Google (ou autre URL absolue), on ne préfixe pas
        $avatar_path = htmlspecialchars($avatar_url_raw);
    } else {
        // C'est un avatar local, on ajoute le préfixe ../
        $avatar_path = '../' . htmlspecialchars($avatar_url_raw);
    }
    // --- FIN DE LA CORRECTION ---
    
    echo '
                            <tr>
                                <td><img src="' . $avatar_path . '" width="40px" height="40px" class="img-circle elevation-2" /> ' . htmlspecialchars($row['username']) . '</td>
								<td>' . htmlspecialchars($row['email']) . '</td>
								<td>' . $badge . '</td>
                                <td>
                                    <a class="btn btn-primary btn-sm" href="?edit-id=' . $row['id'] . '">
                                        <i class="fa fa-edit"></i> Edit
                                    </a>
                                    <a class="btn btn-danger btn-sm" href="?delete-id=' . $row['id'] . '&token=' . $csrf_token . '" onclick="return confirm(\'Are you sure you want to delete this user? All their comments will also be deleted.\');">
                                        <i class="fa fa-trash"></i> Delete
                                    </a>
                                </td>
                            </tr>
';
}
?>
                          </tbody>
                     </table>
                  </div>
            </div>
		</div>
    </section>
<script>
$(document).ready(function() {
	// Activation de DataTables avec ordre par défaut ascendant (colonne 0)
	$('#dt-basic').DataTable({
		"responsive": true,
        "lengthChange": false, 
        "autoWidth": false,
		"order": [[ 0, "asc" ]]
	});
});
</script>
<?php
include "footer.php";
?>