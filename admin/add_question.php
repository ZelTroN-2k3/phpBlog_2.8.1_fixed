<?php
// 1. INCLURE LE NOYAU D'ABORD
include_once '../core.php'; 

// 2. VÉRIFICATION DE SÉCURITÉ
if (isset($_SESSION['sec-username'])) {
    $uname = $_SESSION['sec-username'];
    $stmt = mysqli_prepare($connect, "SELECT * FROM `users` WHERE username=? AND (role='Admin' OR role='Editor')");
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

// --- NOUVEAU : Récupérer l'ID du Quiz ---
if (!isset($_GET['quiz_id']) && !isset($_POST['quiz_id'])) {
    header("Location: quizzes.php");
    exit;
}
$quiz_id = isset($_GET['quiz_id']) ? (int)$_GET['quiz_id'] : (int)$_POST['quiz_id'];
// --- FIN ---

$message = '';
// --- Logique de Traitement (AVANT header.php) ---
if (isset($_POST['add_question'])) {
    validate_csrf_token(); 

    $question = $_POST['question'];
    
    $purifier = get_purifier();
    $explanation = $purifier->purify($_POST['explanation']); 
    
    $active = $_POST['active'];
    $options = $_POST['options'] ?? [];
    $correct_option_index = $_POST['is_correct'] ?? -1; 

    if (empty($question)) {
        $message = '<div class="alert alert-danger">La question ne peut pas être vide.</div>';
    } elseif (count($options) < 2) {
        $message = '<div class="alert alert-danger">Vous devez ajouter au moins deux options de réponse.</div>';
    } elseif ($correct_option_index == -1) {
        $message = '<div class="alert alert-danger">Veuillez marquer une réponse comme correcte.</div>';
    } else {
        
        // REQUÊTE MISE À JOUR : Ajout de quiz_id
        $stmt_question = mysqli_prepare($connect, "INSERT INTO quiz_questions (quiz_id, question, explanation, active, position_order) VALUES (?, ?, ?, ?, 0)");
        mysqli_stmt_bind_param($stmt_question, "isss", $quiz_id, $question, $explanation, $active);
        
        if (mysqli_stmt_execute($stmt_question)) {
            $question_id = mysqli_insert_id($connect); 
            mysqli_stmt_close($stmt_question);

            // Logique d'insertion des options (inchangée)
            $stmt_option = mysqli_prepare($connect, "INSERT INTO quiz_options (question_id, title, is_correct) VALUES (?, ?, ?)");
            $all_options_inserted = true;
            foreach ($options as $index => $title) {
                if (empty(trim($title))) continue; 
                $is_correct = ($index == $correct_option_index) ? 'Yes' : 'No';
                $option_title = $title;
                mysqli_stmt_bind_param($stmt_option, "iss", $question_id, $option_title, $is_correct);
                if (!mysqli_stmt_execute($stmt_option)) {
                    $all_options_inserted = false;
                }
            }
            mysqli_stmt_close($stmt_option);

            if ($all_options_inserted) {
                // REDIRECTION MISE À JOUR : Revenir à la liste des questions de ce quiz
                header("Location: quiz_questions.php?quiz_id=" . $quiz_id);
                exit;
            } else {
                $message = '<div class="alert alert-danger">Erreur lors de l\'ajout des options.</div>';
            }

        } else {
            $message = '<div class="alert alert-danger">Erreur lors de l\'ajout de la question.</div>';
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
                <h1>Ajouter une Question</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="dashboard.php">Accueil</a></li>
                    <li class="breadcrumb-item"><a href="quizzes.php">Quiz Manager</a></li>
                    <li class="breadcrumb-item"><a href="quiz_questions.php?quiz_id=<?php echo $quiz_id; ?>">Questions</a></li>
                    <li class="breadcrumb-item active">Ajouter</li>
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
                        <h3 class="card-title">Nouvelle Question</h3>
                    </div>
                    
                    <?php echo $message; ?>

                    <form method="POST" action="add_question.php">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <input type="hidden" name="quiz_id" value="<?php echo $quiz_id; ?>">
                        <input type="hidden" name="add_question" value="1"> <div class="card-body">
                            <div class="form-group">
                                <label for="question">Question</label>
                                <input type="text" class="form-control" id="question" name="question" placeholder="Entrez la question..." required>
                            </div>

                            <div class="form-group">
                                <label>Options de réponse</label>
                                <div id="options-container"></div>
                                <button type="button" id="add-option-btn" class="btn btn-sm btn-success mt-2">
                                    <i class="fas fa-plus"></i> Ajouter une option
                                </button>
                            </div>
                            
                            <div class="form-group">
                                <label for="explanation">Explication (Affichée après la réponse)</label>
                                <textarea id="summernote" name="explanation" class="form-control" style="height: 200px;"></textarea>
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
                            <button type="submit" class="btn btn-primary">Enregistrer la Question</button>
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

<script>
$(document).ready(function() {
    $('#summernote').summernote({
        height: 200,
        toolbar: [
            ['style', ['style']],
            ['font', ['bold', 'italic', 'underline', 'clear']],
            ['color', ['color']],
            ['para', ['ul', 'ol', 'paragraph']],
            ['table', ['table']],
            ['insert', ['link', 'picture', 'video']],
            ['view', ['fullscreen', 'codeview', 'help']]
        ]
    });

    let optionIndex = 0;

    function addOption() {
        const optionHtml = `
            <div class="input-group mb-2" id="option-group-${optionIndex}">
                <div class="input-group-prepend">
                    <span class="input-group-text">
                        <input type="radio" name="is_correct" value="${optionIndex}" required title="Marquer comme bonne réponse">
                    </span>
                </div>
                <input type="text" name="options[${optionIndex}]" class="form-control" placeholder="Texte de la réponse..." required>
                <div class="input-group-append">
                    <button type="button" class="btn btn-danger remove-option-btn" data-index="${optionIndex}">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `;
        $('#options-container').append(optionHtml);
        optionIndex++;
    }

    $('#add-option-btn').click(function() { addOption(); });

    $(document).on('click', '.remove-option-btn', function() {
        const index = $(this).data('index');
        $('#option-group-' + index).remove();
    });

    addOption();
    addOption();
    $('input[name="is_correct"][value="0"]').prop('checked', true);
});
</script>