<?php
include_once "../core.php"; 

// --- LOGIQUE SUPPRESSION ---
if (isset($_GET['delete-id'])) {
    validate_csrf_token_get();
    $id = (int) $_GET["delete-id"];
    
    // Suppression de l'image associée si elle existe
    $q = mysqli_query($connect, "SELECT image FROM categories WHERE id='$id'");
    $r = mysqli_fetch_assoc($q);
    if (!empty($r['image']) && file_exists("../" . $r['image'])) {
        unlink("../" . $r['image']);
    }

    // Suppression BDD
    $stmt = mysqli_prepare($connect, "DELETE FROM `posts` WHERE category_id=?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $stmt->close();

    $stmt = mysqli_prepare($connect, "DELETE FROM `categories` WHERE id=?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $stmt->close();
    
    header("Location: categories.php");
    exit;
}

// --- LOGIQUE MODIFICATION (POST) ---
if (isset($_POST['submit_edit'])) {
    validate_csrf_token();
    
    $id = (int)$_POST['edit_id'];
    $category = $_POST['category'];
    $description = $_POST['description'];
    $slug = generateSeoURL($category, 0);
    
    // Gestion Image
    $image_sql_part = ""; 
    $types = "ssi"; // Types pour bind_param par défaut (string, string, int)
    $params = array($category, $description, $id); // Valeurs par défaut
    
    if (isset($_FILES['image']['name']) && $_FILES['image']['name'] != "") {
        // Upload nouvelle image
        $target_dir = "../uploads/categories/";
        if (!file_exists($target_dir)) { mkdir($target_dir, 0777, true); }
        $image_name = time() . "_" . basename($_FILES["image"]["name"]);
        $target_file = $target_dir . $image_name;
        
        if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
            $new_image_path = "uploads/categories/" . $image_name;
            
            // Supprimer l'ancienne image
            $old_q = mysqli_query($connect, "SELECT image FROM categories WHERE id='$id'");
            $old_r = mysqli_fetch_assoc($old_q);
            if (!empty($old_r['image']) && file_exists("../" . $old_r['image'])) {
                unlink("../" . $old_r['image']);
            }

            // Préparer la requête avec image
            $stmt = mysqli_prepare($connect, "UPDATE categories SET category=?, description=?, slug=?, image=? WHERE id=?");
            mysqli_stmt_bind_param($stmt, "ssssi", $category, $description, $slug, $new_image_path, $id);
        }
    } else {
        // Pas de changement d'image
        $stmt = mysqli_prepare($connect, "UPDATE categories SET category=?, description=?, slug=? WHERE id=?");
        mysqli_stmt_bind_param($stmt, "sssi", $category, $description, $slug, $id);
    }

    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    header("Location: categories.php");
    exit;
}

include "header.php";
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0"><i class="fas fa-list-ol"></i> Categories</h1>
            </div>
             <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                    <li class="breadcrumb-item active">Categories</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
      
<?php
// --- FORMULAIRE D'EDITION ---
if (isset($_GET['edit-id'])) {
    $id  = (int) $_GET["edit-id"];
    $stmt = mysqli_prepare($connect, "SELECT * FROM `categories` WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $sql = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($sql);
    mysqli_stmt_close($stmt);
    
    if ($row) {
?>
            <div class="card card-primary card-outline mb-3">
                <div class="card-header">
                    <h3 class="card-title">Edit Category: <b><?php echo htmlspecialchars($row['category']); ?></b></h3>
                </div>
                <div class="card-body">
                    <form action="categories.php" method="post" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <input type="hidden" name="edit_id" value="<?php echo $id; ?>">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Category Name</label>
                                    <input class="form-control" name="category" type="text" value="<?php echo htmlspecialchars($row['category']); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>Description</label>
                                    <textarea class="form-control" name="description" rows="4"><?php echo htmlspecialchars($row['description']); ?></textarea>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Current Image</label><br>
                                    <?php if(!empty($row['image'])) { ?>
                                        <img src="../<?php echo $row['image']; ?>" class="img-thumbnail mb-2" style="max-height: 150px;">
                                    <?php } else { echo '<p class="text-muted">No image uploaded.</p>'; } ?>
                                </div>
                                <div class="form-group">
                                    <label>Change Image</label>
                                    <div class="custom-file">
                                        <input type="file" name="image" class="custom-file-input">
                                        <label class="custom-file-label">Choose new file (optional)</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <input type="submit" class="btn btn-success" name="submit_edit" value="Save Changes" />
                        <a href="categories.php" class="btn btn-secondary">Cancel</a>
                    </form>
                </div>
            </div>
<?php
    }
}
?>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <a href="add_category.php" class="btn btn-primary"><i class="fa fa-plus-circle"></i> Add Category</a>
                    </h3>
                </div>
                <div class="card-body">
                    <table class="table table-bordered table-striped table-hover" id="dt-basic">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th class="text-center" style="width: 50px;">Img</th>
                            <th>Name / Description</th>
                            <th>Slug (URL)</th>
                            <th class="text-center">Posts</th>
                            <th class="text-center">Actions</th>
                        </tr>
                        </thead>
                        <tbody>
<?php
$sql = "SELECT * FROM categories ORDER BY category ASC";
$result = mysqli_query($connect, $sql);

while ($row = mysqli_fetch_assoc($result)) {
    // Compter le nombre d'articles dans cette catégorie
    $cat_id = $row['id'];
    $count_sql = mysqli_query($connect, "SELECT COUNT(*) as total FROM posts WHERE category_id = '$cat_id'");
    $count_data = mysqli_fetch_assoc($count_sql);
    $article_count = $count_data['total'];
    
    $badge_color = ($article_count > 0) ? 'badge-info' : 'badge-secondary';

    $img_display = !empty($row['image']) 
        ? '<img src="../' . $row['image'] . '" style="width: 50px; height: 50px; object-fit: cover;" class="rounded">' 
        : '<span class="text-muted"><i class="fas fa-image"></i></span>';

    echo '
        <tr>
            <td style="width: 50px;">' . $row['id'] . '</td>
            <td class="text-center">' . $img_display . '</td>
            <td>
                <b>' . htmlspecialchars($row['category']) . '</b><br>
                <small class="text-muted">' . htmlspecialchars(substr($row['description'], 0, 60)) . (strlen($row['description']) > 60 ? '...' : '') . '</small>
            </td>
            <td><small class="text-muted">/category?name= </small><code><span class="badge ' . $badge_color . '">' . htmlspecialchars($row['slug']) . '</code></td>
            <td class="text-center"><span class="badge ' . $badge_color . '">' . $article_count . '</span></td>
            <td class="text-center">
                <a href="../category?name=' . $row['slug'] . '" target="_blank" class="btn btn-secondary btn-sm"><i class="fas fa-eye"></i></a>
                <a href="?edit-id=' . $row['id'] . '" class="btn btn-primary btn-sm"><i class="fa fa-edit"></i></a>
                <a href="?delete-id=' . $row['id'] . '&token=' . $csrf_token . '" class="btn btn-danger btn-sm" onclick="return confirm(\'WARNING: Deleting this category will DELETE ALL ' . $article_count . ' POSTS inside it. Are you sure?\');" title="Delete"><i class="fa fa-trash"></i></a>
            </td>
        </tr>';
}
?>
                        </tbody>
                    </table>
                </div>
            </div>

    </div>
</section>
<?php include "footer.php"; ?>