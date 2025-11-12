<?php
include "header.php"; // Inclut votre header admin

$message = ''; // Pour les messages de succès ou d'erreur

// --- LOGIQUE DE SAUVEGARDE DU NOUVEAU POPUP ---
if (isset($_POST['save_popup'])) {
    validate_csrf_token(); // Vérifie le jeton de sécurité

    // Récupérer les données du formulaire
    $title = $_POST['title'];
    $content = $_POST['content']; // Contenu HTML
    $active = $_POST['active']; // 'Yes' ou 'No'
    $display_pages = $_POST['display_pages']; // 'home' ou 'all'
    $show_once = $_POST['show_once_per_session']; // 'Yes' ou 'No'
    $delay = (int)$_POST['delay_seconds']; // int

    // Validation simple
    if (empty($title) || empty($content)) {
        $message = '<div class="alert alert-danger">The Title and the Content cannot be empty.</div>';
    } else {
        // Préparer la requête d'insertion
        $stmt = mysqli_prepare($connect, "
            INSERT INTO popups (title, content, active, display_pages, show_once_per_session, delay_seconds) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        // "sssssi" = String, String, String, String, String, Integer
        mysqli_stmt_bind_param($stmt, "sssssi", 
            $title, 
            $content, 
            $active, 
            $display_pages, 
            $show_once, 
            $delay
        );
        
        if (mysqli_stmt_execute($stmt)) {
            // Succès ! Rediriger vers la liste des popups
            echo '<meta http-equiv="refresh" content="0;url=popups.php?success=added">';
            exit;
        } else {
            $message = '<div class="alert alert-danger">Error during saving: ' . mysqli_error($connect) . '</div>';
        }
        mysqli_stmt_close($stmt);
    }
}
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0"><i class="fas fa-plus-circle"></i> Add a Popup</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="popups.php">Popups</a></li>
                    <li class="breadcrumb-item active">Add</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        
        <?php echo $message; // Affiche les messages d'erreur s'il y en a ?>

        <div class="row">
            <div class="col-md-12">
                <form action="add_popup.php" method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    
                    <div class="card card-primary card-outline">
                        <div class="card-header">
                            <h3 class="card-title">Popup Details</h3>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label for="popup_title">Title (for administration)</label>
                                <input type="text" name="title" id="popup_title" class="form-control" placeholder="Ex: Christmas Announcement" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="summernote">Popup Content</label>
                                <textarea id="summernote" name="content"></textarea>
                            </div>

                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Status</label>
                                        <select name="active" class="form-control">
                                            <option value="No">Disabled (Draft)</option>
                                            <option value="Yes">Enabled (Visible)</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Display On</label>
                                        <select name="display_pages" class="form-control">
                                            <option value="home">Home Page Only</option>
                                            <option value="all">All Pages</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Display (per visitor)</label>
                                        <select name="show_once_per_session" class="form-control">
                                            <option value="Yes">Once per session</option>
                                            <option value="No">Every page load</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Display Delay (seconds)</label>
                                        <input type="number" name="delay_seconds" class="form-control" value="2" min="0">
                                    </div>
                                </div>
                            </div>

                        </div>
                        <div class="card-footer">
                            <button type="submit" name="save_popup" class="btn btn-primary"><i class="fas fa-save"></i> Save Popup</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<?php
include "footer.php";
?>

<script>
  $(function () {
    // Initialiser Summernote
    $('#summernote').summernote({
      height: 300,                 // hauteur de l'éditeur
      toolbar: [
          ['style', ['style']],
          ['font', ['bold', 'italic', 'underline', 'clear']],
          ['fontname', ['fontname']],
          ['color', ['color']],
          ['para', ['ul', 'ol', 'paragraph']],
          ['height', ['height']],
          ['table', ['table']],
          ['insert', ['link', 'picture', 'video', 'hr']],
          ['view', ['fullscreen', 'codeview']],
          ['help', ['help']]
      ]
    });
  });
</script>