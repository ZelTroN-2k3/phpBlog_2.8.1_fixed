<?php
include "header.php";

if (isset($_GET['delete-id'])) {
    $id = (int) $_GET["delete-id"];

    // Use prepared statement to get the file path
    $stmt = mysqli_prepare($connect, "SELECT path FROM `files` WHERE id=?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if ($row) {
        $path = $row['path'];
        if (file_exists('../' . $path)) { // Correction du chemin pour la suppression (il faut remonter un niveau)
            unlink('../' . $path);
        }

        // Use prepared statement for DELETE
        $stmt_delete = mysqli_prepare($connect, "DELETE FROM `files` WHERE id=?");
        mysqli_stmt_bind_param($stmt_delete, "i", $id);
        mysqli_stmt_execute($stmt_delete);
        mysqli_stmt_close($stmt_delete);
    }
    
    // Rediriger pour nettoyer l'URL
    echo '<meta http-equiv="refresh" content="0; url=files.php">';
    exit;
}
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0"><i class="fas fa-folder-open"></i> Files</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                    <li class="breadcrumb-item active">Files</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <a href="upload_file.php" class="btn btn-primary"><i class="fas fa-upload"></i> Upload File</a>
                </h3>
            </div>         
			<div class="card-body">
                <table class="table table-bordered table-hover" id="dt-basic" style="width:100%">
                    <thead>
                    <tr>
                        <th>File Name</th>
                        <th>Type</th>
                        <th>Size</th>
                        <th>Uploaded</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody>
<?php
$query = mysqli_query($connect, "SELECT * FROM files ORDER BY id DESC");
while ($row = mysqli_fetch_assoc($query)) {
    // Vérifier si le fichier existe pour éviter les erreurs filesize et filetype sur des fichiers supprimés manuellement
    $full_path = '../' . $row['path'];
    $file_type = file_exists($full_path) ? filetype($full_path) : 'N/A';
    $file_size = file_exists($full_path) ? byte_convert(filesize($full_path)) : 'N/A';
    
    // Le chemin vers le fichier doit commencer par '../' pour être correct dans la table
    $display_path = htmlspecialchars($row['path']);

    echo '
                    <tr>
                        <td>' . htmlspecialchars($row['filename']) . '</td>
                        <td>' . htmlspecialchars($file_type) . '</td>
                        <td>' . htmlspecialchars($file_size) . '</td>
                        <td data-sort="' . strtotime($row['created_at']) . '">' . date($settings['date_format'] . ' H:i', strtotime($row['created_at'])) . '</td>
                        <td>
                            <a href="../' . $display_path . '" target="_blank" class="btn btn-success btn-sm"><i class="fa fa-eye"></i> View</a>
                            <a href="?delete-id=' . $row['id'] . '&token=' . $csrf_token . '" class="btn btn-danger btn-sm" onclick="return confirm(\'Are you sure you want to delete this file?\');"><i class="fa fa-trash"></i> Delete</a>
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
<script>
$(document).ready(function() {
    // Activation de DataTables avec ordre par défaut descendant (colonne 3: Uploaded Date)
	$('#dt-basic').DataTable({
        "responsive": true,
        "lengthChange": false, 
        "autoWidth": false,
		"order": [[ 3, "desc" ]] 
	});
});
</script>
<?php
include "footer.php";
?>