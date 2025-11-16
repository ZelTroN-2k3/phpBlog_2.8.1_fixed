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

$message = '';
// --- Logique de Traitement (AVANT header.php) ---
if (isset($_POST['add_quiz'])) {
    validate_csrf_token();

    $title = $_POST['title'];
    $purifier = get_purifier();
    $description = $purifier->purify($_POST['description']);
    $difficulty = $_POST['difficulty'];
    $active = $_POST['active'];
    
    $image_url = ''; // Par défaut

    // GESTION DE L'UPLOAD D'IMAGE (Utilise votre fonction d'optimisation)
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $temp_file = $_FILES['image']['tmp_name'];
        
        // --- DÉBUT DU BLOC AJOUTÉ ---
        $upload_dir_full_path = __DIR__ . '/../uploads/quiz'; // Chemin complet du dossier
        if (!is_dir($upload_dir_full_path)) {
            // Tenter de créer le dossier (récursivement)
            if (!mkdir($upload_dir_full_path, 0755, true)) {
                $message = '<div class="alert alert-danger">Erreur critique : Impossible de créer le dossier d\'upload sur le serveur. Vérifiez les permissions.</div>';
            }
        }
        // --- FIN DU BLOC AJOUTÉ ---

        // Continuer seulement si $message est toujours vide
        if (empty($message)) {
            // Créer un nom de fichier unique
            $output_file_base = 'uploads/quiz/quiz_' . time() . '_' . rand(100,999);
            $upload_dir = __DIR__ . '/../'; // Revenir à la racine du site
            
            $optimized_image_path = optimize_and_save_image($temp_file, $upload_dir . $output_file_base);

            if ($optimized_image_path) {
                // Chemin relatif pour la BDD (ex: uploads/quiz/quiz_12345.jpg)
                $image_url = str_replace($upload_dir, '', $optimized_image_path);
            } else {
                $message = '<div class="alert alert-danger">Erreur lors de l\'optimisation de l\'image.</div>';
            }
        } // <-- ACCCOLADE FERMANTE AJOUTÉE
    }

    if (empty($message)) { // Si pas d'erreur d'image
        if (empty($title)) {
            $message = '<div class="alert alert-danger">Le titre ne peut pas être vide.</div>';
        } else {
            $stmt_insert = mysqli_prepare($connect, "INSERT INTO quizzes (title, description, image, difficulty, active) VALUES (?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt_insert, "sssss", $title, $description, $image_url, $difficulty, $active);
            
            if (mysqli_stmt_execute($stmt_insert)) {
                mysqli_stmt_close($stmt_insert);
                header("Location: quizzes.php"); // Redirection
                exit;
            } else {
                $message = '<div class="alert alert-danger">Erreur lors de la création du quiz.</div>';
            }
        }
    }
}

// 3. INCLURE LE HEADER HTML
include 'header.php';
?>

<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Créer un nouveau Quiz</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="dashboard.php">Accueil</a></li>
                    <li class="breadcrumb-item"><a href="quizzes.php">Quiz Manager</a></li>
                    <li class="breadcrumb-item active">Créer</li>
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
                        <h3 class="card-title">Détails du Quiz</h3>
                    </div>
                    
                    <?php echo $message; // Afficher les messages ?>

                    <form method="POST" action="add_quiz.php" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        
                        <div class="card-body">
                            <div class="form-group">
                                <label for="title">Titre du Quiz</label>
                                <input type="text" class="form-control" id="title" name="title" placeholder="Entrez le titre..." required>
                            </div>
                            
                            <div class="form-group">
                                <label for="image">Image d'en-tête (Optionnel)</label>
                                <div class="input-group">
                                    <div class="custom-file">
                                        <input type="file" class="custom-file-input" id="image" name="image" accept="image/jpeg,image/png,image/gif,image/webp">
                                        <label class="custom-file-label" for="image">Choisir un fichier...</label>
                                    </div>
                                </div>
                                <small class="text-muted">Sera redimensionnée et convertie en .jpg.</small>
                            </div>

                            <div class="form-group">
                                <label for="difficulty">Niveau de difficulté</label>
                                <select class="form-control" id="difficulty" name="difficulty">
                                    <option value="FACILE">Facile</option>
                                    <option value="NORMAL" selected>Normal</option>
                                    <option value="DIFFICILE">Difficile</option>
                                    <option value="EXPERT">Expert</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="description">Description (Optionnel)</label>
                                <textarea id="summernote" name="description" class="form-control" style="height: 200px;"></textarea>
                            </div>

                            <div class="form-group">
                                <label for="active">Statut</label>
                                <select class="form-control" id="active" name="active">
                                    <option value="Yes" selected>Publié (Active)</option>
                                    <option value="No">Brouillon (Inactive)</option>
                                </select>
                            </div>
                        </div>

                        <div class="card-footer">
                            <button type="submit" name="add_quiz" class="btn btn-primary">Enregistrer le Quiz</button>
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