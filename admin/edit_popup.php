<?php
include "header.php"; // Inclut votre header admin

// --- 1. VÉRIFICATION DE L'ID ---
// S'assurer qu'un ID est passé et qu'il est valide
$id = (int)$_GET['id'] ?? 0;
if ($id === 0) {
    // Si pas d'ID, rediriger vers la liste
    echo '<meta http-equiv="refresh" content="0;url=popups.php">';
    exit;
}

$message = ''; // Pour les messages

// --- 2. LOGIQUE DE MISE À JOUR DU POPUP ---
if (isset($_POST['update_popup'])) {
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
        // Préparer la requête de MISE À JOUR (UPDATE)
        $stmt = mysqli_prepare($connect, "
            UPDATE popups 
            SET title = ?, content = ?, active = ?, display_pages = ?, show_once_per_session = ?, delay_seconds = ?
            WHERE id = ?
        ");
        
        // "sssssii" = 6 champs + l'ID
        mysqli_stmt_bind_param($stmt, "sssssii", 
            $title, 
            $content, 
            $active, 
            $display_pages, 
            $show_once, 
            $delay,
            $id // L'ID à la fin pour le WHERE
        );
        
        if (mysqli_stmt_execute($stmt)) {
            // Succès ! Rediriger vers la liste des popups
            echo '<meta http-equiv="refresh" content="0;url=popups.php?success=updated">';
            exit;
        } else {
            $message = '<div class="alert alert-danger">Error during saving: ' . mysqli_error($connect) . '</div>';
        }
        mysqli_stmt_close($stmt);
    }
}

// --- 3. CHARGER LES DONNÉES DU POPUP EXISTANT ---
// (On fait cela après la logique d'update, pour avoir les données à jour si l'update échoue)
$stmt = mysqli_prepare($connect, "SELECT * FROM popups WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$popup = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

// Si le popup avec cet ID n'existe pas, on redirige
if (!$popup) {
    echo '<meta http-equiv="refresh" content="0;url=popups.php?error=notfound">';
    exit;
}

// Sécuriser le contenu pour l'injecter dans le JavaScript
$safe_content_for_js = json_encode($popup['content']);
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0"><i class="fas fa-edit"></i> Edit the Popup #<?php echo $id; ?></h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="popups.php">Popups</a></li>
                    <li class="breadcrumb-item active">Edit</li>
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
                <form action="edit_popup.php?id=<?php echo $id; ?>" method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    
                    <div class="card card-primary card-outline">
                        <div class="card-header">
                            <h3 class="card-title">Popup Details</h3>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label for="popup_title">Title (for administration)</label>
                                <input type="text" name="title" id="popup_title" class="form-control" value="<?php echo htmlspecialchars($popup['title']); ?>" required>
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
                                            <option value="No" <?php if($popup['active'] == 'No') echo 'selected'; ?>>Disabled (Draft)</option>
                                            <option value="Yes" <?php if($popup['active'] == 'Yes') echo 'selected'; ?>>Enabled (Visible)</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Display On</label>
                                        <select name="display_pages" class="form-control">
                                            <option value="home" <?php if($popup['display_pages'] == 'home') echo 'selected'; ?>>Home Page Only</option>
                                            <option value="all" <?php if($popup['display_pages'] == 'all') echo 'selected'; ?>>All Pages</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Display (per visitor)</label>
                                        <select name="show_once_per_session" class="form-control">
                                            <option value="Yes" <?php if($popup['show_once_per_session'] == 'Yes') echo 'selected'; ?>>Once per session</option>
                                            <option value="No" <?php if($popup['show_once_per_session'] == 'No') echo 'selected'; ?>>Every page load</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Display Delay (seconds)</label>
                                        <input type="number" name="delay_seconds" class="form-control" value="<?php echo (int)$popup['delay_seconds']; ?>" min="0">
                                    </div>
                                </div>
                            </div>

                        </div>
                        <div class="card-footer">
                            <button type="submit" name="update_popup" class="btn btn-primary"><i class="fas fa-save"></i> Update</button>
                            <a href="popups.php" class="btn btn-secondary">Cancel</a>
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
      height: 300,
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
    
    // Charger le contenu existant dans Summernote
    // Nous utilisons le contenu JSON-encodé et sécurisé
    $('#summernote').summernote('code', <?php echo $safe_content_for_js; ?>);
  });
</script>