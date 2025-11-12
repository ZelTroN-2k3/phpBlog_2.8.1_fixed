<?php
include "header.php"; // Inclut votre header admin

$message = ''; // Pour les messages

// --- LOGIQUE DE SAUVEGARDE ---
if (isset($_POST['save_maintenance'])) {
    validate_csrf_token(); // Sécurité

    // Récupérer les données
    $mode = $_POST['maintenance_mode'];
    $title = $_POST['maintenance_title'];
    $content = $_POST['maintenance_message']; // Contenu HTML

    // Préparer les requêtes de mise à jour (une par une, c'est plus simple ici)
    $stmt1 = mysqli_prepare($connect, "UPDATE settings SET maintenance_mode = ? WHERE id = 1");
    mysqli_stmt_bind_param($stmt1, "s", $mode);
    mysqli_stmt_execute($stmt1);
    mysqli_stmt_close($stmt1);
    
    $stmt2 = mysqli_prepare($connect, "UPDATE settings SET maintenance_title = ? WHERE id = 1");
    mysqli_stmt_bind_param($stmt2, "s", $title);
    mysqli_stmt_execute($stmt2);
    mysqli_stmt_close($stmt2);
    
    $stmt3 = mysqli_prepare($connect, "UPDATE settings SET maintenance_message = ? WHERE id = 1");
    mysqli_stmt_bind_param($stmt3, "s", $content);
    mysqli_stmt_execute($stmt3);
    mysqli_stmt_close($stmt3);

    // Afficher un message de succès
    $message = '<div class="alert alert-success alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                    <h5><i class="icon fas fa-check"></i> Success!</h5>
                    Maintenance settings have been saved.
                </div>';
                
    // Recharger les settings pour afficher les nouvelles valeurs
    $settings_result = mysqli_query($connect, "SELECT * FROM settings WHERE id = 1");
    $settings = mysqli_fetch_assoc($settings_result);
}
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0"><i class="fas fa-tools"></i> Maintenance Mode</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                    <li class="breadcrumb-item active">Maintenance</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        
        <?php echo $message; // Affiche les messages de succès ou d'erreur ?>

        <div class="row">
            <div class="col-md-12">
                <form action="maintenance.php" method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    
                    <div class="card card-primary card-outline">
                        <div class="card-header">
                            <h3 class="card-title">Manage Site Status</h3>
                        </div>
                        <div class="card-body">
                        
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label><i class="fas fa-power-off text-danger"></i> Maintenance Mode</label>
                                        <select name="maintenance_mode" class="form-control" required>
                                            <option value="Off" <?php if ($settings['maintenance_mode'] == 'Off') echo 'selected'; ?>>Off (Public Site)</option>
                                            <option value="On" <?php if ($settings['maintenance_mode'] == 'On') echo 'selected'; ?>>On (Site Locked)</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label><i class="fas fa-heading"></i> Maintenance Page Title</label>
                                        <input type="text" name="maintenance_title" class="form-control" value="<?php echo htmlspecialchars($settings['maintenance_title']); ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="summernote">MMaintenance Message (HTML allowed)</label>
                                <textarea id="summernote" name="maintenance_message"><?php echo htmlspecialchars($settings['maintenance_message']); ?></textarea>
                            </div>

                        </div>
                        <div class="card-footer">
                            <button type="submit" name="save_maintenance" class="btn btn-primary"><i class="fas fa-save"></i> Save</button>
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
      height: 250, // Hauteur de l'éditeur
      toolbar: [
          ['style', ['style']],
          ['font', ['bold', 'italic', 'underline', 'clear']],
          ['color', ['color']],
          ['para', ['ul', 'ol', 'paragraph']],
          ['insert', ['link', 'picture', 'video']],
          ['view', ['fullscreen', 'codeview']]
      ]
    });
  });
</script>