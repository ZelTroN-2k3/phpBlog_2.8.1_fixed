<?php
include "core.php";
head();

// 1. Sécurité : Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['sec-username'])) {
    echo '<div class="container mt-5"><div class="alert alert-danger">Please <a href="login.php">login</a> to submit a testimonial.</div></div>';
    footer();
    exit;
}

// Récupérer les infos de l'utilisateur courant
$curr_u = $_SESSION['sec-username'];
$q_u = mysqli_query($connect, "SELECT * FROM users WHERE username='$curr_u'");
$usr = mysqli_fetch_assoc($q_u);

$message = "";

// 2. Traitement du formulaire
if (isset($_POST['submit'])) {
    validate_csrf_token();

    $name     = strip_tags($_POST['name']);
    $position = strip_tags($_POST['position']);
    $content  = strip_tags($_POST['content']);
    $active   = 'Pending'; 
    $avatar_path = '';

    // Gestion Upload Avatar
    if (isset($_FILES['avatar']['name']) && $_FILES['avatar']['name'] != "") {
        $target_dir = "uploads/testimonials/";

        // Vérifier si le dossier existe, sinon le créer
        if (!is_dir($target_dir)) { mkdir($target_dir, 0755, true); }

        $ext = strtolower(pathinfo($_FILES["avatar"]["name"], PATHINFO_EXTENSION));
        $new_name = "user_review_" . uniqid() . "." . $ext;
        $target_file = $target_dir . $new_name;
        
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if(in_array($ext, $allowed)) {
            if (move_uploaded_file($_FILES["avatar"]["tmp_name"], $target_file)) {
                $avatar_path = $target_dir . $new_name;
            } else {
                $message = '<div class="alert alert-danger">Error uploading image.</div>';
            }
        } else {
            $message = '<div class="alert alert-danger">Invalid file format. Only JPG, PNG, GIF allowed.</div>';
        }
    } else {
        // Si pas d'upload, on peut copier l'avatar du profil utilisateur si vous le souhaitez
        $avatar_path = $usr['avatar']; 
        // Ou laisser vide pour l'avatar par défaut
    }

    if (empty($name) || empty($content)) {
        $message = '<div class="alert alert-danger">Name and Content are required.</div>';
    } elseif (strpos($message, 'alert-danger') === false) {
        
        $stmt = mysqli_prepare($connect, "INSERT INTO testimonials (name, position, content, avatar, active) VALUES (?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "sssss", $name, $position, $content, $avatar_path, $active);
        
        if(mysqli_stmt_execute($stmt)) {
            $message = '<div class="alert alert-success"><i class="fas fa-check-circle"></i> Testimonial submitted successfully! It will be visible after Admin approval.</div>';
            $name = $position = $content = "";
        } else {
            $message = '<div class="alert alert-danger">Database error: ' . mysqli_error($connect) . '</div>';
        }
        mysqli_stmt_close($stmt);
    }
}
?>

<div class="container mt-5 mb-5">
    <div class="row justify-content-center">
        
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-star"></i> Add Your Testimonial</h5>
                </div>
                <div class="card-body">
                    
                    <?php echo $message; ?>

                    <form method="post" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Your Name</label>
                                <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($usr['username']); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Position / Job Title (Optional)</label>
                                <input type="text" name="position" class="form-control" placeholder="Ex: Web Designer">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Your Review</label>
                            <textarea name="content" class="form-control" rows="5" required placeholder="Share your experience..."></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Avatar (Optional)</label>
                            <input type="file" name="avatar" class="form-control">
                            <div class="form-text text-muted">Leave empty to use default or your profile picture.</div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" name="submit" class="btn btn-primary">Submit Testimonial</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <?php sidebar(); ?>
        
    </div>
</div>

<?php footer(); ?>