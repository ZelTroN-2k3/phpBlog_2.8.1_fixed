<?php
// 1. INCLURE LE NOYAU D'ABORD
include_once '../core.php'; 

// 2. VÉRIFICATION DE SÉCURITÉ
if (isset($_SESSION['sec-username'])) {
    $uname = $_SESSION['sec-username'];
    $stmt = mysqli_prepare($connect, "SELECT * FROM `users` WHERE username=? AND role='Admin'");
    mysqli_stmt_bind_param($stmt, "s", $uname);
    mysqli_stmt_execute($stmt);
    $suser = mysqli_stmt_get_result($stmt);
    if (mysqli_num_rows($suser) <= 0) {
        header("Location: " . $settings['site_url']); exit;
    }
    $user = mysqli_fetch_assoc($suser);
} else {
    header("Location: ../login"); exit;
}
// --- FIN SÉCURITÉ ---

// --- Vérification de l'ID ---
if (!isset($_GET['id']) && !isset($_POST['quiz_id'])) {
    header("Location: quizzes.php"); exit;
}
$quiz_id = isset($_GET['id']) ? (int)$_GET['id'] : (int)$_POST['quiz_id'];
$message = '';
$quiz_data = null;

// --- Logique de Traitement (POST) ---
if (isset($_POST['edit_quiz'])) {
    validate_csrf_token();

    $title = $_POST['title'];
    $purifier = get_purifier();
    $description = $purifier->purify($_POST['description']);
    $difficulty = $_POST['difficulty'];
    $active = $_POST['active'];
    $image_url = $_POST['current_image']; // Image existante

    // GESTION DE L'UPLOAD (si une nouvelle image est fournie)
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        
        // --- DÉBUT DU BLOC AJOUTÉ ---
        $upload_dir_full_path = __DIR__ . '/../uploads/quiz'; // Chemin complet du dossier
        if (!is_dir($upload_dir_full_path)) {
            if (!mkdir($upload_dir_full_path, 0755, true)) {
                $message = '<div class="alert alert-danger">Critical error: Unable to create the upload folder on the server. Check the permissions.</div>';
            }
        }
        // --- FIN DU BLOC AJOUTÉ ---

        // Continuer seulement si $message est toujours vide
        if (empty($message)) {
            $temp_file = $_FILES['image']['tmp_name'];
            $output_file_base = 'uploads/quiz/quiz_' . $quiz_id . '_' . time(); // Nom basé sur l'ID
            $upload_dir = __DIR__ . '/../'; 
            
            $optimized_image_path = optimize_and_save_image($temp_file, $upload_dir . $output_file_base);

            if ($optimized_image_path) {
                $image_url = str_replace($upload_dir, '', $optimized_image_path);
                // On pourrait supprimer l'ancienne image ici
            } else {
                $message = '<div class="alert alert-danger">Error optimizing the image.</div>';
            }
        } // <-- ACCCOLADE FERMANTE AJOUTÉE
    }

    if (empty($message)) {
        if (empty($title)) {
            $message = '<div class="alert alert-danger">The title cannot be empty.</div>';
        } else {
            $stmt_update = mysqli_prepare($connect, "UPDATE quizzes SET title = ?, description = ?, image = ?, difficulty = ?, active = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt_update, "sssssi", $title, $description, $image_url, $difficulty, $active, $quiz_id);
            
            if (mysqli_stmt_execute($stmt_update)) {
                mysqli_stmt_close($stmt_update);
                header("Location: quizzes.php"); // Redirection
                exit;
            } else {
                $message = '<div class="alert alert-danger">Error updating the quiz.</div>';
            }
        }
    }
}

// --- Logique d'Affichage (GET) ---
$stmt_get = mysqli_prepare($connect, "SELECT * FROM quizzes WHERE id = ?");
mysqli_stmt_bind_param($stmt_get, "i", $quiz_id);
mysqli_stmt_execute($stmt_get);
$result_get = mysqli_stmt_get_result($stmt_get);
$quiz_data = mysqli_fetch_assoc($result_get);
mysqli_stmt_close($stmt_get);

if (!$quiz_data) {
    include 'header.php';
    echo '<section class="content"><div class="alert alert-danger">Quiz not found.</div></section>';
    include 'footer.php';
    exit;
}

// 3. INCLURE LE HEADER HTML
include 'header.php';
?>

<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Edit Quiz</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="quizzes.php">Quiz Manager</a></li>
                    <li class="breadcrumb-item active">Edit</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">Editing: <?php echo htmlspecialchars($quiz_data['title']); ?></h3>
                    </div>
                    
                    <?php echo $message; ?>

                    <form method="POST" action="edit_quiz.php" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <input type="hidden" name="quiz_id" value="<?php echo $quiz_id; ?>">
                        <input type="hidden" name="current_image" value="<?php echo htmlspecialchars($quiz_data['image']); ?>">
                        
                        <div class="card-body">
                            <div class="form-group">
                                <label for="title">Quiz Title</label>
                                <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($quiz_data['title']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="image">Header Image (Optional)</label>
                                <?php if(!empty($quiz_data['image'])): ?>
                                    <div class="mb-2">
                                        <img src="../<?php echo htmlspecialchars($quiz_data['image']); ?>" alt="Image" style="width: 200px; height: auto; border-radius: 4px;">
                                    </div>
                                <?php endif; ?>
                                <div class="input-group">
                                    <div class="custom-file">
                                        <input type="file" class="custom-file-input" id="image" name="image" accept="image/jpeg,image/png,image/gif,image/webp">
                                        <label class="custom-file-label" for="image">Replace image...</label>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="difficulty">Difficulty Level</label>
                                <select class="form-control" id="difficulty" name="difficulty">
                                    <option value="FACILE" <?php if ($quiz_data['difficulty'] == 'FACILE') echo 'selected'; ?>>Easy</option>
                                    <option value="NORMAL" <?php if ($quiz_data['difficulty'] == 'NORMAL') echo 'selected'; ?>>Normal</option>
                                    <option value="DIFFICILE" <?php if ($quiz_data['difficulty'] == 'DIFFICILE') echo 'selected'; ?>>Hard</option>
                                    <option value="EXPERT" <?php if ($quiz_data['difficulty'] == 'EXPERT') echo 'selected'; ?>>Expert</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="description">Description (Optional)</label>
                                <textarea id="summernote" name="description" class="form-control" style="height: 200px;"><?php echo htmlspecialchars($quiz_data['description']); ?></textarea>
                            </div>

                            <div class="form-group">
                                <label for="active">Status</label>
                                <select class="form-control" id="active" name="active">
                                    <option value="Yes" <?php if ($quiz_data['active'] == 'Yes') echo 'selected'; ?>>Published (Active)</option>
                                    <option value="No" <?php if ($quiz_data['active'] == 'No') echo 'selected'; ?>>Draft (Inactive)</option>
                                </select>
                            </div>
                        </div>

                        <div class="card-footer">
                            <button type="submit" name="edit_quiz" class="btn btn-primary">Update Quiz</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
include 'footer.php';
?>

<script src="assets/adminlte/plugins/bs-custom-file-input/bs-custom-file-input.min.js"></script>
<script>
$(document).ready(function() {
    bsCustomFileInput.init();
    
    $('#summernote').summernote({
        height: 200,
        toolbar: [
            ['style', ['style']],
            ['font', ['bold', 'italic', 'underline']],
            ['para', ['ul', 'ol', 'paragraph']],
            ['view', ['fullscreen', 'codeview']]
        ]
    });
});
</script>