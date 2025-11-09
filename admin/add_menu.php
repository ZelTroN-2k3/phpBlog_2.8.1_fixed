<?php
include "header.php";

// --- LOGIQUE : CONSERVATION DU STATUT ---
$status_url_param = ''; // Format &status=...
$status_url_query = ''; // Format ?status=...
$current_status = $_GET['status'] ?? 'all'; // Récupérer le statut pour la présélection

if ($current_status != 'all') {
    $status_param = htmlspecialchars($current_status);
    $status_url_param = '&status=' . $status_param;
    $status_url_query = '?status=' . $status_param;
}
// --- FIN LOGIQUE ---

if (isset($_POST['submit'])) {
    validate_csrf_token();
    
    $page    = $_POST['page'];
    $path    = $_POST['path'];
    $fa_icon = $_POST['fa_icon'];
    $active  = $_POST['active']; // ✨ NOUVEAU CHAMP
    
    // ✨ MODIFIÉ : Ajout de 'active' à la requête
    $stmt = mysqli_prepare($connect, "INSERT INTO menu (page, path, fa_icon, active) VALUES (?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "ssss", $page, $path, $fa_icon, $active);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    // --- MODIFIÉ : Redirection avec statut ---
    echo '<meta http-equiv="refresh" content="0;url=menu_editor.php' . $status_url_query . '">'; 
    exit;
}
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0"><i class="fa fa-plus"></i> Add Menu Item</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="menu_editor.php<?php echo $status_url_query; ?>">Menu Editor</a></li>
                    <li class="breadcrumb-item active">Add Menu Item</li>
                </ol>
            </div>
        </div>
    </div>
</div>
<section class="content">
    <div class="container-fluid">
        <div class="card card-primary card-outline mb-3">
          <div class="card-header">
            <h3 class="card-title">Add Menu Item</h3>
          </div>         
            <form action="" method="post"> 
            <div class="card-body">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <div class="form-group">
              	    <label>Page Title</label>
              	    <input name="page" class="form-control" type="text" value="" required>
                </div>
                <div class="form-group">
              	    <label>Path (Link)</label>
              	    <input name="path" class="form-control" type="text" value="" required>
                    <small class="form-text text-muted">Exemple: <code>page?name=votre-slug-de-page</code> ou <code>blog.php</code></small>
                </div>
                <div class="form-group">
              	    <label>Font Awesome 5 Icon</label>
              	    <input name="fa_icon" class="form-control" type="text" value="fa-link">
                    <small class="form-text text-muted">Exemple: <code>fa-home</code> ou <code>fas fa-user</code></small>
                </div>
                
                <div class="form-group">
                    <label>Status</label>
                    <select class="form-control" name="active" required>
                        <option value="Yes" <?php if ($current_status == 'published' || $current_status == 'all') echo 'selected'; ?>>Published</option>
                        <option value="No" <?php if ($current_status == 'draft') echo 'selected'; ?>>Draft</option>
                    </select>
                </div>
                
            </div>
            <div class="card-footer">
                <input type="submit" class="btn btn-success" name="submit" value="Save" />
                <a href="menu_editor.php<?php echo $status_url_query; ?>" class="btn btn-secondary">Cancel</a>
            </div>
            </form>
        </div>
    </div>
</section>
<?php
include "footer.php";
?>