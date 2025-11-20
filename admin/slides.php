<?php
include "header.php";

$message = '';
$upload_dir = '../uploads/slider/';

// --- SUPPRESSION ---
if (isset($_GET['delete_id'])) {
    validate_csrf_token_get();
    $id = (int)$_GET['delete_id'];
    
    // 1. Récupérer le chemin de l'image pour la supprimer
    $stmt_get = mysqli_prepare($connect, "SELECT image_url FROM slides WHERE id=?");
    mysqli_stmt_bind_param($stmt_get, "i", $id);
    mysqli_stmt_execute($stmt_get);
    $res_get = mysqli_stmt_get_result($stmt_get);
    $row_get = mysqli_fetch_assoc($res_get);
    mysqli_stmt_close($stmt_get);
    
    // 2. Supprimer le fichier image du serveur
    if ($row_get && !empty($row_get['image_url']) && file_exists("../" . $row_get['image_url'])) {
        @unlink("../" . $row_get['image_url']);
    }

    // 3. Supprimer l'enregistrement de la BDD
    $stmt = mysqli_prepare($connect, "DELETE FROM slides WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    if(mysqli_stmt_execute($stmt)) {
        $message = '<div class="alert alert-success">Slide deleted.</div>';
    }
    mysqli_stmt_close($stmt);
}

// --- TOGGLE STATUS (Actif/Inactif) ---
if (isset($_GET['toggle_id'])) {
    validate_csrf_token_get();
    $id = (int)$_GET['toggle_id'];
    $stmt = mysqli_prepare($connect, "UPDATE slides SET active = IF(active='Yes', 'No', 'Yes') WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    if(mysqli_stmt_execute($stmt)) {
        $message = '<div class="alert alert-success">Status updated.</div>';
    }
    mysqli_stmt_close($stmt);
}

// --- LISTING ---
$slides = [];
$q = mysqli_query($connect, "SELECT * FROM slides ORDER BY position_order ASC, id DESC");
while ($row = mysqli_fetch_assoc($q)) {
    $slides[] = $row;
}
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0"><i class="fas fa-images"></i> Slider Management</h1>
            </div>
            <div class="col-sm-6">
                <a href="add_slide.php" class="btn btn-primary float-right"><i class="fas fa-plus"></i> New Slide</a>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <?php echo $message; ?>
        
        <div class="card card-primary card-outline">
            <div class="card-body p-0">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th style="width: 100px;">Preview</th>
                            <th>Titre</th>
                            <th>Lien (URL)</th>
                            <th style="width: 60px;">Ordre</th>
                            <th style="width: 80px;">Statut</th>
                            <th style="width: 180px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($slides)): ?>
                            <tr><td colspan="6" class="text-center text-muted py-4">No slides created.</td></tr>
                        <?php else: ?>
                            <?php foreach($slides as $slide): 
                                // Chemin de l'image (s'assurer qu'elle existe)
                                $img_path = !empty($slide['image_url']) && file_exists('../' . $slide['image_url']) 
                                            ? '../' . $slide['image_url'] 
                                            : '../assets/img/no-image.png';
                            ?>
                                <tr>
                                    <td><img src="<?php echo $img_path; ?>" class="img-thumbnail" width="100" style="object-fit:cover;"></td>
                                    <td><?php echo htmlspecialchars($slide['title']); ?></td>
                                    <td><small><?php echo htmlspecialchars($slide['link_url']); ?></small></td>
                                    <td><span class="badge bg-light border"><?php echo $slide['position_order']; ?></span></td>
                                    <td>
                                        <?php if($slide['active'] == 'Yes'): ?>
                                            <span class="badge bg-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="edit_slide.php?id=<?php echo $slide['id']; ?>" class="btn btn-sm btn-primary"><i class="fas fa-edit"></i></a>
                                        
                                        <a href="?toggle_id=<?php echo $slide['id']; ?>&token=<?php echo $_SESSION['csrf_token']; ?>" 
                                           class="btn btn-sm <?php echo ($slide['active'] == 'Yes' ? 'btn-warning' : 'btn-success'); ?>">
                                           <i class="fas <?php echo ($slide['active'] == 'Yes' ? 'fa-eye-slash' : 'fa-eye'); ?>"></i>
                                        </a>
                                        
                                        <a href="?delete_id=<?php echo $slide['id']; ?>&token=<?php echo $_SESSION['csrf_token']; ?>" 
                                           class="btn btn-sm btn-danger" 
                                           onclick="return confirm('Delete this slide?');">
                                           <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<?php include "footer.php"; ?>