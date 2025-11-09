<?php
include "header.php";

// --- NOUVELLE LOGIQUE : CONSERVATION DU STATUT ---
$status_url_param = ''; // Format &status=...
$status_url_query = ''; // Format ?status=...
$current_status = $_GET['status'] ?? 'all'; // Récupérer le statut pour la présélection

if ($current_status != 'all') {
    $status_param = htmlspecialchars($current_status);
    $status_url_param = '&status=' . $status_param;
    $status_url_query = '?status=' . $status_param;
}
// --- FIN LOGIQUE ---

if (isset($_POST['add'])) {
    
    validate_csrf_token();

    $title    = $_POST['title'];
    $content  = $_POST['content'];
    $position = $_POST['position'];
    $active   = $_POST['active']; // ✨ NOUVEAU CHAMP

    // ✨ MODIFIÉ : Ajout de 'active' à la requête
    $stmt = mysqli_prepare($connect, "INSERT INTO widgets (title, content, position, active) VALUES (?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "ssss", $title, $content, $position, $active);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    // --- MODIFIÉ : Redirection avec statut ---
    echo '<meta http-equiv="refresh" content="0; url=widgets.php' . $status_url_query . '">';
    exit;
}
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0"><i class="fas fa-archive"></i> Add Widget</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="widgets.php<?php echo $status_url_query; ?>">Widgets</a></li>
                    <li class="breadcrumb-item active">Add Widget</li>
                </ol>
            </div>
        </div>
    </div>
</div>
<section class="content">
    <div class="container-fluid">
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title">New Widget Details</h3>
            </div>
            <form action="" method="post">
                <div class="card-body">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <div class="form-group">
                        <label>Title</label>
                        <input class="form-control" name="title" value="" type="text" required>
                    </div>
                    <div class="form-group">
                        <label>Content</label>
                        <textarea class="form-control" id="summernote" name="content" required></textarea>
                    </div>
                    <div class="form-group">
                        <label>Position:</label>
                        <select class="form-control" name="position" required>
                            <option value="Sidebar" selected>Sidebar</option>
                            <option value="Header">Header</option>
                            <option value="Footer">Footer</option>
                        </select>
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
                    <input type="submit" name="add" class="btn btn-primary" value="Add" />
                    <a href="widgets.php<?php echo $status_url_query; ?>" class="btn btn-secondary">Cancel</a>
                </div>
            </form>                          
        </div>

    </div></section>
<?php
include "footer.php";
?>