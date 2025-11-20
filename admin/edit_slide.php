<?php
include "header.php";

if (!isset($_GET['id'])) exit;
$id = (int)$_GET['id'];

// Récupérer les infos actuelles
$q = mysqli_query($connect, "SELECT * FROM slides WHERE id=$id");
$slide = mysqli_fetch_assoc($q);
if(!$slide) { echo "Introuvable"; exit; }

$message = '';

if (isset($_POST['submit'])) {
    validate_csrf_token();
    
    $title      = $_POST['title'];
    $description= $_POST['description'];
    $link_url   = $_POST['link_url'];
    $active     = $_POST['active'];
    $order      = (int)$_POST['position_order'];
    
    // Garder l'ancienne image par défaut
    $image_path = $slide['image_url']; 

    // --- Gestion Remplacement Image ---
    if (isset($_FILES['image_url']['name']) && $_FILES['image_url']['name'] != "") {
        
        $target_dir = "../uploads/slider/";
        $ext = strtolower(pathinfo($_FILES["image_url"]["name"], PATHINFO_EXTENSION));
        $new_name = "slide_" . uniqid() . "." . $ext;
        $target_file = $target_dir . $new_name;
        
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if(in_array($ext, $allowed)) {
            if (move_uploaded_file($_FILES["image_url"]["tmp_name"], $target_file)) {
                // On a réussi l'upload, on met à jour le chemin
                $image_path = "uploads/slider/" . $new_name;
                
                // Supprimer l'ancienne image
                if (!empty($slide['image_url']) && file_exists("../" . $slide['image_url'])) {
                    @unlink("../" . $slide['image_url']);
                }
            } else {
                 $message = '<div class="alert alert-danger">Erreur lors de l\'upload de la nouvelle image.</div>';
            }
        } else {
             $message = '<div class="alert alert-danger">Format de fichier non autorisé.</div>';
        }
    }

    // Mettre à jour si pas d'erreur d'upload
    if (empty($message)) {
        $stmt = mysqli_prepare($connect, "UPDATE slides SET 
            title=?, description=?, image_url=?, link_url=?, active=?, position_order=? 
            WHERE id=?");
        mysqli_stmt_bind_param($stmt, "sssssii", $title, $description, $image_path, $link_url, $active, $order, $id);
        
        if(mysqli_stmt_execute($stmt)) {
            echo '<meta http-equiv="refresh" content="0; url=slides.php">';
            exit;
        } else {
             $message = '<div class="alert alert-danger">Erreur BDD: ' . mysqli_error($connect) . '</div>';
        }
        mysqli_stmt_close($stmt);
    }
}
?>

<div class="content-header">
    <div class="container-fluid">
        <h1 class="m-0">Modifier la Diapositive</h1>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <?php echo $message; ?>
        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            
            <div class="card card-primary">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label>Image (Remplacer)</label>
                                <input type="file" name="image_url" class="form-control-file">
                                <small class="text-muted">Laissez vide pour conserver l'image actuelle.</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                             <label>Aperçu actuel</label><br>
                             <img src="../<?php echo htmlspecialchars($slide['image_url']); ?>" class="img-thumbnail" width="200">
                        </div>
                    </div>
                    
                    <hr>

                    <div class="form-group">
                        <label>Lien (URL)</label>
                        <input type="text" name="link_url" class="form-control" value="<?php echo htmlspecialchars($slide['link_url']); ?>">
                    </div>

                    <div class="form-group">
                        <label>Titre (Optionnel)</label>
                        <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($slide['title']); ?>">
                    </div>

                    <div class="form-group">
                        <label>Description (Optionnel)</label>
                        <textarea name="description" class="summernote"><?php echo $slide['description']; ?></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Ordre</label>
                                <input type="number" name="position_order" class="form-control" value="<?php echo (int)$slide['position_order']; ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Statut</label>
                                <select name="active" class="form-control">
                                    <option value="Yes" <?php if($slide['active']=='Yes') echo 'selected'; ?>>Active</option>
                                    <option value="No" <?php if($slide['active']=='No') echo 'selected'; ?>>Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" name="submit" class="btn btn-primary">Mettre à jour</button>
                    <a href="slides.php" class="btn btn-secondary">Annuler</a>
                </div>
            </div>
        </form>
    </div>
</section>

<?php include "footer.php"; ?>
<script>
$(document).ready(function() {
    $('.summernote').summernote({ height: 100 });
});
</script>