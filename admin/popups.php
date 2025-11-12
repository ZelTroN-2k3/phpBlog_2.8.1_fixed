<?php
include "header.php"; // Inclut votre header admin (connexion, BDD, $settings, etc.)

$message = ''; // Pour les messages de succès ou d'erreur

// --- LOGIQUE 1 : SUPPRIMER UN POPUP ---
if (isset($_GET['delete_id'])) {
    validate_csrf_token_get(); // Vérifie le jeton de sécurité de l'URL
    
    $id_to_delete = (int)$_GET['delete_id'];
    
    $stmt = mysqli_prepare($connect, "DELETE FROM popups WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id_to_delete);
    
    if (mysqli_stmt_execute($stmt)) {
        $message = '<div class="alert alert-success alert-dismissible">
                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                        <h5><i class="icon fas fa-check"></i> Success!</h5>
                        Popup deleted successfully.
                    </div>';
    } else {
        $message = '<div class="alert alert-danger">Error deleting popup.</div>';
    }
    mysqli_stmt_close($stmt);
}

// --- LOGIQUE 2 : ACTIVER/DÉSACTIVER UN POPUP ---
if (isset($_GET['toggle_id'])) {
    validate_csrf_token_get(); // Vérifie le jeton de sécurité de l'URL
    
    $id_to_toggle = (int)$_GET['toggle_id'];
    
    // Inverser le statut actuel
    $stmt = mysqli_prepare($connect, "UPDATE popups SET active = IF(active='Yes', 'No', 'Yes') WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id_to_toggle);
    
    if (mysqli_stmt_execute($stmt)) {
        $message = '<div class="alert alert-success alert-dismissible">
                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                        <h5><i class="icon fas fa-check"></i> Success!</h5>
                        Popup status updated.
                    </div>';
    } else {
        $message = '<div class="alert alert-danger">Error updating popup status.</div>';
    }
    mysqli_stmt_close($stmt);
}

// --- LOGIQUE 3 : RÉCUPÉRER TOUS LES POPUPS ---
$popups = [];
$result = mysqli_query($connect, "SELECT * FROM popups ORDER BY id DESC");
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $popups[] = $row;
    }
}
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0"><i class="fas fa-window-maximize"></i> All Popups</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                    <li class="breadcrumb-item active">Popups</li>
                </ol>
                <a href="add_popup.php" class="btn btn-primary float-right mt-2"><i class="fas fa-plus"></i> Add Popup</a>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        
        <?php echo $message; // Affiche les messages de succès ou d'erreur ?>

        <div class="row">
            <div class="col-md-12">
                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <h3 class="card-title">List of Popups</h3>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th style="width: 10px">#ID</th>
                                    <th>Title</th>
                                    <th>Status</th>
                                    <th>Display</th>
                                    <th>Delay</th>
                                    <th style="width: 250px">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($popups)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center">No popups created yet.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($popups as $popup): ?>
                                        <tr>
                                            <td><?php echo (int)$popup['id']; ?></td>
                                            <td><?php echo htmlspecialchars($popup['title']); ?></td>
                                            <td>
                                                <?php if ($popup['active'] == 'Yes'): ?>
                                                    <span class="badge bg-success">Active</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Inactive</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($popup['display_pages']); ?></td>
                                            <td><?php echo (int)$popup['delay_seconds']; ?>s</td>
                                            <td>
                                                <?php
                                                // Préparer les URL pour les actions
                                                $token_url = '&token=' . $_SESSION['csrf_token'];
                                                $toggle_url = '?toggle_id=' . (int)$popup['id'] . $token_url;
                                                $delete_url = '?delete_id=' . (int)$popup['id'] . $token_url;
                                                $edit_url = 'edit_popup.php?id=' . (int)$popup['id'];
                                                
                                                // Changer le bouton en fonction du statut
                                                $toggle_class = ($popup['active'] == 'Yes') ? 'btn-warning' : 'btn-success';
                                                $toggle_icon = ($popup['active'] == 'Yes') ? 'fa-eye-slash' : 'fa-eye';
                                                $toggle_text = ($popup['active'] == 'Yes') ? ' Désactiver' : ' Activer';
                                                ?>
                                                
                                                <a href="<?php echo $edit_url; ?>" class="btn btn-primary btn-sm"><i class="fas fa-edit"></i> Edit</a>
                                                <a href="<?php echo $toggle_url; ?>" class="btn <?php echo $toggle_class; ?> btn-sm"><i class="fas <?php echo $toggle_icon; ?>"></i><?php echo $toggle_text; ?></a>
                                                <a href="<?php echo $delete_url; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this popup?');">
                                                   <i class="fas fa-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
include "footer.php";
?>