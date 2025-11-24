<?php
include "header.php";

// --- LOGIQUE DE SUPPRESSION ---
if (isset($_GET['delete-id'])) {
    validate_csrf_token_get();
    $id = (int)$_GET['delete-id'];

    // 1. Supprimer les images de la galerie liées à cet album
    // (Optionnel : Vous pourriez aussi vouloir supprimer les fichiers physiques ici si besoin)
    $stmt_imgs = mysqli_prepare($connect, "DELETE FROM `gallery` WHERE album_id=?");
    mysqli_stmt_bind_param($stmt_imgs, "i", $id);
    mysqli_stmt_execute($stmt_imgs);
    mysqli_stmt_close($stmt_imgs);

    // 2. Supprimer l'album
    $stmt_alb = mysqli_prepare($connect, "DELETE FROM `albums` WHERE id=?");
    mysqli_stmt_bind_param($stmt_alb, "i", $id);
    mysqli_stmt_execute($stmt_alb);
    mysqli_stmt_close($stmt_alb);
    
    echo '<meta http-equiv="refresh" content="0; url=albums.php">';
    exit;
}
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0"><i class="fas fa-folder-open"></i> Albums</h1>
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
        <div class="row">
            <div class="col-12">
                
                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <h3 class="card-title">
                            <a href="add_album.php" class="btn btn-primary btn-sm">
                                <i class="fa fa-plus"></i> Add Album
                            </a>
                        </h3>
                    </div>
                    
                    <div class="card-body">
                        <table id="dt-albums" class="table table-bordered table-hover table-striped" style="width:100%">
                            <thead>
                                <tr>
                                    <th style="width: 50px;" class="text-center">ID</th>
                                    <th>Album Title</th>
                                    <th class="text-center">Images Count</th>
                                    <th class="text-center" style="width: 150px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Requête pour lister les albums avec le compte d'images
                                $query = "
                                    SELECT a.*, COUNT(g.id) as image_count 
                                    FROM albums a 
                                    LEFT JOIN gallery g ON a.id = g.album_id 
                                    GROUP BY a.id 
                                    ORDER BY a.id DESC
                                ";
                                $result = mysqli_query($connect, $query);
                                
                                while ($row = mysqli_fetch_assoc($result)) {
                                    echo '<tr>
                                        <td class="text-center">' . $row['id'] . '</td>
                                        <td><strong>' . htmlspecialchars($row['title']) . '</strong></td>
                                        <td class="text-center"><span class="badge badge-info">' . $row['image_count'] . '</span></td>
                                        <td class="text-center">
                                            <a href="edit_album.php?id=' . $row['id'] . '" class="btn btn-primary btn-sm mr-1" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="?delete-id=' . $row['id'] . '&token=' . $csrf_token . '" class="btn btn-danger btn-sm" onclick="return confirm(\'WARNING: Deleting this album will DELETE ALL linked images in the gallery database. Continue?\');" title="Delete">
                                                <i class="fas fa-trash"></i>
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

<?php include "footer.php"; ?>

<script>
$(document).ready(function() {
    $('#dt-albums').DataTable({
        "responsive": true,
        "autoWidth": false,
        "order": [[ 0, "desc" ]] 
    });
});
</script>