<?php
include "header.php";

// 1. Vérification ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo '<meta http-equiv="refresh" content="0; url=categories.php">';
    exit;
}

$id = (int)$_GET['id'];

// 2. Récupération données
$stmt = mysqli_prepare($connect, "SELECT * FROM `categories` WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$row) {
    echo '<div class="alert alert-danger m-3">Category not found.</div>';
    exit;
}

// 3. Traitement Formulaire
if (isset($_POST['submit'])) {
    validate_csrf_token();
    
    $category_name = $_POST['category'];
    $description   = $_POST['description'];
    // Génération automatique du slug si le nom change
    $slug = generateSeoURL($category_name);
    
    // Gestion Image
    $image_path = $row['image']; // Par défaut, on garde l'ancienne
    
    if (isset($_FILES['image']['name']) && $_FILES['image']['name'] != "") {
        $target_dir = "../uploads/categories/";
        if (!file_exists($target_dir)) { mkdir($target_dir, 0777, true); }
        
        $imageFileType = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
        $new_name = "cat_" . time() . "_" . uniqid() . "." . $imageFileType;
        $target_file = $target_dir . $new_name;
        
        $check = getimagesize($_FILES["image"]["tmp_name"]);
        if($check !== false) {
            if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                // Suppression de l'ancienne image si elle existe et n'est pas vide
                if (!empty($row['image']) && file_exists("../" . $row['image'])) {
                    unlink("../" . $row['image']);
                }
                $image_path = "uploads/categories/" . $new_name;
            } else {
                echo '<div class="alert alert-danger">Error uploading file.</div>';
            }
        } else {
            echo '<div class="alert alert-warning">File is not an image.</div>';
        }
    }
    
    // Update SQL
    $stmt_update = mysqli_prepare($connect, "UPDATE categories SET category=?, slug=?, description=?, image=? WHERE id=?");
    mysqli_stmt_bind_param($stmt_update, "ssssi", $category_name, $slug, $description, $image_path, $id);
    mysqli_stmt_execute($stmt_update);
    mysqli_stmt_close($stmt_update);
    
    echo '<div class="alert alert-success m-3">Category updated successfully! Redirecting...</div>';
    echo '<meta http-equiv="refresh" content="1; url=categories.php">';
    exit;
}
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0"><i class="fas fa-edit"></i> Edit Category</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="categories.php">Categories</a></li>
                    <li class="breadcrumb-item active">Edit</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <form action="" method="post" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            
            <div class="row">
                <div class="col-lg-8 col-md-12">
                    <div class="card card-primary card-outline">
                        <div class="card-header">
                            <h3 class="card-title">Category Details</h3>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label>Category Name</label>
                                <input type="text" class="form-control" name="category" value="<?php echo htmlspecialchars($row['category']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label>Slug (URL)</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($row['slug']); ?>" disabled>
                                <small class="text-muted">Slug is automatically updated based on the name.</small>
                            </div>

                            <div class="form-group">
                                <label>Description</label>
                                <textarea class="form-control" name="description" rows="5"><?php echo htmlspecialchars($row['description']); ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 col-md-12">
                    
                    <div class="card card-secondary">
                        <div class="card-header">
                            <h3 class="card-title">Featured Image</h3>
                        </div>
                        <div class="card-body text-center">
                            <?php if ($row['image'] != ''): ?>
                                <div class="mb-3">
                                    <img src="../<?php echo htmlspecialchars($row['image']); ?>" class="img-fluid rounded" style="max-height: 150px; border: 1px solid #ddd; padding: 2px;">
                                </div>
                            <?php else: ?>
                                <div class="alert alert-light text-center border mb-3">No Image Set</div>
                            <?php endif; ?>
                            
                            <div class="form-group text-left">
                                <label>Change Image</label>
                                <div class="custom-file">
                                    <input type="file" class="custom-file-input" name="image" id="catImage">
                                    <label class="custom-file-label" for="catImage">Choose file</label>
                                </div>
                                <small class="text-muted">Leave empty to keep current image.</small>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <button type="submit" name="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-save"></i> Save Changes
                            </button>
                            <a href="categories.php" class="btn btn-default btn-block">Cancel</a>
                        </div>
                    </div>
                    
                </div>
            </div>
        </form>
    </div>
</section>

<script>
// Petit script pour afficher le nom du fichier dans l'input Bootstrap
$(document).ready(function() {
    $(".custom-file-input").on("change", function() {
        var fileName = $(this).val().split("\\").pop();
        $(this).siblings(".custom-file-label").addClass("selected").html(fileName);
    });
});
</script>

<?php include "footer.php"; ?>