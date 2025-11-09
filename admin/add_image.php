<?php
include "header.php";

if (isset($_POST['add'])) {
    
    // --- NOUVEL AJOUT : Validation CSRF ---
    validate_csrf_token();
    // --- FIN AJOUT ---

    $title       = $_POST['title'];
    $active      = $_POST['active'];
	$album_id = $_POST['album_id'];
    $description = $_POST['description'];
    
    $image = '';
    
    if (@$_FILES['avafile']['name'] != '') {

        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $allowed_mime_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

        $file_extension = strtolower(pathinfo($_FILES["avafile"]["name"], PATHINFO_EXTENSION));
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $_FILES["avafile"]["tmp_name"]);
        finfo_close($finfo);

        $uploadOk = 1;

        if (!in_array($file_extension, $allowed_extensions) || !in_array($mime_type, $allowed_mime_types)) {
            echo '<div class="alert alert-danger">Invalid file type. Only JPG, PNG, GIF and WEBP are allowed.</div>';
            $uploadOk = 0;
        }
        
        // Check file size
        if ($_FILES["avafile"]["size"] > 10000000) { // 10 MB
            echo '<div class="alert alert-warning">Sorry, your file is too large (Limit: 10MB).</div>';
            $uploadOk = 0;
        }
        
        if ($uploadOk == 1) {
            // --- MODIFICATION ---
            $string     = "0123456789wsderfgtyhjuk";
            $new_string = str_shuffle($string);
            // Chemin SANS extension
            $destination_path = "../uploads/gallery/image_$new_string";
            
            // Utiliser une largeur max plus grande pour la galerie, ex: 1920px
            $optimized_path = optimize_and_save_image($_FILES["avafile"]["tmp_name"], $destination_path, 1920);
            
            if ($optimized_path) {
                $image = str_replace('../', '', $optimized_path);
            } else {
                 $uploadOk = 0; 
                 echo '<div class="alert alert-danger">An error occurred while processing the image.</div>';
            }
            // --- FIN MODIFICATION ---
        }
    }
    
    // Utiliser $uploadOk pour conditionner l'insertion
    if ($uploadOk == 1) {
        // Use prepared statement for INSERT
        $stmt = mysqli_prepare($connect, "INSERT INTO `gallery` (album_id, title, image, description, active) VALUES (?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "issss", $album_id, $title, $image, $description, $active);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        echo '<meta http-equiv="refresh" content="0; url=gallery.php">';
        exit;
    }
}
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0"><i class="fas fa-camera-retro"></i> Add Image</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="gallery.php">Gallery</a></li>
                    <li class="breadcrumb-item active">Add Image</li>
                </ol>
            </div>
        </div>
    </div>
</div>
<section class="content">
    <div class="container-fluid">

        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title">Add New Image to Gallery</h3>
            </div>         
            <form action="" method="post" enctype="multipart/form-data">
                <div class="card-body">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    
                    <div class="form-group">
						<label>Title</label>
						<input class="form-control" name="title" value="" type="text" required>
					</div>
					<div class="form-group">
						<label>Image</label>
						<input type="file" name="avafile" class="form-control" required />
					</div>
					
					<div class="form-group">
						<label>Active</label>
						<select name="active" class="form-control" required>
							<option value="Yes" selected>Yes</option>
							<option value="No">No</option>
                        </select>
					</div>
					
					<div class="form-group">
						<label>Album</label>
						<select name="album_id" class="form-control" required>
                            <?php
                            $crun = mysqli_query($connect, "SELECT * FROM `albums`");
                            while ($rw = mysqli_fetch_assoc($crun)) {
                                echo '
                                            <option value="' . $rw['id'] . '">' . htmlspecialchars($rw['title']) . '</option>
                                    ';
                            }
                            ?>
						</select>
					</div>
					
					<div class="form-group">
						<label>Description</label>
						<textarea class="form-control" id="summernote" name="description"></textarea>
					</div>
                </div>
                <div class="card-footer">
					<input type="submit" name="add" class="btn btn-primary" value="Add" />
                </div>
			</form>
        </div>

    </div></section>
<script>
$(document).ready(function() {
    // Note: Summernote est initialisé dans footer.php via une vérification
    // $(function () { if ($('#summernote').length) { ... } });
});
</script>
<?php
include "footer.php";
?>