<?php
include "header.php";

// Sécurité Admin
if ($user['role'] != "Admin") {
    echo '<meta http-equiv="refresh" content="0; url=dashboard.php" />'; exit;
}

if (isset($_POST['add_user'])) {
    validate_csrf_token();

    $username = $_POST['username'];
    $email    = $_POST['email'];
    $password = $_POST['password'];
    $role     = $_POST['role'];
    $avatar   = '';

    // Validations
    if (strlen($username) < 3) {
        echo '<div class="alert alert-danger m-3">The username must contain at least 3 characters.</div>';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo '<div class="alert alert-danger m-3">The email address is not valid.</div>';
    } elseif (strlen($password) < 5) {
        echo '<div class="alert alert-danger m-3">The password must contain at least 5 characters.</div>';
    } else {
        
        // 1. Vérifier doublon
        $stmt_check = mysqli_prepare($connect, "SELECT id FROM users WHERE username = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt_check, "s", $username);
        mysqli_stmt_execute($stmt_check);
        mysqli_stmt_store_result($stmt_check);
        
        if (mysqli_stmt_num_rows($stmt_check) > 0) {
            echo '<div class="alert alert-warning m-3">This username is already taken.</div>';
            mysqli_stmt_close($stmt_check);
        } else {
            mysqli_stmt_close($stmt_check);

            // 2. Gestion Avatar (Optionnel)
            if (isset($_FILES['avatar']['name']) && $_FILES['avatar']['name'] != "") {
                $target_dir = "../uploads/avatars/";
                if (!file_exists($target_dir)) { mkdir($target_dir, 0777, true); }
                
                $ext = strtolower(pathinfo($_FILES["avatar"]["name"], PATHINFO_EXTENSION));
                $new_name = "user_" . uniqid() . "." . $ext;
                
                // Utilisation de votre fonction d'optimisation si dispo
                if (function_exists('optimize_and_save_image')) {
                    $optimized_path = optimize_and_save_image($_FILES["avatar"]["tmp_name"], $target_dir . "user_" . uniqid());
                    if ($optimized_path) $avatar = str_replace("../", "", $optimized_path);
                } else {
                    // Fallback
                    move_uploaded_file($_FILES["avatar"]["tmp_name"], $target_dir . $new_name);
                    $avatar = "uploads/avatars/" . $new_name;
                }
            }

            // 3. Insertion
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = mysqli_prepare($connect, "INSERT INTO users (username, email, password, role, avatar) VALUES (?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "sssss", $username, $email, $password_hash, $role, $avatar);
            
            if (mysqli_stmt_execute($stmt)) {
                echo '<div class="alert alert-success m-3">User created successfully! Redirecting...</div>';
                echo '<meta http-equiv="refresh" content="1; url=users.php">';
                exit;
            } else {
                echo '<div class="alert alert-danger m-3">Error creating user.</div>';
            }
            mysqli_stmt_close($stmt);
        }
    }
}
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0"><i class="fas fa-user-plus"></i> Create User</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="users.php">Users</a></li>
                    <li class="breadcrumb-item active">Add</li>
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
                            <h3 class="card-title">Account Details</h3>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label>Username</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    </div>
                                    <input class="form-control" name="username" type="text" placeholder="Enter username" required>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Email Address</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                    </div>
                                    <input class="form-control" name="email" type="email" placeholder="email@example.com" required>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Password</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    </div>
                                    <input class="form-control" name="password" type="password" placeholder="Min. 5 characters" required>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 col-md-12">
                    <div class="card card-secondary">
                        <div class="card-header">
                            <h3 class="card-title">Permissions & Profile</h3>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label>Role</label>
                                <select name="role" class="form-control" required>
                                    <option value="User" selected>User (Comment only)</option>
                                    <option value="Editor">Editor (Manage posts)</option>
                                    <option value="Admin">Admin (Full access)</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Avatar (Optional)</label>
                                <div class="custom-file">
                                    <input type="file" class="custom-file-input" id="avatarUpload" name="avatar">
                                    <label class="custom-file-label" for="avatarUpload">Choose file</label>
                                </div>
                            </div>
                            <div class="mt-2 text-center" id="preview-container" style="display:none;">
                                <img id="image-preview" src="#" class="img-circle elevation-2" style="width: 80px; height: 80px; object-fit: cover;">
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" name="add_user" class="btn btn-primary btn-block">
                                <i class="fas fa-check"></i> Create User
                            </button>
                            <a href="users.php" class="btn btn-default btn-block">Cancel</a>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</section>

<?php include "footer.php"; ?>
<script>
$(document).ready(function() {
    $('#avatarUpload').on('change', function() {
        var fileName = $(this).val().split('\\').pop();
        $(this).next('.custom-file-label').addClass("selected").html(fileName);
        if (this.files && this.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                $('#image-preview').attr('src', e.target.result);
                $('#preview-container').slideDown();
            }
            reader.readAsDataURL(this.files[0]);
        }
    });
});
</script>