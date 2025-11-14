<?php
include "header.php";

if (!isset($_GET['id'])) exit;
$id = (int)$_GET['id'];

// Fetch info
$q = mysqli_query($connect, "SELECT * FROM testimonials WHERE id=$id");
$row = mysqli_fetch_assoc($q);
if(!$row) { echo "Not found"; exit; }

if (isset($_POST['submit'])) {
    validate_csrf_token();
    
    $name     = $_POST['name'];
    $position = $_POST['position'];
    $content  = $_POST['content'];
    $active   = $_POST['active'];
    $avatar_path = $row['avatar']; // Garder l'ancien par défaut

    // Gestion Upload Image
    if (isset($_FILES['avatar']['name']) && $_FILES['avatar']['name'] != "") {
        $target_dir = "../uploads/testimonials/";
        $ext = strtolower(pathinfo($_FILES["avatar"]["name"], PATHINFO_EXTENSION));
        $new_name = "review_" . uniqid() . "." . $ext;
        $target_file = $target_dir . $new_name;
        
        if(in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            if (move_uploaded_file($_FILES["avatar"]["tmp_name"], $target_file)) {
                // Supprimer l'ancienne image si elle existe
                if (!empty($row['avatar']) && file_exists("../" . $row['avatar'])) {
                    unlink("../" . $row['avatar']);
                }
                $avatar_path = "uploads/testimonials/" . $new_name;
            }
        }
    }

    $stmt = mysqli_prepare($connect, "UPDATE testimonials SET name=?, position=?, content=?, avatar=?, active=? WHERE id=?");
    mysqli_stmt_bind_param($stmt, "sssssi", $name, $position, $content, $avatar_path, $active, $id);
    
    if(mysqli_stmt_execute($stmt)) {
        echo '<meta http-equiv="refresh" content="0; url=testimonials.php">';
        exit;
    }
}
?>

<div class="content-header">
    <div class="container-fluid">
        <h1 class="m-0">Edit Testimonial</h1>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            
            <div class="card card-primary">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Name</label>
                                <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($row['name']); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Position / Role</label>
                                <input type="text" name="position" class="form-control" value="<?php echo htmlspecialchars($row['position']); ?>">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Testimonial</label>
                        <textarea name="content" class="form-control" rows="4" required><?php echo htmlspecialchars($row['content']); ?></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Avatar</label><br>
                                <?php if($row['avatar']): ?>
                                    <img src="../<?php echo $row['avatar']; ?>" width="60" class="img-circle mb-2"><br>
                                <?php endif; ?>
                                <input type="file" name="avatar" class="form-control-file">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Status</label>
                                <select name="active" class="form-control">
                                    <option value="Yes" <?php if($row['active']=='Yes') echo 'selected'; ?>>Active</option>
                                    <option value="No" <?php if($row['active']=='No') echo 'selected'; ?>>Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" name="submit" class="btn btn-primary">Update</button>
                    <a href="testimonials.php" class="btn btn-secondary">Cancel</a>
                </div>
            </div>
        </form>
    </div>
</section>

<?php include "footer.php"; ?>