<?php
include "header.php";

// --- LOGIQUE SUPPRESSION ---
if (isset($_GET['delete-id'])) {
    validate_csrf_token_get();
    $id = (int) $_GET["delete-id"];
    
    // 1. Récupérer le chemin de l'image pour la supprimer du serveur
    $stmt_img = mysqli_prepare($connect, "SELECT image FROM gallery WHERE id=?");
    mysqli_stmt_bind_param($stmt_img, "i", $id);
    mysqli_stmt_execute($stmt_img);
    $res_img = mysqli_stmt_get_result($stmt_img);
    
    if ($r = mysqli_fetch_assoc($res_img)) {
        // Supprimer le fichier physique s'il existe
        if (!empty($r['image']) && file_exists("../" . $r['image'])) {
            unlink("../" . $r['image']);
        }
    }
    mysqli_stmt_close($stmt_img);

    // 2. Supprimer l'entrée en base de données
    $stmt = mysqli_prepare($connect, "DELETE FROM `gallery` WHERE id=?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    echo '<meta http-equiv="refresh" content="0; url=gallery.php">';
    exit;
}
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0"><i class="fas fa-images"></i> Gallery</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                    <li class="breadcrumb-item active">Gallery</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        
        <div class="row">
            <div class="col-12">
                
                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <h3 class="card-title">
                            <a href="add_image.php" class="btn btn-primary btn-sm">
                                <i class="fa fa-plus"></i> Add New Image
                            </a>
                        </h3>
                    </div>
                    
                    <div class="card-body">
                        <table class="table table-bordered table-hover" id="dt-gallery" style="width:100%">
                            <thead>
                                <tr>
                                    <th style="width: 120px;" class="text-center">Preview</th>
                                    <th>Title</th>
                                    <th>Album</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center" style="width: 160px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
<?php
// Requête avec jointure pour récupérer le nom de l'album
$query = "
    SELECT g.*, a.title as album_title 
    FROM gallery g 
    LEFT JOIN albums a ON g.album_id = a.id 
    ORDER BY g.id DESC
";
$sql = mysqli_query($connect, $query);

while ($row = mysqli_fetch_assoc($sql)) {
    
    $album_name = !empty($row['album_title']) ? htmlspecialchars($row['album_title']) : '<span class="text-muted">Uncategorized</span>';
    
    echo '
        <tr>
            <td class="text-center">';
            if ($row['image'] != '') {
                echo '<a href="../' . htmlspecialchars($row['image']) . '" data-toggle="lightbox" data-title="' . htmlspecialchars($row['title']) . '">
                        <img src="../' . htmlspecialchars($row['image']) . '" width="80" height="60" style="object-fit: cover; border-radius: 4px;" />
                      </a>';
            } else {
                echo '<span class="text-muted">No Image</span>';
            }
    echo '  </td>
            <td>' . htmlspecialchars($row['title']) . '</td>
            <td>' . $album_name . '</td>
            <td class="text-center">';
            
            if ($row['active'] == "Yes") {
                echo '<span class="badge badge-success">Active</span>';
            } else {
                echo '<span class="badge badge-danger">Inactive</span>';
            }
            
    echo '  </td>
            <td class="text-center">
                <a href="edit_gallery.php?id=' . $row['id'] . '" class="btn btn-primary btn-sm mr-1" title="Edit">
                    <i class="fa fa-edit"></i>
                </a>
                
                <a href="?delete-id=' . $row['id'] . '&token=' . $csrf_token . '" class="btn btn-danger btn-sm" onclick="return confirm(\'Are you sure you want to delete this image?\');" title="Delete">
                    <i class="fa fa-trash"></i>
                </a>
            </td>
        </tr>';
}
?>
                            </tbody>
                        </table>
                    </div>
                </div>
            
            </div>
        </div>

    </div>
</section>

<script>
$(document).ready(function() {
    $('#dt-gallery').DataTable({
        "responsive": true, 
        "autoWidth": false,
        "order": [[ 1, "asc" ]] // Tri par titre
    });
    
    // Activation de Ekko Lightbox pour la prévisualisation (si inclus dans AdminLTE)
    $(document).on('click', '[data-toggle="lightbox"]', function(event) {
        event.preventDefault();
        $(this).ekkoLightbox({
            alwaysShowClose: true
        });
    });
});
</script>

<?php include "footer.php"; ?>