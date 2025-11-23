<?php
include "header.php";

$msg = ''; 

if (isset($_POST['add'])) {
    validate_csrf_token();

    $category = trim($_POST['category']);
    $description = $_POST['description']; // Récupération de la description
    $slug = generateSeoURL($category, 0);
    
    // --- Gestion de l'image ---
    $image_path = "";
    if (isset($_FILES['image']['name']) && $_FILES['image']['name'] != "") {
        $target_dir = "../uploads/categories/";
        if (!file_exists($target_dir)) { mkdir($target_dir, 0777, true); } // Créer le dossier si inexistant
        
        $image_name = time() . "_" . basename($_FILES["image"]["name"]);
        $target_file = $target_dir . $image_name;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        
        // Validation simple
        $check = getimagesize($_FILES["image"]["tmp_name"]);
        if($check !== false) {
            if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                $image_path = "uploads/categories/" . $image_name; // Chemin relatif pour la BDD
            } else {
                $msg = '<div class="alert alert-danger">Sorry, there was an error uploading your file.</div>';
            }
        } else {
            $msg = '<div class="alert alert-danger">File is not an image.</div>';
        }
    }
    // --------------------------

    if(empty($category)){
         $msg = '<div class="alert alert-danger">Category name cannot be empty.</div>';
    } else if (empty($msg)) { // S'il n'y a pas eu d'erreur d'upload
        // Vérification doublon
        $stmt = mysqli_prepare($connect, "SELECT category FROM `categories` WHERE category=? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "s", $category);
        mysqli_stmt_execute($stmt);
        $stmt->store_result();  // Nécessaire pour num_rows avec prepared statement
        
        if (mysqli_stmt_num_rows($stmt) > 0) {
        $msg = '
            <div class="alert alert-warning alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                <h5><i class="icon fas fa-exclamation-triangle"></i> Error!</h5>
                A category with this name already exists.
            </div>';
        } else {
            // Insertion avec description et image
            $stmt_ins = mysqli_prepare($connect, "INSERT INTO categories (category, slug, description, image) VALUES (?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt_ins, "ssss", $category, $slug, $description, $image_path);
            mysqli_stmt_execute($stmt_ins);
            mysqli_stmt_close($stmt_ins);
            
            echo '<meta http-equiv="refresh" content="0;url=categories.php">';
            exit; 
        }
        mysqli_stmt_close($stmt);
    }
}
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0"><i class="fas fa-folder-plus"></i> Add Category</h1>
            </div>
             <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="categories.php">Categories</a></li>
                    <li class="breadcrumb-item active">Add</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <?php echo $msg; ?>
        <div class="col-md-8 mx-auto">
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h3 class="card-title">New Category</h3>
                </div>
                <form action="" method="post" enctype="multipart/form-data">
                    <div class="card-body">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        
                        <div class="form-group">
                            <label for="catInput">Category Name</label>
                            <input id="catInput" class="form-control" name="category" type="text" required autofocus>
                        </div>

                        <div class="form-group">
                            <label>Description</label>
                            <textarea class="form-control" name="description" rows="3" placeholder="Short description for this category..."></textarea>
                        </div>

                        <div class="form-group">
                            <label>Category Image</label>
                            <div class="input-group">
                                <div class="custom-file">
                                    <input type="file" name="image" class="custom-file-input" id="exampleInputFile">
                                    <label class="custom-file-label" for="exampleInputFile">Choose file</label>
                                </div>
                            </div>
                            <small class="text-muted">Recommended size: 800x400px (JPG/PNG)</small>
                        </div>

                    </div>
                    <div class="card-footer text-right">
                        <a href="categories.php" class="btn btn-default">Cancel</a>
                        <button type="submit" name="add" class="btn btn-primary"><i class="fas fa-save"></i> Create Category</button>
                    </div>
                </form>                           
            </div>
        </div>
    </div>
</section>
<?php include "footer.php"; ?>