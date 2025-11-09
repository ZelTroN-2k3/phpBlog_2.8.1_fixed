<?php
include "header.php";

if (isset($_GET['delete-id'])) {
    $id    = (int) $_GET["delete-id"];

    // Corrected table name from galery to gallery and using prepared statements
    $stmt = mysqli_prepare($connect, "DELETE FROM `gallery` WHERE album_id=?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    $stmt = mysqli_prepare($connect, "DELETE FROM `albums` WHERE id=?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    // Rediriger pour nettoyer l'URL
    echo '<meta http-equiv="refresh" content="0; url=albums.php">';
    exit;
}
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0"><i class="fas fa-list-ol"></i> Albums</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                    <li class="breadcrumb-item active">Albums</li>
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
    $stmt = mysqli_prepare($connect, "SELECT * FROM `albums` WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $sql = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($sql);
    mysqli_stmt_close($stmt);

    if (empty($id) || !$row) {
        echo '<meta http-equiv="refresh" content="0; url=albums.php">';
        exit;
    }
    
    if (isset($_POST['submit'])) {
        // --- NOUVEL AJOUT : Validation CSRF ---
        validate_csrf_token();
        // --- FIN AJOUT ---
        
        $title = $_POST['title'];

        // Use prepared statement for UPDATE
        $stmt = mysqli_prepare($connect, "UPDATE albums SET title=? WHERE id=?");
        mysqli_stmt_bind_param($stmt, "si", $title, $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        echo '<meta http-equiv="refresh" content="0; url=albums.php">';
    }
?>
            <div class="card card-primary card-outline mb-3">
              <div class="card-header">
                <h3 class="card-title">Edit Album</h3>
              </div>         
                  <form action="" method="post">
                    <div class="card-body">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <div class="form-group">
                            <label>Title</label>
                            <input class="form-control" name="title" type="text" value="<?php
    echo htmlspecialchars($row['title']); // Prevent XSS
?>" required>
                        </div>
                    </div>
                    <div class="card-footer">
                        <input type="submit" class="btn btn-primary" name="submit" value="Save" />
                        <a href="albums.php" class="btn btn-secondary">Annuler</a>
                    </div>
                  </form>
            </div>
<?php
}
?>

            <div class="card">
              <div class="card-header">
                <h3 class="card-title">
                    <a href="add_album.php" class="btn btn-primary"><i class="fa fa-plus"></i> Add Album</a>
                </h3>
              </div>         
                <div class="card-body">
                    <table class="table table-bordered table-hover" id="dt-basic" style="width:100%">
                        <thead>
                        <tr>
                            <th>Title</th>
                            <th>Actions</th>
                        </tr>
                        </thead>
                        <tbody>
<?php
$sql    = "SELECT * FROM albums ORDER BY title ASC";
$result = mysqli_query($connect, $sql);
while ($row = mysqli_fetch_assoc($result)) {
        echo '
                <tr>
	                <td>' . htmlspecialchars($row['title']) . '</td>
					<td>
					    <a href="?edit-id=' . $row['id'] . '" class="btn btn-primary btn-sm"><i class="fa fa-edit"></i> Edit</a>
						<a href="?delete-id=' . $row['id'] . '&token=' . $csrf_token . '" class="btn btn-danger btn-sm" onclick="return confirm(\'Are you sure you want to delete this album? This will also delete all associated images in the gallery.\');"><i class="fa fa-trash"></i> Delete</a>
					</td>
                </tr>
';
}
?>
                        </tbody>
                    </table>
                </div>
            </div>

    </div></section>
<?php
include "footer.php";
?>