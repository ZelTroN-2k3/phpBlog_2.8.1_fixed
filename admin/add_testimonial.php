<?php
include "header.php";

if (isset($_POST['submit'])) {
    validate_csrf_token();
    
    $name     = $_POST['name'];
    $position = $_POST['position'];
    $content  = $_POST['content'];
    $active   = $_POST['active'];
    $avatar_path = '';

    // Gestion Upload Image
    if (isset($_FILES['avatar']['name']) && $_FILES['avatar']['name'] != "") {
        $target_dir = "../uploads/testimonials/";
        $ext = strtolower(pathinfo($_FILES["avatar"]["name"], PATHINFO_EXTENSION));
        // Nom unique
        $new_name = "review_" . uniqid() . "." . $ext;
        $target_file = $target_dir . $new_name;
        
        if(in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            if (move_uploaded_file($_FILES["avatar"]["tmp_name"], $target_file)) {
                $avatar_path = "uploads/testimonials/" . $new_name;
            }
        }
    }

    $stmt = mysqli_prepare($connect, "INSERT INTO testimonials (name, position, content, avatar, active) VALUES (?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "sssss", $name, $position, $content, $avatar_path, $active);
    
    if(mysqli_stmt_execute($stmt)) {
        echo '<meta http-equiv="refresh" content="0; url=testimonials.php">';
        exit;
    } else {
        echo '<div class="alert alert-danger">Error: ' . mysqli_error($connect) . '</div>';
    }
}
?>

<div class="content-header">
    <div class="container-fluid">
        <h1 class="m-0">Add Testimonial</h1>
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
                                <input type="text" name="name" class="form-control" required placeholder="Ex: John Doe">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Position / Role</label>
                                <input type="text" name="position" class="form-control" placeholder="Ex: Web Developer">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Testimonial</label>
                        <textarea name="content" class="form-control" rows="4" required placeholder="What did they say?"></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Avatar (Optional)</label>
                                <input type="file" name="avatar" class="form-control-file">
                                <small class="text-muted">Square image recommended (100x100).</small>
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
                    <a href="testimonials.php" class="btn btn-secondary">Cancel</a>
                </div>
            </div>
        </form>
    </div>
</section>

<?php include "footer.php"; ?>