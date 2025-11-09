<?php
include "header.php";

// Seuls les Admins peuvent ajouter des utilisateurs
if ($user['role'] != "Admin") {
    echo '<meta http-equiv="refresh" content="0; url=dashboard.php" />';
    exit;
}

$error_message = '';

if (isset($_POST['add_user'])) {
    $username = $_POST['username'];
    $email    = $_POST['email'];
    $password = $_POST['password'];
    $role     = $_POST['role'];

    // Validation basique
    if (strlen($username) < 3) {
        $error_message = '<div class="alert alert-danger">The username must contain at least 3 characters.</div>';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = '<div class="alert alert-danger">The email address is not valid.</div>';
    } elseif (strlen($password) < 5) {
        $error_message = '<div class="alert alert-danger">The password must contain at least 5 characters.</div>';
    } else {
        
        // 1. Vérifier si le nom d'utilisateur existe
        $stmt_user = mysqli_prepare($connect, "SELECT id FROM users WHERE username = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt_user, "s", $username);
        mysqli_stmt_execute($stmt_user);
        $result_user = mysqli_stmt_get_result($stmt_user);
        
        // 2. Vérifier si l'email existe
        $stmt_email = mysqli_prepare($connect, "SELECT id FROM users WHERE email = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt_email, "s", $email);
        mysqli_stmt_execute($stmt_email);
        $result_email = mysqli_stmt_get_result($stmt_email);

        if (mysqli_num_rows($result_user) > 0) {
            // Utiliser la classe AdminLTE pour les alertes
            $error_message = '
                <div class="alert alert-warning alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                    <h5><i class="icon fas fa-exclamation-triangle"></i> Warning!</h5>
                    This username is already taken.
                </div>';
        } elseif (mysqli_num_rows($result_email) > 0) {
            // Utiliser la classe AdminLTE pour les alertes
            $error_message = '
                <div class="alert alert-warning alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                    <h5><i class="icon fas fa-exclamation-triangle"></i> Warning!</h5>
                    This email address is already in use.
                </div>';
        } else {
            // 3. Hasher le mot de passe
            $password_hashed = password_hash($password, PASSWORD_DEFAULT);
            
            // 4. Insérer l'utilisateur
            $stmt_insert = mysqli_prepare($connect, "INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt_insert, "ssss", $username, $email, $password_hashed, $role);
            mysqli_stmt_execute($stmt_insert);
            mysqli_stmt_close($stmt_insert);
            
            // Rediriger vers la liste des utilisateurs
            echo '<meta http-equiv="refresh" content="0; url=users.php">';
            exit;
        }
        
        mysqli_stmt_close($stmt_user);
        mysqli_stmt_close($stmt_email);
    }
}
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0"><i class="fas fa-user-plus"></i> Add User</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="users.php">Users</a></li>
                    <li class="breadcrumb-item active">Add User</li>
                </ol>
            </div>
        </div>
    </div>
</div>
<section class="content">
    <div class="container-fluid">
        
        <?php echo $error_message; ?>

        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title">Add New User</h3>
            </div>
            <form action="" method="post">
            <div class="card-body">
                <div class="form-group">
                    <label>Username</label>
                    <input class="form-control" name="username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" type="text" required>
                </div>
                <div class="form-group">
                    <label>Email Address</label>
                    <input class="form-control" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" type="email" required>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input class="form-control" name="password" value="" type="password" required>
                </div>
                <div class="form-group">
                    <label>Role</label>
                    <select name="role" class="form-control" required>
                        <option value="User" selected>User</option>
                        <option value="Editor">Editor</option>
                        <option value="Admin">Admin</option>
                    </select>
                </div>
            </div>
            <div class="card-footer">
                <input type="submit" name="add_user" class="btn btn-primary col-12" value="Create User" />
            </div>
            </form>
        </div>

    </div>
</section>
<?php
include "footer.php";
?>