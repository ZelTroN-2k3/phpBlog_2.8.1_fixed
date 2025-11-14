<?php
include "header.php";

$message = '';

if (isset($_POST['submit'])) {
    validate_csrf_token();
    
    $title      = $_POST['title'];
    $description= $_POST['description']; // Contenu HTML (Summernote)
    $link_url   = $_POST['link_url'];
    $active     = $_POST['active'];
    $order      = (int)$_POST['position_order'];
    $image_path = '';

    // --- Gestion de l'Upload ---
    if (isset($_FILES['image_url']['name']) && $_FILES['image_url']['name'] != "") {
        
        $target_dir = "../uploads/slider/";
        $ext = strtolower(pathinfo($_FILES["image_url"]["name"], PATHINFO_EXTENSION));
        $new_name = "slide_" . uniqid() . "." . $ext; // Nom unique
        $target_file = $target_dir . $new_name;
        
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if(in_array($ext, $allowed)) {
            // Tenter l'upload
            if (move_uploaded_file($_FILES["image_url"]["tmp_name"], $target_file)) {
                // Important : stocker le chemin SANS le "../"
                $image_path = "uploads/slider/" . $new_name; 
            } else {
                $message = '<div class="alert alert-danger">Erreur lors de l\'upload de l\'image.</div>';
            }
        } else {
            $message = '<div class="alert alert-danger">Format de fichier non autorisé (JPG, PNG, GIF, WEBP uniquement).</div>';
        }
    } else {
        $message = '<div class="alert alert-danger">Une image est requise.</div>';
    }

    // Si l'upload est OK (ou pas d'erreur), on insère
    if (empty($message)) {
        $stmt = mysqli_prepare($connect, "INSERT INTO slides (title, description, image_url, link_url, active, position_order) VALUES (?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "sssssi", $title, $description, $image_path, $link_url, $active, $order);
        
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
        <h1 class="m-0">Nouvelle Diapositive</h1>
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
                                <label>Image (Requis)</label>
                                <input type="file" name="image_url" class="form-control-file" required>
                                <small class="text-muted">Recommandé : 1200px de large (ex: 1200x400).</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Lien (URL)</label>
                                <input type="text" name="link_url" class="form-control" value="#">
                            </div>
                        </div>
                    </div>
                    
                    <hr>

                    <div class="form-group">
                        <label>Titre (Optionnel)</label>
                        <input type="text" name="title" class="form-control" placeholder="Titre affiché sur l'image">
                    </div>

                    <div class="form-group">
                        <label>Description (Optionnel)</label>
                        <textarea name="description" class="summernote"></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Ordre</label>
                                <input type="number" name="position_order" class="form-control" value="0">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Statut</label>
                                <select name="active" class="form-control">
                                    <option value="Yes">Active</option>
                                    <option value="No">Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" name="submit" class="btn btn-primary">Enregistrer</button>
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