<?php
include "header.php";

$message = '';

if (isset($_POST['submit'])) {
    validate_csrf_token();
    
    $title      = $_POST['title'];
    $description= $_POST['description']; // Contenu HTML
    $link_url   = $_POST['link_url'];
    $active     = $_POST['active'];
    $order      = (int)$_POST['position_order'];
    $image_path = '';

// --- Gestion de l'Upload avec Optimisation ---
    if (isset($_FILES['image_url']['name']) && $_FILES['image_url']['name'] != "") {
        
        $target_dir = "../uploads/slider/";
        if (!is_dir($target_dir)) { mkdir($target_dir, 0755, true); }

        $ext = strtolower(pathinfo($_FILES["image_url"]["name"], PATHINFO_EXTENSION));
        // Nom de base sans extension
        $new_name_base = "slide_" . uniqid(); 
        $target_file_base = $target_dir . $new_name_base;
        
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if(in_array($ext, $allowed)) {
            // Utilisation de la fonction d'optimisation (définie dans core.php)
            if (function_exists('optimize_and_save_image')) {
                // On redimensionne à 1920px max pour un slider HD, qualité 85
                $optimized_path = optimize_and_save_image($_FILES["image_url"]["tmp_name"], $target_file_base, 1920, 85);
                
                if ($optimized_path) {
                    // Nettoyer le chemin pour la BDD (enlever le "../")
                    $image_path = str_replace('../', '', $optimized_path);
                } else {
                    $message = '<div class="alert alert-danger">Error during image optimization.</div>';
                }
            } else {
                // Fallback si la fonction n'existe pas (upload simple)
                $target_file = $target_file_base . '.' . $ext;
                if (move_uploaded_file($_FILES["image_url"]["tmp_name"], $target_file)) {
                    $image_path = "uploads/slider/" . $new_name_base . '.' . $ext;
                } else {
                    $message = '<div class="alert alert-danger">Error during upload.</div>';
                }
            }
        } else {
            $message = '<div class="alert alert-danger">Format not allowed. JPG, PNG, GIF, WEBP only.</div>';
        }
    } else {
        $message = '<div class="alert alert-danger">An image is required.</div>';
    }

    // Si l'upload est OK (chemin rempli) et pas d'erreur
    if (!empty($image_path) && empty($message)) {
        $stmt = mysqli_prepare($connect, "INSERT INTO slides (title, description, image_url, link_url, active, position_order) VALUES (?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "sssssi", $title, $description, $image_path, $link_url, $active, $order);
        
        if(mysqli_stmt_execute($stmt)) {
            echo '<meta http-equiv="refresh" content="0; url=slides.php">';
            exit;
        } else {
            $message = '<div class="alert alert-danger">Database error: ' . mysqli_error($connect) . '</div>';
        }
        mysqli_stmt_close($stmt);
    }
}
?>

<div class="content-header">
    <div class="container-fluid">
        <h1 class="m-0">New Slide</h1>
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
                                <label>Image (Required)</label>
                                <div class="custom-file">
                                    <input type="file" name="image_url" class="custom-file-input" id="customFile" required>
                                    <label class="custom-file-label" for="customFile">Choose an image</label>
                                </div>
                                <small class="text-muted">Recommended: 1920x600 px. The image will be automatically optimized.</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Link (URL)</label>
                                <input type="text" name="link_url" class="form-control" value="#">
                            </div>
                        </div>
                    </div>
                    
                    <hr>

                    <div class="form-group">
                        <label>Title (Optional)</label>
                        <input type="text" name="title" class="form-control" placeholder="Title displayed on the image">
                    </div>

                    <div class="form-group">
                        <label>Description (Optional)</label>
                        <textarea name="description" class="summernote"></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Order</label>
                                <input type="number" name="position_order" class="form-control" value="0">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Status</label>
                                <select name="active" class="form-control">
                                    <option value="Yes">Active</option>
                                    <option value="No">Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" name="submit" class="btn btn-primary">Save</button>
                    <a href="slides.php" class="btn btn-secondary">Cancel</a>
                </div>
            </div>
        </form>
    </div>
</section>

<?php include "footer.php"; ?>
<script>
$(document).ready(function() {
    $('.summernote').summernote({ height: 100 });
    
    // Script pour afficher le nom du fichier dans l'input custom
    $('.custom-file-input').on('change', function() {
        var fileName = $(this).val().split('\\').pop();
        $(this).next('.custom-file-label').addClass("selected").html(fileName);
    });
});
</script>